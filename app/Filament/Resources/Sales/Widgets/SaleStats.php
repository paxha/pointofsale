<?php

namespace App\Filament\Resources\Sales\Widgets;

use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;
use Filament\Support\Enums\IconPosition;

class SaleStats extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();
        $now = Carbon::now();
        $yesterday = $today->copy()->subDay();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // Today's sales
        $todayTotal = Sale::whereDate('created_at', $today)->sum('total');
        $yesterdayTotal = Sale::whereDate('created_at', $yesterday)->sum('total');
        $todayChange = $yesterdayTotal > 0 ? (($todayTotal - $yesterdayTotal) / $yesterdayTotal) * 100 : null;
        $todayIcon = $todayChange === null ? null : ($todayChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down');
        $todayColor = $todayChange === null ? null : ($todayChange >= 0 ? 'success' : 'danger');
        $todayChart = Trend::model(Sale::class)
            ->between($today->copy()->subDays(6), $now)
            ->perDay()
            ->sum('total')
            ->map(fn(TrendValue $value) => $value->aggregate)
            ->toArray();

        // Monthly sales
        $monthTotal = Sale::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->sum('total');
        $lastMonthTotal = Sale::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->sum('total');
        $monthChange = $lastMonthTotal > 0 ? (($monthTotal - $lastMonthTotal) / $lastMonthTotal) * 100 : null;
        $monthIcon = $monthChange === null ? null : ($monthChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down');
        $monthColor = $monthChange === null ? null : ($monthChange >= 0 ? 'success' : 'danger');
        $monthChart = Trend::model(Sale::class)
            ->between($startOfMonth, $now)
            ->perDay()
            ->sum('total')
            ->map(fn(TrendValue $value) => $value->aggregate)
            ->toArray();

        // Per day average (this month)
        $daysInMonth = $now->day;
        $perDayAverage = $daysInMonth > 0 ? $monthTotal / $daysInMonth : 0;
        $lastMonthDays = $endOfLastMonth->day;
        $lastMonthAverage = $lastMonthDays > 0 ? $lastMonthTotal / $lastMonthDays : 0;
        $avgChange = $lastMonthAverage > 0 ? (($perDayAverage - $lastMonthAverage) / $lastMonthAverage) * 100 : null;
        $avgIcon = $avgChange === null ? null : ($avgChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down');
        $avgColor = $avgChange === null ? null : ($avgChange >= 0 ? 'success' : 'danger');
        $avgChart = $monthChart;

        // Today's sales count
        $todayCount = Sale::whereDate('created_at', $today)->count();
        // Monthly sales count
        $monthCount = Sale::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();
        // Per day average count (this month)
        $perDayCount = $daysInMonth > 0 ? $monthCount / $daysInMonth : 0;
        $lastMonthCount = Sale::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $lastMonthPerDayCount = $lastMonthDays > 0 ? $lastMonthCount / $lastMonthDays : 0;
        $perDayCountChange = $lastMonthPerDayCount > 0 ? (($perDayCount - $lastMonthPerDayCount) / $lastMonthPerDayCount) * 100 : null;
        $perDayCountIcon = $perDayCountChange === null ? null : ($perDayCountChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down');
        $perDayCountColor = $perDayCountChange === null ? null : ($perDayCountChange >= 0 ? 'success' : 'danger');

        return [
            Stat::make("Today's Sale", number_format(round($todayTotal/100)) . ' PKR')
                ->description(($todayChange === null ? 'No data' : (abs(round($todayChange, 2)) . '% ' . ($todayChange >= 0 ? 'increase' : 'decrease'))) . " | $todayCount sales")
                ->descriptionIcon($todayIcon, IconPosition::Before)
                ->color($todayColor)
                ->chart($todayChart),
            Stat::make('Monthly Sale', number_format(round($monthTotal/100)) . ' PKR')
                ->description(($monthChange === null ? 'No data' : (abs(round($monthChange, 2)) . '% ' . ($monthChange >= 0 ? 'increase' : 'decrease'))) . " | $monthCount sales")
                ->descriptionIcon($monthIcon, IconPosition::Before)
                ->color($monthColor)
                ->chart($monthChart),
            Stat::make('Per Day Average', number_format(round($perDayAverage/100)) . ' PKR')
                ->description(($avgChange === null ? 'No data' : (abs(round($avgChange, 2)) . '% ' . ($avgChange >= 0 ? 'increase' : 'decrease'))) . " | " . round($perDayCount) . ' sales/day')
                ->descriptionIcon($avgIcon, IconPosition::Before)
                ->color($avgColor)
                ->chart($avgChart),
        ];
    }
}
