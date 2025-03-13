<?php
require_once "../config/classDatabase.php";

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Prepare the query
    $stmt = $conn->prepare("
        SELECT 
            s.student_id,
            s.name,
            c.subject_name,
            COUNT(CASE WHEN a.status = 'Present' THEN 1 END) AS present_days,
            c.total_classes,
            ROUND((COUNT(CASE WHEN a.status = 'Present' THEN 1 END) / c.total_classes) * 100, 2) AS attendance_percentage
        FROM students s
        JOIN attendance a ON s.student_id = a.student_id
        JOIN class c ON a.class_id = c.class_id
        GROUP BY s.student_id, c.subject_name, c.total_classes
        ORDER BY s.student_id, c.subject_name
    ");

    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attendance Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            text-align: center;
        }

        .container {
            width: 90%;
            margin: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid black;
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #f4f4f4;
        }

        .student-name {
            font-weight: bold;
            background-color: #e8f6ff;
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>Student Attendance Report</h2>

        <table>
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Subject</th>
                    <th>Present Days</th>
                    <th>Total Classes</th>
                    <th>Attendance (%)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $current_student = null;
                foreach ($students as $row) {
                    // If new student, display name once and merge rows
                    if ($current_student !== $row['student_id']) {
                        $current_student = $row['student_id'];
                        echo "<tr class='student-name'>";
                        echo "<td>{$row['student_id']}</td>";
                        echo "<td>{$row['username']}</td>";
                        echo "<td colspan='4'></td>"; // Empty columns to span
                        echo "</tr>";
                    }
                    ?>
                    <tr>
                        <td></td>
                        <td></td>
                        <td><?= htmlspecialchars($row['subject_name']) ?></td>
                        <td><?= htmlspecialchars($row['present_days']) ?></td>
                        <td><?= htmlspecialchars($row['total_classes']) ?></td>
                        <td><?= htmlspecialchars($row['attendance_percentage']) ?>%</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

</body>

</html>