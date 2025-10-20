import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';

export default function BusinessView() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [application, setApplication] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [taxRates, setTaxRates] = useState([]);
  const [regulatoryFees, setRegulatoryFees] = useState([]);
  const [selectedTaxRate, setSelectedTaxRate] = useState('');
  const [selectedFees, setSelectedFees] = useState([]);
  const [totalAmount, setTotalAmount] = useState(0);
  const [taxAmount, setTaxAmount] = useState(0);
  const [quarterlyBreakdown, setQuarterlyBreakdown] = useState([]);
  const [calculating, setCalculating] = useState(false);
  const [submitLoading, setSubmitLoading] = useState(false);

  // Fetch application data, tax rates, and regulatory fees in one call
  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        setError('');
        
        // Fetch application details
        const appResponse = await fetch(`http://localhost/revenue/backend/Business/business_view.php?id=${id}`);
        
        if (!appResponse.ok) {
          throw new Error(`HTTP error! status: ${appResponse.status}`);
        }
        
        const appData = await appResponse.json();
        
        if (appData.success && appData.application) {
          setApplication(appData.application);
        } else {
          throw new Error(appData.message || 'Application not found');
        }

        // Fetch tax rates and regulatory fees in one call
        const combinedResponse = await fetch('http://localhost/revenue/backend/Business/get_tax_and_fees.php');
        if (!combinedResponse.ok) {
          throw new Error(`HTTP error! status: ${combinedResponse.status}`);
        }
        const combinedData = await combinedResponse.json();
        
        if (combinedData.success) {
          setTaxRates(combinedData.taxRates);
          setRegulatoryFees(combinedData.fees);
        } else {
          throw new Error('Failed to load tax rates and fees');
        }

      } catch (err) {
        console.error('Fetch error:', err);
        setError('Error loading data: ' + err.message);
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

  // Calculate quarters remaining in the year with day-based calculation for current quarter
  const getRemainingQuarters = () => {
    const now = new Date();
    const currentMonth = now.getMonth(); // 0-11 (Jan-Dec)
    const currentQuarter = Math.floor(currentMonth / 3); // 0-3 (Q1-Q4)
    const currentYear = now.getFullYear();
    
    const quarters = [];
    
    for (let quarter = currentQuarter; quarter < 4; quarter++) {
      const quarterStartMonth = quarter * 3;
      const quarterEndMonth = quarterStartMonth + 2;
      
      const quarterStart = new Date(currentYear, quarterStartMonth, 1);
      const quarterEnd = new Date(currentYear, quarterEndMonth + 1, 0); // Last day of the quarter
      
      let daysInQuarter, daysRemaining, percentageOfQuarter;
      
      if (quarter === currentQuarter) {
        // Current quarter - calculate based on remaining days
        const totalDaysInQuarter = Math.floor((quarterEnd - quarterStart) / (1000 * 60 * 60 * 24)) + 1;
        const daysPassed = Math.floor((now - quarterStart) / (1000 * 60 * 60 * 24));
        daysRemaining = totalDaysInQuarter - daysPassed;
        daysInQuarter = totalDaysInQuarter;
        percentageOfQuarter = daysRemaining / totalDaysInQuarter;
      } else {
        // Future quarters - full quarter
        daysInQuarter = Math.floor((quarterEnd - quarterStart) / (1000 * 60 * 60 * 24)) + 1;
        daysRemaining = daysInQuarter;
        percentageOfQuarter = 1;
      }
      
      quarters.push({
        quarter: quarter + 1,
        name: `Q${quarter + 1} ${currentYear}`,
        startDate: quarterStart,
        endDate: quarterEnd,
        daysInQuarter: daysInQuarter,
        daysRemaining: daysRemaining,
        percentageOfQuarter: percentageOfQuarter,
        isCurrentQuarter: quarter === currentQuarter
      });
    }
    
    return quarters;
  };

  // Calculate total amount and quarterly breakdown
  useEffect(() => {
    if (application && selectedTaxRate) {
      calculateTotalAmount();
    }
  }, [selectedTaxRate, selectedFees, application]);

  const calculateTotalAmount = () => {
    setCalculating(true);
    
    // Calculate total regulatory fees
    const totalRegulatoryFees = selectedFees.reduce((total, feeId) => {
      const fee = regulatoryFees.find(f => f.id == feeId);
      return total + (fee ? parseFloat(fee.fee) : 0);
    }, 0);

    // Get selected tax rate
    const taxRate = taxRates.find(tax => tax.id == selectedTaxRate);
    const taxRateValue = taxRate ? parseFloat(taxRate.tax_rate) : 0;

    // Calculate tax amount only (on investment + regulatory fees)
    const investmentAmount = parseFloat(application.amount);
    const taxableAmount = investmentAmount + totalRegulatoryFees;
    const calculatedTaxAmount = taxableAmount * (taxRateValue / 100);
    
    // Total amount = investment + regulatory fees + tax
    const finalTotal = taxableAmount + calculatedTaxAmount;

    setTaxAmount(calculatedTaxAmount);
    setTotalAmount(finalTotal);
    
    // Calculate quarterly breakdown for TAX AMOUNT ONLY
    calculateQuarterlyBreakdown(calculatedTaxAmount);
    setCalculating(false);
  };

  const calculateQuarterlyBreakdown = (taxAmount) => {
    const remainingQuarters = getRemainingQuarters();
    const totalQuarters = remainingQuarters.length;
    
    if (totalQuarters === 0) {
      setQuarterlyBreakdown([]);
      return;
    }

    // Calculate total remaining days across all quarters
    const totalRemainingDays = remainingQuarters.reduce((total, quarter) => {
      return total + quarter.daysRemaining;
    }, 0);

    // Distribute TAX AMOUNT proportionally based on remaining days
    const breakdown = remainingQuarters.map((quarter) => {
      // Calculate proportional tax amount based on remaining days
      const proportionalTaxAmount = taxAmount * (quarter.daysRemaining / totalRemainingDays);
      
      return {
        ...quarter,
        amount: proportionalTaxAmount,
        dueDate: new Date(quarter.endDate.getTime() + 15 * 24 * 60 * 60 * 1000), // 15 days after quarter end
        proportionalPercentage: (quarter.daysRemaining / totalRemainingDays * 100).toFixed(1)
      };
    });

    setQuarterlyBreakdown(breakdown);
  };

  const handleFeeSelection = (feeId) => {
    setSelectedFees(prev => {
      if (prev.includes(feeId)) {
        return prev.filter(id => id !== feeId);
      } else {
        return [...prev, feeId];
      }
    });
  };

  const handleSubmitAssessment = async () => {
    if (!selectedTaxRate) {
      alert('Please select a tax rate');
      return;
    }

    if (selectedFees.length === 0) {
      alert('Please select at least one regulatory fee');
      return;
    }

    try {
      setSubmitLoading(true);
      
      const taxRate = taxRates.find(tax => tax.id == selectedTaxRate);
      const selectedFeeDetails = regulatoryFees.filter(fee => selectedFees.includes(fee.id));

      const assessmentData = {
        application_id: parseInt(id),
        tax_rate_id: parseInt(selectedTaxRate),
        tax_rate: parseFloat(taxRate.tax_rate),
        tax_name: taxRate.tax_name,
        regulatory_fees: selectedFeeDetails,
        total_amount: parseFloat(totalAmount.toFixed(2)),
        tax_amount: parseFloat(taxAmount.toFixed(2)),
        quarterly_breakdown: quarterlyBreakdown.map(q => ({
          ...q,
          amount: parseFloat(q.amount.toFixed(2))
        })),
        status: 'assessed'
      };

      console.log('Sending assessment data:', assessmentData);

      const response = await fetch('http://localhost/revenue/backend/Business/assess_application.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(assessmentData)
      });

      // Check if response is OK
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();
      console.log('Assessment response:', result);

      if (result.success) {
        alert('Application assessed successfully!');
        setApplication(prev => ({ ...prev, status: 'assessed' }));
        navigate('/Business/BusinessAssess');
      } else {
        throw new Error(result.message || 'Failed to assess application');
      }

    } catch (err) {
      console.error('Submit error:', err);
      
      // More specific error messages
      if (err.message.includes('Failed to fetch')) {
        alert('Error: Cannot connect to server. Please check if the backend is running.');
      } else if (err.message.includes('HTTP error')) {
        alert(`Error: Server returned ${err.message}. Please check the backend logs.`);
      } else {
        alert('Error assessing application: ' + err.message);
      }
    } finally {
      setSubmitLoading(false);
    }
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-PH', {
      style: 'currency',
      currency: 'PHP'
    }).format(amount);
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  // Helper function to get current date info
  const getCurrentDateInfo = () => {
    const now = new Date();
    const currentMonth = now.getMonth();
    const currentQuarter = Math.floor(currentMonth / 3);
    const quarterStart = new Date(now.getFullYear(), currentQuarter * 3, 1);
    const quarterEnd = new Date(now.getFullYear(), currentQuarter * 3 + 3, 0);
    const daysPassed = Math.floor((now - quarterStart) / (1000 * 60 * 60 * 24));
    const totalDays = Math.floor((quarterEnd - quarterStart) / (1000 * 60 * 60 * 24)) + 1;
    const daysRemaining = totalDays - daysPassed;
    const percentage = ((daysRemaining / totalDays) * 100).toFixed(1);
    
    return {
      currentDate: now.toLocaleDateString(),
      currentQuarter: currentQuarter + 1,
      currentYear: now.getFullYear(),
      daysPassed,
      totalDays,
      daysRemaining,
      percentage
    };
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
        <button
          onClick={() => navigate('/Business/BusinessAssess')}
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
            onClick={() => navigate('/Business/BusinessAssess')}
            className="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
          >
            ← Back to Applications
          </button>
        </div>
      </div>
    );
  }

  const currentDateInfo = getCurrentDateInfo();
  const totalSelectedFees = selectedFees.reduce((total, feeId) => {
    const fee = regulatoryFees.find(f => f.id == feeId);
    return total + (fee ? parseFloat(fee.fee) : 0);
  }, 0);

  return (
    <div className='mx-1 mt-1 p-6 dark:bg-slate-900 bg-white dark:text-slate-300 rounded-lg'>
      {/* Header */}
      <div className="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
        <div>
          <button
            onClick={() => navigate('/Business/BusinessAssess')}
            className="inline-flex items-center text-blue-600 hover:text-blue-800 mb-2"
          >
            ← Back to Applications
          </button>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
            Business Application Assessment
          </h1>
          <p className="text-gray-600 dark:text-gray-400">
            Reference: {application.application_ref}
          </p>
        </div>
        <div className="flex items-center gap-2">
          <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${
            application.status === 'pending' 
              ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100'
              : application.status === 'approved' || application.status === 'assessed'
              ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
              : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100'
          }`}>
            {application.status === 'pending' ? '⏳' : application.status === 'approved' || application.status === 'assessed' ? '✅' : '❌'}
            {application.status.charAt(0).toUpperCase() + application.status.slice(1)}
          </span>
        </div>
      </div>

      {/* Current Quarter Info */}
      <div className="mb-4 p-4 bg-blue-50 dark:bg-blue-900 rounded-lg">
        <div className="text-sm text-blue-800 dark:text-blue-200">
          <strong>Current Quarter:</strong> Q{currentDateInfo.currentQuarter} {currentDateInfo.currentYear} | 
          <strong> Today:</strong> {currentDateInfo.currentDate} | 
          <strong> Days Remaining:</strong> {currentDateInfo.daysRemaining}/{currentDateInfo.totalDays} ({currentDateInfo.percentage}%)
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left Column - Business Information */}
        <div className="space-y-6">
          {/* Business Details Card */}
          <div className="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
              🏢 Business Information
            </h2>
            <div className="space-y-4">
              <div>
                <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Business Name</label>
                <p className="text-gray-900 dark:text-white font-medium text-lg">{application.business_name}</p>
              </div>
              <div>
                <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Business Type</label>
                <p className="text-gray-900 dark:text-white">{application.business_type}</p>
              </div>
              <div>
                <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Tax Base Type</label>
                <p className="text-gray-900 dark:text-white capitalize">
                  {application.tax_base_type.replace('_', ' ')}
                </p>
              </div>
              <div>
                <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Investment Amount</label>
                <p className="text-green-600 dark:text-green-400 font-semibold text-xl">
                  {formatCurrency(application.amount)}
                </p>
              </div>
              <div>
                <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Business Address</label>
                <p className="text-gray-900 dark:text-white">{application.full_address}</p>
              </div>
            </div>
          </div>
        </div>

        {/* Middle Column - Assessment Inputs */}
        <div className="space-y-6">
          {/* Tax Rate Selection */}
          <div className="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
              📊 Select Tax Rate
            </h2>
            <div className="space-y-3">
              {taxRates.map(tax => (
                <label key={tax.id} className="flex items-center space-x-3 cursor-pointer">
                  <input
                    type="radio"
                    name="taxRate"
                    value={tax.id}
                    checked={selectedTaxRate == tax.id}
                    onChange={(e) => setSelectedTaxRate(e.target.value)}
                    className="text-blue-600 focus:ring-blue-500"
                  />
                  <div>
                    <span className="text-gray-900 dark:text-white font-medium">{tax.tax_name}</span>
                    <span className="text-gray-600 dark:text-gray-400 ml-2">{tax.tax_rate}%</span>
                  </div>
                </label>
              ))}
            </div>
          </div>

          {/* Regulatory Fees Selection */}
          <div className="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
              💰 Regulatory Fees
            </h2>
            <div className="space-y-3 max-h-60 overflow-y-auto">
              {regulatoryFees.map(fee => (
                <label key={fee.id} className="flex items-center justify-between cursor-pointer p-2 hover:bg-gray-50 dark:hover:bg-slate-700 rounded">
                  <div className="flex items-center space-x-3">
                    <input
                      type="checkbox"
                      checked={selectedFees.includes(fee.id)}
                      onChange={() => handleFeeSelection(fee.id)}
                      className="text-blue-600 focus:ring-blue-500"
                    />
                    <span className="text-gray-900 dark:text-white">{fee.name}</span>
                  </div>
                  <span className="text-green-600 dark:text-green-400 font-medium">
                    {formatCurrency(fee.fee)}
                  </span>
                </label>
              ))}
            </div>
            <div className="mt-3 p-2 bg-gray-50 dark:bg-gray-700 rounded">
              <div className="text-sm text-gray-600 dark:text-gray-300">
                <strong>Selected: {selectedFees.length}</strong> fees totaling {' '}
                <span className="text-green-600 dark:text-green-400 font-semibold">
                  {formatCurrency(totalSelectedFees)}
                </span>
              </div>
            </div>
          </div>
        </div>

        {/* Right Column - Calculation Results */}
        <div className="space-y-6">
          {/* Total Calculation */}
          <div className="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
              🧮 Total Calculation
            </h2>
            {calculating ? (
              <div className="flex justify-center py-4">
                <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
              </div>
            ) : (
              <div className="space-y-3">
                <div className="flex justify-between">
                  <span className="text-gray-600 dark:text-gray-400">Investment Amount:</span>
                  <span className="text-gray-900 dark:text-white">{formatCurrency(application.amount)}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600 dark:text-gray-400">Regulatory Fees:</span>
                  <span className="text-green-600 dark:text-green-400">
                    {formatCurrency(totalSelectedFees)}
                  </span>
                </div>
                {selectedTaxRate && (
                  <>
                    <div className="flex justify-between">
                      <span className="text-gray-600 dark:text-gray-400">Tax Rate:</span>
                      <span className="text-gray-900 dark:text-white">
                        {taxRates.find(tax => tax.id == selectedTaxRate)?.tax_rate}%
                      </span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-600 dark:text-gray-400">Tax Amount:</span>
                      <span className="text-orange-600 dark:text-orange-400">
                        {formatCurrency(taxAmount)}
                      </span>
                    </div>
                  </>
                )}
                <div className="border-t border-gray-200 dark:border-gray-600 pt-3">
                  <div className="flex justify-between text-lg font-semibold">
                    <span className="text-gray-900 dark:text-white">Total Amount:</span>
                    <span className="text-blue-600 dark:text-blue-400">
                      {formatCurrency(totalAmount)}
                    </span>
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Quarterly Tax Breakdown */}
          {quarterlyBreakdown.length > 0 && (
            <div className="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                📅 Quarterly Tax Breakdown ({quarterlyBreakdown.length} quarters)
              </h2>
              <div className="space-y-3">
                {quarterlyBreakdown.map((quarter, index) => (
                  <div key={quarter.quarter} className="border border-gray-200 dark:border-gray-600 rounded-lg p-3">
                    <div className="flex justify-between items-start mb-2">
                      <div>
                        <span className="font-medium text-gray-900 dark:text-white">{quarter.name}</span>
                        {quarter.isCurrentQuarter && (
                          <span className="ml-2 text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">
                            Current
                          </span>
                        )}
                      </div>
                      <span className="text-orange-600 dark:text-orange-400 font-semibold">
                        {formatCurrency(quarter.amount)}
                      </span>
                    </div>
                    <div className="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                      <div>Tax Due: {formatDate(quarter.dueDate)}</div>
                      <div>Days: {quarter.daysRemaining}/{quarter.daysInQuarter} ({quarter.proportionalPercentage}%)</div>
                    </div>
                  </div>
                ))}
                <div className="border-t border-gray-200 dark:border-gray-600 pt-2 mt-2">
                  <div className="flex justify-between text-sm font-semibold">
                    <span className="text-gray-600 dark:text-gray-400">Total Tax:</span>
                    <span className="text-orange-600 dark:text-orange-400">
                      {formatCurrency(quarterlyBreakdown.reduce((sum, quarter) => sum + quarter.amount, 0))}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Submit Button */}
          {application.status === 'pending' && (
            <div className="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
              <button
                onClick={handleSubmitAssessment}
                disabled={!selectedTaxRate || selectedFees.length === 0 || submitLoading}
                className="w-full bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200"
              >
                {submitLoading ? (
                  <div className="flex items-center justify-center">
                    <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                    Assessing...
                  </div>
                ) : (
                  '✅ Assess Application'
                )}
              </button>
              <p className="text-sm text-gray-500 dark:text-gray-400 mt-2 text-center">
                This will change the status to "Assessed"
              </p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}