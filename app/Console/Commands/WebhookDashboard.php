<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WebhookEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WebhookDashboard extends Command
{
    protected $signature = 'webhook:dashboard {--refresh=5 : Refresh interval in seconds}';
    
    protected $description = 'Display real-time webhook monitoring dashboard';

    public function handle(): int
    {
        $refreshInterval = (int) $this->option('refresh');
        
        $this->info('Starting Webhook Dashboard...');
        $this->info('Press Ctrl+C to exit');
        $this->newLine();
        
        while (true) {
            $this->clearScreen();
            $this->displayDashboard();
            sleep($refreshInterval);
        }
        
        return Command::SUCCESS;
    }

    private function clearScreen(): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            system('cls');
        } else {
            system('clear');
        }
    }

    private function displayDashboard(): void
    {
        $this->displayHeader();
        $this->displayOverview();
        $this->displayGatewayStats();
        $this->displayRecentEvents();
        $this->displayPerformanceMetrics();
        $this->displayAlerts();
        $this->displayFooter();
    }

    private function displayHeader(): void
    {
        $this->line('╔════════════════════════════════════════════════════════════════════════╗');
        $this->line('║                    WEBHOOK MONITORING DASHBOARD                        ║');
        $this->line('║                    ' . now()->format('Y-m-d H:i:s') . '                ║');
        $this->line('╚════════════════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    private function displayOverview(): void
    {
        $stats = $this->getOverviewStats();
        
        $this->line('┌─ OVERVIEW ─────────────────────────────────────────────────────────────┐');
        $this->line(sprintf(
            '│ Total Events: %-10d  Processed: %-10d  Failed: %-10d │',
            $stats['total'],
            $stats['processed'],
            $stats['failed']
        ));
        $this->line(sprintf(
            '│ Success Rate: %-10s  Pending: %-10d  Last Hour: %-8d │',
            $stats['success_rate'] . '%',
            $stats['pending'],
            $stats['last_hour']
        ));
        $this->line('└────────────────────────────────────────────────────────────────────────┘');
        $this->newLine();
    }

    private function displayGatewayStats(): void
    {
        $gateways = $this->getGatewayStats();
        
        $this->line('┌─ BY GATEWAY ───────────────────────────────────────────────────────────┐');
        
        foreach ($gateways as $gateway) {
            $successRate = $gateway->total > 0 
                ? round(($gateway->processed / $gateway->total) * 100, 1) 
                : 0;
            
            $status = $successRate >= 95 ? '✓' : ($successRate >= 80 ? '⚠' : '✗');
            
            $this->line(sprintf(
                '│ %s %-12s  Total: %-6d  Success: %-6d  Rate: %-6s │',
                $status,
                ucfirst($gateway->gateway),
                $gateway->total,
                $gateway->processed,
                $successRate . '%'
            ));
        }
        
        $this->line('└────────────────────────────────────────────────────────────────────────┘');
        $this->newLine();
    }

    private function displayRecentEvents(): void
    {
        $recent = WebhookEvent::latest()
            ->take(5)
            ->get(['gateway', 'type', 'status', 'created_at']);
        
        $this->line('┌─ RECENT EVENTS ────────────────────────────────────────────────────────┐');
        
        foreach ($recent as $event) {
            $statusIcon = match($event->status) {
                'processed' => '✓',
                'failed' => '✗',
                'pending' => '⋯',
                default => '?'
            };
            
            $time = $event->created_at->diffForHumans();
            
            $this->line(sprintf(
                '│ %s %-10s  %-25s  %-20s │',
                $statusIcon,
                ucfirst($event->gateway),
                substr($event->type, 0, 25),
                $time
            ));
        }
        
        $this->line('└────────────────────────────────────────────────────────────────────────┘');
        $this->newLine();
    }

    private function displayPerformanceMetrics(): void
    {
        $metrics = $this->getPerformanceMetrics();
        
        $this->line('┌─ PERFORMANCE ─────────────────────────────────────────────────────┐');
        $this->line(sprintf(
            '│ Avg Processing Time: %-8s  Events/Hour: %-8d  Peak Hour: %-6d │',
            $metrics['avg_time'] . 's',
            $metrics['events_per_hour'],
            $metrics['peak_hour']
));
        $this->line(sprintf(
            '│ Last 24h: %-10d  Last 7d: %-10d  Last 30d: %-10d │',
            $metrics['last_24h'],
            $metrics['last_7d'],
            $metrics['last_30d']
        ));
        $this->line('└────────────────────────────────────────────────────────────────────────┘');
        $this->newLine();
    }

    private function displayAlerts(): void
    {
        $alerts = $this->getAlerts();
        
        if (empty($alerts)) {
            $this->line('┌─ ALERTS ───────────────────────────────────────────────────────────────┐');
            $this->line('│ ✓ No alerts - All systems operational                                  │');
            $this->line('└────────────────────────────────────────────────────────────────────────┘');
        } else {
            $this->line('┌─ ALERTS ───────────────────────────────────────────────────────────────┐');
            foreach ($alerts as $alert) {
                $icon = $alert['severity'] === 'critical' ? '✗' : '⚠';
                $this->line(sprintf('│ %s %s', $icon, str_pad($alert['message'], 68) . '│'));
            }
            $this->line('└────────────────────────────────────────────────────────────────────────┘');
        }
        $this->newLine();
    }

    private function displayFooter(): void
    {
        $this->line('Commands: [R]efresh now  [Q]uit  [H]elp');
        $this->line('Auto-refresh: ' . $this->option('refresh') . 's');
    }

    private function getOverviewStats(): array
    {
        return [
            'total' => WebhookEvent::count(),
            'processed' => WebhookEvent::where('status', 'processed')->count(),
            'failed' => WebhookEvent::where('status', 'failed')->count(),
            'pending' => WebhookEvent::where('status', 'pending')->count(),
            'last_hour' => WebhookEvent::where('created_at', '>=', now()->subHour())->count(),
            'success_rate' => $this->calculateSuccessRate(),
        ];
    }

    private function getGatewayStats()
    {
        return WebhookEvent::select(
            'gateway',
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN status = "processed" THEN 1 ELSE 0 END) as processed'),
            DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
        )
        ->groupBy('gateway')
        ->get();
    }

    private function getPerformanceMetrics(): array
    {
        return [
            'avg_time' => $this->calculateAvgProcessingTime(),
            'events_per_hour' => $this->calculateEventsPerHour(),
            'peak_hour' => $this->calculatePeakHour(),
            'last_24h' => WebhookEvent::where('created_at', '>=', now()->subDay())->count(),
            'last_7d' => WebhookEvent::where('created_at', '>=', now()->subDays(7))->count(),
            'last_30d' => WebhookEvent::where('created_at', '>=', now()->subDays(30))->count(),
        ];
    }

    private function getAlerts(): array
    {
        $alerts = [];
        
        // Check for recent failures
        $recentFailures = WebhookEvent::where('status', 'failed')
            ->where('created_at', '>=', now()->subHour())
            ->count();
        
        if ($recentFailures > 5) {
            $alerts[] = [
                'severity' => 'critical',
                'message' => "High failure rate: {$recentFailures} failures in last hour"
            ];
        }
        
        // Check for pending events
        $oldPending = WebhookEvent::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(5))
            ->count();
        
        if ($oldPending > 0) {
            $alerts[] = [
                'severity' => 'warning',
                'message' => "{$oldPending} events pending for more than 5 minutes"
            ];
        }
        
        // Check success rate
        $successRate = $this->calculateSuccessRate();
        if ($successRate < 95 && WebhookEvent::count() > 10) {
            $alerts[] = [
                'severity' => 'warning',
                'message' => "Success rate below 95%: {$successRate}%"
            ];
        }
        
        return $alerts;
    }

    private function calculateSuccessRate(): float
    {
        $total = WebhookEvent::count();
        if ($total === 0) {
            return 100.0;
        }
        
        $processed = WebhookEvent::where('status', 'processed')->count();
        return round(($processed / $total) * 100, 1);
    }

    private function calculateAvgProcessingTime(): string
    {
        $avg = WebhookEvent::whereNotNull('processed_at')
            ->where('created_at', '>=', now()->subDay())
            ->get()
            ->avg(function ($event) {
                return $event->created_at->diffInSeconds($event->processed_at);
            });
        
        return $avg ? number_format($avg, 2) : '0.00';
    }

    private function calculateEventsPerHour(): int
    {
        $count = WebhookEvent::where('created_at', '>=', now()->subHour())->count();
        return $count;
    }

    private function calculatePeakHour(): int
    {
        $peak = WebhookEvent::select(
            DB::raw('HOUR(created_at) as hour'),
            DB::raw('COUNT(*) as count')
        )
        ->where('created_at', '>=', now()->subDay())
        ->groupBy('hour')
        ->orderBy('count', 'desc')
        ->first();
        
        return $peak ? $peak->count : 0;
    }
}