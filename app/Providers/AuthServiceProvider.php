<?php

namespace App\Providers;

use App\Models\Item;
use App\Models\StockOpname;
use App\Models\User;
use App\Policies\ItemPolicy;
use App\Policies\StockOpnamePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Item::class => ItemPolicy::class,
        User::class => UserPolicy::class,
        StockOpname::class => StockOpnamePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
