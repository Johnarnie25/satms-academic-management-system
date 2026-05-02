<?php include 'header.php'; ?>

<?php
$user_id = $_SESSION['user_id'] ?? 0;

/* ================= GET STUDENT ID ================= */
$student_id = 0;

$resS = mysqli_query($conn,"
    SELECT student_id
    FROM students
    WHERE user_id='".intval($user_id)."'
    LIMIT 1
");

if($resS && mysqli_num_rows($resS)>0){
    $student_id = mysqli_fetch_assoc($resS)['student_id'];
}

/* ================= FILTERS ================= */
$filterYear    = $_GET['filterYear'] ?? '';
$filterSem     = $_GET['filterSem'] ?? '';
$filterSubject = $_GET['filterSubject'] ?? '';
$search        = $_GET['search'] ?? '';
$page       = max(1, intval($_GET['page'] ?? 1));
$limit      = intval($_GET['limit'] ?? 10);

if(!in_array($limit,[5,10,25,50])) $limit=10;

$offset = ($page-1)*$limit;

/* ================= WHERE ================= */
$where = "WHERE ss.student_id='".intval($student_id)."'";

if($filterYear){
    $fy = mysqli_real_escape_string($conn,$filterYear);
    $where .= " AND s.year='$fy'";
}

if($filterSem){
    $fs = mysqli_real_escape_string($conn,$filterSem);
    $where .= " AND s.semester='$fs'";
}

if($filterSubject){
    $fsub = mysqli_real_escape_string($conn,$filterSubject);
    $where .= " AND s.subject_id='$fsub'";
}

if($search){
    $sc = mysqli_real_escape_string($conn,$search);
    $where .= " AND (
        s.subject_name LIKE '%$sc%'
    )";
}

/* ================= COUNT ================= */
$total=0;
$totalPages=0;

