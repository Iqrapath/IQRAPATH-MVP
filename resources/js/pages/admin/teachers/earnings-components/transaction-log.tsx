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
import { Eye } from 'lucide-react';

interface Transaction {
  id: number;
  uuid: string;
  date: string;
  description: string;
  amount: number;
  formatted_amount: string;
  type: string;
  status: string;
  session?: {
    id: number;
    subject: string;
    student_name?: string;
  };
  created_by?: {
    name: string;
    role: string;
  };
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
  type?: string;
  status?: string;
  date_from?: string;
  date_to?: string;
}

interface Props {
  transactions: PaginatedData<Transaction>;
  filters: Filters;
}

export default function TransactionLog({ transactions, filters }: Props) {
  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    });
  };

  const getStatusBadge = (status: string) => {
    const statusStyles = {
      completed: 'bg-green-100 text-green-800 border-green-200',
      pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
      failed: 'bg-red-100 text-red-800 border-red-200',
      cancelled: 'bg-gray-100 text-gray-800 border-gray-200',
    };

    return (
      <Badge className={statusStyles[status as keyof typeof statusStyles] || statusStyles.pending}>
        {status.charAt(0).toUpperCase() + status.slice(1)}
      </Badge>
    );
  };

  const getTypeBadge = (type: string) => {
    const typeStyles = {
      session_payment: 'bg-blue-100 text-blue-800 border-blue-200',
      withdrawal: 'bg-purple-100 text-purple-800 border-purple-200',
      referral_bonus: 'bg-teal-100 text-teal-800 border-teal-200',
      system_adjustment: 'bg-orange-100 text-orange-800 border-orange-200',
      refund: 'bg-red-100 text-red-800 border-red-200',
    };

    const displayType = type.split('_').map(word => 
      word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ');

    return (
      <Badge variant="outline" className={typeStyles[type as keyof typeof typeStyles] || 'bg-gray-100 text-gray-800 border-gray-200'}>
        {displayType}
      </Badge>
    );
  };

  const handlePageChange = (page: number) => {
    router.get(route('admin.teachers.earnings', route().params.teacher), {
      ...filters,
      page: page,
    }, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  const handleViewTransaction = (transaction: Transaction) => {
    // TODO: Implement view transaction details
    console.log('View transaction:', transaction.uuid);
  };

  return (
    <div className="space-y-4">
      {/* Table */}
      <div className="overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Date</TableHead>
              <TableHead>Description</TableHead>
              <TableHead>Amount</TableHead>
              <TableHead>Type</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {transactions.data.length === 0 ? (
              <TableRow>
                <TableCell colSpan={6} className="text-center py-8 text-gray-500">
                  No transactions found
                </TableCell>
              </TableRow>
            ) : (
              transactions.data.map((transaction) => (
                <TableRow key={transaction.id}>
                  <TableCell className="font-medium">
                    {formatDate(transaction.date)}
                  </TableCell>
                  <TableCell>
                    <div>
                      <p className="font-medium">{transaction.description}</p>
                      {transaction.session && (
                        <p className="text-sm text-gray-500">
                          Subject: {transaction.session.subject}
                          {transaction.session.student_name && (
                            <span> • Student: {transaction.session.student_name}</span>
                          )}
                        </p>
                      )}
                      {transaction.created_by && (
                        <p className="text-xs text-gray-400">
                          By: {transaction.created_by.name} ({transaction.created_by.role})
                        </p>
                      )}
                    </div>
                  </TableCell>
                  <TableCell>
                    <span className={`font-semibold ${
                      transaction.type === 'withdrawal' ? 'text-red-600' : 'text-green-600'
                    }`}>
                      {transaction.type === 'withdrawal' ? '-' : '+'}₦{transaction.formatted_amount}
                    </span>
                  </TableCell>
                  <TableCell>
                    {getTypeBadge(transaction.type)}
                  </TableCell>
                  <TableCell>
                    {getStatusBadge(transaction.status)}
                  </TableCell>
                  <TableCell>
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => handleViewTransaction(transaction)}
                      className="h-8 w-8 p-0"
                    >
                      <Eye className="h-4 w-4" />
                    </Button>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>

      {/* Pagination */}
      {transactions.last_page > 1 && (
        <div className="flex items-center justify-between">
          <div className="text-sm text-gray-500">
            Showing {transactions.from} to {transactions.to} of {transactions.total} transactions
          </div>
          
          <Pagination
            currentPage={transactions.current_page}
            totalPages={transactions.last_page}
            onPageChange={handlePageChange}
          />
        </div>
      )}
    </div>
  );
}
