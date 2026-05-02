<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'header.php';

$statusMsg = "";

// -------------------- INITIALIZE --------------------
$search = $_GET['search'] ?? '';
$limit = intval($_GET['limit'] ?? 10);
$page  = intval($_GET['page'] ?? 1);
if($limit <= 0) $limit = 10;
if($page <= 0) $page = 1;
$offset = ($page - 1) * $limit;

// -------------------- FETCH COURSES --------------------
$courseQuery = mysqli_query($conn, "SELECT * FROM courses ORDER BY course_name ASC");
$courses = [];
while($c = mysqli_fetch_assoc($courseQuery)){
    $courses[$c['course_id']] = $c['course_name'];
}

// -------------------- YEAR LEVELS --------------------
$yearLevels = ['1st Year','2nd Year','3rd Year','4th Year'];

// -------------------- SAVE STUDENT --------------------
if(isset($_POST['save'])){
    
    $fname     = trim(mysqli_real_escape_string($conn,$_POST['fname']));
    $lname     = trim(mysqli_real_escape_string($conn,$_POST['lname']));
    $email     = trim(mysqli_real_escape_string($conn,$_POST['email']));
    $phone     = trim(mysqli_real_escape_string($conn,$_POST['phone']));
    $password  = trim($_POST['password']);
    $courseId  = intval($_POST['course_id']);
    $yearLevel = !empty($_POST['year_level']) ? trim(mysqli_real_escape_string($conn,$_POST['year_level'])) : NULL;
    $student_id_number = trim(mysqli_real_escape_string($conn,$_POST['student_id_number']));

    if(empty($password)){
        $statusMsg = "<div class='alert alert-danger'>Password is required.</div>";
    } else {
        $hashedPassword = hash('sha256', $password);

        // Check duplicate email
        $check = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email' AND role='student'");
        if(!$check) die("Check query failed: " . mysqli_error($conn));

        if(mysqli_num_rows($check) > 0){
            $statusMsg = "<div class='alert alert-danger'>Student already exists!</div>";
        } else {
            // Insert into users including student_id_number
            $insertUser = mysqli_query($conn, "INSERT INTO users 
                (username,password,role,first_name,last_name,student_id_number,email,contact_number) 
                VALUES ('$email','$hashedPassword','student','$fname','$lname','$student_id_number','$email','$phone')");
            if(!$insertUser) die("Insert user failed: " . mysqli_error($conn));
            $user_id = mysqli_insert_id($conn);

            // Insert into students (course & year only)
            $insertStudent = mysqli_query($conn, "INSERT INTO students 
                (user_id, course_id, year_level) VALUES ('$user_id','$courseId'," . ($yearLevel ? "'$yearLevel'" : "NULL") . ")");
            if(!$insertStudent) die("Insert student failed: " . mysqli_error($conn));

            $statusMsg = "<div class='alert alert-success'>Student created successfully!</div>";
        }
    }
}

// -------------------- EDIT STUDENT --------------------
$editRow = null;
if(isset($_GET['action']) && $_GET['action']=='edit'){
    $student_id = intval($_GET['student_id']);
    $res = mysqli_query($conn,"SELECT s.student_id, s.user_id, s.course_id, s.year_level, u.student_id_number, u.first_name, u.last_name, u.email, u.contact_number 
                              FROM students s 
                              LEFT JOIN users u ON s.user_id = u.user_id 
                              WHERE s.student_id='$student_id' LIMIT 1");
    $editRow = mysqli_fetch_assoc($res);
}

// -------------------- UPDATE STUDENT --------------------
if(isset($_POST['update'])){
    $student_id = intval($_POST['student_id']);
    $user_id    = intval($_POST['user_id']);
    $fname      = trim(mysqli_real_escape_string($conn,$_POST['fname']));
    $lname      = trim(mysqli_real_escape_string($conn,$_POST['lname']));
    $email      = trim(mysqli_real_escape_string($conn,$_POST['email']));
    $phone      = trim(mysqli_real_escape_string($conn,$_POST['phone']));
    $password   = trim($_POST['password']);
    $courseId   = intval($_POST['course_id']);
    $yearLevel  = !empty($_POST['year_level']) ? trim(mysqli_real_escape_string($conn,$_POST['year_level'])) : NULL;
    $student_id_number = trim(mysqli_real_escape_string($conn,$_POST['student_id_number']));

    // Check duplicate email for other users
    $check = mysqli_query($conn,"SELECT user_id FROM users WHERE email='$email' AND role='student' AND user_id != '$user_id'");
    if(!$check) die("Check duplicate email failed: " . mysqli_error($conn));
    if(mysqli_num_rows($check) > 0){
        $statusMsg = "<div class='alert alert-danger'>Email already used by another student!</div>";
    } else {
        if(!empty($password)){
            $hashedPassword = hash('sha256',$password);
            mysqli_query($conn,"UPDATE users SET first_name='$fname', last_name='$lname', student_id_number='$student_id_number', email='$email', username='$email', contact_number='$phone', password='$hashedPassword' WHERE user_id='$user_id'");
        } else {
            mysqli_query($conn,"UPDATE users SET first_name='$fname', last_name='$lname', student_id_number='$student_id_number', email='$email', username='$email', contact_number='$phone' WHERE user_id='$user_id'");
        }
        mysqli_query($conn,"UPDATE students SET course_id='$courseId', year_level=".($yearLevel? "'$yearLevel'" : "NULL")." WHERE student_id='$student_id'");

        echo "<script>window.location='create_student.php';</script>";
        exit;
    }
}

// -------------------- DELETE STUDENT --------------------
if(isset($_GET['action']) && $_GET['action']=='delete'){
    $student_id = intval($_GET['student_id']);
    $res = mysqli_query($conn,"SELECT user_id FROM students WHERE student_id='$student_id'");
    $row = mysqli_fetch_assoc($res);
    $user_id = $row['user_id'] ?? 0;

    mysqli_query($conn,"DELETE FROM students WHERE student_id='$student_id'");
    mysqli_query($conn,"DELETE FROM users WHERE user_id='$user_id'");

    echo "<script>window.location='create_student.php';</script>";
    exit;
}

// -------------------- SEARCH & PAGINATION --------------------
$where = '';
if(!empty($search)){
    $searchEsc = mysqli_real_escape_string($conn,$search);
    $where = "WHERE u.first_name LIKE '%$searchEsc%' OR u.last_name LIKE '%$searchEsc%' OR u.email LIKE '%$searchEsc%' OR u.student_id_number LIKE '%$searchEsc%'";
}

// Total students
$totalQuery = mysqli_query($conn,"SELECT COUNT(*) AS total FROM students s LEFT JOIN users u ON s.user_id = u.user_id $where");
$totalRows = mysqli_fetch_assoc($totalQuery)['total'];
$totalPages = ceil($totalRows / $limit);

// Fetch students
$dataQuery = mysqli_query($conn,"SELECT s.student_id, s.user_id, s.course_id, s.year_level, u.student_id_number, u.first_name, u.last_name, u.email
                                FROM students s 
                                LEFT JOIN users u ON s.user_id = u.user_id
                                $where ORDER BY s.student_id DESC LIMIT $offset,$limit");
?>

<main>
<div class="head-title">
    <div class="left">
        <h1>Student </h1>
       <ul class="breadcrumb">
                <li><a href="#">Home</a></li>
                <li><i class='bx bx-chevron-right'></i></li>
                <li><a class="active">Management Information</a></li>
            </ul>
        </div>
</div>

<!-- CREATE / EDIT FORM -->
<div class="table-data">
<div class="order">
<div class="head"><h3><?php echo $editRow ? "Edit Student" : "Create Student"; ?></h3></div>
<?php if(!empty($statusMsg)) echo $statusMsg; ?>

<form method="POST" class="form-class">
    <input type="hidden" name="student_id" value="<?php echo $editRow['student_id'] ?? ''; ?>">
    <input type="hidden" name="user_id" value="<?php echo $editRow['user_id'] ?? ''; ?>">

    <input type="text" name="student_id_number" placeholder="Student ID (e.g. 01-2122-034292)" value="<?php echo $editRow['student_id_number'] ?? ''; ?>" required>
    <input type="text" name="fname" placeholder="First Name" value="<?php echo $editRow['first_name'] ?? ''; ?>" required>
    <input type="text" name="lname" placeholder="Last Name" value="<?php echo $editRow['last_name'] ?? ''; ?>" required>
    <input type="email" name="email" placeholder="Email" value="<?php echo $editRow['email'] ?? ''; ?>" required>
    <input type="text" name="phone" placeholder="Phone" value="<?php echo $editRow['contact_number'] ?? ''; ?>">

    <input type="password" name="password" placeholder="Password <?php echo $editRow ? '(leave blank to keep current)' : ''; ?>" <?php if(!$editRow) echo "required"; ?>>

    <select name="course_id" required>
        <option value="">Select Course</option>
        <?php foreach($courses as $id=>$name){ ?>
            <option value="<?php echo $id; ?>" <?php if(($editRow['course_id'] ?? '')==$id) echo "selected"; ?>><?php echo $name; ?></option>
        <?php } ?>
    </select>

    <select name="year_level" required>
        <option value="">Select Year Level</option>
        <?php foreach($yearLevels as $year){ ?>
            <option value="<?php echo $year; ?>" <?php if(($editRow['year_level'] ?? '')==$year) echo "selected"; ?>><?php echo $year; ?></option>
        <?php } ?>
    </select>

    <?php if($editRow){ ?>
        <button type="submit" name="update" class="btn primary">Update</button>
        <a href="create_student.php" class="btn danger small">Cancel</a>
    <?php } else { ?>
        <button type="submit" name="save" class="btn primary">Save</button>
    <?php } ?>
</form>
</div>
</div>

<!-- STUDENTS TABLE -->
<div class="table-data">
<div class="order">
<div class="head flex-between">
<h3>All Students</h3>
</div>

<!-- FILTERS (UNCHANGED DESIGN) -->
<div class="row-controls">
<input type="search" id="searchInput" class="search-input" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
<select id="limitSelect" class="search-input">
<?php foreach([5,10,20,50] as $option){ ?>
<option value="<?php echo $option; ?>" <?php if($option==$limit) echo "selected"; ?>><?php echo $option; ?></option>
<?php } ?>
</select>
</div>

<table>
<thead>
<tr>
<th>#</th>
<th>Student ID</th>
<th>First Name</th>
<th>Last Name</th>
<th>Email</th>
<th>Course</th>
<th>Year Level</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php $sn=$offset+1; while($row=mysqli_fetch_assoc($dataQuery)){ ?>
<tr>
<td><?php echo $sn++; ?></td>
<td><?php echo $row['student_id_number']; ?></td>
<td><?php echo $row['first_name']; ?></td>
<td><?php echo $row['last_name']; ?></td>
<td><?php echo $row['email']; ?></td>
<td><?php echo $courses[$row['course_id']] ?? 'N/A'; ?></td>
<td><?php echo $row['year_level']; ?></td>
<td>
<a href="?action=edit&student_id=<?php echo $row['student_id']; ?>" class="btn small primary">Edit</a>
<a href="?action=delete&student_id=<?php echo $row['student_id']; ?>" class="btn small danger" onclick="return confirm('Delete this student?')">Delete</a>
</td>
</tr>
<?php } ?>
</tbody>
</table>

<!-- PAGINATION -->
<div class="mt-15">
<?php if($page>1){ ?>
<a href="?search=<?php echo urlencode($search); ?>&limit=<?php echo $limit; ?>&page=<?php echo $page-1; ?>" class="btn small">Prev</a>
<?php } ?>
<?php for($i=1;$i<=$totalPages;$i++){ ?>
<a href="?search=<?php echo urlencode($search); ?>&limit=<?php echo $limit; ?>&page=<?php echo $i; ?>" class="btn small <?php if($i==$page) echo 'primary'; ?>"><?php echo $i; ?></a>
<?php } ?>
<?php if($page<$totalPages){ ?>
<a href="?search=<?php echo urlencode($search); ?>&limit=<?php echo $limit; ?>&page=<?php echo $page+1; ?>" class="btn small">Next</a>
<?php } ?>
</div>
</div>
</div>
</main>

<script>
const searchInput=document.getElementById('searchInput');
const limitSelect=document.getElementById('limitSelect');

searchInput.addEventListener('keyup',function(){
    window.location="?search="+encodeURIComponent(searchInput.value)+"&limit="+limitSelect.value;
});
limitSelect.addEventListener('change',function(){
    window.location="?search="+encodeURIComponent(searchInput.value)+"&limit="+limitSelect.value;
});
</script>

<?php include 'stms_footer.php'; ?>
