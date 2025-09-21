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
        Schema::create('content_pages', function (Blueprint $table) {
            $table->id();
            $table->string('page_key', 100)->unique();
            $table->string('title');
            $table->text('content')->nullable();
            $table->unsignedBigInteger('last_updated_by')->nullable();
            $table->foreign('last_updated_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });

        // Insert default content pages
        $this->seedDefaultContentPages();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_pages');
    }

    /**
     * Seed default content pages.
     */
    private function seedDefaultContentPages(): void
    {
        $pages = [
            [
                'page_key' => 'terms_conditions',
                'title' => 'Terms & Conditions',
                'content' => 'Welcome to IqraQuest – a trusted platform for connecting Quran teachers with students and guardians across the globe. These Terms & Conditions ("Terms") govern your use of the IqraQuest website, services, and any associated content provided by IqraQuest.',
            ],
            [
                'page_key' => 'privacy_policy',
                'title' => 'Privacy Policy',
                'content' => 'At IqraQuest, we take your privacy seriously. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our platform.',
            ],
        ];

        $now = now();

        foreach ($pages as $page) {
            DB::table('content_pages')->insert([
                'page_key' => $page['page_key'],
                'title' => $page['title'],
                'content' => $page['content'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
