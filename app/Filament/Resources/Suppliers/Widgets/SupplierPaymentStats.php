<?php

namespace App\Filament\Resources\Suppliers\Widgets;

use App\Models\Supplier;
use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SupplierPaymentStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $latestTransactions = Transaction::query()
            ->where('transactionable_type', Supplier::class)
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('transactions')
                    ->where('transactionable_type', Supplier::class)
                    ->groupBy('transactionable_id');
            })
            ->get();

        $pendingAmount = $latestTransactions
            ->where('amount_balance', '<', 0)
            ->sum('amount_balance');

        $suppliersToBePaid = $latestTransactions
            ->where('amount_balance', '<', 0)
            ->count();

        $totalSuppliers = $latestTransactions->count();

        // Chart data for last 6 months
        $months = collect(range(0, 5))->map(fn ($i) => Carbon::now()->subMonths($i)->format('Y-m'))->reverse();

        $transactions = Transaction::query()
            ->where('transactionable_type', Supplier::class)
            ->where('created_at', '>=', Carbon::now()->subMonths(5)->startOfMonth())
            ->where('amount_balance', '<', 0)
            ->get();

        $pendingAmountChart = $months->map(function ($month) use ($transactions) {
            return $transactions
                ->filter(fn ($transaction) => $transaction->created_at->format('Y-m') === $month)
                ->sum('amount_balance');
        })->toArray();

        $suppliersToBePaidChart = $months->map(function ($month) use ($transactions) {
            return $transactions
                ->filter(fn ($transaction) => $transaction->created_at->format('Y-m') === $month)
                ->unique('transactionable_id')
                ->count();
        })->toArray();

        return [
            Stat::make('Pending Amount', number_format($pendingAmount, 2))
                ->description('Total amount to be paid')
                ->color('warning')
                ->chart($pendingAmountChart),
            Stat::make('Suppliers to be Paid', $suppliersToBePaid)
                ->description('Suppliers with pending balances')
                ->color('info')
                ->chart($suppliersToBePaidChart),
            Stat::make('Suppliers with Transactions', $totalSuppliers)
                ->description('Suppliers with any transaction')
                ->color('success'),
        ];
    }

    public static function getPendingAmountStatForPeriod(Carbon $startDate, Carbon $endDate): array
    {
        $latestTransactions = Transaction::query()
            ->where('transactionable_type', Supplier::class)
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->whereIn('id', function ($query) use ($startDate, $endDate) {
                $query->selectRaw('MAX(id)')
                    ->from('transactions')
                    ->where('transactionable_type', Supplier::class)
                    ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                    ->groupBy('transactionable_id');
            })
            ->get();

        $pendingAmount = $latestTransactions->sum('amount_balance');

        $chartDays = collect(range(0, 6))->map(
            fn ($i) => $endDate->copy()->subDays(6 - $i)->toDateString()
        );

        $transactions = Transaction::query()
            ->where('transactionable_type', Supplier::class)
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->get();

        $chartData = $chartDays->map(function ($date) use ($transactions) {
            return $transactions
                ->filter(fn ($transaction) => $transaction->created_at->toDateString() === $date)
                ->sum('amount');
        })->toArray();

        // Invert the chart for correct visual direction
        $chartData = array_map(fn ($v) => -1 * $v, $chartData);

        return [
            'value' => $pendingAmount,
            'chart' => $chartData,
        ];
    }
}
