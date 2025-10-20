import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom'; // Add this import

export default function BusinessAssess() {
  const [applications, setApplications] = useState([]);
  const [filteredApplications, setFilteredApplications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [taxBaseFilter, setTaxBaseFilter] = useState('all');
  const [searchTerm, setSearchTerm] = useState('');
  const navigate = useNavigate(); // Add this hook

  // Fetch pending applications from PHP backend
  useEffect(() => {
    const fetchPendingApplications = async () => {
      try {
        setLoading(true);
        const response = await fetch('http://localhost/revenue/backend/Business/all_business.php?status=pending');
        
        if (!response.ok) {
          throw new Error('Failed to fetch applications');
        }
        
        const data = await response.json();
        
        if (data.success) {
          setApplications(data.applications || []);
          setFilteredApplications(data.applications || []);
        } else {
          setError(data.message || 'Failed to load applications');
        }
      } catch (err) {
        setError('Error connecting to server: ' + err.message);
        console.error('Fetch error:', err);
      } finally {
        setLoading(false);
      }
    };

    fetchPendingApplications();
  }, []);

  // Filter applications based on tax base and search term
  useEffect(() => {
    let filtered = applications;

    // Filter by tax base type
    if (taxBaseFilter !== 'all') {
      filtered = filtered.filter(app => app.tax_base_type === taxBaseFilter);
    }

    // Filter by search term
    if (searchTerm) {
      const term = searchTerm.toLowerCase();
      filtered = filtered.filter(app => 
        app.business_name.toLowerCase().includes(term) ||
        app.owner_name.toLowerCase().includes(term) ||
        app.application_ref.toLowerCase().includes(term) ||
        app.business_type.toLowerCase().includes(term)
      );
    }

    setFilteredApplications(filtered);
  }, [applications, taxBaseFilter, searchTerm]);

  const handleViewApplication = (applicationId) => {
    // Use React Router navigation - FIXED
    navigate(`/Business/BusinessView/${applicationId}`);
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

  const handleClearFilters = () => {
    setTaxBaseFilter('all');
    setSearchTerm('');
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
      <div className="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Pending Business Applications</h1>
          <p className="text-gray-600 dark:text-gray-400 mt-1">Manage and review pending business registrations</p>
        </div>
        <div className="flex items-center gap-2">
          <div className="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
            {filteredApplications.length} {filteredApplications.length === 1 ? 'Application' : 'Applications'}
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 mb-6">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {/* Search Input */}
          <div>
            <label htmlFor="search" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Search Applications
            </label>
            <input
              type="text"
              id="search"
              placeholder="Search by business name, owner, or reference..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
            />
          </div>

          {/* Tax Base Filter */}
          <div>
            <label htmlFor="taxBaseFilter" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Tax Base Type
            </label>
            <select
              id="taxBaseFilter"
              value={taxBaseFilter}
              onChange={(e) => setTaxBaseFilter(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
            >
              <option value="all">All Tax Base Types</option>
              <option value="capital_investment">Capital Investment</option>
              <option value="gross_rate">Gross Rate</option>
            </select>
          </div>

          {/* Clear Filters */}
          <div className="flex items-end">
            <button
              onClick={handleClearFilters}
              className="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white dark:bg-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-500 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              Clear Filters
            </button>
          </div>
        </div>
      </div>

      {filteredApplications.length === 0 ? (
        <div className="text-center py-12 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
          <div className="text-gray-400 text-6xl mb-4">📋</div>
          <h3 className="text-xl font-semibold text-gray-500 mb-2">
            {applications.length === 0 ? 'No Pending Applications' : 'No Applications Match Filters'}
          </h3>
          <p className="text-gray-400">
            {applications.length === 0 
              ? 'All business applications have been processed.' 
              : 'Try adjusting your search or filter criteria.'
            }
          </p>
          {applications.length > 0 && (
            <button
              onClick={handleClearFilters}
              className="mt-4 px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-500"
            >
              Clear all filters
            </button>
          )}
        </div>
      ) : (
        <div className="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg">
          <table className="min-w-full divide-y divide-gray-300 dark:divide-gray-600">
            <thead className="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Business Details
                </th>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Owner & TIN
                </th>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Financial Info
                </th>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Date & Status
                </th>
                <th scope="col" className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              {filteredApplications.map((application) => (
                <tr key={application.id} className="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                  {/* Business Details */}
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div>
                      <div className="text-sm font-medium text-gray-900 dark:text-white">
                        {application.business_name}
                      </div>
                      <div className="text-sm text-gray-500 dark:text-gray-400">
                        {application.business_type}
                      </div>
                      <div className="text-xs text-gray-400 dark:text-gray-500 mt-1">
                        Ref: {application.application_ref}
                      </div>
                    </div>
                  </td>

                  {/* Owner & TIN */}
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div>
                      <div className="text-sm text-gray-900 dark:text-white">
                        {application.owner_name}
                      </div>
                      <div className="text-sm text-gray-500 dark:text-gray-400">
                        TIN: {application.tin_id}
                      </div>
                    </div>
                  </td>

                  {/* Financial Info */}
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div>
                      <div className="text-sm font-semibold text-green-600 dark:text-green-400">
                        {formatCurrency(application.amount)}
                      </div>
                      <div className="text-xs text-gray-500 dark:text-gray-400 capitalize">
                        {application.tax_base_type.replace('_', ' ')}
                      </div>
                    </div>
                  </td>

                  {/* Date & Status */}
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div>
                      <div className="text-sm text-gray-900 dark:text-white">
                        {formatDate(application.application_date)}
                      </div>
                      <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                        ⏳ Pending
                      </span>
                    </div>
                  </td>

                  {/* Actions */}
                  <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button
                      onClick={() => handleViewApplication(application.id)}
                      className="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                    >
                      <svg className="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      View
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Summary */}
      {filteredApplications.length > 0 && (
        <div className="mt-4 text-sm text-gray-500 dark:text-gray-400">
          Showing {filteredApplications.length} of {applications.length} pending applications
          {taxBaseFilter !== 'all' && ` • Filtered by: ${taxBaseFilter.replace('_', ' ')}`}
          {searchTerm && ` • Search: "${searchTerm}"`}
        </div>
      )}
    </div>
  );
}