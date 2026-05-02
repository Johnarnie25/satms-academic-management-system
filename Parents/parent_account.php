<?php include 'header.php'; ?>

<?php
$statusMsg = "";
$user_id = $_SESSION['user_id'] ?? 0;

// ================= HANDLE UPDATE =================
if(isset($_POST['update'])){

    $fname  = mysqli_real_escape_string($conn,$_POST['fname']);
    $lname  = mysqli_real_escape_string($conn,$_POST['lname']);
    $email  = mysqli_real_escape_string($conn,$_POST['email']);
    $phone  = mysqli_real_escape_string($conn,$_POST['phone']);
    $password = mysqli_real_escape_string($conn,$_POST['password']);

    $hashedPassword = !empty($password) ? hash('sha256',$password) : null;

    if(!empty($hashedPassword)){
        mysqli_query($conn,"
            UPDATE users SET
            first_name='$fname',
            last_name='$lname',
            email='$email',
            username='$email',
            contact_number='$phone',
            password='$hashedPassword'
            WHERE user_id='$user_id' AND role='parent'
        ");
    }else{
        mysqli_query($conn,"
            UPDATE users SET
            first_name='$fname',
            last_name='$lname',
            email='$email',
            username='$email',
            contact_number='$phone'
            WHERE user_id='$user_id' AND role='parent'
        ");
    }

    $statusMsg = "<div class='alert alert-success'>Profile updated successfully!</div>";
}

// ================= FETCH PARENT INFO =================
$res = mysqli_query($conn,"
SELECT *
FROM users
WHERE user_id = '$user_id' AND role='parent'
LIMIT 1
");

$parent = mysqli_fetch_assoc($res);

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

<div class="head">
<h3>Edit Profile</h3>
</div>

<?php echo $statusMsg; ?>

<form method="POST" class="form-class">

<input type="text"
name="fname"
placeholder="First Name"
value="<?php echo $parent['first_name'] ?? ''; ?>"
required>

<input type="text"
name="lname"
placeholder="Last Name"
value="<?php echo $parent['last_name'] ?? ''; ?>"
required>

<input type="email"
name="email"
placeholder="Email"
value="<?php echo $parent['email'] ?? ''; ?>"
required>

<input type="text"
name="phone"
placeholder="Phone"
value="<?php echo $parent['contact_number'] ?? ''; ?>"
required>

<input type="text"
name="password"
placeholder="Password (leave blank to keep current)">

<button type="submit" name="update" class="btn primary">Update</button>

</form>

</div>
</div>

</main>

<?php include 'stms_footer.php'; ?>
