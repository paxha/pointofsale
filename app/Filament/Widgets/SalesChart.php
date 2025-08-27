<?php

namespace App\Filament\Widgets;

use App\Enums\SaleStatus;
use App\Models\Sale;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class SalesChart extends ChartWidget
{
    protected ?string $heading = 'Sales Chart';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $year = now()->year;
        $months = collect(range(1, 12))->map(fn ($m) => Carbon::create()->month($m)->format('M'));

        // Sales per month
        $salesPerMonth = Sale::query()
            ->where('status', SaleStatus::Completed->value)
            ->whereYear('created_at', $year)
            ->selectRaw("strftime('%m', created_at) as month, SUM(total) as total")
            ->groupByRaw("strftime('%m', created_at)")
            ->pluck('total', 'month');

        // Profit per month
        $profitPerMonth = Sale::query()
            ->where('status', SaleStatus::Completed->value)
            ->whereYear('created_at', $year)
            ->with('products')
            ->get()
            ->groupBy(fn ($sale) => $sale->created_at->format('m'))
            ->map(function ($sales) {
                return $sales->sum(function ($sale) {
                    return $sale->products->sum(function ($product) {
                        return ($product->pivot->total ?? 0) - ($product->pivot->supplier_total ?? 0);
                    });
                });
            });

        // Supplier price per month
        $supplierPricePerMonth = Sale::query()
            ->where('status', SaleStatus::Completed->value)
            ->whereYear('created_at', $year)
            ->with('products')
            ->get()
            ->groupBy(fn ($sale) => $sale->created_at->format('m'))
            ->map(function ($sales) {
                return $sales->sum(function ($sale) {
                    return $sale->products->sum(function ($product) {
                        return $product->pivot->supplier_total ?? 0;
                    });
                });
            });

        $salesData = $months->map(function ($label, $i) use ($salesPerMonth) {
            $monthKey = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
            return round(($salesPerMonth[$monthKey] ?? 0), 2);
        })->toArray();

        $profitData = $months->map(function ($label, $i) use ($profitPerMonth) {
            $monthKey = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
            return round(($profitPerMonth[$monthKey] ?? 0), 2);
        })->toArray();

        $supplierPriceData = $months->map(function ($label, $i) use ($supplierPricePerMonth) {
            $monthKey = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
            return round(($supplierPricePerMonth[$monthKey] ?? 0), 2);
        })->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Sales',
                    'data' => $salesData,
                    'borderColor' => '#3b82f6', // blue-500
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)', // blue-500/20
                ],
                [
                    'label' => 'Profit',
                    'data' => $profitData,
                    'borderColor' => '#22c55e', // green-500
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)', // green-500/20
                ],
                [
                    'label' => 'Supplier Price',
                    'data' => $supplierPriceData,
                    'borderColor' => '#f59e42', // orange-400
                    'backgroundColor' => 'rgba(245, 158, 66, 0.2)', // orange-400/20
                ],
            ],
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
