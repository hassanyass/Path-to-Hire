<?php

header("Content-Type: application/json");

// ===================== DATABASE CONFIGURATION =====================

//here doma - START EDITING THESE VALUES (MUST MATCH YOUR MYSQL SETUP)
define('DB_HOST', 'localhost');
/*
WHAT: MySQL server hostname or IP address
CHANGE TO:
- Local development: 'localhost' or '127.0.0.1'
- Production: Your database server address (e.g., 'db.yourcompany.com')
*/

define('DB_USER', 'root');


define('DB_PASS', '');


define('DB_NAME', 'PathToHire');
/*
WHAT: Name of the database containing the interview Q/A table
MUST MATCH: The actual database name where your colleague stores data
EXAMPLE: 'ai_interview_system' or 'project_interviews'
*/
//here doma - STOP EDITING HERE


// ===================== SECURITY CONFIGURATION =====================
header("Access-Control-Allow-Origin: *"); 

// ===================== DATABASE CONNECTION ========================
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

// ===================== REQUEST VALIDATION ========================
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die(json_encode([
        'success' => false,
        'error' => 'Only GET method allowed'
    ]));
}

if (!isset($_GET['session_id'])) {
    http_response_code(400);
    die(json_encode([
        'success' => false,
        'error' => 'Missing session_id parameter'
    ]));
}

// ===================== DATA RETRIEVAL =============================

//here doma - SESSION ID FORMAT SHOULD MATCH YOUR SYSTEM
$session_id = $conn->real_escape_string($_GET['session_id']);
/*
WHAT: Unique identifier for the interview session
FORMAT REQUIREMENTS:
- Must match how your system generates session IDs
- Example formats: 'ses_123456' or 'int-abc123-def456'
*/

$query = "SELECT question, answer FROM interviews WHERE session_id = '$session_id'";
$result = $conn->query($query);

if (!$result) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'error' => 'Database query failed: ' . $conn->error
    ]));
}

// ===================== DATA FORMATTING ============================
$qa_pairs = [];
while ($row = $result->fetch_assoc()) {
    $qa_pairs[] = [
        'question' => htmlspecialchars_decode($row['question']),
        'answer' => htmlspecialchars_decode($row['answer'])
    ];
}

// ===================== RESPONSE ===================================
echo json_encode([
    'success' => true,
    'qa_pairs' => $qa_pairs
]);

$conn->close();
?>
