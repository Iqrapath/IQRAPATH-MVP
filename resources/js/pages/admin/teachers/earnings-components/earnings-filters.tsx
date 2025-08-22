import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { X } from 'lucide-react';

interface Filters {
  type?: string;
  status?: string;
  date_from?: string;
  date_to?: string;
  payout_status?: string;
  payout_date_from?: string;
  payout_date_to?: string;
  [key: string]: string | undefined;
}

interface Props {
  filters: Filters;
}

export default function EarningsFilters({ filters }: Props) {
  const [localFilters, setLocalFilters] = useState<Filters>(filters);

  const handleFilterChange = (key: keyof Filters, value: string) => {
    setLocalFilters(prev => ({
      ...prev,
      [key]: value || undefined,
    }));
  };

  const applyFilters = () => {
    router.get(route('admin.teachers.earnings', route().params.teacher), localFilters, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  const clearFilters = () => {
    const clearedFilters: Filters = {};
    setLocalFilters(clearedFilters);
    router.get(route('admin.teachers.earnings', route().params.teacher), clearedFilters, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  const hasActiveFilters = Object.values(filters).some(value => value !== undefined && value !== '');

  return (
    <div className="space-y-6">
      {/* Transaction Filters */}
      <div>
        <h3 className="text-lg font-medium text-gray-900 mb-4">Transaction Filters</h3>
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <Label htmlFor="transaction-type">Transaction Type</Label>
            <Select
              value={localFilters.type || 'all'}
              onValueChange={(value) => handleFilterChange('type', value === 'all' ? '' : value)}
            >
              <SelectTrigger>
                <SelectValue placeholder="All types" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All types</SelectItem>
                <SelectItem value="session_payment">Session Payment</SelectItem>
                <SelectItem value="withdrawal">Withdrawal</SelectItem>
                <SelectItem value="referral_bonus">Referral Bonus</SelectItem>
                <SelectItem value="system_adjustment">System Adjustment</SelectItem>
                <SelectItem value="refund">Refund</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div>
            <Label htmlFor="transaction-status">Status</Label>
            <Select
              value={localFilters.status || 'all'}
              onValueChange={(value) => handleFilterChange('status', value === 'all' ? '' : value)}
            >
              <SelectTrigger>
                <SelectValue placeholder="All statuses" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All statuses</SelectItem>
                <SelectItem value="completed">Completed</SelectItem>
                <SelectItem value="pending">Pending</SelectItem>
                <SelectItem value="failed">Failed</SelectItem>
                <SelectItem value="cancelled">Cancelled</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div>
            <Label htmlFor="date-from">Date From</Label>
            <Input
              id="date-from"
              type="date"
              value={localFilters.date_from || ''}
              onChange={(e) => handleFilterChange('date_from', e.target.value)}
            />
          </div>

          <div>
            <Label htmlFor="date-to">Date To</Label>
            <Input
              id="date-to"
              type="date"
              value={localFilters.date_to || ''}
              onChange={(e) => handleFilterChange('date_to', e.target.value)}
            />
          </div>
        </div>
      </div>

      {/* Payout Request Filters */}
      <div>
        <h3 className="text-lg font-medium text-gray-900 mb-4">Payout Request Filters</h3>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <Label htmlFor="payout-status">Payout Status</Label>
            <Select
              value={localFilters.payout_status || 'all'}
              onValueChange={(value) => handleFilterChange('payout_status', value === 'all' ? '' : value)}
            >
              <SelectTrigger>
                <SelectValue placeholder="All statuses" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All statuses</SelectItem>
                <SelectItem value="pending">Pending</SelectItem>
                <SelectItem value="approved">Approved</SelectItem>
                <SelectItem value="rejected">Rejected</SelectItem>
                <SelectItem value="completed">Completed</SelectItem>
                <SelectItem value="processing">Processing</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div>
            <Label htmlFor="payout-date-from">Request Date From</Label>
            <Input
              id="payout-date-from"
              type="date"
              value={localFilters.payout_date_from || ''}
              onChange={(e) => handleFilterChange('payout_date_from', e.target.value)}
            />
          </div>

          <div>
            <Label htmlFor="payout-date-to">Request Date To</Label>
            <Input
              id="payout-date-to"
              type="date"
              value={localFilters.payout_date_to || ''}
              onChange={(e) => handleFilterChange('payout_date_to', e.target.value)}
            />
          </div>
        </div>
      </div>

      {/* Filter Actions */}
      <div className="flex items-center gap-3 pt-4 border-t">
        <Button onClick={applyFilters} className="bg-teal-600 hover:bg-teal-700">
          Apply Filters
        </Button>
        
        {hasActiveFilters && (
          <Button 
            variant="outline" 
            onClick={clearFilters}
            className="flex items-center gap-2"
          >
            <X className="h-4 w-4" />
            Clear All
          </Button>
        )}

        {hasActiveFilters && (
          <div className="text-sm text-gray-500">
            {Object.values(filters).filter(v => v !== undefined && v !== '').length} filter(s) active
          </div>
        )}
      </div>
    </div>
  );
}
