<?php 
include 'header.php';
// ----------------------- Dashboard Counts -----------------------

// Total Students
$queryStudents = $conn->query("SELECT COUNT(*) AS totalStudents FROM users WHERE role='student'");
$rowStudents = $queryStudents->fetch_assoc();
$totalStudents = $rowStudents['totalStudents'];


// Total Teachers
$queryTeachers = $conn->query("SELECT COUNT(*) AS totalTeachers FROM users WHERE role='teacher'");
$rowTeachers = $queryTeachers->fetch_assoc();
$totalTeachers = $rowTeachers['totalTeachers'];

// Total Subjects
$querySubjects = $conn->query("SELECT COUNT(*) AS totalSubjects FROM subjects");
$rowSubjects = $querySubjects->fetch_assoc();
$totalSubjects = $rowSubjects['totalSubjects'];

// Total Classes (Rooms)
$queryClasses = $conn->query("SELECT COUNT(*) AS totalClasses FROM rooms");
$rowClasses = $queryClasses->fetch_assoc();
$totalClasses = $rowClasses['totalClasses'];

// Total Courses
$queryCourses = $conn->query("SELECT COUNT(*) AS totalCourses FROM courses");
$rowCourses = $queryCourses->fetch_assoc();
$totalCourses = $rowCourses['totalCourses'];

// Total Parents
$queryParents = $conn->query("SELECT COUNT(*) AS totalParents FROM users WHERE role='parent'");
$rowParents = $queryParents->fetch_assoc();
$totalParents = $rowParents['totalParents'];

// Total Attendance
$queryAttendance = $conn->query("SELECT COUNT(*) AS totalAttendance FROM attendance");
$rowAttendance = $queryAttendance->fetch_assoc();
$totalAttendance = $rowAttendance['totalAttendance'];

// Attendance per day for chart
$attendanceDates = [];
$attendanceCounts = [];
$q = $conn->query("SELECT DATE(attendance_date) AS adate, COUNT(*) AS cnt 
                   FROM attendance 
                   GROUP BY DATE(attendance_date) 
                   ORDER BY DATE(attendance_date) ASC");
while($r = $q->fetch_assoc()){
    $attendanceDates[] = $r['adate'];
    $attendanceCounts[] = (int)$r['cnt'];
}
?>

<!-- MAIN -->
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
            <i class='bx bxs-user-badge'></i>
            <span class="text">
                <h3 id="total-students"><?php echo $totalStudents ?? '--'; ?></h3>
                <p>Total Students</p>
            </span>
        </li>
        <li>
            <i class='bx bxs-user'></i>
            <span class="text">
                <h3 id="total-teachers"><?php echo $totalTeachers ?? '--'; ?></h3>
                <p>Class Teachers</p>
            </span>
        </li>
        <li>
            <i class='bx bxs-book'></i>
            <span class="text">
                <h3 id="total-subjects"><?php echo $totalSubjects ?? '--'; ?></h3>
                <p>Total Subjects</p>
            </span>
        </li>
        <li>
            <i class='bx bxs-grid'></i>
            <span class="text">
                <h3 id="total-classes"><?php echo $totalClasses ?? '--'; ?></h3>
                <p>Room Section</p>
            </span>
        </li>
        <li>
            <i class='bx bxs-check-shield'></i>
            <span class="text">
                <h3 id="total-attendance"><?php echo $totalAttendance ?? '--'; ?></h3>
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
                            <svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M18 2H6a2 2 0 0 0-2 2v16a1 1 0 0 0 1.447.894L12 18l6.553 2.894A1 1 0 0 0 20 20V4a2 2 0 0 0-2-2z"/></svg>
                        </span>
                        <strong id="total-courses" class="stat-number courses-color"><?php echo $totalCourses ?? '--'; ?></strong>
                    </span>
                    <span>Total Courses</span>
                </li>
                <li>
                    <span class="stat-left">
                        <span class="stat-icon icon-parents" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M16 11c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM8 11c1.657 0 3-1.343 3-3S9.657 5 8 5 5 6.343 5 8s1.343 3 3 3zM8 13c-2.67 0-8 1.337-8 4v2h16v-2c0-2.663-5.33-4-8-4z"/></svg>
                        </span>
                        <strong id="total-parents" class="stat-number parents-color"><?php echo $totalParents ?? '--'; ?></strong>
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
            <canvas id="residentChart"></canvas>
        </div>
    </div>
</main>
<!-- MAIN -->

<!-- Chart.js for Attendance -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
window.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('residentChart');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($attendanceDates ?: []); ?>,
                datasets: [{
                    label: 'Attendance per Day',
                    data: <?php echo json_encode($attendanceCounts ?: []); ?>,
                    borderColor: '#127106',
                    backgroundColor: 'rgba(60,145,230,0.2)',
                    fill: true,
                    tension: 0.2
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                scales: {
                    x: { title: { display: true, text: 'Date' } },
                    y: { title: { display: true, text: 'Count' }, beginAtZero: true }
                }
            }
        });
    }
});
</script>

<?php include 'stms_footer.php'; ?>
