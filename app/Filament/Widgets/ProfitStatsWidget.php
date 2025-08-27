<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Illuminate\Support\Facades\DB;

class ProfitStatsWidget extends BaseStatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $startDate = ! is_null($this->pageFilters['startDate'] ?? null)
            ? Carbon::parse($this->pageFilters['startDate'])
            : now()->subMonth();
        $endDate = ! is_null($this->pageFilters['endDate'] ?? null)
            ? Carbon::parse($this->pageFilters['endDate'])
            : now();

        $profit = DB::table('product_sale')
            ->join('sales', 'product_sale.sale_id', '=', 'sales.id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->selectRaw('SUM(product_sale.total-product_sale.supplier_total) as profit')
            ->value('profit') ?? 0;

        $profitValue = $this->formatCompactNumber($profit / 100);
        $color = $profit < 0 ? 'danger' : 'success';

        return [
            BaseStatsOverviewWidget\Stat::make('Profit', 'PKR '.$profitValue)
                ->color($color),
        ];
    }

    private function formatCompactNumber(int|float $number): string
    {
        $abs = abs($number);
        if ($abs >= 1_000_000) {
            return number_format($number / 1_000_000, 1).'M';
        }
        if ($abs >= 1_000) {
            return number_format($number / 1_000, 1).'k';
        }

        return number_format($number, 2);
    }
}
