<?php

namespace Aginev\SearchFilters;

use Illuminate\Support\ServiceProvider;

class SearchFiltersServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        // Merge config
        $this->mergeConfigFrom(__DIR__ . '/../config/search-filters.php', 'search-filters');
        
        // Publish package config
        $this->publishes([
            __DIR__ . '/../config/search-filters.php' => config_path('search-filters.php'),
        ], 'config');
    }

}
