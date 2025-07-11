<?php

namespace App\Providers;

use App\Models\Document;
use App\Models\Subject;
use App\Models\TeachingSession;
use App\Policies\DocumentPolicy;
use App\Policies\SubjectPolicy;
use App\Policies\TeachingSessionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Document::class => DocumentPolicy::class,
        Subject::class => SubjectPolicy::class,
        TeachingSession::class => TeachingSessionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
