<?php include 'header.php'; ?>


<?php
// ================= INIT =================
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn,$_GET['search']) : '';
$limit  = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$page   = isset($_GET['page']) ? intval($_GET['page']) : 1;
if($limit<=0) $limit=10;
if($page<=0) $page=1;
$offset = ($page-1)*$limit;

// ================= SAVE PARENT =================
$statusMsg = '';
if(isset($_POST['save'])){
    $fname    = mysqli_real_escape_string($conn,$_POST['fname']);
    $lname    = mysqli_real_escape_string($conn,$_POST['lname']);
    $email    = mysqli_real_escape_string($conn,$_POST['email']);
    $phone    = mysqli_real_escape_string($conn,$_POST['phone']);
    $password = mysqli_real_escape_string($conn,$_POST['password']);
    $hashedPassword = hash('sha256', $password);

    // Check if email exists
    $check = mysqli_query($conn,"SELECT user_id FROM users WHERE email='$email' AND role='parent'");
    if(mysqli_num_rows($check) > 0){
        $statusMsg = "<div class='alert alert-danger'>Parent already exists!</div>";
    } else {
        // Insert into users
        mysqli_query($conn,"
            INSERT INTO users (username,password,role,first_name,last_name,email,contact_number)
            VALUES ('$email','$hashedPassword','parent','$fname','$lname','$email','$phone')
        ");
        $user_id = mysqli_insert_id($conn);

        // Insert into parents table
        mysqli_query($conn,"INSERT INTO parents (user_id) VALUES ('$user_id')");

        echo "<script>window.location='create_parents.php';</script>";
        exit;
    }
}

// ================= EDIT PARENT =================
$editRow = null;
if(isset($_GET['action']) && $_GET['action']=='edit'){
    $parent_id = intval($_GET['parent_id']);
    $res = mysqli_query($conn,"
        SELECT p.parent_id, p.user_id, u.first_name, u.last_name, u.email, u.contact_number
        FROM parents p
        LEFT JOIN users u ON p.user_id = u.user_id
        WHERE p.parent_id='$parent_id'
        LIMIT 1
    ");
    $editRow = mysqli_fetch_assoc($res);
}

// ================= UPDATE PARENT =================
if(isset($_POST['update'])){
    $parent_id = intval($_POST['parent_id']);
    $user_id   = intval($_POST['user_id']);
    $fname     = mysqli_real_escape_string($conn,$_POST['fname']);
    $lname     = mysqli_real_escape_string($conn,$_POST['lname']);
    $email     = mysqli_real_escape_string($conn,$_POST['email']);
    $phone     = mysqli_real_escape_string($conn,$_POST['phone']);
    $password  = mysqli_real_escape_string($conn,$_POST['password']);

    if(!empty($password)){
        $hashedPassword = hash('sha256',$password);
        mysqli_query($conn,"
            UPDATE users SET first_name='$fname', last_name='$lname', email='$email', username='$email', contact_number='$phone', password='$hashedPassword'
            WHERE user_id='$user_id'
        ");
    } else {
        mysqli_query($conn,"
            UPDATE users SET first_name='$fname', last_name='$lname', email='$email', username='$email', contact_number='$phone'
            WHERE user_id='$user_id'
        ");
    }

    echo "<script>window.location='create_parents.php';</script>";
    exit;
}

// ================= DELETE PARENT =================
if(isset($_GET['action']) && $_GET['action']=='delete'){
    $parent_id = intval($_GET['parent_id']);
    $res = mysqli_query($conn,"SELECT user_id FROM parents WHERE parent_id='$parent_id'");
    $row = mysqli_fetch_assoc($res);
    $user_id = $row['user_id'] ?? 0;

    mysqli_query($conn,"DELETE FROM parents WHERE parent_id='$parent_id'");
    mysqli_query($conn,"DELETE FROM users WHERE user_id='$user_id'");

    echo "<script>window.location='create_parents.php';</script>";
    exit;
}

// ================= SEARCH & PAGINATION =================
$where = '';
if(!empty($search)){
    $searchEsc = mysqli_real_escape_string($conn,$search);
    $where = "WHERE u.first_name LIKE '%$searchEsc%' OR u.last_name LIKE '%$searchEsc%' OR u.email LIKE '%$searchEsc%' OR u.contact_number LIKE '%$searchEsc%'";
}

// Total records
$totalQuery = mysqli_query($conn,"
    SELECT COUNT(*) as total
    FROM parents p
    LEFT JOIN users u ON p.user_id = u.user_id
    $where
");
$totalRow = mysqli_fetch_assoc($totalQuery);
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $limit);

// Fetch parent data
$dataQuery = mysqli_query($conn,"
    SELECT p.parent_id, p.user_id, u.first_name, u.last_name, u.email, u.contact_number
    FROM parents p
    LEFT JOIN users u ON p.user_id = u.user_id
    $where
    ORDER BY p.parent_id DESC
    LIMIT $offset,$limit
");
?>

<main>
<div class="head-title">
    <div class="left">
        <h1>Parents Management</h1>
        <ul class="breadcrumb">
            <li><a href="#">Home</a></li>
            <li><i class='bx bx-chevron-right'></i></li>
            <li><a class="active">Parent Information</a></li>
        </ul>
    </div>
</div>

<!-- CREATE / EDIT FORM -->
<div class="table-data">
    <div class="order">
        <div class="head"><h3><?php echo $editRow ? "Update Parent" : "Create Parent"; ?></h3></div>
        <?php if(!empty($statusMsg)) echo $statusMsg; ?>
        <form method="POST" class="form-class">
            <input type="hidden" name="parent_id" value="<?php echo $editRow['parent_id'] ?? ''; ?>">
            <input type="hidden" name="user_id" value="<?php echo $editRow['user_id'] ?? ''; ?>">
            <input type="text" name="fname" placeholder="First Name" value="<?php echo $editRow['first_name'] ?? ''; ?>" required>
            <input type="text" name="lname" placeholder="Last Name" value="<?php echo $editRow['last_name'] ?? ''; ?>" required>
            <input type="email" name="email" placeholder="Email" value="<?php echo $editRow['email'] ?? ''; ?>" required>
            <input type="text" name="phone" placeholder="Phone" value="<?php echo $editRow['contact_number'] ?? ''; ?>" required>
            <input type="text" name="password" placeholder="Password <?php echo $editRow ? '(leave blank to keep current)' : ''; ?>" <?php if(!$editRow) echo 'required'; ?>>

            <?php if($editRow){ ?>
                <button type="submit" name="update" class="btn primary"><i class='bx bx-edit'></i> Update</button>
                <a href="create_parents.php" class="btn danger small">Cancel</a>
            <?php } else { ?>
                <button type="submit" name="save" class="btn primary"><i class='bx bx-save'></i> Save</button>
            <?php } ?>
        </form>
    </div>
</div>

<!-- PARENTS LIST -->
<div class="table-data">
    <div class="order">
        <div class="head flex-between">
            <h3>All Parents</h3>
            <div class="row-controls">
                <input type="search" id="searchInput" class="search-input" placeholder="Search Parents..." value="<?php echo htmlspecialchars($search); ?>">
                <select id="limitSelect" class="search-input">
                    <?php foreach([5,10,20,50] as $option){ ?>
                        <option value="<?php echo $option; ?>" <?php if($option==$limit) echo 'selected'; ?>><?php echo $option; ?></option>
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
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($dataQuery)>0){ $sn=$offset+1; while($row=mysqli_fetch_assoc($dataQuery)){ ?>
                    <tr>
                        <td><?php echo $sn++; ?></td>
                        <td><?php echo $row['first_name']; ?></td>
                        <td><?php echo $row['last_name']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td><?php echo $row['contact_number']; ?></td>
                        <td>
                            <a href="?action=edit&parent_id=<?php echo $row['parent_id']; ?>" class="btn small primary">Edit</a>
                            <a href="?action=delete&parent_id=<?php echo $row['parent_id']; ?>" class="btn small danger" onclick="return confirm('Delete this parent?')">Delete</a>
                        </td>
                    </tr>
                <?php } } else { ?>
                    <tr><td colspan="6" class="no-records-center">No records found</td></tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- PAGINATION -->
        <?php if($totalPages>1){ ?>
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
        <?php } ?>
    </div>
</div>
</main>

<script>
const searchInput = document.getElementById('searchInput');
const limitSelect = document.getElementById('limitSelect');
let typingTimer;
const typingDelay = 500;

searchInput.addEventListener('keyup', ()=>{
    clearTimeout(typingTimer);
    typingTimer = setTimeout(()=>{
        const params = new URLSearchParams(window.location.search);
        params.set('search', searchInput.value);
        params.set('limit', limitSelect.value);
        params.set('page',1);
        window.location.search = params.toString();
    }, typingDelay);
});

limitSelect.addEventListener('change', ()=>{
    const params = new URLSearchParams(window.location.search);
    params.set('search', searchInput.value);
    params.set('limit', limitSelect.value);
    params.set('page',1);
    window.location.search = params.toString();
});
</script>

<?php include 'stms_footer.php'; ?>
