<?php

namespace App\Filament\Widgets;

use App\Enums\SalePaymentStatus;
use App\Enums\SaleStatus;
use App\Filament\Resources\Customers\Widgets\CustomerPaymentStats;
use App\Filament\Resources\Suppliers\Widgets\SupplierPaymentStats;
use App\Models\Sale;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;

class StatsOverviewWidget extends BaseStatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        $startDate = !is_null($this->pageFilters['startDate'] ?? null)
            ? Carbon::parse($this->pageFilters['startDate'])
            : now()->subMonth();

        $endDate = !is_null($this->pageFilters['endDate'] ?? null)
            ? Carbon::parse($this->pageFilters['endDate'])
            : now();

        $days = $startDate->diffInDays($endDate) + 1;

        // Revenue stat
        $revenue = Sale::query()
                ->where('status', SaleStatus::Completed->value)
                ->whereIn('payment_status', [
                    SalePaymentStatus::Paid->value,
                    SalePaymentStatus::Credit->value,
                ])
                ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                ->sum('total') / 100;

        $prevStart = $startDate->copy()->subDays($days);
        $prevEnd = $startDate->copy()->subDay();

        $prevRevenue = Sale::query()
                ->where('status', SaleStatus::Completed->value)
                ->whereIn('payment_status', [
                    SalePaymentStatus::Paid->value,
                    SalePaymentStatus::Credit->value,
                ])
                ->whereBetween('created_at', [$prevStart->startOfDay(), $prevEnd->endOfDay()])
                ->sum('total') / 100;

        $absChange = $revenue - $prevRevenue;
        $percentChange = $prevRevenue > 0 ? ($absChange / $prevRevenue) * 100 : 0;

        $sparklineDays = collect(range(0, 6))->map(
            fn($i) => $endDate->copy()->subDays(6 - $i)->toDateString()
        );

        $sparklineData = $sparklineDays->map(function ($date) {
            return Sale::query()
                    ->where('status', SaleStatus::Completed->value)
                    ->whereIn('payment_status', [
                        SalePaymentStatus::Paid->value,
                        SalePaymentStatus::Credit->value,
                    ])
                    ->whereDate('created_at', $date)
                    ->sum('total') / 100;
        })->toArray();

        $revenueFormatted = $this->formatCompactNumber($revenue);
        $absChangeFormatted = $this->formatCompactNumber($absChange, true);
        $percentChangeFormatted = number_format(abs($percentChange), 1) . '%';
        $isIncrease = $absChange >= 0;

        $description = $isIncrease
            ? "{$absChangeFormatted} ({$percentChangeFormatted}) increase"
            : "{$absChangeFormatted} ({$percentChangeFormatted}) decrease";

        // Pending Amount stat with chart and trend
        $pendingStatData = SupplierPaymentStats::getPendingAmountStatForPeriod($startDate, $endDate);
        $pendingAmount = $pendingStatData['value'];
        $pendingAmountFormatted = $this->formatCompactNumber($pendingAmount, true);

        $prevPendingStatData = SupplierPaymentStats::getPendingAmountStatForPeriod($prevStart, $prevEnd);
        $prevPendingAmount = $prevPendingStatData['value'];

        $pendingAbsChange = $pendingAmount - $prevPendingAmount;
        $pendingPercentChange = $prevPendingAmount != 0
            ? ($pendingAbsChange / $prevPendingAmount) * 100
            : 0;

        $pendingAbsChangeFormatted = $this->formatCompactNumber($pendingAbsChange, true);
        $pendingPercentChangeFormatted = number_format(abs($pendingPercentChange), 1) . '%';

        $pendingIsIncrease = $pendingAbsChange > 0;

        $pendingDescription = $pendingIsIncrease
            ? "{$pendingAbsChangeFormatted} ({$pendingPercentChangeFormatted}) decrease"
            : "{$pendingAbsChangeFormatted} ({$pendingPercentChangeFormatted}) increase";

        $customerPendingStatData = CustomerPaymentStats::getPendingAmountStatForPeriod($startDate, $endDate);
        $customerPendingAmount = $customerPendingStatData['value'];
        $customerPendingAmountFormatted = $this->formatCompactNumber($customerPendingAmount, true);

        // Customer Amount stat with chart and trend
        $customerPendingStatData = CustomerPaymentStats::getPendingAmountStatForPeriod($startDate, $endDate);
        $customerPendingAmount = $customerPendingStatData['value'];
        $customerPendingAmountFormatted = $this->formatCompactNumber($customerPendingAmount, true);

        $prevCustomerPendingStatData = CustomerPaymentStats::getPendingAmountStatForPeriod($prevStart, $prevEnd);
        $prevCustomerPendingAmount = $prevCustomerPendingStatData['value'];

        $customerPendingAbsChange = $customerPendingAmount - $prevCustomerPendingAmount;
        $customerPendingPercentChange = $prevCustomerPendingAmount != 0
            ? ($customerPendingAbsChange / $prevCustomerPendingAmount) * 100
            : 0;

        $customerPendingAbsChangeFormatted = $this->formatCompactNumber($customerPendingAbsChange, true);
        $customerPendingPercentChangeFormatted = number_format(abs($customerPendingPercentChange), 1) . '%';

        $customerPendingIsIncrease = $customerPendingAbsChange > 0;

        $customerPendingDescription = $customerPendingIsIncrease
            ? "{$customerPendingAbsChangeFormatted} ({$customerPendingPercentChangeFormatted}) increase"
            : "{$customerPendingAbsChangeFormatted} ({$customerPendingPercentChangeFormatted}) decrease";

        return [
            BaseStatsOverviewWidget\Stat::make('Revenue', $revenueFormatted)
                ->description($description)
                ->descriptionIcon($isIncrease ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($isIncrease ? 'success' : 'danger')
                ->chart($sparklineData)
                ->extraAttributes(['title' => "Revenue for {$days} days"])
                ->extraAttributes(['trend' => ($isIncrease ? '+' : '-') . $percentChangeFormatted]),

            BaseStatsOverviewWidget\Stat::make('Supplier Amount', $pendingAmountFormatted)
                ->description($pendingDescription)
                ->descriptionIcon($pendingAmount < 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($pendingAmount < 0 ? 'success' : 'warning')
                ->chart($pendingStatData['chart']),

            BaseStatsOverviewWidget\Stat::make('Customer Amount', $customerPendingAmountFormatted)
                ->description($customerPendingDescription)
                ->descriptionIcon($customerPendingAmount > 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($customerPendingAmount > 0 ? 'success' : 'warning')
                ->chart($customerPendingStatData['chart']),
        ];
    }

    private function formatCompactNumber(int|float $number, bool $showSign = false): string
    {
        $abs = abs($number);
        $sign = $showSign ? ($number > 0 ? '+' : ($number < 0 ? '-' : '')) : '';
        if ($abs >= 1_000_000) {
            return $sign . number_format($abs / 1_000_000, 1) . 'M';
        }
        if ($abs >= 1_000) {
            return $sign . number_format($abs / 1_000, 1) . 'k';
        }
        return $sign . number_format($abs, 0);
    }
}
