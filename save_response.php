<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    include 'conn.php';

    $data = json_decode(file_get_contents('php://input'), true);

    $qId = $data['q_id'];
    $qNumber = $data['q_number'];
    $userId = $data['user_id'];
    $question = $data['question'];
    $answer = $data['answer']; // This is now the transcribed text
    $interviewNum = $data['interview_num'];
    $majorId = $data['major_id'];
    $levelId = $data['level_id'];
    $field = $data['field'];

    $query = mysqli_query($conn, "
        INSERT INTO interview_responses (
            q_id, q_number, user_id, question, answer, interview_num, major_id, level_id, field
        ) VALUES (
            '$qId', '$qNumber', '$userId', '$question', '$answer', '$interviewNum', '$majorId', '$levelId', '$field'
        )
    ");

    if (!$query) {
        die("Database query failed: " . mysqli_error($conn));
    }

    echo json_encode(["status" => "success"]);
?>