$totRes = mysqli_query($conn,"
    SELECT COUNT(*) as total
    FROM student_subjects ss
    JOIN subjects s ON ss.subject_id=s.subject_id
    $where
");

if($totRes){
    $total=mysqli_fetch_assoc($totRes)['total'];
    $totalPages=ceil($total/$limit);
}

/* ================= FETCH ================= */
$subjects=[];

$res=mysqli_query($conn,"
    SELECT 
        ss.student_id,
        ss.subject_id,
        s.subject_name,
        s.semester,
        s.year
    FROM student_subjects ss
    JOIN subjects s ON ss.subject_id=s.subject_id
    $where
    ORDER BY s.subject_name ASC
    LIMIT $offset,$limit
");

if($res){
    while($row=mysqli_fetch_assoc($res)){
        $subjects[]=$row;
    }
}
?>

<main>

<div class="head-title">
<div class="left">
<h1>My Grades</h1>

<ul class="breadcrumb">
<li><a href="#">Home</a></li>
<li><i class='bx bx-chevron-right'></i></li>
<li><a class="active">My Grades</a></li>
</ul>

</div>
</div>

<div class="table-data">
<div class="order">

<div class="head flex-between">
<h3>My Subjects Grades</h3>
</div>

<!-- FILTERS -->
<div class="row-controls">

<input type="search" id="searchInput" class="search-input"
placeholder="Search subject..."
value="<?= htmlspecialchars($search) ?>">

<select id="yearFilter" class="search-input">
<option value="">All Year</option>

<?php
$resYear=mysqli_query($conn,"
SELECT DISTINCT year 
FROM subjects
ORDER BY year ASC
");

while($y=mysqli_fetch_assoc($resYear)){

$sel=($filterYear==$y['year'])?'selected':'';

echo "<option value='{$y['year']}' $sel>{$y['year']}</option>";

}
?>

</select>

<select id="semFilter" class="search-input">

<option value="">All Semester</option>

<?php

$sems=['1st Sem','2nd Sem','3rd Sem','Summer'];

foreach($sems as $s){

$sel=($filterSem==$s)?'selected':'';

echo "<option value='$s' $sel>$s</option>";

}

?>

</select>

<select id="subjectFilter" class="search-input">

<option value="">All Subject</option>

<?php

$resSub=mysqli_query($conn,"
SELECT subject_id,subject_name 
FROM subjects
ORDER BY subject_name ASC
");

while($sub=mysqli_fetch_assoc($resSub)){

$sel=($filterSubject==$sub['subject_id'])?'selected':'';

echo "<option value='{$sub['subject_id']}' $sel>{$sub['subject_name']}</option>";

}

?>

</select>

<select id="limitFilter" class="search-input">

<?php

foreach([5,10,25,50] as $l){

$sel=($limit==$l)?'selected':'';

echo "<option value='$l' $sel>Show $l</option>";

}

?>

</select>

</div>

<table>

<thead>

<tr>

<th>#</th>
<th>Semester</th>
<th>Year</th>
<th>Subject</th>
<th>Prelim</th>
<th>Midterm</th>
<th>Finals</th>
<th>Average</th>
<th>Result</th>

</tr>

</thead>

<tbody>

<?php

if(empty($subjects)){

echo "<tr><td colspan='9'>No Records Found</td></tr>";

}else{

$sn=$offset+1;

foreach($subjects as $row){

$subject_id=$row['subject_id'];

$scores=['Prelim'=>'-','Midterm'=>'-','Finals'=>'-'];

$total_grade='-';

$status='Incomplete';

$total_grade_val=null;

$gRes=mysqli_query($conn,"
SELECT e.exam_type, fg.score, fg.total_grade
FROM exams e
LEFT JOIN final_grades fg
ON e.exam_id=fg.exam_id
AND fg.student_id='$student_id'
WHERE e.subject_id='$subject_id'
");

while($g=mysqli_fetch_assoc($gRes)){

if($g['score']!==null){

$scores[$g['exam_type']]=$g['score'];

}

if($g['total_grade']>0){

$total_grade_val=$g['total_grade'];

$total_grade=number_format($total_grade_val,2);

}

}

if($scores['Prelim']!='-' &&
$scores['Midterm']!='-' &&
$scores['Finals']!='-'){

if($total_grade_val>=1.00 && $total_grade_val<=3.00){

$status='Passed';

}elseif($total_grade_val>3.00){

$status='Failed';

}

}

?>

<tr>

<td><?= $sn++ ?></td>

<td><?= $row['semester'] ?></td>

<td><?= $row['year'] ?></td>

<td><?= $row['subject_name'] ?></td>

<td><?= $scores['Prelim'] ?></td>

<td><?= $scores['Midterm'] ?></td>

<td><?= $scores['Finals'] ?></td>

<td><?= $total_grade ?></td>

<td>

<?php

if($status=='Passed'){

echo "<span style='color:green;font-weight:bold;'>Passed</span>";

}elseif($status=='Failed'){

echo "<span style='color:red;font-weight:bold;'>Failed</span>";

}else{

echo "<span style='color:orange;font-weight:bold;'>Incomplete</span>";

}

?>

</td>

</tr>

<?php }} ?>

</tbody>

</table>

<!-- PAGINATION -->

<?php if($totalPages>1){ ?>

<div class="mt-15">

<?php if($page>1){ ?>

<a href="?page=<?= $page-1 ?>&limit=<?= $limit ?>" class="btn small">Prev</a>

<?php } ?>

<?php for($i=1;$i<=$totalPages;$i++){ ?>

<a href="?page=<?= $i ?>&limit=<?= $limit ?>"
class="btn small <?= $i==$page?'primary':'' ?>">

<?= $i ?>

</a>

<?php } ?>

<?php if($page<$totalPages){ ?>

<a href="?page=<?= $page+1 ?>&limit=<?= $limit ?>" class="btn small">Next</a>

<?php } ?>

</div>

<?php } ?>

</div>
</div>

</main>

<script>

const searchInput=document.getElementById('searchInput');
const yearFilter=document.getElementById('yearFilter');
const semFilter=document.getElementById('semFilter');
const subjectFilter=document.getElementById('subjectFilter');
const limitFilter=document.getElementById('limitFilter');

let timer;

function reload(){

const p=new URLSearchParams();

if(searchInput.value)p.set('search',searchInput.value);
if(yearFilter.value)p.set('filterYear',yearFilter.value);
if(semFilter.value)p.set('filterSem',semFilter.value);
if(subjectFilter.value)p.set('filterSubject',subjectFilter.value);

p.set('limit',limitFilter.value);

p.set('page',1);

window.location.search=p.toString();

}

searchInput.addEventListener('keyup',()=>{

clearTimeout(timer);

timer=setTimeout(reload,500);

});

[yearFilter,semFilter,subjectFilter,limitFilter]
.forEach(el=>el.addEventListener('change',reload));

</script>

<?php include 'stms_footer.php'; ?>
