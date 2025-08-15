import { Card } from "@/components/ui/card";
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { format } from 'date-fns';
import { useState } from 'react';
import { Select, SelectContent, SelectItem, SelectTrigger } from "@/components/ui/select";

interface RevenueSummaryProps {
  year: number;
  months: Array<{
    month: string;
    revenue: number;
  }>;
  currentMonthRevenue: number;
  totalRevenue: number;
}

type TimeRange = 'this-month' | 'last-month' | '3-months' | '6-months' | 'year';

export function RevenueSummary({
  year,
  months,
  currentMonthRevenue,
  totalRevenue
}: RevenueSummaryProps) {
  // State for selected time range
  const [timeRange, setTimeRange] = useState<TimeRange>('year');
  
  const currentDate = new Date();
  const currentMonthIndex = currentDate.getMonth();
  const currentMonthName = format(currentDate, 'MMM');
  
  // Filter data based on the selected time range
  const getFilteredData = () => {
    if (!months || months.length === 0) return [];
    
    switch (timeRange) {
      case 'this-month':
        // Find current month by name
        const currentMonth = months.find(m => m.month === currentMonthName);
        return currentMonth ? [currentMonth] : [];
        
      case 'last-month':
        const lastMonthIndex = currentMonthIndex === 0 ? 11 : currentMonthIndex - 1;
        const lastMonthName = format(new Date(year, lastMonthIndex), 'MMM');
        const lastMonth = months.find(m => m.month === lastMonthName);
        return lastMonth ? [lastMonth] : [];
        
      case '3-months':
        // Last 3 months (or all if less than 3)
        return months.length <= 3 
          ? [...months] 
          : months.slice(-3);
        
      case '6-months':
        // Last 6 months (or all if less than 6)
        return months.length <= 6 
          ? [...months] 
          : months.slice(-6);
        
      case 'year':
      default:
        // All months
        return [...months];
    }
  };
  
  // Get data filtered by the selected time range
  const filteredData = getFilteredData();
  
  // Find the highlighted point (current month for this display)
  const highlightedPoint = months.find(m => m.month === currentMonthName) || null;
  
  // Display values
  const displayDate = highlightedPoint 
    ? `${highlightedPoint.month} ${currentDate.getDate()}, ${year}` 
    : format(currentDate, 'MMMM d, yyyy');
    
  const displayAmount = highlightedPoint 
    ? highlightedPoint.revenue.toLocaleString() 
    : '0';
  
  // Y-axis ticks
  const yAxisTicks = [0, 250000, 500000, 750000, 1000000];

  // Get display name for the selected time range
  const getTimeRangeDisplayName = () => {
    switch(timeRange) {
      case 'this-month': return 'This Month';
      case 'last-month': return 'Last Month';
      case '3-months': return '3 Months';
      case '6-months': return '6 Months';
      case 'year': return 'This Year';
      default: return 'This Month';
    }
  };
  
  // Handle empty data case
  if (filteredData.length === 0) {
    return (
      <Card className="p-6 rounded-2xl shadow-sm border">
        <div className="flex justify-between items-center">
          <h2 className="text-xl font-semibold text-gray-800">Revenue Summary</h2>
          <Select 
            value={timeRange} 
            onValueChange={(value) => setTimeRange(value as TimeRange)}
          >
            <SelectTrigger className="bg-gray-100 border-0 px-4 py-2 h-8 text-sm w-[130px] rounded-md cursor-pointer">
              <div className="flex items-center justify-between w-full">
                <span>{getTimeRangeDisplayName()}</span>
              </div>
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="this-month">This Month</SelectItem>
              <SelectItem value="last-month">Last Month</SelectItem>
              <SelectItem value="3-months">3 Months</SelectItem>
              <SelectItem value="6-months">6 Months</SelectItem>
              <SelectItem value="year">This Year</SelectItem>
            </SelectContent>
          </Select>
        </div>
        
        <div className="h-[350px] mt-8 flex items-center justify-center">
          <p className="text-gray-500">No revenue data available for the selected time period.</p>
        </div>
      </Card>
    );
  }

  return (
    <Card className="p-6 rounded-2xl shadow-sm border">
      <div className="flex justify-between items-center">
        <h2 className="text-xl font-semibold text-gray-800">Revenue Summary</h2>
        <Select 
          value={timeRange} 
          onValueChange={(value) => setTimeRange(value as TimeRange)}
        >
          <SelectTrigger className="bg-gray-100 border-0 px-4 py-2 h-8 text-sm w-[130px] rounded-md cursor-pointer">
            <div className="flex items-center justify-between w-full">
              <span>{getTimeRangeDisplayName()}</span>
            </div>
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="this-month">This Month</SelectItem>
            <SelectItem value="last-month">Last Month</SelectItem>
            <SelectItem value="3-months">3 Months</SelectItem>
            <SelectItem value="6-months">6 Months</SelectItem>
            <SelectItem value="year">This Year</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div className="h-[350px] mt-8">
        <ResponsiveContainer width="100%" height="100%">
          <LineChart
            data={filteredData}
            margin={{
              top: 10,
              right: 30,
              left: 0,
              bottom: 30,
            }}
          >
            <CartesianGrid 
              horizontal={true} 
              vertical={false} 
              stroke="#e5e7eb" 
              strokeDasharray="5 5"
            />
            <XAxis 
              dataKey="month" 
              axisLine={false} 
              tickLine={false}
              dy={10}
              tick={{ fontSize: 12 }}
              padding={{ left: 30, right: 30 }}
            />
            <YAxis 
              axisLine={false} 
              tickLine={false}
              tickFormatter={(value) => `${value/1000}K`}
              dx={-10}
              tick={{ fontSize: 12, fill: '#6b7280' }}
              domain={[0, 1000000]}
              ticks={yAxisTicks}
            />
            <Tooltip 
              formatter={(value: number) => [`$${Number(value).toLocaleString()}`, 'Revenue']}
              contentStyle={{ 
                borderRadius: '8px',
                border: 'none',
                boxShadow: '0 4px 12px rgba(0,0,0,0.1)'
              }}
              cursor={{ stroke: '#e5e7eb', strokeDasharray: '5 5' }}
            />
            <Line 
              type="monotone" 
              dataKey="revenue" 
              stroke="#F0C389" 
              strokeWidth={3}
              dot={{ r: 3 }}
              activeDot={{ r: 8, fill: "#0d9488" }}
            />
          </LineChart>
        </ResponsiveContainer>
      </div>

      {highlightedPoint && (
        <div className="mt-2 text-xs text-gray-500">
          <div className="flex items-center">
            <div className="w-2 h-2 rounded-full bg-teal-600 mr-2"></div>
            <span>{displayDate}</span>
          </div>
          <div className="flex items-center mt-1">
            <span className="ml-4">${displayAmount}</span>
          </div>
        </div>
      )}
    </Card>
  );
} 