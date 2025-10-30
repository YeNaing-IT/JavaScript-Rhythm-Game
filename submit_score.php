<?php
$servername = "localhost";
$username = "yenaing";
$password = "AuXL)8t]l[3akpJO";
$dbname = "mygames";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed");
}

// Collect POST values
$username = $_POST['username'] ?? '';
$score = intval($_POST['score'] ?? 0);
$combo = intval($_POST['combo'] ?? 0);
$miss = intval($_POST['miss'] ?? 0);
$perfect = intval($_POST['perfect'] ?? 0);
$good = intval($_POST['good'] ?? 0);
$song_id = intval($_POST['song_id'] ?? 0);

// Validate
if (empty($username) || $song_id <= 0) {
    echo "Missing username or song_id\n";
    exit;
}

// Check if user already has a score for this song
$stmt = $conn->prepare("SELECT high_score FROM scoreboard WHERE username = ? AND song_id = ?");
$stmt->bind_param("si", $username, $song_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($score > $row['high_score']) {
        $update = $conn->prepare("UPDATE scoreboard SET high_score = ?, combo = ?, miss = ?, perfect = ?, good = ? WHERE username = ? AND song_id = ?");
        $update->bind_param("iiiiisi", $score, $combo, $miss, $perfect, $good, $username, $song_id);
        $update->execute();
        echo "Updated high score\n";
    } else {
        echo "Score not higher, not updated\n";
    }
} else {
    $insert = $conn->prepare("INSERT INTO scoreboard (username, high_score, combo, miss, perfect, good, song_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $insert->bind_param("siiiiii", $username, $score, $combo, $miss, $perfect, $good, $song_id);
    $insert->execute();
    echo "Score inserted\n";
}

$conn->close();
?>
