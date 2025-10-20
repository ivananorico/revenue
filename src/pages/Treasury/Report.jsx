export default function Report() {
  // Mock data for reports
  const anomalyReports = [
    {
      id: 1,
      title: "Sudden Revenue Tax Drop Detected",
      type: "Revenue Anomaly",
      severity: "High",
      date: "2024-01-15",
      description: "Unusual 45% decrease in tax revenue compared to previous quarter",
      previousAmount: "$2.8M",
      currentAmount: "$1.5M",
      variance: "-45%",
      status: "Under Investigation"
    },
    {
      id: 2,
      title: "Scholarship Distribution Spike",
      type: "Financial Aid Anomaly",
      severity: "Medium",
      date: "2024-01-10",
      description: "Unexpected 300% increase in scholarship disbursements",
      previousAmount: "$150K",
      currentAmount: "$600K",
      variance: "+300%",
      status: "Reviewed"
    },
    {
      id: 3,
      title: "Financial Aid Irregular Pattern",
      type: "Distribution Anomaly",
      severity: "High",
      date: "2024-01-08",
      description: "Multiple large aid distributions to single institution",
      previousAmount: "N/A",
      currentAmount: "$850K",
      variance: "Pattern Alert",
      status: "Escalated"
    }
  ];

  const getSeverityColor = (severity) => {
    switch (severity) {
      case 'High': return 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300';
      case 'Medium': return 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300';
      case 'Low': return 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300';
      default: return 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-300';
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'Under Investigation': return 'text-orange-500';
      case 'Reviewed': return 'text-green-500';
      case 'Escalated': return 'text-red-500';
      default: return 'text-gray-500';
    }
  };

  return (
    <div className='mx-1 mt-1 p-6 dark:bg-slate-900 bg-white dark:text-slate-300 rounded-lg'>
      <h1 className="text-2xl font-bold mb-6">Anomaly Reports</h1>
      
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div className="bg-slate-50 dark:bg-slate-800 p-4 rounded-lg">
          <h3 className="font-semibold text-lg">Total Anomalies</h3>
          <p className="text-2xl font-bold">12</p>
          <p className="text-sm text-green-600 dark:text-green-400">+2 this week</p>
        </div>
        <div className="bg-slate-50 dark:bg-slate-800 p-4 rounded-lg">
          <h3 className="font-semibold text-lg">High Priority</h3>
          <p className="text-2xl font-bold text-red-600 dark:text-red-400">5</p>
          <p className="text-sm">Requires immediate attention</p>
        </div>
        <div className="bg-slate-50 dark:bg-slate-800 p-4 rounded-lg">
          <h3 className="font-semibold text-lg">Resolved</h3>
          <p className="text-2xl font-bold text-green-600 dark:text-green-400">7</p>
          <p className="text-sm">This month</p>
        </div>
      </div>

      <div className="space-y-4">
        <h2 className="text-xl font-semibold mb-4">Recent Anomaly Detections</h2>
        
        {anomalyReports.map((report) => (
          <div key={report.id} className="border dark:border-slate-700 rounded-lg p-4 hover:shadow-md transition-shadow">
            <div className="flex justify-between items-start mb-2">
              <h3 className="font-semibold text-lg">{report.title}</h3>
              <span className={`px-2 py-1 rounded-full text-xs font-medium ${getSeverityColor(report.severity)}`}>
                {report.severity}
              </span>
            </div>
            
            <p className="text-slate-600 dark:text-slate-400 mb-3">{report.description}</p>
            
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-3">
              <div>
                <span className="font-medium">Type:</span> {report.type}
              </div>
              <div>
                <span className="font-medium">Date:</span> {report.date}
              </div>
              <div>
                <span className="font-medium">Previous:</span> {report.previousAmount}
              </div>
              <div>
                <span className="font-medium">Current:</span> {report.currentAmount}
              </div>
            </div>
            
            <div className="flex justify-between items-center">
              <div className="flex items-center space-x-4">
                <span className={`font-medium ${report.variance.startsWith('+') ? 'text-green-600' : report.variance.startsWith('-') ? 'text-red-600' : 'text-orange-600'}`}>
                  Variance: {report.variance}
                </span>
                <span className={getStatusColor(report.status)}>
                  Status: {report.status}
                </span>
              </div>
              <button className="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors text-sm">
                Investigate
              </button>
            </div>
          </div>
        ))}
      </div>

      <div className="mt-8">
        <h2 className="text-xl font-semibold mb-4">Quick Actions</h2>
        <div className="flex space-x-4">
          <button className="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors">
            Generate New Report
          </button>
          <button className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
            Export All Reports
          </button>
          <button className="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition-colors">
            Schedule Audit
          </button>
        </div>
      </div>
    </div>
  );
}