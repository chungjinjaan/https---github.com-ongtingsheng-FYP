<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $class_id = $_POST['class_id'];

    // Check if the student is already in the class
    $checkQuery = "SELECT * FROM student_classes WHERE student_id = ? AND class_id = ?";
    $stmt = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($stmt, "ii", $student_id, $class_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        echo json_encode(["status" => "error", "message" => "Student is already enrolled in this class."]);
    } else {
        // Insert student into class
        $insertQuery = "INSERT INTO student_classes (student_id, class_id, status) VALUES (?, ?, 1)";
        $stmt = mysqli_prepare($conn, $insertQuery);
        mysqli_stmt_bind_param($stmt, "ii", $student_id, $class_id);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(["status" => "success", "message" => "Student successfully added to class!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Could not add student to class."]);
        }
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    exit;
}
?>
