export default function Digital1() {
  const transactionData = {
    receiptNo: "EW-2024-00892",
    date: "January 16, 2024",
    time: "02:15:30 PM",
    cashier: "Auto-Processing System",
    status: "PAID",
    transactions: [
      {
        id: 1,
        customer: {
          name: "Juan Dela Cruz",
          phone: "+639171234567",
          email: "juan.delacruz@email.com"
        },
        description: "Real Property Tax (RPT) - Residential Property",
        propertyDetails: "Lot 5, Block 2, TCT No. 123456",
        period: "Q1 2024",
        amount: 3850.00,
        taxType: "RPT",
        paymentMethod: "GCash",
        referenceNo: "GC-789456123001",
        ewalletRef: "MP3245678901"
      },
      {
        id: 2,
        customer: {
          name: "Roberto Santos",
          phone: "+639287654321",
          email: "roberto.santos@email.com",
          businessName: "Santos Retail Store"
        },
        description: "Business Tax - Retail Store Operation",
        businessType: "Retail Merchandise",
        period: "January 2024",
        amount: 2750.00,
        taxType: "BUSINESS",
        paymentMethod: "Maya",
        referenceNo: "MY-456789012001",
        ewalletRef: "MA5678901234"
      },
      {
        id: 3,
        customer: {
          name: "Elena Rodriguez",
          phone: "+639395678901",
          email: "elena.rodriguez@email.com"
        },
        description: "Public Market Stall Rental - Section A, Stall 15",
        stallDetails: "Vegetable Section, 5sqm",
        period: "January 2024",
        amount: 4500.00,
        taxType: "RENTAL",
        paymentMethod: "GCash",
        referenceNo: "GC-789456123002",
        ewalletRef: "MP3245678902"
      },
      {
        id: 4,
        customer: {
          name: "Michael Tan",
          phone: "+639456789012",
          email: "michael.tan@email.com",
          businessName: "Tan Auto Repair"
        },
        description: "Business Tax - Auto Repair Shop",
        businessType: "Automotive Services",
        period: "January 2024",
        amount: 3200.00,
        taxType: "BUSINESS",
        paymentMethod: "Maya",
        referenceNo: "MY-456789012002",
        ewalletRef: "MA5678901235"
      },
      {
        id: 5,
        customer: {
          name: "Angelica Reyes",
          phone: "+639567890123",
          email: "angelica.reyes@email.com"
        },
        description: "Real Property Tax (RPT) - Commercial Building",
        propertyDetails: "2-storey commercial building, TCT No. 789012",
        period: "Q1 2024",
        amount: 12500.00,
        taxType: "RPT",
        paymentMethod: "GCash",
        referenceNo: "GC-789456123003",
        ewalletRef: "MP3245678903"
      },
      {
        id: 6,
        customer: {
          name: "Carlos Lim",
          phone: "+639678901234",
          email: "carlos.lim@email.com"
        },
        description: "Market Stall Rental - Section B, Stall 8",
        stallDetails: "Fish Section, 4sqm",
        period: "January 2024",
        amount: 3800.00,
        taxType: "RENTAL",
        paymentMethod: "Maya",
        referenceNo: "MY-456789012003",
        ewalletRef: "MA5678901236"
      }
    ]
  };

  const getTaxTypeColor = (type) => {
    switch(type) {
      case 'RPT': return 'text-blue-600 dark:text-blue-400';
      case 'BUSINESS': return 'text-green-600 dark:text-green-400';
      case 'RENTAL': return 'text-purple-600 dark:text-purple-400';
      default: return 'text-gray-600 dark:text-gray-400';
    }
  };

  const getTaxTypeBadge = (type) => {
    switch(type) {
      case 'RPT': return 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200';
      case 'BUSINESS': return 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200';
      case 'RENTAL': return 'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200';
      default: return 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200';
    }
  };

  const getPaymentMethodBadge = (method) => {
    switch(method) {
      case 'GCash': return 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200';
      case 'Maya': return 'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200';
      default: return 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200';
    }
  };

  const totalAmount = transactionData.transactions.reduce((sum, transaction) => sum + transaction.amount, 0);
  
  const gcashTotal = transactionData.transactions
    .filter(t => t.paymentMethod === 'GCash')
    .reduce((sum, t) => sum + t.amount, 0);
  
  const mayaTotal = transactionData.transactions
    .filter(t => t.paymentMethod === 'Maya')
    .reduce((sum, t) => sum + t.amount, 0);

  return (
    <div className='mx-1 mt-1 p-6 dark:bg-slate-900 bg-white dark:text-slate-300 rounded-lg shadow-lg'>
      <div className="max-w-4xl mx-auto">
        {/* Header */}
        <div className="text-center mb-8 border-b dark:border-slate-700 pb-4">
          <h1 className="text-2xl font-bold text-gray-800 dark:text-white">eWallet Payment Receipt</h1>
          <p className="text-gray-600 dark:text-gray-400">Digital Transactions Summary</p>
          <div className="flex justify-between items-center mt-4 text-sm">
            <div className="text-left">
              <p className="text-gray-500 dark:text-gray-400">Receipt No.</p>
              <p className="font-semibold">{transactionData.receiptNo}</p>
            </div>
            <div className="text-right">
              <p className="text-gray-500 dark:text-gray-400">Date & Time</p>
              <p className="font-semibold">{transactionData.date}</p>
              <p className="text-sm">{transactionData.time}</p>
            </div>
          </div>
        </div>

        {/* Transactions List */}
        <div className="space-y-6 mb-8">
          {transactionData.transactions.map((transaction, index) => (
            <div key={transaction.id} className="border dark:border-slate-700 rounded-lg p-4 hover:shadow-md transition-shadow">
              {/* Transaction Header */}
              <div className="flex justify-between items-start mb-3">
                <div>
                  <div className="flex items-center gap-2 mb-2">
                    <span className={`text-xs px-2 py-1 rounded-full ${getTaxTypeBadge(transaction.taxType)}`}>
                      {transaction.taxType}
                    </span>
                    <span className={`text-xs px-2 py-1 rounded-full ${getPaymentMethodBadge(transaction.paymentMethod)}`}>
                      {transaction.paymentMethod}
                    </span>
                    <span className="text-sm text-gray-500 dark:text-gray-400">
                      Transaction #{index + 1}
                    </span>
                  </div>
                  <h3 className="font-semibold text-lg">{transaction.description}</h3>
                </div>
                <div className="text-right">
                  <p className="text-2xl font-bold text-green-600 dark:text-green-400">
                    ₱{transaction.amount.toLocaleString()}
                  </p>
                </div>
              </div>

              {/* Customer Info */}
              <div className="grid md:grid-cols-2 gap-4 mb-3">
                <div>
                  <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Customer</p>
                  <p className="font-semibold">{transaction.customer.name}</p>
                  {transaction.customer.businessName && (
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                      {transaction.customer.businessName}
                    </p>
                  )}
                  <p className="text-sm text-gray-600 dark:text-gray-400">{transaction.customer.phone}</p>
                  <p className="text-sm text-gray-600 dark:text-gray-400">{transaction.customer.email}</p>
                </div>
                <div>
                  <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Payment Details</p>
                  <p className="font-medium">{transaction.paymentMethod}</p>
                  <p className="text-sm text-gray-600 dark:text-gray-400">Ref: {transaction.referenceNo}</p>
                  <p className="text-sm text-gray-600 dark:text-gray-400">eWallet: {transaction.ewalletRef}</p>
                  <p className="text-sm text-gray-600 dark:text-gray-400">Period: {transaction.period}</p>
                </div>
              </div>

              {/* Additional Details */}
              <div className="bg-gray-50 dark:bg-slate-800 rounded p-3">
                <p className="text-sm text-gray-600 dark:text-gray-400">
                  {transaction.propertyDetails || transaction.businessType || transaction.stallDetails}
                </p>
              </div>
            </div>
          ))}
        </div>

        {/* Summary Section */}
        <div className="border-t dark:border-slate-700 pt-6">
          <div className="flex justify-between items-center mb-6">
            <div>
              <h3 className="text-lg font-semibold">Digital Payment Summary</h3>
              <p className="text-sm text-gray-500 dark:text-gray-400">
                {transactionData.transactions.length} eWallet transactions processed
              </p>
            </div>
            <div className="text-right">
              <p className="text-sm text-gray-500 dark:text-gray-400">Total Amount Collected</p>
              <p className="text-3xl font-bold text-green-600 dark:text-green-400">
                ₱{totalAmount.toLocaleString()}
              </p>
            </div>
          </div>

          {/* Breakdown by Payment Method */}
          <div className="grid grid-cols-3 gap-4 mb-6">
            <div className="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
              <p className="text-sm font-semibold text-green-600 dark:text-green-400">GCash Total</p>
              <p className="text-2xl font-bold">₱{gcashTotal.toLocaleString()}</p>
              <p className="text-xs text-green-600 dark:text-green-400 mt-1">
                {transactionData.transactions.filter(t => t.paymentMethod === 'GCash').length} transactions
              </p>
            </div>
            <div className="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
              <p className="text-sm font-semibold text-purple-600 dark:text-purple-400">Maya Total</p>
              <p className="text-2xl font-bold">₱{mayaTotal.toLocaleString()}</p>
              <p className="text-xs text-purple-600 dark:text-purple-400 mt-1">
                {transactionData.transactions.filter(t => t.paymentMethod === 'Maya').length} transactions
              </p>
            </div>
            <div className="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
              <p className="text-sm font-semibold text-blue-600 dark:text-blue-400">Total Transactions</p>
              <p className="text-2xl font-bold">{transactionData.transactions.length}</p>
              <p className="text-xs text-blue-600 dark:text-blue-400 mt-1">All eWallet</p>
            </div>
          </div>

          {/* Breakdown by Tax Type */}
          <div className="grid grid-cols-3 gap-4">
            <div className="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded">
              <p className="text-sm text-blue-600 dark:text-blue-400">RPT Tax</p>
              <p className="font-semibold">
                ₱{transactionData.transactions
                  .filter(t => t.taxType === 'RPT')
                  .reduce((sum, t) => sum + t.amount, 0)
                  .toLocaleString()}
              </p>
            </div>
            <div className="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded">
              <p className="text-sm text-green-600 dark:text-green-400">Business Tax</p>
              <p className="font-semibold">
                ₱{transactionData.transactions
                  .filter(t => t.taxType === 'BUSINESS')
                  .reduce((sum, t) => sum + t.amount, 0)
                  .toLocaleString()}
              </p>
            </div>
            <div className="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded">
              <p className="text-sm text-purple-600 dark:text-purple-400">Rental</p>
              <p className="font-semibold">
                ₱{transactionData.transactions
                  .filter(t => t.taxType === 'RENTAL')
                  .reduce((sum, t) => sum + t.amount, 0)
                  .toLocaleString()}
              </p>
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="mt-8 pt-6 border-t dark:border-slate-700">
          <div className="flex justify-between items-center">
            <div>
              <p className="text-sm text-gray-500 dark:text-gray-400">Processing System</p>
              <p className="font-semibold">{transactionData.cashier}</p>
            </div>
            <div className={`px-4 py-2 rounded-full ${
              transactionData.status === 'PAID' 
                ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' 
                : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'
            }`}>
              <span className="font-semibold">{transactionData.status}</span>
            </div>
          </div>
          <p className="text-xs text-gray-500 dark:text-gray-400 text-center mt-4">
            All transactions processed digitally via eWallet. Receipts have been sent to registered email and mobile numbers.
          </p>
        </div>
      </div>
    </div>
  );
}