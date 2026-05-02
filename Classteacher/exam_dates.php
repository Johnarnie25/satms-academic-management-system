<?php include 'header.php'; ?>

<?php
$user_id = $_SESSION['user_id'] ?? 0;

/* ================= GET TEACHER ID ================= */
$teacher_id = 0;
$resT = mysqli_query($conn, "SELECT teacher_id FROM teachers WHERE user_id='".intval($user_id)."' LIMIT 1");
if($resT && mysqli_num_rows($resT) > 0){
    $teacher_id = mysqli_fetch_assoc($resT)['teacher_id'];
}

/* ================= SAVE EXAM DATES ================= */
if(isset($_POST['saveExamDates'])){
    $subject_id = intval($_POST['subject_id']);

    $examTypes = [
        'Prelim'  => $_POST['prelim_date'] ?? '',
        'Midterm' => $_POST['midterm_date'] ?? '',
        'Finals'  => $_POST['finals_date'] ?? ''
    ];

    foreach($examTypes as $type => $date){
        if(!empty($date)){
            mysqli_query($conn,"
                INSERT INTO exams (subject_id, exam_type, exam_title, exam_date, total_score)
                VALUES ('$subject_id','$type','$type Exam','$date',100)
                ON DUPLICATE KEY UPDATE exam_date='$date'
            ");
        }
    }

    echo "<script>window.location='exam_dates.php';</script>";
    exit;
}

/* ================= FILTERS ================= */
$filterYear = $_GET['filterYear'] ?? '';
$filterSem  = $_GET['filterSem'] ?? '';
$search     = $_GET['search'] ?? '';
$page       = max(1, intval($_GET['page'] ?? 1));
$limit      = intval($_GET['limit'] ?? 10);

if(!in_array($limit,[5,10,25,50])) $limit = 10;

$offset = ($page - 1) * $limit;

/* ================= BUILD WHERE ================= */
$where = "WHERE s.teacher_id='".intval($teacher_id)."'";

if($filterYear){
    $fy = mysqli_real_escape_string($conn,$filterYear);
    $where .= " AND s.year='$fy'";
}

if($filterSem){
    $fs = mysqli_real_escape_string($conn,$filterSem);
    $where .= " AND s.semester='$fs'";
}

if($search){
    $sc = mysqli_real_escape_string($conn,$search);
    $where .= " AND s.subject_name LIKE '%$sc%'";
}

/* ================= COUNT ================= */
$total = 0;
$totalPages = 0;

$totRes = mysqli_query($conn,"
    SELECT COUNT(*) as total
    FROM subjects s
    $where
");

if($totRes){
    $total = mysqli_fetch_assoc($totRes)['total'];
    $totalPages = ceil($total / $limit);
}

/* ================= FETCH SUBJECTS ================= */
$subjects = [];

$res = mysqli_query($conn,"
    SELECT s.subject_id, s.subject_name, s.semester, s.year,
    MAX(CASE WHEN e.exam_type='Prelim' THEN e.exam_date END) AS prelim_date,
    MAX(CASE WHEN e.exam_type='Midterm' THEN e.exam_date END) AS midterm_date,
    MAX(CASE WHEN e.exam_type='Finals' THEN e.exam_date END) AS finals_date
    FROM subjects s
    LEFT JOIN exams e ON s.subject_id = e.subject_id
    $where
    GROUP BY s.subject_id
    ORDER BY s.subject_name ASC
    LIMIT $offset, $limit
");

if($res){
    while($row = mysqli_fetch_assoc($res)){
        $subjects[] = $row;
    }
}
?>

<main>
<div class="head-title">
    <div class="left">
        <h1>Exam Dates</h1>
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
<h3>Exam Date Management</h3>
</div>

<!-- FILTERS -->
<div class="row-controls">

<input type="search" id="searchInput" class="search-input"
placeholder="Search subject..."
value="<?= htmlspecialchars($search) ?>">

<select id="yearFilter" class="search-input">
<option value="">All Year</option>
<?php
$resYear = mysqli_query($conn,"
    SELECT DISTINCT year 
    FROM subjects 
    WHERE teacher_id='".intval($teacher_id)."'
    ORDER BY year ASC
");
while($y = mysqli_fetch_assoc($resYear)){
    $sel = ($filterYear==$y['year'])?'selected':'';
    echo "<option value='{$y['year']}' $sel>{$y['year']}</option>";
}
?>
</select>

<select id="semFilter" class="search-input">
<option value="">All Semester</option>
<?php
$sems = ['1st Sem','2nd Sem','3rd Sem','Summer'];
foreach($sems as $s){
    $sel = ($filterSem==$s)?'selected':'';
    echo "<option value='$s' $sel>$s</option>";
}
?>
</select>

<select id="limitFilter" class="search-input">
<?php foreach([5,10,25,50] as $l){
$sel = ($limit==$l)?'selected':'';
echo "<option value='$l' $sel>Show $l</option>";
} ?>
</select>

</div>

<table>
<thead>
<tr>
<th>#</th>
<th>Subject</th>
<th>Semester</th>
<th>Year</th>
<th>Prelim</th>
<th>Midterm</th>
<th>Finals</th>
<th>Action</th>
</tr>
</thead>
<tbody>

<?php
if(empty($subjects)){
    echo "<tr><td colspan='8' class='text-center'>No Subjects Found</td></tr>";
}else{
$sn = $offset + 1;
foreach($subjects as $row){
?>
<tr>
<td><?= $sn++ ?></td>
<td><?= htmlspecialchars($row['subject_name']) ?></td>
<td><?= htmlspecialchars($row['semester']) ?></td>
<td><?= htmlspecialchars($row['year']) ?></td>
<td><?= $row['prelim_date'] ?: '-' ?></td>
<td><?= $row['midterm_date'] ?: '-' ?></td>
<td><?= $row['finals_date'] ?: '-' ?></td>
<td>
<button type="button"
class="btn small primary"
onclick="openExamModal(
<?= $row['subject_id'] ?>,
'<?= $row['prelim_date'] ?? '' ?>',
'<?= $row['midterm_date'] ?? '' ?>',
'<?= $row['finals_date'] ?? '' ?>'
)">Set Exam Date</button>
</td>
</tr>
<?php }} ?>
</tbody>
</table>

<!-- PAGINATION -->
<?php if($totalPages > 1){ ?>
<div class="mt-15">

<?php if($page > 1){ ?>
<a href="?page=<?= $page-1 ?>&limit=<?= $limit ?>&filterYear=<?= urlencode($filterYear) ?>&filterSem=<?= urlencode($filterSem) ?>&search=<?= urlencode($search) ?>" class="btn small">Prev</a>
<?php } ?>

<?php for($i=1;$i<=$totalPages;$i++){ ?>
<a href="?page=<?= $i ?>&limit=<?= $limit ?>&filterYear=<?= urlencode($filterYear) ?>&filterSem=<?= urlencode($filterSem) ?>&search=<?= urlencode($search) ?>"
class="btn small <?= $i==$page?'primary':'' ?>">
<?= $i ?>
</a>
<?php } ?>

<?php if($page < $totalPages){ ?>
<a href="?page=<?= $page+1 ?>&limit=<?= $limit ?>&filterYear=<?= urlencode($filterYear) ?>&filterSem=<?= urlencode($filterSem) ?>&search=<?= urlencode($search) ?>" class="btn small">Next</a>
<?php } ?>

</div>
<?php } ?>

</div>
</div>
</main>

<!-- MODAL -->
<div id="examModal" class="modal" aria-hidden="true">
<div class="modal-content">
<button class="close" onclick="closeExamModal()">&times;</button>
<h3>Set Exam Dates</h3>

<form method="POST">
<input type="hidden" name="subject_id" id="modal_subject_id">

<label>Prelim</label>
<input type="date" name="prelim_date" id="prelim_date">

<label>Midterm</label>
<input type="date" name="midterm_date" id="midterm_date">

<label>Finals</label>
<input type="date" name="finals_date" id="finals_date">

<button type="submit" name="saveExamDates" class="btn primary">Save</button>
</form>
</div>
</div>

<script>
function openExamModal(id, prelim, midterm, finals){
document.getElementById('modal_subject_id').value=id;
document.getElementById('prelim_date').value=prelim||'';
document.getElementById('midterm_date').value=midterm||'';
document.getElementById('finals_date').value=finals||'';
document.getElementById('examModal').setAttribute('aria-hidden','false');
}

function closeExamModal(){
document.getElementById('examModal').setAttribute('aria-hidden','true');
}

document.getElementById('examModal').addEventListener('click',function(e){
if(e.target===this) closeExamModal();
});

/* FILTER JS */
const searchInput=document.getElementById('searchInput');
const yearFilter=document.getElementById('yearFilter');
const semFilter=document.getElementById('semFilter');
const limitFilter=document.getElementById('limitFilter');

let timer;
function reload(){
const p=new URLSearchParams();
if(searchInput.value)p.set('search',searchInput.value);
if(yearFilter.value)p.set('filterYear',yearFilter.value);
if(semFilter.value)p.set('filterSem',semFilter.value);
p.set('limit',limitFilter.value);
p.set('page',1);
window.location.search=p.toString();
}
searchInput.addEventListener('keyup',()=>{clearTimeout(timer);timer=setTimeout(reload,500);});
[yearFilter,semFilter,limitFilter].forEach(el=>el.addEventListener('change',reload));
</script>

<?php include 'stms_footer.php'; ?>
