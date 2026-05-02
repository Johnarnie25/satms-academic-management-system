<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'header.php';

$user_id = $_SESSION['user_id'] ?? 0;
if(!$user_id){
    die("<div class='alert alert-danger'>Parent not logged in.</div>");
}

// ================= GET PARENT ID =================
$parentQuery = mysqli_query($conn, "SELECT parent_id FROM parents WHERE user_id='".intval($user_id)."' LIMIT 1");
if(!$parentQuery || mysqli_num_rows($parentQuery)==0){
    die("<div class='alert alert-danger'>Parent account not found.</div>");
}
$parent_id = mysqli_fetch_assoc($parentQuery)['parent_id'];

// ================= GET STUDENTS FOR THIS PARENT =================
$students = [];
$resStu = mysqli_query($conn, "SELECT student_id, user_id FROM students WHERE parent_id='".intval($parent_id)."'");
if($resStu && mysqli_num_rows($resStu) > 0){
    while($s = mysqli_fetch_assoc($resStu)){
        $students[] = $s;
    }
}else{
    die("<div class='alert alert-danger'>No student account found for this parent.</div>");
}

// ================= GET FILTERS =================
$student_id = $_GET['student_id'] ?? $students[0]['student_id']; // default first student
$subject_id = $_GET['subject_id'] ?? '';
$search     = $_GET['search'] ?? '';
$limit      = intval($_GET['limit'] ?? 10);
$page       = intval($_GET['page'] ?? 1);
if($limit<=0) $limit=10;
if($page<=0) $page=1;
$offset = ($page-1)*$limit;

