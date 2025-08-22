import React from 'react';
import { router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Pagination } from '@/components/ui/pagination';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { CheckCircle, XCircle, Eye, MoreHorizontal } from 'lucide-react';

interface PayoutRequest {
  id: number;
  uuid: string;
  request_date: string;
  amount: number;
  formatted_amount: string;
  payment_method: string;
  payment_method_display: string;
  status: string;
  status_display: string;
  processed_date?: string;
  processed_by?: {
    name: string;
  };
  notes?: string;
  created_at: string;
}

interface PaginatedData<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

interface Filters {
  payout_status?: string;
  payout_date_from?: string;
  payout_date_to?: string;
}

interface Props {
  payoutRequests: PaginatedData<PayoutRequest>;
  filters: Filters;
}

export default function PayoutRequests({ payoutRequests, filters }: Props) {
  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    });
  };

  const getStatusBadge = (status: string) => {
    const statusStyles = {
      pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
      approved: 'bg-green-100 text-green-800 border-green-200',
      rejected: 'bg-red-100 text-red-800 border-red-200',
      completed: 'bg-blue-100 text-blue-800 border-blue-200',
      processing: 'bg-purple-100 text-purple-800 border-purple-200',
    };

    return (
      <Badge className={statusStyles[status as keyof typeof statusStyles] || statusStyles.pending}>
        {status.charAt(0).toUpperCase() + status.slice(1)}
      </Badge>
    );
  };

  const handlePageChange = (page: number) => {
    router.get(route('admin.teachers.earnings', route().params.teacher), {
      ...filters,
      payout_page: page,
    }, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  const handleViewRequest = (request: PayoutRequest) => {
    // TODO: Implement view payout request details
    console.log('View payout request:', request.uuid);
  };

  const handleApproveRequest = (request: PayoutRequest) => {
    if (confirm(`Are you sure you want to approve payout request ${request.uuid} for ₦${request.formatted_amount}?`)) {
      // TODO: Implement approve request API call
      console.log('Approve payout request:', request.uuid);
    }
  };

  const handleRejectRequest = (request: PayoutRequest) => {
    const reason = prompt('Please provide a reason for rejection:');
    if (reason) {
      // TODO: Implement reject request API call
      console.log('Reject payout request:', request.uuid, 'Reason:', reason);
    }
  };

  return (
    <div className="space-y-4">
      {/* Table */}
      <div className="overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Request Date</TableHead>
              <TableHead>Amount</TableHead>
              <TableHead>Payment Method</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Processed</TableHead>
              <TableHead>Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {payoutRequests.data.length === 0 ? (
              <TableRow>
                <TableCell colSpan={6} className="text-center py-8 text-gray-500">
                  No payout requests found
                </TableCell>
              </TableRow>
            ) : (
              payoutRequests.data.map((request) => (
                <TableRow key={request.id}>
                  <TableCell className="font-medium">
                    <div>
                      <p>{formatDate(request.request_date)}</p>
                      <p className="text-xs text-gray-500">#{request.uuid}</p>
                    </div>
                  </TableCell>
                  <TableCell>
                    <span className="font-semibold text-green-600">
                      ₦{request.formatted_amount}
                    </span>
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline">
                      {request.payment_method_display}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    {getStatusBadge(request.status)}
                  </TableCell>
                  <TableCell>
                    {request.processed_date ? (
                      <div>
                        <p className="text-sm">{formatDate(request.processed_date)}</p>
                        {request.processed_by && (
                          <p className="text-xs text-gray-500">
                            By: {request.processed_by.name}
                          </p>
                        )}
                      </div>
                    ) : (
                      <span className="text-gray-400 text-sm">Not processed</span>
                    )}
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-2">
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => handleViewRequest(request)}
                        className="h-8 w-8 p-0"
                      >
                        <Eye className="h-4 w-4" />
                      </Button>

                      {request.status === 'pending' && (
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                              <MoreHorizontal className="h-4 w-4" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end">
                            <DropdownMenuItem
                              onClick={() => handleApproveRequest(request)}
                              className="text-green-600"
                            >
                              <CheckCircle className="h-4 w-4 mr-2" />
                              Approve Request
                            </DropdownMenuItem>
                            <DropdownMenuItem
                              onClick={() => handleRejectRequest(request)}
                              className="text-red-600"
                            >
                              <XCircle className="h-4 w-4 mr-2" />
                              Reject Request
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      )}
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>

      {/* Pagination */}
      {payoutRequests.last_page > 1 && (
        <div className="flex items-center justify-between">
          <div className="text-sm text-gray-500">
            Showing {payoutRequests.from} to {payoutRequests.to} of {payoutRequests.total} payout requests
          </div>
          
          <Pagination
            currentPage={payoutRequests.current_page}
            totalPages={payoutRequests.last_page}
            onPageChange={handlePageChange}
          />
        </div>
      )}
    </div>
  );
}
