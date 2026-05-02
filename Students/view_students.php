<?php include 'header.php'; ?>

<?php
$user_id = $_SESSION['user_id'] ?? 0;

// ================= FETCH STUDENT ID =================
$student_id = 0;
$resStudent = mysqli_query($conn,"SELECT student_id FROM students WHERE user_id='$user_id' LIMIT 1");
if($resStudent && mysqli_num_rows($resStudent)){
    $student_id = mysqli_fetch_assoc($resStudent)['student_id'];
}

// ================= FETCH SUBJECTS OF LOGGED-IN STUDENT =================
$subjects = [];
$q = mysqli_query($conn, "
    SELECT su.subject_id, su.subject_name 
    FROM subjects su
    JOIN student_subjects ss ON su.subject_id=ss.subject_id
    WHERE ss.student_id='$student_id'
    ORDER BY su.subject_name ASC
");
while($row = mysqli_fetch_assoc($q)) $subjects[] = $row;

// ================= FILTERS =================
$filterYear    = $_GET['filterYear'] ?? '';
$filterSem     = $_GET['filterSem'] ?? '';
$filterSubject = $_GET['filterSubject'] ?? '';
$search        = $_GET['search'] ?? '';
$page          = max(1, intval($_GET['page'] ?? 1));
$limit         = intval($_GET['limit'] ?? 10);
$offset        = ($page-1)*$limit;

// ================= BUILD WHERE =================
// only consider subjects of logged-in student
$where = "WHERE ss.student_id='$student_id' ";
if($filterYear!='') $where .= " AND su.year='".mysqli_real_escape_string($conn,$filterYear)."' ";
if($filterSem!='') $where .= " AND su.semester='".mysqli_real_escape_string($conn,$filterSem)."' ";
if($filterSubject!='') $where .= " AND su.subject_id='".intval($filterSubject)."' ";
if($search!='') $where .= " AND (u.first_name LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR u.last_name LIKE '%".mysqli_real_escape_string($conn,$search)."%') ";

// ================= FETCH TOTAL ROWS =================
$total = 0; $totalPages = 0; $studentsData = [];
$hasFilter = ($filterYear!='' || $filterSem!='' || $filterSubject!='' || $search!='');

if($hasFilter){
    $totRes = mysqli_query($conn,"
        SELECT COUNT(*) as total
        FROM student_subjects ss
        JOIN students st ON ss.student_id=st.student_id
        JOIN subjects su ON ss.subject_id=su.subject_id
        JOIN users u ON st.user_id=u.user_id
        LEFT JOIN courses c ON st.course_id=c.course_id
        LEFT JOIN rooms r ON su.room_id=r.room_id
        $where
    ");
    $total = $totRes ? mysqli_fetch_assoc($totRes)['total'] : 0;
    $totalPages = $limit>0 ? ceil($total/$limit) : 0;

    // ================= FETCH STUDENT DATA =================
    $res = mysqli_query($conn,"
        SELECT u.user_id, u.first_name, u.last_name, student_id_number AS student_id_number, 
               c.course_name, r.room_name, su.subject_name, su.semester, su.year, su.schedule_days
        FROM student_subjects ss
        JOIN students st ON ss.student_id=st.student_id
        JOIN subjects su ON ss.subject_id=su.subject_id
        JOIN users u ON st.user_id=u.user_id
        LEFT JOIN courses c ON st.course_id=c.course_id
        LEFT JOIN rooms r ON su.room_id=r.room_id
        $where
        ORDER BY u.last_name ASC, u.first_name ASC
        LIMIT $offset, $limit
    ");

    while($row = mysqli_fetch_assoc($res)) $studentsData[] = $row;
}

// ================= FETCH SEMESTERS & YEARS =================
$years = []; $res = mysqli_query($conn,"SELECT DISTINCT year FROM subjects WHERE subject_id IN (SELECT subject_id FROM student_subjects WHERE student_id='$student_id') ORDER BY year ASC");
while($r = mysqli_fetch_assoc($res)) $years[] = $r['year'];
$sems = ['1st Sem','2nd Sem','3rd Sem','Summer'];
?>

<main>
<div class="head-title">
  <div class="left">
    <h1>Student Subjects</h1>
    <ul class="breadcrumb">
      <li><a href="#">Home</a></li>
      <li><i class='bx bx-chevron-right'></i></li>
      <li><a class="active">Student Subject Information</a></li>
    </ul>
  </div>
</div>

<?= $statusMsg ?>

<div class="table-data">
<div class="order">

<div class="head flex-between">
<h3>Student List</h3>
</div>

<!-- FILTER + SEARCH + LIMIT -->
<div class="row-controls">
    <select id="subjectFilter" class="search-input">
        <option value="">All Subjects</option>
        <?php foreach($subjects as $s){ ?>
        <option value="<?= $s['subject_id'] ?>" <?= $filterSubject==$s['subject_id']?'selected':'' ?>><?= $s['subject_name'] ?></option>
        <?php } ?>
    </select>

    <select id="semFilter" class="search-input">
        <option value="">All Semester</option>
        <?php foreach($sems as $s){ ?>
        <option value="<?= $s ?>" <?= $filterSem==$s?'selected':'' ?>><?= $s ?></option>
        <?php } ?>
    </select>

    <select id="yearFilter" class="search-input">
        <option value="">All Year</option>
        <?php foreach($years as $y){ ?>
        <option value="<?= $y ?>" <?= $filterYear==$y?'selected':'' ?>><?= $y ?></option>
        <?php } ?>
    </select>

    <input type="search" id="searchInput" class="search-input" placeholder="Search student..." value="<?= htmlspecialchars($search) ?>">

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
<th>Student Full Name</th>
<th>Student ID Number</th>
<th>Course</th>
<th>Room</th>
<th>Subject</th>
<th>Semester</th>
<th>Schedule Days</th>
</tr>
</thead>
<tbody>
<?php
$sn = $offset + 1;
if(!$hasFilter){
  echo "<tr><td colspan='8' style='white-space:nowrap;'>Please select Subject / Semester / Year or search to view students.</td></tr>";
} else if(!$studentsData){
  echo "<tr><td colspan='8' class='text-center'>No Record Found!</td></tr>";
} else {
  foreach($studentsData as $row){
    $fullname = htmlspecialchars($row['first_name'].' '.$row['last_name']);
    echo "<tr>
    <td>{$sn}</td>
    <td>{$fullname}</td>
    <td>".htmlspecialchars($row['student_id_number'])."</td>
    <td>".htmlspecialchars($row['course_name'] ?? 'N/A')."</td>
    <td>".htmlspecialchars($row['room_name'] ?? 'N/A')."</td>
    <td>".htmlspecialchars($row['subject_name'] ?? 'N/A')."</td>
    <td>".htmlspecialchars($row['semester'] ?? 'N/A')."</td>
    <td>".htmlspecialchars($row['schedule_days'] ?? 'N/A')."</td>
    </tr>";
    $sn++;
  }
}
?>
</tbody>
</table>

<!-- PAGINATION -->
<?php if($hasFilter && $totalPages>0){ ?>
<div class="mt-15">
<?php if($page>1){ ?>
<a href="?page=<?= $page-1 ?>&limit=<?= $limit ?>&filterSubject=<?= $filterSubject ?>&filterYear=<?= $filterYear ?>&filterSem=<?= $filterSem ?>&search=<?= urlencode($search) ?>" class="btn small">Prev</a>
<?php } ?>
<?php for($i=1;$i<=$totalPages;$i++){ ?>
<a href="?page=<?= $i ?>&limit=<?= $limit ?>&filterSubject=<?= $filterSubject ?>&filterYear=<?= $filterYear ?>&filterSem=<?= $filterSem ?>&search=<?= urlencode($search) ?>" class="btn small <?= $i==$page?'primary':'' ?>"><?= $i ?></a>
<?php } ?>
<?php if($page<$totalPages){ ?>
<a href="?page=<?= $page+1 ?>&limit=<?= $limit ?>&filterSubject=<?= $filterSubject ?>&filterYear=<?= $filterYear ?>&filterSem=<?= $filterSem ?>&search=<?= urlencode($search) ?>" class="btn small">Next</a>
<?php } ?>
</div>
<?php } ?>

</div>
</div>
</main>

<script>
const searchInput = document.getElementById('searchInput');
const yearFilter = document.getElementById('yearFilter');
const semFilter  = document.getElementById('semFilter');
const subjectFilter = document.getElementById('subjectFilter');
const limitFilter = document.getElementById('limitFilter');

function reload(){
    const p = new URLSearchParams();
    if(searchInput.value) p.set('search', searchInput.value);
    if(yearFilter.value) p.set('filterYear', yearFilter.value);
    if(semFilter.value) p.set('filterSem', semFilter.value);
    if(subjectFilter.value) p.set('filterSubject', subjectFilter.value);
    if(limitFilter.value) p.set('limit', limitFilter.value);
    p.set('page', 1);
    window.location.search = p.toString();
}

searchInput.addEventListener('keyup', ()=>{ clearTimeout(window.timer); window.timer = setTimeout(reload, 500); });
[yearFilter, semFilter, subjectFilter, limitFilter].forEach(el=>el.addEventListener('change', reload));
</script>

<?php include 'stms_footer.php'; ?>
