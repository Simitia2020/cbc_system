<?php
// SMS Gateway Configuration (Using Africa's Talking or equivalent)
// For demo, we'll use a simple function. Replace with actual SMS API

function sendSMS($phone, $message) {
    // Remove any non-numeric characters from phone
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Ensure phone starts with 254 (Kenya format)
    if (substr($phone, 0, 1) == '0') {
        $phone = '254' . substr($phone, 1);
    } elseif (substr($phone, 0, 3) != '254') {
        $phone = '254' . $phone;
    }
    
    // Option 1: Using Africa's Talking API (Recommended for Kenya)
    // Uncomment and add your credentials:
    /*
    $username = 'YOUR_USERNAME';
    $api_key = 'YOUR_API_KEY';
    
    $url = 'https://api.africastalking.com/version1/messaging';
    $data = array(
        'username' => $username,
        'to' => $phone,
        'message' => $message,
        'from' => 'CBCKENYA'
    );
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('apiKey: ' . $api_key));
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
    */
    
    // Option 2: For testing/development - log to file
    $log_file = __DIR__ . '/../logs/sms_log.txt';
    $log_dir = dirname($log_file);
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    $log_entry = date('Y-m-d H:i:s') . " | To: $phone | Message: $message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    return true;
}

// Function to format assessment message
function formatAssessmentMessage($student_name, $subject, $performance_level, $term, $exam_type) {
    $level_text = '';
    switch($performance_level) {
        case 'EE': $level_text = 'Exceeding Expectations (85-100%)'; break;
        case 'ME': $level_text = 'Meeting Expectations (70-84%)'; break;
        case 'AE': $level_text = 'Approaching Expectations (50-69%)'; break;
        case 'BE': $level_text = 'Below Expectations (0-49%)'; break;
        default: $level_text = $performance_level;
    }
    
    return "CBC Assessment Report: $student_name - $subject: $level_text ($term - $exam_type)";
}
?>