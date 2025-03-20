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

$query = mysqli_query($conn, "
    SELECT u.field, m.major_name, m.major_id, l.level_id 
    FROM users u
    JOIN major m ON u.major_id = m.major_id
    JOIN level l ON u.level_id = l.level_id
    WHERE u.user_id = '$userId'
");
if (!$query) {
    die("Database query failed: " . mysqli_error($conn));
}
$userData = mysqli_fetch_assoc($query);

$majorName = $userData['major_name'];
$majorId = $userData['major_id'];
$levelId = $userData['level_id'];
$field = $userData['field'];

// Get the next interview number
$interviewNumQuery = mysqli_query($conn, "
    SELECT IFNULL(MAX(interview_num), 0) + 1 AS next_interview_num
    FROM interview_responses
    WHERE user_id = '$userId'
");
$interviewNumData = mysqli_fetch_assoc($interviewNumQuery);
$interviewNum = $interviewNumData['next_interview_num'];

// Fetch questions from session
$questions = $_SESSION['questions'] ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Room - Path2Hire</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="navbar__container">
            <a href="index.php" id="navbar__logo">Path2Hire</a>
            <div class="navbar__btn">
                <a href="feedback.php" class="signup-btn">End Interview</a>
            </div>
        </div>
    </nav>

    <main>
        <div class="interview-room">
            <div class="interview-container">
                <div id="prepPhase" class="interview-phase">
                    <h2>Prepare for Your Question</h2>
                    <div class="prep-info">
                        <p class="prep-note">You have 1 minute to prepare, or you can skip when ready</p>
                        <div id="prepTimer" class="timer">01:00</div>
                    </div>
                    <p class="current-question">Take a moment to gather your thoughts...</p>
                    <button id="skipPrep" class="main__btn">Start Now →</button>
                    <p class="skip-hint">Click 'Start Now' to skip preparation time</p>
                </div>

                <div id="answerPhase" class="interview-phase" style="display: none;">
                    <div class="question-section">
                        <h2>Current Question</h2>
                        <div id="answerTimer" class="timer">02:00</div>
                        <div id="currentQuestion" class="current-question">
                            <?php echo $questions[0] ?? 'No questions available.'; ?>
                        </div>
                    </div>

                    <div class="video-section">
                        <div class="video-container">
                            <video id="videoElement" autoplay playsinline></video>
                        </div>
                        <div class="controls">
                            <button id="startRecord" class="record-btn">Start Recording</button>
                            <button id="stopRecord" class="record-btn" disabled>Stop Recording</button>
                            <button id="skipQuestion" class="secondary-btn">Skip Question</button>
                            <button id="nextQuestion" class="main__btn">Next Question</button>
                        </div>
                        <div id="answerDisplay" class="answer-display">
                            <h3>Your Answer:</h3>
                            <p id="userAnswerText"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const questions = <?php echo json_encode($questions); ?>;
        let currentQuestionIndex = 0;
        let prepTimeLeft = 60;
        let answerTimeLeft = 120;
        let prepTimerInterval;
        let answerTimerInterval;
        let speechSynth = window.speechSynthesis;
        let naturalVoice = null;
        let mediaRecorder;
        let recordedChunks = [];
        let interviewNum = <?php echo $interviewNum; ?>;
        let recognition;
        let userAnswerText = '';
        let isQuestionSpoken = false;

        function initializeSpeechRecognition() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            recognition = new SpeechRecognition();
            recognition.continuous = true;
            recognition.interimResults = true;
            recognition.lang = 'en-US';
            recognition.onresult = (event) => {
                let interimTranscript = '';
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    const transcript = event.results[i][0].transcript;
                    if (event.results[i].isFinal) {
                        userAnswerText += transcript + ' ';
                    } else {
                        interimTranscript += transcript;
                    }
                }
                document.getElementById('userAnswerText').textContent = userAnswerText + interimTranscript;
            };

            recognition.onerror = (event) => {
                console.error('Speech recognition error:', event.error);
            };
        }

        function startSpeechRecognition() {
            userAnswerText = '';
            recognition.start();
        }

        function stopSpeechRecognition() {
            recognition.stop();
        }

        async function saveResponse() {
            const question = questions[currentQuestionIndex];
            const qNumber = currentQuestionIndex + 1;
            const userId = <?php echo $userId; ?>;
            const majorId = <?php echo $majorId; ?>;
            const levelId = <?php echo $levelId; ?>;
            const field = "<?php echo $field; ?>";

            const response = await fetch('save_response.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    q_id: qNumber,
                    q_number: qNumber,
                    user_id: userId,
                    question: question,
                    answer: userAnswerText,
                    interview_num: interviewNum,
                    major_id: majorId,
                    level_id: levelId,
                    field: field
                })
            });

            if (!response.ok) {
                console.error('Failed to save response:', await response.text());
                return false;
            }
            return true;
        }

        async function moveToNextQuestion() {
            const isSaved = await saveResponse();
            if (!isSaved) {
                alert('Failed to save the response. Please try again.');
                return;
            }

            currentQuestionIndex++;
            if (currentQuestionIndex >= questions.length) {
                endInterview();
                return;
            }

            resetAnswerTimer();

            document.getElementById('currentQuestion').textContent = questions[currentQuestionIndex];
            speakQuestion(questions[currentQuestionIndex]);

            userAnswerText = '';
            document.getElementById('userAnswerText').textContent = '';
        }

        async function startRecording() {
            recordedChunks = [];
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    recordedChunks.push(event.data);
                }
            };
            mediaRecorder.start();
            document.getElementById('startRecord').disabled = true;
            document.getElementById('stopRecord').disabled = false;
            startSpeechRecognition();
        }

        function stopRecording() {
            mediaRecorder.stop();
            document.getElementById('startRecord').disabled = false;
            document.getElementById('stopRecord').disabled = true;
            stopSpeechRecognition();

            mediaRecorder.onstop = async () => {
                const audioBlob = new Blob(recordedChunks, { type: 'audio/webm' });
                const reader = new FileReader();
                reader.readAsDataURL(audioBlob);
                reader.onloadend = () => {
                    const base64Audio = reader.result.split(',')[1];
                };
            };
        }

        function startPrepTimer() {
            prepTimerInterval = setInterval(() => {
                prepTimeLeft--;
                const minutes = Math.floor(prepTimeLeft / 60);
                const seconds = prepTimeLeft % 60;
                document.getElementById('prepTimer').textContent = 
                    `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                if (prepTimeLeft <= 0) {
                    clearInterval(prepTimerInterval);
                    startAnswerPhase();
                }
            }, 1000);
        }

        function startAnswerTimer() {
            answerTimerInterval = setInterval(() => {
                answerTimeLeft--;
                const minutes = Math.floor(answerTimeLeft / 60);
                const seconds = answerTimeLeft % 60;
                document.getElementById('answerTimer').textContent = 
                    `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                if (answerTimeLeft <= 0) {
                    clearInterval(answerTimerInterval);
                    moveToNextQuestion();
                }
            }, 1000);
        }

        function resetAnswerTimer() {
            answerTimeLeft = 120
            clearInterval(answerTimerInterval);
        }

        function speakQuestion(question) {
            if (speechSynth.speaking) {
                speechSynth.cancel();
            }

            const voices = speechSynth.getVoices();
            const maleVoice = voices.find(voice => voice.name.includes('Male') || voice.lang === 'en-US');

            const utterance = new SpeechSynthesisUtterance(question);
            if (maleVoice) {
                utterance.voice = maleVoice;
            }
            utterance.pitch = 1;
            utterance.rate = 1;
            utterance.volume = 1;

            utterance.onend = () => {
                isQuestionSpoken = true;
                startAnswerTimer();
            };
            utterance.onerror = (event) => {
                console.error("Speech synthesis error:", event.error);
                isQuestionSpoken = true;
                startAnswerTimer();
            };
            speechSynth.speak(utterance);
        }

        function startAnswerPhase() {
            document.getElementById('prepPhase').style.display = 'none';
            document.getElementById('answerPhase').style.display = 'block';
            if (questions.length > 0) {
                document.getElementById('currentQuestion').textContent = questions[currentQuestionIndex];
                speakQuestion(questions[currentQuestionIndex]);
            }
        }

        function endInterview() {
            clearInterval(answerTimerInterval);
            if (speechSynth.speaking) {
                speechSynth.cancel();
            }
            window.location.href = 'feedback.php';
        }

        initializeSpeechRecognition();

        document.getElementById('skipPrep').addEventListener('click', () => {
            clearInterval(prepTimerInterval);
            startAnswerPhase();
        });

        document.getElementById('skipQuestion').addEventListener('click', moveToNextQuestion);
        document.getElementById('nextQuestion').addEventListener('click', moveToNextQuestion);
        document.getElementById('startRecord').addEventListener('click', startRecording);
        document.getElementById('stopRecord').addEventListener('click', stopRecording);

        startPrepTimer();
    </script>
</body>
</html>