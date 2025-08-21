<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\Procurement;
use App\Models\ProcurementProduct;
use App\Models\ProductSale;
use App\Models\Role;
use App\Models\Sale;
use App\Observers\ProcurementObserver;
use App\Observers\ProcurementProductObserver;
use App\Observers\ProductSaleObserver;
use App\Observers\SaleObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)
            ->setPermissionClass(Permission::class)
            ->setRoleClass(Role::class);

        Procurement::observe(ProcurementObserver::class);
        Sale::observe(SaleObserver::class);
        ProcurementProduct::observe(ProcurementProductObserver::class);
        ProductSale::observe(ProductSaleObserver::class);

        //
    }
}
