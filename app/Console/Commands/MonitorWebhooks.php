<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WebhookEvent;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonitorWebhooks extends Command
{
    protected $signature = 'webhooks:monitor 
                            {--alert : Send alerts if issues detected}
                            {--threshold=95 : Success rate threshold percentage}';

    protected $description = 'Monitor webhook health and send alerts if needed';

    public function __construct(
        private NotificationService $notificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Monitoring webhook system...');
        $this->newLine();

        // Get monitoring data
        $stats = $this->getWebhookStats();
        
        // Display stats
        $this->displayStats($stats);
        
        // Check for issues
        $issues = $this->detectIssues($stats);
        
        if (!empty($issues)) {
            $this->warn('Issues detected:');
            foreach ($issues as $issue) {
                $this->error("  • $issue");
            }
            
            if ($this->option('alert')) {
                $this->sendAlerts($issues, $stats);
            }
            
            return self::FAILURE;
        }
        
        $this->info('✅ All webhook systems operational');
        
        // Cache stats for dashboard
        Cache::put('webhook_stats', $stats, now()->addMinutes(5));
        
        return self::SUCCESS;
    }

    private function getWebhookStats(): array
    {
        $last24Hours = now()->subDay();
        $lastHour = now()->subHour();

        return [
            'total' => WebhookEvent::count(),
            'last_24h' => WebhookEvent::where('created_at', '>=', $last24Hours)->count(),
            'last_hour' => WebhookEvent::where('created_at', '>=', $lastHour)->count(),
            'processed' => WebhookEvent::where('status', 'processed')->count(),
            'failed' => WebhookEvent::where('status', 'failed')->count(),
            'pending' => WebhookEvent::where('status', 'pending')->count(),
            'success_rate' => $this->calculateSuccessRate(),
            'success_rate_24h' => $this->calculateSuccessRate($last24Hours),
            'by_gateway' => $this->getStatsByGateway(),
            'recent_failures' => $this->getRecentFailures(),
            'avg_processing_time' => $this->getAverageProcessingTime(),
            'last_webhook' => WebhookEvent::latest()->first()?->created_at,
        ];
    }

    private function calculateSuccessRate($since = null): float
    {
        $query = WebhookEvent::query();
        
        if ($since) {
            $query->where('created_at', '>=', $since);
        }
        
        $total = $query->count();
        
        if ($total === 0) {
            return 100.0;
        }
        
        $processed = (clone $query)->where('status', 'processed')->count();
        
        return round(($processed / $total) * 100, 2);
    }

    private function getStatsByGateway(): array
    {
        return WebhookEvent::select('gateway', DB::raw('COUNT(*) as total'))
            ->selectRaw('SUM(CASE WHEN status = "processed" THEN 1 ELSE 0 END) as processed')
            ->selectRaw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
            ->groupBy('gateway')
            ->get()
            ->mapWithKeys(function ($row) {
                return [
                    $row->gateway => [
                        'total' => $row->total,
                        'processed' => $row->processed,
                        'failed' => $row->failed,
                        'success_rate' => $row->total > 0 
                            ? round(($row->processed / $row->total) * 100, 2) 
                            : 100.0,
                    ],
                ];
            })
            ->toArray();
    }

    private function getRecentFailures(): array
    {
        return WebhookEvent::where('status', 'failed')
            ->where('created_at', '>=', now()->subDay())
            ->latest()
            ->take(10)
            ->get(['id', 'gateway', 'type', 'error_message', 'created_at'])
            ->toArray();
    }

    private function getAverageProcessingTime(): ?float
    {
        $avg = WebhookEvent::where('status', 'processed')
            ->whereNotNull('processed_at')
            ->where('created_at', '>=', now()->subDay())
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, processed_at)) as avg_time')
            ->value('avg_time');

        return $avg ? round($avg, 2) : null;
    }

    private function displayStats(array $stats): void
    {
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Webhooks', number_format($stats['total'])],
                ['Last 24 Hours', number_format($stats['last_24h'])],
                ['Last Hour', number_format($stats['last_hour'])],
                ['Processed', number_format($stats['processed'])],
                ['Failed', number_format($stats['failed'])],
                ['Pending', number_format($stats['pending'])],
                ['Success Rate (All Time)', $stats['success_rate'] . '%'],
                ['Success Rate (24h)', $stats['success_rate_24h'] . '%'],
                ['Avg Processing Time', $stats['avg_processing_time'] ? $stats['avg_processing_time'] . 's' : 'N/A'],
                ['Last Webhook', $stats['last_webhook'] ? $stats['last_webhook']->diffForHumans() : 'Never'],
            ]
        );

        $this->newLine();
        $this->info('By Gateway:');
        
        foreach ($stats['by_gateway'] as $gateway => $data) {
            $this->line(sprintf(
                '  %s: %d total, %d processed, %d failed (%.2f%% success)',
                ucfirst($gateway),
                $data['total'],
                $data['processed'],
                $data['failed'],
                $data['success_rate']
            ));
        }
        
        $this->newLine();
    }

    private function detectIssues(array $stats): array
    {
        $issues = [];
        $threshold = (float) $this->option('threshold');

        // Check success rate
        if ($stats['success_rate_24h'] < $threshold) {
            $issues[] = "Success rate in last 24h ({$stats['success_rate_24h']}%) is below threshold ({$threshold}%)";
        }

        // Check for recent failures
        if (count($stats['recent_failures']) > 5) {
            $issues[] = count($stats['recent_failures']) . " failed webhooks in the last 24 hours";
        }

        // Check if webhooks are being received
        if ($stats['last_webhook'] && $stats['last_webhook']->lt(now()->subHours(2))) {
            $issues[] = "No webhooks received in the last 2 hours (last: {$stats['last_webhook']->diffForHumans()})";
        }

        // Check pending webhooks
        if ($stats['pending'] > 10) {
            $issues[] = "{$stats['pending']} webhooks stuck in pending status";
        }

        // Check processing time
        if ($stats['avg_processing_time'] && $stats['avg_processing_time'] > 10) {
            $issues[] = "Average processing time ({$stats['avg_processing_time']}s) is high";
        }

        // Check gateway-specific issues
        foreach ($stats['by_gateway'] as $gateway => $data) {
            if ($data['success_rate'] < $threshold) {
                $issues[] = ucfirst($gateway) . " gateway success rate ({$data['success_rate']}%) is below threshold";
            }
        }

        return $issues;
    }

    private function sendAlerts(array $issues, array $stats): void
    {
        $this->info('Sending alerts...');

        try {
            // Get admin users
            $admins = \App\Models\User::whereIn('role', ['admin', 'super-admin'])->get();

            foreach ($admins as $admin) {
                $this->notificationService->createNotification([
                    'title' => '⚠️ Webhook System Alert',
                    'body' => $this->formatAlertMessage($issues, $stats),
                    'type' => 'system',
                    'priority' => 'high',
                    'sender_type' => 'system',
                    'sender_id' => null,
                    'data' => [
                        'issues' => $issues,
                        'stats' => $stats,
                        'timestamp' => now()->toISOString(),
                    ],
                ]);

                $this->notificationService->addRecipients(
                    $this->notificationService->createNotification([
                        'title' => '⚠️ Webhook System Alert',
                        'body' => $this->formatAlertMessage($issues, $stats),
                        'type' => 'system',
                        'priority' => 'high',
                        'sender_type' => 'system',
                        'sender_id' => null,
                    ]),
                    [
                        'user_ids' => [$admin->id],
                        'channels' => ['in-app', 'email'],
                    ]
                );
            }

            // Log to system
            Log::warning('Webhook monitoring alert', [
                'issues' => $issues,
                'stats' => $stats,
            ]);

            $this->info('✅ Alerts sent to ' . $admins->count() . ' admin(s)');
        } catch (\Exception $e) {
            $this->error('Failed to send alerts: ' . $e->getMessage());
            Log::error('Failed to send webhook alerts', [
                'error' => $e->getMessage(),
                'issues' => $issues,
            ]);
        }
    }

    private function formatAlertMessage(array $issues, array $stats): string
    {
        $message = "Webhook system issues detected:\n\n";
        
        foreach ($issues as $issue) {
            $message .= "• $issue\n";
        }
        
        $message .= "\nCurrent Stats:\n";
        $message .= "• Success Rate (24h): {$stats['success_rate_24h']}%\n";
        $message .= "• Failed (24h): " . count($stats['recent_failures']) . "\n";
        $message .= "• Pending: {$stats['pending']}\n";
        
        return $message;
    }
}
