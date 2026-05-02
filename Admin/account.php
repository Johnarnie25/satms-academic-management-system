<?php include 'header.php'; ?>


<?php
// ================= FILTERS / SEARCH / PAGINATION =================
$search = $_GET['search'] ?? '';
$limit  = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$limit  = $limit>0 ? $limit : 10;
$page   = isset($_GET['page']) ? intval($_GET['page']) : 1;
$page   = $page>0 ? $page : 1;
$offset = ($page-1)*$limit;

// ================= HANDLE CREATE / UPDATE =================
$statusMsg = "";

if(isset($_POST['save']) || isset($_POST['update'])){
    $fname  = mysqli_real_escape_string($conn,$_POST['fname']);
    $lname  = mysqli_real_escape_string($conn,$_POST['lname']);
    $email  = mysqli_real_escape_string($conn,$_POST['email']);
    $phone  = mysqli_real_escape_string($conn,$_POST['phone']);
    $password = mysqli_real_escape_string($conn,$_POST['password']);

    $hashedPassword = !empty($password) ? hash('sha256',$password) : null;

    if(isset($_POST['save'])){
        // check duplicate email
        $check = mysqli_query($conn,"SELECT user_id FROM users WHERE email='$email' AND role='admin' LIMIT 1");
        if(mysqli_num_rows($check)>0){
            $statusMsg = "<div class='alert alert-danger'>Admin email already exists!</div>";
        } else {
            $insert = mysqli_query($conn,"
                INSERT INTO users (username,password,role,first_name,last_name,email,contact_number)
                VALUES ('$email','$hashedPassword','admin','$fname','$lname','$email','$phone')
            ");
            $statusMsg = $insert ? "<div class='alert alert-success'>Admin created successfully!</div>" : "<div class='alert alert-danger'>Error creating admin!</div>";
        }
    }

    if(isset($_POST['update'])){
        $Id = intval($_POST['Id']);
        if(!empty($hashedPassword)){
            mysqli_query($conn,"
                UPDATE users SET 
                first_name='$fname',
                last_name='$lname',
                email='$email',
                username='$email',
                contact_number='$phone',
                password='$hashedPassword'
                WHERE user_id='$Id' AND role='admin'
            ");
        } else {
            mysqli_query($conn,"
                UPDATE users SET 
                first_name='$fname',
                last_name='$lname',
                email='$email',
                username='$email',
                contact_number='$phone'
                WHERE user_id='$Id' AND role='admin'
            ");
        }
        $statusMsg = "<div class='alert alert-success'>Admin updated successfully!</div>";
    }
}

// ================= HANDLE DELETE =================
if(isset($_GET['action']) && $_GET['action']=='delete' && isset($_GET['Id'])){
    $Id = intval($_GET['Id']);
    mysqli_query($conn,"DELETE FROM users WHERE user_id='$Id' AND role='admin'");
    $statusMsg = "<div class='alert alert-success'>Admin deleted successfully!</div>";
}

// ================= FETCH ADMIN FOR EDIT =================
$editRow = null;
if(isset($_GET['action']) && $_GET['action']=='edit' && isset($_GET['Id'])){
    $Id = intval($_GET['Id']);
    $res = mysqli_query($conn,"SELECT * FROM users WHERE user_id='$Id' AND role='admin'");
    $editRow = mysqli_fetch_assoc($res);
}

// ================= FETCH ADMINS =================
$searchSql = "";
if(!empty($search)){
    $s = mysqli_real_escape_string($conn,$search);
    $searchSql = " AND (first_name LIKE '%$s%' OR last_name LIKE '%$s%' OR email LIKE '%$s%') ";
}

// total rows
$totalQuery = mysqli_query($conn,"SELECT COUNT(*) as total FROM users WHERE role='admin' $searchSql");
$totalRows = mysqli_fetch_assoc($totalQuery)['total'];
$totalPages = ceil($totalRows / $limit);

// fetch current page
$dataQuery = mysqli_query($conn,"SELECT * FROM users WHERE role='admin' $searchSql ORDER BY first_name ASC LIMIT $offset,$limit");
?>

<main>
<div class="head-title">
    <div class="left">
        <h1>Administrator</h1>
        <ul class="breadcrumb">
            <li><a href="#">Home</a></li>
            <li><i class='bx bx-chevron-right'></i></li>
            <li><a class="active">Account Management</a></li>
        </ul>
    </div>
</div>

<!-- CREATE / UPDATE FORM -->
<div class="table-data">
    <div class="order">
        <div class="head"><h3><?php echo $editRow ? "Update Admin" : "Create Admin"; ?></h3></div>
        <?php echo $statusMsg; ?>
        <form method="POST" class="form-class">
            <input type="hidden" name="Id" value="<?php echo $editRow['user_id'] ?? ''; ?>">
            <input type="text" name="fname" placeholder="First Name" value="<?php echo $editRow['first_name'] ?? ''; ?>" required>
            <input type="text" name="lname" placeholder="Last Name" value="<?php echo $editRow['last_name'] ?? ''; ?>" required>
            <input type="email" name="email" placeholder="Email" value="<?php echo $editRow['email'] ?? ''; ?>" required>
            <input type="text" name="phone" placeholder="Phone" value="<?php echo $editRow['contact_number'] ?? ''; ?>" required>
            <input type="text" name="password" placeholder="Password <?php echo $editRow?'(leave blank to keep current)':''; ?>" <?php if(!$editRow) echo 'required'; ?>>

            <?php if($editRow){ ?>
                <button type="submit" name="update" class="btn primary">Update</button>
                <a href="account.php" class="btn danger small">Cancel</a>
            <?php } else { ?>
                <button type="submit" name="save" class="btn primary">Save</button>
            <?php } ?>
        </form>
    </div>
</div>

<!-- ADMIN LIST -->
<div class="table-data">
    <div class="order">
        <div class="head flex-between">
            <h3>All Administrators</h3>
            <div class="row-controls">
                <input type="search" id="searchInput" class="search-input" placeholder="Search Admin..." value="<?php echo htmlspecialchars($search); ?>">
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
                            <a href="account.php?Id=<?php echo $row['user_id']; ?>&action=edit" class="btn small primary">Edit</a>
                            <a href="account.php?Id=<?php echo $row['user_id']; ?>&action=delete" class="btn small danger" onclick="return confirm('Delete this admin?')">Delete</a>
                        </td>
                    </tr>
                <?php } } else { ?>
                    <tr><td colspan="6" class="text-center">No records found</td></tr>
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
searchInput.addEventListener('keyup', ()=>{
    clearTimeout(typingTimer);
    typingTimer = setTimeout(()=>{
        const params = new URLSearchParams(window.location.search);
        params.set('search', searchInput.value);
        params.set('limit', limitSelect.value);
        params.set('page',1);
        window.location.search = params.toString();
    },500);
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
