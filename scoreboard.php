<?php
// DB connection
$servername = "localhost";
$username = "yenaing";
$password = "AuXL)8t]l[3akpJO";
$dbname = "mygames";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed");
}

// Get selected song ID from GET (default to 1)
$song_id = isset($_GET['song_id']) ? intval($_GET['song_id']) : 1;

// Get song name
$songName = "Unknown Song";
$songQuery = $conn->prepare("SELECT name FROM songs WHERE id = ?");
$songQuery->bind_param("i", $song_id);
$songQuery->execute();
$songResult = $songQuery->get_result();
if ($songResult && $songResult->num_rows > 0) {
    $row = $songResult->fetch_assoc();
    $songName = $row['name'];
}

// Get top scores for that song
$stmt = $conn->prepare("SELECT username, high_score, combo, miss, perfect, good FROM scoreboard WHERE song_id = ? ORDER BY high_score DESC LIMIT 10");
$stmt->bind_param("i", $song_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Scoreboard</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; }
        table { margin: auto; border-collapse: collapse; width: 80%; }
        th, td { padding: 10px; border: 1px solid #444; }
        select { padding: 5px; margin: 20px; }
        h1 { margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Top Scores - <?= htmlspecialchars($songName) ?></h1>

    <form method="GET" onchange="this.submit()">
        <label for="song_id">Select Song:</label>
        <select name="song_id" id="song_id">
            <?php
            $songs = $conn->query("SELECT id, name FROM songs");
            while ($song = $songs->fetch_assoc()) {
                $selected = $song['id'] == $song_id ? "selected" : "";
                echo "<option value='{$song['id']}' $selected>" . htmlspecialchars($song['name']) . "</option>";
            }
            ?>
        </select>
    </form>

    <table>
        <tr>
            <th>Rank</th>
            <th>Username</th>
            <th>Score</th>
            <th>Combo</th>
            <th>Perfect</th>
            <th>Good</th>
            <th>Miss</th>
        </tr>
        <?php
        $rank = 1;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                    <td>{$rank}</td>
                    <td>" . htmlspecialchars($row['username']) . "</td>
                    <td>{$row['high_score']}</td>
                    <td>{$row['combo']}</td>
                    <td>{$row['perfect']}</td>
                    <td>{$row['good']}</td>
                    <td>{$row['miss']}</td>
                  </tr>";
                $rank++;
            }
        } else {
            echo "<tr><td colspan='7'>No scores yet for this song.</td></tr>";
        }
        ?>
    </table>

    <br>
    <a href="select_song.php">Back to Home</a>
</body>
</html>
