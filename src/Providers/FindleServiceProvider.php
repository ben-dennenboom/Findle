<?php

namespace Dennenboom\Findle\Providers;

use Dennenboom\Findle\Http\Controllers\ComparisonsController;
use Dennenboom\Findle\Http\Controllers\StatisticsController;
use Dennenboom\Findle\Http\Controllers\TrackingApiController;
use Dennenboom\Findle\Middleware\TrackPageViews;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class FindleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/findle.php',
            'findle'
        );
    }

    public function boot(): void
    {
        $this->publishes(
            [
                __DIR__ . '/../../config/findle.php' => config_path('findle.php'),
            ],
            'findle-config'
        );

        $this->publishes(
            [
                __DIR__ . '/../../database/migrations/' => database_path('migrations'),
            ],
            'findle-migrations'
        );

        $this->loadViewsFrom(__DIR__ . '/../../resources/views/findle', 'findle');

        $this->publishes(
            [
                __DIR__ . '/../../resources/views' => resource_path('views/vendor/findle'),
            ],
            'findle-views'
        );

        $this->registerViewComposers();

        $this->registerRoutes();
        $this->registerMiddleware();
    }

    protected function registerRoutes(): void
    {
        if (!$this->app->routesAreCached()) {
            Route::group(
                [
                    'prefix'     => trim(config('findle.route', '/admin/statistics'), '/'),
                    'middleware' => ['web', 'findle.auth'],
                ],
                function () {
                    Route::get('/', [StatisticsController::class, 'index'])
                        ->name('findle.statistics.index');

                    Route::get('/show', [StatisticsController::class, 'show'])
                        ->name('findle.statistics.show');

                    Route::get('/compare', [ComparisonsController::class, 'index'])
                        ->name('findle.statistics.compare');
                }
            );

            Route::group(
                [
                    'prefix'     => 'api/findle',
                    'middleware' => ['api'],
                ],
                function () {
                    Route::post('/track', [TrackingApiController::class, 'track'])
                        ->name('findle.api.track');

                    Route::get('/token', [TrackingApiController::class, 'generateToken'])
                        ->name('findle.api.token');
                }
            );
        }
    }

    protected function registerMiddleware(): void
    {
        $this->app['router']->aliasMiddleware('findle.track', TrackPageViews::class);

        $this->app['router']->pushMiddlewareToGroup('web', 'findle.track');

        $this->app['router']->aliasMiddleware('findle.auth', function ($request, $next) {
            $user = auth()->user();
            $middlewareCheck = config('findle.middleware_check');

            if (is_callable($middlewareCheck)) {
                if (!$middlewareCheck($user)) {
                    abort(403, 'Unauthorized access to statistics');
                }
            } elseif (!$user || !$user->is_admin) {
                abort(403, 'Unauthorized access to statistics');
            }

            return $next($request);
        });
    }

    protected function registerViewComposers(): void
    {
        view()->addNamespace('findle', __DIR__ . '/../../resources/views/findle');
    }
}
