<?php
include 'db.php';

// Ensure session is started
if (!isset($_SESSION)) {
    session_start();
}

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Extract values safely
$faceImage = $data['face_image'] ?? null;
$classId = $data['class_id'] ?? null;
$currentTime = date("H:i:s");
$currentDate = date("Y-m-d");

// Log received data for debugging
file_put_contents("debug_log.txt", "Received Data: " . print_r($data, true) . PHP_EOL, FILE_APPEND);

// Validate input
if (empty($faceImage) || empty($classId)) {
    die("ERROR: Missing required data.");
}

// Ensure the uploads directory exists
$uploadDir = "../uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Convert base64 to an image file
$imageData = base64_decode(str_replace("data:image/png;base64,", "", $faceImage));
$imagePath = $uploadDir . uniqid() . ".png";

if (!file_put_contents($imagePath, $imageData)) {
    die("ERROR: Failed to save image.");
}

// Fetch all stored face encodings from database
$query = "SELECT student_id, face_encoding FROM students";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("ERROR: Database query failed - " . mysqli_error($conn));
}

$matchedUser = null;

// Set correct Python path
$pythonPath = "C:/Users/G3-15/AppData/Local/Programs/Python/Python312/python.exe";
$scriptPath = realpath("../python/verify_face.py");

while ($row = mysqli_fetch_assoc($result)) {
    $dbFaceEncoding = json_encode(json_decode($row['face_encoding'])); // Ensure valid JSON
    $dbFaceEncodingEscaped = escapeshellarg($dbFaceEncoding);

    // Run Python script to compare faces
    $command = escapeshellcmd("$pythonPath $scriptPath $imagePath $dbFaceEncodingEscaped");
    $matchResult = shell_exec($command);

    // Debugging
    file_put_contents("debug_log.txt", "Python Output: " . $matchResult . PHP_EOL, FILE_APPEND);

    if (strpos($matchResult, "Match Found") !== false) {
        $matchedUser = $row;
        break;
    }
}

// ✅ If face is recognized, proceed with attendance
if ($matchedUser) {
    $studentId = $matchedUser['student_id'];

    // ✅ Check if the student is enrolled in the class
    $checkEnrollmentQuery = "SELECT c.start_time, c.end_time 
                             FROM student_classes sc
                             JOIN class c ON sc.class_id = c.class_id
                             WHERE sc.student_id = ? AND sc.class_id = ?";
    $stmt = mysqli_prepare($conn, $checkEnrollmentQuery);
    mysqli_stmt_bind_param($stmt, "ii", $studentId, $classId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $classData = mysqli_fetch_assoc($result);

    if (!$classData) {
        die("ERROR: You are not enrolled in this class.");
    }

    $startTime = $classData['start_time'];
    $endTime = $classData['end_time'];

    // ✅ Check if attendance is within the scheduled class time
    if ($currentTime < $startTime || $currentTime > $endTime) {
        die("ERROR: Attendance can only be marked during the scheduled class time.");
    }

    // ✅ Prevent duplicate attendance for the same student on the same day
    $checkQuery = "SELECT * FROM attendance WHERE student_id = ? AND class_id = ? AND DATE(timestamp) = CURDATE()";
    $stmt = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($stmt, "ii", $studentId, $classId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        die("ERROR: Attendance already marked today.");
    }

    // ✅ Insert attendance record
    $insertQuery = "INSERT INTO attendance (student_id, class_id, timestamp) VALUES (?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $insertQuery);
    mysqli_stmt_bind_param($stmt, "ii", $studentId, $classId);

    if (mysqli_stmt_execute($stmt)) {
        echo "Attendance Marked Successfully!";
    } else {
        echo "ERROR: Could not mark attendance - " . mysqli_error($conn);
    }
} else {
    echo "ERROR: Face not recognized. Try again.";
}

// Close the database connection
mysqli_close($conn);
?>
