export default function AnomalyDetectionDashboard() {
  // Simple mock data for tax revenue anomalies
  const taxAnomalies = [
    {
      id: 1,
      type: "Revenue Drop",
      taxType: "Business Tax",
      location: "Quezon City",
      period: "January 2024",
      expected: "₱2.8M",
      actual: "₱1.5M",
      difference: "-46%",
      severity: "High",
      status: "Investigating"
    },
    {
      id: 2,
      type: "Revenue Spike",
      taxType: "Property Tax",
      location: "Makati",
      period: "January 2024",
      expected: "₱1.2M",
      actual: "₱2.1M",
      difference: "+75%",
      severity: "Medium",
      status: "Reviewed"
    },
    {
      id: 3,
      type: "Revenue Drop",
      taxType: "Sales Tax",
      location: "Manila",
      period: "December 2023",
      expected: "₱3.5M",
      actual: "₱2.1M",
      difference: "-40%",
      severity: "High",
      status: "Investigating"
    },
    {
      id: 4,
      type: "Revenue Spike",
      taxType: "Income Tax",
      location: "Taguig",
      period: "January 2024",
      expected: "₱4.2M",
      actual: "₱6.8M",
      difference: "+62%",
      severity: "Low",
      status: "Resolved"
    },
    {
      id: 5,
      type: "Revenue Drop",
      taxType: "VAT",
      location: "Pasig",
      period: "January 2024",
      expected: "₱5.1M",
      actual: "₱3.9M",
      difference: "-24%",
      severity: "Medium",
      status: "Monitoring"
    }
  ];

  // Simple revenue trend data
  const revenueTrend = [
    { month: "Jul", expected: 4.1, actual: 4.2 },
    { month: "Aug", expected: 4.3, actual: 4.4 },
    { month: "Sep", expected: 4.5, actual: 4.6 },
    { month: "Oct", expected: 4.7, actual: 4.8 },
    { month: "Nov", expected: 4.9, actual: 5.0 },
    { month: "Dec", expected: 5.1, actual: 3.1 }, // Big drop
    { month: "Jan", expected: 5.3, actual: 5.4 },
  ];

  // Tax type performance
  const taxPerformance = [
    { type: "Business Tax", normal: 85, current: 45, trend: "down" },
    { type: "Property Tax", normal: 92, current: 95, trend: "up" },
    { type: "Income Tax", normal: 88, current: 92, trend: "up" },
    { type: "Sales Tax", normal: 78, current: 52, trend: "down" },
    { type: "VAT", normal: 82, current: 76, trend: "down" },
  ];

  const getSeverityColor = (severity) => {
    switch (severity) {
      case 'High': return 'bg-red-100 text-red-800 border border-red-300';
      case 'Medium': return 'bg-yellow-100 text-yellow-800 border border-yellow-300';
      case 'Low': return 'bg-green-100 text-green-800 border border-green-300';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'Investigating': return 'bg-orange-100 text-orange-800';
      case 'Reviewed': return 'bg-blue-100 text-blue-800';
      case 'Resolved': return 'bg-green-100 text-green-800';
      case 'Monitoring': return 'bg-purple-100 text-purple-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getDifferenceColor = (difference) => {
    if (difference.startsWith('+')) return 'text-green-600 font-bold';
    if (difference.startsWith('-')) return 'text-red-600 font-bold';
    return 'text-gray-600';
  };

  return (
    <div className='mx-1 mt-1 p-6 dark:bg-slate-900 bg-white dark:text-slate-300 rounded-lg'>
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-2xl font-bold">Tax Revenue Anomaly Detection</h1>
          <p className="text-gray-600 dark:text-gray-400">Monitoring unusual changes in tax collections</p>
        </div>
        <div className="text-sm">
          <div className="bg-green-100 text-green-800 px-3 py-1 rounded-full">
            Live Monitoring
          </div>
        </div>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div className="bg-white dark:bg-slate-800 p-4 rounded-lg border border-gray-200 dark:border-slate-700 shadow-sm">
          <h3 className="font-semibold text-gray-600 dark:text-gray-400">Total Alerts</h3>
          <p className="text-2xl font-bold">12</p>
          <p className="text-sm text-red-600">5 require attention</p>
        </div>
        <div className="bg-white dark:bg-slate-800 p-4 rounded-lg border border-gray-200 dark:border-slate-700 shadow-sm">
          <h3 className="font-semibold text-gray-600 dark:text-gray-400">Revenue Impact</h3>
          <p className="text-2xl font-bold text-red-600">-₱8.2M</p>
          <p className="text-sm">This month</p>
        </div>
        <div className="bg-white dark:bg-slate-800 p-4 rounded-lg border border-gray-200 dark:border-slate-700 shadow-sm">
          <h3 className="font-semibold text-gray-600 dark:text-gray-400">Detection Accuracy</h3>
          <p className="text-2xl font-bold text-green-600">96%</p>
          <p className="text-sm">Based on historical data</p>
        </div>
      </div>

      {/* Simple Revenue Trend Chart */}
      <div className="bg-white dark:bg-slate-800 p-4 rounded-lg border border-gray-200 dark:border-slate-700 shadow-sm mb-6">
        <h3 className="font-semibold mb-4">Monthly Revenue Trend (in Millions)</h3>
        <div className="flex items-end space-x-2 h-40">
          {revenueTrend.map((month, index) => (
            <div key={month.month} className="flex-1 flex flex-col items-center">
              <div className="flex flex-col items-center space-y-1 w-full">
                {/* Expected Revenue */}
                <div 
                  className="w-3/4 bg-blue-200 rounded-t hover:bg-blue-300 transition-colors"
                  style={{ height: `${month.expected * 6}px` }}
                  title={`Expected: ₱${month.expected}M`}
                />
                {/* Actual Revenue */}
                <div 
                  className={`w-3/4 rounded-t transition-colors ${
                    month.actual < month.expected - 0.5 ? 'bg-red-500 hover:bg-red-600' : 
                    month.actual > month.expected + 0.5 ? 'bg-green-500 hover:bg-green-600' : 'bg-gray-500 hover:bg-gray-600'
                  }`}
                  style={{ height: `${month.actual * 6}px` }}
                  title={`Actual: ₱${month.actual}M`}
                />
              </div>
              <span className="text-xs mt-2 text-gray-600 dark:text-gray-400">{month.month}</span>
              {month.actual < month.expected - 0.5 && (
                <span className="text-xs text-red-600 font-bold">▼</span>
              )}
              {month.actual > month.expected + 0.5 && (
                <span className="text-xs text-green-600 font-bold">▲</span>
              )}
            </div>
          ))}
        </div>
        <div className="flex justify-center space-x-4 mt-4 text-sm">
          <div className="flex items-center">
            <div className="w-3 h-3 bg-blue-500 rounded mr-2"></div>
            <span>Expected Revenue</span>
          </div>
          <div className="flex items-center">
            <div className="w-3 h-3 bg-gray-500 rounded mr-2"></div>
            <span>Normal Revenue</span>
          </div>
          <div className="flex items-center">
            <div className="w-3 h-3 bg-red-500 rounded mr-2"></div>
            <span>Revenue Drop</span>
          </div>
          <div className="flex items-center">
            <div className="w-3 h-3 bg-green-500 rounded mr-2"></div>
            <span>Revenue Spike</span>
          </div>
        </div>
      </div>

      {/* Tax Type Performance */}
      <div className="bg-white dark:bg-slate-800 p-4 rounded-lg border border-gray-200 dark:border-slate-700 shadow-sm mb-6">
        <h3 className="font-semibold mb-4">Tax Type Performance (% of Target)</h3>
        <div className="space-y-3">
          {taxPerformance.map((tax, index) => (
            <div key={tax.type} className="flex items-center justify-between">
              <span className="w-24 text-sm">{tax.type}</span>
              <div className="flex-1 mx-4">
                <div className="bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                  <div 
                    className={`h-4 rounded-full transition-all ${
                      tax.trend === 'down' ? 'bg-red-500' : 'bg-green-500'
                    }`}
                    style={{ width: `${tax.current}%` }}
                  ></div>
                </div>
              </div>
              <div className="flex items-center space-x-2 w-20">
                <span className={`text-sm font-bold ${
                  tax.trend === 'down' ? 'text-red-600' : 'text-green-600'
                }`}>
                  {tax.current}%
                </span>
                {tax.trend === 'down' ? (
                  <span className="text-red-500 text-lg">▼</span>
                ) : (
                  <span className="text-green-500 text-lg">▲</span>
                )}
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Recent Anomalies */}
      <div className="space-y-4">
        <h2 className="text-xl font-semibold">Recent Revenue Alerts</h2>
        
        {taxAnomalies.map((anomaly) => (
          <div key={anomaly.id} className="bg-white dark:bg-slate-800 p-4 rounded-lg border border-gray-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow">
            <div className="flex justify-between items-start mb-3">
              <div className="flex items-center space-x-3">
                <h3 className="font-semibold text-lg">{anomaly.taxType}</h3>
                <span className={`px-2 py-1 rounded-full text-xs font-medium ${getSeverityColor(anomaly.severity)}`}>
                  {anomaly.severity} Priority
                </span>
                <span className={`px-2 py-1 rounded-full text-xs ${getStatusColor(anomaly.status)}`}>
                  {anomaly.status}
                </span>
              </div>
              <span className="text-sm text-gray-500">{anomaly.period}</span>
            </div>
            
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-3">
              <div>
                <span className="text-gray-600 dark:text-gray-400">Location:</span>
                <p className="font-medium">{anomaly.location}</p>
              </div>
              <div>
                <span className="text-gray-600 dark:text-gray-400">Expected:</span>
                <p className="font-medium">{anomaly.expected}</p>
              </div>
              <div>
                <span className="text-gray-600 dark:text-gray-400">Actual:</span>
                <p className="font-medium">{anomaly.actual}</p>
              </div>
              <div>
                <span className="text-gray-600 dark:text-gray-400">Difference:</span>
                <p className={`text-lg ${getDifferenceColor(anomaly.difference)}`}>
                  {anomaly.difference}
                </p>
              </div>
            </div>

            <div className="flex justify-between items-center">
              <div className="text-sm text-gray-600 dark:text-gray-400">
                {anomaly.type === "Revenue Drop" ? "⚠️ Lower than expected revenue" : "📈 Higher than expected revenue"}
              </div>
              <div className="flex space-x-2">
                <button className="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                  Investigate
                </button>
                <button className="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-700">
                  Details
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Simple Explanation */}
      <div className="mt-8 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
        <h3 className="font-semibold text-blue-800 dark:text-blue-300 mb-2">How This Works:</h3>
        <div className="text-sm text-blue-700 dark:text-blue-400 space-y-1">
          <p>• <strong>Revenue Drop</strong> = Collections are significantly lower than expected</p>
          <p>• <strong>Revenue Spike</strong> = Collections are significantly higher than expected</p>
          <p>• <strong>Expected values</strong> are based on historical patterns and seasonal trends</p>
          <p>• <strong>Alerts trigger</strong> when revenue differs from expectations by more than 20%</p>
        </div>
      </div>
    </div>
  );
}