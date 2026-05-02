<?php 
include 'header.php'; 


$statusMsg = "";

/* ================= SAVE MULTIPLE SUBJECTS ================= */
if(isset($_POST['save'])){
    $count = count($_POST['subjectName']); // number of rows
    $success = 0;
    $errors = [];

    for($i=0; $i<$count; $i++){
        $subjectName = mysqli_real_escape_string($conn, $_POST['subjectName'][$i]);
        $courseId    = $_POST['courseId'][$i];
        $teacherId   = $_POST['teacherId'][$i];
        $roomId      = $_POST['roomId'][$i] ?: NULL;
        $semester    = $_POST['semester'][$i];
        $year        = $_POST['year'][$i];
        $scheduleTime= $_POST['scheduleTime'][$i];
        $scheduleDays= isset($_POST['scheduleDays'][$i]) ? implode(',', $_POST['scheduleDays'][$i]) : '';

        // Check duplicate
        $query = mysqli_query($conn, "SELECT * FROM subjects WHERE subject_name='$subjectName' AND course_id='$courseId'");
        if(mysqli_num_rows($query) > 0){
            $errors[] = $subjectName;
            continue;
        }

        $insert = mysqli_query($conn, "INSERT INTO subjects(subject_name, course_id, teacher_id, room_id, semester, year, schedule_time, schedule_days)
                                       VALUES('$subjectName', '$courseId', '$teacherId', ".($roomId?:'NULL').", '$semester', '$year', '$scheduleTime', '$scheduleDays')");
        if($insert) $success++;
    }

    $statusMsg = "<div class='alert alert-success' style='margin-right:700px;'>$success subject(s) added successfully!</div>";
    if(!empty($errors)){
        $statusMsg .= "<div class='alert alert-danger' style='margin-right:700px;'>Skipped duplicates: ".implode(', ', $errors)."</div>";
    }
}

/* ================= EDIT ================= */
if (isset($_GET['id'], $_GET['action']) && $_GET['action']=="edit") {
    $id = $_GET['id'];
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM subjects WHERE subject_id='$id'"));

    if(isset($_POST['update'])){
        $subjectName = mysqli_real_escape_string($conn, $_POST['subjectName']);
        $courseId = $_POST['courseId'];
        $teacherId = $_POST['teacherId'];
        $roomId = $_POST['roomId'] ?: NULL;
        $semester = $_POST['semester'];
        $year = $_POST['year'];
        $scheduleTime = $_POST['scheduleTime'];
        $scheduleDays = isset($_POST['scheduleDays']) ? implode(',', $_POST['scheduleDays']) : '';

        $update = mysqli_query($conn, "UPDATE subjects SET
                                       subject_name='$subjectName',
                                       course_id='$courseId',
                                       teacher_id='$teacherId',
                                       room_id=".($roomId?:'NULL').",
                                       semester='$semester',
                                       year='$year',
                                       schedule_time='$scheduleTime',
                                       schedule_days='$scheduleDays'
                                       WHERE subject_id='$id'");
        if($update){
            echo "<script>window.location='assign_course_subject.php';</script>";
        } else {
            $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>An error Occurred!</div>";
        }
    }
}

/* ================= DELETE ================= */
if (isset($_GET['id'], $_GET['action']) && $_GET['action']=="delete") {
    $id = $_GET['id'];
    $delete = mysqli_query($conn, "DELETE FROM subjects WHERE subject_id='$id'");
    if($delete){
        echo "<script>window.location='assign_course_subject.php';</script>";
    } else {
        $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>An error Occurred!</div>";
    }
}

/* ================= FILTER & PAGINATION ================= */
$search = $_GET['search'] ?? '';
$filterCourse = $_GET['filterCourse'] ?? '';
$filterYear = $_GET['filterYear'] ?? '';
$filterSemester = $_GET['filterSemester'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 10;
$offset = ($page-1)*$limit;

// Dropdown arrays
$coursesArr = [];
$teachersArr = [];
$roomsArr = [];

$coursesRes = mysqli_query($conn, "SELECT * FROM courses ORDER BY course_name ASC");
while($c = mysqli_fetch_assoc($coursesRes)) $coursesArr[] = $c;

$teachersRes = mysqli_query($conn, "
    SELECT t.teacher_id, u.first_name, u.last_name
    FROM teachers t
    INNER JOIN users u ON t.user_id = u.user_id
    WHERE u.role='teacher'
    ORDER BY u.first_name ASC
");
while($t = mysqli_fetch_assoc($teachersRes)) $teachersArr[] = $t;

$roomsRes = mysqli_query($conn, "SELECT * FROM rooms ORDER BY room_name ASC");
while($r = mysqli_fetch_assoc($roomsRes)) $roomsArr[] = $r;

$years = ['1st Year','2nd Year','3rd Year','4th Year'];
$semesters = ['1st Sem','2nd Sem','3rd Sem','Summer'];

// Build WHERE
$where = "WHERE 1";

if($search) {
    $search = mysqli_real_escape_string($conn, $search);
    $where .= " AND s.subject_name LIKE '%$search%'";
}

if($filterCourse) {
    $filterCourse = mysqli_real_escape_string($conn, $filterCourse);
    $where .= " AND s.course_id='$filterCourse'";
}

if($filterYear) {
    $filterYear = mysqli_real_escape_string($conn, $filterYear);
    $where .= " AND s.year='$filterYear'";
}

if($filterSemester) {
    $filterSemester = mysqli_real_escape_string($conn, $filterSemester);
    $where .= " AND s.semester='$filterSemester'";
}

// Total rows for pagination
$totalQuery = mysqli_query($conn, "SELECT COUNT(*) as total 
                                   FROM subjects s
                                   LEFT JOIN courses c ON s.course_id=c.course_id
                                   LEFT JOIN rooms r ON s.room_id=r.room_id
                                   LEFT JOIN teachers tt ON s.teacher_id=tt.teacher_id
                                   LEFT JOIN users u ON tt.user_id=u.user_id
                                   $where");
$total = mysqli_fetch_assoc($totalQuery)['total'];
$totalPages = ceil($total/$limit);

// Fetch data
$data = mysqli_query($conn, "
    SELECT s.*, c.course_name, r.room_name, CONCAT(u.first_name,' ',u.last_name) as teacher_name,
           (SELECT COUNT(*) FROM student_subjects ss WHERE ss.subject_id=s.subject_id) as assigned_count
    FROM subjects s
    LEFT JOIN courses c ON s.course_id=c.course_id
    LEFT JOIN rooms r ON s.room_id=r.room_id
    LEFT JOIN teachers tt ON s.teacher_id=tt.teacher_id
    LEFT JOIN users u ON tt.user_id=u.user_id
    $where
    ORDER BY s.subject_name ASC
    LIMIT $offset, $limit
");
?>

<main>
<div class="head-title">
    <div class="left">
        <h1>Assign Course Subject</h1>
        <ul class="breadcrumb">
            <li><a href="#">Home</a></li>
            <li><i class='bx bx-chevron-right'></i></li>
            <li><a class="active">Management Information</a></li>
        </ul>
    </div>
</div>

<div class="table-data">
<div class="order">
<div class="head"><h3><?= isset($row) ? "Update Assign Course Subject" : "Create Assign Course Subject" ?></h3></div>

<form method="POST" class="form-class" id="subjectForm">
    <?= $statusMsg ?>
    <div id="subjectRows">
        <div class="subject-row">
            <input type="text" name="subjectName[]" class="search-input" placeholder="Subject Name" required>

            <select name="courseId[]" class="search-input" required>
                <option value="">Select Course</option>
                <?php foreach($coursesArr as $c){ ?>
                    <option value="<?= $c['course_id'] ?>"><?= $c['course_name'] ?></option>
                <?php } ?>
            </select>

            <select name="teacherId[]" class="search-input" required>
                <option value="">Select Teacher</option>
                <?php foreach($teachersArr as $t){ ?>
                    <option value="<?= $t['teacher_id'] ?>"><?= $t['first_name'].' '.$t['last_name'] ?></option>
                <?php } ?>
            </select>

            <select name="roomId[]" class="search-input">
                <option value="">Select Room</option>
                <?php foreach($roomsArr as $r){ ?>
                    <option value="<?= $r['room_id'] ?>"><?= $r['room_name'] ?></option>
                <?php } ?>
            </select>

            <select name="semester[]" class="search-input" required>
                <option value="">Select Semester</option>
                <?php foreach($semesters as $s){ ?>
                    <option value="<?= $s ?>"><?= $s ?></option>
                <?php } ?>
            </select>

            <select name="year[]" class="search-input" required>
                <option value="">Select Year</option>
                <?php foreach($years as $y){ ?>
                    <option value="<?= $y ?>"><?= $y ?></option>
                <?php } ?>
            </select>

            <input type="text" name="scheduleTime[]" class="search-input" placeholder="Schedule Time">

            <div class="days-wrap compact">
                <?php $days=['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                foreach($days as $d){ ?>
                    <label class='day-item'>
                        <input type='checkbox' name='scheduleDays[0][]' value='<?= $d ?>'>
                        <span><?= $d ?></span>
                    </label>
                <?php } ?>
            </div>

            <button type="button" class="btn small danger removeRow">Remove</button>
          
        </div>
    </div>

    <button type="button" class="btn small primary" id="addRow">Add Another Subject</button>
    <div class="form-actions">
        <?php if(isset($row)){ ?>
            <button name="update" class="btn primary">Update</button>
            <button type="button" id="cancelBtn" class="btn">Cancel</button>
        <?php } else { ?>
            <button name="save" class="btn primary">Save All</button>
        <?php } ?>
    </div>
</form>
</div>
</div>

<!-- ================= FILTER + TABLE ================= -->
<div class="table-data">
<div class="order">
<div class="head flex-between"><h3>All Subjects</h3></div>

<div class="row-controls">
<input type="search" id="searchInput" class="search-input" placeholder="Search subject..." value="<?= htmlspecialchars($search) ?>">
<select id="courseFilter" class="search-input">
    <option value="">All Courses</option>
    <?php foreach($coursesArr as $c){ ?>
        <option value="<?= $c['course_id'] ?>" <?= $filterCourse==$c['course_id']?'selected':'' ?>>
            <?= $c['course_name'] ?>
        </option>
    <?php } ?>
</select>

<select id="yearFilter" class="search-input">
    <option value="">All Years</option>
    <?php foreach($years as $y){ ?>
    <option value="<?= $y ?>" <?= $filterYear==$y?'selected':'' ?>><?= $y ?></option>
    <?php } ?>
</select>

<select id="semFilter" class="search-input">
    <option value="">All Semester</option>
    <?php foreach($semesters as $s){ ?>
    <option value="<?= $s ?>" <?= $filterSemester==$s?'selected':'' ?>><?= $s ?></option>
    <?php } ?>
</select>

<select id="limitFilter" class="search-input">
    <?php foreach([5,10,25,50] as $l){ $sel=$limit==$l?'selected':''; ?>
    <option value="<?= $l ?>" <?= $sel ?>><?= $l ?></option>
    <?php } ?>
</select>
</div>

<table>
<thead>
<tr>
<th>Subject</th>
<th>Room</th>
<th>Semester</th>
<th>Year</th>
<th>Schedule Time</th>
<th>Schedule Days</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php if(mysqli_num_rows($data)>0){
while($r=mysqli_fetch_assoc($data)){
$status=$r['assigned_count']?'Assigned':'Unassigned';
?>
<tr>
<td><?= $r['subject_name'] ?></td>
<td><?= $r['room_name']??'' ?></td>
<td><?= $r['semester'] ?></td>
<td><?= $r['year'] ?></td>
<td><?= $r['schedule_time'] ?></td>
<td><button type="button" class="btn small primary" data-days="<?= htmlspecialchars($r['schedule_days'],ENT_QUOTES) ?>" onclick="openDaysModal(this)">View</button></td>
<td>
<a href="?action=edit&id=<?= $r['subject_id'] ?>" class="btn small primary">Edit</a>
<a href="?action=delete&id=<?= $r['subject_id'] ?>" class="btn small danger" onclick="return confirm('Delete subject?')">Delete</a>
</td>
</tr>
<?php }} else { ?>
<tr><td colspan="10">No records found</td></tr>
<?php } ?>
</tbody>
</table>

<!-- PAGINATION -->
<div class="mt-15">
<?php if($page>1){ ?><a href="?page=<?= $page-1 ?>" class="btn small">Prev</a><?php } ?>
<?php for($i=1;$i<=$totalPages;$i++){ ?>
<a href="?page=<?= $i ?>" class="btn small <?= $i==$page?'primary':'' ?>"><?= $i ?></a>
<?php } ?>
<?php if($page<$totalPages){ ?><a href="?page=<?= $page+1 ?>" class="btn small">Next</a><?php } ?>
</div>
</div>
</div>

</main>

<!-- Modal for showing scheduled days -->
<style>
#daysModal .modal-content { max-width:420px; width:auto; padding:30px; border-radius:8px; background:var(--light); color:var(--dark); box-shadow:0 8px 20px rgba(0,0,0,0.12); }
#daysModal .close, #daysModal .modal-close { float:right; font-size:22px; font-weight:700; border:none; background:transparent; cursor:pointer; color:var(--dark); }
</style>

<div id="daysModal" class="modal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="daysTitle">
        <button class="close" aria-label="Close" onclick="closeDaysModal()">&times;</button>
        <h3 id="daysTitle">Scheduled Days</h3>
        <p id="modalDays">&nbsp;</p>
    </div>
</div>

<script>
const searchInput=document.getElementById('searchInput');
const courseFilter=document.getElementById('courseFilter');
const yearFilter=document.getElementById('yearFilter');
const semFilter=document.getElementById('semFilter');
const limitFilter=document.getElementById('limitFilter');

let timer;
function reload(){
    const p=new URLSearchParams();
    if(searchInput.value)p.set('search',searchInput.value);
    if(courseFilter.value)p.set('filterCourse',courseFilter.value);
    if(yearFilter.value)p.set('filterYear',yearFilter.value);
    if(semFilter.value)p.set('filterSemester',semFilter.value);
    p.set('limit',limitFilter.value);
    p.set('page',1);
    window.location.search=p.toString();
}
searchInput.addEventListener('keyup',()=>{clearTimeout(timer);timer=setTimeout(reload,500);});
[courseFilter,yearFilter,semFilter,limitFilter].forEach(el=>el.addEventListener('change',reload));

function openDaysModal(el){
    var days = el.getAttribute('data-days') || '';
    document.getElementById('modalDays').textContent = days? days : 'No scheduled days';
    var modal = document.getElementById('daysModal');
    modal.setAttribute('aria-hidden','false');
}
function closeDaysModal(){
    var modal = document.getElementById('daysModal');
    modal.setAttribute('aria-hidden','true');
}
document.getElementById('daysModal').addEventListener('click', function(e){
    if(e.target===this) closeDaysModal();
});

(function(){
    var cancelBtn = document.getElementById('cancelBtn');
    if(!cancelBtn) return;
    cancelBtn.addEventListener('click', function(){
        if(!confirm('Cancel editing? Unsaved changes will be lost.')) return;
        var form = document.getElementById('subjectForm');
        if(form) form.reset();
        window.location.href = 'assign_course_subject.php';
    });
})();

// ================= DYNAMIC MULTIPLE ROWS =================
const addRowBtn = document.getElementById('addRow');
const subjectRows = document.getElementById('subjectRows');

addRowBtn.addEventListener('click', function() {
    const index = subjectRows.querySelectorAll('.subject-row').length;
    const newRow = subjectRows.querySelector('.subject-row').cloneNode(true);

    // Reset values
    newRow.querySelectorAll('input, select').forEach(el => {
        if(el.type === 'checkbox') el.checked = false;
        else el.value = '';
    });

    // Update checkbox names to unique index
    newRow.querySelectorAll('.days-wrap input[type=checkbox]').forEach(chk => {
        chk.name = `scheduleDays[${index}][]`;
    });

    subjectRows.appendChild(newRow);

    // Remove button
    newRow.querySelector('.removeRow').addEventListener('click', function() {
        if(confirm('Remove this row?')) newRow.remove();
    });
});

// Remove first row button
subjectRows.querySelectorAll('.removeRow').forEach(btn=>{
    btn.addEventListener('click', function() {
        if(confirm('Remove this row?')) btn.closest('.subject-row').remove();
    });
});
</script>

<?php include 'stms_footer.php'; ?>
