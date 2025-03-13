<?php
include 'db.php';

date_default_timezone_set('Asia/Kuala_Lumpur');
echo "Server Time: " . date("Y-m-d H:i:s");

// Ensure session is started
if (!isset($_SESSION)) {
    session_start();
}

// Manually set student ID and class ID for testing
$studentId = 58;  // Replace with an actual student_id from your database
$classId = 101;    // Replace with an actual class_id

$currentTime = date("H:i:s"); // Get current time
$currentDate = date("Y-m-d"); // Get current date

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
    echo "Attendance Marked Successfully for Student ID $studentId in Class ID $classId!";
} else {
    echo "ERROR: Could not mark attendance - " . mysqli_error($conn);
}

// Close the database connection
mysqli_close($conn);
?>
