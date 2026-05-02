<?php include 'header.php'; ?>

<?php
$statusMsg = "";
$teacherId = $_SESSION['user_id'] ?? 0;

// ================= HANDLE UPDATE =================
if(isset($_POST['update'])){
    $fname  = mysqli_real_escape_string($conn,$_POST['fname']);
    $lname  = mysqli_real_escape_string($conn,$_POST['lname']);
    $email  = mysqli_real_escape_string($conn,$_POST['email']);
    $phone  = mysqli_real_escape_string($conn,$_POST['phone']);
    $password = mysqli_real_escape_string($conn,$_POST['password']);

    $hashedPassword = !empty($password) ? hash('sha256',$password) : null;

    // update users table
    if(!empty($hashedPassword)){
        mysqli_query($conn,"
            UPDATE users SET 
            first_name='$fname',
            last_name='$lname',
            email='$email',
            username='$email',
            contact_number='$phone',
            password='$hashedPassword'
            WHERE user_id='$teacherId' AND role='teacher'
        ");
    } else {
        mysqli_query($conn,"
            UPDATE users SET 
            first_name='$fname',
            last_name='$lname',
            email='$email',
            username='$email',
            contact_number='$phone'
            WHERE user_id='$teacherId' AND role='teacher'
        ");
    }

    $statusMsg = "<div class='alert alert-success'>Profile updated successfully!</div>";
}

// ================= FETCH TEACHER INFO =================
// Join with courses to get department name via course_id
$res = mysqli_query($conn,"
    SELECT u.*, c.department_name AS department_name
    FROM users u
    JOIN teachers t ON u.user_id = t.user_id
    LEFT JOIN courses c ON t.course_id = c.course_id
    WHERE u.user_id = '$teacherId' AND u.role='teacher'
");
$teacher = mysqli_fetch_assoc($res);
?>

<main>
<div class="head-title">
    <div class="left">
        <h1>My Profile</h1>
        <ul class="breadcrumb">
            <li><a href="#">Home</a></li>
            <li><i class='bx bx-chevron-right'></i></li>
            <li><a class="active">Profile</a></li>
        </ul>
    </div>
</div>

<div class="table-data">
    <div class="order">
        <div class="head"><h3>Edit Profile</h3></div>
        <?php echo $statusMsg; ?>
        <form method="POST" class="form-class">
            <input type="text" name="fname" placeholder="First Name" value="<?php echo $teacher['first_name'] ?? ''; ?>" required>
            <input type="text" name="lname" placeholder="Last Name" value="<?php echo $teacher['last_name'] ?? ''; ?>" required>
            <input type="email" name="email" placeholder="Email" value="<?php echo $teacher['email'] ?? ''; ?>" required>
            <input type="text" name="phone" placeholder="Phone" value="<?php echo $teacher['contact_number'] ?? ''; ?>" required>
            
            <input type="text" name="password" placeholder="Password (leave blank to keep current)">

            <input type="text" value="<?php echo $teacher['department_name'] ?? ''; ?>" disabled placeholder="Department">

            <button type="submit" name="update" class="btn primary">Update</button>
        </form>
    </div>
</div>
</main>

<?php include 'stms_footer.php'; ?>
