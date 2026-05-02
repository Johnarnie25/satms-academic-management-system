<?php include 'header.php'; ?>

<?php

// ================= FILTERS =================
$filterStatus = $_GET['filterStatus'] ?? '';
$filterDept   = $_GET['filterDept'] ?? '';
$search       = $_GET['search'] ?? '';
$page         = max(1, intval($_GET['page'] ?? 1));
$limit        = intval($_GET['limit'] ?? 10);
$offset       = ($page-1)*$limit;

// ================= BUILD WHERE =================
$where = "WHERE 1=1 ";
if($filterStatus!='') $where .= " AND t.status='".mysqli_real_escape_string($conn,$filterStatus)."' ";
if($filterDept!='')   $where .= " AND c.department_name='".mysqli_real_escape_string($conn,$filterDept)."' ";
if($search!='')       $where .= " AND (u.first_name LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR u.last_name LIKE '%".mysqli_real_escape_string($conn,$search)."%') ";

// ================= FETCH TOTAL ROWS =================
$total = 0; $totalPages = 0; $teachers = [];
$statuses = ['Active','Inactive']; // for dropdown filter

// ================= FETCH DEPARTMENTS FROM COURSES =================
$departments = [];
$depRes = mysqli_query($conn,"SELECT DISTINCT department_name FROM courses WHERE department_name IS NOT NULL AND department_name <> '' ORDER BY department_name ASC");
while($row = mysqli_fetch_assoc($depRes)) $departments[] = $row['department_name'];

// ================= FETCH TOTAL TEACHERS =================
$totRes = mysqli_query($conn,"
    SELECT COUNT(*) as total
    FROM teachers t
    LEFT JOIN users u ON t.user_id = u.user_id
    LEFT JOIN courses c ON t.course_id = c.course_id
    $where
");
$total = $totRes ? mysqli_fetch_assoc($totRes)['total'] : 0;
$totalPages = $limit>0 ? ceil($total/$limit) : 0;

// ================= FETCH TEACHERS =================
$res = mysqli_query($conn,"
    SELECT t.teacher_id, t.status, t.course_id,
           u.first_name, u.last_name, u.email, u.contact_number,
           c.department_name
    FROM teachers t
    LEFT JOIN users u ON t.user_id = u.user_id
    LEFT JOIN courses c ON t.course_id = c.course_id
    $where
    ORDER BY u.first_name ASC
    LIMIT $offset, $limit
");
while($row = mysqli_fetch_assoc($res)) $teachers[] = $row;

?>

<main>
<div class="head-title">
  <div class="left">
    <h1>All Teachers</h1>
    <ul class="breadcrumb">
      <li><a href="#">Home</a></li>
      <li><i class='bx bx-chevron-right'></i></li>
      <li><a class="active">Teacher Information</a></li>
    </ul>
  </div>
</div>

<div class="table-data">
<div class="order">

<div class="head flex-between">
<h3>Teacher List</h3>
</div>

<!-- FILTER + SEARCH + LIMIT -->
<div class="row-controls">
    <select id="statusFilter" class="search-input">
        <option value="">All Status</option>
        <?php foreach($statuses as $s){ ?>
        <option value="<?= $s ?>" <?= $filterStatus==$s?'selected':'' ?>><?= $s ?></option>
        <?php } ?>
    </select>

    <select id="deptFilter" class="search-input">
        <option value="">All Departments</option>
        <?php foreach($departments as $d){ ?>
        <option value="<?= htmlspecialchars($d) ?>" <?= $filterDept==$d?'selected':'' ?>>
            <?= htmlspecialchars($d) ?>
        </option>
        <?php } ?>
    </select>

    <input type="search" id="searchInput" class="search-input" placeholder="Search teacher..." value="<?= htmlspecialchars($search) ?>">

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
<th>Name</th>
<th>Email</th>
<th>Contact</th>
<th>Department</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php
$sn = $offset + 1;
if(!$teachers){
  echo "<tr><td colspan='6' class='text-center'>No Record Found!</td></tr>";
} else {
  foreach($teachers as $row){
    $deptName = $row['department_name'] ?? 'N/A';
    echo "<tr>
    <td>{$sn}</td>
    <td>".htmlspecialchars($row['first_name'].' '.$row['last_name'])."</td>
    <td>".htmlspecialchars($row['email'] ?? 'N/A')."</td>
    <td>".htmlspecialchars($row['contact_number'] ?? 'N/A')."</td>
    <td>".htmlspecialchars($deptName)."</td>
    <td>".htmlspecialchars($row['status'])."</td>
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
<a href="?page=<?= $page-1 ?>&limit=<?= $limit ?>&filterStatus=<?= urlencode($filterStatus) ?>&filterDept=<?= urlencode($filterDept) ?>&search=<?= urlencode($search) ?>" class="btn small">Prev</a>
<?php } ?>
<?php for($i=1;$i<=$totalPages;$i++){ ?>
<a href="?page=<?= $i ?>&limit=<?= $limit ?>&filterStatus=<?= urlencode($filterStatus) ?>&filterDept=<?= urlencode($filterDept) ?>&search=<?= urlencode($search) ?>" class="btn small <?= $i==$page?'primary':'' ?>"><?= $i ?></a>
<?php } ?>
<?php if($page<$totalPages){ ?>
<a href="?page=<?= $page+1 ?>&limit=<?= $limit ?>&filterStatus=<?= urlencode($filterStatus) ?>&filterDept=<?= urlencode($filterDept) ?>&search=<?= urlencode($search) ?>" class="btn small">Next</a>
<?php } ?>
</div>
<?php } ?>

</div>
</div>
</main>

<script>
const searchInput = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
const deptFilter = document.getElementById('deptFilter');
const limitFilter = document.getElementById('limitFilter');

function reload(){
    const p = new URLSearchParams();
    if(searchInput.value) p.set('search', searchInput.value);
    if(statusFilter.value) p.set('filterStatus', statusFilter.value);
    if(deptFilter.value) p.set('filterDept', deptFilter.value);
    if(limitFilter.value) p.set('limit', limitFilter.value);
    p.set('page', 1);
    window.location.search = p.toString();
}

// debounce search
searchInput.addEventListener('keyup', ()=>{ clearTimeout(window.timer); window.timer = setTimeout(reload, 500); });
[statusFilter, deptFilter, limitFilter].forEach(el=>el.addEventListener('change', reload));
</script>

<?php include 'stms_footer.php'; ?>
