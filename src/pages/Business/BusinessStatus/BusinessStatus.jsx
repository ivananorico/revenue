import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';

export default function BusinessStatus() {
  const navigate = useNavigate();
  const [assessedApplications, setAssessedApplications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  // Fetch assessed applications
  useEffect(() => {
    const fetchAssessedApplications = async () => {
      try {
        setLoading(true);
        setError('');

        const response = await fetch('http://localhost/revenue/backend/Business/BusinessStatus/get_assessed_applications.php');
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
          setAssessedApplications(data.applications);
        } else {
          throw new Error(data.message || 'Failed to load applications');
        }

      } catch (err) {
        console.error('Fetch error:', err);
        setError('Error loading applications: ' + err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchAssessedApplications();
  }, []);

  const handleViewApplication = (applicationId) => {
    navigate(`/Business/BusinessStatusView/${applicationId}`);
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-PH', {
      style: 'currency',
      currency: 'PHP'
    }).format(amount);
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  if (loading) {
    return (
      <div className='mx-1 mt-1 p-6 dark:bg-slate-900 bg-white dark:text-slate-300 rounded-lg'>
        <div className="flex justify-center items-center h-40">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className='mx-1 mt-1 p-6 dark:bg-slate-900 bg-white dark:text-slate-300 rounded-lg'>
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          <strong>Error: </strong> {error}
        </div>
      </div>
    );
  }

  return (
    <div className='mx-1 mt-1 p-6 dark:bg-slate-900 bg-white dark:text-slate-300 rounded-lg'>
      <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-6">Business Tax Status</h1>
      
      {assessedApplications.length === 0 ? (
        <div className="text-center py-12">
          <div className="text-gray-500 text-6xl mb-4">📋</div>
          <h3 className="text-xl font-semibold text-gray-600 mb-2">No Assessed Applications</h3>
          <p className="text-gray-500">There are no assessed business applications at the moment.</p>
        </div>
      ) : (
        <div className="space-y-6">
          <div className="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
            <div className="flex items-center">
              <div className="text-blue-600 dark:text-blue-300 mr-3">
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div>
                <p className="text-blue-800 dark:text-blue-200 text-sm">
                  You have <strong>{assessedApplications.length}</strong> assessed business application(s)
                </p>
              </div>
            </div>
          </div>

          <div className="grid gap-4">
            {assessedApplications.map((application) => (
              <div key={application.id} className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                  <div className="flex-1">
                    <div className="flex items-start justify-between mb-3">
                      <div>
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                          {application.business_name}
                        </h3>
                        <p className="text-gray-600 dark:text-gray-400 text-sm">
                          {application.business_type} • {application.owner_name}
                        </p>
                      </div>
                      <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                        ✅ Assessed
                      </span>
                    </div>
                    
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                      <div>
                        <span className="text-gray-500 dark:text-gray-400">Reference:</span>
                        <p className="text-gray-900 dark:text-white font-mono">{application.application_ref}</p>
                      </div>
                      <div>
                        <span className="text-gray-500 dark:text-gray-400">Applied:</span>
                        <p className="text-gray-900 dark:text-white">{formatDate(application.application_date)}</p>
                      </div>
                      <div>
                        <span className="text-gray-500 dark:text-gray-400">Total Amount:</span>
                        <p className="text-green-600 dark:text-green-400 font-semibold">
                          {formatCurrency(application.display_amount || application.amount)}
                        </p>
                      </div>
                    </div>
                    
                    <div className="mt-3">
                      <span className="text-gray-500 dark:text-gray-400">Address:</span>
                      <p className="text-gray-900 dark:text-white text-sm">{application.full_address}</p>
                    </div>
                  </div>
                  
                  <div className="lg:text-right">
                    <button
                      onClick={() => handleViewApplication(application.id)}
                      className="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200 flex items-center"
                    >
                      <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      View Details
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}