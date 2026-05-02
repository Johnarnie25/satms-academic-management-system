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

/* ================= GRADE CONVERSION ================= */
function convertToGrade($avg){
    if($avg >= 98) return 1.00;
    elseif($avg >= 95) return 1.25;
    elseif($avg >= 92) return 1.50;
    elseif($avg >= 89) return 1.75;
    elseif($avg >= 86) return 2.00;
    elseif($avg >= 83) return 2.25;
    elseif($avg >= 80) return 2.50;
    elseif($avg >= 77) return 2.75;
    elseif($avg >= 75) return 3.00;
    else return 5.00;
}

/* ================= SAVE GRADES ================= */
if(isset($_POST['saveGrades'])){

    $student_id = intval($_POST['student_id']);
    $subject_id = intval($_POST['subject_id']);

    $grades = [
        'Prelim'  => $_POST['prelim'] ?? '',
        'Midterm' => $_POST['midterm'] ?? '',
        'Finals'  => $_POST['finals'] ?? '',
    ];

    $totalScore = 0;
    $count = 0;

    foreach($grades as $type => $val){

        if($val === '') continue;

        $score = intval($val);

        if($score >= 0 && $score <= 100){

            $totalScore += $score;
            $count++;

            $examRes = mysqli_query($conn,"
                SELECT exam_id 
                FROM exams 
                WHERE subject_id='$subject_id' 
                AND exam_type='$type'
                LIMIT 1
            ");

            if($examRes && mysqli_num_rows($examRes)>0){
                $exam_id = mysqli_fetch_assoc($examRes)['exam_id'];
            } else {
                mysqli_query($conn,"
                    INSERT INTO exams 
                    (subject_id, exam_type, exam_title, exam_date, total_score)
                    VALUES (
                        '$subject_id',
                        '$type',
                        '$type Exam',
                        CURDATE(),
                        100
                    )
                ");
                $exam_id = mysqli_insert_id($conn);
            }

            mysqli_query($conn,"
                INSERT INTO final_grades (exam_id, student_id, score, total_grade)
                VALUES ('$exam_id','$student_id','$score','0')
                ON DUPLICATE KEY UPDATE score='$score'
            ");
        }
    }

    if($count == 3){
        $average = $totalScore / 3;
        $finalGrade = convertToGrade($average);

        mysqli_query($conn,"
            UPDATE final_grades fg
            JOIN exams e ON fg.exam_id = e.exam_id
            SET fg.total_grade = '$finalGrade'
            WHERE fg.student_id = '$student_id'
            AND e.subject_id = '$subject_id'
        ");
    }

    echo "<script>window.location='grading_system.php';</script>";
    exit;
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
$where = "WHERE s.teacher_id='".intval($teacher_id)."'";

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
        u.student_id_number LIKE '%$sc%' OR
        u.first_name LIKE '%$sc%' OR
        u.last_name LIKE '%$sc%' OR
        s.subject_name LIKE '%$sc%'
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
    $where
");

if($totRes){
    $total=mysqli_fetch_assoc($totRes)['total'];
    $totalPages=ceil($total/$limit);
}

/* ================= FETCH ================= */
$students=[];
$res=mysqli_query($conn,"
    SELECT 
        ss.student_id,
        ss.subject_id,
        u.student_id_number,
        CONCAT(u.first_name,' ',u.last_name) as fullname,
        s.subject_name,
        s.semester,
        s.year
    FROM student_subjects ss
    JOIN students st ON ss.student_id=st.student_id
    JOIN users u ON st.user_id=u.user_id
    JOIN subjects s ON ss.subject_id=s.subject_id
    $where
    ORDER BY u.last_name ASC
    LIMIT $offset,$limit
");

if($res){
    while($row=mysqli_fetch_assoc($res)){
        $students[]=$row;
    }
}
?>

<main>
    <div class="head-title"> <div class="left"> <h1>Grading System</h1> <ul class="breadcrumb"> <li><a href="#">Home</a></li> <li><i class='bx bx-chevron-right'></i></li> <li><a class="active">Management Information</a></li> </ul> </div> </div>
<div class="table-data">
<div class="order">

<div class="head flex-between">
<h3>Student Grades Management</h3>
</div>

<!-- FILTERS (UNCHANGED DESIGN) -->
<div class="row-controls">

<input type="search" id="searchInput" class="search-input"
placeholder="Search..."
value="<?= htmlspecialchars($search) ?>">

<select id="yearFilter" class="search-input">
<option value="">All Year</option>
<?php
$resYear=mysqli_query($conn,"SELECT DISTINCT year FROM subjects WHERE teacher_id='$teacher_id'");
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
$resSub=mysqli_query($conn,"SELECT subject_id,subject_name FROM subjects WHERE teacher_id='$teacher_id'");
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
<th>Name</th>
<th>Semester</th>
<th>Year</th>
<th>Subject</th>
<th>Prelim</th>
<th>Midterm</th>
<th>Finals</th>
<th>Average</th>
<th>Result</th>
<th>Action</th>
</tr>
</thead>
<tbody>

<?php
if(empty($students)){
echo "<tr><td colspan='12'>No Records Found</td></tr>";
}else{
$sn=$offset+1;
foreach($students as $row){

$student_id=$row['student_id'];
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
<td><?= $row['student_id_number'] ?></td>
<td><?= $row['fullname'] ?></td>
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
<td>
<button class="btn small primary"
onclick="openGradeModal(
<?= $student_id ?>,
<?= $subject_id ?>,
'<?= $scores['Prelim'] ?>',
'<?= $scores['Midterm'] ?>',
'<?= $scores['Finals'] ?>'
)">Set Grades</button>
</td>
</tr>

<?php }} ?>

</tbody>
</table>

<!-- PAGINATION -->
<!-- PAGINATION -->
<?php if($totalPages>1){ ?>
<div class="mt-15">

<?php if($page>1){ ?>
<a href="?page=<?= $page-1 ?>&limit=<?= $limit ?>&filterYear=<?= urlencode($filterYear) ?>&filterSem=<?= urlencode($filterSem) ?>&filterSubject=<?= urlencode($filterSubject) ?>&search=<?= urlencode($search) ?>" 
class="btn small">Prev</a>
<?php } ?>

<?php for($i=1;$i<=$totalPages;$i++){ ?>
<a href="?page=<?= $i ?>&limit=<?= $limit ?>&filterYear=<?= urlencode($filterYear) ?>&filterSem=<?= urlencode($filterSem) ?>&filterSubject=<?= urlencode($filterSubject) ?>&search=<?= urlencode($search) ?>" 
class="btn small <?= $i==$page?'primary':'' ?>">
<?= $i ?>
</a>
<?php } ?>

<?php if($page<$totalPages){ ?>
<a href="?page=<?= $page+1 ?>&limit=<?= $limit ?>&filterYear=<?= urlencode($filterYear) ?>&filterSem=<?= urlencode($filterSubject) ?>&search=<?= urlencode($search) ?>" 
class="btn small">Next</a>
<?php } ?>

</div>
<?php } ?>

</div>
</div>
</main>

<!-- MODAL (UNCHANGED DESIGN) -->
<div id="gradeModal" class="modal" aria-hidden="true">
<div class="modal-content">
<button class="close" onclick="closeGradeModal()">&times;</button>
<h3>Set Student Grades</h3>
<form method="POST">
<input type="hidden" name="student_id" id="modal_student_id">
<input type="hidden" name="subject_id" id="modal_subject_id">

<label>Prelim</label>
<input type="number" name="prelim" id="modal_prelim" min="0" max="100">

<label>Midterm</label>
<input type="number" name="midterm" id="modal_midterm" min="0" max="100">

<label>Finals</label>
<input type="number" name="finals" id="modal_finals" min="0" max="100">

<button type="submit" name="saveGrades" class="btn primary">Save</button>
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

function openGradeModal(student_id, subject_id, prelim, midterm, finals){
document.getElementById('modal_student_id').value=student_id;
document.getElementById('modal_subject_id').value=subject_id;
document.getElementById('modal_prelim').value=prelim!='-'?prelim:'';
document.getElementById('modal_midterm').value=midterm!='-'?midterm:'';
document.getElementById('modal_finals').value=finals!='-'?finals:'';
document.getElementById('gradeModal').setAttribute('aria-hidden','false');
}
function closeGradeModal(){
document.getElementById('gradeModal').setAttribute('aria-hidden','true');
}
document.getElementById('gradeModal').addEventListener('click',function(e){
if(e.target===this){closeGradeModal();}
});
</script>

<?php include 'stms_footer.php'; ?>