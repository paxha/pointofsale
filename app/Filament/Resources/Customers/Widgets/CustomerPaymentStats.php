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

        $months = collect(range(0, 5))->map(function ($i) {
            return Carbon::now()->subMonths($i)->format('Y-m');
        })->reverse();

        $pendingAmountChart = $months->map(function ($month) {
            return Transaction::query()
                ->where('transactionable_type', Customer::class)
                ->whereRaw("strftime('%Y-%m', created_at) = ?", [$month])
                ->where('amount_balance', '>', 0)
                ->sum('amount_balance');
        })->toArray();

        $customersToBeReceivedChart = $months->map(function ($month) {
            return Transaction::query()
                ->where('transactionable_type', Customer::class)
                ->whereRaw("strftime('%Y-%m', created_at) = ?", [$month])
                ->where('amount_balance', '>', 0)
                ->distinct('transactionable_id')
                ->count('transactionable_id');
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
}
