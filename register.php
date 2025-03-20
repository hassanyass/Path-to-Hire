<?php
session_start(); // Start the session
include 'conn.php'; // Include your database connection file

$message = []; // Initialize an empty message array

if (isset($_POST['submit'])) {
    $fullName = mysqli_real_escape_string($conn, $_POST['fullName']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, md5($_POST['password'])); // Hash the password
    $confirmPassword = mysqli_real_escape_string($conn, md5($_POST['confirmPassword'])); // Hash the confirm password

    // Check if the user already exists
    $select = mysqli_query($conn, "SELECT * FROM `users` WHERE user_name = '$fullName' OR user_email = '$email'") 
    or die('Query failed');

    if (mysqli_num_rows($select) > 0) {
        $message[] = 'User already exists!';
    } else {
        if ($password != $confirmPassword) {
            $message[] = 'Confirm password does not match!';
        } else {
            // Insert the new user into the database
            $insert = mysqli_query($conn, "INSERT INTO `users` (user_name, user_email, password) 
            VALUES ('$fullName', '$email', '$password')") 
            or die('Query failed');

            if ($insert) {
                // Automatically log the user in after successful registration
                $selectUser = mysqli_query($conn, "SELECT * FROM `users` WHERE user_email = '$email' AND password = '$password'") 
                or die('Query failed');

                if (mysqli_num_rows($selectUser) > 0) {
                    $user = mysqli_fetch_assoc($selectUser); // Fetch user data
                    $_SESSION['user'] = $user; // Store user data in the session
                    $message[] = 'Registered successfully!';
                    header('Location: index.php'); // Redirect to index.php
                    exit();
                }
            } else {
                $message[] = 'Registration failed!';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Path2Hire</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="navbar__container">
            <a href="index.php" id="navbar__logo">Path2Hire</a>
            <div class="navbar__btn">
                <a href="login.php" class="login-btn">Sign In</a>
            </div>
        </div>
    </nav>

    <main>
        <div class="auth-container">
            <form class="auth-form" id="signupForm" method="post" action="">
                <!-- Display error/success messages -->
                <?php
                    if (isset($message)) {
                        foreach ($message as $msg) {
                            echo '<div class="error">' . $msg . '</div>';
                        }
                    }
                ?>

                <div class="form-group">
                    <label for="fullName">Full Name</label>
                    <input type="text" id="fullName" name="fullName" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" 
                           required minlength="8" maxlength="64"
                           pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,64}$"
                           title="Password must be 8-64 characters long and include at least one letter and one number">
                    <div class="password-requirements">
                        Password must be 8-64 characters long and include:
                        <ul>
                            <li id="length-check">✓ 8-64 characters</li>
                            <li id="letter-check">✓ At least one letter</li>
                            <li id="number-check">✓ At least one number</li>
                        </ul>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                    <div class="password-match" id="passwordMatch"></div>
                </div>

                <button name="submit" type="submit" class="signup-btn">Create Account</button>
                
                <div class="auth-toggle">
                    Already have an account? <a href="login.php">Sign In</a>
                </div>
            </form>
        </div>
    </main>

    <script>
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirmPassword');
        const passwordMatch = document.getElementById('passwordMatch');
        const lengthCheck = document.getElementById('length-check');
        const letterCheck = document.getElementById('letter-check');
        const numberCheck = document.getElementById('number-check');

        // Real-time password validation
        password.addEventListener('input', validatePassword);
        confirmPassword.addEventListener('input', checkPasswordMatch);

        function validatePassword() {
            const value = password.value;
            
            // Check length
            if (value.length >= 8 && value.length <= 64) {
                lengthCheck.style.color = 'var(--secondary)';
            } else {
                lengthCheck.style.color = 'var(--gray-600)';
            }
            
            // Check for letters
            if (/[A-Za-z]/.test(value)) {
                letterCheck.style.color = 'var(--secondary)';
            } else {
                letterCheck.style.color = 'var(--gray-600)';
            }
            
            // Check for numbers
            if (/\d/.test(value)) {
                numberCheck.style.color = 'var(--secondary)';
            } else {
                numberCheck.style.color = 'var(--gray-600)';
            }

            checkPasswordMatch();
        }

        function checkPasswordMatch() {
            if (confirmPassword.value === '') {
                passwordMatch.textContent = '';
                return;
            }
            
            if (password.value === confirmPassword.value) {
                passwordMatch.textContent = '✓ Passwords match';
                passwordMatch.style.color = 'var(--secondary)';
            } else {
                passwordMatch.textContent = '✗ Passwords do not match';
                passwordMatch.style.color = '#ff4444';
            }
        }
    </script>
</body>
</html>