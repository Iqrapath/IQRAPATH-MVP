<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupDuplicateConversations extends Command
{
    protected $signature = 'conversations:cleanup-duplicates';
    protected $description = 'Remove duplicate conversations between the same users';

    public function handle()
    {
        $this->info('Checking for duplicate conversations...');

        // Find all direct conversations
        $conversations = Conversation::where('type', 'direct')
            ->with('participants')
            ->get();

        $userPairs = [];
        $duplicates = [];

        foreach ($conversations as $conversation) {
            $participantIds = $conversation->participants->pluck('id')->sort()->values()->toArray();
            
            if (count($participantIds) === 2) {
                $key = implode('-', $participantIds);
                
                if (isset($userPairs[$key])) {
                    // This is a duplicate
                    $duplicates[] = [
                        'keep' => $userPairs[$key],
                        'delete' => $conversation->id,
                        'users' => $participantIds,
                    ];
                    $this->warn("Found duplicate: Conversation {$conversation->id} (will delete) - same users as {$userPairs[$key]} (will keep)");
                } else {
                    $userPairs[$key] = $conversation->id;
                }
            }
        }

        if (empty($duplicates)) {
            $this->info('No duplicate conversations found!');
            return 0;
        }

        $this->info('Found ' . count($duplicates) . ' duplicate conversation(s)');

        if ($this->confirm('Do you want to delete the duplicate conversations?')) {
            foreach ($duplicates as $duplicate) {
                // Move messages from duplicate to the one we're keeping
                DB::table('messages')
                    ->where('conversation_id', $duplicate['delete'])
                    ->update(['conversation_id' => $duplicate['keep']]);

                // Delete the duplicate conversation
                Conversation::find($duplicate['delete'])->delete();
                
                $this->info("Merged conversation {$duplicate['delete']} into {$duplicate['keep']}");
            }

            $this->info('Cleanup complete!');
        } else {
            $this->info('Cleanup cancelled.');
        }

        return 0;
    }
}
