<?php include 'header.php'; ?>

<?php
$user_id = $_SESSION['user_id'] ?? 0;

/* ================= GET TEACHER ID ================= */
$teacher_id = 0;
$resT = mysqli_query($conn,"
    SELECT teacher_id 
    FROM teachers 
    WHERE user_id='".intval($user_id)."'
    LIMIT 1
");
if($resT && mysqli_num_rows($resT)>0){
    $teacher_id = mysqli_fetch_assoc($resT)['teacher_id'];
}

/* ================= SAVE QUIZ GRADE ================= */
if(isset($_POST['saveQuizGrade'])){

    $student_id = intval($_POST['student_id']);
    $quiz_id    = intval($_POST['quiz_id']);
    $score      = $_POST['score'];

    if($score !== '' && is_numeric($score)){
        $score = intval($score);

        mysqli_query($conn,"
            INSERT INTO quiz_grades (quiz_id, student_id, score)
            VALUES ('$quiz_id','$student_id','$score')
            ON DUPLICATE KEY UPDATE score='$score'
        ");
    }

    echo "<script>window.location='quizes_grades.php';</script>";
    exit;
}

/* ================= FILTERS ================= */
$filterYear    = $_GET['filterYear'] ?? '';
$filterSem     = $_GET['filterSem'] ?? '';
$filterSubject = $_GET['filterSubject'] ?? '';
$search        = $_GET['search'] ?? '';
$page  = max(1, intval($_GET['page'] ?? 1));
$limit = intval($_GET['limit'] ?? 10);

if(!in_array($limit,[5,10,25,50])) $limit=10;
$offset = ($page-1)*$limit;

/* ================= BUILD WHERE ================= */
$where = "WHERE s.teacher_id='".intval($teacher_id)."'";

if($filterYear){
    $where .= " AND s.year='".mysqli_real_escape_string($conn,$filterYear)."'";
}
if($filterSem){
    $where .= " AND s.semester='".mysqli_real_escape_string($conn,$filterSem)."'";
}
if($filterSubject){
    $where .= " AND s.subject_id='".intval($filterSubject)."'";
}
if($search){
    $sc = mysqli_real_escape_string($conn,$search);
    $where .= " AND (
        u.student_id_number LIKE '%$sc%' OR
        u.first_name LIKE '%$sc%' OR
        u.last_name LIKE '%$sc%' OR
        s.subject_name LIKE '%$sc%' OR
        q.quiz_title LIKE '%$sc%'
    )";
}

/* ================= COUNT ================= */
$total=0;
$totalPages=0;

$totRes = mysqli_query($conn,"
    SELECT COUNT(*) as total
    FROM student_subjects ss
    JOIN students st ON ss.student_id=st.student_id
    JOIN users u ON st.user_id=u.user_id
    JOIN subjects s ON ss.subject_id=s.subject_id
    JOIN quizzes q ON s.subject_id=q.subject_id
    $where
");

if($totRes){
    $total=mysqli_fetch_assoc($totRes)['total'];
    $totalPages=ceil($total/$limit);
}

/* ================= FETCH DATA ================= */
$data=[];

$res=mysqli_query($conn,"
    SELECT 
        ss.student_id,
        q.quiz_id,
        u.student_id_number,
        CONCAT(u.first_name,' ',u.last_name) as fullname,
        s.subject_name,
        s.semester,
        s.year,
        q.quiz_title,
        q.total_score,
        q.quiz_date
    FROM student_subjects ss
    JOIN students st ON ss.student_id=st.student_id
    JOIN users u ON st.user_id=u.user_id
    JOIN subjects s ON ss.subject_id=s.subject_id
    JOIN quizzes q ON s.subject_id=q.subject_id
    $where
    ORDER BY u.last_name ASC
    LIMIT $offset,$limit
");

if($res){
    while($row=mysqli_fetch_assoc($res)){
        $data[]=$row;
    }
}
?>

<main>
<div class="head-title">
<div class="left">
<h1>Quiz Grades</h1>
 <ul class="breadcrumb">
      <li><a href="#">Home</a></li>
      <li><i class='bx bx-chevron-right'></i></li>
      <li><a class="active">Management Information</a></li>
    </ul>
  </div>
</div>

<div class="table-data">
<div class="order">

<div class="head flex-between">
<h3>Quiz Grades Management</h3>
</div>

<!-- ================= FILTERS ================= -->
<div class="row-controls">

<input type="search" id="searchInput" class="search-input"
placeholder="Search student, subject or quiz..."
value="<?= htmlspecialchars($search) ?>">

<select id="yearFilter" class="search-input">
<option value="">All Year</option>
<?php
$resYear=mysqli_query($conn,"
SELECT DISTINCT year FROM subjects
WHERE teacher_id='".intval($teacher_id)."'
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
$resSub = mysqli_query($conn,"
SELECT subject_id, subject_name 
FROM subjects 
WHERE teacher_id='".intval($teacher_id)."'
ORDER BY subject_name ASC
");
while($sub=mysqli_fetch_assoc($resSub)){
$sel=($filterSubject==$sub['subject_id'])?'selected':'';
echo "<option value='{$sub['subject_id']}' $sel>{$sub['subject_name']}</option>";
}
?>
</select>

<select id="limitFilter" class="search-input">
<?php foreach([5,10,25,50] as $l){
$sel=($limit==$l)?'selected':'';
echo "<option value='$l' $sel>Show $l</option>";
} ?>
</select>

</div>

<table>
<thead>
<tr>
<th>#</th>
<th>Student ID</th>
<th>Fullname</th>
<th>Semester</th>
<th>Year</th>
<th>Subject</th>
<th>Quiz</th>
<th>Total</th>
<th>Score</th>
<th>Date</th>
<th>Action</th>
</tr>
</thead>
<tbody>

<?php
if(empty($data)){
echo "<tr><td colspan='11'>No Records Found</td></tr>";
}else{
$sn=$offset+1;
foreach($data as $row){

$student_id=$row['student_id'];
$quiz_id=$row['quiz_id'];

$score='-';

$gRes=mysqli_query($conn,"
SELECT score FROM quiz_grades
WHERE quiz_id='$quiz_id'
AND student_id='$student_id'
LIMIT 1
");

if($gRes && mysqli_num_rows($gRes)>0){
$score=mysqli_fetch_assoc($gRes)['score'];
}
?>
<tr>
<td><?= $sn++ ?></td>
<td><?= htmlspecialchars($row['student_id_number']) ?></td>
<td><?= htmlspecialchars($row['fullname']) ?></td>
<td><?= htmlspecialchars($row['semester']) ?></td>
<td><?= htmlspecialchars($row['year']) ?></td>
<td><?= htmlspecialchars($row['subject_name']) ?></td>
<td><?= htmlspecialchars($row['quiz_title']) ?></td>
<td><?= $row['total_score'] ?></td>
<td><?= $score ?></td>
<td><?= $row['quiz_date'] ?></td>
<td>
<button class="btn small primary"
onclick="openQuizModal(
<?= $student_id ?>,
<?= $quiz_id ?>,
'<?= $score ?>'
)">Set Score</button>
</td>
</tr>
<?php }} ?>
</tbody>
</table>

<!-- ================= PAGINATION ================= -->
<?php if($totalPages>1){ ?>
<div class="mt-15">
<?php if($page>1){ ?>
<a href="?page=<?= $page-1 ?>&limit=<?= $limit ?>&filterYear=<?= urlencode($filterYear) ?>&filterSem=<?= urlencode($filterSem) ?>&filterSubject=<?= urlencode($filterSubject) ?>&search=<?= urlencode($search) ?>" class="btn small">Prev</a>
<?php } ?>

<?php for($i=1;$i<=$totalPages;$i++){ ?>
<a href="?page=<?= $i ?>&limit=<?= $limit ?>&filterYear=<?= urlencode($filterYear) ?>&filterSem=<?= urlencode($filterSem) ?>&filterSubject=<?= urlencode($filterSubject) ?>&search=<?= urlencode($search) ?>" class="btn small <?= $i==$page?'primary':'' ?>">
<?= $i ?>
</a>
<?php } ?>

<?php if($page<$totalPages){ ?>
<a href="?page=<?= $page+1 ?>&limit=<?= $limit ?>&filterYear=<?= urlencode($filterYear) ?>&filterSem=<?= urlencode($filterSem) ?>&filterSubject=<?= urlencode($filterSubject) ?>&search=<?= urlencode($search) ?>" class="btn small">Next</a>
<?php } ?>
</div>
<?php } ?>

</div>
</div>
</main>

<!-- MODAL -->
<div id="quizModal" class="modal" aria-hidden="true">
<div class="modal-content">
<button class="close" onclick="closeQuizModal()">&times;</button>

<h3>Set Quiz Score</h3>

<form method="POST">
<input type="hidden" name="student_id" id="modal_student_id">
<input type="hidden" name="quiz_id" id="modal_quiz_id">

<label>Score</label>
<input type="number" name="score" id="modal_score" min="0">

<button type="submit" name="saveQuizGrade" class="btn primary">Save</button>
</form>
</div>
</div>

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
searchInput.addEventListener('keyup',()=>{clearTimeout(timer);timer=setTimeout(reload,500);});
[yearFilter,semFilter,subjectFilter,limitFilter].forEach(el=>el.addEventListener('change',reload));

function openQuizModal(student_id, quiz_id, score){
document.getElementById('modal_student_id').value=student_id;
document.getElementById('modal_quiz_id').value=quiz_id;
document.getElementById('modal_score').value=score!='-'?score:'';
document.getElementById('quizModal').setAttribute('aria-hidden','false');
}
function closeQuizModal(){
document.getElementById('quizModal').setAttribute('aria-hidden','true');
}
document.getElementById('quizModal').addEventListener('click',function(e){
if(e.target===this){closeQuizModal();}
});
</script>

<?php include 'stms_footer.php'; ?>