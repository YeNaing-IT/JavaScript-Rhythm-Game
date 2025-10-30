const canvas = document.getElementById("myCanvas");
const ctx = canvas.getContext("2d");

const countdown = document.getElementById("countdown");
const gameState = document.getElementById('playPauseButton');
const playPauseIcon = document.getElementById("gameState");
const hitSound = document.getElementById("hitSound");
const progressBar = document.getElementById("progressBar");
const restartButton = document.getElementById("restartButton");
const gameOver = document.getElementById("game-over");
const menuButton = document.querySelector('.menu');
const songId = localStorage.getItem("song_id");


let animationFrameId = null;

// Score
let score = 0;
let combo = 0;
let highestCombo = 0;
let perfectCount = 0;
let goodCount = 0;
let missCount = 0;
const maxMisses = 5;
let gameRunning = true;
const judgment = document.getElementById("judgment");

// Ball
const BALL_RADIUS = 30;
let MAX_BALLS = 10;
let BALL_SPEED = 2;

// Read difficulty from hidden input
const difficulty = (document.getElementById("songDifficulty")?.value || "Normal").toLowerCase();

switch (difficulty) {
  case "easy":
    MAX_BALLS = 10;
    BALL_SPEED = 1.5;
    break;
  case "normal":
    MAX_BALLS = 11;
    BALL_SPEED = 2;
    break;
  case "hard":
    MAX_BALLS = 12;
    BALL_SPEED = 2.5;
    break;
  default:
    MAX_BALLS = 11;
    BALL_SPEED = 2;
}

let balls = [];


// Song
const volume = document.getElementById('volumeButton');
const audio = document.getElementById("backgroundMusic");
const volumeIcon = document.getElementById("volumeState");
const BPM = parseInt(document.getElementById("songBpm").value) || 87;
const beatInterval = 60000 / BPM;
let ballSpawnInterval = null;

// Lanes and Keys (updated for 500px canvas)
const lanes = [62.5, 187.5, 312.5, 437.5];
const keys = ['d', 'f', 'j', 'k'];

document.getElementById("gameScore").innerText = score;

menuButton.addEventListener('click', () => {
  const confirmQuit = confirm("Are you sure you want to quit?");
  if (confirmQuit) {
    window.location.href = "select_song.php";
  }
});


// Countdown
function showCountdown(callback) {
  const messages = ["3", "2", "1", "Go!"];
  let index = 0;
  const beep = document.getElementById("countdownBeep");

  countdown.classList.remove("countdown-hidden");
  countdown.innerText = messages[index];
  beep.play();

  const interval = setInterval(() => {
    index++;
    if (index < messages.length) {
      countdown.innerText = messages[index];
    } else {
      clearInterval(interval);
      countdown.classList.add("countdown-hidden");
      callback();
    }
  }, 1000);
}

// Ball Spawner
function startBallSpawner() {
  clearInterval(ballSpawnInterval);
  ballSpawnInterval = setInterval(() => {
    const remaining = audio.duration - audio.currentTime;
    if (remaining <= 3) {
      clearInterval(ballSpawnInterval);
      return;
    }
    if (balls.length < MAX_BALLS) {
      const lane = Math.floor(Math.random() * 4);
      balls.push({ x: lanes[lane], y: -BALL_RADIUS, lane });
    }
  }, beatInterval);
}

// Progress Bar
function updateProgressBar() {
  if (!audio.duration || audio.ended || audio.paused) return;
  const remaining = audio.duration - audio.currentTime;
  const percentRemaining = (remaining / audio.duration) * 100;
  progressBar.style.height = `${100 - percentRemaining}%`;
  requestAnimationFrame(updateProgressBar);
}

// Listeners
audio.addEventListener("ended", () => {
  gameRunning = false;
  clearInterval(ballSpawnInterval);
  submitScore(score);

  gameOver.textContent = "You Win!";
  gameOver.classList.remove("game-over-red");
  gameOver.classList.add("game-over-green");
  gameOver.classList.add("show");

  document.getElementById("summaryMisses").innerText = missCount;
  document.getElementById("summaryCombo").innerText = highestCombo;
  document.getElementById("summaryPerfect").innerText = perfectCount;
  document.getElementById("summaryGood").innerText = goodCount;
  document.getElementById("summaryBox").classList.add("show");
});

volume.addEventListener("click", () => {
  audio.muted = !audio.muted;
  volumeIcon.className = audio.muted ? "fa-solid fa-volume-xmark" : "fa-solid fa-volume-high";
  volume.title = audio.muted ? "Unmute" : "Mute";
});

gameState.addEventListener("click", () => {
  if (audio.paused) {
    audio.play();
    gameRunning = true;
    playPauseIcon.className = "fa-solid fa-pause";
    gameLoop();
    requestAnimationFrame(updateProgressBar);
  } else {
    audio.pause();
    gameRunning = false;
    playPauseIcon.className = "fa-solid fa-play";
  }
});

restartButton.addEventListener("click", restartGame);
document.getElementById("playAgainButton").addEventListener("click", restartGame);

