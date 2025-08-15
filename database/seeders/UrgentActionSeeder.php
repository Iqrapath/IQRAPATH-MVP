<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UrgentAction;

class UrgentActionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $urgentActions = [
            [
                'type' => UrgentAction::TYPE_WITHDRAWAL_REQUESTS,
                'title' => 'Withdrawal Requests Pending Approval',
                'action_text' => 'View Requests',
                'action_url' => '/admin/payout-requests',
                'priority_level' => UrgentAction::PRIORITY_CRITICAL,
                'business_rules' => [
                    'min_count' => 1,
                    'min_amount' => 100,
                    'max_days' => 3
                ],
                'permissions' => [
                    'roles' => ['super-admin', 'admin'],
                    'permissions' => ['view_withdrawals', 'approve_withdrawals']
                ]
            ],
            [
                'type' => UrgentAction::TYPE_TEACHER_APPLICATIONS,
                'title' => 'Teacher Applications Awaiting Verification',
                'action_text' => 'Review Now',
                'action_url' => '/admin/verification-requests',
                'priority_level' => UrgentAction::PRIORITY_HIGH,
                'business_rules' => [
                    'min_count' => 1,
                    'max_days' => 2
                ],
                'permissions' => [
                    'roles' => ['super-admin', 'admin'],
                    'permissions' => ['view_teacher_applications', 'verify_teachers']
                ]
            ],
            [
                'type' => UrgentAction::TYPE_SESSION_ASSIGNMENTS,
                'title' => 'Sessions Pending Teacher Assignment',
                'action_text' => 'Assign Teachers',
                'action_url' => '/admin/teaching-sessions',
                'priority_level' => UrgentAction::PRIORITY_HIGH,
                'business_rules' => [
                    'min_count' => 1,
                    'max_days' => 1
                ],
                'permissions' => [
                    'roles' => ['super-admin', 'admin', 'support'],
                    'permissions' => ['view_sessions', 'assign_teachers']
                ]
            ],
            [
                'type' => UrgentAction::TYPE_DISPUTES,
                'title' => 'Reported Dispute Requires Resolution',
                'action_text' => 'Open Dispute',
                'action_url' => '/admin/disputes',
                'priority_level' => UrgentAction::PRIORITY_CRITICAL,
                'business_rules' => [
                    'min_count' => 1,
                    'max_days' => 1
                ],
                'permissions' => [
                    'roles' => ['super-admin', 'admin', 'moderator'],
                    'permissions' => ['view_disputes', 'resolve_disputes']
                ]
            ],
            [
                'type' => UrgentAction::TYPE_PAYMENT_FAILURES,
                'title' => 'Payment Failures Require Attention',
                'action_text' => 'Review Payments',
                'action_url' => '/admin/transactions',
                'priority_level' => UrgentAction::PRIORITY_HIGH,
                'business_rules' => [
                    'min_count' => 1,
                    'max_days' => 1,
                    'min_amount' => 50
                ],
                'permissions' => [
                    'roles' => ['super-admin', 'admin'],
                    'permissions' => ['view_payments', 'resolve_payment_issues']
                ]
            ],
            [
                'type' => UrgentAction::TYPE_ACCOUNT_SUSPENSIONS,
                'title' => 'Account Suspensions Need Review',
                'action_text' => 'Review Accounts',
                'action_url' => '/admin/users',
                'priority_level' => UrgentAction::PRIORITY_MEDIUM,
                'business_rules' => [
                    'min_count' => 1,
                    'max_days' => 2
                ],
                'permissions' => [
                    'roles' => ['super-admin', 'admin'],
                    'permissions' => ['view_accounts', 'manage_suspensions']
                ]
            ],
            [
                'type' => UrgentAction::TYPE_COMPLIANCE_ALERTS,
                'title' => 'Compliance Alerts Require Action',
                'action_text' => 'View Alerts',
                'action_url' => '/admin/verification-requests',
                'priority_level' => UrgentAction::PRIORITY_CRITICAL,
                'business_rules' => [
                    'min_count' => 1,
                    'max_days' => 7
                ],
                'permissions' => [
                    'roles' => ['super-admin'],
                    'permissions' => ['view_compliance', 'resolve_compliance_issues']
                ]
            ],
            [
                'type' => UrgentAction::TYPE_NEW_USER_REGISTRATION,
                'title' => 'New Users Awaiting Role Assignment',
                'action_text' => 'Assign Roles',
                'action_url' => '/admin/users',
                'priority_level' => UrgentAction::PRIORITY_MEDIUM,
                'business_rules' => [
                    'min_count' => 1,
                    'max_hours' => 24
                ],
                'permissions' => [
                    'roles' => ['super-admin', 'admin'],
                    'permissions' => ['view_users', 'assign_roles']
                ]
            ]
        ];

        foreach ($urgentActions as $action) {
            UrgentAction::updateOrCreate(
                ['type' => $action['type']],
                $action
            );
        }
    }
}
