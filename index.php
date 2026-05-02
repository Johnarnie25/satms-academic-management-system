<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
define('DEBUG_LOGIN', true);

include 'Includes/stms_connection.php'; // your new connection.php
session_start();

if (isset($_POST['Login'])) {

    $userType = $_POST['usertype'];
    $username = trim($_POST['Username']);
    $password = trim($_POST['Password']);

    if (empty($userType)) {
        $_SESSION['login_error'] = "Please select a valid User Role.";
    } else {
        // Prepare query to select user by username/email and role
        $query = "SELECT * FROM users WHERE username = ? AND role = ? LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $username, $userType);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verify SHA2(256) hashed password
            if (hash('sha256', $password) === $user['password']) {
                // Set common session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Redirect based on role
                switch($user['role']){
                    case 'admin':
                        header("Location: Admin/index.php");
                        break;
                    case 'teacher':
                        header("Location: Classteacher/index.php");
                        break;
                    case 'student':
                        header("Location: Students/index.php");
                        break;
                    case 'parent':
                        header("Location: Parents/index.php");
                        break;
                }
                exit();
            } else {
                $_SESSION['login_error'] = "Invalid Password!";
            }
        } else {
            $_SESSION['login_error'] = "Invalid Username or Role!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
<link rel="stylesheet" href="css/swiper-bundle.min.css">
<link rel="stylesheet" href="css/stms_login.css">

<title>Login | SATMS</title>
</head>
<body>

<div class="page">

<!-- LEFT COLUMN WITH SHAPES -->
<div class="logo-area">
    <div class="shape1"></div>
    <div class="shape2"></div>

    <div class="logo-box">
        <img src="stms_images/logo/lacson.png">
        <h1 class="brgy-title">Dr. Gloria D. Lacson Foundation Colleges, Inc.</h1>
        <p class="brgy-address">Barrera District, Maharlika Highway, Cabanatuan City, Nueva Ecija</p>
    </div>

    <div class="divider"></div>
</div>

<!-- RIGHT COLUMN -->
<div class="right-area">
<div class="login__container grid">

<div class="login__swiper swiper">
    <div class="swiper-wrapper">
        <img src="stms_images/login_swiper/lacson1.png" class="login__swiper-img swiper-slide">
        <img src="stms_images/login_swiper/lacson2.png" class="login__swiper-img swiper-slide">
        <img src="stms_images/login_swiper/lacson3.png" class="login__swiper-img swiper-slide">
    </div>
    <div class="swiper-pagination"></div>
</div>

<div class="login__area grid">

<div class="box">
    <img src="stms_images/logo/lacson.png">
</div>

<div class="login__data">
    <h1 class="login__title">STMS</h1>
</div>

<?php if (!empty($_SESSION['login_error'])): ?>
<div style="color:red;text-align:center;margin-bottom:10px;font-weight:bold;">
<?php echo $_SESSION['login_error']; unset($_SESSION['login_error']); ?>
</div>
<?php endif; ?>

<form method="POST" action="" class="login__form">

<div class="login__content grid">

<div class="login__box">
    <input type="text" name="Username" placeholder="Email" class="login__input" required autocomplete="off">
    <i class="ri-mail-line"></i>
</div>

<div class="login__box">
    <select name="usertype" class="login__input" required>
        <option value="">--Select User Roles--</option>
        <option value="student">Student</option>
        <option value="teacher">Teacher</option>
        <option value="admin">Administrator</option>
        <option value="parent">Parent</option>
    </select>
    <i class="ri-user-line"></i>
</div>

<div class="login__box">
    <input type="password" name="Password" placeholder="Password" class="login__input" id="loginPass" required>
    <i class="ri-eye-line login__eye" id="loginEye"></i>
</div>

</div>

<input type="submit" class="login__button" value="Login" name="Login">
</form>

</div>
</div>
</div>
</div>

<script src="Script/swiper-bundle.min.js"></script>
<script src="Script/login_stms.js"></script>
</body>
</html>
