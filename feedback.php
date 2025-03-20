<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

include 'conn.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user']['user_id'];

$userQuery = mysqli_query($conn, "
    SELECT u.field, m.major_name, l.level_name
    FROM users u
    JOIN major m ON u.major_id = m.major_id
    JOIN level l ON u.level_id = l.level_id
    WHERE u.user_id = '$userId'
");
$userData = mysqli_fetch_assoc($userQuery);
$major = $userData['major_name'];
$level = $userData['level_name'];
$field = $userData['field'];

$interviewNumQuery = mysqli_query($conn, "
    SELECT MAX(interview_num) AS latest_interview_num
    FROM interview_responses
    WHERE user_id = '$userId'
");
$interviewNumData = mysqli_fetch_assoc($interviewNumQuery);
$interviewNum = $interviewNumData['latest_interview_num'];

$qaQuery = mysqli_query($conn, "
    SELECT question, answer
    FROM interview_responses
    WHERE user_id = '$userId' AND interview_num = '$interviewNum'
");
$qaPairs = [];
while ($row = mysqli_fetch_assoc($qaQuery)) {
    $qaPairs[] = [
        'question' => $row['question'],
        'answer' => $row['answer']
    ];
}

$data = [
    'major' => $major,
    'level' => $level,
    'field' => $field,
    'qa_pairs' => $qaPairs
];
$jsonData = json_encode($data);

$pythonApiUrl = "http://localhost:5000/feedback";
$feedback = null;

if (!empty($qaPairs)) {
    $ch = curl_init($pythonApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $feedback = json_decode($response, true);
    } else {
        die("Error: Failed to get feedback from the server. HTTP Code: $httpCode");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Path2Hire - Interview Feedback</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar__container">
            <a href="index.php" id="navbar__logo">Path2Hire</a>
            <div class="navbar__btn">
                <a href="dashboard.php" class="login-btn">Dashboard</a>
                <a href="setup.php" class="signup-btn">New Interview</a>
            </div>
        </div>
    </nav>

    <main>
        <div class="feedback-container">
            <h1 class="feedback__heading">Interview Feedback</h1>
            
            <!-- Overall Feedback -->
            <div class="feedback__overall">
                <h2>Overall Feedback</h2>
                <?php if ($feedback && isset($feedback['overall_feedback'])): ?>
                    <div class="feedback-section strengths">
                        <h3>Strengths</h3>
                        <p><?php echo htmlspecialchars($feedback['overall_feedback']['strengths']); ?></p>
                    </div>
                    <div class="feedback-section weaknesses">
                        <h3>Areas of Improvement</h3>
                        <p><?php echo htmlspecialchars($feedback['overall_feedback']['weaknesses']); ?></p>
                    </div>
                    <div class="feedback-section suggestions">
                        <h3>Suggestions</h3>
                        <p><?php echo htmlspecialchars($feedback['overall_feedback']['suggestions']); ?></p>
                    </div>
                <?php else: ?>
                    <p>No overall feedback available.</p>
                <?php endif; ?>
            </div>

            <div class="feedback__questions">
                <h2>Question-Specific Feedback</h2>
                <?php if ($feedback && isset($feedback['question_feedback'])): ?>
                    <?php foreach ($feedback['question_feedback'] as $index => $questionFeedback): ?>
                        <div class="question-feedback">
                            <h3>Question <?php echo $index + 1; ?>: <?php echo htmlspecialchars($questionFeedback['question']); ?></h3>
                            <p><strong>Your Answer:</strong> <?php echo htmlspecialchars($qaPairs[$index]['answer']); ?></p>
                            <p><strong>Best Answer:</strong> <?php echo htmlspecialchars($questionFeedback['best_answer']); ?></p>
                            <p><strong>Improvement Tips:</strong> <?php echo htmlspecialchars($questionFeedback['improvement_tips']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No question-specific feedback available.</p>
                <?php endif; ?>
            </div>

            <div class="feedback__actions">
                <a href="setup.php" class="main__btn">Practice Again</a>
                <a href="dashboard.php" class="login-btn">Back to Dashboard</a>
            </div>
        </div>
    </main>
</body>
</html>