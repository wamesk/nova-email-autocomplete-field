<?php

declare(strict_types = 1);

namespace Wame\NovaEmailAutocompleteField;

use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;

class FieldServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Nova::serving(function (ServingNova $event): void {
            Nova::script('nova-email-autocomplete-field', __DIR__ . '/../dist/js/field.js');
            Nova::style('nova-email-autocomplete-field', __DIR__ . '/../dist/css/field.css');
            Nova::translations(resource_path('/lang/vendor/nova-email-autocomplete-field/' . app()->getLocale() . '.json'));
        });

        $this->loadTranslationsFrom(resource_path('/lang/vendor/nova-email-autocomplete-field'), 'translations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/nova-email-autocomplete.php' => config_path('nova-email-autocomplete.php'),
            ], 'config');
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/nova-email-autocomplete.php', 'wame-commands');
    }
}
