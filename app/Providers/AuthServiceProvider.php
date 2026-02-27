<?php

namespace App\Providers;

use App\Models\GovernanceLayer;
use App\Policies\Admin\GovernanceLayerPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        GovernanceLayer::class => GovernanceLayerPolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();
    }
}