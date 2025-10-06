import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { MoreVertical } from 'lucide-react';
import { toast } from 'sonner';

interface SubscriptionPlan {
    id: number;
    name: string;
    description?: string;
    price_naira: number;
    price_dollar: number;
    billing_cycle: 'monthly' | 'quarterly' | 'biannually' | 'annually';
    duration_months: number;
    features?: string[];
    tags?: string[];
    image_path?: string;
    is_active: boolean;
    subscriptions_count: number;
    created_at: string;
    updated_at: string;
}

interface Stats {
    total_plans: number;
    active_plans: number;
    total_subscriptions: number;
    active_subscriptions: number;
    monthly_revenue: number;
}

interface Props {
    plans: {
        data: SubscriptionPlan[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

export default function SubscriptionIndex({ plans }: Props) {
    const [openDropdown, setOpenDropdown] = useState<number | null>(null);

    const handleToggleStatus = (plan: SubscriptionPlan) => {
        router.patch(route('admin.subscription-plans.toggle-active', plan.id), {}, {
            onSuccess: () => {
                toast.success(`Plan ${plan.is_active ? 'deactivated' : 'activated'} successfully`);
                router.reload({ only: ['plans'] });
            },
            onError: () => {
                toast.error('Failed to update plan status');
            }
        });
        setOpenDropdown(null);
    };

    const handleDuplicate = (plan: SubscriptionPlan) => {
        router.post(route('admin.subscription-plans.duplicate', plan.id), {}, {
            onSuccess: () => {
                toast.success('Plan duplicated successfully');
                router.reload({ only: ['plans'] });
            },
            onError: () => {
                toast.error('Failed to duplicate plan');
            }
        });
        setOpenDropdown(null);
    };

    const handleDelete = (plan: SubscriptionPlan) => {
        if (confirm(`Are you sure you want to delete "${plan.name}"? This action cannot be undone.`)) {
            router.delete(route('admin.subscription-plans.destroy', plan.id), {
                onSuccess: () => {
                    toast.success('Plan deleted successfully');
                    router.reload({ only: ['plans'] });
                },
                onError: () => {
                    toast.error('Failed to delete plan');
                }
            });
        }
        setOpenDropdown(null);
    };

    const formatPrice = (plan: SubscriptionPlan) => {
        return `#${plan.price_naira.toLocaleString()} / $${plan.price_dollar}`;
    };

    const formatBillingCycle = (cycle: string) => {
        return cycle.charAt(0).toUpperCase() + cycle.slice(1);
    };

    const getStatusBadge = (isActive: boolean) => {
        return (
            <Badge variant={isActive ? "default" : "secondary"} className={isActive ? "bg-green-100 text-green-800" : "bg-red-100 text-red-800"}>
                {isActive ? 'Active' : 'Inactive'}
            </Badge>
        );
    };

    // Close dropdowns when clicking outside
    const handlePageClick = () => {
        setOpenDropdown(null);
    };

    return (
        <AdminLayout pageTitle="Subscription & Plans Management" showRightSidebar={false}>
            <Head title="Subscription & Plans Management" />
            
            <div className="space-y-6" onClick={handlePageClick}>
                {/* Breadcrumbs */}
                <Breadcrumbs
                    breadcrumbs={[
                        { title: 'Dashboard', href: route('admin.dashboard') },
                        { title: 'Subscription & Plans Management', href: route('admin.subscription-plans.index') }
                    ]}
                />

                {/* Header */}
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">Subscription & Plans Management</h1>
                    <Button 
                        onClick={() => router.visit(route('admin.subscription-plans.create'))}
                        className="bg-teal-600 hover:bg-teal-700 text-white"
                    >
                        Create / Edit Plan
                    </Button>
                </div>

                {/* Plans Table */}
                <div className="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left">
                                        <Checkbox />
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Plan Name
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Price (/USD)
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Billing Cycle
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Enrolled Users
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {plans.data.map((plan) => (
                                    <tr key={plan.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4">
                                            <Checkbox />
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {plan.name}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                                            {formatPrice(plan)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                                            {formatBillingCycle(plan.billing_cycle)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                                            {plan.subscriptions_count}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-center">
                                            {getStatusBadge(plan.is_active)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div onClick={(e) => e.stopPropagation()}>
                                                <DropdownMenu 
                                                    open={openDropdown === plan.id}
                                                    onOpenChange={(open) => setOpenDropdown(open ? plan.id : null)}
                                                >
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" size="sm">
                                                            <MoreVertical className="h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end" className="w-48">
                                                        <DropdownMenuItem
                                                            onClick={() => handleDuplicate(plan)}
                                                        >
                                                            Duplicate Plan
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            onClick={() => router.visit(route('admin.subscription-plans.edit', plan.id))}
                                                        >
                                                            Edit Plan Details
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            onClick={() => handleToggleStatus(plan)}
                                                        >
                                                            {plan.is_active ? 'Pause Plan' : 'Activate Plan'}
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            onClick={() => router.visit(route('admin.subscription-plans.enrolled-users', plan.id))}
                                                        >
                                                            View enrolled users
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            onClick={() => handleDelete(plan)}
                                                            className="text-red-600"
                                                        >
                                                            Delete
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Pagination */}
                {plans.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <div className="text-sm text-gray-700">
                            Showing {((plans.current_page - 1) * plans.per_page) + 1} to{' '}
                            {Math.min(plans.current_page * plans.per_page, plans.total)} of{' '}
                            {plans.total} results
                        </div>
                        <div className="flex gap-1">
                            {/* Previous Button */}
                            {plans.current_page > 1 && (
                                <Button
                                    variant="outline"
                                    className="rounded-full"
                                    size="sm"
                                    onClick={() => router.visit(route('admin.subscription-plans.index', { 
                                        page: plans.current_page - 1 
                                    }))}
                                >
                                    Previous
                                </Button>
                            )}

                            {/* Page Numbers */}
                            {(() => {
                                const currentPage = plans.current_page;
                                const lastPage = plans.last_page;
                                const maxVisiblePages = 5;
                                
                                let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
                                const endPage = Math.min(lastPage, startPage + maxVisiblePages - 1);
                                
                                if (endPage - startPage + 1 < maxVisiblePages) {
                                    startPage = Math.max(1, endPage - maxVisiblePages + 1);
                                }

                                const pages = [];
                                
                                if (startPage > 1) {
                                    pages.push(
                                        <Button
                                            key={1}
                                            variant="outline"
                                            className="rounded-full"
                                            size="sm"
                                            onClick={() => router.visit(route('admin.subscription-plans.index', { page: 1 }))}
                                        >
                                            1
                                        </Button>
                                    );
                                    if (startPage > 2) {
                                        pages.push(
                                            <span key="ellipsis1" className="px-2 py-1 text-sm text-gray-500">
                                                ...
                                            </span>
                                        );
                                    }
                                }

                                for (let i = startPage; i <= endPage; i++) {
                                    pages.push(
                                        <Button
                                            key={i}
                                            variant={i === currentPage ? "default" : "outline"}
                                            className="rounded-full"
                                            size="sm"
                                            onClick={() => router.visit(route('admin.subscription-plans.index', { page: i }))}
                                        >
                                            {i}
                                        </Button>
                                    );
                                }

                                if (endPage < lastPage) {
                                    if (endPage < lastPage - 1) {
                                        pages.push(
                                            <span key="ellipsis2" className="px-2 py-1 text-sm text-gray-500">
                                                ...
                                            </span>
                                        );
                                    }
                                    pages.push(
                                        <Button
                                            key={lastPage}
                                            variant="outline"
                                            className="rounded-full"
                                            size="sm"
                                            onClick={() => router.visit(route('admin.subscription-plans.index', { page: lastPage }))}
                                        >
                                            {lastPage}
                                        </Button>
                                    );
                                }

                                return pages;
                            })()}

                            {/* Next Button */}
                            {plans.current_page < plans.last_page && (
                                <Button
                                    variant="outline"
                                    className="rounded-full"
                                    size="sm"
                                    onClick={() => router.visit(route('admin.subscription-plans.index', { 
                                        page: plans.current_page + 1 
                                    }))}
                                >
                                    Next
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
