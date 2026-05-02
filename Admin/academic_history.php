<?php 
include 'header.php'; 

$statusMsg = "";

/* ================= ASSIGN STUDENT TO ACADEMIC HISTORY ================= */
if(isset($_POST['assign'])){
    $selectedStudents = $_POST['student_ids'] ?? [];
    $courseId  = intval($_POST['course_id']);
    $yearLevel = $_POST['year'];
    $semester  = $_POST['semester'];
    $schoolYear = trim($_POST['school_year'] ?? '');
    $status    = $_POST['status'] ?? 'Enrolled';
    $remarks   = trim($_POST['remarks'] ?? '');
    $transferSchool = trim($_POST['transfer_school'] ?? '');
    $transferReason = trim($_POST['transfer_reason'] ?? '');

    if(!$selectedStudents || !$courseId || !$yearLevel || !$semester || !$schoolYear || !$status){
        $statusMsg = "<div class='alert alert-danger'>Please complete all required fields and select at least one student.</div>";
    } else {
        $assignedTotal = 0;
        foreach($selectedStudents as $studentId){
            $studentId = intval($studentId);

            // Check if already assigned for same course/year/semester/school year
            $check = mysqli_query($conn,"
                SELECT * FROM academic_history
                WHERE student_id='$studentId'
                AND course_id='$courseId'
                AND year_level='$yearLevel'
                AND semester='$semester'
                AND school_year='$schoolYear'
            ");
            if(mysqli_num_rows($check)==0){
                mysqli_query($conn," 
    INSERT INTO academic_history(
        student_id, course_id, year_level, semester, school_year, status, remarks, transfer_school, transfer_reason, recorded_by
    ) VALUES (
        '$studentId',
        '$courseId',
        '$yearLevel',
        '$semester',
        '".mysqli_real_escape_string($conn,$schoolYear)."',
        '$status',
        '".mysqli_real_escape_string($conn,$remarks)."',
        '".mysqli_real_escape_string($conn,$transferSchool)."',
        '".mysqli_real_escape_string($conn,$transferReason)."',
        '".mysqli_real_escape_string($conn,$_POST['recorded_by'] ?? '')."'
    )
");

                $assignedTotal++;
            }
        }
        $statusMsg = "<div class='alert alert-success'>$assignedTotal record(s) assigned successfully!</div>";
    }
}

/* ================= FETCH STUDENTS ================= */
$studentResult = mysqli_query($conn,"
    SELECT s.student_id, u.first_name, u.last_name, u.email
    FROM students s
    INNER JOIN users u ON s.user_id=u.user_id
    ORDER BY u.first_name ASC
");

/* ================= FETCH COURSES ================= */
$courses = mysqli_query($conn,"SELECT * FROM courses ORDER BY course_name ASC");
$years = ['1st Year','2nd Year','3rd Year','4th Year'];
$semesters = ['1st Sem','2nd Sem','3rd Sem','Summer'];
$statuses = ['Enrolled','Dropped','Transferred'];

/* ================= FILTER & PAGINATION ================= */
$search    = $_GET['search'] ?? '';
$filterCourse = $_GET['course_id'] ?? '';
$filterYear   = $_GET['year'] ?? '';
$filterSem    = $_GET['semester'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterSY     = $_GET['school_year'] ?? '';

$where = "WHERE 1";
if($search){
    $searchEsc = mysqli_real_escape_string($conn, $search);
    $where .= " AND (u.first_name LIKE '%$searchEsc%' OR u.last_name LIKE '%$searchEsc%')";
}
if($filterCourse){
    $filterCourse = intval($filterCourse);
    $where .= " AND ah.course_id='$filterCourse'";
}
if($filterYear){
    $filterYear = mysqli_real_escape_string($conn, $filterYear);
    $where .= " AND ah.year_level='$filterYear'";
}
if($filterSem){
    $filterSem = mysqli_real_escape_string($conn, $filterSem);
    $where .= " AND ah.semester='$filterSem'";
}
if($filterStatus){
    $filterStatus = mysqli_real_escape_string($conn, $filterStatus);
    $where .= " AND ah.status='$filterStatus'";
}
if($filterSY){
    $filterSY = mysqli_real_escape_string($conn, $filterSY);
    $where .= " AND ah.school_year='$filterSY'";
}

// Pagination
$limit = intval($_GET['limit'] ?? 10);
$page  = intval($_GET['page'] ?? 1);
if($page<1) $page=1;
$offset = ($page-1)*$limit;

// Count total assigned
$totalRes = mysqli_query($conn,"
    SELECT COUNT(*) AS total
    FROM academic_history ah
    INNER JOIN students s ON ah.student_id=s.student_id
    INNER JOIN users u ON s.user_id=u.user_id
    INNER JOIN courses c ON ah.course_id=c.course_id
    $where
");
$totalRow = mysqli_fetch_assoc($totalRes);
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $limit);

// Fetch assigned academic history
$assigned = mysqli_query($conn,"
    SELECT ah.*, CONCAT(u.first_name,' ',u.last_name) as student_name, c.course_name,
           r.first_name AS recorder_fname, r.last_name AS recorder_lname
    FROM academic_history ah
    INNER JOIN students s ON ah.student_id=s.student_id
    INNER JOIN users u ON s.user_id=u.user_id
    INNER JOIN courses c ON ah.course_id=c.course_id
    LEFT JOIN users r ON ah.recorded_by=r.user_id  -- Join recorder
    $where
    ORDER BY u.first_name ASC
    LIMIT $offset, $limit
");

?>

<main>
<div class="head-title">
    <div class="left">
        <h1>Assign Academic History</h1>
        <ul class="breadcrumb">
            <li><a href="#">Home</a></li>
            <li><i class='bx bx-chevron-right'></i></li>
            <li><a class="active">Academic History</a></li>
        </ul>
    </div>
</div>

<!-- ================= ASSIGN FORM ================= -->
<div class="table-data">
<div class="order">
<div class="head"><h3>Assign Academic History Automatically</h3></div>

<form method="POST" class="form-class">

<?= $statusMsg ?>

<!-- Student Search & Checkbox -->
<label>Select Students</label>
<input type="text" id="studentSearch" class="search-input" placeholder="Search students...">
<div id="studentControls" style="margin-top:6px;margin-bottom:6px;display:flex;gap:8px;align-items:center;">
    <label style="font-size:13px;"></label>
    <select id="studentLimit" class="search-input" style="width:80px;">
        <option value="5">5</option>
        <option value="10" selected>10</option>
        <option value="25">25</option>
        <option value="50">50</option>
    </select>
    <div id="studentPager" style="margin-left:auto"></div>
</div>
<div id="studentCheckboxes" style="display:none;">
<?php while($s=mysqli_fetch_assoc($studentResult)){ ?>
    <div class="student-item">
        <input type="checkbox" name="student_ids[]" value="<?= $s['student_id'] ?>" id="student_<?= $s['student_id'] ?>">
        <label for="student_<?= $s['student_id'] ?>"><?= htmlspecialchars($s['first_name'].' '.$s['last_name'].' ('.$s['email'].')') ?></label>
    </div>
<?php } ?>
</div>
<div id="studentSearchResults" class="border p-3" style="max-height:220px; overflow-y:auto;"></div>

<br>

<select name="course_id" class="search-input" required>
    <option value="">Select Course</option>
    <?php mysqli_data_seek($courses, 0);
    while($c=mysqli_fetch_assoc($courses)){ ?>
        <option value="<?= $c['course_id'] ?>"><?= $c['course_name'] ?></option>
    <?php } ?>
</select>

<select name="year" class="search-input" required>
    <option value="">Select Year Level</option>
    <?php foreach($years as $y){ ?>
        <option value="<?= $y ?>"><?= $y ?></option>
    <?php } ?>
</select>

<select name="semester" class="search-input" required>
    <option value="">Select Semester</option>
    <?php foreach($semesters as $s){ ?>
        <option value="<?= $s ?>"><?= $s ?></option>
    <?php } ?>
</select>

<input type="text" name="school_year" class="search-input" placeholder="Enter School Year" required>

<select name="status" id="statusSelect" class="search-input" required>
    <option value="">Select Status</option>
    <?php foreach($statuses as $st){ ?>
        <option value="<?= $st ?>"><?= $st ?></option>
    <?php } ?>
</select>

<!-- TRANSFER FIELDS (hidden initially) -->
<div id="transferFields" style="display:none; margin-top:6px;">
    <input type="text" name="transfer_school" class="search-input" placeholder="Transfer School">
    <input type="text" name="transfer_reason" class="search-input" placeholder="Transfer Reason">
</div>

<!-- Remarks (dropdown with default) -->
<select name="remarks" class="search-input">
    <option value="">Select Remarks</option>
    <option value="Complete">Complete</option>
    <option value="Incomplete">Incomplete</option>
    <option value="Pending">Pending</option>
</select>

<!-- Recorded By -->
<select name="recorded_by" class="search-input" required>
    <option value="">Select Recorder</option>
    <?php
    // Fetch only admin and teacher users
    $usersRes = mysqli_query($conn,"
        SELECT user_id, CONCAT(first_name,' ',last_name) AS name
        FROM users
        WHERE role IN ('Admin','Teacher')
        ORDER BY first_name ASC
    ");
    while($u = mysqli_fetch_assoc($usersRes)){
        echo '<option value="'.$u['user_id'].'">'.htmlspecialchars($u['name']).'</option>';
    }
    ?>
</select>



<br><br>
<button name="assign" class="btn primary">Assign Academic History</button>

</form>
</div>
</div>

<!-- ================= ASSIGNED TABLE ================= -->
<div class="table-data">
<div class="order">
<div class="head flex-between"><h3>Assigned Academic History</h3></div>

<div class="row-controls" style="margin-bottom:10px;">
    <input type="search" id="assignedSearch" class="search-input" placeholder="Search student..." value="<?= htmlspecialchars($search) ?>">
    <select id="assignedCourseFilter" class="search-input">
        <option value="">All Courses</option>
        <?php mysqli_data_seek($courses,0); while($c=mysqli_fetch_assoc($courses)){ ?>
            <option value="<?= $c['course_id'] ?>" <?= ($filterCourse==$c['course_id']?'selected':'') ?>><?= $c['course_name'] ?></option>
        <?php } ?>
    </select>
    <select id="assignedYearFilter" class="search-input">
        <option value="">All Years</option>
        <?php foreach($years as $y){ ?>
            <option value="<?= $y ?>" <?= ($filterYear==$y?'selected':'') ?>><?= $y ?></option>
        <?php } ?>
    </select>
    <select id="assignedSemFilter" class="search-input">
        <option value="">All Semesters</option>
        <?php foreach($semesters as $s){ ?>
            <option value="<?= $s ?>" <?= ($filterSem==$s?'selected':'') ?>><?= $s ?></option>
        <?php } ?>
    </select>
    <select id="assignedStatusFilter" class="search-input">
        <option value="">All Status</option>
        <?php foreach($statuses as $st){ ?>
            <option value="<?= $st ?>" <?= ($filterStatus==$st?'selected':'') ?>><?= $st ?></option>
        <?php } ?>
    </select>
    <input type="text" id="assignedSYFilter" class="search-input" placeholder="School Year" value="<?= htmlspecialchars($filterSY) ?>">
    <select id="assignedLimit" class="search-input">
        <?php foreach([5,10,25,50] as $l){ $sel=$limit==$l?'selected':''; ?>
        <option value="<?= $l ?>" <?= $sel ?>><?= $l ?></option>
        <?php } ?>
    </select>
</div>

<table>
<thead>
<tr>
<th>Student</th>
<th>Course</th>
<th>Year Level</th>
<th>Semester</th>
<th>School Year</th>
<th>Status</th>
<th>Transfer School</th>
<th>Transfer Reason</th>
<th>Remarks</th>
<th>Recorded By</th> <!-- New column -->
</tr>
</thead>

<tbody>
<?php if(mysqli_num_rows($assigned)>0){ 
while($row=mysqli_fetch_assoc($assigned)){ ?>
<tr>
<td><?= $row['student_name'] ?></td>
<td><?= $row['course_name'] ?></td>
<td><?= $row['year_level'] ?></td>
<td><?= $row['semester'] ?></td>
<td><?= $row['school_year'] ?></td>
<td><?= $row['status'] ?></td>
<td><?= htmlspecialchars($row['transfer_school']) ?></td>
<td><?= htmlspecialchars($row['transfer_reason']) ?></td>
<td><?= htmlspecialchars($row['remarks']) ?></td>
<td><?= isset($row['recorder_fname']) ? htmlspecialchars($row['recorder_fname'].' '.$row['recorder_lname']) : '-' ?></td>
</tr>
<?php }} else { ?>
<tr><td colspan="10">No assigned academic history found</td></tr>
<?php } ?>
</tbody>

</table>

<!-- PAGINATION -->
<div class="mt-15">
<?php if($page>1){ $params=$_GET; $params['page']=$page-1; ?><a href="?<?= http_build_query($params) ?>" class="btn small">Prev</a><?php } ?>
<?php for($i=1;$i<=$totalPages;$i++){ $params=$_GET; $params['page']=$i; ?>
    <a href="?<?= http_build_query($params) ?>" class="btn small <?= $i==$page?'primary':'' ?>"><?= $i ?></a>
<?php } ?>
<?php if($page<$totalPages){ $params=$_GET; $params['page']=$page+1; ?><a href="?<?= http_build_query($params) ?>" class="btn small">Next</a><?php } ?>
</div>

</div>
</div>

</main>

<script>
// ---------------- Student search (hide initially) ----------------
const studentSearchInput = document.getElementById('studentSearch');
const studentCheckboxes = document.getElementById('studentCheckboxes');
const studentSearchResults = document.getElementById('studentSearchResults');
const studentsArray = Array.from(studentCheckboxes.querySelectorAll('.student-item'));
const studentLimitSelect = document.getElementById('studentLimit');
const studentPager = document.getElementById('studentPager');

function getFilteredStudents(query){
    if(!query) return [];
    return studentsArray.filter(item=>item.textContent.toLowerCase().includes(query.toLowerCase()));
}

function renderStudentList(list, paginate, page, limit){
    studentSearchResults.innerHTML = '';
    studentPager.innerHTML = '';
    if(!list || list.length===0) return;

    const items = paginate ? (function(){
        const total = list.length;
        const totalPages = Math.max(1, Math.ceil(total/limit));
        if(page<1) page=1; if(page>totalPages) page=totalPages;
        const start=(page-1)*limit; const end=start+limit;
        const slice = list.slice(start,end);
        // pager controls
        if(totalPages>1){
            if(page>1){ const btn=document.createElement('button'); btn.className='btn small'; btn.textContent='Prev'; btn.addEventListener('click', ()=>renderStudentList(list,true,page-1,limit)); studentPager.appendChild(btn); }
            for(let i=1;i<=totalPages;i++){ const btn=document.createElement('button'); btn.className='btn small'+(i===page?' primary':''); btn.style.marginLeft='6px'; btn.textContent=i; btn.addEventListener('click', ((p)=>()=>renderStudentList(list,true,p,limit))(i)); studentPager.appendChild(btn); }
            if(page<totalPages){ const btn=document.createElement('button'); btn.className='btn small'; btn.style.marginLeft='6px'; btn.textContent='Next'; btn.addEventListener('click', ()=>renderStudentList(list,true,page+1,limit)); studentPager.appendChild(btn); }
        }
        return slice;
    })() : list;

    items.forEach(item=>{
        const clone = item.cloneNode(true);
        const checkbox = clone.querySelector('input[type="checkbox"]');
        const original = studentCheckboxes.querySelector('#'+checkbox.id);
        if(original) checkbox.checked = original.checked;
        checkbox.addEventListener('change', function(){
            const orig = studentCheckboxes.querySelector('#'+this.id);
            if(orig) orig.checked = this.checked;
        });
        studentSearchResults.appendChild(clone);
    });
}

let studentTimer;
function studentSearchHandler(){
    clearTimeout(studentTimer);
    studentTimer = setTimeout(()=>{
        const q = studentSearchInput.value.trim();
        if(q===''){ studentSearchResults.innerHTML=''; studentPager.innerHTML=''; return; }
        const filtered = getFilteredStudents(q);
        renderStudentList(filtered, false, 1, filtered.length);
    }, 400);
}

studentSearchInput.addEventListener('keyup', studentSearchHandler);
studentLimitSelect.addEventListener('change', ()=>{
    const q = studentSearchInput.value.trim();
    if(q===''){
        const all = Array.from(studentsArray);
        renderStudentList(all, true, 1, parseInt(studentLimitSelect.value,10)||10);
    }
});

// ---------------- Assigned table filters (debounced, reload) ----------------
const assignedSearch = document.getElementById('assignedSearch');
const assignedCourseFilter = document.getElementById('assignedCourseFilter');
const assignedYearFilter = document.getElementById('assignedYearFilter');
const assignedSemFilter = document.getElementById('assignedSemFilter');
const assignedLimit = document.getElementById('assignedLimit');
const assignedStatusFilter = document.getElementById('assignedStatusFilter');
const assignedSYFilter = document.getElementById('assignedSYFilter');
let assignedTimer;
function reloadAssigned(){
    const p = new URLSearchParams();
    if(assignedSearch.value) p.set('search', assignedSearch.value);
    if(assignedCourseFilter.value) p.set('course_id', assignedCourseFilter.value);
    if(assignedYearFilter.value) p.set('year', assignedYearFilter.value);
    if(assignedSemFilter.value) p.set('semester', assignedSemFilter.value);
    if(assignedStatusFilter.value) p.set('status', assignedStatusFilter.value);
    if(assignedSYFilter.value) p.set('school_year', assignedSYFilter.value);
    p.set('limit', assignedLimit.value);
    p.set('page',1);
    window.location.search = p.toString();
}
assignedSearch.addEventListener('keyup', ()=>{ clearTimeout(assignedTimer); assignedTimer=setTimeout(reloadAssigned,500); });
[assignedCourseFilter, assignedYearFilter, assignedSemFilter, assignedLimit, assignedStatusFilter, assignedSYFilter].forEach(el=>el.addEventListener('change', reloadAssigned));

// ---------------- Show/hide transfer fields ----------------
const statusSelect = document.getElementById('statusSelect');
const transferFields = document.getElementById('transferFields');

statusSelect.addEventListener('change', function(){
    if(this.value === 'Transferred'){
        transferFields.style.display = 'block';
    } else {
        transferFields.style.display = 'none';
    }
});
</script>

<?php include 'stms_footer.php'; ?>
