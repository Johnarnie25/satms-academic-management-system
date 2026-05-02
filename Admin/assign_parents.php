<?php
include 'header.php';


// ---------------- INITIALIZE ----------------
$editingParentId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$statusMsg = "";

// ---------------- FETCH PARENTS & STUDENTS ----------------
// Fetch parents from parents table and join users for name
$parentDropdown = $conn->query("
    SELECT p.parent_id, u.first_name, u.last_name 
    FROM parents p
    INNER JOIN users u ON u.user_id = p.user_id
    ORDER BY u.first_name ASC
");

$parentList = [];
while ($pRow = $parentDropdown->fetch_assoc()) {
    $parentList[] = $pRow;
}

// Fetch all students
$studentList = [];
$studentResult = $conn->query("
    SELECT u.user_id, u.first_name, u.last_name, u.email 
    FROM users u 
    INNER JOIN students s ON s.user_id = u.user_id
    ORDER BY u.first_name ASC
");
while ($s = $studentResult->fetch_assoc()) {
    $studentList[$s['user_id']] = $s;
}

// ---------------- IF EDITING ----------------
$currentStudents = [];
$editRow = null;
if ($editingParentId > 0) {
    // Fetch parent info
    $parentResult = $conn->query("
        SELECT p.parent_id, u.first_name, u.last_name 
        FROM parents p
        INNER JOIN users u ON u.user_id = p.user_id
        WHERE p.parent_id='$editingParentId' LIMIT 1
    ");
    $editRow = $parentResult->fetch_assoc();

    // Get students already assigned to this parent
    $assigned = $conn->query("SELECT user_id FROM students WHERE parent_id='$editingParentId'");
    while ($a = $assigned->fetch_assoc()) {
        $currentStudents[] = $a['user_id'];
    }
}

// ---------------- SAVE ASSIGNMENTS ----------------
if (isset($_POST['save'])) {
    $parentId = intval($_POST['parentId']);
    $selectedStudents = $_POST['studentIds'] ?? [];

    if ($parentId > 0) {
        // Remove previous assignments for this parent
        $conn->query("UPDATE students SET parent_id=NULL WHERE parent_id='$parentId'");

        // Assign selected students to this parent
        foreach ($selectedStudents as $studentId) {
            $studentId = intval($studentId);
            $conn->query("UPDATE students SET parent_id='$parentId' WHERE user_id='$studentId'");
        }

        $statusMsg = "<div class='status success'>Parent assignments updated successfully.</div>";
        $currentStudents = $selectedStudents;

        // Refresh editRow
        $parentResult = $conn->query("
            SELECT p.parent_id, u.first_name, u.last_name 
            FROM parents p
            INNER JOIN users u ON u.user_id = p.user_id
            WHERE p.parent_id='$parentId' LIMIT 1
        ");
        $editRow = $parentResult->fetch_assoc();
    } else {
        $statusMsg = "<div class='status warning'>Please select a parent.</div>";
    }
}

// ---------------- SEARCH & PAGINATION ----------------
$search = $_GET['search'] ?? '';
$limit  = intval($_GET['limit'] ?? 10);
$page   = intval($_GET['page'] ?? 1);
if ($limit <= 0) $limit = 10;
if ($page <= 0) $page = 1;
$offset = ($page - 1) * $limit;

$where = "";
if (!empty($search)) {
    $searchEsc = mysqli_real_escape_string($conn, $search);
    $where = "AND (u.first_name LIKE '%$searchEsc%' OR u.last_name LIKE '%$searchEsc%' OR u.email LIKE '%$searchEsc%')";
}

// Total rows
$totalRows = $conn->query("
    SELECT COUNT(*) as total 
    FROM parents p
    INNER JOIN users u ON u.user_id = p.user_id
    WHERE 1 $where
")->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// ---------------- FETCH PARENTS WITH STUDENTS ----------------
$parentsQuery = $conn->query("
    SELECT p.parent_id, u.first_name AS parent_fname, u.last_name AS parent_lname,
    GROUP_CONCAT(CONCAT(su.first_name,' ',su.last_name,' (',su.email,')') SEPARATOR ', ') AS students_assigned
    FROM parents p
    INNER JOIN users u ON u.user_id = p.user_id
    LEFT JOIN students s ON s.parent_id = p.parent_id
    LEFT JOIN users su ON su.user_id = s.user_id
    WHERE 1 $where
    GROUP BY p.parent_id
    ORDER BY u.first_name ASC
    LIMIT $offset, $limit
");
?>

<main>
<div class="head-title">
    <div class="left">
        <h1>Assign Parents to Students</h1>
        <ul class="breadcrumb">
            <li><a href="#">Home</a></li>
            <li><i class='bx bx-chevron-right'></i></li>
            <li><a class="active">Parent Assignment</a></li>
        </ul>
    </div>
</div>

<!-- ASSIGNMENT FORM -->
<div class="table-data">
<div class="order">
    <div class="head"><h3><?php echo $editingParentId ? "Edit Parent Assignment" : "Assign Parent"; ?></h3></div>

    <?php if(!empty($statusMsg)) echo $statusMsg; ?>

    <form method="POST" class="form-class">
        <!-- Parent Picker (searchable) -->
        <label>Select Parent</label>
        <input type="text" id="parentSearch" class="search-input" placeholder="Search parents..." autocomplete="off">
        <input type="hidden" name="parentId" id="parentIdInput" value="<?php echo $editingParentId; ?>" required>

        <!-- Hidden parent list used as source -->
        <div id="parentHiddenList" style="display:none;">
            <?php foreach($parentList as $p){ ?>
                <div class="parent-item">
                    <input type="radio" name="parentRadio" id="parent_<?php echo $p['parent_id']; ?>" value="<?php echo $p['parent_id']; ?>" <?php if($editingParentId==$p['parent_id']) echo 'checked'; ?>>
                    <label for="parent_<?php echo $p['parent_id']; ?>"><?php echo htmlspecialchars($p['first_name'].' '.$p['last_name']); ?></label>
                </div>
            <?php } ?>
        </div>

        <!-- Parent search results rendered here -->
        <div id="parentSearchResults" class="border p-2" style="max-height:150px; overflow-y:auto;"></div>

        <!-- Student Selection -->
        <label>Select Students</label>
        <input type="text" id="studentSearch" class="search-input" placeholder="Search students...">

        <!-- Hidden checkboxes -->
        <div id="studentCheckboxes" style="display:none;">
            <?php foreach($studentList as $s){ ?>
                <div class="student-item">
                    <input type="checkbox" name="studentIds[]" value="<?php echo $s['user_id']; ?>" id="student_<?php echo $s['user_id']; ?>" <?php echo in_array($s['user_id'], $currentStudents) ? 'checked' : ''; ?>>
                    <label for="student_<?php echo $s['user_id']; ?>"><?php echo htmlspecialchars($s['first_name'].' '.$s['last_name'].' ('.$s['email'].')'); ?></label>
                </div>
            <?php } ?>
        </div>

        <!-- Dynamic search results -->
        <div id="studentSearchResults" class="border p-3" style="max-height:200px; overflow-y:auto;"></div>

        <!-- Search-result styling moved to Css/stms.css to respect dark-mode variables -->

        <button type="submit" name="save" class="btn primary mt-2">Save</button>
    </form>
</div>
</div>

<!-- PARENTS TABLE -->
<div class="table-data">
<div class="order">
<div class="head flex-between">
    <h3>Parents & Assigned Students</h3>
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
<th>Parent Name</th>
<th>Assigned Students</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php if($parentsQuery->num_rows>0){ $sn=$offset+1; while($row=$parentsQuery->fetch_assoc()){ ?>
<tr>
<td><?php echo $sn++; ?></td>
<td><?php echo htmlspecialchars($row['parent_fname'].' '.$row['parent_lname']); ?></td>
<td>
<?php if($row['students_assigned']){ ?>
<button type="button" class="view-btn" data-students="<?php echo htmlspecialchars($row['students_assigned']); ?>">View</button>
<?php }else echo '—'; ?>
</td>
<td>
<a href="?id=<?php echo $row['parent_id']; ?>" class="btn small primary">Edit</a>
</td>
</tr>
<?php } }else{ ?>
<tr><td colspan="4">No records found</td></tr>
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
<!-- MODAL -->
<div id="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);justify-content:center;align-items:center;z-index:9999;">
<div style="background:#fff;padding:20px;border-radius:10px;width:400px;max-height:80%;overflow-y:auto;position:relative;">
<span id="closeModal" style="position:absolute;top:10px;right:15px;cursor:pointer;font-size:20px;">&times;</span>
<h3>Assigned Students</h3>
<ul id="modalList" style="list-style:none;padding-left:0;"></ul>
</div>
</div>
</main>

<script>
// ---------------- Parent search & limit ----------------
const searchInput = document.getElementById('searchInput');
const limitSelect = document.getElementById('limitSelect');
let typingTimer;
const typingDelay = 500;

searchInput.addEventListener('keyup', () => {
    clearTimeout(typingTimer);
    typingTimer = setTimeout(() => {
        const params = new URLSearchParams(window.location.search);
        params.set('search', searchInput.value);
        params.set('limit', limitSelect.value);
        params.set('page', 1);
        window.location.search = params.toString();
    }, typingDelay);
});

limitSelect.addEventListener('change', () => {
    const params = new URLSearchParams(window.location.search);
    params.set('search', searchInput.value);
    params.set('limit', limitSelect.value);
    params.set('page', 1);
    window.location.search = params.toString();
});

// ---------------- Student search filter (vertical) ----------------
const studentSearchInput = document.getElementById('studentSearch');
const studentCheckboxes = document.getElementById('studentCheckboxes');
const searchResults = document.getElementById('studentSearchResults');

const studentsArray = Array.from(studentCheckboxes.querySelectorAll('.student-item'));

function renderItems(items){
    searchResults.innerHTML = '';
    items.forEach(item => {
        const clone = item.cloneNode(true);
        const checkbox = clone.querySelector('input[type="checkbox"]');

        // Sync with hidden checkbox state
        const original = studentCheckboxes.querySelector('#' + checkbox.id);
        if(original) checkbox.checked = original.checked;

        // Keep original and cloned checkbox in sync
        checkbox.addEventListener('change', function() {
            const original = studentCheckboxes.querySelector('#' + this.id);
            if(original) original.checked = this.checked;
        });

        searchResults.appendChild(clone);
    });
}

// Do NOT show all students on load - only render when the user types a query
studentSearchInput.addEventListener('keyup', function() {
    const query = this.value.toLowerCase().trim();

    if (query === '') {
        searchResults.innerHTML = '';
        return;
    }

    const matches = studentsArray.filter(item => item.textContent.toLowerCase().includes(query));
    renderItems(matches);
});

// ---------------- Parent search & selection ----------------
const parentSearchInput = document.getElementById('parentSearch');
const parentHiddenList = document.getElementById('parentHiddenList');
const parentResults = document.getElementById('parentSearchResults');
const parentIdInput = document.getElementById('parentIdInput');

const parentsArray = parentHiddenList ? Array.from(parentHiddenList.querySelectorAll('.parent-item')) : [];

function renderParentItems(items){
    parentResults.innerHTML = '';
    items.forEach(item => {
        const clone = item.cloneNode(true);
        const radio = clone.querySelector('input[type="radio"]');

        // Sync checked state from original
        const original = parentHiddenList.querySelector('#' + radio.id);
        if(original) radio.checked = original.checked;

        // When clicked, set hidden input and sync original
        clone.addEventListener('click', function(e){
            e.preventDefault();
            const orig = parentHiddenList.querySelector('#' + radio.id);
            if(orig){
                // Uncheck all originals then check this one
                parentHiddenList.querySelectorAll('input[type="radio"]').forEach(r=>r.checked=false);
                orig.checked = true;
                parentIdInput.value = orig.value;
            }
            // Also visually mark the clone radio
            parentResults.querySelectorAll('input[type="radio"]').forEach(r=>r.checked=false);
            radio.checked = true;
        });

        // Keep original and clone radios in sync when original changes (useful for edit pre-select)
        const origRadio = parentHiddenList.querySelector('#' + radio.id);
        if(origRadio){
            origRadio.addEventListener('change', function(){
                parentIdInput.value = this.checked ? this.value : parentIdInput.value;
            });
            if(origRadio.checked) parentIdInput.value = origRadio.value;
        }

        parentResults.appendChild(clone);
    });
}

parentSearchInput.addEventListener('keyup', function(){
    const q = this.value.toLowerCase().trim();
    if(q === ''){ parentResults.innerHTML = ''; return; }
    const matches = parentsArray.filter(item => item.textContent.toLowerCase().includes(q));
    renderParentItems(matches);
});
// ---------- Modal ----------
const modal = document.getElementById('modal');
const modalList = document.getElementById('modalList');
const closeModal = document.getElementById('closeModal');
document.addEventListener('click', e=>{
    if(e.target.classList.contains('view-btn')){
        const students = e.target.dataset.students.split(', ');
        modalList.innerHTML='';
        students.forEach(s=>{
            const li=document.createElement('li');
            li.textContent=s;
            li.style.padding='5px 0';
            modalList.appendChild(li);
        });
        modal.style.display='flex';
    }
});
closeModal.addEventListener('click', ()=>modal.style.display='none');
window.addEventListener('click', e=>{if(e.target===modal) modal.style.display='none';});
</script>

<?php include 'stms_footer.php'; ?>
