<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'header.php';

$statusMsg = "";

// ================= GET TEACHER ID =================
$user_id = $_SESSION['user_id'] ?? 0;
$teacher_id = 0;

$resT = mysqli_query($conn,"SELECT teacher_id FROM teachers WHERE user_id='$user_id' LIMIT 1");
if($resT && mysqli_num_rows($resT)>0){
    $teacher_id = mysqli_fetch_assoc($resT)['teacher_id'];
}

if($teacher_id == 0){
    die("<div class='alert alert-danger'>Access denied. Teacher only.</div>");
}

// ================= FETCH TEACHER SUBJECTS =================
$subjects = [];
$q = mysqli_query($conn,"
SELECT subject_id,subject_name,year,semester
FROM subjects
WHERE teacher_id='$teacher_id'
ORDER BY subject_name ASC
");

while($row=mysqli_fetch_assoc($q)){
$subjects[$row['subject_id']] = $row;
}

// ================= FILTERS =================
$subject_id = $_GET['subject_id'] ?? '';
$date = $_GET['attendance_date'] ?? date("Y-m-d");

$search = $_GET['search'] ?? '';
$limit = intval($_GET['limit'] ?? 10);
$page = intval($_GET['page'] ?? 1);

if($limit<=0) $limit=10;
if($page<=0) $page=1;

$offset = ($page-1)*$limit;

// ================= SAVE ATTENDANCE =================
if(isset($_POST['take_attendance'])){

$subject_id = intval($_POST['subject_id']);
$date = $_POST['attendance_date'];

$presentStudents = $_POST['present'] ?? [];

$studentsQuery = mysqli_query($conn,"
SELECT ss.student_id
FROM student_subjects ss
WHERE ss.subject_id='$subject_id'
");

while($s = mysqli_fetch_assoc($studentsQuery)){

$student_id = $s['student_id'];

$status = in_array($student_id,$presentStudents) ? "Present" : "Absent";

mysqli_query($conn,"
INSERT INTO attendance(student_id,subject_id,attendance_date,status)
VALUES('$student_id','$subject_id','$date','$status')
ON DUPLICATE KEY UPDATE status='$status'
");

}

$statusMsg = "<div class='alert alert-success'>Attendance saved successfully!</div>";
}

// ================= FETCH STUDENTS =================
$students = [];

if(!empty($subject_id)){

$where = "WHERE ss.subject_id='$subject_id'";

if(!empty($search)){
$searchEsc = mysqli_real_escape_string($conn,$search);
$where .= " AND (u.first_name LIKE '%$searchEsc%' 
            OR u.last_name LIKE '%$searchEsc%')";
}

// ================= COUNT =================
$countQuery = mysqli_query($conn,"
SELECT COUNT(*) as total
FROM student_subjects ss
LEFT JOIN students st ON ss.student_id = st.student_id
LEFT JOIN users u ON st.user_id = u.user_id
$where
");

$totalRows = mysqli_fetch_assoc($countQuery)['total'];
$totalPages = max(1,ceil($totalRows/$limit));

// ================= DATA =================
$query = mysqli_query($conn,"
SELECT 
st.student_id,
u.first_name,
u.last_name,
st.year_level,
c.course_name,
r.room_name,
s.subject_name,
s.semester,
s.year
FROM student_subjects ss
LEFT JOIN students st ON ss.student_id = st.student_id
LEFT JOIN users u ON st.user_id = u.user_id
LEFT JOIN subjects s ON ss.subject_id = s.subject_id
LEFT JOIN courses c ON st.course_id = c.course_id
LEFT JOIN rooms r ON s.room_id = r.room_id
$where
ORDER BY u.last_name ASC
LIMIT $offset,$limit
");

while($row=mysqli_fetch_assoc($query)){
$students[]=$row;
}

}

?>

<main>

<div class="head-title">
<div class="left">
<h1>Take Attendance</h1>

<ul class="breadcrumb">
<li><a href="#">Home</a></li>
<li><i class='bx bx-chevron-right'></i></li>
<li><a class="active">Attendance</a></li>
</ul>

</div>
</div>

<!-- SUBJECT SELECT -->
<div class="table-data">
<div class="order">

<div class="head">
<h3>Select Subject</h3>
</div>

<?php if(!empty($statusMsg)) echo $statusMsg; ?>

<form method="GET" class="form-class">

<select name="subject_id" required>

<option value="">Select Subject</option>

<?php foreach($subjects as $id=>$sub){ ?>

<option value="<?php echo $id; ?>"
<?php if($subject_id==$id) echo "selected"; ?>>

<?php echo $sub['subject_name']." (Year ".$sub['year']." - ".$sub['semester'].")"; ?>

</option>

<?php } ?>

</select>

<input type="date" name="attendance_date" value="<?php echo $date; ?>" required>

<button class="btn primary">Load Students</button>

</form>

</div>
</div>


<?php if(!empty($subject_id)){ ?>

<div class="table-data">

<div class="order">
<div class="head">
<h3>Student List</h3>
</div>
<!-- SEARCH CONTROLS -->
<div class="row-controls">

<input type="search" id="searchInput" class="search-input"
placeholder="Search student..."
value="<?php echo htmlspecialchars($search); ?>">

<select id="limitSelect" class="search-input">
<?php foreach([5,10,20,50] as $opt){ ?>
<option value="<?php echo $opt; ?>"
<?php if($opt==$limit) echo "selected"; ?>>
<?php echo $opt; ?>
</option>
<?php } ?>
</select>

</div>

<form method="POST">

<input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
<input type="hidden" name="attendance_date" value="<?php echo $date; ?>">



<table>

<thead>

<tr>
<th>#</th>
<th>Student Name</th>
<th>Course</th>
<th>Year</th>
<th>Room</th>
<th>Subject</th>
<th>Semester</th>
<th>Present</th>
</tr>

</thead>

<tbody>

<?php 
$sn=$offset+1;

if(!empty($students)){
foreach($students as $row){
?>

<tr>

<td><?php echo $sn++; ?></td>

<td>
<?php echo $row['last_name'].", ".$row['first_name']; ?>
</td>

<td><?php echo $row['course_name']; ?></td>
<td><?php echo $row['year_level']; ?></td>
<td><?php echo $row['room_name']; ?></td>
<td><?php echo $row['subject_name']; ?></td>
<td><?php echo $row['semester']; ?></td>

<td>
<input type="checkbox" name="present[]" value="<?php echo $row['student_id']; ?>">
</td>

</tr>

<?php } } else { ?>

<tr>
<td colspan="8" style="text-align:center;">No students found.</td>
</tr>

<?php } ?>

</tbody>

</table>

<div class="mt-15">
<button type="submit" name="take_attendance" class="btn primary">
Take Attendance
</button>
</div>

</form>

<!-- PAGINATION -->
<div class="mt-15">

<?php if($page>1){ ?>

<a href="?subject_id=<?php echo $subject_id; ?>
&attendance_date=<?php echo $date; ?>
&search=<?php echo urlencode($search); ?>
&limit=<?php echo $limit; ?>
&page=<?php echo $page-1; ?>" class="btn small">Prev</a>

<?php } ?>

<?php for($i=1;$i<=$totalPages;$i++){ ?>

<a href="?subject_id=<?php echo $subject_id; ?>
&attendance_date=<?php echo $date; ?>
&search=<?php echo urlencode($search); ?>
&limit=<?php echo $limit; ?>
&page=<?php echo $i; ?>"
class="btn small <?php if($i==$page) echo 'primary'; ?>">
<?php echo $i; ?>
</a>

<?php } ?>

<?php if($page<$totalPages){ ?>

<a href="?subject_id=<?php echo $subject_id; ?>
&attendance_date=<?php echo $date; ?>
&search=<?php echo urlencode($search); ?>
&limit=<?php echo $limit; ?>
&page=<?php echo $page+1; ?>" class="btn small">Next</a>

<?php } ?>

</div>

</div>
</div>

<?php } ?>

</main>

<script>

const searchInput = document.getElementById('searchInput');
const limitSelect = document.getElementById('limitSelect');

function applyFilters(){

const subject = "<?php echo $subject_id; ?>";
const date = "<?php echo $date; ?>";

window.location =
"?subject_id="+subject+
"&attendance_date="+date+
"&search="+encodeURIComponent(searchInput.value)+
"&limit="+limitSelect.value;

}

searchInput?.addEventListener('keyup',applyFilters);
limitSelect?.addEventListener('change',applyFilters);

</script>

<?php include 'stms_footer.php'; ?>
