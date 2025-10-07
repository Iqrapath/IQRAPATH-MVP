<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AddStudentRoleToGuardian extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'guardian:add-student-role {email : The email of the guardian}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add student role to a guardian so they can book classes for themselves';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $guardian = User::where('email', $email)->first();
        
        if (!$guardian) {
            $this->error("Guardian with email {$email} not found.");
            return 1;
        }
        
        if (!$guardian->isGuardian()) {
            $this->error("User {$email} is not a guardian.");
            return 1;
        }
        
        if ($guardian->hasAdditionalRole('student')) {
            $this->info("Guardian {$email} already has student role.");
            return 0;
        }
        
        $guardian->addAdditionalRole('student');
        
        $this->info("Successfully added student role to guardian {$email}.");
        $this->info("Guardian can now book classes for themselves.");
        
        return 0;
    }
}