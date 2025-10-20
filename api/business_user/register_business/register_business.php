<?php
// register_business.php - Business Registration Form
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['full_name'])) {
    header('Location: ../index.php');
    exit;
}

// Database connection
$host = 'localhost:3307';
$dbname = 'business';
$username = 'root'; // Change if needed
$password = ''; // Change if needed

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Generate unique application reference
function generateApplicationRef() {
    return 'BUS-' . date('Ymd') . '-' . rand(1000, 9999);
}

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Generate application reference
        $application_ref = generateApplicationRef();
        
        // Insert into database with new structure
        $stmt = $pdo->prepare("INSERT INTO business_applications 
                              (user_id, owner_name, application_ref, business_name, business_type, 
                               amount, tax_base_type, tin_id, full_address, application_date, status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending')");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $_SESSION['full_name'],
            $application_ref,
            $_POST['business_name'],
            $_POST['business_type'],
            $_POST['amount'],
            $_POST['tax_base_type'],
            $_POST['tin_id'],
            $_POST['full_address']
        ]);
        
        $success_message = "Business registration submitted successfully! Your reference number: <strong>$application_ref</strong>";
        
        // Clear form if success
        $_POST = array();
        
    } catch(PDOException $e) {
        $error_message = "Error submitting application: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Business - Municipal Services</title>
    <link rel="stylesheet" href="../dashboard.css">
    <link rel="stylesheet" href="../navbar.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: #95a5a6;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s ease;
        }
        
        .back-button:hover {
            background: #7f8c8d;
        }
        
        .form-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .form-title {
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #bdc3c7;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            border-color: #3498db;
            outline: none;
        }
        
        .form-textarea {
            height: 100px;
            resize: vertical;
        }
        
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .submit-btn:hover {
            background: #219a52;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .tax-base-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        
        .tax-base-option {
            padding: 15px;
            border: 2px solid #bdc3c7;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .tax-base-option:hover {
            border-color: #3498db;
            background: #e3f2fd;
        }
        
        .tax-base-option.selected {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .tax-base-option input {
            display: none;
        }
        
        .tax-base-icon {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .tax-base-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .tax-base-desc {
            font-size: 0.9em;
            color: #666;
        }
        
        .tax-base-option.selected .tax-base-desc {
            color: #e3f2fd;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
        }

        .modal-title {
            color: #2c3e50;
            margin: 0;
            font-size: 1.5em;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #7f8c8d;
            transition: color 0.3s ease;
        }

        .close-btn:hover {
            color: #e74c3c;
        }

        .confirmation-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #dee2e6;
        }

        .detail-label {
            font-weight: bold;
            color: #495057;
        }

        .detail-value {
            color: #6c757d;
            text-align: right;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 25px;
        }

        .btn-cancel {
            padding: 12px 25px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn-cancel:hover {
            background: #5a6268;
        }

        .btn-confirm {
            padding: 12px 25px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn-confirm:hover {
            background: #218838;
        }

        .business-type-suggestions {
            position: absolute;
            background: white;
            border: 1px solid #bdc3c7;
            border-radius: 5px;
            max-height: 150px;
            overflow-y: auto;
            width: calc(100% - 20px);
            z-index: 100;
            display: none;
        }

        .suggestion-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #ecf0f1;
        }

        .suggestion-item:hover {
            background: #3498db;
            color: white;
        }

        .form-input-wrapper {
            position: relative;
        }
    </style>
</head>
<body>

    <!-- Include Navbar -->
    <?php include '../../../citizen_portal/navbar.php'; ?>

    <div class="form-container">
        <a href="../../../api/business_user/business_dashboard.php" class="back-button">← Back to Business Dashboard</a>
        
        <div class="form-card">
            <h2 class="form-title">Register New Business</h2>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="businessForm">
                <div class="form-group">
                    <label class="form-label" for="business_name">Business Name *</label>
                    <input type="text" class="form-input" id="business_name" name="business_name" 
                           value="<?php echo isset($_POST['business_name']) ? htmlspecialchars($_POST['business_name']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="business_type">Business Type *</label>
                    <div class="form-input-wrapper">
                        <input type="text" class="form-input" id="business_type" name="business_type" 
                               placeholder="e.g., Retail, Restaurant, Manufacturing, Service, etc."
                               value="<?php echo isset($_POST['business_type']) ? htmlspecialchars($_POST['business_type']) : ''; ?>" required>
                        <div class="business-type-suggestions" id="businessTypeSuggestions"></div>
                    </div>
                    <small style="color: #7f8c8d;">Common types: Retail, Wholesale, Restaurant, Manufacturing, Service, Consulting, Construction, Transportation</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Tax Base Type *</label>
                    <div class="tax-base-options">
                        <label class="tax-base-option">
                            <input type="radio" name="tax_base_type" value="capital_investment" required>
                            <div class="tax-base-icon">💰</div>
                            <div class="tax-base-title">Capital Investment</div>
                            <div class="tax-base-desc">Tax based on initial investment amount</div>
                        </label>
                        <label class="tax-base-option">
                            <input type="radio" name="tax_base_type" value="gross_rate">
                            <div class="tax-base-icon">📊</div>
                            <div class="tax-base-title">Gross Rate</div>
                            <div class="tax-base-desc">Tax based on gross receipts/sales</div>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="amount">Amount (₱) *</label>
                    <input type="number" class="form-input" id="amount" name="amount" 
                           step="0.01" min="0" placeholder="0.00" 
                           value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="tin_id">Tax Identification Number (TIN) *</label>
                    <input type="text" class="form-input" id="tin_id" name="tin_id" 
                           pattern="[0-9]{3}-[0-9]{3}-[0-9]{3}-[0-9]{3}" 
                           placeholder="000-000-000-000" 
                           value="<?php echo isset($_POST['tin_id']) ? htmlspecialchars($_POST['tin_id']) : ''; ?>" required>
                    <small style="color: #7f8c8d;">Format: 000-000-000-000</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="full_address">Business Address *</label>
                    <textarea class="form-textarea" id="full_address" name="full_address" 
                              placeholder="Enter complete business address..." required><?php echo isset($_POST['full_address']) ? htmlspecialchars($_POST['full_address']) : ''; ?></textarea>
                </div>
                
                <button type="button" class="submit-btn" onclick="showConfirmationModal()">Submit Business Registration</button>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Business Registration</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            
            <p>Please review your business registration details before submitting:</p>
            
            <div class="confirmation-details">
                <div class="detail-row">
                    <span class="detail-label">Business Name:</span>
                    <span class="detail-value" id="confirmBusinessName"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Business Type:</span>
                    <span class="detail-value" id="confirmBusinessType"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tax Base Type:</span>
                    <span class="detail-value" id="confirmTaxBase"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Amount:</span>
                    <span class="detail-value" id="confirmAmount">₱ 0.00</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">TIN:</span>
                    <span class="detail-value" id="confirmTin"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Address:</span>
                    <span class="detail-value" id="confirmAddress"></span>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="button" class="btn-confirm" onclick="submitForm()">Confirm & Submit</button>
            </div>
        </div>
    </div>

    <script>
        // Common business type suggestions
        const businessTypes = [
            'Retail Store', 'Wholesale Distribution', 'Restaurant', 'Food & Beverage',
            'Manufacturing', 'Construction', 'Transportation', 'Logistics',
            'Consulting', 'Professional Services', 'Healthcare', 'Education',
            'Technology', 'IT Services', 'Real Estate', 'Hospitality',
            'Beauty Salon', 'Automotive', 'Repair Services', 'Agriculture'
        ];

        // TIN formatting
        const tinInput = document.getElementById('tin_id');
        tinInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = value.match(/.{1,3}/g).join('-');
            }
            e.target.value = value.substring(0, 15);
        });

        // Business type suggestions
        const businessTypeInput = document.getElementById('business_type');
        const suggestionsContainer = document.getElementById('businessTypeSuggestions');

        businessTypeInput.addEventListener('input', function() {
            const value = this.value.toLowerCase();
            if (value.length > 1) {
                const filtered = businessTypes.filter(type => 
                    type.toLowerCase().includes(value)
                );
                showSuggestions(filtered);
            } else {
                hideSuggestions();
            }
        });

        businessTypeInput.addEventListener('focus', function() {
            if (this.value.length > 1) {
                const value = this.value.toLowerCase();
                const filtered = businessTypes.filter(type => 
                    type.toLowerCase().includes(value)
                );
                showSuggestions(filtered);
            }
        });

        function showSuggestions(suggestions) {
            if (suggestions.length > 0) {
                suggestionsContainer.innerHTML = suggestions.map(type => 
                    `<div class="suggestion-item" onclick="selectSuggestion('${type}')">${type}</div>`
                ).join('');
                suggestionsContainer.style.display = 'block';
            } else {
                hideSuggestions();
            }
        }

        function hideSuggestions() {
            suggestionsContainer.style.display = 'none';
        }

        function selectSuggestion(type) {
            businessTypeInput.value = type;
            hideSuggestions();
        }

        // Tax base type selection
        const taxBaseOptions = document.querySelectorAll('.tax-base-option');
        taxBaseOptions.forEach(option => {
            option.addEventListener('click', function() {
                taxBaseOptions.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
            });
        });

        // Close suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!businessTypeInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                hideSuggestions();
            }
        });

        // Modal functions
        function showConfirmationModal() {
            // Validate form first
            if (!validateForm()) {
                return;
            }

            // Populate confirmation details
            document.getElementById('confirmBusinessName').textContent = document.getElementById('business_name').value;
            document.getElementById('confirmBusinessType').textContent = document.getElementById('business_type').value;
            
            const taxBaseType = document.querySelector('input[name="tax_base_type"]:checked');
            document.getElementById('confirmTaxBase').textContent = taxBaseType ? 
                (taxBaseType.value === 'capital_investment' ? 'Capital Investment' : 'Gross Rate') : 'Not selected';
            
            document.getElementById('confirmAmount').textContent = '₱ ' + parseFloat(document.getElementById('amount').value).toLocaleString('en-PH', {minimumFractionDigits: 2});
            document.getElementById('confirmTin').textContent = document.getElementById('tin_id').value;
            document.getElementById('confirmAddress').textContent = document.getElementById('full_address').value;

            // Show modal
            document.getElementById('confirmationModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('confirmationModal').style.display = 'none';
        }

        function submitForm() {
            document.getElementById('businessForm').submit();
        }

        function validateForm() {
            const businessName = document.getElementById('business_name').value.trim();
            const businessType = document.getElementById('business_type').value.trim();
            const amount = document.getElementById('amount').value;
            const taxBaseType = document.querySelector('input[name="tax_base_type"]:checked');
            const tin = document.getElementById('tin_id').value;
            const address = document.getElementById('full_address').value.trim();

            if (!businessName) {
                alert('Please enter business name');
                return false;
            }

            if (!businessType) {
                alert('Please enter business type');
                return false;
            }

            if (!taxBaseType) {
                alert('Please select tax base type');
                return false;
            }

            if (!amount || parseFloat(amount) <= 0) {
                alert('Please enter valid amount');
                return false;
            }

            if (!tin || !/^\d{3}-\d{3}-\d{3}-\d{3}$/.test(tin)) {
                alert('Please enter valid TIN format (000-000-000-000)');
                return false;
            }

            if (!address) {
                alert('Please enter business address');
                return false;
            }

            return true;
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('confirmationModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>

</body>
</html>