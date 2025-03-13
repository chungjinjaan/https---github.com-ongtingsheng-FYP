<?php
session_start();
include 'db.php';

$username = $_POST['username'];
$password = $_POST['password'];

// Validate input
if (empty($username) || empty($password)) {
    die("ERROR: All fields are required.");
}

// Retrieve student data
$query = "SELECT student_id, password FROM students WHERE username = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    // Verify password
    if (password_verify($password, $row['password'])) {
        $_SESSION['student_id'] = $row['student_id'];
        $_SESSION['username'] = $username;
        echo "Login Successful! Redirecting...";
        header("refresh:2; url=../html/dashboard.html");
    } else {
        echo "ERROR: Incorrect password.";
    }
} else {
    echo "ERROR: User not found.";
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
