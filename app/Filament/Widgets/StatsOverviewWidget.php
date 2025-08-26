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

    /**
     * Helper to calculate and format stat data in a unified way.
     */
    private function calculateStatData(string $label, float|int $currentValue, float|int $previousValue, array $chart, int $days, ?string $title = null): BaseStatsOverviewWidget\Stat
    {
        $absChange = $currentValue - $previousValue;
        $isIncrease = $absChange > 0;
        $color = $isIncrease ? 'success' : ($absChange < 0 ? 'danger' : 'warning');
        $icon = $isIncrease ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down';
        $absChangeFormatted = $this->formatCompactNumber($absChange, true);
        // Improved percent change logic
        if ($previousValue == 0 && $currentValue == 0) {
            $percentChange = 0;
            $percentChangeFormatted = '0.0%';
            $trend = '0.0%';
            $description = 'No change';
        } elseif ($previousValue == 0 && $currentValue > 0) {
            $percentChange = INF;
            $percentChangeFormatted = '∞%';
            $trend = '+∞%';
            $description = "{$absChangeFormatted} (∞%) increase";
        } elseif ($previousValue > 0 && $currentValue == 0) {
            $percentChange = -100;
            $percentChangeFormatted = '100.0%';
            $trend = '-100.0%';
            $description = "-{$this->formatCompactNumber($previousValue, true)} (100.0%) decrease";
        } else {
            $percentChange = ($absChange / $previousValue) * 100;
            $percentChangeFormatted = number_format(abs($percentChange), 1).'%';
            $trend = ($isIncrease ? '+' : ($absChange < 0 ? '-' : '')).$percentChangeFormatted;
            $description = $isIncrease
                ? "{$absChangeFormatted} ({$percentChangeFormatted}) increase"
                : ($absChange < 0
                    ? "{$absChangeFormatted} ({$percentChangeFormatted}) decrease"
                    : 'No change');
        }
        $title = $title ?? "$label for {$days} days";

        return BaseStatsOverviewWidget\Stat::make($label, $this->formatCompactNumber($currentValue))
            ->description($description)
            ->descriptionIcon($icon)
            ->color($color)
            ->chart($chart)
            ->extraAttributes([
                'title' => $title,
                'trend' => $trend,
            ]);
    }

    protected function getStats(): array
    {
        $startDate = ! is_null($this->pageFilters['startDate'] ?? null)
            ? Carbon::parse($this->pageFilters['startDate'])
            : now()->subMonth();
        $endDate = ! is_null($this->pageFilters['endDate'] ?? null)
            ? Carbon::parse($this->pageFilters['endDate'])
            : now();
        $days = $startDate->diffInDays($endDate) + 1;
        $prevStart = $startDate->copy()->subDays($days);
        $prevEnd = $startDate->copy()->subDay();

        // Revenue stat
        $revenue = Sale::query()
            ->where('status', SaleStatus::Completed->value)
            ->whereIn('payment_status', [
                SalePaymentStatus::Paid->value,
                SalePaymentStatus::Credit->value,
            ])
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->sum('total') / 100;
        $prevRevenue = Sale::query()
            ->where('status', SaleStatus::Completed->value)
            ->whereIn('payment_status', [
                SalePaymentStatus::Paid->value,
                SalePaymentStatus::Credit->value,
            ])
            ->whereBetween('created_at', [$prevStart->startOfDay(), $prevEnd->endOfDay()])
            ->sum('total') / 100;
        $sparklineDays = collect(range(0, 6))->map(
            fn ($i) => $endDate->copy()->subDays(6 - $i)->toDateString()
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

        // Supplier Amount stat
        $pendingStatData = SupplierPaymentStats::getPendingAmountStatForPeriod($startDate, $endDate);
        $pendingAmount = $pendingStatData['value'];
        $pendingChart = $pendingStatData['chart'];
        $prevPendingStatData = SupplierPaymentStats::getPendingAmountStatForPeriod($prevStart, $prevEnd);
        $prevPendingAmount = $prevPendingStatData['value'];

        // Customer Amount stat
        $customerPendingStatData = CustomerPaymentStats::getPendingAmountStatForPeriod($startDate, $endDate);
        $customerPendingAmount = $customerPendingStatData['value'];
        $customerPendingChart = $customerPendingStatData['chart'];
        $prevCustomerPendingStatData = CustomerPaymentStats::getPendingAmountStatForPeriod($prevStart, $prevEnd);
        $prevCustomerPendingAmount = $prevCustomerPendingStatData['value'];

        return [
            $this->calculateStatData('Revenue', $revenue, $prevRevenue, $sparklineData, $days),
            $this->calculateStatData('Supplier Amount', $pendingAmount, $prevPendingAmount, $pendingChart, $days),
            $this->calculateStatData('Customer Amount', $customerPendingAmount, $prevCustomerPendingAmount, $customerPendingChart, $days),
        ];
    }

    private function formatCompactNumber(int|float $number, bool $showSign = false): string
    {
        $abs = abs($number);
        $sign = $showSign ? ($number > 0 ? '+' : ($number < 0 ? '-' : '')) : '';
        if ($abs >= 1_000_000) {
            return $sign.number_format($abs / 1_000_000, 1).'M';
        }
        if ($abs >= 1_000) {
            return $sign.number_format($abs / 1_000, 1).'k';
        }

        return $sign.number_format($abs, 0);
    }
}
