<?php include 'header.php'; ?>

<?php

// ============================================
// INITIALIZATION
// ============================================

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn,$_GET['search']) : "";
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if($limit <= 0) $limit = 10;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page <= 0) $page = 1;

$offset = ($page - 1) * $limit;

// ============================================
// STATUSES
// ============================================

$statuses = ['Active','Inactive']; // For dropdown

// ============================================
// FETCH COURSES FOR DROPDOWN
// ============================================

$courses = [];
$courseQuery = mysqli_query($conn, "SELECT course_id, course_name FROM courses ORDER BY course_name ASC");
while($c = mysqli_fetch_assoc($courseQuery)) {
    $courses[] = $c;
}

// ============================================
// SAVE TEACHER
// ============================================

if(isset($_POST['save'])){
    $fname      = mysqli_real_escape_string($conn,$_POST['fname']);
    $lname      = mysqli_real_escape_string($conn,$_POST['lname']);
    $email      = mysqli_real_escape_string($conn,$_POST['email']);
    $phone      = mysqli_real_escape_string($conn,$_POST['phone']);
    $password   = mysqli_real_escape_string($conn,$_POST['password']);
    $status     = mysqli_real_escape_string($conn,$_POST['status']);
    $course_id  = (int)$_POST['course_id'];

    $hashedPassword = hash('sha256', $password);

    // Check duplicate email
    $check = mysqli_query($conn,"
        SELECT user_id
        FROM users
        WHERE email='$email' AND role='teacher'
        LIMIT 1
    ");

    if(mysqli_num_rows($check) > 0){
        $statusMsg = "<div class='alert alert-danger'>Teacher already exists!</div>";
    } else {
        // Insert into users
        $insertUser = mysqli_query($conn,"
            INSERT INTO users
            (username, password, role, first_name, last_name, email, contact_number)
            VALUES
            ('$email','$hashedPassword','teacher','$fname','$lname','$email','$phone')
        ");

        if($insertUser){
            $user_id = mysqli_insert_id($conn);

            // Insert into teachers with course and status
            mysqli_query($conn,"
                INSERT INTO teachers
                (user_id, course_id, status)
                VALUES
                ('$user_id','$course_id','$status')
            ");

            echo "<script type='text/javascript'>
                    window.location = 'create_teacher.php';
                  </script>";
            exit;
        } else {
            $statusMsg = "<div class='alert alert-danger'>Error Creating Teacher!</div>";
        }
    }
}

// ============================================
// DELETE TEACHER
// ============================================

if(isset($_GET['action']) && $_GET['action'] == "delete"){
    $Id = (int)$_GET['Id'];
    mysqli_query($conn,"
        DELETE FROM users
        WHERE user_id='$Id' AND role='teacher'
    ");
    echo "<script type='text/javascript'>
            window.location = 'create_teacher.php';
          </script>";
    exit;
}

// ============================================
// FETCH TEACHER FOR EDIT
// ============================================

$editRow = null;
if(isset($_GET['action']) && $_GET['action'] == "edit"){
    $Id = (int)$_GET['Id'];
    $editQuery = mysqli_query($conn,"
        SELECT u.*, t.course_id, t.status, c.course_name
        FROM users u
        LEFT JOIN teachers t ON u.user_id = t.user_id
        LEFT JOIN courses c ON t.course_id = c.course_id
        WHERE u.user_id='$Id' AND u.role='teacher'
    ");
    $editRow = mysqli_fetch_assoc($editQuery);
}

// ============================================
// UPDATE TEACHER
// ============================================

if(isset($_POST['update'])){
    $Id         = (int)$_GET['Id'];
    $fname      = mysqli_real_escape_string($conn,$_POST['fname']);
    $lname      = mysqli_real_escape_string($conn,$_POST['lname']);
    $email      = mysqli_real_escape_string($conn,$_POST['email']);
    $phone      = mysqli_real_escape_string($conn,$_POST['phone']);
    $password   = mysqli_real_escape_string($conn,$_POST['password']);
    $status     = mysqli_real_escape_string($conn,$_POST['status']);
    $course_id  = (int)$_POST['course_id'];

    if(!empty($password)){
        $hashedPassword = hash('sha256', $password);
        mysqli_query($conn,"
            UPDATE users SET
            first_name='$fname',
            last_name='$lname',
            email='$email',
            username='$email',
            contact_number='$phone',
            password='$hashedPassword'
            WHERE user_id='$Id' AND role='teacher'
        ");
    } else {
        mysqli_query($conn,"
            UPDATE users SET
            first_name='$fname',
            last_name='$lname',
            email='$email',
            username='$email',
            contact_number='$phone'
            WHERE user_id='$Id' AND role='teacher'
        ");
    }

    // Update course and status
    mysqli_query($conn,"
        UPDATE teachers SET
        course_id='$course_id',
        status='$status'
        WHERE user_id='$Id'
    ");

    echo "<script type='text/javascript'>
            window.location = 'create_teacher.php';
          </script>";
    exit;
}

// ============================================
// TOTAL RECORDS & PAGINATION
// ============================================

$totalQuery = mysqli_query($conn,"
    SELECT COUNT(*) as total
    FROM users u
    LEFT JOIN teachers t ON u.user_id = t.user_id
    LEFT JOIN courses c ON t.course_id = c.course_id
    WHERE u.role='teacher'
    AND (u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR u.email LIKE '%$search%')
");
$totalRow = mysqli_fetch_assoc($totalQuery);
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $limit);

// ============================================
// FETCH TEACHERS FOR DISPLAY
// ============================================

$dataQuery = mysqli_query($conn,"
    SELECT u.*, t.course_id, t.status, c.course_name
    FROM users u
    LEFT JOIN teachers t ON u.user_id = t.user_id
    LEFT JOIN courses c ON t.course_id = c.course_id
    WHERE u.role='teacher'
    AND (u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR u.email LIKE '%$search%')
    ORDER BY u.user_id DESC
    LIMIT $offset,$limit
");

?>

<main>
    <div class="head-title">
        <div class="left">
            <h1>Teacher</h1>
            <ul class="breadcrumb">
                <li><a href="#">Home</a></li>
                <li><i class='bx bx-chevron-right'></i></li>
                <li><a class="active">Management Information</a></li>
            </ul>
        </div>
    </div>

    <!-- CREATE / UPDATE FORM -->
    <div class="table-data">
        <div class="order">
            <div class="head">
                <h3><?php echo $editRow ? "Update Teacher" : "Create Teacher"; ?></h3>
            </div>

            <?php echo $statusMsg ?? ''; ?>

            <form method="POST" class="form-class">
                <input type="text" name="fname" placeholder="First Name" value="<?php echo $editRow['first_name'] ?? ''; ?>" required>
                <input type="text" name="lname" placeholder="Last Name" value="<?php echo $editRow['last_name'] ?? ''; ?>" required>
                <input type="email" name="email" placeholder="Email" value="<?php echo $editRow['email'] ?? ''; ?>" required>
                <input type="text" name="phone" placeholder="Phone" value="<?php echo $editRow['contact_number'] ?? ''; ?>" required>
                <input type="text" name="password" placeholder="Password <?php echo $editRow ? '(leave blank to keep current)' : ''; ?>" <?php if(!$editRow) echo "required"; ?>>

                <!-- Course -->
                <select name="course_id" required>
                    <option value="">Select Course</option>
                    <?php foreach($courses as $course){ ?>
                        <option value="<?= $course['course_id'] ?>" 
                            <?php if(($editRow['course_id'] ?? '') == $course['course_id']) echo 'selected'; ?>>
                            <?= htmlspecialchars($course['course_name']) ?>
                        </option>
                    <?php } ?>
                </select>

                <!-- Status -->
                <select name="status" required>
                    <option value="">Select Status</option>
                    <?php foreach($statuses as $s){ ?>
                        <option value="<?= $s ?>" <?php if(($editRow['status'] ?? '')==$s) echo 'selected'; ?>><?= $s ?></option>
                    <?php } ?>
                </select>

                <?php if($editRow){ ?>
                    <button type="submit" name="update" class="btn primary">Update</button>
                    <a href="create_teacher.php" class="btn danger small">Cancel</a>
                <?php } else { ?>
                    <button type="submit" name="save" class="btn primary">Save</button>
                <?php } ?>
            </form>
        </div>
    </div>

    <!-- TEACHER TABLE -->
    <div class="table-data">
        <div class="order">
            <div class="head flex-between">
                <h3>All Teachers</h3>
                <div class="row-controls">
                    <input type="search" id="searchInput" class="search-input" placeholder="Search Teacher..." value="<?php echo htmlspecialchars($search); ?>">
                    <select id="limitSelect" class="search-input">
                        <?php foreach([5,10,20,50] as $option){ ?>
                            <option value="<?php echo $option; ?>" <?php if($option==$limit) echo "selected"; ?>><?php echo $option; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Course</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $sn = $offset + 1; while($row = mysqli_fetch_assoc($dataQuery)){ ?>
                    <tr>
                        <td><?php echo $sn++; ?></td>
                        <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td>
                            <a href="?Id=<?php echo $row['user_id']; ?>&action=edit" class="btn small primary">Edit</a>
                            <a href="?Id=<?php echo $row['user_id']; ?>&action=delete" class="btn small danger" onclick="return confirm('Delete this teacher?')">Delete</a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>

            <!-- PAGINATION -->
            <div class="mt-15">
                <?php if($page>1){ ?>
                    <a href="?search=<?php echo $search; ?>&limit=<?php echo $limit; ?>&page=<?php echo $page-1; ?>" class="btn small">Prev</a>
                <?php } ?>
                <?php for($i=1;$i<=$totalPages;$i++){ ?>
                    <a href="?search=<?php echo $search; ?>&limit=<?php echo $limit; ?>&page=<?php echo $i; ?>" class="btn small <?php if($i==$page) echo 'primary'; ?>"><?php echo $i; ?></a>
                <?php } ?>
                <?php if($page<$totalPages){ ?>
                    <a href="?search=<?php echo $search; ?>&limit=<?php echo $limit; ?>&page=<?php echo $page+1; ?>" class="btn small">Next</a>
                <?php } ?>
            </div>
        </div>
    </div>
</main>

<script>
const searchInput = document.getElementById('searchInput');
const limitSelect = document.getElementById('limitSelect');

searchInput.addEventListener('keyup', function(){
    clearTimeout(window.timer);
    window.timer = setTimeout(()=> {
        window.location="?search="+encodeURIComponent(searchInput.value)+"&limit="+limitSelect.value;
    }, 500);
});

limitSelect.addEventListener('change', function(){
    window.location="?search="+encodeURIComponent(searchInput.value)+"&limit="+limitSelect.value;
});
</script>

<?php include 'stms_footer.php'; ?>
