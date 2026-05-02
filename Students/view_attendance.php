<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'header.php';

$user_id = $_SESSION['user_id'] ?? 0;

/* ================= GET STUDENT ID ================= */

$student_id = 0;

$res = mysqli_query($conn,"
SELECT student_id
FROM students
WHERE user_id='$user_id'
LIMIT 1
");

if($res && mysqli_num_rows($res)>0){
$student_id = mysqli_fetch_assoc($res)['student_id'];
}

if($student_id == 0){
die("<div class='alert alert-danger'>Student account not found.</div>");
}

/* ================= FILTERS ================= */

$subject_id = $_GET['subject_id'] ?? '';
$search = $_GET['search'] ?? '';

$limit = intval($_GET['limit'] ?? 10);
$page = intval($_GET['page'] ?? 1);

if($limit<=0) $limit=10;
if($page<=0) $page=1;

$offset = ($page-1)*$limit;

/* ================= SUBJECT LIST ================= */

$subjects=[];

$q = mysqli_query($conn,"
SELECT s.subject_id,s.subject_name,s.year,s.semester
FROM student_subjects ss
LEFT JOIN subjects s ON ss.subject_id=s.subject_id
WHERE ss.student_id='$student_id'
ORDER BY s.subject_name ASC
");

while($row=mysqli_fetch_assoc($q)){
$subjects[]=$row;
}

/* ================= WHERE ================= */

$where="WHERE a.student_id='$student_id'";

if(!empty($subject_id)){
$where.=" AND a.subject_id='".intval($subject_id)."'";
}

if(!empty($search)){
$searchEsc=mysqli_real_escape_string($conn,$search);
$where.=" AND a.attendance_date LIKE '%$searchEsc%'";
}

/* ================= COUNT ================= */

$countQuery=mysqli_query($conn,"
SELECT COUNT(*) as total
FROM attendance a
$where
");

$totalRows=mysqli_fetch_assoc($countQuery)['total'];
$totalPages=max(1,ceil($totalRows/$limit));

/* ================= DATA ================= */

$attendance=[];

$query=mysqli_query($conn,"
SELECT
a.attendance_date,
a.status,
s.subject_name,
s.semester,
r.room_name
FROM attendance a
LEFT JOIN subjects s ON a.subject_id=s.subject_id
LEFT JOIN rooms r ON s.room_id=r.room_id
$where
ORDER BY a.attendance_date DESC
LIMIT $offset,$limit
");

while($row=mysqli_fetch_assoc($query)){
$attendance[]=$row;
}

?>

<main>

<div class="head-title">

<div class="left">

<h1>My Attendance</h1>

<ul class="breadcrumb">
<li><a href="#">Home</a></li>
<li><i class='bx bx-chevron-right'></i></li>
<li><a class="active">Attendance</a></li>
</ul>

</div>

</div>


<div class="table-data">

<div class="order">

<div class="head">
<h3>Attendance Records</h3>
</div>


<!-- FILTERS -->

<div class="row-controls">

<input type="search"
id="searchInput"
class="search-input"
placeholder="Search date..."
value="<?php echo htmlspecialchars($search); ?>">

<select id="subjectFilter" class="search-input">

<option value="">All Subjects</option>

<?php foreach($subjects as $sub){ ?>

<option value="<?php echo $sub['subject_id']; ?>"
<?php if($subject_id==$sub['subject_id']) echo "selected"; ?>>

<?php
echo $sub['subject_name']." (Year ".$sub['year']." - ".$sub['semester'].")";
?>

</option>

<?php } ?>

</select>

<select id="limitSelect" class="search-input">

<?php foreach([5,10,20,50] as $opt){ ?>

<option value="<?php echo $opt; ?>"
<?php if($opt==$limit) echo "selected"; ?>>
<?php echo $opt; ?>
</option>

<?php } ?>

</select>

</div>



<table>

<thead>

<tr>
<th>#</th>
<th>Subject</th>
<th>Room</th>
<th>Semester</th>
<th>Date</th>
<th>Status</th>
</tr>

</thead>

<tbody>

<?php

$sn=$offset+1;

if(!empty($attendance)){

foreach($attendance as $row){

$status=$row['status'];

?>

<tr>

<td><?php echo $sn++; ?></td>

<td><?php echo $row['subject_name']; ?></td>

<td><?php echo $row['room_name']; ?></td>

<td><?php echo $row['semester']; ?></td>

<td><?php echo $row['attendance_date']; ?></td>

<td>

<?php

if($status=="Present"){
echo "<span class='status completed'>Present</span>";
}
elseif($status=="Absent"){
echo "<span class='status pending'>Absent</span>";
}
elseif($status=="Late"){
echo "<span class='status process'>Late</span>";
}
else{
echo "<span class='status process'>Unknown</span>";
}

?>

</td>

</tr>

<?php

}

}else{

?>

<tr>
<td colspan="6" style="text-align:center;">No attendance records found.</td>
</tr>

<?php } ?>

</tbody>

</table>


<!-- PAGINATION -->

<div class="mt-15">

<?php if($page>1){ ?>

<a href="?subject_id=<?php echo $subject_id; ?>
&search=<?php echo urlencode($search); ?>
&limit=<?php echo $limit; ?>
&page=<?php echo $page-1; ?>" class="btn small">Prev</a>

<?php } ?>

<?php for($i=1;$i<=$totalPages;$i++){ ?>

<a href="?subject_id=<?php echo $subject_id; ?>
&search=<?php echo urlencode($search); ?>
&limit=<?php echo $limit; ?>
&page=<?php echo $i; ?>"
class="btn small <?php if($i==$page) echo 'primary'; ?>">

<?php echo $i; ?>

</a>

<?php } ?>

<?php if($page<$totalPages){ ?>

<a href="?subject_id=<?php echo $subject_id; ?>
&search=<?php echo urlencode($search); ?>
&limit=<?php echo $limit; ?>
&page=<?php echo $page+1; ?>" class="btn small">Next</a>

<?php } ?>

</div>

</div>

</div>

</main>


<script>

const searchInput=document.getElementById('searchInput');
const subjectFilter=document.getElementById('subjectFilter');
const limitSelect=document.getElementById('limitSelect');

function applyFilters(){

const params=new URLSearchParams();

if(searchInput.value) params.set('search',searchInput.value);
if(subjectFilter.value) params.set('subject_id',subjectFilter.value);

params.set('limit',limitSelect.value);
params.set('page',1);

window.location.search=params.toString();

}

searchInput?.addEventListener('keyup',applyFilters);
subjectFilter?.addEventListener('change',applyFilters);
limitSelect?.addEventListener('change',applyFilters);

</script>

<?php include 'stms_footer.php'; ?>
