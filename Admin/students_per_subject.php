<?php 
include 'header.php';

$statusMsg = "";

/* ================= DELETE SUBJECT FROM STUDENT ================= */
if(isset($_GET['delete'])){
    $deleteId = intval($_GET['delete']);
    mysqli_query($conn,"DELETE FROM student_subjects WHERE id='$deleteId'");
    echo "<script>window.location='student_per_subject.php';</script>";
    exit;
}

/* ================= FILTER VALUES ================= */
$search       = $_GET['search'] ?? '';
$filterCourse = $_GET['course_id'] ?? '';
$filterSubject= $_GET['subject_id'] ?? '';
$filterYear   = $_GET['year'] ?? '';
$filterSem    = $_GET['semester'] ?? '';

$hasFilter = ($search || $filterCourse || $filterSubject || $filterYear || $filterSem);

$where = "WHERE 1";

/* ================= APPLY FILTERS ================= */
if($search){
    $searchEsc = mysqli_real_escape_string($conn, $search);
    $where .= " AND (u.first_name LIKE '%$searchEsc%' 
                OR u.last_name LIKE '%$searchEsc%')";
}

if($filterCourse){
    $where .= " AND c.course_id='".intval($filterCourse)."'";
}

if($filterSubject){
    $where .= " AND sub.subject_id='".intval($filterSubject)."'";
}

if($filterYear){
    $filterYear = mysqli_real_escape_string($conn, $filterYear);
    $where .= " AND sub.year='$filterYear'";
}

if($filterSem){
    $filterSem = mysqli_real_escape_string($conn, $filterSem);
    $where .= " AND sub.semester='$filterSem'";
}

/* ================= FETCH COURSES ================= */
$courses = mysqli_query($conn,"SELECT * FROM courses ORDER BY course_name ASC");

/* ================= FETCH SUBJECTS BASED ON COURSE ================= */
$subjectQuery = "SELECT * FROM subjects";
if($filterCourse){
    $subjectQuery .= " WHERE course_id='".intval($filterCourse)."'";
}
$subjectQuery .= " ORDER BY subject_name ASC";
$subjects = mysqli_query($conn,$subjectQuery);

/* ================= FETCH TABLE DATA (ONLY IF FILTERED) ================= */
$result = null;

if($hasFilter){
    $result = mysqli_query($conn,"
        SELECT ss.id,
               CONCAT(u.first_name,' ',u.last_name) AS student_name,
               c.course_name,
               sub.subject_name,
               sub.year,
               sub.semester
        FROM student_subjects ss
        INNER JOIN students s ON ss.student_id = s.student_id
        INNER JOIN users u ON s.user_id = u.user_id
        INNER JOIN subjects sub ON ss.subject_id = sub.subject_id
        INNER JOIN courses c ON sub.course_id = c.course_id
        $where
        ORDER BY u.first_name ASC
    ");
}
?>

<main>

<div class="head-title">
    <div class="left">
        <h1>Students Per Subject</h1>
    <ul class="breadcrumb">
                <li><a href="#">Home</a></li>
                <li><i class='bx bx-chevron-right'></i></li>
                <li><a class="active" href="#">Managemnt Information</a></li>
            </ul>
        </div>
    </div>

<div class="table-data">
<div class="order">

<div class="head flex-between">
    <h3>Students Per Subject</h3>
</div>

<!-- ================= HORIZONTAL FILTERS ================= -->
<div class="row-controls">

<input type="search" id="searchStudent" 
       class="search-input" 
       placeholder="Search student..."
       value="<?= htmlspecialchars($search) ?>">

<select id="filterCourse" class="search-input">
    <option value="">Course</option>
    <?php while($c=mysqli_fetch_assoc($courses)){ ?>
        <option value="<?= $c['course_id'] ?>"
            <?= ($filterCourse==$c['course_id']?'selected':'') ?>>
            <?= $c['course_name'] ?>
        </option>
    <?php } ?>
</select>

<select id="filterSubject" class="search-input">
    <option value="">Subject</option>
    <?php while($s=mysqli_fetch_assoc($subjects)){ ?>
        <option value="<?= $s['subject_id'] ?>"
            <?= ($filterSubject==$s['subject_id']?'selected':'') ?>>
            <?= $s['subject_name'] ?>
        </option>
    <?php } ?>
</select>

<select id="filterYear" class="search-input">
    <option value="">Year</option>
    <option value="1st Year" <?= ($filterYear=="1st Year"?'selected':'') ?>>1st Year</option>
    <option value="2nd Year" <?= ($filterYear=="2nd Year"?'selected':'') ?>>2nd Year</option>
    <option value="3rd Year" <?= ($filterYear=="3rd Year"?'selected':'') ?>>3rd Year</option>
    <option value="4th Year" <?= ($filterYear=="4th Year"?'selected':'') ?>>4th Year</option>
</select>

<select id="filterSem" class="search-input">
    <option value="">Semester</option>
    <option value="1st Sem" <?= ($filterSem=="1st Sem"?'selected':'') ?>>1st Sem</option>
    <option value="2nd Sem" <?= ($filterSem=="2nd Sem"?'selected':'') ?>>2nd Sem</option>
    <option value="3rd Sem" <?= ($filterSem=="3rd Sem"?'selected':'') ?>>3rd Sem</option>
    <option value="Summer" <?= ($filterSem=="Summer"?'selected':'') ?>>Summer</option>
</select>

</div>

<!-- ================= TABLE ================= -->
<table>
<thead>
<tr>
    <th>Student Name</th>
    <th>Course</th>
    <th>Subject</th>
    <th>Year</th>
    <th>Semester</th>
    <th>Action</th>
</tr>
</thead>
<tbody>

<?php if(!$hasFilter){ ?>
<tr>
    <td colspan="6" style="text-align:center;">
        Please select a filter to display data.
    </td>
</tr>
<?php } else if(mysqli_num_rows($result)>0){ 
while($row=mysqli_fetch_assoc($result)){ ?>
<tr>
    <td><?= $row['student_name'] ?></td>
    <td><?= $row['course_name'] ?></td>
    <td><?= $row['subject_name'] ?></td>
    <td><?= $row['year'] ?></td>
    <td><?= $row['semester'] ?></td>
    <td>
        <a href="?delete=<?= $row['id'] ?>" 
           class="btn small"
           onclick="return confirm('Remove this subject from student?')">
           Delete
        </a>
    </td>
</tr>
<?php } } else { ?>
<tr>
    <td colspan="6" style="text-align:center;">
        No records found.
    </td>
</tr>
<?php } ?>

</tbody>
</table>

</div>
</div>

</main>

<script>
const searchStudent = document.getElementById('searchStudent');
const filterCourse  = document.getElementById('filterCourse');
const filterSubject = document.getElementById('filterSubject');
const filterYear    = document.getElementById('filterYear');
const filterSem     = document.getElementById('filterSem');

let timer;

function reloadTable(){
    const params = new URLSearchParams();
    if(searchStudent.value) params.set('search', searchStudent.value);
    if(filterCourse.value)  params.set('course_id', filterCourse.value);
    if(filterSubject.value) params.set('subject_id', filterSubject.value);
    if(filterYear.value)    params.set('year', filterYear.value);
    if(filterSem.value)     params.set('semester', filterSem.value);
    window.location.search = params.toString();
}

searchStudent.addEventListener('keyup',()=>{
    clearTimeout(timer);
    timer=setTimeout(reloadTable,500);
});

[filterCourse, filterSubject, filterYear, filterSem]
.forEach(el=>el.addEventListener('change', reloadTable));
</script>

<?php include 'stms_footer.php'; ?>
