<?php
session_start();
include 'conn.php';

$isLoggedIn = isset($_SESSION['user']);
$userName = $isLoggedIn ? $_SESSION['user']['user_name'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Path2Hire - AI Mock Interviews</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
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
                <?php if ($isLoggedIn): ?>
                    <span>Welcome, Mr. <?php echo htmlspecialchars($userName); ?></span>
                    <a href="logout.php" class="login-btn">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="login-btn">Sign In</a>
                    <a href="register.php" class="signup-btn">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main>
        <section class="hero">
            <div class="hero__container">
                <h1 class="hero__heading">AI-Driven Mock Interviews</h1>
                <h2 class="hero__tagline">Climb higher, conquer interviews</h2>
                <p class="hero__description">AI-powered mock interviews with real-time feedback.<br>Practice, improve, and ace your next interview.</p>
                <a href="<?php echo $isLoggedIn ? 'setup.php' : 'register.php'; ?>" class="main__btn">Start Interview</a>
            </div>
        </section>

        <section class="features">
            <div class="features__container">
                <div class="features__grid">
                    <div class="feature-card">
                        <div class="feature-icon">🎯</div>
                        <h3>Personalized Practice</h3>
                        <p>Get tailored interview questions based on your industry and experience level</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">💡</div>
                        <h3>Real-time Feedback</h3>
                        <p>Receive instant feedback on your responses and body language</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📈</div>
                        <h3>Track Progress</h3>
                        <p>Monitor your improvement over time with detailed performance analytics</p>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>