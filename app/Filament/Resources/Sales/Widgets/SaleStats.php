<?php

namespace App\Filament\Resources\Sales\Widgets;

use App\Enums\SaleStatus;
use App\Models\Sale;
use Carbon\CarbonPeriod;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

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
        $todayTotal = Sale::where('status', SaleStatus::Completed)
            ->whereDate('created_at', $today)
            ->sum('total');
        $yesterdayTotal = Sale::where('status', SaleStatus::Completed)
            ->whereDate('created_at', $yesterday)
            ->sum('total');
        $todayChange = $yesterdayTotal > 0 ? (($todayTotal - $yesterdayTotal) / $yesterdayTotal) * 100 : null;
        $todayIcon = $todayChange === null ? null : ($todayChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down');
        $todayColor = $todayChange === null ? null : ($todayChange >= 0 ? 'success' : 'danger');
        // Generate daily sales totals for the last 7 days (no foreach)
        $start7 = $today->copy()->subDays(6);
        $sales7 = Sale::where('status', SaleStatus::Completed)
            ->whereBetween('created_at', [$start7, $now])
            ->selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->groupBy('date')
            ->pluck('total', 'date');
        $period7 = CarbonPeriod::create($start7, $now);
        $todayChart = collect($period7)->map(fn($date) => $sales7[$date->toDateString()] ?? 0)->toArray();

        // Monthly sales
        $monthTotal = Sale::where('status', SaleStatus::Completed)
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->sum('total');
        $lastMonthTotal = Sale::where('status', SaleStatus::Completed)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('total');
        $monthChange = $lastMonthTotal > 0 ? (($monthTotal - $lastMonthTotal) / $lastMonthTotal) * 100 : null;
        $monthIcon = $monthChange === null ? null : ($monthChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down');
        $monthColor = $monthChange === null ? null : ($monthChange >= 0 ? 'success' : 'danger');
        // Generate daily sales totals for the current month (no foreach)
        $salesMonth = Sale::where('status', SaleStatus::Completed)
            ->whereBetween('created_at', [$startOfMonth, $now])
            ->selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->groupBy('date')
            ->pluck('total', 'date');
        $periodMonth = CarbonPeriod::create($startOfMonth, $now);
        $monthChart = collect($periodMonth)->map(fn($date) => $salesMonth[$date->toDateString()] ?? 0)->toArray();

        // Active days in the current month (days with at least one completed sale)
        $activeDays = Sale::where('status', SaleStatus::Completed)
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->selectRaw('DATE(created_at) as sale_date')
            ->groupBy('sale_date')
            ->get()
            ->count();
        // Active days in the previous month (days with at least one completed sale)
        $lastMonthActiveDays = Sale::where('status', SaleStatus::Completed)
            ->whereMonth('created_at', $startOfLastMonth->month)
            ->whereYear('created_at', $startOfLastMonth->year)
            ->selectRaw('DATE(created_at) as sale_date')
            ->groupBy('sale_date')
            ->get()
            ->count();

        // Today's sales count
        $todayCount = Sale::where('status', SaleStatus::Completed)
            ->whereDate('created_at', $today)
            ->count();
        // Monthly sales count
        $monthCount = Sale::where('status', SaleStatus::Completed)
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();
        $lastMonthCount = Sale::where('status', SaleStatus::Completed)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->count();
        // Per day sales count (active days only)
        $perDayAmount = $activeDays > 0 ? $monthTotal / $activeDays : 0;
        $perDayCount = $activeDays > 0 ? $monthCount / $activeDays : 0;
        // Last month's per day sales count (active days only)
        $lastMonthPerDayCount = $lastMonthActiveDays > 0 ? $lastMonthCount / $lastMonthActiveDays : 0;
        $perDayCountChange = $lastMonthPerDayCount > 0 ? (($perDayCount - $lastMonthPerDayCount) / $lastMonthPerDayCount) * 100 : null;
        $perDayCountIcon = $perDayCountChange === null ? null : ($perDayCountChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down');
        $perDayCountColor = $perDayCountChange === null ? null : ($perDayCountChange >= 0 ? 'success' : 'danger');

        return [
            Stat::make("Today's Sale", number_format(round($todayTotal / 100)).' PKR')
                ->description(($todayChange === null ? 'No data' : (abs(round($todayChange, 2)).'% '.($todayChange >= 0 ? 'increase' : 'decrease')))." | $todayCount sales")
                ->descriptionIcon($todayIcon, IconPosition::Before)
                ->color($todayColor)
                ->chart($todayChart),
            Stat::make('Monthly Sale', number_format(round($monthTotal / 100)).' PKR')
                ->description(($monthChange === null ? 'No data' : (abs(round($monthChange, 2)).'% '.($monthChange >= 0 ? 'increase' : 'decrease')))." | $monthCount sales")
                ->descriptionIcon($monthIcon, IconPosition::Before)
                ->color($monthColor)
                ->chart($monthChart),
            Stat::make('Per Day Average', number_format(round($perDayAmount / 100)).' PKR')
                ->description(($perDayCountChange === null ? 'No data' : (abs(round($perDayCountChange, 2)).'% '.($perDayCountChange >= 0 ? 'increase' : 'decrease'))).' | '.round($perDayCount).' sales/active day')
                ->descriptionIcon($perDayCountIcon, IconPosition::Before)
                ->color($perDayCountColor)
                ->chart($monthChart),
        ];
    }
}
