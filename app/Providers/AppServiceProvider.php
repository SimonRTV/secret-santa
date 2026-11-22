<?php

namespace App\Providers;

use App\Observers\ActivityObserver;
use App\Policies\RolePolicy;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Head\Enums\OgType;
use Laravel\Head\Enums\TwitterCard;
use Laravel\Head\ErrorPages;
use Laravel\Head\Facades\Head;
use Laravel\Head\HeadBuilder;
use Spatie\Permission\Models\Role;

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
        Gate::policy(Role::class, RolePolicy::class);

        /** @var array<class-string<Model>, mixed> $auditedModels */
        $auditedModels = config('activity.models', []);
        foreach (array_keys($auditedModels) as $model) {
            $model::observe(ActivityObserver::class);
        }

        $this->configureHead();
        $this->configureDefaults();
        $this->configureExchangeRateLimits();
    }

    /**
     * Rate limits for the account-free exchange pages. A household usually
     * shares one IP address, so edits are counted per exchange or invitation
     * rather than per address alone.
     */
    protected function configureExchangeRateLimits(): void
    {
        RateLimiter::for('exchange-create', fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('exchange-edits', fn (Request $request): Limit => Limit::perMinute(120)
            ->by($request->route()?->originalParameter('family').'|'.$request->ip()));
        RateLimiter::for('exchange-draw', fn (Request $request): Limit => Limit::perMinute(20)
            ->by((string) $request->route()?->originalParameter('family')));
        RateLimiter::for('invitation', fn (Request $request): Limit => Limit::perMinute(120)
            ->by($request->route()?->originalParameter('member').'|'.$request->ip()));
    }

    /**
     * Configure document metadata shared by the Inertia frontend.
     */
    protected function configureHead(): void
    {
        $appName = (string) config('app.name', 'Laravel');

        Head::defaults(fn (HeadBuilder $head) => $head
            ->title($appName, suffix: " - {$appName}")
            ->canonical(forceHttps: app()->isProduction())
            ->og(
                type: OgType::Website,
                siteName: $appName,
                locale: app()->getLocale(),
            )
            ->twitter(card: TwitterCard::Summary));

        Head::inertiaGlobals(fn (HeadBuilder $head) => $head
            ->viewport('width=device-width, initial-scale=1')
            ->applicationName($appName)
            ->colorScheme('only light')
            ->appleWebAppTitle($appName));

        Head::errors(function (ErrorPages $errors): void {
            $errors->defaults(robots: 'none');
            $errors->status(403, title: 'Accès interdit');
            $errors->status(404, title: 'Page introuvable');
            $errors->status(419, title: 'Page expirée');
            $errors->status(429, title: 'Trop de requêtes');
            $errors->status(500, title: 'Erreur serveur');
            $errors->status(503, title: 'Service indisponible');
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
