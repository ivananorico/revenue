import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';

export default function BusinessStatusView() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [application, setApplication] = useState(null);
  const [assessment, setAssessment] = useState(null);
  const [regulatoryFees, setRegulatoryFees] = useState([]);
  const [quarterlyBreakdown, setQuarterlyBreakdown] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  // Fetch application and assessment details
  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        setError('');

        // CORRECTED API URL
        const apiUrl = `http://localhost/revenue/backend/Business/BusinessStatus/get_assessment_details.php?id=${id}`;
        console.log('API URL:', apiUrl);

        const response = await fetch(apiUrl);
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('API Response:', data);
        
        if (data.success) {
          setApplication(data.application);
          setAssessment(data.assessment);
          setRegulatoryFees(data.regulatory_fees || []);
          setQuarterlyBreakdown(data.quarterly_breakdown || []);
        } else {
          throw new Error(data.message || 'Application not found');
        }

      } catch (err) {
        console.error('Fetch error:', err);
        setError('Error loading application details: ' + err.message);
      } finally {
        setLoading(false);
      }
    };

    if (id) {
      fetchData();
    } else {
      setError('No application ID provided');
      setLoading(false);
    }
  }, [id]);

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-PH', {
      style: 'currency',
      currency: 'PHP'
    }).format(amount || 0);
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  if (loading) {
    return (
      <div className='mx-1 mt-1 p-6 dark:bg-slate-900 bg-white dark:text-slate-300 rounded-lg'>
        <div className="flex justify-center items-center h-40">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
          <span className="ml-3">Loading application details...</span>
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
        <button
          onClick={() => navigate('/Business/BusinessStatus')}
          className="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
        >
          ← Back to Applications
        </button>
      </div>
    );
  }

  if (!application) {
    return (
      <div className='mx-1 mt-1 p-6 dark:bg-slate-900 bg-white dark:text-slate-300 rounded-lg'>
        <div className="text-center py-12">
          <div className="text-gray-500 text-6xl mb-4">❌</div>
          <h3 className="text-xl font-semibold text-gray-600 mb-2">Application Not Found</h3>
          <p className="text-gray-500">The requested business application could not be found.</p>
          <button
            onClick={() => navigate('/Business/BusinessStatus')}
            className="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
          >
            ← Back to Applications
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className='mx-1 mt-1 p-6 dark:bg-slate-900 bg-white dark:text-slate-300 rounded-lg'>
      {/* Header */}
      <div className="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
        <div>
          <button
            onClick={() => navigate('/Business/BusinessStatus')}
            className="inline-flex items-center text-blue-600 hover:text-blue-800 mb-2"
          >
            ← Back to Applications
          </button>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
            Business Tax Assessment Details
          </h1>
          <p className="text-gray-600 dark:text-gray-400">
            Reference: {application.application_ref}
          </p>
        </div>
        <div className="flex items-center gap-2">
          <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
            ✅ Assessed
          </span>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Left Column - Business Information */}
        <div className="space-y-6">
          {/* Business Details */}
          <div className="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">🏢 Business Information</h2>
            <div className="space-y-3">
              <div>
                <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Business Name</label>
                <p className="text-gray-900 dark:text-white font-medium">{application.business_name}</p>
              </div>
              <div>
                <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Business Type</label>
                <p className="text-gray-900 dark:text-white">{application.business_type}</p>
              </div>
              <div>
                <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Owner Name</label>
                <p className="text-gray-900 dark:text-white">{application.owner_name}</p>
              </div>
              <div>
                <label className="text-sm font-medium text-gray-500 dark:text-gray-400">TIN Number</label>
                <p className="text-gray-900 dark:text-white font-mono">{application.tin_id}</p>
              </div>
              <div>
                <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Business Address</label>
                <p className="text-gray-900 dark:text-white">{application.full_address}</p>
              </div>
              <div>
                <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Application Date</label>
                <p className="text-gray-900 dark:text-white">{formatDate(application.application_date)}</p>
              </div>
            </div>
          </div>

          {/* Regulatory Fees */}
          {regulatoryFees.length > 0 ? (
            <div className="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">💰 Regulatory Fees</h2>
              <div className="space-y-2">
                {regulatoryFees.map((fee, index) => (
                  <div key={index} className="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <span className="text-gray-700 dark:text-gray-300">{fee.fee_name}</span>
                    <span className="text-green-600 dark:text-green-400 font-medium">
                      {formatCurrency(fee.fee_amount)}
                    </span>
                  </div>
                ))}
                <div className="flex justify-between items-center pt-2 border-t border-gray-200 dark:border-gray-600 font-semibold">
                  <span className="text-gray-900 dark:text-white">Total Fees</span>
                  <span className="text-green-600 dark:text-green-400">
                    {formatCurrency(regulatoryFees.reduce((sum, fee) => sum + parseFloat(fee.fee_amount), 0))}
                  </span>
                </div>
              </div>
            </div>
          ) : (
            <div className="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">💰 Regulatory Fees</h2>
              <p className="text-gray-500 dark:text-gray-400 text-center py-4">No regulatory fees recorded</p>
            </div>
          )}
        </div>

        {/* Right Column - Assessment Details */}
        <div className="space-y-6">
          {/* Tax Assessment */}
          {assessment && (
            <div className="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">📊 Tax Assessment</h2>
              <div className="space-y-3">
                <div className="flex justify-between">
                  <span className="text-gray-600 dark:text-gray-400">Investment Amount</span>
                  <span className="text-gray-900 dark:text-white">{formatCurrency(application.amount)}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600 dark:text-gray-400">Tax Rate</span>
                  <span className="text-gray-900 dark:text-white">{assessment.tax_rate}% ({assessment.tax_name})</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600 dark:text-gray-400">Tax Amount</span>
                  <span className="text-orange-600 dark:text-orange-400 font-semibold">
                    {formatCurrency(assessment.tax_amount)}
                  </span>
                </div>
                <div className="border-t border-gray-200 dark:border-gray-600 pt-3">
                  <div className="flex justify-between text-lg font-semibold">
                    <span className="text-gray-900 dark:text-white">Total Amount</span>
                    <span className="text-blue-600 dark:text-blue-400">
                      {formatCurrency(assessment.total_amount)}
                    </span>
                  </div>
                </div>
                <div className="text-sm text-gray-500 dark:text-gray-400">
                  Assessed on: {formatDate(assessment.assessed_at)}
                </div>
              </div>
            </div>
          )}

          {/* Quarterly Tax Breakdown */}
          {quarterlyBreakdown.length > 0 ? (
            <div className="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">📅 Quarterly Tax Payments</h2>
              <div className="space-y-3">
                {quarterlyBreakdown.map((quarter, index) => (
                  <div key={index} className="border border-gray-200 dark:border-gray-600 rounded-lg p-3">
                    <div className="flex justify-between items-start mb-2">
                      <span className="font-medium text-gray-900 dark:text-white">{quarter.quarter_name}</span>
                      <span className="text-orange-600 dark:text-orange-400 font-semibold">
                        {formatCurrency(quarter.amount)}
                      </span>
                    </div>
                    <div className="text-sm text-gray-600 dark:text-gray-400">
                      Due: {formatDate(quarter.due_date)}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          ) : (
            <div className="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">📅 Quarterly Tax Payments</h2>
              <p className="text-gray-500 dark:text-gray-400 text-center py-4">No quarterly breakdown available</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}