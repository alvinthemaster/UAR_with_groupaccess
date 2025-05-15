<?php
session_start();
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['requestor_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'data' => []
];

// Add PHPMailer requirements
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

require '../vendor/autoload.php';
require_once '../config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Set response header
    header('Content-Type: application/json');

    // Generate access request number (UAR-GREQ2025-XXX format for group requests)
    $year = date('Y');
    
    // Check both tables to find the highest request number
    $sql = "SELECT MAX(request_num) as max_num FROM (
        SELECT CAST(SUBSTRING_INDEX(access_request_number, '-', -1) AS UNSIGNED) as request_num 
        FROM access_requests 
        WHERE access_request_number LIKE :year_prefix
        UNION
        SELECT CAST(SUBSTRING_INDEX(access_request_number, '-', -1) AS UNSIGNED) as request_num 
        FROM approval_history 
        WHERE access_request_number LIKE :year_prefix
        UNION
        SELECT CAST(SUBSTRING_INDEX(access_request_number, '-', -1) AS UNSIGNED) as request_num 
        FROM group_access_requests 
        WHERE access_request_number LIKE :year_prefix
    ) combined";
    
    $stmt = $pdo->prepare($sql);
    $year_prefix = "UAR-GREQ$year-%";
    $stmt->execute(['year_prefix' => $year_prefix]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $next_num = ($result['max_num'] ?? 0) + 1;
    $access_request_number = sprintf("UAR-GREQ%d-%03d", $year, $next_num);

    // Verify the generated number doesn't exist in any table
    $check_sql = "SELECT 1 FROM (
        SELECT access_request_number FROM access_requests
        UNION
        SELECT access_request_number FROM approval_history
        UNION
        SELECT access_request_number FROM group_access_requests
    ) combined WHERE access_request_number = :request_number";
    
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute(['request_number' => $access_request_number]);
    
    if ($check_stmt->rowCount() > 0) {
        // If duplicate found, increment and try again
        $next_num++;
        $access_request_number = sprintf("UAR-GREQ%d-%03d", $year, $next_num);
    }

    // Get form data
    $requestor_name = $_POST['requestor_name'];
    $business_unit = $_POST['business_unit'];
    $department = $_POST['department'];
    $email = $_POST['email'];
    $employee_id = $_POST['employee_id'];
    $request_date = $_POST['request_date'];

    // Get arrays from form
    $application_systems = $_POST['application_system'] ?? [];
    $user_names = $_POST['user_name'] ?? [];
    $access_types = $_POST['access_type'] ?? [];
    $access_durations = $_POST['access_duration'] ?? [];
    $start_dates = $_POST['start_date'] ?? [];
    $end_dates = $_POST['end_date'] ?? [];
    $date_needed = $_POST['date_needed'] ?? [];
    $justifications = $_POST['justification'] ?? [];

    // Begin transaction
    $pdo->beginTransaction();

    // Insert main group request record
    $mainSql = "INSERT INTO group_access_requests (
        requestor_name,
        business_unit,
        access_request_number,
        department,
        email,
        employee_id,
        request_date,
        submission_date,
        status
    ) VALUES (
        :requestor_name,
        :business_unit,
        :access_request_number,
        :department,
        :email,
        :employee_id,
        :request_date,
        NOW(),
        'pending'
    )";

    $mainStmt = $pdo->prepare($mainSql);
    $mainStmt->execute([
        'requestor_name' => $requestor_name,
        'business_unit' => $business_unit,
        'access_request_number' => $access_request_number,
        'department' => $department,
        'email' => $email,
        'employee_id' => $employee_id,
        'request_date' => $request_date
    ]);
    
    $group_request_id = $pdo->lastInsertId();

    // Insert details for each row
    $detailSql = "INSERT INTO group_access_details (
        group_request_id,
        application_system,
        user_name,
        access_type,
        access_duration,
        start_date,
        end_date,
        date_needed,
        justification
    ) VALUES (
        :group_request_id,
        :application_system,
        :user_name,
        :access_type,
        :access_duration,
        :start_date,
        :end_date,
        :date_needed,
        :justification
    )";
    
    $detailStmt = $pdo->prepare($detailSql);

    // Process each row
    $user_details = [];
    for ($i = 0; $i < count($application_systems); $i++) {
        // Skip empty rows
        if (empty($user_names[$i])) continue;
        
        $start_date = ($access_durations[$i] === 'Temporary' && !empty($start_dates[$i])) ? $start_dates[$i] : null;
        $end_date = ($access_durations[$i] === 'Temporary' && !empty($end_dates[$i])) ? $end_dates[$i] : null;
        
        // Execute the detail insert
        $detailStmt->execute([
            'group_request_id' => $group_request_id,
            'application_system' => $application_systems[$i],
            'user_name' => $user_names[$i],
            'access_type' => $access_types[$i],
            'access_duration' => $access_durations[$i],
            'start_date' => $start_date,
            'end_date' => $end_date,
            'date_needed' => $date_needed[$i],
            'justification' => $justifications[$i]
        ]);
        
        // Build user details for email
        $duration_details = $access_durations[$i] === 'Permanent' ? 
            'Permanent' : 
            "Temporary (From: {$start_dates[$i]} To: {$end_dates[$i]})";
            
        $user_details[] = [
            'user_name' => $user_names[$i],
            'application_system' => $application_systems[$i],
            'access_type' => $access_types[$i],
            'access_duration' => $duration_details,
            'date_needed' => $date_needed[$i],
            'justification' => $justifications[$i]
        ];
    }

    // Commit transaction
    $pdo->commit();

    // Send email notification
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'charlesondota@gmail.com';
        $mail->Password   = 'crpf bbcb vodv xbjk';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('charlesondota@gmail.com', 'Access Request System');
        $mail->addAddress($email, $requestor_name); // Add requestor
        $mail->addAddress('charlesondota@gmail.com', 'System Administrator'); // Add admin

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Group Access Request Submitted - $access_request_number";
        
        // Build user details table
        $user_details_html = "";
        foreach ($user_details as $index => $detail) {
            $user_details_html .= "
                <tr style='" . ($index % 2 === 0 ? "background-color: #f8f9fa;" : "") . "'>
                    <td style='padding: 8px; border: 1px solid #ddd;'>{$detail['user_name']}</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>{$detail['application_system']}</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>{$detail['access_type']}</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>{$detail['access_duration']}</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>{$detail['date_needed']}</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>{$detail['justification']}</td>
                </tr>
            ";
        }

        $mail->Body = "
            <h2>Group Access Request Details</h2>
            <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
                <h3>Request Information:</h3>
                <table style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>
                    <tr style='background-color: #f8f9fa;'>
                        <td style='padding: 8px; border: 1px solid #ddd;'><strong>Request Number:</strong></td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>{$access_request_number}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; border: 1px solid #ddd;'><strong>Submission Date:</strong></td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>" . date('Y-m-d H:i:s') . "</td>
                    </tr>
                    <tr style='background-color: #f8f9fa;'>
                        <td style='padding: 8px; border: 1px solid #ddd;'><strong>Status:</strong></td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>Pending</td>
                    </tr>
                </table>

                <h3>Requestor Information:</h3>
                <table style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>
                    <tr style='background-color: #f8f9fa;'>
                        <td style='padding: 8px; border: 1px solid #ddd;'><strong>Requestor:</strong></td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>{$requestor_name}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; border: 1px solid #ddd;'><strong>Business Unit:</strong></td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>{$business_unit}</td>
                    </tr>
                    <tr style='background-color: #f8f9fa;'>
                        <td style='padding: 8px; border: 1px solid #ddd;'><strong>Department:</strong></td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>{$department}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; border: 1px solid #ddd;'><strong>Email:</strong></td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>{$email}</td>
                    </tr>
                    <tr style='background-color: #f8f9fa;'>
                        <td style='padding: 8px; border: 1px solid #ddd;'><strong>Employee ID:</strong></td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>{$employee_id}</td>
                    </tr>
                </table>

                <h3>User Access Details:</h3>
                <table style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>
                    <thead>
                        <tr style='background-color: #e0f2fe;'>
                            <th style='padding: 8px; border: 1px solid #ddd; text-align: left;'>User Name</th>
                            <th style='padding: 8px; border: 1px solid #ddd; text-align: left;'>Application/System</th>
                            <th style='padding: 8px; border: 1px solid #ddd; text-align: left;'>Access Type</th>
                            <th style='padding: 8px; border: 1px solid #ddd; text-align: left;'>Duration</th>
                            <th style='padding: 8px; border: 1px solid #ddd; text-align: left;'>Date Needed</th>
                            <th style='padding: 8px; border: 1px solid #ddd; text-align: left;'>Justification</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$user_details_html}
                    </tbody>
                </table>

                <p style='margin-top: 20px;'>Thank you for your request. It has been submitted for approval and you will be notified once it is processed.</p>
            </div>
        ";

        $mail->AltBody = "Group Access Request {$access_request_number} has been submitted and is pending approval.";
        $mail->send();

        // Return success response
        $response = [
            'success' => true,
            'message' => 'Group access request successfully submitted!',
            'data' => [
                'access_request_number' => $access_request_number,
                'user_count' => count($user_details)
            ]
        ];
    } catch (PHPMailerException $e) {
        // Email failed but database transaction succeeded
        $response = [
            'success' => true,
            'message' => 'Group access request submitted, but email notification failed to send.',
            'data' => [
                'access_request_number' => $access_request_number,
                'email_error' => $e->getMessage()
            ]
        ];
    }
} catch (Exception $e) {
    // If any part of the transaction fails, roll back
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $response = [
        'success' => false,
        'message' => 'An error occurred while processing your request: ' . $e->getMessage(),
        'data' => []
    ];
}

echo json_encode($response);
exit; 