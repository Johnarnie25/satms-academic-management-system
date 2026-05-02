<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
define('DEBUG_LOGIN', true);
include '../Includes/session.php';
include_once 'sidebar.php';
include '../Includes/stms_connection.php'; // updated connection file

$statusMsg = "";

// Fetch logged-in admin info from users table
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $fullName = $user['first_name'] . " " . $user['last_name'];
} else {
    // If somehow user not found, destroy session and redirect
    session_destroy();
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<!-- Boxicons -->
	<link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
	
	<!-- My CSS -->
	<link rel="stylesheet" href="../Css/stms.css">

	<!-- Profile dropdown styles (force readable light-mode and strong dark overrides) -->
	<style>
	.profile-container{position:relative;display:inline-block}
	.profile{display:inline-flex;align-items:center;justify-content:center}
	.profile-dropdown{position:absolute;right:0;top:calc(100% + 8px);background:#ffffff;border-radius:6px;box-shadow:0 6px 18px rgba(0,0,0,0.08);min-width:160px;padding:6px 0;display:none;z-index:60;border:1px solid rgba(0,0,0,0.06);color:#111!important}
	.profile-dropdown .dropdown-item{display:block;padding:8px 14px;color:#111!important;text-decoration:none;font-size:14px;white-space:nowrap}
	.profile-dropdown .dropdown-item:hover{background:#f5f5f5}
	.profile-container.active .profile-dropdown{display:block}
	/* Force readable light-mode appearance unless an explicit dark-mode class/attribute is present */
	body:not(.dark-mode):not(.theme-dark) .profile-dropdown,
	.profile-dropdown:not([data-theme='dark']){background:#ffffff!important;color:#111!important;border-color:rgba(0,0,0,0.06)!important}
	body:not(.dark-mode):not(.theme-dark) .profile-dropdown .dropdown-item,
	.profile-dropdown:not([data-theme='dark']) .dropdown-item{color:#111!important}
		/* Dark-mode overrides (class-based or explicit attribute) */
		body.dark-mode .profile-dropdown, body.theme-dark .profile-dropdown, .profile-dropdown[data-theme='dark']{background:#1e1e1e!important;color:#eaeaea!important;border-color:rgba(255,255,255,0.06)!important}
		body.dark-mode .profile-dropdown .dropdown-item, body.theme-dark .profile-dropdown .dropdown-item, .profile-dropdown[data-theme='dark'] .dropdown-item{color:#eaeaea!important}
		/* Logout-specific styles */
		.profile-dropdown .dropdown-item.logout{color:#d9534f!important;display:flex;align-items:center}
		.profile-dropdown .dropdown-item.logout .logout-icon{color:#d9534f!important;font-size:18px;margin-right:10px}
		.profile-dropdown .dropdown-item.logout .logout-text{color:inherit;font-weight:600}
		/* Hover should show the branded green (#127106) rather than a dark color */
		.profile-dropdown .dropdown-item.logout:hover{color:#127106!important;background:rgba(18,113,6,0.06)!important}
		/* Ensure logout remains red by default in dark-mode, but still use green on hover */
		body.dark-mode .profile-dropdown .dropdown-item.logout, body.theme-dark .profile-dropdown .dropdown-item.logout, .profile-dropdown[data-theme='dark'] .dropdown-item.logout{color:#ff6b6b!important}
		body.dark-mode .profile-dropdown .dropdown-item.logout .logout-icon, body.theme-dark .profile-dropdown .dropdown-item.logout .logout-icon{color:#ff6b6b!important}
		body.dark-mode .profile-dropdown .dropdown-item.logout:hover, body.theme-dark .profile-dropdown .dropdown-item.logout:hover, .profile-dropdown[data-theme='dark'] .dropdown-item.logout:hover{color:#127106!important;background:rgba(18,113,6,0.06)!important}
	@media (prefers-color-scheme: dark){
		.profile-dropdown{background:#1e1e1e!important;color:#eaeaea!important;border-color:rgba(255,255,255,0.06)!important}
		.profile-dropdown .dropdown-item{color:#eaeaea!important}
		.profile-dropdown .dropdown-item:hover{background:#2a2a2a}
	}
	</style>

	<title>STMS</title>
</head>
<body>
<?php include '../Includes/darkmode.php'; ?>
	<!-- CONTENT -->
	<section id="content">
		<!-- NAVBAR -->
		<nav>
			<i class='bx bx-menu' ></i>
			
			<!-- search removed; keep placeholder to preserve layout -->
			<div class="form-placeholder" aria-hidden="true"></div>
			<input type="checkbox" id="switch-mode" hidden>
			<label for="switch-mode" class="switch-mode"></label>
			<div class="datetime" aria-live="polite">
				<span id="current-date">--</span>
				<span id="current-time">--:--:--</span>
			</div>
			<div class="profile-container" id="profile-container">
				<a href="#" id="profile-button" class="profile" title="Edit profile" role="button" aria-haspopup="true" aria-expanded="false">
					<img id="profile-img" src="../stms_images/admin/admin.png" alt="profile">
				</a>
				<div class="profile-dropdown" id="profile-dropdown" role="menu" aria-hidden="true">
					<a href="logout.php" class="dropdown-item logout" role="menuitem">
						<i class='bx bx-log-out logout-icon' aria-hidden="true"></i>
						<span class="logout-text">Logout</span>
					</a>
				</div>
			</div>

			<!-- Profile modal removed -->
		</nav>
			
			<!-- Fallback: ensure date/time show even if external script didn't load -->
			<script>
			document.addEventListener('DOMContentLoaded', function(){
				const dateEl = document.getElementById('current-date');
				const timeEl = document.getElementById('current-time');
				if(!dateEl || !timeEl) return;
				function pad(n){ return n < 10 ? '0'+n : n }
				function updateClock(){
					const now = new Date();
					const d = now.toLocaleDateString(undefined, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
					const t = pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
					dateEl.textContent = d;
					timeEl.textContent = t;
				}
				// If current values are placeholders, start a fallback interval.
				if(dateEl.textContent.trim() === '--' || timeEl.textContent.trim() === '--:--:--'){
					updateClock();
					setInterval(updateClock, 1000);
				}
			});
			</script>
			<script>
			// Profile dropdown toggle + close-on-outside-click
			document.addEventListener('DOMContentLoaded', function(){
				var container = document.getElementById('profile-container');
				if(!container) return;
				var button = document.getElementById('profile-button');
				button.addEventListener('click', function(e){
					e.preventDefault();
					var isActive = container.classList.toggle('active');
					button.setAttribute('aria-expanded', isActive ? 'true' : 'false');
				});
				// Close when clicking outside
				document.addEventListener('click', function(e){
					if(!container.contains(e.target)){
						container.classList.remove('active');
						button.setAttribute('aria-expanded','false');
					}
				});
				// Close on Escape
				document.addEventListener('keydown', function(e){
					if(e.key === 'Escape'){
						container.classList.remove('active');
						button.setAttribute('aria-expanded','false');
					}
				});
			});
			
			</script>
			
			<script src="../Script/stms.js"></script>