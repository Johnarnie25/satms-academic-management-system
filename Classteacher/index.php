<?php 
include 'header.php';

// ================= DEFAULT VALUES =================
$totalSubjects = 0;
$totalStudents = 0;
$totalRooms = 0;
$totalAttendance = 0;
$totalParents = 0;
$totalCourses = 0;
$attendanceDates = [];
$attendanceCounts = [];

$user_id = $_SESSION['user_id'] ?? 0;

// ================= GET TEACHER ID =================
$teacherQuery = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
$teacherQuery->bind_param("i", $user_id);
$teacherQuery->execute();
$teacherResult = $teacherQuery->get_result();

if($teacherResult->num_rows > 0){
    $teacherRow = $teacherResult->fetch_assoc();
    $teacher_id = $teacherRow['teacher_id'];

    // ================= TOTAL SUBJECTS =================
    $q1 = $conn->prepare("SELECT COUNT(*) as total FROM subjects WHERE teacher_id = ?");
    $q1->bind_param("i", $teacher_id);
    $q1->execute();
    $totalSubjects = $q1->get_result()->fetch_assoc()['total'];

    // ================= TOTAL ROOMS =================
    $q2 = $conn->prepare("SELECT COUNT(DISTINCT room_id) as total FROM subjects WHERE teacher_id = ? AND room_id IS NOT NULL");
    $q2->bind_param("i", $teacher_id);
    $q2->execute();
    $totalRooms = $q2->get_result()->fetch_assoc()['total'];

    // ================= TOTAL STUDENTS =================
    $q3 = $conn->prepare("
        SELECT COUNT(DISTINCT ss.student_id) as total
        FROM student_subjects ss
        JOIN subjects s ON ss.subject_id = s.subject_id
        WHERE s.teacher_id = ?
    ");
    $q3->bind_param("i", $teacher_id);
    $q3->execute();
    $totalStudents = $q3->get_result()->fetch_assoc()['total'];

    // ================= TOTAL PARENTS =================
    $qParent = $conn->prepare("
        SELECT COUNT(DISTINCT st.parent_id) as total
        FROM student_subjects ss
        JOIN subjects s ON ss.subject_id = s.subject_id
        JOIN students st ON ss.student_id = st.student_id
        WHERE s.teacher_id = ? AND st.parent_id IS NOT NULL
    ");
    $qParent->bind_param("i", $teacher_id);
    $qParent->execute();
    $totalParents = $qParent->get_result()->fetch_assoc()['total'];

    // ================= TOTAL COURSES =================
    $qCourse = $conn->prepare("SELECT COUNT(DISTINCT s.course_id) as total FROM subjects s WHERE s.teacher_id = ?");
    $qCourse->bind_param("i", $teacher_id);
    $qCourse->execute();
    $totalCourses = $qCourse->get_result()->fetch_assoc()['total'];

    // ================= TOTAL ATTENDANCE =================
    $q4 = $conn->prepare("
        SELECT COUNT(*) as total
        FROM attendance a
        JOIN subjects s ON a.subject_id = s.subject_id
        WHERE s.teacher_id = ?
    ");
    $q4->bind_param("i", $teacher_id);
    $q4->execute();
    $totalAttendance = $q4->get_result()->fetch_assoc()['total'];

    // ================= ATTENDANCE PER DAY =================
    $q5 = $conn->prepare("
        SELECT DATE(a.attendance_date) as adate, COUNT(*) as cnt
        FROM attendance a
        JOIN subjects s ON a.subject_id = s.subject_id
        WHERE s.teacher_id = ?
        GROUP BY DATE(a.attendance_date)
        ORDER BY DATE(a.attendance_date) ASC
    ");
    $q5->bind_param("i", $teacher_id);
    $q5->execute();
    $resultChart = $q5->get_result();

    while($row = $resultChart->fetch_assoc()){
        $attendanceDates[] = $row['adate'];
        $attendanceCounts[] = (int)$row['cnt'];
    }
}
?>

<main>
    <div class="head-title">
        <div class="left">
            <h1>Dashboard</h1>
            <ul class="breadcrumb">
                <li><a href="#">Home</a></li>
                <li><i class='bx bx-chevron-right'></i></li>
                <li><a class="active" href="#">Dashboard</a></li>
            </ul>
        </div>
    </div>

    <!-- Dashboard Boxes -->
    <ul class="box-info">
        <li>
            <i class='bx bxs-book'></i>
            <span class="text">
                <h3><?php echo $totalSubjects; ?></h3>
                <p>My Subjects</p>
            </span>
        </li>
        <li>
            <i class='bx bxs-user'></i>
            <span class="text">
                <h3><?php echo $totalStudents; ?></h3>
                <p>Total Students</p>
            </span>
        </li>
        <li>
            <i class='bx bxs-grid'></i>
            <span class="text">
                <h3><?php echo $totalRooms; ?></h3>
                <p>Assigned Rooms</p>
            </span>
        </li>
        <li>
            <i class='bx bxs-check-shield'></i>
            <span class="text">
                <h3><?php echo $totalAttendance; ?></h3>
                <p>Total Attendance</p>
            </span>
        </li>
    </ul>

    <!-- School Data & Attendance Chart -->
    <div class="table-data">
        <div class="verify">
            <div class="head">
                <h3>School Data</h3>
            </div>
            <ul class="verify-list">
                <li>
                    <span class="stat-left">
                        <span class="stat-icon icon-courses" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2H6a2 2 0 0 0-2 2v16a1 1 0 0 0 1.447.894L12 18l6.553 2.894A1 1 0 0 0 20 20V4a2 2 0 0 0-2-2z"/></svg>
                        </span>
                        <strong class="stat-number courses-color"><?php echo $totalCourses ?? '--'; ?></strong>
                    </span>
                    <span>Total Courses</span>
                </li>
                <li>
                    <span class="stat-left">
                        <span class="stat-icon icon-parents" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM8 11c1.657 0 3-1.343 3-3S9.657 5 8 5 5 6.343 5 8s1.343 3 3 3zM8 13c-2.67 0-8 1.337-8 4v2h16v-2c0-2.663-5.33-4-8-4z"/></svg>
                        </span>
                        <strong class="stat-number parents-color"><?php echo $totalParents ?? '--'; ?></strong>
                    </span>
                    <span>Total Parents</span>
                </li>
            </ul>
        </div>
        <div class="order">
            <div class="head">
                <h3>Attendance Analytics</h3>
                <i class='bx bx-filter'></i>
            </div>
            <div style="height:400px; width:100%;">
                <canvas id="residentChart"></canvas>
            </div>
        </div>
    </div>
</main>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
window.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('residentChart').getContext('2d');

    const labels = <?php echo json_encode($attendanceDates ?: []); ?>;
    const dataCounts = <?php echo json_encode($attendanceCounts ?: []); ?>;

    if(labels.length === 0){
        ctx.font = "16px Arial";
        ctx.fillText("No attendance data available", 50, 50);
        return;
    }

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Attendance per Day',
                data: dataCounts,
                borderColor: '#127106',
                backgroundColor: 'rgba(60,145,230,0.2)',
                fill: true,
                tension: 0.3,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true, position: 'top' }
            },
            scales: {
                x: {
                    title: { display: true, text: 'Date' },
                    ticks: { autoSkip: true, maxTicksLimit: 10 }
                },
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Count' },
                    precision:0
                }
            }
        }
    });
});
</script>

<?php include 'stms_footer.php'; ?>
