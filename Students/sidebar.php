<!-- SIDEBAR -->
<section id="sidebar">
<?php
	$current = basename($_SERVER['PHP_SELF']);

	$subwrapPages = [
		'view-student' => ['view_students.php'],
		'view-exam' => ['exam_grades.php'],
		'view-quiz' => ['view_quizes_grades.php'],
		'view-final' => ['grading_system.php'],
		'view-attendance' => ['view_attendance.php'],
		'view-exam-dates' => ['exam_dates.php'],
		'view-subject' => ['subject_schedule.php'],
		'account' => ['student_account.php'] // added for Account
	];

	function is_active($files, $current){
		if(!is_array($files)) $files = [$files];
		return in_array($current, $files) ? 'active' : '';
	}

	function should_show($files, $current){
		if(!is_array($files)) $files = [$files];
		return in_array($current, $files) ? 'show' : '';
	}
?>

	<a href="#" class="brand">
		<img src="../stms_images/logo/lacson.png" alt="STMS Logo">

	</a>

	<ul class="side-menu top">

		<!-- Dashboard -->
		<li class="<?php echo ($current == 'index.php') ? 'active' : ''; ?>">
			<a href="index.php">
				<i class='bx bxs-dashboard'></i>
				<span class="text">Dashboard</span>
			</a>
		</li>

		<!-- Manage Students -->
		<li class="<?php echo is_active($subwrapPages['view-student'], $current); ?>">
			<a href="view_students.php">
				<i class='bx bxs-graduation'></i>
				<span class="text">Room / Subject</span>
			</a>
		</li>
<!-- Manage Exam Dates -->
		<li class="<?php echo is_active($subwrapPages['view-exam-dates'], $current); ?>">
			<a href="exam_dates.php">
				<i class='bx bxs-calendar'></i>
				<span class="text">View Exam Dates</span>
			</a>
		</li>
		<!-- Manage Exam Grades -->
		<li class="<?php echo is_active($subwrapPages['view-exam'], $current); ?>">
			<a href="exam_grades.php">
				<i class='bx bxs-file'></i>
				<span class="text">View Exam Grades</span>
			</a>
		</li>
		<li class="<?php echo is_active($subwrapPages['view-subject'], $current); ?>">
			<a href="subject_schedule.php">
				<i class='bx bxs-graduation'></i>
				<span class="text">Subject Schedule</span>
			</a>
		</li>

		<!-- Manage Quiz Grades -->
<li class="<?php echo is_active($subwrapPages['view-quiz'], $current); ?>">
			<a href="view_quizes_grades.php">
				<i class='bx bxs-graduation'></i>
				<span class="text">View Quiz Grades</span>
			</a>
		</li>
	

		<!-- Manage Final Grades -->
		<li class="<?php echo is_active($subwrapPages['view-final'], $current); ?>">
			<a href="grading_system.php">
				<i class='bx bxs-medal'></i>
				<span class="text">Grading System</span>
			</a>
		</li>

		<!-- Manage Attendance -->
	
<li class="<?php echo is_active($subwrapPages['view-attendance'], $current); ?>">
			<a href="#" id="view-attendance-btn">
				<i class='bx bxs-calendar-check'></i>
				<span class="text">Manage Attendance</span>
			</a>
		</li>
		<ul class="create-section <?php echo should_show($subwrapPages['view-attendance'], $current); ?>" id="view-attendance-subwrap">
			<li><a href="view_attendance.php"><i class='bx bx-grid-alt'></i><span class="text">View Attendance</span></a></li>
		</ul>
		

	</ul>

	<ul class="side-menu">

		<!-- Account -->
		<li class="<?php echo is_active($subwrapPages['account'], $current); ?>">
			<a href="student_account.php">
				<i class='bx bxs-user-circle'></i>
				<span class="text">Account</span>
			</a>
		</li>

		<!-- Backup and Reset -->
		
	</ul>

	<script>
	(function(){
		var toggleIds = [
			'view-room',
			'view-subject',
			'view-course',
			'view-quiz',
			'view-teacher',
			'view-student',
			'view-parents',
			'view-attendance',
			'account'
		];

		function closeAll(){
			document.querySelectorAll('.create-section').forEach(function(el){
				el.classList.remove('show');
			});
		}

		toggleIds.forEach(function(id){
			var btn = document.getElementById(id + '-btn');
			var wrap = document.getElementById(id + '-subwrap');
			if(!btn || !wrap) return;
			btn.addEventListener('click', function(e){
				e.preventDefault();
				var willShow = !wrap.classList.contains('show');
				closeAll();
				if(willShow) wrap.classList.add('show');
			});
		});

		// close when clicking outside sidebar
		document.addEventListener('click', function(e){
			var sidebar = document.getElementById('sidebar');
			if(sidebar && !sidebar.contains(e.target)){
				closeAll();
			}
		});
	})();
	</script>
</section>
<!-- SIDEBAR -->
