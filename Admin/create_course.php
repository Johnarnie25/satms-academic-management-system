<?php include 'header.php'; ?>

<?php 
$editRow = null;
$statusMsg = "";

// ================= DEPARTMENTS LIST =================
// You can define departments here as an array if you don't have a departments table
$departments = ["CITE", "BSED", "BSN", "BSHM", "CELA"]; // Example departments

// ================= SAVE =================
if(isset($_POST['save'])){
    $courseName = mysqli_real_escape_string($conn, $_POST['courseName']);
    $departmentName = mysqli_real_escape_string($conn, $_POST['department']);

    // Check duplicate course
    $check = mysqli_query($conn, "SELECT * FROM courses WHERE course_name='$courseName'");
    if(mysqli_num_rows($check) > 0){
        $statusMsg = "<p class='status error'>Course already exists</p>";
    } else {
        $insert = mysqli_query($conn, "INSERT INTO courses(course_name, department_name) VALUES('$courseName', '$departmentName')");
        if($insert){
            echo "<script>window.location='create_course.php';</script>";
        } else {
            $statusMsg = "<p class='status error'>An error occurred!</p>";
        }
    }
}

// ================= EDIT FETCH =================
if(isset($_GET['id'], $_GET['action']) && $_GET['action'] == "edit"){
    $id = $_GET['id'];
    $editQuery = mysqli_query($conn, "SELECT * FROM courses WHERE course_id='$id'");
    $editRow = mysqli_fetch_assoc($editQuery);
}

// ================= UPDATE =================
if(isset($_POST['update'])){
    $id = $_POST['id'];
    $courseName = mysqli_real_escape_string($conn, $_POST['courseName']);
    $departmentName = mysqli_real_escape_string($conn, $_POST['department']);

    $update = mysqli_query($conn, "UPDATE courses SET course_name='$courseName', department_name='$departmentName' WHERE course_id='$id'");
    if($update){
        echo "<script>window.location='create_course.php';</script>";
    } else {
        $statusMsg = "<p class='status error'>An error occurred!</p>";
    }
}

// ================= DELETE =================
if(isset($_GET['id'], $_GET['action']) && $_GET['action'] == "delete"){
    $id = $_GET['id'];
    $delete = mysqli_query($conn, "DELETE FROM courses WHERE course_id='$id'");
    if($delete){
        echo "<script>window.location='create_course.php';</script>";
    } else {
        $statusMsg = "<p class='status error'>An error occurred!</p>";
    }
}

// ================= SEARCH + PAGINATION =================
$limitOptions = [5,10,20,50];
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $limitOptions) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : "";
$searchQuery = $search != "" ? "WHERE course_name LIKE '%$search%'" : "";

// total rows
$totalRowsRes = mysqli_query($conn, "SELECT COUNT(*) as total FROM courses $searchQuery");
$totalRows = mysqli_fetch_assoc($totalRowsRes)['total'];
$totalPages = ceil($totalRows / $limit);

// fetch data
$dataQuery = mysqli_query($conn, "SELECT * FROM courses $searchQuery ORDER BY course_id DESC LIMIT $offset, $limit");
?>

<main>
    <div class="head-title">
        <div class="left">
            <h1>Create Course</h1>
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
                <h3><?php echo isset($editRow) ? "Update Course" : "Create Course"; ?></h3>
            </div>

            <?php echo $statusMsg; ?>

            <form method="POST" class="form-class">
                <input type="hidden" name="id" value="<?php echo $editRow['course_id'] ?? ''; ?>">

                <input type="text" name="courseName" placeholder="Enter Course Name" value="<?php echo $editRow['course_name'] ?? ''; ?>" required>

                <!-- DEPARTMENT DROPDOWN -->
                <select name="department" required>
                    <option value="">Select Department</option>
                    <?php foreach($departments as $dep){ ?>
                        <option value="<?php echo $dep; ?>" <?php if(isset($editRow) && $editRow['department_name']==$dep) echo "selected"; ?>>
                            <?php echo $dep; ?>
                        </option>
                    <?php } ?>
                </select>

                <div class="form-actions">
                    <?php if(isset($editRow)){ ?>
                        <button type="submit" name="update" class="btn primary">Update</button>
                        <a href="create_course.php" class="btn danger small">Cancel</a>
                    <?php } else { ?>
                        <button type="submit" name="save" class="btn primary">Save</button>
                    <?php } ?>
                </div>
            </form>
        </div>
    </div>

    <!-- TABLE -->
    <div class="table-data">
        <div class="order">
            <div class="head flex-between">
                <h3>All Courses</h3>
                <div class="row-controls">
                    <select id="limitSelect" class="search-input">
                        <?php foreach($limitOptions as $option){ ?>
                            <option value="<?php echo $option;?>" <?php if($option==$limit) echo "selected"; ?>><?php echo $option; ?></option>
                        <?php } ?>
                    </select>

                    <input type="search" id="searchInput" class="search-input" placeholder="Search Course..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Course Name</th>
                        <th>Department</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if($totalRows > 0){
                        while($row = mysqli_fetch_assoc($dataQuery)){ ?>
                            <tr>
                                <td><?php echo $row['course_name']; ?></td>
                                <td><?php echo $row['department_name']; ?></td>
                                <td>
                                    <a href="create_course.php?id=<?php echo $row['course_id']; ?>&action=edit" class="btn small primary">Edit</a>
                                    <a href="create_course.php?id=<?php echo $row['course_id']; ?>&action=delete" class="btn small danger" onclick="return confirm('Delete this course?')">Delete</a>
                                </td>
                            </tr>
                        <?php }
                    } else { ?>
                        <tr><td colspan="3" class="no-records-center">No records found</td></tr>
                    <?php } ?>
                </tbody>
            </table>

            <!-- PAGINATION -->
            <?php if($totalPages > 1){ ?>
                <div class="mt-15">
                    <?php if($page > 1){ ?>
                        <a href="?search=<?php echo $search;?>&limit=<?php echo $limit;?>&page=<?php echo $page-1;?>" class="btn small">Prev</a>
                    <?php } ?>
                    <?php for($i = 1; $i <= $totalPages; $i++){ ?>
                        <a href="?search=<?php echo $search;?>&limit=<?php echo $limit;?>&page=<?php echo $i;?>" class="btn small <?php if($i==$page) echo 'primary';?>">
                            <?php echo $i;?>
                        </a>
                    <?php } ?>
                    <?php if($page < $totalPages){ ?>
                        <a href="?search=<?php echo $search;?>&limit=<?php echo $limit;?>&page=<?php echo $page+1;?>" class="btn small">Next</a>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </div>
</main>

<script>
const searchInput = document.getElementById('searchInput');
const limitSelect = document.getElementById('limitSelect');

// INSTANT SEARCH
searchInput.addEventListener('keyup', ()=>{
    const params = new URLSearchParams(window.location.search);
    params.set('search', searchInput.value);
    params.set('limit', limitSelect.value);
    params.set('page', 1);
    window.location.search = params.toString();
});

// LIMIT CHANGE
limitSelect.addEventListener('change', ()=>{
    const params = new URLSearchParams(window.location.search);
    params.set('search', searchInput.value);
    params.set('limit', limitSelect.value);
    params.set('page', 1);
    window.location.search = params.toString();
});
</script>

<?php include 'stms_footer.php'; ?>
</body>
</html>
