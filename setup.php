<?php
    session_start();
    include 'conn.php';

    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit();
    }

    $userId = $_SESSION['user']['user_id'];
    
    // Fetch current user's major_id, level_id, and field
    $query = mysqli_query($conn, "SELECT major_id, level_id, field FROM users WHERE user_id = '$userId'");
    $userData = mysqli_fetch_assoc($query);
    $userMajor = $userData['major_id'] ?? null;
    $userLevel = $userData['level_id'] ?? null;
    $userField = $userData['field'] ?? null;

    $showPopup = false; // Flag to control popup display

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $major = mysqli_real_escape_string($conn, $_POST['major']);
        $level = mysqli_real_escape_string($conn, $_POST['level']);
        $field = mysqli_real_escape_string($conn, $_POST['field']);

        // Fetch major_name based on major_id
        $majorQuery = mysqli_query($conn, "SELECT major_name FROM major WHERE major_id = '$major'");
        if (!$majorQuery) {
            die("Database query failed: " . mysqli_error($conn));
        }
        $majorData = mysqli_fetch_assoc($majorQuery);
        $majorName = $majorData['major_name'];

        if ($userMajor === null || $userLevel === null) {
            // First-time selection
            mysqli_query($conn, "UPDATE users SET major_id = '$major', level_id = '$level', field = '$field' WHERE user_id = '$userId'");
        } elseif ($userMajor !== $major || $userLevel !== $level || $userField !== $field) {
            // Update only if the selection is different
            mysqli_query($conn, "UPDATE users SET major_id = '$major', level_id = '$level', field = '$field' WHERE user_id = '$userId'");
        }
        
        // Update session data
        $_SESSION['user']['major_id'] = $major;
        $_SESSION['user']['level_id'] = $level;
        $_SESSION['user']['field'] = $field;

        // Prepare data for OpenAI API
        $apiData = [
            'jobRole' => $majorName, // Use major_name instead of major_id
            'industry' => $field,
            'numQuestions' => 10,
            'level_id' => $userLevel, // Pass level_id to the API
            'topic' => $majorName . ' ' . $field
        ];
        $apiDataJson = json_encode($apiData);

        // Send data to OpenAI API
        $ch = curl_init('http://localhost:5000/generate_questions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $apiDataJson);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $questions = json_decode($response, true)['questions'];
            $_SESSION['questions'] = $questions; // Store questions in session
            $showPopup = true; // Show popup to start interview
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Path2Hire - Interview Setup</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Background overlay */
        #pre-start-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            z-index: 999;
        }

        /* Popup container */
        #pre-start {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            width: 90%;
            max-width: 800px;
            padding: 50px;
            background: var(--white);
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            border-top: 4px solid var(--secondary);
        }

        /* Center text */
        #pre-start h2 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            color: var(--primary);
            text-align: center;
            font-weight: 900;
        }

        /* Button */
        #pre-start .reveal-btn {
            display: block;
            margin: 20px auto;
            padding: 15px 30px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            border: none;
            border-radius: 5px;
        }

        .navbar {
            z-index: 888;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar__container">
            <a href="/" id="navbar__logo">Path2Hire</a>
        </div>
    </nav>

    <div class="setup-container">
        <h2>Setup Your Interview</h2>
        <form class="setup-form" method="POST">
            <div class="select-group">
                <label for="major">Select Your Major</label>
                <select id="major" name="major" required>
                    <option value="">Choose your major</option>
                    <?php
                        $sql = mysqli_query($conn, "SELECT * FROM major ORDER BY major_id");
                        while($row = mysqli_fetch_array($sql)) {
                            $selected = ($row['major_id'] == $userMajor) ? 'selected' : '';
                            echo "<option value='{$row['major_id']}' $selected>{$row['major_name']}</option>";
                        }
                    ?>
                </select>
            </div>

            <div class="select-group">
                <label for="field">Your Field</label>
                <input type="text" id="field" name="field" value="<?php echo $userField ? htmlspecialchars($userField) : ''; ?>" placeholder="<?php echo $userField ? '' : 'Add your field'; ?>">
            </div>

            <div class="select-group">
                <label for="level">Experience Level</label>
                <select id="level" name="level" required>
                    <option value="">Choose your level</option>
                    <?php
                        $sql = mysqli_query($conn, "SELECT * FROM level ORDER BY level_id");
                        while($row = mysqli_fetch_array($sql)) {
                            $selected = ($row['level_id'] == $userLevel) ? 'selected' : '';
                            echo "<option value='{$row['level_id']}' $selected>{$row['level_name']}</option>";
                        }
                    ?>
                </select>
            </div>

            <button type="submit" class="button">Start Interview Preparation</button>
        </form>
    </div>

    <div id="pre-start-overlay"></div>

    <!-- Pop-up Modal -->
    <div id="pre-start" class="question-container" style="display: none;">
        <h2>Ready to Start Your Interview?</h2>
        <p>Once you click the button below, you'll have 1 minute to read and understand the question.</p>
        <button class="reveal-btn" id="ready-btn">I'm Ready</button>
    </div>

    <script>
        // Show popup if flag is set
        <?php if ($showPopup): ?>
            document.getElementById('pre-start-overlay').style.display = 'block';
            document.getElementById('pre-start').style.display = 'block';
        <?php endif; ?>

        // Redirect to interview room
        document.getElementById('ready-btn').addEventListener('click', function() {
            window.location.href = 'interview-room.php';
        });
    </script>
</body>
</html>