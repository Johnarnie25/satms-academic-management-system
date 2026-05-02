<?php include 'header.php'; ?>

<?php
$user_id = $_SESSION['user_id'] ?? 0;

// ================= GET PARENT ID =================
$parent_id = 0;
$resParent = mysqli_query($conn,"SELECT parent_id FROM parents WHERE user_id='".intval($user_id)."' LIMIT 1");
if($resParent && mysqli_num_rows($resParent)){
    $parent_id = mysqli_fetch_assoc($resParent)['parent_id'];
}

// ================= GET CHILDREN =================
$children = [];
$resChildren = mysqli_query($conn,"SELECT student_id, CONCAT(u.first_name,' ',u.last_name) as student_name 
                                  FROM students s 
                                  JOIN users u ON s.user_id=u.user_id 
                                  WHERE s.parent_id='$parent_id'");
while($c = mysqli_fetch_assoc($resChildren)) $children[] = $c;

// ================= SELECTED STUDENT =================
$student_id = intval($_GET['student_id'] ?? ($children[0]['student_id'] ?? 0));

// ================= FILTERS =================
$filterYear = $_GET['filterYear'] ?? '';
$filterSem  = $_GET['filterSem'] ?? '';
$search     = $_GET['search'] ?? '';
$page       = max(1, intval($_GET['page'] ?? 1));
$limit      = intval($_GET['limit'] ?? 10);
if(!in_array($limit,[5,10,25,50])) $limit = 10;
$offset = ($page - 1) * $limit;

// ================= BUILD WHERE =================
$where = "WHERE ss.student_id='$student_id' ";
if($filterYear){
    $where .= " AND s.year='".mysqli_real_escape_string($conn,$filterYear)."' ";
}
if($filterSem){
    $where .= " AND s.semester='".mysqli_real_escape_string($conn,$filterSem)."' ";
}
if($search){
    $where .= " AND s.subject_name LIKE '%".mysqli_real_escape_string($conn,$search)."%' ";
}

// ================= COUNT =================
$total = 0;
$totalPages = 0;
$totRes = mysqli_query($conn,"
    SELECT COUNT(DISTINCT s.subject_id) as total
    FROM student_subjects ss
    JOIN subjects s ON ss.subject_id=s.subject_id
    $where
");
if($totRes){
    $total = mysqli_fetch_assoc($totRes)['total'];
    $totalPages = ceil($total / $limit);
}

// ================= FETCH SUBJECTS & EXAMS =================
$subjects = [];
$res = mysqli_query($conn,"
    SELECT s.subject_id, s.subject_name, s.semester, s.year,
        MAX(CASE WHEN e.exam_type='Prelim' THEN e.exam_date END) AS prelim_date,
        MAX(CASE WHEN e.exam_type='Midterm' THEN e.exam_date END) AS midterm_date,
        MAX(CASE WHEN e.exam_type='Finals' THEN e.exam_date END) AS finals_date
    FROM student_subjects ss
    JOIN subjects s ON ss.subject_id=s.subject_id
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

// ================= FETCH YEARS =================
$years = [];
$resYear = mysqli_query($conn,"SELECT DISTINCT s.year 
                               FROM student_subjects ss 
                               JOIN subjects s ON ss.subject_id=s.subject_id 
                               WHERE ss.student_id='$student_id' 
                               ORDER BY s.year ASC");
while($y = mysqli_fetch_assoc($resYear)) $years[] = $y['year'];
$sems = ['1st Sem','2nd Sem','3rd Sem','Summer'];
?>

<main>
<div class="head-title">
    <div class="left">
        <h1>Exam Dates</h1>
        <ul class="breadcrumb">
            <li><a href="#">Home</a></li>
            <li><i class='bx bx-chevron-right'></i></li>
            <li><a class="active">Student Exams</a></li>
        </ul>
    </div>
</div>

<div class="table-data">
<div class="order">
<div class="head flex-between">
<h3>Student Exam Dates</h3>
</div>

<!-- SELECT CHILD -->
<div class="row-controls">
<label for="studentSelect">Select Student:</label>
<select id="studentSelect" class="search-input">
<?php foreach($children as $c){
    $sel = ($c['student_id']==$student_id)?'selected':'';
    echo "<option value='{$c['student_id']}' $sel>{$c['student_name']}</option>";
} ?>
</select>
</div>

<!-- FILTERS -->
<div class="row-controls mt-10">
<input type="search" id="searchInput" class="search-input"
placeholder="Search subject..." value="<?= htmlspecialchars($search) ?>">

<select id="yearFilter" class="search-input">
<option value="">All Year</option>
<?php foreach($years as $y){
    $sel = ($filterYear==$y)?'selected':'';
    echo "<option value='$y' $sel>$y</option>";
} ?>
</select>

<select id="semFilter" class="search-input">
<option value="">All Semester</option>
<?php foreach($sems as $s){
    $sel = ($filterSem==$s)?'selected':'';
    echo "<option value='$s' $sel>$s</option>";
} ?>
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
</tr>
</thead>
<tbody>
<?php
if(empty($subjects)){
    echo "<tr><td colspan='7' class='text-center'>No Subjects Found</td></tr>";
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
</tr>
<?php }} ?>
</tbody>
</table>

<!-- PAGINATION -->
<?php if($totalPages > 1){ ?>
<div class="mt-15">
<?php if($page > 1){ ?>
<a href="?student_id=<?= $student_id ?>&page=<?= $page-1 ?>&limit=<?= $limit ?>&filterYear=<?= urlencode($filterYear) ?>&filterSem=<?= urlencode($filterSem) ?>&search=<?= urlencode($search) ?>" class="btn small">Prev</a>
<?php } ?>

<?php for($i=1;$i<=$totalPages;$i++){ ?>
<a href="?student_id=<?= $student_id ?>&page=<?= $i ?>&limit=<?= $limit ?>&filterYear=<?= urlencode($filterYear) ?>&filterSem=<?= urlencode($filterSem) ?>&search=<?= urlencode($search) ?>" class="btn small <?= $i==$page?'primary':'' ?>"><?= $i ?></a>
<?php } ?>

<?php if($page < $totalPages){ ?>
<a href="?student_id=<?= $student_id ?>&page=<?= $page+1 ?>&limit=<?= $limit ?>&filterYear=<?= urlencode($filterYear) ?>&filterSem=<?= urlencode($filterSem) ?>&search=<?= urlencode($search) ?>" class="btn small">Next</a>
<?php } ?>
</div>
<?php } ?>

</div>
</div>
</main>

<script>
const studentSelect=document.getElementById('studentSelect');
const searchInput=document.getElementById('searchInput');
const yearFilter=document.getElementById('yearFilter');
const semFilter=document.getElementById('semFilter');
const limitFilter=document.getElementById('limitFilter');

function reload(){
    const p=new URLSearchParams();
    p.set('student_id',studentSelect.value);
    if(searchInput.value)p.set('search',searchInput.value);
    if(yearFilter.value)p.set('filterYear',yearFilter.value);
    if(semFilter.value)p.set('filterSem',semFilter.value);
    p.set('limit',limitFilter.value);
    p.set('page',1);
    window.location.search=p.toString();
}

studentSelect.addEventListener('change',reload);
let timer;
searchInput.addEventListener('keyup',()=>{clearTimeout(timer);timer=setTimeout(reload,500);});
[yearFilter,semFilter,limitFilter].forEach(el=>el.addEventListener('change',reload));
</script>

<?php include 'stms_footer.php'; ?>
