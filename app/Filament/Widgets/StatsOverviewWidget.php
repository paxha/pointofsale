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
        $change = $currentValue - $previousValue;
        $hasIncrease = $change > 0;
        $hasDecrease = $change < 0;
        $color = $hasIncrease ? 'success' : ($hasDecrease ? 'danger' : 'warning');
        $icon = $hasIncrease ? 'heroicon-o-arrow-trending-up' : ($hasDecrease ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-minus');

        // Percent change calculation, safe for zero
        if ($previousValue == 0) {
            $percentChange = $currentValue == 0 ? 0 : 100;
        } else {
            $percentChange = ($change / abs($previousValue)) * 100;
        }

        $percentSign = $percentChange > 0 ? '+' : ($percentChange < 0 ? '-' : '');
        $percentChangeFormatted = $percentSign . number_format(abs($percentChange), 1) . '%';

        $trend = $percentChangeFormatted;
        $changeFormatted = $this->formatCompactNumber($change, true);
        $description = match (true) {
            $hasIncrease => "{$changeFormatted} ({$percentChangeFormatted}) increase",
            $hasDecrease => "{$changeFormatted} ({$percentChangeFormatted}) decrease",
            default => 'No change',
        };
        $title = $title ?? "$label for {$days} days";

        return BaseStatsOverviewWidget\Stat::make($label, $this->formatCompactNumber($currentValue, true))
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
        // Always use the original value for sign and formatting
        $sign = '';
        if ($showSign) {
            if ($number < 0 || (is_float($number) && (string)$number === '-0')) {
                $sign = '-';
            } elseif ($number > 0) {
                $sign = '+';
            }
        }
        $abs = abs($number);
        if ($abs >= 1_000_000) {
            return $sign . number_format($abs / 1_000_000, 1) . 'M';
        }
        if ($abs >= 1_000) {
            return $sign . number_format($abs / 1_000, 1) . 'k';
        }
        return $sign . number_format($abs, 0);
    }
}
