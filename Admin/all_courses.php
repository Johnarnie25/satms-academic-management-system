<?php include 'header.php'; ?>

<?php

// ================= FETCH COURSES =================
$courses = [];
$q = mysqli_query($conn, "SELECT * FROM courses ORDER BY course_name ASC");
while ($row = mysqli_fetch_assoc($q)) $courses[] = $row;

// ================= FILTERS =================
$filterCourse = $_GET['filterCourse'] ?? '';
$filterYear   = $_GET['filterYear'] ?? '';
$filterSem    = $_GET['filterSem'] ?? '';
$search       = $_GET['search'] ?? '';
$page         = max(1, intval($_GET['page'] ?? 1));
$limit        = intval($_GET['limit'] ?? 10);
$offset       = ($page-1)*$limit;

// ================= BUILD WHERE =================
$where = "WHERE 1=1 ";
if($filterCourse!='') $where .= " AND s.course_id='".intval($filterCourse)."' ";
if($filterYear!='') $where .= " AND s.year='".mysqli_real_escape_string($conn,$filterYear)."' ";
if($filterSem!='') $where .= " AND s.semester='".mysqli_real_escape_string($conn,$filterSem)."' ";
if($search!='') $where .= " AND s.subject_name LIKE '%".mysqli_real_escape_string($conn,$search)."%' ";

// ================= FETCH TOTAL ROWS =================
$total = 0; $totalPages = 0; $subjects = [];
$hasFilter = ($filterCourse!='' || $filterYear!='' || $filterSem!='' || $search!='');

if($hasFilter){
    $totRes = mysqli_query($conn,"
        SELECT COUNT(*) as total
        FROM subjects s
        $where
    ");
    $total = $totRes ? mysqli_fetch_assoc($totRes)['total'] : 0;
    $totalPages = $limit>0 ? ceil($total/$limit) : 0;

    // ================= FETCH SUBJECT DATA =================
    $res = mysqli_query($conn,"
        SELECT s.subject_id, s.subject_name, s.semester, s.year,
               c.course_name, r.room_name, t.teacher_id, u.first_name, u.last_name
        FROM subjects s
        LEFT JOIN courses c ON s.course_id = c.course_id
        LEFT JOIN teachers t ON s.teacher_id = t.teacher_id
        LEFT JOIN users u ON t.user_id = u.user_id
        LEFT JOIN rooms r ON s.room_id = r.room_id
        $where
        ORDER BY s.subject_name ASC
        LIMIT $offset, $limit
    ");
    while($row = mysqli_fetch_assoc($res)){
        $subjects[] = $row;
    }
}

// ================= FETCH SEMESTERS & YEARS =================
$years = []; $res = mysqli_query($conn,"SELECT DISTINCT year FROM subjects ORDER BY year ASC");
while($r = mysqli_fetch_assoc($res)) $years[] = $r['year'];

$sems = ['1st Sem','2nd Sem','3rd Sem','Summer']; // can add more dynamically if needed
?>

<main>
<div class="head-title">
  <div class="left">
    <h1>Subjects Per Course</h1>
    <ul class="breadcrumb">
      <li><a href="#">Home</a></li>
      <li><i class='bx bx-chevron-right'></i></li>
      <li><a class="active">Subject Data Information</a></li>
    </ul>
  </div>
</div>

<?= $statusMsg ?>

<div class="table-data">
<div class="order">

<div class="head flex-between">
<h3>All Subjects</h3>
</div>

<!-- FILTER + SEARCH + LIMIT -->
<div class="row-controls">
    <select id="courseFilter" class="search-input">
        <option value="">All Courses</option>
        <?php foreach($courses as $c){ ?>
        <option value="<?= $c['course_id'] ?>" <?= $filterCourse==$c['course_id']?'selected':'' ?>><?= $c['course_name'] ?></option>
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

    <input type="search" id="searchInput" class="search-input" placeholder="Search subject..." value="<?= htmlspecialchars($search) ?>">

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
<th>Subject Name</th>
<th>Course</th>
<th>Teacher</th>
<th>Room</th>
<th>Semester</th>
<th>Year</th>
</tr>
</thead>
<tbody>
<?php
$sn = $offset + 1;
if(!$hasFilter){
  echo "<tr><td colspan='7' style='white-space:nowrap;'>Please select Course / Semester / Year or use search to view data.</td></tr>";
} else if(!$subjects){
  echo "<tr><td colspan='7' class='text-center'>No Record Found!</td></tr>";
} else {
  foreach($subjects as $row){
    echo "<tr>
    <td>{$sn}</td>
    <td>".htmlspecialchars($row['subject_name'])."</td>
    <td>".htmlspecialchars($row['course_name'])."</td>
    <td>".htmlspecialchars($row['first_name'].' '.$row['last_name'])."</td>
    <td>".htmlspecialchars($row['room_name'] ?? 'N/A')."</td>
    <td>".htmlspecialchars($row['semester'] ?? 'N/A')."</td>
    <td>".htmlspecialchars($row['year'] ?? 'N/A')."</td>
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
<a href="?page=<?= $page-1 ?>&limit=<?= $limit ?>&filterCourse=<?= $filterCourse ?>&filterYear=<?= $filterYear ?>&filterSem=<?= $filterSem ?>&search=<?= urlencode($search) ?>" class="btn small">Prev</a>
<?php } ?>
<?php for($i=1;$i<=$totalPages;$i++){ ?>
<a href="?page=<?= $i ?>&limit=<?= $limit ?>&filterCourse=<?= $filterCourse ?>&filterYear=<?= $filterYear ?>&filterSem=<?= $filterSem ?>&search=<?= urlencode($search) ?>" class="btn small <?= $i==$page?'primary':'' ?>"><?= $i ?></a>
<?php } ?>
<?php if($page<$totalPages){ ?>
<a href="?page=<?= $page+1 ?>&limit=<?= $limit ?>&filterCourse=<?= $filterCourse ?>&filterYear=<?= $filterYear ?>&filterSem=<?= $filterSem ?>&search=<?= urlencode($search) ?>" class="btn small">Next</a>
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
const courseFilter = document.getElementById('courseFilter');
const limitFilter = document.getElementById('limitFilter');

function reload(){
    const p = new URLSearchParams();
    if(searchInput.value) p.set('search', searchInput.value);
    if(yearFilter.value) p.set('filterYear', yearFilter.value);
    if(semFilter.value) p.set('filterSem', semFilter.value);
    if(courseFilter.value) p.set('filterCourse', courseFilter.value);
    if(limitFilter.value) p.set('limit', limitFilter.value);
    p.set('page', 1);
    window.location.search = p.toString();
}

// debounce search
searchInput.addEventListener('keyup', ()=>{ clearTimeout(window.timer); window.timer = setTimeout(reload, 500); });
[yearFilter, semFilter, courseFilter, limitFilter].forEach(el=>el.addEventListener('change', reload));
</script>

<?php include 'stms_footer.php'; ?>
