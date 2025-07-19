<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class StartReverbServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:start-reverb {--background : Run the server in the background}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the Reverb WebSocket server if not already running';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking if Reverb server is already running...');
        
        // Check if the server is already running
        $checkProcess = Process::fromShellCommandline('ps aux | grep "reverb:start" | grep -v grep');
        $checkProcess->run();
        
        if ($checkProcess->isSuccessful() && trim($checkProcess->getOutput()) !== '') {
            $this->info('Reverb server is already running.');
            return Command::SUCCESS;
        }
        
        $this->info('Starting Reverb WebSocket server...');
        
        // Start the Reverb server
        if ($this->option('background')) {
            $this->startInBackground();
        } else {
            $this->call('reverb:start');
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Start the Reverb server in the background.
     */
    protected function startInBackground()
    {
        $command = PHP_BINARY . ' ' . base_path('artisan') . ' reverb:start > /dev/null 2>&1 &';
        
        if (PHP_OS_FAMILY === 'Windows') {
            // For Windows, use start command
            $command = 'start /B ' . PHP_BINARY . ' ' . base_path('artisan') . ' reverb:start > nul 2>&1';
        }
        
        $process = Process::fromShellCommandline($command);
        $process->run();
        
        $this->info('Reverb server started in the background.');
    }
}
