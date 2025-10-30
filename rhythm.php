<?php
$servername = "localhost";
$username = "yenaing";
$password = "AuXL)8t]l[3akpJO";
$dbname = "mygames";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("DB connection failed.");

$song_id = isset($_GET['song_id']) ? intval($_GET['song_id']) : 0;
if ($song_id <= 0) die("No song selected.");

// Fetch song info
$stmt = $conn->prepare("SELECT file_path, name, bpm FROM songs WHERE id = ?");
$stmt->bind_param("i", $song_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    die("Song not found.");
}
$song = $result->fetch_assoc();
$songPath = $song['file_path'];
$songName = $song['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rhythm Game</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css">
</head>
<body>
    <div class="nav">
        <div>
            <a class="user-profile" href="#">
                <div class="profile-container">
                    <img id="user-image" src="images/default.gif" alt="user profile">
                    <strong id="username">UserName</strong>
                </div>
            </a>
        </div>
        <div class="game-control">
            <audio id="backgroundMusic" src="<?= htmlspecialchars($songPath) ?>" preload="auto"></audio>
            <audio id="hitSound" src="sounds/hit.mp3" preload="auto"></audio>
            <input type="hidden" id="songBpm" value="<?= $song['bpm'] ?>">
            <audio id="countdownBeep" src="sounds/countdown.mp3" preload="auto"></audio>

            <input type="hidden" id="songId" value="<?= $song_id ?>">
            <input type="hidden" id="songDifficulty" value="<?= htmlspecialchars($_GET['difficulty'] ?? 'Normal') ?>">
  

            <button id="volumeButton" title="Mute" class="state-controls">
                <i id="volumeState" class="fa-solid fa-volume-high"></i>
            </button>
            <button id="playPauseButton" title="Pause" class="state-controls">
                <i id="gameState" class="fa-solid fa-pause"></i>
            </button>
            <button id="restartButton" title="Restart" class="state-controls">
                <i class="fa-solid fa-rotate-left"></i>
            </button>
        </div>
        <div class="settings">
            <div class="menu">Menu</div>
            <div class="scoreboard-btn">Scoreboard</div>
        </div>
    </div>

    <div class="score-display">Score:<strong id="gameScore">0</strong></div>

    <div class="main">
        <div class="left">
            <div class="progress-container">
                <div class="progress-bar" id="progressBar"></div>
            </div>
            <div><h3 style="text-align: center;">Now Playing: <?= htmlspecialchars($songName) ?></h3></div>
        </div>

        <div class="center">
            <div id="countdown" class="countdown-hidden">3</div>
            <canvas id="myCanvas" width="500" height="400"></canvas>
            <div id="judgment" class="judgment-hidden">Perfect</div>

            <div id="summaryBox" class="hidden">
                <div id="game-over" class="hidden">Game Over</div>
                <h2>Game Summary</h2>
                <p>Highest Combo: <span id="summaryCombo">0</span></p>
                <p>Perfect Hits: <span id="summaryPerfect">0</span></p>
                <p>Good Hits: <span id="summaryGood">0</span></p>
                <p>Misses: <span id="summaryMisses">0</span></p>
                <button id="playAgainButton">Play Again</button>
            </div>
    
            <div id="missContainer" class="miss-bar">
                <div class="miss-box"></div>
                <div class="miss-box"></div>
                <div class="miss-box"></div>
                <div class="miss-box"></div>
                <div class="miss-box"></div>
            </div>
            

            <div class="lane-buttons">
                <button class="lane-button" id="btn-d">D</button>
                <button class="lane-button" id="btn-f">F</button>
                <button class="lane-button" id="btn-j">J</button>
                <button class="lane-button" id="btn-k">K</button>
            </div>
        </div>

        <div class="right">
            <div id="combo-display">
                <strong id="combo-number">0</strong>
                <strong id="combo-title">Combo</strong>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener("DOMContentLoaded", () => {
            const username = localStorage.getItem("username");
            const profile = localStorage.getItem("profile");

            if (username) {
                document.getElementById("username").textContent = username;
            }
            if (profile) {
                document.getElementById("user-image").src = profile;
            }

            const songId = document.getElementById("songId").value;
            localStorage.setItem("song_id", songId);
        });
    </script>

    <script src="script.js"></script>
</body>
</html>
