<?php
include 'db.php';

$username = $_POST['username'];
$password = password_hash($_POST['password'], PASSWORD_BCRYPT);
$contact_number = $_POST['contact_number']; // New field for contact number
$faceImage = $_POST['face_image'];  // Base64 image from the client

// Validate input
if (empty($username) || empty($_POST['password']) || empty($contact_number) || empty($faceImage)) {
    die("ERROR: All fields are required.");
}

// Ensure the uploads directory exists
$uploadDir = "../uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Convert Base64 to an image file
$imageData = base64_decode(str_replace("data:image/png;base64,", "", $faceImage));
$imagePath = $uploadDir . uniqid() . ".png";

if (!file_put_contents($imagePath, $imageData)) {
    die("ERROR: Failed to save image.");
}

// Insert user into the database first to get student_id
$query = "INSERT INTO students (username, password, contact_number) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "sss", $username, $password, $contact_number);

if (!mysqli_stmt_execute($stmt)) {
    die("ERROR: Database insertion failed.");
}

// Get the student_id of the newly inserted student
$student_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

// Set correct Python path (change if needed)
$pythonPath = "C:/Users/G3-15/AppData/Local/Programs/Python/Python312/python.exe";
$scriptPath = realpath("../python/encode_face.py");

// Run Python script & capture output
$command = escapeshellcmd("$pythonPath $scriptPath $imagePath $student_id");
$output = shell_exec($command);

// Debugging: Log the output
file_put_contents("debug_log.txt", "Python Output: " . $output . PHP_EOL, FILE_APPEND);

if (strpos($output, "Error") !== false) {
    die("ERROR: Face not detected. Try again.");
}

echo "Registration Successful!";
mysqli_close($conn);
?>
