<?php 
include 'header.php';

// ================= DEFAULT VALUES =================
$totalSubjects = 0;
$totalRooms = 0;
$totalParents = 1;
$studentSubjects = [];
$examDates = [];
$students = [];

$user_id = $_SESSION['user_id'] ?? 0;
$selectedStudent = $_GET['student_id'] ?? 0;


// ================= GET PARENT ID =================
$parentQuery = $conn->prepare("SELECT parent_id FROM parents WHERE user_id = ?");
$parentQuery->bind_param("i",$user_id);
$parentQuery->execute();
$parentResult = $parentQuery->get_result();

if($parentResult->num_rows > 0){

    $parentRow = $parentResult->fetch_assoc();
    $parent_id = $parentRow['parent_id'];

    // ================= GET STUDENTS OF THIS PARENT =================
    $qStudents = $conn->prepare("
        SELECT s.student_id, u.first_name, u.last_name
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        WHERE s.parent_id = ?
    ");
    $qStudents->bind_param("i",$parent_id);
    $qStudents->execute();
    $resultStudents = $qStudents->get_result();

    while($row = $resultStudents->fetch_assoc()){
        $students[] = $row;
    }

    // default student
    if(!$selectedStudent && count($students) > 0){
        $selectedStudent = $students[0]['student_id'];
    }

    // ================= STUDENT SUBJECTS =================
    if($selectedStudent){

        $qSubjects = $conn->prepare("
            SELECT s.subject_id, s.subject_name, s.room_id
            FROM student_subjects ss
            JOIN subjects s ON ss.subject_id = s.subject_id
            WHERE ss.student_id = ?
        ");
        $qSubjects->bind_param("i",$selectedStudent);
        $qSubjects->execute();
        $resultSubjects = $qSubjects->get_result();

        while($row = $resultSubjects->fetch_assoc()){
            $studentSubjects[] = $row;
        }

        $totalSubjects = count($studentSubjects);

        // ================= TOTAL ROOMS =================
        foreach($studentSubjects as $sub){
            if($sub['room_id'] !== null){
                $totalRooms++;
            }
        }

        // ================= EXAMS FOR CALENDAR =================
        $qExams = $conn->prepare("
            SELECT e.exam_id, e.exam_date, e.exam_type, s.subject_name
            FROM exams e
            JOIN subjects s ON e.subject_id = s.subject_id
            JOIN student_subjects ss ON ss.subject_id = s.subject_id
            WHERE ss.student_id = ?
        ");
        $qExams->bind_param("i",$selectedStudent);
        $qExams->execute();
        $resultExams = $qExams->get_result();

        while($row = $resultExams->fetch_assoc()){
            $examDates[$row['exam_date']][] = [
                'exam_id'=>$row['exam_id'],
                'subject'=>$row['subject_name'],
                'type'=>$row['exam_type']
            ];
        }
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


<!-- STUDENT DROPDOWN -->
<div style="margin-bottom:20px;">
<form method="GET">

<select name="student_id" onchange="this.form.submit()" style="padding:8px 20px;border-radius:5px;">

<?php foreach($students as $student): ?>

<option value="<?php echo $student['student_id']; ?>"
<?php if($selectedStudent == $student['student_id']) echo "selected"; ?>>

<?php echo $student['first_name']." ".$student['last_name']; ?>

</option>

<?php endforeach; ?>

</select>

</form>
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



<!-- MODAL -->
<div id="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);justify-content:center;align-items:center;z-index:9999;">

<div style="background:#fff;padding:20px;border-radius:10px;width:400px;max-height:80%;overflow-y:auto;position:relative;">

<span id="closeModal" style="position:absolute;top:10px;right:15px;cursor:pointer;font-size:20px;">&times;</span>

<h3>Exams on this Date</h3>

<ul id="modalList" style="list-style:none;padding-left:0;"></ul>

</div>

</div>



<!-- FULLCALENDAR -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>



<style>

#examCalendar{
max-width:1200px;
margin:0 auto;
font-size:1.2em;
}

.fc-daygrid-day-number{
font-weight:bold;
}

.fc-daygrid-day.fc-day-has-events{
background-color:rgba(15,87,5,0.1);
}

.fc .fc-event{
background-color:#0f5705 !important;
color:#fff !important;
border:none !important;
cursor:pointer;
text-align:center;
font-size:0.8em;
}

</style>



<script>

document.addEventListener('DOMContentLoaded',function(){

const examDates = <?php echo json_encode($examDates); ?>;

const calendarEl = document.getElementById('examCalendar');

const modal = document.getElementById('modal');

const modalList = document.getElementById('modalList');

const closeModal = document.getElementById('closeModal');


// convert exams to events
const events = Object.keys(examDates).map(date=>({

title:'View',
start:date,
allDay:true,
extendedProps:{exams:examDates[date]}

}));


const calendar = new FullCalendar.Calendar(calendarEl,{

initialView:'dayGridMonth',

headerToolbar:{
left:'prev,next today',
center:'title',
right:''
},

events:events,

eventContent:function(arg){

const btn = document.createElement('button');

btn.innerText='View';

btn.style.backgroundColor='#0f5705';

btn.style.color='#fff';

btn.style.width='100%';

btn.onclick=function(e){

e.stopPropagation();

const exams = arg.event.extendedProps.exams;

modalList.innerHTML='';

exams.forEach(ex=>{

const li = document.createElement('li');

li.innerHTML=`<strong>${ex.subject}</strong> (${ex.type})`;

li.style.padding='5px 0';

modalList.appendChild(li);

});

modal.style.display='flex';

};

return {domNodes:[btn]};

},

height:'auto'

});

calendar.render();


// close modal
closeModal.addEventListener('click',()=>modal.style.display='none');

window.addEventListener('click',e=>{
if(e.target===modal){
modal.style.display='none';
}
});

document.addEventListener('keydown',e=>{
if(e.key === "Escape"){
modal.style.display='none';
}
});

});

</script>



<?php include 'stms_footer.php'; ?>
