<!-- SIDEBAR -->
<section id="sidebar">
<?php
	$current = basename($_SERVER['PHP_SELF']);

	$subwrapPages = [
		'manage-student' => ['manage_students.php'],
		'manage-exam' => ['exam_grades.php'],
		'manage-quiz' => ['create_quizes.php','quizes_grades.php'],
		'manage-final' => ['grading_system.php'],
		'manage-attendance' => ['attendance.php','view_attendance.php','view_student_attendance.php'],
		'manage-exam-dates' => ['exam_dates.php'],
		'manage-subject' => ['subject_schedule.php'],
		'account' => ['teacher_account.php'] // added for Account
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
		<li class="<?php echo is_active($subwrapPages['manage-student'], $current); ?>">
			<a href="manage_students.php">
				<i class='bx bxs-graduation'></i>
				<span class="text">Manage Students</span>
			</a>
		</li>
<!-- Manage Exam Dates -->
		<li class="<?php echo is_active($subwrapPages['manage-exam-dates'], $current); ?>">
			<a href="exam_dates.php">
				<i class='bx bxs-calendar'></i>
				<span class="text">Manage Exam Dates</span>
			</a>
		</li>
		<!-- Manage Exam Grades -->
		<li class="<?php echo is_active($subwrapPages['manage-exam'], $current); ?>">
			<a href="exam_grades.php">
				<i class='bx bxs-file'></i>
				<span class="text">Manage Exam Grades</span>
			</a>
		</li>
		<li class="<?php echo is_active($subwrapPages['manage-subject'], $current); ?>">
			<a href="subject_schedule.php">
				<i class='bx bxs-file'></i>
				<span class="text">Subject Schedule</span>
			</a>
		</li>

		<!-- Manage Quiz Grades -->

		<li class="<?php echo is_active($subwrapPages['manage-quiz'], $current); ?>">
			<a href="#" id="manage-quiz-btn">
				<i class='bx bxs-graduation'></i>
				<span class="text">Manage Quizes</span>
			</a>
		</li>
		<ul class="create-section <?php echo should_show($subwrapPages['manage-quiz'], $current); ?>" id="manage-quiz-subwrap">
			<li><a href="create_quizes.php"><i class='bx bx-plus'></i><span class="text">Create Quiz</span></a></li>
			<li><a href="quizes_grades.php"><i class='bx bx-grid-alt'></i><span class="text">Quiz Grades</span></a></li>
		</ul>

		<!-- Manage Final Grades -->
		<li class="<?php echo is_active($subwrapPages['manage-final'], $current); ?>">
			<a href="grading_system.php">
				<i class='bx bxs-medal'></i>
				<span class="text">Grading System</span>
			</a>
		</li>

		<!-- Manage Attendance -->
	
<li class="<?php echo is_active($subwrapPages['manage-attendance'], $current); ?>">
			<a href="#" id="manage-attendance-btn">
				<i class='bx bxs-calendar-check'></i>
				<span class="text">Manage Attendance</span>
			</a>
		</li>
		<ul class="create-section <?php echo should_show($subwrapPages['manage-attendance'], $current); ?>" id="manage-attendance-subwrap">
			<li><a href="take_attendance.php"><i class='bx bx-plus'></i><span class="text">Take Attendance</span></a></li>
			<li><a href="view_attendance.php"><i class='bx bx-grid-alt'></i><span class="text">View Attendance</span></a></li>
				<li><a href="view_student_attendance.php"><i class='bx bx-grid-alt'></i><span class="text">View Student Attendance</span></a></li>
		</ul>
		

	</ul>

	<ul class="side-menu">

		<!-- Account -->
		<li class="<?php echo is_active($subwrapPages['account'], $current); ?>">
			<a href="teacher_account.php">
				<i class='bx bxs-user-circle'></i>
				<span class="text">Account</span>
			</a>
		</li>

		<!-- Backup and Reset -->
		
	</ul>

	<script>
	(function(){
		var toggleIds = [
			'manage-room',
			'manage-subject',
			'manage-course',
			'manage-quiz',
			'manage-teacher',
			'manage-student',
			'manage-parents',
			'manage-attendance',
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
