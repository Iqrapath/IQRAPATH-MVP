<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->enum('status', ['published', 'draft'])->default('draft');
            $table->integer('order_index')->default(0);
            $table->timestamps();
        });

        // Insert default FAQs
        $this->seedDefaultFaqs();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }

    /**
     * Seed default FAQs.
     */
    private function seedDefaultFaqs(): void
    {
        $faqs = [
            [
                'title' => 'What is Iqrapath?',
                'content' => 'Iqrapath is an online platform connecting Quran teachers with students worldwide. Our mission is to make quality Quranic education accessible to everyone, regardless of location.',
                'status' => 'published',
                'order_index' => 1,
            ],
            [
                'title' => 'How do register as a teacher on Iqrapath?',
                'content' => 'To register as a teacher, click on "Become a Teacher" on the homepage, fill out the application form, upload your credentials, and submit for verification. Our team will review your application and contact you within 48 hours.',
                'status' => 'published',
                'order_index' => 2,
            ],
            [
                'title' => 'How to book a class session on Iqrapath?',
                'content' => 'To book a class, log in to your student account, browse available teachers, select your preferred teacher, choose an available time slot, and confirm your booking. Payment will be processed, and both you and the teacher will receive confirmation.',
                'status' => 'published',
                'order_index' => 3,
            ],
            [
                'title' => 'How to reset password?',
                'content' => 'To reset your password, click on "Login", then "Forgot Password". Enter your email address, and we will send you a password reset link. Click the link in the email and follow the instructions to create a new password.',
                'status' => 'published',
                'order_index' => 4,
            ],
            [
                'title' => 'Is live verification mandatory for teachers?',
                'content' => 'Yes, all teachers must complete a live verification call before they can start teaching on Iqrapath. This ensures the safety and quality of our platform.',
                'status' => 'draft',
                'order_index' => 5,
            ],
            [
                'title' => 'How to Withdraw Money',
                'content' => 'Teachers can withdraw their earnings by going to the Financial dashboard, clicking on "Withdraw Funds", selecting their preferred payment method, entering the amount, and confirming the withdrawal. Processing typically takes 1-3 business days.',
                'status' => 'draft',
                'order_index' => 6,
            ],
        ];

        $now = now();

        foreach ($faqs as $faq) {
            DB::table('faqs')->insert([
                'title' => $faq['title'],
                'content' => $faq['content'],
                'status' => $faq['status'],
                'order_index' => $faq['order_index'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
