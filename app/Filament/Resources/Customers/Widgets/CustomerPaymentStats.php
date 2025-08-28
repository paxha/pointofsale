<?php

namespace App\Filament\Resources\Customers\Widgets;

use App\Models\Customer;
use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerPaymentStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $latestTransactions = Transaction::query()
            ->where('transactionable_type', Customer::class)
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('transactions')
                    ->where('transactionable_type', Customer::class)
                    ->groupBy('transactionable_id');
            })
            ->get();

        $pendingAmount = $latestTransactions
            ->where('amount_balance', '>', 0)
            ->sum('amount_balance');

        $customersToBeReceived = $latestTransactions
            ->where('amount_balance', '>', 0)
            ->count();

        $totalCustomers = $latestTransactions->count();

        $months = collect(range(0, 5))->map(fn ($i) => Carbon::now()->subMonths($i)->format('Y-m'))->reverse();

        $transactions = Transaction::query()
            ->where('transactionable_type', Customer::class)
            ->where('created_at', '>=', Carbon::now()->subMonths(5)->startOfMonth())
            ->where('amount_balance', '>', 0)
            ->get();

        $pendingAmountChart = $months->map(function ($month) use ($transactions) {
            return $transactions
                ->filter(fn ($transaction) => $transaction->created_at->format('Y-m') === $month)
                ->sum('amount_balance');
        })->toArray();

        $customersToBeReceivedChart = $months->map(function ($month) use ($transactions) {
            return $transactions
                ->filter(fn ($transaction) => $transaction->created_at->format('Y-m') === $month)
                ->unique('transactionable_id')
                ->count();
        })->toArray();

        return [
            Stat::make('Pending Amount', number_format($pendingAmount, 2))
                ->description('Total amount to be received')
                ->color('warning')
                ->chart($pendingAmountChart),
            Stat::make('Customers to be Received', $customersToBeReceived)
                ->description('Customers with pending balances')
                ->color('info')
                ->chart($customersToBeReceivedChart),
            Stat::make('Customers with Transactions', $totalCustomers)
                ->description('Customers with any transaction')
                ->color('success'),
        ];
    }

    public static function getPendingAmountStatForPeriod($startDate, $endDate): array
    {
        $pendingAmount = Transaction::query()
            ->where('transactionable_type', Customer::class)
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->sum('amount_balance') / 100;

        $sparklineDays = collect(range(0, 6))->map(
            fn ($i) => $endDate->copy()->subDays(6 - $i)->toDateString()
        );

        $transactions = Transaction::query()
            ->where('transactionable_type', Customer::class)
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->get();

        $chart = $sparklineDays->map(function ($date) use ($transactions) {
            return $transactions
                ->filter(fn ($transaction) => $transaction->created_at->toDateString() === $date)
                ->sum('amount_balance') / 100;
        })->toArray();

        return [
            'value' => $pendingAmount,
            'chart' => $chart,
        ];
    }
}