function restartGame() {
  cancelAnimationFrame(animationFrameId);
  score = 0;
  combo = 0;
  missCount = 0;
  highestCombo = 0;
  perfectCount = 0;
  goodCount = 0;
  balls = [];
  updateMissBoxes();

  document.getElementById("gameScore").innerText = score;
  document.getElementById("combo-number").innerText = combo;
  document.getElementById("summaryBox").classList.remove("show");
  gameOver.classList.remove("show");
  clearInterval(ballSpawnInterval);
  audio.pause();
  audio.currentTime = 0;
  

  ctx.clearRect(0, 0, canvas.width, canvas.height);
  drawLanes(); 

  showCountdown(() => {
    gameRunning = true;
    audio.play();
    startBallSpawner();
    requestAnimationFrame(updateProgressBar);
    gameLoop();
  });
}

// Drawing
function drawLanes() {
    ctx.strokeStyle = "#999";
    ctx.lineWidth = 2;
    for (let i = 1; i < 4; i++) {
      const x = i * 125;
      ctx.beginPath();
      ctx.moveTo(x, 0);
      ctx.lineTo(x, canvas.height);
      ctx.stroke();
    }
}


function drawBalls() {
  balls.forEach(ball => {
    ctx.beginPath();
    ctx.arc(ball.x, ball.y, BALL_RADIUS, 0, Math.PI * 2);
    ctx.fillStyle = "green";
    ctx.fill();
    ctx.strokeStyle = "black";
    ctx.lineWidth = 2;
    ctx.stroke();
  });
}

function updateBalls() {
  balls.forEach(ball => ball.y += BALL_SPEED);

  balls = balls.filter(ball => {
    if (ball.y > canvas.height) {
      missCount++;
      updateCombo(false);
      showJudgment("Miss");
      if (score > 0) score = Math.max(0, score - 10);
      document.getElementById("gameScore").innerText = score;
      updateMissBoxes();
      return false;
    }
    return true;
  });

  if (missCount >= maxMisses) {
    gameRunning = false;
    clearInterval(ballSpawnInterval);
    audio.pause();

    submitScore(score);

    gameOver.textContent = "Game Over";
    gameOver.classList.remove("game-over-green");
    gameOver.classList.add("game-over-red");
    gameOver.classList.add("show");

    document.getElementById("summaryMisses").innerText = missCount;
    document.getElementById("summaryCombo").innerText = highestCombo;
    document.getElementById("summaryPerfect").innerText = perfectCount;
    document.getElementById("summaryGood").innerText = goodCount;
    document.getElementById("summaryBox").classList.add("show");
  }
}

function updateCombo(hit) {
  combo = hit ? combo + 1 : 0;
  if (combo > highestCombo) highestCombo = combo;
  document.getElementById("combo-number").innerText = combo;
}

// Key Events
document.addEventListener("keydown", (e) => {
  if (!gameRunning) return;
  const key = e.key.toLowerCase();
  const laneIndex = keys.indexOf(key);

  if (laneIndex !== -1) {
    const button = document.getElementById(`btn-${key}`);
    if (button) button.classList.add("active");

    const hitY = canvas.height - 50;
    for (let i = 0; i < balls.length; i++) {
      const ball = balls[i];
      const distance = Math.abs(ball.y - hitY);
      if (ball.lane === laneIndex && distance < 40) {
        balls.splice(i, 1);

        if (distance < 15) {
          showJudgment("Perfect");
          score += 50;
          perfectCount++;
        } else if (distance < 35) {
          showJudgment("Good");
          score += 25;
          goodCount++;
        } else {
          showJudgment("Miss");
          if (score > 0) score = Math.max(0, score - 10);
        }

        updateCombo(true);
        document.getElementById("gameScore").innerText = score;

        hitSound.pause();
        hitSound.currentTime = 0;
        hitSound.play();
        break;
      }
    }
  }
});

document.addEventListener("keyup", (e) => {
  const key = e.key.toLowerCase();
  const button = document.getElementById(`btn-${key}`);
  if (button) button.classList.remove("active");
});

// Game Loop
function gameLoop() {
  if (!gameRunning) return;
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  drawLanes();
  drawBalls();
  updateBalls();
  animationFrameId = requestAnimationFrame(gameLoop);
}

// Start Game
audio.pause();
audio.currentTime = 0;

ctx.clearRect(0, 0, canvas.width, canvas.height);
drawLanes();

showCountdown(() => {
  gameRunning = true;
  audio.play();
  startBallSpawner();
  requestAnimationFrame(updateProgressBar);
  gameLoop();
});

// Judgments
function showJudgment(type) {
  judgment.textContent = type;
  judgment.className = `show ${type.toLowerCase()}`;
  setTimeout(() => {
    judgment.className = "judgment-hidden";
  }, 600);
}

// Miss Indicator Boxes
function updateMissBoxes() {
  const boxes = document.querySelectorAll(".miss-box");

  boxes.forEach((box, index) => {
    if (index < missCount) {
      box.classList.add("missed");
    } else {
      box.classList.remove("missed");
    }
  });
}

// Submit Score
function submitScore(score) {
    const username = localStorage.getItem("username");
    const songId = document.getElementById("songId").value;

    const data = new URLSearchParams();
    data.append("username", username);
    data.append("score", score);
    data.append("combo", highestCombo);
    data.append("perfect", perfectCount);
    data.append("good", goodCount);
    data.append("miss", missCount);
    data.append("song_id", songId);

    fetch("submit_score.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: data.toString()
    })
    .then(res => res.text())
    .then(console.log)
    .catch(console.error);
}

// Show Scoreboard
document.querySelector('.scoreboard-btn').addEventListener('click', () => {
   window.open("scoreboard.php", "_blank");
});

