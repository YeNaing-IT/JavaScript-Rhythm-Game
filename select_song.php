<?php
session_start();
$servername = "localhost";
$username = "yenaing";
$password = "AuXL)8t]l[3akpJO";
$dbname = "mygames";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("DB connection failed");

$error = "";
$songs = $conn->query("SELECT * FROM songs");

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    echo "<script>localStorage.clear(); location.href='select_song.php';</script>";
    exit;
}

// Handle login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $user, $pass);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $data = $res->fetch_assoc();
        $_SESSION['username'] = $data['username'];
        $_SESSION['user_id'] = $data['user_id'];
        $_SESSION['profile_image'] = $data['profile_image'];
    } else {
        $error = "Incorrect username or password";
    }
}

// Handle profile image update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $url = trim($_POST['profile_image']);
    $uid = $_SESSION['user_id'];
    if (!empty($url)) {
        $update = $conn->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
        $update->bind_param("si", $url, $uid);
        $update->execute();
        $_SESSION['profile_image'] = $url;
    }
}

// Redirect to game
if (isset($_GET['song_id'])) {
    $selected = $_GET['song_id'];
    $query = $conn->prepare("SELECT difficulty FROM songs WHERE id = ?");
    $query->bind_param("i", $selected);
    $query->execute();
    $res = $query->get_result()->fetch_assoc();
    $diff = $res['difficulty'];
    header("Location: rhythm.php?song_id=$selected&difficulty=$diff");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Select Song</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: Arial; }
        .container { max-width: 300px; margin: 50px auto; text-align: center; }
        img { width: 200px; height: 200px; object-fit: cover; margin-bottom: 10px; }
        select, input[type=text], input[type=password] { padding: 8px; width: 80%; margin-bottom: 10px; }
        button { padding: 10px 20px; margin: 10px; }
        .error { color: red; }
    </style>
</head>
<body>
<div class="container">
    <?php if (!isset($_SESSION['username'])): ?>
        <h2>Login</h2>
        <form method="POST">
            <input type="text" name="username" required placeholder="Username"><br>
            <input type="password" name="password" required placeholder="Password"><br>
            <button type="submit" name="login">Login</button>
        </form>
        <div class="error"><?= $error ?></div>

    <?php elseif (empty($_SESSION['profile_image'])): ?>
        <h3>Enter Profile Image URL</h3>
        <form method="POST">
            <input type="text" name="profile_image" required placeholder="Image URL"><br>
            <button type="submit" name="update_profile">Submit</button>
        </form>

    <?php elseif (isset($_POST['update_profile']) || !isset($_POST['skip_profile'])): ?>
        <div>
            <h3>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h3>
            <img src="<?= $_SESSION['profile_image'] ?>" alt="Profile"><br>

            <form method="POST" style="display: inline-block;">
                <input type="text" name="profile_image" placeholder="New Image URL"><br>
                <button type="submit" name="update_profile">Update Profile</button>
            </form>
            <form method="POST" style="display: inline-block;">
                <button type="submit" name="skip_profile">Skip</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['profile_image']) && $_SESSION['profile_image'] !== ""): ?>
        <h2>Select a Song</h2>
        <form method="GET">
            <select name="song_id" id="song_id" onchange="showAlbum(this)">
                <option value="">-- Choose Song --</option>
                <?php while ($row = $songs->fetch_assoc()): ?>
                    <option value="<?= $row['id'] ?>" data-img="<?= $row['album_image'] ?>">
                        <?= htmlspecialchars($row['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select><br>
            <img id="album" src="images/default.gif" alt="Album Image"><br>
            <button type="submit">Start Game</button>
        </form>
        <button onclick="window.open('scoreboard.php','_blank')">View Scoreboard</button>
        <button onclick="logout()">Logout</button>
    <?php endif; ?>
</div>

<script>
function logout() {
    localStorage.clear();
    location.href = "select_song.php?logout=1";
}

function showAlbum(sel) {
    const img = sel.options[sel.selectedIndex].getAttribute("data-img");
    document.getElementById("album").src = img || "images/default.gif";
}

// Save to localStorage
<?php if (isset($_SESSION['username'])): ?>
    localStorage.setItem("username", "<?= $_SESSION['username'] ?>");
    localStorage.setItem("profile", "<?= $_SESSION['profile_image'] ?>");
<?php endif; ?>
</script>
</body>
</html>
