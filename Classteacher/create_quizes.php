<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'header.php';

$statusMsg = "";

// ================= GET TEACHER ID =================
$user_id = $_SESSION['user_id'] ?? 0;
$teacher_id = 0;

$resT = mysqli_query($conn,"SELECT teacher_id FROM teachers WHERE user_id='$user_id' LIMIT 1");
if($resT && mysqli_num_rows($resT)>0){
    $teacher_id = mysqli_fetch_assoc($resT)['teacher_id'];
}

if($teacher_id == 0){
    die("<div class='alert alert-danger'>Access denied. Teacher only.</div>");
}

// ================= SEARCH / FILTER / PAGINATION =================
$search = $_GET['search'] ?? '';
$filterSubject = $_GET['filterSubject'] ?? '';
$limit = intval($_GET['limit'] ?? 10);
$page  = intval($_GET['page'] ?? 1);

if($limit <= 0) $limit = 10;
if($page <= 0) $page = 1;

$offset = ($page - 1) * $limit;

// ================= FETCH TEACHER SUBJECTS =================
$subjectQuery = mysqli_query($conn,"
    SELECT subject_id, subject_name 
    FROM subjects 
    WHERE teacher_id='$teacher_id'
    ORDER BY subject_name ASC
");

$subjects = [];
while($row = mysqli_fetch_assoc($subjectQuery)){
    $subjects[$row['subject_id']] = $row['subject_name'];
}

// ================= SAVE QUIZ =================
if(isset($_POST['save'])){
    $subject_id = intval($_POST['subject_id']);
    $quiz_title = mysqli_real_escape_string($conn,$_POST['quiz_title']);
    $quiz_date  = mysqli_real_escape_string($conn,$_POST['quiz_date']);
    $total_score = intval($_POST['total_score']);

    if(!isset($subjects[$subject_id])){
        $statusMsg = "<div class='alert alert-danger'>Invalid subject.</div>";
    } else {
        mysqli_query($conn,"INSERT INTO quizzes (subject_id,quiz_title,quiz_date,total_score)
                            VALUES ('$subject_id','$quiz_title','$quiz_date','$total_score')");
        $statusMsg = "<div class='alert alert-success'>Quiz created successfully!</div>";
    }
}

// ================= EDIT =================
$editRow = null;
if(isset($_GET['action']) && $_GET['action']=='edit'){
    $quiz_id = intval($_GET['quiz_id']);
    $res = mysqli_query($conn,"SELECT * FROM quizzes WHERE quiz_id='$quiz_id' LIMIT 1");
    $editRow = mysqli_fetch_assoc($res);
}

// ================= UPDATE =================
if(isset($_POST['update'])){
    $quiz_id   = intval($_POST['quiz_id']);
    $subject_id = intval($_POST['subject_id']);
    $quiz_title = mysqli_real_escape_string($conn,$_POST['quiz_title']);
    $quiz_date  = mysqli_real_escape_string($conn,$_POST['quiz_date']);
    $total_score = intval($_POST['total_score']);

    mysqli_query($conn,"UPDATE quizzes SET 
                        subject_id='$subject_id',
                        quiz_title='$quiz_title',
                        quiz_date='$quiz_date',
                        total_score='$total_score'
                        WHERE quiz_id='$quiz_id'");

    echo "<script>window.location='create_quizes.php';</script>";
    exit;
}

// ================= DELETE =================
if(isset($_GET['action']) && $_GET['action']=='delete'){
    $quiz_id = intval($_GET['quiz_id']);
    mysqli_query($conn,"DELETE FROM quizzes WHERE quiz_id='$quiz_id'");
    echo "<script>window.location='create_quizes.php';</script>";
    exit;
}

// ================= FILTER =================
$where = "WHERE s.teacher_id='$teacher_id'";

if(!empty($search)){
    $searchEsc = mysqli_real_escape_string($conn,$search);
    $where .= " AND (q.quiz_title LIKE '%$searchEsc%' OR s.subject_name LIKE '%$searchEsc%')";
}

if(!empty($filterSubject)){
    $filterSubject = intval($filterSubject);
    $where .= " AND q.subject_id='$filterSubject'";
}

// ================= COUNT =================
$totalQuery = mysqli_query($conn,"
    SELECT COUNT(*) AS total
    FROM quizzes q
    LEFT JOIN subjects s ON q.subject_id = s.subject_id
    $where
");

$totalRows = mysqli_fetch_assoc($totalQuery)['total'];
$totalPages = ceil($totalRows / $limit);

// ================= FETCH DATA =================
$dataQuery = mysqli_query($conn,"
    SELECT q.*, s.subject_name
    FROM quizzes q
    LEFT JOIN subjects s ON q.subject_id = s.subject_id
    $where
    ORDER BY q.quiz_id DESC
    LIMIT $offset,$limit
");
?>

<main>

<div class="head-title">
    <div class="left">
        <h1>Quiz Management</h1>
  <ul class="breadcrumb">
<li><a href="#">Home</a></li>
<li><i class='bx bx-chevron-right'></i></li>
<li><a class="active">Management Information</a></li>
</ul>
</div>
</div>

<!-- ================= FORM ================= -->
<div class="table-data">
<div class="order">
<div class="head">
<h3><?php echo $editRow ? "Edit Quiz" : "Create Quiz"; ?></h3>
</div>

<?php if(!empty($statusMsg)) echo $statusMsg; ?>

<form method="POST" class="form-class">

<input type="hidden" name="quiz_id" value="<?php echo $editRow['quiz_id'] ?? ''; ?>">

<select name="subject_id" required>
<option value="">Select Subject</option>
<?php foreach($subjects as $id=>$name){ ?>
<option value="<?php echo $id; ?>"
<?php if(($editRow['subject_id'] ?? '')==$id) echo "selected"; ?>>
<?php echo $name; ?>
</option>
<?php } ?>
</select>

<input type="text" name="quiz_title" placeholder="Quiz Title"
value="<?php echo $editRow['quiz_title'] ?? ''; ?>" required>

<input type="number" name="total_score" placeholder="Total Score"
value="<?php echo $editRow['total_score'] ?? ''; ?>" required>

<input type="date" name="quiz_date"
value="<?php echo $editRow['quiz_date'] ?? ''; ?>" required>

<?php if($editRow){ ?>
<button type="submit" name="update" class="btn primary">Update</button>
<a href="create_quizes.php" class="btn danger small">Cancel</a>
<?php } else { ?>
<button type="submit" name="save" class="btn primary">Save</button>
<?php } ?>

</form>
</div>
</div>

<!-- ================= TABLE ================= -->
<div class="table-data">
<div class="order">

<div class="head flex-between">
<h3>All Quizzes</h3>
</div>

<!-- FILTER CONTROLS -->
<div class="row-controls">

<input type="search" id="searchInput" class="search-input"
placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">

<select id="subjectFilter" class="search-input">
<option value="">All Subjects</option>
<?php foreach($subjects as $id=>$name){ ?>
<option value="<?php echo $id; ?>"
<?php if($filterSubject==$id) echo "selected"; ?>>
<?php echo $name; ?>
</option>
<?php } ?>
</select>

<select id="limitSelect" class="search-input">
<?php foreach([5,10,20,50] as $opt){ ?>
<option value="<?php echo $opt; ?>" <?php if($opt==$limit) echo "selected"; ?>>
<?php echo $opt; ?>
</option>
<?php } ?>
</select>

</div>

<table>
<thead>
<tr>
<th>#</th>
<th>Subject</th>
<th>Quiz Title</th>
<th>Total Score</th>
<th>Quiz Date</th>
<th>Action</th>
</tr>
</thead>
<tbody>

<?php 
$sn=$offset+1; 
while($row=mysqli_fetch_assoc($dataQuery)){ 
?>

<tr>
<td><?php echo $sn++; ?></td>
<td><?php echo $row['subject_name']; ?></td>
<td><?php echo $row['quiz_title']; ?></td>
<td><?php echo $row['total_score']; ?></td>
<td><?php echo $row['quiz_date']; ?></td>
<td>
<a href="?action=edit&quiz_id=<?php echo $row['quiz_id']; ?>" class="btn small primary">Edit</a>
<a href="?action=delete&quiz_id=<?php echo $row['quiz_id']; ?>" class="btn small danger"
onclick="return confirm('Delete this quiz?')">Delete</a>
</td>
</tr>

<?php } ?>

</tbody>
</table>

<!-- PAGINATION -->
<div class="mt-15">

<?php if($page>1){ ?>
<a href="?search=<?php echo urlencode($search); ?>
&filterSubject=<?php echo urlencode($filterSubject); ?>
&limit=<?php echo $limit; ?>
&page=<?php echo $page-1; ?>" class="btn small">Prev</a>
<?php } ?>

<?php for($i=1;$i<=$totalPages;$i++){ ?>
<a href="?search=<?php echo urlencode($search); ?>
&filterSubject=<?php echo urlencode($filterSubject); ?>
&limit=<?php echo $limit; ?>
&page=<?php echo $i; ?>"
class="btn small <?php if($i==$page) echo 'primary'; ?>">
<?php echo $i; ?>
</a>
<?php } ?>

<?php if($page<$totalPages){ ?>
<a href="?search=<?php echo urlencode($search); ?>
&filterSubject=<?php echo urlencode($filterSubject); ?>
&limit=<?php echo $limit; ?>
&page=<?php echo $page+1; ?>" class="btn small">Next</a>
<?php } ?>

</div>

</div>
</div>

</main>

<script>
const searchInput = document.getElementById('searchInput');
const limitSelect = document.getElementById('limitSelect');
const subjectFilter = document.getElementById('subjectFilter');

function applyFilters(){
    window.location = "?search=" + encodeURIComponent(searchInput.value)
        + "&filterSubject=" + subjectFilter.value
        + "&limit=" + limitSelect.value;
}

searchInput.addEventListener('keyup', applyFilters);
limitSelect.addEventListener('change', applyFilters);
subjectFilter.addEventListener('change', applyFilters);
</script>

<?php include 'stms_footer.php'; ?>