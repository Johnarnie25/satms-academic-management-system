<?php 
include 'header.php'; 


$statusMsg = "";

/* ================= REMOVE ASSIGNED SUBJECT ================= */
if(isset($_GET['remove'])){
    $removeId = intval($_GET['remove']);
    mysqli_query($conn,"DELETE FROM student_subjects WHERE student_id='$removeId'");
    echo "<script>window.location='assign_students_course.php';</script>";
    exit;
}


/* ================= ASSIGN STUDENT ================= */
if(isset($_POST['assign'])){
    $selectedStudents = $_POST['student_ids'] ?? [];
    $courseId  = intval($_POST['course_id']);
    $yearLevel = $_POST['year'];
    $semester  = $_POST['semester'];

    if(!$selectedStudents || !$courseId || !$yearLevel || !$semester){
        $statusMsg = "<div class='alert alert-danger'>Please complete all fields and select at least one student.</div>";
    } else {
        $assignedTotal = 0;
        foreach($selectedStudents as $studentId){
            $studentId = intval($studentId);

            // Update student's course & year level
            mysqli_query($conn,"
                UPDATE students 
                SET course_id='$courseId',
                    year_level='$yearLevel'
                WHERE student_id='$studentId'
            ");

            // Get subjects
            $subjects = mysqli_query($conn, "
                SELECT subject_id 
                FROM subjects 
                WHERE course_id='$courseId'
                AND year='$yearLevel'
                AND semester='$semester'
            ");

            if(mysqli_num_rows($subjects)>0){
                while($sub=mysqli_fetch_assoc($subjects)){
                    $subjectId = $sub['subject_id'];
                    $check = mysqli_query($conn,"
                        SELECT * FROM student_subjects
                        WHERE student_id='$studentId'
                        AND subject_id='$subjectId'
                    ");
                    if(mysqli_num_rows($check)==0){
                        mysqli_query($conn,"
                            INSERT INTO student_subjects(student_id, subject_id)
                            VALUES('$studentId','$subjectId')
                        ");
                        $assignedTotal++;
                    }
                }
            }
        }

        $statusMsg = "<div class='alert alert-success'>$assignedTotal subject(s) assigned successfully!</div>";
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

/* ================= FILTER & PAGINATION ================= */
$search    = $_GET['search'] ?? '';
$filterCourse = $_GET['course_id'] ?? '';
$filterYear   = $_GET['year'] ?? '';
$filterSem    = $_GET['semester'] ?? '';

$where = "WHERE 1";
if($search){
    $searchEsc = mysqli_real_escape_string($conn, $search);
    $where .= " AND (u.first_name LIKE '%$searchEsc%' OR u.last_name LIKE '%$searchEsc%')";
}
if($filterCourse){
    $filterCourse = intval($filterCourse);
    $where .= " AND c.course_id='$filterCourse'";
}
if($filterYear){
    $filterYear = mysqli_real_escape_string($conn, $filterYear);
    $where .= " AND s.year_level='$filterYear'";
}
if($filterSem){
    $filterSem = mysqli_real_escape_string($conn, $filterSem);
    $where .= " AND sub.semester='$filterSem'";
}

// Pagination
$limit = intval($_GET['limit'] ?? 10);
$page  = intval($_GET['page'] ?? 1);
if($page<1) $page=1;
$offset = ($page-1)*$limit;

// Count total assigned
$totalRes = mysqli_query($conn,"
    SELECT COUNT(DISTINCT s.student_id) AS total
    FROM student_subjects ss
    INNER JOIN students s ON ss.student_id=s.student_id
    INNER JOIN users u ON s.user_id=u.user_id
    INNER JOIN subjects sub ON ss.subject_id=sub.subject_id
    INNER JOIN courses c ON sub.course_id=c.course_id
    $where
");
$totalRow = mysqli_fetch_assoc($totalRes);
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $limit);

// Fetch assigned students
$assigned = mysqli_query($conn,"
    SELECT s.student_id,
           CONCAT(u.first_name,' ',u.last_name) as student_name,
           c.course_name,
           GROUP_CONCAT(sub.subject_name SEPARATOR ', ') as subjects_list,
           COUNT(ss.subject_id) as total_subjects
    FROM student_subjects ss
    INNER JOIN students s ON ss.student_id=s.student_id
    INNER JOIN users u ON s.user_id=u.user_id
    INNER JOIN subjects sub ON ss.subject_id=sub.subject_id
    INNER JOIN courses c ON sub.course_id=c.course_id
    $where
    GROUP BY s.student_id
    ORDER BY u.first_name ASC
    LIMIT $offset, $limit
");
?>

<main>

<div class="head-title">
    <div class="left">
        <h1>Assign Students to Course</h1>
   <ul class="breadcrumb">
                <li><a href="#">Home</a></li>
                <li><i class='bx bx-chevron-right'></i></li>
                <li><a class="active">Management Information</a></li>
            </ul>
        </div>
    </div>

<!-- ================= ASSIGN FORM ================= -->
<div class="table-data">
<div class="order">
<div class="head"><h3>Assign Subjects Automatically</h3></div>

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
    <?php 
    mysqli_data_seek($courses, 0);
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

<br><br>
<button name="assign" class="btn primary">Assign Subjects</button>

</form>
</div>
</div>

<!-- ================= ASSIGNED TABLE ================= -->
<div class="table-data">
<div class="order">
<div class="head flex-between"><h3>Assigned Students</h3></div>

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
<th>Total Subjects</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php if(mysqli_num_rows($assigned)>0){ 
while($row=mysqli_fetch_assoc($assigned)){ ?>
<tr>
<td><?= $row['student_name'] ?></td>
<td><?= $row['course_name'] ?></td>
<td><?= $row['total_subjects'] ?> Subjects</td>
<td>
    <?php if($row['subjects_list']){ ?>
        <button type="button" class="view-btn" data-subjects='<?= json_encode(explode(", ",$row['subjects_list'])) ?>'>View</button>
        <!-- Delete button -->
        <a href="?remove=<?= $row['student_id'] ?>" onclick="return confirm('Are you sure you want to remove all assigned subjects for <?= htmlspecialchars($row['student_name']) ?>?');" class="btn danger small">Delete</a>
    <?php } else { echo '—'; } ?>
</td>

</tr>
<?php }} else { ?>
<tr><td colspan="4">No assigned students found</td></tr>
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

<!-- Modal -->
<div id="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);justify-content:center;align-items:center;z-index:9999;">
<div style="background:#fff;padding:20px;border-radius:10px;width:400px;max-height:80%;overflow-y:auto;position:relative;">
<span id="closeModal" style="position:absolute;top:10px;right:15px;cursor:pointer;font-size:20px;">&times;</span>
<h3>Assigned Subjects</h3>
<ul id="modalList" style="list-style:none;padding-left:0;"></ul>
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
        // when searching show all matches without pagination
        renderStudentList(filtered, false, 1, filtered.length);
    }, 400);
}

studentSearchInput.addEventListener('keyup', studentSearchHandler);
studentLimitSelect.addEventListener('change', ()=>{
    // if user wants to browse full list (no query), render paginated list; otherwise ignore
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
let assignedTimer;
function reloadAssigned(){
    const p = new URLSearchParams();
    if(assignedSearch.value) p.set('search', assignedSearch.value);
    if(assignedCourseFilter.value) p.set('course_id', assignedCourseFilter.value);
    if(assignedYearFilter.value) p.set('year', assignedYearFilter.value);
    if(assignedSemFilter.value) p.set('semester', assignedSemFilter.value);
    p.set('limit', assignedLimit.value);
    p.set('page',1);
    window.location.search = p.toString();
}
assignedSearch.addEventListener('keyup', ()=>{ clearTimeout(assignedTimer); assignedTimer=setTimeout(reloadAssigned,500); });
[assignedCourseFilter, assignedYearFilter, assignedSemFilter, assignedLimit].forEach(el=>el.addEventListener('change', reloadAssigned));

// ---------------- Modal ----------------
const modal = document.getElementById('modal');
const modalList = document.getElementById('modalList');
const closeModal = document.getElementById('closeModal');

document.addEventListener('click', e=>{
    if(e.target.classList.contains('view-btn')){
        const subjects = JSON.parse(e.target.dataset.subjects);
        modalList.innerHTML='';
        subjects.forEach(s=>{
            const li=document.createElement('li');
            li.textContent=s;
            li.style.padding='5px 0';
            modalList.appendChild(li);
        });
        modal.style.display='flex';
    }
});
closeModal.addEventListener('click', ()=>modal.style.display='none');
window.addEventListener('click', e=>{ if(e.target===modal) modal.style.display='none'; });
document.addEventListener('keydown', e=>{ if(e.key==="Escape") modal.style.display='none'; });
</script>

<?php include 'stms_footer.php'; ?>
