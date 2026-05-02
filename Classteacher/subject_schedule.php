<?php include 'header.php'; ?>

<?php


// logged-in user's ID
$user_id = $_SESSION['user_id'] ?? 0;

// ================= GET TEACHER ID =================
$teacher_id = 0;
$resT = mysqli_query($conn, "SELECT teacher_id FROM teachers WHERE user_id='".intval($user_id)."' LIMIT 1");
if($resT && mysqli_num_rows($resT) > 0){
    $teacher_id = mysqli_fetch_assoc($resT)['teacher_id'];
}

// ================= FILTERS =================
$filterYear = $_GET['filterYear'] ?? '';
$filterSem  = $_GET['filterSem'] ?? '';
$search     = $_GET['search'] ?? '';
$page       = max(1, intval($_GET['page'] ?? 1));
$limit      = intval($_GET['limit'] ?? 10);
$offset     = ($page-1)*$limit;

// ================= BUILD WHERE =================
$where = "WHERE teacher_id='".intval($teacher_id)."'";
if($filterYear!='') $where .= " AND year='".mysqli_real_escape_string($conn,$filterYear)."'";
if($filterSem!='')  $where .= " AND semester='".mysqli_real_escape_string($conn,$filterSem)."'";
if($search!='')     $where .= " AND subject_name LIKE '%".mysqli_real_escape_string($conn,$search)."%'";
  
// ================= FETCH TOTAL ROWS =================
$total = 0; $totalPages = 0; $subjectData = [];

$totRes = mysqli_query($conn,"
    SELECT COUNT(*) as total
    FROM subjects
    $where
");
$total = $totRes ? mysqli_fetch_assoc($totRes)['total'] : 0;
$totalPages = $limit>0 ? ceil($total/$limit) : 0;

// ================= FETCH SUBJECT DATA =================
$res = mysqli_query($conn,"
    SELECT subject_id, subject_name, semester, year, schedule_days, schedule_time
    FROM subjects
    $where
    ORDER BY subject_name ASC
    LIMIT $offset, $limit
");
while($row = mysqli_fetch_assoc($res)) $subjectData[] = $row;

// ================= FETCH SEMESTERS & YEARS =================
$years = []; 
$res = mysqli_query($conn,"SELECT DISTINCT year FROM subjects WHERE teacher_id='".intval($teacher_id)."' ORDER BY year ASC");
while($r = mysqli_fetch_assoc($res)) $years[] = $r['year'];

$sems = ['1st Sem','2nd Sem','3rd Sem','Summer'];
?>


<main>
<div class="head-title">
  <div class="left">
    <h1>My Subject Schedule</h1>
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
<h3>Subject List</h3>
</div>

<!-- FILTER + SEARCH + LIMIT -->
<div class="row-controls">
    <select id="yearFilter" class="search-input">
        <option value="">All Year</option>
        <?php foreach($years as $y){ ?>
        <option value="<?= $y ?>" <?= $filterYear==$y?'selected':'' ?>><?= $y ?></option>
        <?php } ?>
    </select>

    <select id="semFilter" class="search-input">
        <option value="">All Semester</option>
        <?php foreach($sems as $s){ ?>
        <option value="<?= $s ?>" <?= $filterSem==$s?'selected':'' ?>><?= $s ?></option>
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
<th>Semester</th>
<th>Year</th>
<th>Schedule Days</th>
<th>Schedule Time</th>
</tr>
</thead>
<tbody>
<?php
$sn = $offset + 1;
if(!$subjectData){
  echo "<tr><td colspan='6' class='text-center'>No Record Found!</td></tr>";
} else {
  foreach($subjectData as $row){
    echo "<tr>
    <td>{$sn}</td>
    <td>".htmlspecialchars($row['subject_name'])."</td>
    <td>".htmlspecialchars($row['semester'])."</td>
    <td>".htmlspecialchars($row['year'])."</td>
    <td>".htmlspecialchars($row['schedule_days'])."</td>
    <td>".htmlspecialchars($row['schedule_time'])."</td>
    </tr>";
    $sn++;
  }
}
?>
</tbody>
</table>

<!-- PAGINATION -->
<?php if($totalPages>0){ ?>
<div class="mt-15">
<?php if($page>1){ ?>
<a href="?page=<?= $page-1 ?>&limit=<?= $limit ?>&filterYear=<?= $filterYear ?>&filterSem=<?= $filterSem ?>&search=<?= urlencode($search) ?>" class="btn small">Prev</a>
<?php } ?>
<?php for($i=1;$i<=$totalPages;$i++){ ?>
<a href="?page=<?= $i ?>&limit=<?= $limit ?>&filterYear=<?= $filterYear ?>&filterSem=<?= $filterSem ?>&search=<?= urlencode($search) ?>" class="btn small <?= $i==$page?'primary':'' ?>"><?= $i ?></a>
<?php } ?>
<?php if($page<$totalPages){ ?>
<a href="?page=<?= $page+1 ?>&limit=<?= $limit ?>&filterYear=<?= $filterYear ?>&filterSem=<?= $filterSem ?>&search=<?= urlencode($search) ?>" class="btn small">Next</a>
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
const limitFilter = document.getElementById('limitFilter');

function reload(){
    const p = new URLSearchParams();
    if(searchInput.value) p.set('search', searchInput.value);
    if(yearFilter.value) p.set('filterYear', yearFilter.value);
    if(semFilter.value) p.set('filterSem', semFilter.value);
    if(limitFilter.value) p.set('limit', limitFilter.value);
    p.set('page', 1);
    window.location.search = p.toString();
}

searchInput.addEventListener('keyup', ()=>{ clearTimeout(window.timer); window.timer = setTimeout(reload, 500); });
[yearFilter, semFilter, limitFilter].forEach(el=>el.addEventListener('change', reload));
</script>

<?php include 'stms_footer.php'; ?>
