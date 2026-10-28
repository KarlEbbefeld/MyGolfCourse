<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\Courses\Models\Course;
use App\Modules\Courses\Models\Hole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Lokal fallen fehlende Eager-Loads sofort auf (N+1-Abfragen), auf dem Server nie
        Model::preventLazyLoading(! app()->isProduction() && ! app()->environment('staging'));

        // Kurze Typnamen statt Klassennamen in polymorphen Beziehungen (spätere Favoriten, Audit-Log)
        Relation::enforceMorphMap([
            'user' => User::class,
            'course' => Course::class,
            'hole' => Hole::class,
        ]);

        // Bereichsrechte (docs/ARCHITECTURE.md § 7); objektbezogene Rechte kommen als Policies je Modell
        Gate::define('edit-content', fn (User $user) => $user->role->canEditContent());
        Gate::define('publish-content', fn (User $user) => $user->role->canPublish());

        Password::defaults(fn () => Password::min(10)->letters()->numbers());
    }
}
