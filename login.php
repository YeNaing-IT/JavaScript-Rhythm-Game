<?php
$servername = "localhost";
$username = "yenaing";
$password = "AuXL)8t]l[3akpJO";
$dbname = "mygames";

// Connect
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("DB error");
}

// Sanitize Input
$user = $_POST['username'] ?? '';
$pass = $_POST['password'] ?? '';

if (!$user || !$pass) {
    die("error");
}

// Query
$stmt = $conn->prepare("SELECT profile_image FROM users WHERE username = ? AND password = ?");
$stmt->bind_param("ss", $user, $pass);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    echo "success," . $row['profile_image'];
} else {
    echo "username or Password Incorrect";
}

$conn->close();
?>
