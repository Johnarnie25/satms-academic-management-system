<?php include 'header.php'; ?>


<?php
/* ================= FILTER VALUES ================= */
$filterSemester = $_GET['filterType'] ?? '';
$filterYear     = $_GET['filterYear'] ?? '';
$filterRoom     = $_GET['filterRoom'] ?? '';

/* ================= ROOM LIST ================= */
$roomList = [];
$roomQuery = $conn->query("SELECT room_name FROM rooms ORDER BY room_name ASC");
while($r = $roomQuery->fetch_assoc()){
    $roomList[] = $r['room_name'];
}

/* ================= FETCH DATA ================= */
$dataRows = [];
$hasFilter = ($filterSemester != '' || $filterYear != '' || $filterRoom != '');

if($hasFilter){
    $conditions = [];

    if($filterSemester != '') $conditions[] = "sub.semester='$filterSemester'";
    if($filterYear != '')     $conditions[] = "sub.year='$filterYear'";
    if($filterRoom != '')     $conditions[] = "r.room_name='$filterRoom'";

    $where = count($conditions) > 0 ? "WHERE ".implode(" AND ", $conditions) : '';

    $sql = "
        SELECT 
            CONCAT(u.first_name,' ',u.last_name) AS teacherName,
            u.email AS teacherEmail,
            sub.semester,
            GROUP_CONCAT(CONCAT(sub.subject_name,'||',sub.year,'||',IFNULL(r.room_name,'')) SEPARATOR '##') AS classData
        FROM subjects sub
        JOIN teachers t ON sub.teacher_id = t.teacher_id
        JOIN users u ON t.user_id = u.user_id
        LEFT JOIN rooms r ON sub.room_id = r.room_id
        $where
        GROUP BY t.teacher_id, sub.semester
        ORDER BY u.first_name ASC
    ";

    $rs = $conn->query($sql);
    if($rs){
        while($row = $rs->fetch_assoc()) $dataRows[] = $row;
    }
}
?>

<main>

   <div class="head-title">
        <div class="left">
            <h1>Room Section Teacher Assignments</h1>
            <ul class="breadcrumb">
                <li><a href="#">Home</a></li>
                <li><i class='bx bx-chevron-right'></i></li>
                <li><a class="active">Management Information</a></li>
            </ul>
        </div>
    </div>

<!-- ================= FILTERS ================= -->
<div class="table-data">
<div class="order">

<div class="head"><h3>Teacher Assignments</h3></div>

<div class="row-controls">

<select id="roomFilter" onchange="reload()" class="search-input">
<option value="">All Room</option>
<?php foreach($roomList as $r){ ?>
<option value="<?= htmlspecialchars($r) ?>" <?= $filterRoom==$r?'selected':'' ?>>
<?= htmlspecialchars($r) ?>
</option>
<?php } ?>
</select>

<select id="yearFilter" onchange="reload()" class="search-input">
<option value="">All Year</option>
<option value="1st Year" <?= $filterYear=='1st Year'?'selected':'' ?>>1st Year</option>
<option value="2nd Year" <?= $filterYear=='2nd Year'?'selected':'' ?>>2nd Year</option>
<option value="3rd Year" <?= $filterYear=='3rd Year'?'selected':'' ?>>3rd Year</option>
<option value="4th Year" <?= $filterYear=='4th Year'?'selected':'' ?>>4th Year</option>
</select>

<select id="semFilter" onchange="reload()" class="search-input">
<option value="">All Semester</option>
<option value="1st Sem" <?= $filterSemester=='1st Sem'?'selected':'' ?>>1st Sem</option>
<option value="2nd Sem" <?= $filterSemester=='2nd Sem'?'selected':'' ?>>2nd Sem</option>
<option value="3rd Sem" <?= $filterSemester=='3rd Sem'?'selected':'' ?>>3rd Sem</option>
<option value="Summer" <?= $filterSemester=='Summer'?'selected':'' ?>>Summer</option>
</select>

</div>

<!-- ================= TABLE ================= -->
<table>
<thead>
<tr>
<th>#</th>
<th>Teacher Name</th>
<th>Email Address</th>
<th>Details</th>
<th>Semester</th>
</tr>
</thead>

<tbody>

<?php
if(!$hasFilter){
    echo "<tr><td colspan='5' style='white-space:nowrap;'>Please select Room / Semester or Year to view data.</td></tr>";
} else if(count($dataRows) > 0){
    $sn = 1;
    foreach($dataRows as $r){
        ?>
        <tr>
            <td><?= $sn++ ?></td>
            <td><?= htmlspecialchars($r['teacherName']) ?></td>
            <td><?= htmlspecialchars($r['teacherEmail']) ?></td>
            <td>
                <button class="btn small primary"
                data-classdata="<?= htmlspecialchars($r['classData'],ENT_QUOTES) ?>"
                onclick="openDetailsModal(this)">View</button>
            </td>
            <td><?= htmlspecialchars($r['semester']) ?></td>
        </tr>
        <?php
    }
} else {
    echo "<tr><td colspan='5'>No records found</td></tr>";
}
?>

</tbody>
</table>

</div>
</div>

</main>

<!-- ================= MODAL ================= -->
<div id="classArmModal" class="modal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="classArmModalTitle">
        <button class="modal-close" aria-label="Close">&times;</button>
        <h5 id="classArmModalTitle">Assigned Subject / Year / Room</h5>

        <div class="modal-body">
            <table class="table table-bordered">
                <thead><tr><th>Subject</th><th>Year</th><th>Room</th></tr></thead>
                <tbody id="classArmModalBody"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
function reload(){
    const p = new URLSearchParams();
    if(roomFilter.value) p.set('filterRoom', roomFilter.value);
    if(yearFilter.value) p.set('filterYear', yearFilter.value);
    if(semFilter.value) p.set('filterType', semFilter.value);
    window.location.search = p.toString();
}

function openDetailsModal(btn){
    let raw = btn.getAttribute('data-classdata');
    if(!raw) return;

    let rows = raw.split('##');
    let html = '';

    rows.forEach(r=>{
        let p = r.split('||');
        const subject = p[0] ? p[0] : '';
        const year = p[1] ? p[1] : '';
        const room = p[2] ? p[2] : '';
        html += `<tr><td>${subject}</td><td>${year}</td><td>${room}</td></tr>`;
    });

    document.getElementById('classArmModalBody').innerHTML = html;

    const modal = document.getElementById('classArmModal');
    modal.setAttribute('aria-hidden','false');

    // attach close handlers
    const closeBtn = modal.querySelector('.modal-close');
    if(closeBtn){
        closeBtn.onclick = () => modal.setAttribute('aria-hidden','true');
    }

    // clicking backdrop closes
    modal.addEventListener('click', function onBackdrop(e){
        if(e.target === modal){
            modal.setAttribute('aria-hidden','true');
            modal.removeEventListener('click', onBackdrop);
        }
    });
}
</script>

<?php include 'stms_footer.php'; ?>
</body>
</html>
