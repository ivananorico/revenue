import { useState, useEffect } from 'react';
import { 
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer,
  PieChart, Pie, Cell, LineChart, Line, AreaChart, Area, ComposedChart
} from 'recharts';

export default function Revenue() {
  const [selectedYear, setSelectedYear] = useState(2025);
  const [revenueData, setRevenueData] = useState({});
  const [loading, setLoading] = useState(false);

  // Mock data for barangay-level revenue including traffic violations
  const mockRevenueData = {
    2023: {
      totalRevenue: 18650000,
      businessTax: 1250000,
      rptTax: 850000,
      stallRental: 13580000,
      trafficViolations: 2400000,
      monthly: [
        { month: 'Jan', businessTax: 98000, rptTax: 65000, stallRental: 1120000, trafficViolations: 185000, total: 1468000 },
        { month: 'Feb', businessTax: 102000, rptTax: 68000, stallRental: 1150000, trafficViolations: 192000, total: 1514000 },
        { month: 'Mar', businessTax: 115000, rptTax: 72000, stallRental: 1180000, trafficViolations: 210000, total: 1577000 },
        { month: 'Apr', businessTax: 108000, rptTax: 71000, stallRental: 1160000, trafficViolations: 195000, total: 1534000 },
        { month: 'May', businessTax: 112000, rptTax: 69000, stallRental: 1200000, trafficViolations: 205000, total: 1586000 },
        { month: 'Jun', businessTax: 105000, rptTax: 75000, stallRental: 1100000, trafficViolations: 180000, total: 1460000 },
        { month: 'Jul', businessTax: 118000, rptTax: 73000, stallRental: 1250000, trafficViolations: 220000, total: 1663000 },
        { month: 'Aug', businessTax: 122000, rptTax: 77000, stallRental: 1280000, trafficViolations: 235000, total: 1694000 },
        { month: 'Sep', businessTax: 110000, rptTax: 71000, stallRental: 1190000, trafficViolations: 198000, total: 1571000 },
        { month: 'Oct', businessTax: 125000, rptTax: 79000, stallRental: 1320000, trafficViolations: 245000, total: 1769000 },
        { month: 'Nov', businessTax: 118000, rptTax: 76000, stallRental: 1240000, trafficViolations: 228000, total: 1662000 },
        { month: 'Dec', businessTax: 135000, rptTax: 82000, stallRental: 1400000, trafficViolations: 267000, total: 1884000 }
      ],
      // Barangay-level data with traffic violations
      barangays: [
        { 
          name: 'Barangay 1', 
          revenue: 3250000, 
          businessTax: 250000, 
          rptTax: 180000, 
          stallRental: 2420000, 
          trafficViolations: 400000,
          violationsCount: 320,
          color: '#0088FE' 
        },
        { 
          name: 'Barangay 2', 
          revenue: 2850000, 
          businessTax: 220000, 
          rptTax: 160000, 
          stallRental: 2070000, 
          trafficViolations: 400000,
          violationsCount: 280,
          color: '#00C49F' 
        },
        { 
          name: 'Barangay 3', 
          revenue: 2450000, 
          businessTax: 200000, 
          rptTax: 150000, 
          stallRental: 1700000, 
          trafficViolations: 400000,
          violationsCount: 250,
          color: '#FFBB28' 
        },
        { 
          name: 'Barangay 4', 
          revenue: 2250000, 
          businessTax: 180000, 
          rptTax: 140000, 
          stallRental: 1530000, 
          trafficViolations: 400000,
          violationsCount: 220,
          color: '#FF8042' 
        },
        { 
          name: 'Barangay 5', 
          revenue: 2050000, 
          businessTax: 160000, 
          rptTax: 120000, 
          stallRental: 1370000, 
          trafficViolations: 400000,
          violationsCount: 190,
          color: '#8884D8' 
        },
        { 
          name: 'Barangay 6', 
          revenue: 1850000, 
          businessTax: 140000, 
          rptTax: 100000, 
          stallRental: 1210000, 
          trafficViolations: 400000,
          violationsCount: 160,
          color: '#82CA9D' 
        },
        { 
          name: 'Barangay 7', 
          revenue: 1650000, 
          businessTax: 120000, 
          rptTax: 80000, 
          stallRental: 1050000, 
          trafficViolations: 400000,
          violationsCount: 130,
          color: '#FF6B6B' 
        },
        { 
          name: 'Barangay 8', 
          revenue: 1450000, 
          businessTax: 100000, 
          rptTax: 60000, 
          stallRental: 890000, 
          trafficViolations: 400000,
          violationsCount: 100,
          color: '#4ECDC4' 
        }
      ],
      revenueBreakdown: [
        { name: 'Business Tax', value: 1250000, color: '#10B981' },
        { name: 'RPT Tax', value: 850000, color: '#8B5CF6' },
        { name: 'Stall Rental', value: 13580000, color: '#F59E0B' },
        { name: 'Traffic Violations', value: 2400000, color: '#EF4444' }
      ],
      violationTypes: [
        { name: 'No Parking', value: 850000, count: 680, color: '#FF6B6B' },
        { name: 'Overloading', value: 620000, count: 310, color: '#4ECDC4' },
        { name: 'No License', value: 450000, count: 225, color: '#45B7D1' },
        { name: 'Reckless Driving', value: 320000, count: 160, color: '#96CEB4' },
        { name: 'Other Violations', value: 160000, count: 125, color: '#FFEAA7' }
      ]
    },
    2024: {
      totalRevenue: 21470000,
      businessTax: 1420000,
      rptTax: 920000,
      stallRental: 15510000,
      trafficViolations: 3480000,
      monthly: [
        { month: 'Jan', businessTax: 112000, rptTax: 72000, stallRental: 1250000, trafficViolations: 265000, total: 1719000 },
        { month: 'Feb', businessTax: 118000, rptTax: 75000, stallRental: 1280000, trafficViolations: 278000, total: 1751000 },
        { month: 'Mar', businessTax: 125000, rptTax: 78000, stallRental: 1320000, trafficViolations: 295000, total: 1818000 },
        { month: 'Apr', businessTax: 122000, rptTax: 76000, stallRental: 1300000, trafficViolations: 285000, total: 1781000 },
        { month: 'May', businessTax: 128000, rptTax: 82000, stallRental: 1350000, trafficViolations: 310000, total: 1870000 },
        { month: 'Jun', businessTax: 135000, rptTax: 85000, stallRental: 1380000, trafficViolations: 320000, total: 1920000 },
        { month: 'Jul', businessTax: 142000, rptTax: 88000, stallRental: 1450000, trafficViolations: 345000, total: 2005000 },
        { month: 'Aug', businessTax: 138000, rptTax: 90000, stallRental: 1420000, trafficViolations: 335000, total: 1973000 },
        { month: 'Sep', businessTax: 145000, rptTax: 92000, stallRental: 1480000, trafficViolations: 355000, total: 2052000 },
        { month: 'Oct', businessTax: 152000, rptTax: 95000, stallRental: 1550000, trafficViolations: 380000, total: 2177000 },
        { month: 'Nov', businessTax: 148000, rptTax: 93000, stallRental: 1520000, trafficViolations: 365000, total: 2106000 },
        { month: 'Dec', businessTax: 165000, rptTax: 98000, stallRental: 1650000, trafficViolations: 425000, total: 2338000 }
      ],
      barangays: [
        { 
          name: 'Barangay 1', 
          revenue: 3850000, 
          businessTax: 280000, 
          rptTax: 200000, 
          stallRental: 2770000, 
          trafficViolations: 600000,
          violationsCount: 480,
          color: '#0088FE' 
        },
        { 
          name: 'Barangay 2', 
          revenue: 3450000, 
          businessTax: 250000, 
          rptTax: 180000, 
          stallRental: 2420000, 
          trafficViolations: 600000,
          violationsCount: 420,
          color: '#00C49F' 
        },
        { 
          name: 'Barangay 3', 
          revenue: 2950000, 
          businessTax: 220000, 
          rptTax: 160000, 
          stallRental: 1970000, 
          trafficViolations: 600000,
          violationsCount: 380,
          color: '#FFBB28' 
        },
        { 
          name: 'Barangay 4', 
          revenue: 2650000, 
          businessTax: 190000, 
          rptTax: 140000, 
          stallRental: 1720000, 
          trafficViolations: 600000,
          violationsCount: 340,
          color: '#FF8042' 
        },
        { 
          name: 'Barangay 5', 
          revenue: 2350000, 
          businessTax: 170000, 
          rptTax: 120000, 
          stallRental: 1460000, 
          trafficViolations: 500000,
          violationsCount: 300,
          color: '#8884D8' 
        },
        { 
          name: 'Barangay 6', 
          revenue: 2150000, 
          businessTax: 150000, 
          rptTax: 100000, 
          stallRental: 1300000, 
          trafficViolations: 500000,
          violationsCount: 260,
          color: '#82CA9D' 
        },
        { 
          name: 'Barangay 7', 
          revenue: 1950000, 
          businessTax: 130000, 
          rptTax: 80000, 
          stallRental: 1140000, 
          trafficViolations: 500000,
          violationsCount: 220,
          color: '#FF6B6B' 
        },
        { 
          name: 'Barangay 8', 
          revenue: 1750000, 
          businessTax: 110000, 
          rptTax: 60000, 
          stallRental: 980000, 
          trafficViolations: 500000,
          violationsCount: 180,
          color: '#4ECDC4' 
        }
      ],
      revenueBreakdown: [
        { name: 'Business Tax', value: 1420000, color: '#10B981' },
        { name: 'RPT Tax', value: 920000, color: '#8B5CF6' },
        { name: 'Stall Rental', value: 15510000, color: '#F59E0B' },
        { name: 'Traffic Violations', value: 3480000, color: '#EF4444' }
      ],
      violationTypes: [
        { name: 'No Parking', value: 1250000, count: 1000, color: '#FF6B6B' },
        { name: 'Overloading', value: 880000, count: 440, color: '#4ECDC4' },
        { name: 'No License', value: 650000, count: 325, color: '#45B7D1' },
        { name: 'Reckless Driving', value: 450000, count: 225, color: '#96CEB4' },
        { name: 'Other Violations', value: 250000, count: 190, color: '#FFEAA7' }
      ]
    },
    2025: {
      totalRevenue: 25630000,
      businessTax: 1890000,
      rptTax: 1150000,
      stallRental: 19460000,
      trafficViolations: 4120000,
      monthly: [
        { month: 'Jan', businessTax: 145000, rptTax: 85000, stallRental: 1550000, trafficViolations: 320000, total: 2105000 },
        { month: 'Feb', businessTax: 152000, rptTax: 88000, stallRental: 1600000, trafficViolations: 335000, total: 2155000 },
        { month: 'Mar', businessTax: 165000, rptTax: 92000, stallRental: 1680000, trafficViolations: 355000, total: 2212000 },
        { month: 'Apr', businessTax: 158000, rptTax: 90000, stallRental: 1620000, trafficViolations: 345000, total: 2165000 },
        { month: 'May', businessTax: 172000, rptTax: 95000, stallRental: 1750000, trafficViolations: 375000, total: 2297000 },
        { month: 'Jun', businessTax: 168000, rptTax: 98000, stallRental: 1700000, trafficViolations: 365000, total: 2246000 },
        { month: 'Jul', businessTax: 185000, rptTax: 105000, stallRental: 1850000, trafficViolations: 405000, total: 2550000 },
        { month: 'Aug', businessTax: 192000, rptTax: 108000, stallRental: 1900000, trafficViolations: 420000, total: 2620000 },
        { month: 'Sep', businessTax: 178000, rptTax: 102000, stallRental: 1800000, trafficViolations: 395000, total: 2475000 },
        { month: 'Oct', businessTax: 195000, rptTax: 112000, stallRental: 2000000, trafficViolations: 445000, total: 2752000 },
        { month: 'Nov', businessTax: 188000, rptTax: 110000, stallRental: 1950000, trafficViolations: 430000, total: 2678000 },
        { month: 'Dec', businessTax: 206000, rptTax: 120000, stallRental: 2100000, trafficViolations: 480000, total: 2906000 }
      ],
      barangays: [
        { 
          name: 'Barangay 1', 
          revenue: 4650000, 
          businessTax: 320000, 
          rptTax: 230000, 
          stallRental: 3300000, 
          trafficViolations: 800000,
          violationsCount: 640,
          color: '#0088FE' 
        },
        { 
          name: 'Barangay 2', 
          revenue: 4150000, 
          businessTax: 290000, 
          rptTax: 210000, 
          stallRental: 2950000, 
          trafficViolations: 700000,
          violationsCount: 560,
          color: '#00C49F' 
        },
        { 
          name: 'Barangay 3', 
          revenue: 3550000, 
          businessTax: 250000, 
          rptTax: 180000, 
          stallRental: 2420000, 
          trafficViolations: 700000,
          violationsCount: 500,
          color: '#FFBB28' 
        },
        { 
          name: 'Barangay 4', 
          revenue: 3250000, 
          businessTax: 220000, 
          rptTax: 170000, 
          stallRental: 2160000, 
          trafficViolations: 700000,
          violationsCount: 460,
          color: '#FF8042' 
        },
        { 
          name: 'Barangay 5', 
          revenue: 2850000, 
          businessTax: 200000, 
          rptTax: 150000, 
          stallRental: 1900000, 
          trafficViolations: 600000,
          violationsCount: 400,
          color: '#8884D8' 
        },
        { 
          name: 'Barangay 6', 
          revenue: 2650000, 
          businessTax: 180000, 
          rptTax: 140000, 
          stallRental: 1730000, 
          trafficViolations: 600000,
          violationsCount: 360,
          color: '#82CA9D' 
        },
        { 
          name: 'Barangay 7', 
          revenue: 2350000, 
          businessTax: 160000, 
          rptTax: 120000, 
          stallRental: 1470000, 
          trafficViolations: 600000,
          violationsCount: 320,
          color: '#FF6B6B' 
        },
        { 
          name: 'Barangay 8', 
          revenue: 2150000, 
          businessTax: 140000, 
          rptTax: 110000, 
          stallRental: 1300000, 
          trafficViolations: 600000,
          violationsCount: 280,
          color: '#4ECDC4' 
        }
      ],
      revenueBreakdown: [
        { name: 'Business Tax', value: 1890000, color: '#10B981' },
        { name: 'RPT Tax', value: 1150000, color: '#8B5CF6' },
        { name: 'Stall Rental', value: 19460000, color: '#F59E0B' },
        { name: 'Traffic Violations', value: 4120000, color: '#EF4444' }
      ],
      violationTypes: [
        { name: 'No Parking', value: 1480000, count: 1184, color: '#FF6B6B' },
        { name: 'Overloading', value: 1050000, count: 525, color: '#4ECDC4' },
        { name: 'No License', value: 780000, count: 390, color: '#45B7D1' },
        { name: 'Reckless Driving', value: 550000, count: 275, color: '#96CEB4' },
        { name: 'Other Violations', value: 360000, count: 275, color: '#FFEAA7' }
      ]
    }
  };

  useEffect(() => {
    // Simulate API call
    setLoading(true);
    setTimeout(() => {
      setRevenueData(mockRevenueData[selectedYear] || mockRevenueData[2025]);
      setLoading(false);
    }, 500);
  }, [selectedYear]);

  const currentData = revenueData;

  if (loading) {
    return (
      <div className='mx-1 mt-1 p-6 dark:bg-slate-900 bg-white dark:text-slate-300 rounded-lg'>
        <div className="text-2xl font-bold mb-4">Revenue Dashboard</div>
        <div className="flex justify-center items-center h-64">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>
      </div>
    );
  }

  return (
    <div className='mx-1 mt-1 p-6 dark:bg-slate-900 bg-white dark:text-slate-300 rounded-lg'>
      <h1 className="text-2xl font-bold mb-6">Municipal Revenue Dashboard</h1>
      
      {/* Year Selector */}
      <div className="mb-6">
        <label className="block text-sm font-medium mb-2">Select Year:</label>
        <select 
          value={selectedYear} 
          onChange={(e) => setSelectedYear(parseInt(e.target.value))}
          className="dark:bg-slate-800 dark:text-white border border-gray-300 rounded px-3 py-2"
        >
          <option value={2023}>2023</option>
          <option value={2024}>2024</option>
          <option value={2025}>2025</option>
        </select>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
        <div className="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
          <h3 className="text-lg font-semibold text-blue-800 dark:text-blue-200">Total Revenue</h3>
          <p className="text-2xl font-bold text-blue-600 dark:text-blue-300">
            ₱{(currentData.totalRevenue || 0).toLocaleString()}
          </p>
        </div>
        <div className="bg-green-50 dark:bg-green-900 p-4 rounded-lg">
          <h3 className="text-lg font-semibold text-green-800 dark:text-green-200">Business Tax</h3>
          <p className="text-2xl font-bold text-green-600 dark:text-green-300">
            ₱{(currentData.businessTax || 0).toLocaleString()}
          </p>
        </div>
        <div className="bg-purple-50 dark:bg-purple-900 p-4 rounded-lg">
          <h3 className="text-lg font-semibold text-purple-800 dark:text-purple-200">RPT Tax</h3>
          <p className="text-2xl font-bold text-purple-600 dark:text-purple-300">
            ₱{(currentData.rptTax || 0).toLocaleString()}
          </p>
        </div>
        <div className="bg-orange-50 dark:bg-orange-900 p-4 rounded-lg">
          <h3 className="text-lg font-semibold text-orange-800 dark:text-orange-200">Stall Rental</h3>
          <p className="text-2xl font-bold text-orange-600 dark:text-orange-300">
            ₱{(currentData.stallRental || 0).toLocaleString()}
          </p>
        </div>
        <div className="bg-red-50 dark:bg-red-900 p-4 rounded-lg">
          <h3 className="text-lg font-semibold text-red-800 dark:text-red-200">Traffic Violations</h3>
          <p className="text-2xl font-bold text-red-600 dark:text-red-300">
            ₱{(currentData.trafficViolations || 0).toLocaleString()}
          </p>
        </div>
      </div>

      {/* Monthly Revenue Trend */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div className="bg-white dark:bg-slate-800 p-4 rounded-lg shadow">
          <h3 className="text-lg font-semibold mb-4">Monthly Revenue Trend - {selectedYear}</h3>
          <ResponsiveContainer width="100%" height={300}>
            <AreaChart data={currentData.monthly || []}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="month" />
              <YAxis />
              <Tooltip formatter={(value) => `₱${value.toLocaleString()}`} />
              <Legend />
              <Area type="monotone" dataKey="businessTax" stackId="1" stroke="#10B981" fill="#10B981" fillOpacity={0.6} name="Business Tax" />
              <Area type="monotone" dataKey="rptTax" stackId="1" stroke="#8B5CF6" fill="#8B5CF6" fillOpacity={0.6} name="RPT Tax" />
              <Area type="monotone" dataKey="stallRental" stackId="1" stroke="#F59E0B" fill="#F59E0B" fillOpacity={0.6} name="Stall Rental" />
              <Area type="monotone" dataKey="trafficViolations" stackId="1" stroke="#EF4444" fill="#EF4444" fillOpacity={0.6} name="Traffic Violations" />
            </AreaChart>
          </ResponsiveContainer>
        </div>

        {/* Revenue Breakdown */}
        <div className="bg-white dark:bg-slate-800 p-4 rounded-lg shadow">
          <h3 className="text-lg font-semibold mb-4">Revenue Breakdown</h3>
          <ResponsiveContainer width="100%" height={300}>
            <PieChart>
              <Pie
                data={currentData.revenueBreakdown || []}
                cx="50%"
                cy="50%"
                labelLine={false}
                label={({ name, percent }) => `${name} (${(percent * 100).toFixed(0)}%)`}
                outerRadius={80}
                fill="#8884d8"
                dataKey="value"
              >
                {(currentData.revenueBreakdown || []).map((entry, index) => (
                  <Cell key={`cell-${index}`} fill={entry.color} />
                ))}
              </Pie>
              <Tooltip formatter={(value) => `₱${value.toLocaleString()}`} />
            </PieChart>
          </ResponsiveContainer>
        </div>
      </div>

      {/* Barangay Revenue and Violations */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {/* Revenue by Barangay */}
        <div className="bg-white dark:bg-slate-800 p-4 rounded-lg shadow">
          <h3 className="text-lg font-semibold mb-4">Revenue by Barangay - {selectedYear}</h3>
          <ResponsiveContainer width="100%" height={300}>
            <BarChart data={currentData.barangays || []}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="name" angle={-45} textAnchor="end" height={80} />
              <YAxis />
              <Tooltip formatter={(value) => `₱${value.toLocaleString()}`} />
              <Legend />
              <Bar dataKey="businessTax" name="Business Tax" fill="#10B981" />
              <Bar dataKey="rptTax" name="RPT Tax" fill="#8B5CF6" />
              <Bar dataKey="stallRental" name="Stall Rental" fill="#F59E0B" />
              <Bar dataKey="trafficViolations" name="Traffic Violations" fill="#EF4444" />
            </BarChart>
          </ResponsiveContainer>
        </div>

        {/* Traffic Violation Types */}
        <div className="bg-white dark:bg-slate-800 p-4 rounded-lg shadow">
          <h3 className="text-lg font-semibold mb-4">Traffic Violation Types - {selectedYear}</h3>
          <ResponsiveContainer width="100%" height={300}>
            <PieChart>
              <Pie
                data={currentData.violationTypes || []}
                cx="50%"
                cy="50%"
                labelLine={false}
                label={({ name, percent }) => `${name} (${(percent * 100).toFixed(0)}%)`}
                outerRadius={80}
                fill="#8884d8"
                dataKey="value"
              >
                {(currentData.violationTypes || []).map((entry, index) => (
                  <Cell key={`cell-${index}`} fill={entry.color} />
                ))}
              </Pie>
              <Tooltip formatter={(value) => `₱${value.toLocaleString()}`} />
            </PieChart>
          </ResponsiveContainer>
        </div>
      </div>

      {/* Additional Charts */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Barangay Traffic Violations Count */}
        <div className="bg-white dark:bg-slate-800 p-4 rounded-lg shadow">
          <h3 className="text-lg font-semibold mb-4">Traffic Violations by Barangay - {selectedYear}</h3>
          <ResponsiveContainer width="100%" height={300}>
            <BarChart data={currentData.barangays || []}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="name" angle={-45} textAnchor="end" height={80} />
              <YAxis />
              <Tooltip />
              <Bar dataKey="violationsCount" name="Violations Count" fill="#EF4444" />
            </BarChart>
          </ResponsiveContainer>
        </div>

        {/* Monthly Traffic Violations Trend */}
        <div className="bg-white dark:bg-slate-800 p-4 rounded-lg shadow">
          <h3 className="text-lg font-semibold mb-4">Monthly Traffic Violations Trend - {selectedYear}</h3>
          <ResponsiveContainer width="100%" height={300}>
            <LineChart data={currentData.monthly || []}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="month" />
              <YAxis />
              <Tooltip formatter={(value) => `₱${value.toLocaleString()}`} />
              <Line type="monotone" dataKey="trafficViolations" stroke="#EF4444" strokeWidth={2} name="Traffic Violations" />
            </LineChart>
          </ResponsiveContainer>
        </div>
      </div>
    </div>
  );
}