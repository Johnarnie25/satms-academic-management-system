<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'header.php';

// ================= GET PARENT =================
$user_id = $_SESSION['user_id'] ?? 0;
$parent_id = 0;

$resP = mysqli_query($conn,"SELECT parent_id FROM parents WHERE user_id='$user_id' LIMIT 1");
if($resP && mysqli_num_rows($resP)>0){
    $parent_id = mysqli_fetch_assoc($resP)['parent_id'];
}

if($parent_id==0){
    die("<div class='alert alert-danger'>Parent account not found.</div>");
}

// ================= FETCH CHILDREN =================
$students = [];
$q = mysqli_query($conn,"
    SELECT st.student_id, u.first_name, u.last_name
    FROM students st
    LEFT JOIN users u ON st.user_id = u.user_id
    WHERE st.parent_id='$parent_id'
    ORDER BY u.last_name, u.first_name
");
while($row=mysqli_fetch_assoc($q)){
    $students[] = $row;
}

// ================= FILTERS =================
$student_id = $_GET['student_id'] ?? '';
$type = $_GET['type'] ?? 'all';
$single_date = $_GET['single_date'] ?? date("Y-m-d");
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';
$limit = intval($_GET['limit'] ?? 10);
$page = intval($_GET['page'] ?? 1);

if($limit<=0) $limit=10;
if($page<=0) $page=1;

$offset = ($page-1)*$limit;
$attendance = [];

// ================= FETCH ATTENDANCE DATA =================
if(!empty($student_id)){
    $where = "WHERE a.student_id='$student_id'";

    if($type=="single"){
        $where .= " AND a.attendance_date='$single_date'";
    }

    if($type=="range" && !empty($date_from) && !empty($date_to)){
        $where .= " AND a.attendance_date BETWEEN '$date_from' AND '$date_to'";
    }

    if(!empty($search)){
        $searchEsc = mysqli_real_escape_string($conn,$search);
        $where .= " AND s.subject_name LIKE '%$searchEsc%'";
    }

    // COUNT
    $countQ = mysqli_query($conn,"
        SELECT COUNT(*) as total
        FROM attendance a
        LEFT JOIN subjects s ON a.subject_id = s.subject_id
        $where
    ");
    $totalRows = mysqli_fetch_assoc($countQ)['total'];
    $totalPages = max(1,ceil($totalRows/$limit));

    // DATA
    $query = mysqli_query($conn,"
        SELECT
            s.subject_name,
            r.room_name,
            s.semester,
            a.attendance_date,
            a.status
        FROM attendance a
        LEFT JOIN subjects s ON a.subject_id = s.subject_id
        LEFT JOIN rooms r ON s.room_id = r.room_id
        $where
        ORDER BY a.attendance_date DESC
        LIMIT $offset,$limit
    ");
    while($row=mysqli_fetch_assoc($query)){
        $attendance[]=$row;
    }
}
?>

<main>
<div class="head-title">
<div class="left">
<h1>View Child Attendance</h1>
<ul class="breadcrumb">
<li><a href="#">Home</a></li>
<li><i class='bx bx-chevron-right'></i></li>
<li><a class="active">Attendance</a></li>
</ul>
</div>
</div>

<!-- ================= FILTER FORM ================= -->
<div class="table-data">
<div class="order">

<div class="head">
<h3>Select Child</h3>
</div>

<form method="GET" class="form-class">

<!-- Student Dropdown -->
<label>Select Child</label>
<select name="student_id" class="search-input" required>
    <option value="">-- Select Student --</option>
    <?php foreach($students as $s){ ?>
        <option value="<?= $s['student_id'] ?>" <?= ($student_id==$s['student_id']?'selected':'') ?>>
            <?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?>
        </option>
    <?php } ?>
</select>

<!-- Date Filters -->
<label>Select Date Filter</label>
<select name="type" id="typeSelect" class="search-input">
    <option value="all" <?= $type=="all"?'selected':'' ?>>All</option>
    <option value="single" <?= $type=="single"?'selected':'' ?>>By Single Date</option>
    <option value="range" <?= $type=="range"?'selected':'' ?>>By Date Range</option>
</select>

<input type="date" name="single_date" id="singleDate" class="search-input" value="<?= $single_date ?>">
<input type="date" name="date_from" id="dateFrom" class="search-input" value="<?= $date_from ?>">
<input type="date" name="date_to" id="dateTo" class="search-input" value="<?= $date_to ?>">

<br><br>
<button class="btn primary">Load</button>
</form>
</div>
</div>

<?php if(!empty($student_id)){ ?>
<div class="table-data">
<div class="order">

<div class="head">
<h3>Attendance Records</h3>
</div>

<!-- SEARCH & LIMIT -->
<div class="row-controls">
<input type="search" id="searchInput" class="search-input" placeholder="Search subject..." value="<?= htmlspecialchars($search); ?>">
<select id="limitSelect" class="search-input">
<?php foreach([5,10,20,50] as $opt){ ?>
<option value="<?= $opt ?>" <?= ($limit==$opt?'selected':'') ?>><?= $opt ?></option>
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
?>
<tr>
<td><?= $sn++ ?></td>
<td><?= $row['subject_name'] ?></td>
<td><?= $row['room_name'] ?></td>
<td><?= $row['semester'] ?></td>
<td><?= $row['attendance_date'] ?></td>
<td>
<?php
if($row['status']=="Present"){
    echo "<span class='status completed'>Present</span>";
}elseif($row['status']=="Absent"){
    echo "<span class='status pending'>Absent</span>";
}elseif($row['status']=="Late"){
    echo "<span class='status late'>Late</span>";
}
?>
</td>
</tr>
<?php } } else { ?>
<tr><td colspan="6" style="text-align:center;">No attendance records found.</td></tr>
<?php } ?>
</tbody>
</table>

<!-- PAGINATION -->
<div class="mt-15">
<?php if($page>1){ ?>
<a href="?student_id=<?= $student_id ?>&type=<?= $type ?>&page=<?= $page-1 ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>" class="btn small">Prev</a>
<?php } ?>
<?php for($i=1;$i<=$totalPages;$i++){ ?>
<a href="?student_id=<?= $student_id ?>&type=<?= $type ?>&page=<?= $i ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>" class="btn small <?= ($page==$i?'primary':'') ?>"><?= $i ?></a>
<?php } ?>
<?php if($page<$totalPages){ ?>
<a href="?student_id=<?= $student_id ?>&type=<?= $type ?>&page=<?= $page+1 ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>" class="btn small">Next</a>
<?php } ?>
</div>

</div>
</div>
<?php } ?>

</main>

<script>
// ---------------- Date toggle ----------------
const typeSelect = document.getElementById("typeSelect");
const singleDate = document.getElementById("singleDate");
const dateFrom = document.getElementById("dateFrom");
const dateTo = document.getElementById("dateTo");

function toggleDates(){
    if(typeSelect.value=="single"){
        singleDate.style.display="inline-block";
        dateFrom.style.display="none";
        dateTo.style.display="none";
    } else if(typeSelect.value=="range"){
        singleDate.style.display="none";
        dateFrom.style.display="inline-block";
        dateTo.style.display="inline-block";
    } else {
        singleDate.style.display="none";
        dateFrom.style.display="none";
        dateTo.style.display="none";
    }
}
toggleDates();
typeSelect.addEventListener("change",toggleDates);

// ---------------- Attendance search & limit ----------------
const searchInput = document.getElementById("searchInput");
const limitSelect = document.getElementById("limitSelect");

function applyFilters(){
    const student = document.querySelector('select[name="student_id"]').value;
    const type = typeSelect.value;
    window.location = "?student_id="+student+"&type="+type+"&search="+encodeURIComponent(searchInput.value)+"&limit="+limitSelect.value;
}
searchInput?.addEventListener("keyup",()=>setTimeout(applyFilters,500));
limitSelect?.addEventListener("change",applyFilters);
</script>

<?php include 'stms_footer.php'; ?>
