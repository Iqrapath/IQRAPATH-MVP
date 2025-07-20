<?php

namespace App\Providers;

use App\Models\Dispute;
use App\Models\Document;
use App\Models\EvidenceAttachment;
use App\Models\Feedback;
use App\Models\Subject;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Models\TeachingSession;
use App\Models\TicketResponse;
use App\Models\VerificationRequest;
use App\Policies\DisputePolicy;
use App\Policies\DocumentPolicy;
use App\Policies\EvidenceAttachmentPolicy;
use App\Policies\FeedbackPolicy;
use App\Policies\SubjectPolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\SupportTicketPolicy;
use App\Policies\TeachingSessionPolicy;
use App\Policies\TicketResponsePolicy;
use App\Policies\VerificationRequestPolicy;
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
        Subscription::class => SubscriptionPolicy::class,
        TeachingSession::class => TeachingSessionPolicy::class,
        Feedback::class => FeedbackPolicy::class,
        SupportTicket::class => SupportTicketPolicy::class,
        Dispute::class => DisputePolicy::class,
        TicketResponse::class => TicketResponsePolicy::class,
        EvidenceAttachment::class => EvidenceAttachmentPolicy::class,
        VerificationRequest::class => VerificationRequestPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define gates for managing feedback and support
        Gate::define('manage-feedback', function ($user) {
            return $user->role === 'super-admin' || $user->role === 'admin';
        });

        Gate::define('manage-support', function ($user) {
            return $user->role === 'super-admin' || $user->role === 'admin';
        });

        Gate::define('manage-disputes', function ($user) {
            return $user->role === 'super-admin' || $user->role === 'admin';
        });

        Gate::define('view-feedback', function ($user) {
            return $user->role === 'super-admin' || $user->role === 'admin';
        });
        
        Gate::define('manage-teacher-verifications', function ($user) {
            return $user->role === 'super-admin';
        });
        
        Gate::define('verifyDocuments', function ($user) {
            return $user->role === 'super-admin';
        });
    }
}
