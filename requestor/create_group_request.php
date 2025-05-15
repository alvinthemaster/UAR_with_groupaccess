<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['requestor_id'])) {
    header("Location: login.php");
    exit();
}

$requestorId = $_SESSION['requestor_id'];
$username = $_SESSION['username'] ?? 'User';
$employeeId = $_SESSION['employee_id'] ?? $requestorId;

// Fetch requestor information from database
$requestorInfo = [];
try {
    $stmt = $pdo->prepare("SELECT employee_name as full_name, employee_email as email, employee_id, company as business_unit, department FROM employees WHERE employee_id = ?");
    $stmt->execute([$employeeId]);
    $requestorInfo = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Silent error handling
    error_log("Error fetching requestor info: " . $e->getMessage());
}

// Fetch all employees for the employee search functionality
$allEmployees = [];
try {
    $stmt = $pdo->prepare("SELECT id, employee_name as name, employee_email as email, employee_id FROM employees ORDER BY employee_name");
    $stmt->execute();
    $allEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Silent error handling
    error_log("Error fetching employees: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Group Access Request</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Boxicons -->
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Alpine.js for interactions -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Animate on Scroll -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                            950: '#082f49',
                        },
                        danger: {
                            DEFAULT: '#dc3545',
                            dark: '#c82333',
                        }
                    },
                    fontFamily: {
                        'sans': ['Inter', 'sans-serif'],
                    },
                    boxShadow: {
                        'soft': '0 2px 15px -3px rgba(0, 0, 0, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04)',
                        'card': '0 0 25px 0 rgba(0,0,0,0.04)',
                        'input': '0 1px 2px 0 rgba(0, 0, 0, 0.05)'
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.5s ease-in-out'
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' }
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-image: url('bg2.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            font-family: 'Inter', sans-serif;
        }
        
        .form-card {
            backdrop-filter: blur(5px);
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 0.75rem;
            transition: all 0.3s ease;
        }
        
        .input-field {
            transition: all 0.2s ease;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            box-shadow: var(--tw-shadow-input);
            width: 100%;
        }
        
        .input-field:focus {
            outline: none;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.2);
        }
        
        .radio-card {
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.75rem;
            display: flex;
            align-items: center;
        }
        
        .radio-card:hover {
            background-color: #f8fafc;
            border-color: #cbd5e1;
        }
        
        .custom-radio:checked + span {
            color: #0ea5e9;
            font-weight: 500;
        }
        
        .custom-radio:checked ~ .radio-card {
            border-color: #0ea5e9;
            background-color: #f0f9ff;
        }
        
        .checkbox-card {
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.75rem;
            display: flex;
            align-items: center;
        }
        
        .checkbox-card:hover {
            background-color: #f8fafc;
            border-color: #cbd5e1;
        }
        
        .custom-checkbox:checked + span {
            color: #0ea5e9;
            font-weight: 500;
        }
        
        .checkbox-card.checked {
            border-color: #0ea5e9;
            background-color: #f0f9ff;
        }

        .logo {
            max-width: 220px;
            height: auto;
        }
        
        @media (max-width: 768px) {
            .logo {
                max-width: 180px;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }

        /* Animated Card Transition */
        .card-transition {
            transition: all 0.3s ease;
        }
        
        .card-transition:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Progress Bar */
        .progress-container {
            width: 100%;
            height: 4px;
            background-color: #e2e8f0;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 999;
        }
        
        .progress-bar {
            height: 4px;
            background: linear-gradient(90deg, #0ea5e9, #38bdf8);
            width: 0%;
            transition: width 0.3s ease;
        }

        [x-cloak] {
            display: none !important;
        }
        
        /* Table Styles */
        .access-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
        }
        
        .access-table th {
            background-color: #f0f9ff;
            color: #0369a1;
            font-weight: 600;
            text-align: left;
            padding: 12px 8px;
            border-bottom: 2px solid #e0f2fe;
            white-space: normal;
            font-size: 0.875rem;
            line-height: 1.25;
        }
        
        .access-table td {
            padding: 12px 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .access-table tr:hover {
            background-color: #f8fafc;
        }
        
        .access-table select, 
        .access-table input {
            width: 100%;
            min-width: auto;
            font-size: 0.875rem;
            padding: 0.5rem;
        }
        
        /* Prevent table from being too squished */
        .overflow-x-auto {
            min-width: 100%;
            overflow-x: auto;
        }
        
        /* Responsive table for smaller screens */
        @media (max-width: 1280px) {
            .access-table th {
                font-size: 0.8rem;
                padding: 8px 6px;
            }
            
            .access-table td {
                padding: 8px 6px;
            }
            
            .access-table select, 
            .access-table input {
                padding: 6px 4px;
                font-size: 0.8rem;
            }
        }
        
        /* Ensure date inputs are properly sized */
        input[type="date"] {
            min-width: 130px;
        }
        
        /* Make action button more compact */
        .access-table button {
            padding: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Employee search dropdown styling */
        .employee-search-container {
            position: relative;
        }
        
        .employee-search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            max-height: 200px;
            overflow-y: auto;
            background-color: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            z-index: 50;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            display: none;
        }
        
        .employee-search-results.active {
            display: block;
        }
        
        .employee-search-item {
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .employee-search-item:hover {
            background-color: #f0f9ff;
        }
        
        /* Expandable textarea for justification */
        .justification-container {
            position: relative;
        }
        
        .justification-expand {
            position: absolute;
            bottom: 2px;
            right: 2px;
            padding: 4px;
            background-color: #f0f9ff;
            border-radius: 4px;
            font-size: 12px;
            color: #0369a1;
            cursor: pointer;
            z-index: 5;
        }
        
        .justification-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .justification-modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .justification-modal-content {
            width: 90%;
            max-width: 600px;
            background-color: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .justification-textarea {
            width: 100%;
            min-height: 150px;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.75rem;
            margin-bottom: 1rem;
            resize: vertical;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans" x-data="formHandler()">
<!-- Progress bar -->
<div class="progress-container">
    <div class="progress-bar" id="progressBar"></div>
</div>

<!-- Sidebar -->
<div class="fixed inset-y-0 left-0 z-50 w-72 bg-white shadow-card transform transition-transform duration-300 overflow-hidden" x-data="{open: true}">
    <div class="flex flex-col h-full">
        <div class="text-center p-5 flex items-center justify-center border-b border-gray-100">
            <img src="../logo.png" alt="Logo" class="w-48 mx-auto transition-all duration-300 hover:scale-105">
        </div>
        <nav class="flex-1 pt-4 px-3 space-y-1 overflow-y-auto">
            <a href="dashboard.php" class="flex items-center p-3 text-gray-700 rounded-xl transition-all duration-200 hover:bg-gray-50 hover:text-primary-600 group">
                <span class="flex items-center justify-center w-10 h-10 bg-gray-100 text-gray-600 rounded-xl mr-3 group-hover:bg-primary-50 group-hover:text-primary-600 transition-all duration-200">
                    <i class='bx bxs-dashboard text-xl'></i>
                </span>
                <span class="font-medium">Dashboard</span>
            </a>
            <a href="create_request.php" class="flex items-center p-3 text-gray-700 rounded-xl transition-all duration-200 hover:bg-gray-50 hover:text-primary-600 group">                
                <span class="flex items-center justify-center w-10 h-10 bg-gray-100 text-gray-600 rounded-xl mr-3 group-hover:bg-primary-50 group-hover:text-primary-600 transition-all duration-200">                    
                    <i class='bx bx-send text-xl'></i>                
                </span>                
                <span class="font-medium">Individual Request</span>            
            </a>            
            <a href="create_group_request.php" class="flex items-center p-3 text-primary-600 bg-primary-50 rounded-xl transition-all duration-200 group">                
                <span class="flex items-center justify-center w-10 h-10 bg-primary-100 text-primary-600 rounded-xl mr-3">                    
                    <i class='bx bx-group text-xl'></i>                
                </span>                
                <span class="font-medium">Group Request</span>            
            </a>
            <a href="my_requests.php" class="flex items-center p-3 text-gray-700 rounded-xl transition-all duration-200 hover:bg-gray-50 hover:text-primary-600 group">
                <span class="flex items-center justify-center w-10 h-10 bg-gray-100 text-gray-600 rounded-xl mr-3 group-hover:bg-primary-50 group-hover:text-primary-600 transition-all duration-200">
                    <i class='bx bx-list-ul text-xl'></i>
                </span>
                <span class="font-medium">My Requests</span>
            </a>
            <a href="request_history.php" class="flex items-center p-3 text-gray-700 rounded-xl transition-all duration-200 hover:bg-gray-50 hover:text-primary-600 group">
                <span class="flex items-center justify-center w-10 h-10 bg-gray-100 text-gray-600 rounded-xl mr-3 group-hover:bg-primary-50 group-hover:text-primary-600 transition-all duration-200">
                    <i class='bx bx-history text-xl'></i>
                </span>
                <span class="font-medium">Request History</span>
            </a>
        </nav>

        <div class="p-3 mt-auto">
            <a href="logout.php" class="flex items-center p-3 text-red-600 bg-red-50 rounded-xl transition-all duration-200 hover:bg-red-100 group">
                <span class="flex items-center justify-center w-10 h-10 bg-red-100 text-red-600 rounded-xl mr-3">
                    <i class='bx bx-log-out text-xl'></i>
                </span>
                <span class="font-medium">Logout</span>
            </a>
        </div>

        <div class="px-4 py-4 border-t border-gray-100">
            <div class="flex items-center space-x-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary-100 text-primary-600">
                    <i class='bx bxs-user text-xl'></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($username); ?></p>
                    <p class="text-xs text-gray-500">Requestor</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile menu toggle -->
<div class="fixed top-4 left-4 z-50 md:hidden">
    <button type="button" class="p-2 bg-white rounded-lg shadow-md text-gray-700" @click="open = !open">
        <i class='bx bx-menu text-2xl'></i>
    </button>
</div>

<!-- Main Content -->
<div class="ml-0 md:ml-72 transition-all duration-300">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 sticky top-0 z-10 shadow-sm">
        <div class="flex justify-between items-center px-6 py-4">
            <div data-aos="fade-right" data-aos-duration="800">
                <h2 class="text-2xl font-bold text-gray-800">Create Group Access Request</h2>
                <p class="text-gray-600 text-lg mt-1">Add group access requests for multiple users</p>
            </div>
            <div data-aos="fade-left" data-aos-duration="800" class="hidden md:block">
                <div class="flex items-center space-x-2 text-sm bg-primary-50 text-primary-700 px-4 py-2 rounded-lg">
                    <i class='bx bx-time-five'></i>
                    <span id="current_time"></span>
                </div>
            </div>
        </div>
    </div>

    <div class="p-6">
        <div class="max-w-8xl mx-auto bg-white rounded-2xl shadow-card overflow-hidden" data-aos="fade-up" data-aos-duration="1000">
            <div class="bg-gradient-to-r from-primary-600 to-primary-500 text-white py-6 px-8 border-b relative overflow-hidden">
                <!-- Animated background shapes -->
                <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-10 rounded-full -ml-12 -mb-12"></div>
            </div>
            
            <div class="p-6">
                <div class="text-sm text-gray-500 mb-6 flex items-center" data-aos="fade-up" data-aos-duration="800">
                    <i class='bx bx-info-circle mr-2'></i>
                    <span>Please fill in all required fields marked with <span class="text-red-500">*</span></span>
                </div>
                
                <form action="submit_group_request.php" method="POST" id="groupAccessRequestForm" class="space-y-8">
                    <input type="hidden" name="requestor_id" value="<?php echo htmlspecialchars($requestorId); ?>">
                    
                    <!-- Requestor Information -->
                    <div class="bg-white p-6 rounded-xl shadow-card border border-gray-100 transition-all duration-300 hover:shadow-lg" data-aos="fade-up" data-aos-duration="800">
                        <h2 class="text-xl font-semibold text-gray-800 mb-5 pb-2 border-b border-gray-200 flex items-center">
                            <i class='bx bx-user-circle text-primary-500 text-2xl mr-2'></i>
                            Requestor Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Requestor Name <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                        <i class='bx bx-user'></i>
                                    </span>
                                    <input type="text" name="requestor_name" value="<?php echo htmlspecialchars($requestorInfo['full_name'] ?? ''); ?>"
                                        class="input-field pl-10 bg-gray-50 text-gray-700" readonly>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                        <i class='bx bx-calendar'></i>
                                    </span>
                                    <input type="text" name="request_date" id="current_date" readonly
                                        class="input-field pl-10 bg-gray-50 text-gray-700">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                        <i class='bx bx-envelope'></i>
                                    </span>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($requestorInfo['email'] ?? ''); ?>"
                                        class="input-field pl-10 bg-gray-50 text-gray-700" readonly>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Employee ID <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                        <i class='bx bx-id-card'></i>
                                    </span>
                                    <input type="text" name="employee_id" value="<?php echo htmlspecialchars($requestorInfo['employee_id'] ?? ''); ?>"
                                        class="input-field pl-10 bg-gray-50 text-gray-700" readonly>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Business Unit <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                        <i class='bx bx-buildings'></i>
                                    </span>
                                    <input type="text" name="business_unit" value="<?php echo htmlspecialchars($requestorInfo['business_unit'] ?? ''); ?>"
                                        class="input-field pl-10 bg-gray-50 text-gray-700" readonly>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Department <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                        <i class='bx bx-briefcase'></i>
                                    </span>
                                    <input type="text" name="department" value="<?php echo htmlspecialchars($requestorInfo['department'] ?? ''); ?>"
                                        class="input-field pl-10 bg-gray-50 text-gray-700" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Group Access Information -->
                    <div class="bg-white p-6 rounded-xl shadow-card border border-gray-100 transition-all duration-300 hover:shadow-lg" data-aos="fade-up" data-aos-duration="800">
                        <h2 class="text-xl font-semibold text-gray-800 mb-5 pb-2 border-b border-gray-200 flex items-center">
                            <i class='bx bx-group text-primary-500 text-2xl mr-2'></i>
                            Group Access Information
                        </h2>
                        
                        <div class="mb-6">
                            <div class="text-sm text-gray-500 mb-4 flex items-center">
                                <i class='bx bx-info-circle mr-2'></i>
                                <span>Add group access requests below. For multiple applications, add additional rows.</span>
                            </div>
                            
                            <div class="w-full rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                                <div class="overflow-x-auto w-full" style="max-width: 200%; scrollbar-width: thin;">
                                    <table class="w-full text-sm text-left access-table" id="accessTable">
                                        <thead>
                                            <tr>
                                                <th style="width: 18%;">Application/System</th>
                                                <th style="width: 14%;">User Name</th>
                                                <th style="width: 10%;">Access Type</th>
                                                <th style="width: 12%;">Access Duration</th>
                                                <th style="width: 10%;">Start Date</th>
                                                <th style="width: 10%;">End Date</th>
                                                <th style="width: 10%;">Date Needed</th>
                                                <th style="width: 12%;">Justification</th>
                                                <th style="width: 4%;">Delete</th>
                                            </tr>
                                        </thead>
                                        <tbody id="accessRows">
                                            <tr>
                                                <td>
                                                    <select name="application_system[]" class="input-field">
                                                        <option value="Active Directory Access">Active Directory Access</option>
                                                        <option value="Canvasing">Canvasing</option>
                                                        <option value="CCTV Access">CCTV Access</option>
                                                        <option value="Email Access">Email Access</option>
                                                        <option value="ERP/NAV">ERP/NAV</option>
                                                        <option value="Firewall Access">Firewall Access</option>
                                                        <option value="Fresh Chilled Receiving System">Fresh Chilled Receiving System</option>
                                                        <option value="HRIS">HRIS</option>
                                                        <option value="Internet Access">Internet Access</option>
                                                        <option value="Legacy Inventory">Legacy Inventory</option>
                                                        <option value="Legacy Ledger System">Legacy Ledger System</option>
                                                        <option value="Legacy Payroll">Legacy Payroll</option>
                                                        <option value="Legacy Purchasing">Legacy Purchasing</option>
                                                        <option value="Legacy Vouchering">Legacy Vouchering</option>
                                                        <option value="Memorandum Receipt">Memorandum Receipt</option>
                                                        <option value="Offsite Storage Facility Access">Offsite Storage Facility Access</option>
                                                        <option value="PC Access - Local">PC Access - Local</option>
                                                        <option value="PC Access - Network">PC Access - Network</option>
                                                        <option value="Piece Rate Payroll System">Piece Rate Payroll System</option>
                                                        <option value="Printer Access">Printer Access</option>
                                                        <option value="Quickbooks">Quickbooks</option>
                                                        <option value="Server Access">Server Access</option>
                                                        <option value="TNA Biometric Device Access">TNA Biometric Device Access</option>
                                                        <option value="USB/PC-port Access">USB/PC-port Access</option>
                                                        <option value="VPN Access">VPN Access</option>
                                                        <option value="Wi-Fi/Access Point Access">Wi-Fi/Access Point Access</option>
                                                        <option value="ZankPOS">ZankPOS</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <div class="employee-search-container">
                                                        <input type="text" name="user_name[]" class="input-field" required>
                                                        <div class="employee-search-results"></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <select name="access_type[]" class="input-field">
                                                        <option value="Full">Full</option>
                                                        <option value="Read">Read</option>
                                                        <option value="Write">Write</option>
                                                        <option value="Admin">Admin</option>
                                                        <option value="User">User</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="access_duration[]" class="input-field duration-select" onchange="toggleDateFields(this)">
                                                        <option value="Permanent">Permanent</option>
                                                        <option value="Temporary">Temporary</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="date" name="start_date[]" class="input-field start-date" disabled>
                                                </td>
                                                <td>
                                                    <input type="date" name="end_date[]" class="input-field end-date" disabled>
                                                </td>
                                                <td>
                                                    <input type="date" name="date_needed[]" class="input-field" required>
                                                </td>
                                                <td>
                                                    <div class="justification-container">
                                                        <input type="text" name="justification[]" class="input-field justification-input" required>
                                                        <span class="justification-expand" onclick="expandJustification(this)">Expand</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <button type="button" class="p-2 text-white bg-red-600 rounded-lg hover:bg-red-700 transition duration-200" onclick="removeRow(this)">
                                                        <i class='bx bx-trash'></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="button" id="addRowBtn" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition duration-200 flex items-center">
                                    <i class='bx bx-plus mr-2'></i> Add Application Row
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="sticky bottom-0 bg-white p-4 border-t border-gray-200 rounded-b-lg shadow-lg flex justify-end space-x-4 items-center z-30" 
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 transform translate-y-4"
                        x-transition:enter-end="opacity-100 transform translate-y-0">
                        <div class="text-sm text-gray-500 mr-auto">
                            <span class="text-red-500">*</span> Required fields
                        </div>
                        <button type="reset" @click="resetForm"
                            class="px-6 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition duration-200 text-lg font-medium shadow-sm flex items-center">
                            <i class='bx bx-refresh mr-2'></i> Reset
                        </button>
                        <button type="submit" 
                            class="px-6 py-3 bg-primary-600 text-white rounded-xl hover:bg-primary-700 transition duration-200 text-lg font-medium shadow-sm flex items-center">
                            <i class='bx bx-paper-plane mr-2'></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white p-8 rounded-xl shadow-xl max-w-md w-full mx-4 transform transition-all">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-3">Group Request Submitted!</h3>
            <p class="text-gray-600 mb-6" id="modalMessage"></p>
            <button type="button" onclick="closeModal()" 
                class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-6 py-3 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-200">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Justification Edit Modal -->
<div id="justificationModal" class="justification-modal">
    <div class="justification-modal-content">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Edit Justification</h3>
        <textarea id="justificationTextarea" class="justification-textarea"></textarea>
        <div class="flex justify-end space-x-3">
            <button type="button" onclick="closeJustificationModal()" 
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition duration-200">
                Cancel
            </button>
            <button type="button" onclick="saveJustification()" 
                class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition duration-200">
                Save
            </button>
        </div>
    </div>
</div>

<!---------------------------------------------------------------------SCRIPT------------------------------------------------------------------------------------------------------------------------------------------------------------------------------>
<script>
    // Global variables for justification modal
    let currentJustificationInput = null;

    function formHandler() {
        return {
            init() {
                this.setupProgressBar();
                this.setupDateTime();
                
                // Initialize AOS
                AOS.init({
                    once: true,
                    duration: 800,
                    offset: 100
                });
                
                // Set current date
                const today = new Date();
                const options = { year: 'numeric', month: 'long', day: 'numeric' };
                document.getElementById('current_date').value = today.toLocaleDateString('en-US', options);
                
                // Initialize employee search
                this.initEmployeeSearch();
            },
            
            setupProgressBar() {
                window.onscroll = function() {
                    const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
                    const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                    const scrolled = (winScroll / height) * 100;
                    document.getElementById("progressBar").style.width = scrolled + "%";
                };
            },
            
            setupDateTime() {
                const updateTime = () => {
                    const now = new Date();
                    const timeElement = document.getElementById('current_time');
                    if (timeElement) {
                        timeElement.textContent = now.toLocaleTimeString('en-US', { 
                            hour: '2-digit', 
                            minute: '2-digit',
                            hour12: true 
                        });
                    }
                };
                
                updateTime();
                setInterval(updateTime, 1000);
            },
            
            initEmployeeSearch() {
                // Initialize employee search for the first row
                setupEmployeeSearch(document.querySelector('.employee-search-container input'));
            },
            
            resetForm() {
                // Reset the form and keep only one row
                const tbody = document.getElementById('accessRows');
                // Clear all rows except the first one
                while (tbody.children.length > 1) {
                    tbody.removeChild(tbody.lastChild);
                }
                
                // Reset the values in the first row
                const firstRow = tbody.firstChild;
                if (firstRow) {
                    const selects = firstRow.querySelectorAll('select');
                    const inputs = firstRow.querySelectorAll('input');
                    
                    selects.forEach(select => {
                        select.selectedIndex = 0;
                    });
                    
                    inputs.forEach(input => {
                        input.value = '';
                    });
                    
                    // Disable date fields in the first row
                    const startDate = firstRow.querySelector('.start-date');
                    const endDate = firstRow.querySelector('.end-date');
                    if (startDate) startDate.disabled = true;
                    if (endDate) endDate.disabled = true;
                }
                
                // Reset the entire form
                document.getElementById('groupAccessRequestForm').reset();
                
                // Reset current date
                const today = new Date();
                const options = { year: 'numeric', month: 'long', day: 'numeric' };
                document.getElementById('current_date').value = today.toLocaleDateString('en-US', options);
            }
        }
    }

    // Employee list from database
    const employees = <?php echo json_encode($allEmployees ?: []); ?>;

    function setupEmployeeSearch(inputElement) {
        if (!inputElement) return;
        
        const container = inputElement.closest('.employee-search-container');
        const resultsDiv = container.querySelector('.employee-search-results');
        
        // Create results div if it doesn't exist
        if (!resultsDiv) {
            const newResultsDiv = document.createElement('div');
            newResultsDiv.className = 'employee-search-results';
            container.appendChild(newResultsDiv);
        }
        
        // Add input event listener
        inputElement.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            if (searchTerm.length < 2) {
                resultsDiv.classList.remove('active');
                resultsDiv.innerHTML = '';
                return;
            }
            
            // Filter employees based on search term
            const filteredEmployees = employees.filter(emp => 
                emp.name.toLowerCase().includes(searchTerm) || 
                emp.employee_id.toLowerCase().includes(searchTerm)
            );
            
            // Display results
            displayEmployeeResults(filteredEmployees, resultsDiv, inputElement);
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!container.contains(e.target)) {
                resultsDiv.classList.remove('active');
            }
        });
    }

    function displayEmployeeResults(employees, resultsDiv, inputElement) {
        resultsDiv.innerHTML = '';
        
        if (employees.length === 0) {
            const noResults = document.createElement('div');
            noResults.className = 'employee-search-item';
            noResults.textContent = 'No employees found';
            resultsDiv.appendChild(noResults);
        } else {
            employees.forEach(emp => {
                const item = document.createElement('div');
                item.className = 'employee-search-item';
                item.textContent = `${emp.name} (${emp.employee_id})`;
                
                item.addEventListener('click', function() {
                    inputElement.value = emp.name;
                    resultsDiv.classList.remove('active');
                });
                
                resultsDiv.appendChild(item);
            });
        }
        
        resultsDiv.classList.add('active');
    }

    // Function to expand justification field
    function expandJustification(element) {
        const container = element.closest('.justification-container');
        const input = container.querySelector('.justification-input');
        const modal = document.getElementById('justificationModal');
        const textarea = document.getElementById('justificationTextarea');
        
        // Store the current input for reference
        currentJustificationInput = input;
        
        // Set the textarea value to match the input
        textarea.value = input.value;
        
        // Show the modal
        modal.classList.add('active');
        
        // Focus the textarea
        setTimeout(() => textarea.focus(), 100);
    }

    function closeJustificationModal() {
        const modal = document.getElementById('justificationModal');
        modal.classList.remove('active');
    }

    function saveJustification() {
        if (!currentJustificationInput) return;
        
        const textarea = document.getElementById('justificationTextarea');
        currentJustificationInput.value = textarea.value;
        
        closeJustificationModal();
    }

    // Functions moved to main DOMContentLoaded handler

    // Function to toggle date fields based on duration selection
    function toggleDateFields(select) {
        const row = select.closest('tr');
        const startDateInput = row.querySelector('.start-date');
        const endDateInput = row.querySelector('.end-date');
        
        if (select.value === 'Temporary') {
            startDateInput.disabled = false;
            endDateInput.disabled = false;
            startDateInput.required = true;
            endDateInput.required = true;
        } else {
            startDateInput.disabled = true;
            endDateInput.disabled = true;
            startDateInput.required = false;
            endDateInput.required = false;
            startDateInput.value = '';
            endDateInput.value = '';
        }
    }

    // Function to remove a row
    function removeRow(button) {
        const tbody = document.getElementById('accessRows');
        // Don't remove if it's the only row
        if (tbody.children.length > 1) {
            const row = button.closest('tr');
            row.remove();
        } else {
            // If it's the last row, just clear the inputs
            const row = button.closest('tr');
            row.querySelectorAll('input').forEach(input => {
                input.value = '';
            });
            row.querySelectorAll('select').forEach(select => {
                select.selectedIndex = 0;
            });
            
            // Reset duration settings
            const startDate = row.querySelector('.start-date');
            const endDate = row.querySelector('.end-date');
            
            if (startDate) {
                startDate.disabled = true;
                startDate.value = '';
            }
            if (endDate) {
                endDate.disabled = true;
                endDate.value = '';
            }
        }
    }

    // Function to close the success modal
    function closeModal() {
        document.getElementById('successModal').classList.add('hidden');
    }
    
    // Initialize form submission with AJAX
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize employee search for existing rows
        document.querySelectorAll('.employee-search-container input').forEach(input => {
            setupEmployeeSearch(input);
        });
        
        // Button to add new row
        document.getElementById('addRowBtn').addEventListener('click', function() {
            const tbody = document.getElementById('accessRows');
            const template = tbody.firstElementChild.cloneNode(true);
            
            // Clear input values
            template.querySelectorAll('input').forEach(input => {
                input.value = '';
            });
            
            // Reset selects to first option
            template.querySelectorAll('select').forEach(select => {
                select.selectedIndex = 0;
            });
            
            // Reset duration settings
            const durationSelect = template.querySelector('.duration-select');
            const startDate = template.querySelector('.start-date');
            const endDate = template.querySelector('.end-date');
            
            if (startDate) startDate.disabled = true;
            if (endDate) endDate.disabled = true;
            
            tbody.appendChild(template);
            
            // Setup employee search for the new row
            const newUserInput = template.querySelector('.employee-search-container input');
            setupEmployeeSearch(newUserInput);
        });
        
        // Close justification modal when clicking outside
        const justificationModal = document.getElementById('justificationModal');
        justificationModal.addEventListener('click', function(e) {
            if (e.target === justificationModal) {
                closeJustificationModal();
            }
        });
        
        // AJAX form submission
        const form = document.getElementById('groupAccessRequestForm');
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            
            // Show loading state
            Swal.fire({
                title: 'Submitting Request',
                text: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('submit_group_request.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Hide loading
                Swal.close();
                
                if (data.success) {
                    // Show success modal
                    const successModal = document.getElementById('successModal');
                    const modalMessage = document.getElementById('modalMessage');
                    
                    // Set success message
                    modalMessage.innerHTML = `
                        Your group access request <strong>${data.data.access_request_number}</strong> has been submitted successfully!<br>
                        Number of users: <strong>${data.data.user_count}</strong><br>
                        You will receive an email notification when your request is processed.
                    `;
                    
                    // Show modal
                    successModal.classList.remove('hidden');
                    
                    // Reset form
                    form.reset();
                    
                    // Reset rows to just one
                    const tbody = document.getElementById('accessRows');
                    while (tbody.children.length > 1) {
                        tbody.removeChild(tbody.lastChild);
                    }
                    
                    // Reset the first row
                    const firstRow = tbody.firstChild;
                    if (firstRow) {
                        firstRow.querySelectorAll('select').forEach(select => {
                            select.selectedIndex = 0;
                        });
                        
                        firstRow.querySelectorAll('input').forEach(input => {
                            input.value = '';
                        });
                        
                        // Reset date fields
                        const startDate = firstRow.querySelector('.start-date');
                        const endDate = firstRow.querySelector('.end-date');
                        if (startDate) {
                            startDate.disabled = true;
                            startDate.value = '';
                        }
                        if (endDate) {
                            endDate.disabled = true;
                            endDate.value = '';
                        }
                    }
                    
                    // Set current date again
                    const today = new Date();
                    const options = { year: 'numeric', month: 'long', day: 'numeric' };
                    document.getElementById('current_date').value = today.toLocaleDateString('en-US', options);
                } else {
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'An error occurred while submitting your request.',
                        confirmButtonColor: '#0284c7'
                    });
                }
            })
            .catch(error => {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An unexpected error occurred. Please try again.',
                    confirmButtonColor: '#0284c7'
                });
                console.error('Error:', error);
            });
        });
    });
</script>
</body>
</html> 