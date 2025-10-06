<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\Faq;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    /**
     * Display the welcome page with featured teachers.
     */
    public function index(): Response
    {
        // Fetch featured teachers for landing page
        $teachers = User::where('role', 'teacher')
            ->where('account_status', 'active')
            ->with(['teacherProfile', 'teacherReviews', 'subjects.template'])
            ->whereHas('teacherProfile', function ($query) {
                $query->where('verified', true);
            })
            ->limit(4)
            ->get()
                   ->map(function ($teacher) {
                       $profile = $teacher->teacherProfile;
                       $reviews = $teacher->teacherReviews;

                       // Calculate average rating
                       $avgRating = $reviews->avg('rating') ?? 0;
                       $reviewsCount = $reviews->count();

                       // Get primary specialization from subjects
                       $primarySubject = $teacher->subjects->first()?->template?->name ?? 'Quran & Islamic Studies';

                       return [
                           'id' => $teacher->id,
                           'name' => $teacher->name,
                           'specialization' => $primarySubject,
                           'image' => $teacher->avatar,
                           'initials' => $this->getInitials($teacher->name),
                           'rating' => round($avgRating, 1),
                           'reviews' => $reviewsCount,
                           'yearsExp' => $profile->experience_years ?? 0,
                       ];
                   });

               // Fetch active subscription plans for the modal
               $subscriptionPlans = SubscriptionPlan::where('is_active', true)
                   ->orderBy('price_naira', 'asc')
                   ->get()
                   ->map(function ($plan) {
                       return [
                           'id' => $plan->id,
                           'name' => $plan->name,
                           'description' => $plan->description,
                           'price_naira' => $plan->price_naira,
                           'price_dollar' => $plan->price_dollar,
                           'billing_cycle' => $plan->billing_cycle,
                           'duration_months' => $plan->duration_months,
                           'features' => $plan->features,
                           'tags' => $plan->tags,
                           'image_path' => $plan->image_path,
                           'is_active' => $plan->is_active,
                       ];
                   });

               // Fetch published FAQs for the landing page
               $faqs = Faq::getPublished()
                   ->map(function ($faq) {
                       return [
                           'id' => $faq->id,
                           'title' => $faq->title,
                           'content' => $faq->content,
                           'status' => $faq->status,
                           'order_index' => $faq->order_index,
                       ];
                   });

               return Inertia::render('welcome', [
                   'teachers' => $teachers,
                   'subscriptionPlans' => $subscriptionPlans,
                   'faqs' => $faqs
               ]);
    }

    /**
     * Generate initials from a full name.
     */
    private function getInitials(string $name): string
    {
        $words = explode(' ', trim($name));
        $initials = '';
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }
        
        return substr($initials, 0, 2); // Limit to 2 characters
    }
}