// ================= SUBJECTS FOR SELECT =================
$subjects = [];
$resSub = mysqli_query($conn,"
    SELECT s.subject_id,s.subject_name,s.year,s.semester
    FROM student_subjects ss
    JOIN subjects s ON ss.subject_id=s.subject_id
    WHERE ss.student_id='".intval($student_id)."'
    ORDER BY s.subject_name ASC
");
while($sub = mysqli_fetch_assoc($resSub)){
    $subjects[] = $sub;
}

// ================= WHERE CLAUSE =================
$where = "WHERE a.student_id='".intval($student_id)."'";
if(!empty($subject_id)) $where .= " AND a.subject_id='".intval($subject_id)."'";
if(!empty($search)){
    $searchEsc = mysqli_real_escape_string($conn, $search);
    $where .= " AND a.attendance_date LIKE '%$searchEsc%'";
}

// ================= COUNT TOTAL ROWS =================
$countQuery = mysqli_query($conn,"SELECT COUNT(*) as total FROM attendance a $where");
$totalRows = $countQuery ? intval(mysqli_fetch_assoc($countQuery)['total']) : 0;
$totalPages = max(1, ceil($totalRows/$limit));

// ================= FETCH ATTENDANCE =================
$attendance = [];
$query = mysqli_query($conn,"
    SELECT a.attendance_date,a.status,s.subject_name,s.semester,r.room_name
    FROM attendance a
    LEFT JOIN subjects s ON a.subject_id=s.subject_id
    LEFT JOIN rooms r ON s.room_id=r.room_id
    $where
    ORDER BY a.attendance_date DESC
    LIMIT $offset,$limit
");
while($row = mysqli_fetch_assoc($query)){
    $attendance[] = $row;
}
?>

<main>
<div class="head-title">
    <div class="left">
        <h1>My Student Attendance</h1>
        <ul class="breadcrumb">
            <li><a href="#">Home</a></li>
            <li><i class='bx bx-chevron-right'></i></li>
            <li><a class="active">Attendance</a></li>
        </ul>
    </div>
</div>

<div class="table-data">
<div class="order">
<div class="head">
<h3>Attendance Records</h3>
</div>

<div class="row-controls">
    <!-- STUDENT DROPDOWN -->
<select id="studentFilter" class="search-input">
    <option value="">All Students</option>
    <?php
    // Get all students assigned to this parent
    $studentQuery = mysqli_query($conn,"
        SELECT st.student_id, u.first_name, u.last_name
        FROM students st
        LEFT JOIN users u ON st.user_id = u.user_id
        WHERE st.parent_id = '$parent_id'
        ORDER BY u.first_name, u.last_name
    ");

    while($stu = mysqli_fetch_assoc($studentQuery)) {
        $selected = ($stu['student_id'] == $student_id) ? 'selected' : '';
        echo "<option value='{$stu['student_id']}' $selected>";
        echo htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']);
        echo "</option>";
    }
    ?>
</select>


    <select id="subjectFilter" class="search-input">
        <option value="">All Subjects</option>
        <?php foreach($subjects as $sub){ ?>
            <option value="<?= $sub['subject_id'] ?>" <?= $sub['subject_id']==$subject_id?'selected':'' ?>>
                <?= $sub['subject_name'] ?> (<?= $sub['year'].' - '.$sub['semester'] ?>)
            </option>
        <?php } ?>
    </select>

    <input type="search" id="searchInput" class="search-input" placeholder="Search date..." value="<?= htmlspecialchars($search) ?>">

    <select id="limitSelect" class="search-input">
        <?php foreach([5,10,20,50] as $opt){ ?>
            <option value="<?= $opt ?>" <?= $opt==$limit?'selected':'' ?>><?= $opt ?></option>
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
$sn = $offset+1;
if(!empty($attendance)){
    foreach($attendance as $row){
        ?>
        <tr>
            <td><?= $sn++ ?></td>
            <td><?= htmlspecialchars($row['subject_name']) ?></td>
            <td><?= htmlspecialchars($row['room_name']) ?></td>
            <td><?= htmlspecialchars($row['semester']) ?></td>
            <td><?= htmlspecialchars($row['attendance_date']) ?></td>
            <td>
                <?php
                if($row['status']=='Present') echo "<span class='status completed'>Present</span>";
                elseif($row['status']=='Absent') echo "<span class='status pending'>Absent</span>";
                elseif($row['status']=='Late') echo "<span class='status process'>Late</span>";
                else echo "<span class='status process'>Unknown</span>";
                ?>
            </td>
        </tr>
        <?php
    }
}else{
    echo "<tr><td colspan='6' style='text-align:center;'>No attendance records found.</td></tr>";
}
?>
</tbody>
</table>

<div class="mt-15">
<?php if($page>1){ ?>
<a href="?student_id=<?= $student_id ?>&subject_id=<?= $subject_id ?>&search=<?= urlencode($search) ?>&limit=<?= $limit ?>&page=<?= $page-1 ?>" class="btn small">Prev</a>
<?php } ?>
<?php for($i=1;$i<=$totalPages;$i++){ ?>
<a href="?student_id=<?= $student_id ?>&subject_id=<?= $subject_id ?>&search=<?= urlencode($search) ?>&limit=<?= $limit ?>&page=<?= $i ?>" class="btn small <?= $i==$page?'primary':'' ?>"><?= $i ?></a>
<?php } ?>
<?php if($page<$totalPages){ ?>
<a href="?student_id=<?= $student_id ?>&subject_id=<?= $subject_id ?>&search=<?= urlencode($search) ?>&limit=<?= $limit ?>&page=<?= $page+1 ?>" class="btn small">Next</a>
<?php } ?>
</div>

</div>
</div>
</main>

<script>
const searchInput = document.getElementById('searchInput');
const studentFilter = document.getElementById('studentFilter');
const subjectFilter = document.getElementById('subjectFilter');
const limitSelect = document.getElementById('limitSelect');

function applyFilters(){
    const params = new URLSearchParams();
    if(searchInput.value) params.set('search', searchInput.value);
    if(studentFilter.value) params.set('student_id', studentFilter.value);
    if(subjectFilter.value) params.set('subject_id', subjectFilter.value);
    params.set('limit', limitSelect.value);
    params.set('page', 1);
    window.location.search = params.toString();
}

searchInput.addEventListener('keyup', ()=>{clearTimeout(window.timer); window.timer=setTimeout(applyFilters,500);});
[studentFilter, subjectFilter, limitSelect].forEach(el=>el.addEventListener('change', applyFilters));
</script>

<?php include 'stms_footer.php'; ?>
