<?php
// dashboard.php
session_start();
include 'conn.php';

// Redirect if the user is not logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user']['user_id'];
$username = $_SESSION['user']['user_name'] ?? 'User'; // Default to 'User' if username is not set

// Fetch the user's interview sessions
$interviewQuery = mysqli_query($conn, "
    SELECT interview_num, major_name, level_name, field, created_at
    FROM interview_responses
    JOIN major ON interview_responses.major_id = major.major_id
    JOIN level ON interview_responses.level_id = level.level_id
    WHERE user_id = '$userId'
    GROUP BY interview_num
    ORDER BY created_at DESC
");

$interviewSessions = [];
while ($row = mysqli_fetch_assoc($interviewQuery)) {
    $interviewSessions[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Path2Hire - Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <style>
        /* Custom styles for the dashboard */
        .interview-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* 3 columns per row */
            gap: 20px; /* Space between cards */
            margin-top: 20px;
        }

        .interview-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .interview-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .interview-card h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #333;
        }

        .interview-card .interview-meta {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }

        .interview-card .main__btn {
            display: inline-block;
            padding: 10px 20px;
            background: var(--primary);
            color: #fff;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .interview-card .main__btn:hover {
            background: var(--secondary);
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .interview-grid {
                grid-template-columns: repeat(2, 1fr); /* 2 columns per row on smaller screens */
            }
        }

        @media (max-width: 480px) {
            .interview-grid {
                grid-template-columns: 1fr; /* 1 column per row on mobile */
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar__container">
            <a href="index.php" id="navbar__logo">Path2Hire</a>
            <ul class="navbar__menu">
                <li class="navbar__item">
                    <a href="about.html" class="navbar__links">About</a>
                </li>
                <li class="navbar__item">
                    <a href="faqs.html" class="navbar__links">FAQs</a>
                </li>
                <li class="navbar__item">
                    <a href="contact.html" class="navbar__links">Contact</a>
                </li>
            </ul>
            <div class="navbar__btn">
                <a href="logout.php" class="login-btn">Logout</a> <!-- Add a logout link -->
                <a href="setup.php" class="signup-btn">New Interview</a>
            </div>
        </div>
    </nav>

    <main>
        <div class="dashboard-container">
            <div class="dashboard-header">
                <div class="welcome-section">
                    <!-- <h1>Search</h1> -->
                    <p class="welcome-message">Welcome back, <?php echo htmlspecialchars($username); ?></p>
                </div>
            </div>

            <section class="recent-interviews">
                <h2>Recent Mock Interviews</h2>
                <div class="interview-grid">
                    <?php if (!empty($interviewSessions)): ?>
                        <?php foreach ($interviewSessions as $session): ?>
                            <div class="interview-card">
                                <h3><?php echo htmlspecialchars($session['major_name']); ?></h3>
                                <p class="interview-meta">Completed on: <?php echo date('F j, Y', strtotime($session['created_at'])); ?></p>
                                <p class="interview-meta">Field: <?php echo htmlspecialchars($session['field']); ?></p>
                                <p class="interview-meta">Level: <?php echo htmlspecialchars($session['level_name']); ?></p>
                                <a href="feedback.php?interview_num=<?php echo $session['interview_num']; ?>" class="main__btn">View Feedback</a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No interview sessions found.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="hiring-companies">
                <h2>Top Hiring Companies</h2>
                <div class="tips-grid">
                    <div class="tips-card">
                        <h3>Interview Tips</h3>
                        <ul>
                            <li>Research company culture</li>
                            <li>Prepare STAR method responses</li>
                            <li>Practice common questions</li>
                        </ul>
                    </div>
                    <div class="tips-card">
                        <h3>Common Behavioral Questions</h3>
                        <ul>
                            <li>Tell me about yourself</li>
                            <li>Why do you want this role?</li>
                            <li>What are your strengths?</li>
                        </ul>
                    </div>
                </div>
            </section>

            <section class="mistakes-section">
                <h2>Mistakes to Avoid</h2>
                <div class="mistakes-card">
                    <h3>Common Mistakes That Could Cost You the Job Offer</h3>
                    <ul>
                        <li>Lack of preparation</li>
                        <li>Poor communication skills</li>
                        <li>Not asking questions</li>
                        <li>Being late to the interview</li>
                    </ul>
                </div>
            </section>
        </div>
    </main>
</body>
</html>