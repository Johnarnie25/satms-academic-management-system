<?php 
include 'header.php';

// ================= DEFAULT VALUES =================
$totalSubjects = 0;
$totalRooms = 0;
$totalParents = 0;
$studentSubjects = [];
$examDates = []; // array to store exams grouped by date

$user_id = $_SESSION['user_id'] ?? 0;

// ================= GET STUDENT ID =================
$studentQuery = $conn->prepare("SELECT student_id, parent_id FROM students WHERE user_id = ?");
$studentQuery->bind_param("i", $user_id);
$studentQuery->execute();
$studentResult = $studentQuery->get_result();

if($studentResult->num_rows > 0){
    $studentRow = $studentResult->fetch_assoc();
    $student_id = $studentRow['student_id'];
    $parent_id = $studentRow['parent_id'];

    // ================= STUDENT SUBJECTS =================
    $qSubjects = $conn->prepare("
        SELECT s.subject_id, s.subject_name, s.room_id
        FROM student_subjects ss
        JOIN subjects s ON ss.subject_id = s.subject_id
        WHERE ss.student_id = ?
    ");
    $qSubjects->bind_param("i", $student_id);
    $qSubjects->execute();
    $resultSubjects = $qSubjects->get_result();

    while($row = $resultSubjects->fetch_assoc()){
        $studentSubjects[] = $row;
    }

    $totalSubjects = count($studentSubjects);

    // ================= TOTAL ROOMS =================
    $totalRooms = 0;
    foreach($studentSubjects as $sub){
        if($sub['room_id'] !== null) $totalRooms++;
    }

    // ================= TOTAL PARENTS =================
    $totalParents = $parent_id ? 1 : 0;

    // ================= EXAMS FOR CALENDAR =================
    $qExams = $conn->prepare("
        SELECT e.exam_id, e.exam_date, e.exam_type, s.subject_name
        FROM exams e
        JOIN subjects s ON e.subject_id = s.subject_id
        JOIN student_subjects ss ON ss.subject_id = s.subject_id
        WHERE ss.student_id = ?
    ");
    $qExams->bind_param("i", $student_id);
    $qExams->execute();
    $resultExams = $qExams->get_result();

    while($row = $resultExams->fetch_assoc()){
        $examDates[$row['exam_date']][] = [
            'exam_id' => $row['exam_id'],
            'subject' => $row['subject_name'],
            'type' => $row['exam_type']
        ];
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
            <i class='bx bxs-grid'></i>
            <span class="text">
                <h3><?php echo $totalRooms; ?></h3>
                <p>Assigned Rooms</p>
            </span>
        </li>
      
        <li>
            <i class='bx bxs-user-check'></i>
            <span class="text">
                <h3><?php echo $totalParents; ?></h3>
                <p>Parents</p>
            </span>
        </li>
    </ul>

    <!-- Exam Calendar -->
    <div class="calendar-container mt-4">
        <div id="examCalendar"></div>
    </div>
</main>

<!-- Modal (hidden by default) -->
<!-- Custom Modal (hidden by default) -->
<div id="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);justify-content:center;align-items:center;z-index:9999;">
  <div style="background:#fff;padding:20px;border-radius:10px;width:400px;max-height:80%;overflow-y:auto;position:relative;">
    <span id="closeModal" style="position:absolute;top:10px;right:15px;cursor:pointer;font-size:20px;">&times;</span>
    <h3>Exams on this Date</h3>
    <ul id="modalList" style="list-style:none;padding-left:0;"></ul>
  </div>
</div>
<!-- FullCalendar CSS & JS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

<style>
#examCalendar {
    max-width: 1200px;
    margin: 0 auto;
    font-size: 1.2em;
}

.fc-daygrid-day-number {
    font-weight: bold;
}

/* Highlight the day with low opacity green */
.fc-daygrid-day.fc-day-has-events {
    background-color: rgba(15, 87, 5, 0.1); /* green with low opacity */
}

/* Style the View button inside calendar */
.fc .fc-event {
    background-color: #0f5705 !important;
    color: #fff !important;
    border: none !important;
    cursor: pointer;
    text-align: center;
    font-size: 0.8em;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const examDates = <?php echo json_encode($examDates); ?>;
    const calendarEl = document.getElementById('examCalendar');
    const modal = document.getElementById('modal');
    const modalList = document.getElementById('modalList');
    const closeModal = document.getElementById('closeModal');

    // Convert examDates to FullCalendar events (one per date)
    const events = Object.keys(examDates).map(date => ({
        title: 'View',
        start: date,
        allDay: true,
        extendedProps: { exams: examDates[date] }
    }));

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
        events: events,
        eventContent: function(arg) {
            const btn = document.createElement('button');
            btn.className = 'btn btn-sm view-btn';
            btn.innerText = 'View';
            btn.style.backgroundColor = '#0f5705';
            btn.style.color = '#fff';
            btn.style.width = '100%';

            btn.onclick = function(e) {
                e.stopPropagation(); // prevent default calendar click
                const exams = arg.event.extendedProps.exams;
                modalList.innerHTML = '';
                exams.forEach(ex => {
                    const li = document.createElement('li');
                    li.innerHTML = `<strong>${ex.subject}</strong> (${ex.type})`;
                    li.style.padding = '5px 0';
                    modalList.appendChild(li);
                });
                modal.style.display = 'flex';
            };
            return { domNodes: [btn] };
        },
        dayMaxEvents: true,
        height: 'auto'
    });

    calendar.render();

    // Close modal
    closeModal.addEventListener('click', () => modal.style.display = 'none');
    window.addEventListener('click', e => { if(e.target === modal) modal.style.display = 'none'; });
    document.addEventListener('keydown', e => { if(e.key === "Escape") modal.style.display = 'none'; });
});
</script>


<?php include 'stms_footer.php'; ?>
