import { Card } from "@/components/ui/card";
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { format } from 'date-fns';

interface RevenueSummaryProps {
  year: number;
  months: Array<{
    month: string;
    revenue: number;
  }>;
  currentMonthRevenue: number;
  totalRevenue: number;
}

export function RevenueSummary({
  year,
  months,
  currentMonthRevenue,
  totalRevenue
}: RevenueSummaryProps) {
  // Find current month
  const currentDate = new Date();
  const currentMonthName = format(currentDate, 'MMM');
  const currentMonthIndex = currentDate.getMonth();
  
  // Format data for chart display
  const chartData = months.map(month => ({
    month: month.month,
    revenue: month.revenue
  }));

  // Get the max value for Y-axis scaling
  const maxRevenue = Math.max(...chartData.map(item => item.revenue));
  const yAxisMax = Math.ceil(maxRevenue / 25000) * 25000; // Round up to nearest 25K

  return (
    <Card className="p-6 rounded-2xl">
      <div className="flex justify-between items-center mb-4">
        <h2 className="text-lg font-medium">Revenue Summary</h2>
        <div className="bg-gray-100 rounded-md px-3 py-1.5 text-sm flex items-center gap-1">
          This Month
          <svg 
            xmlns="http://www.w3.org/2000/svg" 
            width="16" 
            height="16" 
            viewBox="0 0 24 24" 
            fill="none" 
            stroke="currentColor" 
            strokeWidth="2" 
            strokeLinecap="round" 
            strokeLinejoin="round"
          >
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </div>
      </div>

      <div className="h-[300px] mt-6">
        <ResponsiveContainer width="100%" height="100%">
          <LineChart
            data={chartData}
            margin={{
              top: 5,
              right: 20,
              left: 0,
              bottom: 20,
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
            />
            <YAxis 
              axisLine={false} 
              tickLine={false}
              tickFormatter={(value) => `${value/1000}K`}
              dx={-10}
              tick={{ fontSize: 12 }}
              domain={[0, yAxisMax]}
              ticks={[0, yAxisMax/4, yAxisMax/2, yAxisMax*3/4, yAxisMax]}
            />
            <Tooltip 
              formatter={(value: number) => [`$${Number(value).toLocaleString()}`, 'Revenue']}
              contentStyle={{ 
                borderRadius: '8px',
                border: 'none',
                boxShadow: '0 4px 12px rgba(0,0,0,0.1)'
              }}
            />
            <Line 
              type="monotone" 
              dataKey="revenue" 
              stroke="#f0c389" 
              strokeWidth={2}
              dot={false}
              activeDot={{ r: 8, fill: "#0d9488" }}
            />
            {/* Current month dot */}
            {chartData[currentMonthIndex] && (
              <Line 
                type="monotone" 
                data={[chartData[currentMonthIndex]]} 
                dataKey="revenue" 
                stroke="transparent"
                dot={{ 
                  r: 8, 
                  fill: "#0d9488",
                  stroke: "#fff",
                  strokeWidth: 2
                }}
              />
            )}
          </LineChart>
        </ResponsiveContainer>
      </div>

      <div className="mt-2 text-xs text-gray-500">
        <div className="flex items-center">
          <div className="w-2 h-2 rounded-full bg-teal-600 mr-2"></div>
          <span>{format(currentDate, 'MMMM d, yyyy')}</span>
        </div>
        <div className="flex items-center mt-1">
          <span className="ml-4">${currentMonthRevenue.toLocaleString()}</span>
        </div>
      </div>
    </Card>
  );
} 