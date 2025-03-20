<?php
session_start();
include 'conn.php';

$message = [];

if (isset($_POST['submit'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, md5($_POST['password']));

    $select = mysqli_query($conn, "SELECT * FROM `users` WHERE user_email = '$email' AND password = '$password'") 
    or die('Query failed');

    if (mysqli_num_rows($select) > 0) {
        $user = mysqli_fetch_assoc($select);
        $_SESSION['user'] = $user;
        header('Location: index.php');
        exit();
    } else {
        $message[] = 'Incorrect email or password!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Path2Hire - Sign In</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="navbar__container">
            <a href="index.php" id="navbar__logo">Path2Hire</a>
            <div class="navbar__btn">
                <a href="register.php" class="signup-btn">Sign Up</a>
            </div>
        </div>
    </nav>

    <main>
        <div class="auth-container">
            <form class="auth-form" method="post" action="">
                <h2>Log In</h2>

                <?php
                    if (isset($message)) {
                        foreach ($message as $msg) {
                            echo '<div class="error">' . $msg . '</div>';
                        }
                    }
                ?>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" name="submit" class="login-btn">Log In</button>
                
                <p class="auth-toggle">Don't have an account? <a href="register.php">Sign Up</a></p>
            </form>
        </div>
    </main>

    <script>
        function validateForm() {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            if (!email || !password) {
                alert('Please fill in all fields.');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>