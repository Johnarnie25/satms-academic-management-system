	
<!-- SIDEBAR -->
<section id="sidebar">
<?php

	$current = basename($_SERVER['PHP_SELF']);
	$subwrapPages = [
		'manage-room' => ['create_room_section.php'],
		'manage-subject' => ['assign_course_subject.php','subject_section.php'],
		'manage-course' => ['create_course.php','course_management.php','all_courses.php'],
		'manage-teacher' => ['create_teacher.php','teachers_year1.php','teachers_year2.php','teachers_year3.php','teachers_year4.php','all_teachers.php'],
		'manage-student' => ['create_student.php','assign_students_course.php','students_per_subject.php','academic_history.php'],
		'manage-parents' => ['create_parents.php','assign_parents.php'],
		'administrator' => ['account.php']
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
		<img src="../stms_images/logo/lacson.png" alt="Stms Logo">
	</a>

	<ul class="side-menu top">
		<li class="<?php echo ($current == 'index.php') ? 'active' : ''; ?>">
			<a href="index.php">
				<i class='bx bxs-dashboard'></i>
				<span class="text">Dashboard</span>
			</a>
		</li>

		<!-- Manage Room -->
		<li class="<?php echo is_active($subwrapPages['manage-room'], $current); ?>">
			<a href="#" id="manage-room-btn">
				<i class='bx bxs-door-open'></i>
				<span class="text">Manage Room Section</span>
			</a>
		</li>
		<ul class="create-section <?php echo should_show($subwrapPages['manage-room'], $current); ?>" id="manage-room-subwrap">
			<li>
				<a href="create_room_section.php">
					<i class='bx bx-plus'></i>
					<span class="text">Create Section</span>
				</a>
			</li>
		</ul>


		<!-- Manage Course -->
		<li class="<?php echo is_active($subwrapPages['manage-course'], $current); ?>">
			<a href="#" id="manage-course-btn">
				<i class='bx bxs-graduation'></i>
				<span class="text">Manage Course</span>
			</a>
		</li>
		<ul class="create-section <?php echo should_show($subwrapPages['manage-course'], $current); ?>" id="manage-course-subwrap">
			<li><a href="create_course.php"><i class='bx bx-plus'></i><span class="text">Create Course</span></a></li>
			<li><a href="all_courses.php"><i class='bx bx-grid-alt'></i><span class="text">All Courses</span></a></li>
		</ul>

		<!-- Manage Teacher -->
		<li class="<?php echo is_active($subwrapPages['manage-teacher'], $current); ?>">
			<a href="#" id="manage-teacher-btn">
				<i class='bx bxs-user'></i>
				<span class="text">Manage Teacher</span>
			</a>
		</li>
		<ul class="create-section <?php echo should_show($subwrapPages['manage-teacher'], $current); ?>" id="manage-teacher-subwrap">
			<li><a href="create_teacher.php"><i class='bx bx-plus'></i><span class="text">Create Teacher</span></a></li>
			<li><a href="all_teachers.php"><i class='bx bx-group'></i><span class="text">All Teachers</span></a></li>
		</ul>

		<!-- Manage Subject -->
		<li class="<?php echo is_active($subwrapPages['manage-subject'], $current); ?>">
			<a href="#" id="manage-subject-btn">
				<i class='bx bxs-book-open'></i>
				<span class="text">Manage Subject</span>
			</a>
		</li>
		<ul class="create-section <?php echo should_show($subwrapPages['manage-subject'], $current); ?>" id="manage-subject-subwrap">
			<li><a href="assign_course_subject.php"><i class='bx bx-plus'></i><span class="text">Assign Subject Course</span></a></li>
			<li><a href="subject_section.php"><i class='bx bx-layer'></i><span class="text">Subject / Section</span></a></li>
		
		</ul>
		
		<!-- Manage Student -->
		<li class="<?php echo is_active($subwrapPages['manage-student'], $current); ?>">
			<a href="#" id="manage-student-btn">
				<i class='bx bxs-graduation'></i>
				<span class="text">Manage Student</span>
			</a>
		</li>
		<ul class="create-section <?php echo should_show($subwrapPages['manage-student'], $current); ?>" id="manage-student-subwrap">
			<li><a href="create_student.php"><i class='bx bx-plus'></i><span class="text">Create Students</span></a></li>
			<li><a href="assign_students_course.php"><i class='bx bx-transfer'></i><span class="text">Assign Students Course</span></a></li>
				<li><a href="students_per_subject.php"><i class='bx bx-user'></i><span class="text">Student per Subject</span></a></li>
				<li><a href="academic_history.php"><i class='bx bx-user'></i><span class="text">Academic History</span></a></li>
		</ul>

		<!-- Manage Parents -->
		<li class="<?php echo is_active($subwrapPages['manage-parents'], $current); ?>">
			<a href="#" id="manage-parents-btn">
				<i class='bx bxs-user-pin'></i>
				<span class="text">Manage Parents</span>
			</a>
		</li>
		<ul class="create-section <?php echo should_show($subwrapPages['manage-parents'], $current); ?>" id="manage-parents-subwrap">
			<li><a href="create_parents.php"><i class='bx bx-plus'></i><span class="text">Create Parents</span></a></li>
			<li><a href="assign_parents.php"><i class='bx bx-user-plus'></i><span class="text">Assign Parents Student</span></a></li>
		</ul>

		

	<ul class="side-menu">
		<li class="<?php echo is_active($subwrapPages['administrator'], $current); ?>">
			<a href="account.php">
				<i class='bx bxs-user-circle'></i>
				<span class="text">Administrator</span>
			</a>
		</li>
		
	</ul>

	<script>
	(function(){
		var toggleIds = [
			'manage-room',
			'manage-subject',
			'manage-course',
			'manage-teacher',
			'manage-student',
			'manage-parents',
			'administrator'
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