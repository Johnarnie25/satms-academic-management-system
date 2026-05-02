<?php
include 'header.php';


$statusMsg = "";
$editRow = null;

// -------------------- SAVE --------------------
if(isset($_POST['save'])){
    $roomName = trim($_POST['roomName']);

    $checkStmt = $conn->prepare("SELECT * FROM rooms WHERE room_name = ?");
    $checkStmt->bind_param("s", $roomName);
    $checkStmt->execute();
    $checkRes = $checkStmt->get_result();

    if($checkRes->num_rows > 0){
        $statusMsg = "exists";
    } else {
        $insertStmt = $conn->prepare("INSERT INTO rooms (room_name) VALUES (?)");
        $insertStmt->bind_param("s", $roomName);
        $statusMsg = $insertStmt->execute() ? "success" : "error";
    }
}

// -------------------- EDIT FETCH --------------------
if(isset($_GET['room_id']) && $_GET['action']=="edit"){
    $room_id = (int)$_GET['room_id'];
    $editStmt = $conn->prepare("SELECT * FROM rooms WHERE room_id = ?");
    $editStmt->bind_param("i", $room_id);
    $editStmt->execute();
    $editRes = $editStmt->get_result();
    $editRow = $editRes->fetch_assoc();
}

// -------------------- UPDATE --------------------
if(isset($_POST['update'])){
    $room_id = (int)$_POST['room_id'];
    $roomName = trim($_POST['roomName']);

    $updateStmt = $conn->prepare("UPDATE rooms SET room_name = ? WHERE room_id = ?");
    $updateStmt->bind_param("si", $roomName, $room_id);
    if($updateStmt->execute()){
        echo "<script type='text/javascript'>
                window.location = 'create_room_section.php';
              </script>";
        exit();
    } else {
        $statusMsg = "error";
    }
}

// -------------------- DELETE --------------------
if(isset($_GET['room_id']) && $_GET['action']=="delete"){
    $room_id = (int)$_GET['room_id'];
    $stmt = $conn->prepare("DELETE FROM rooms WHERE room_id=?");
    $stmt->bind_param("i",$room_id);

    if($stmt->execute()){
        echo "<script type='text/javascript'>
                window.location = 'create_room_section.php';
              </script>";
        exit();
    } else {
        $statusMsg = "error";
    }
}

// -------------------- PAGINATION & SEARCH --------------------
$limitOptions = [5,10,20,50];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$searchQuery = $search != "" ? "WHERE room_name LIKE '%".$conn->real_escape_string($search)."%' " : "";

// TOTAL ROWS
$totalRowsRes = $conn->query("SELECT COUNT(*) as total FROM rooms $searchQuery");
$totalRows = $totalRowsRes->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// FETCH PAGINATED DATA
$dataQuery = $conn->query("SELECT * FROM rooms $searchQuery ORDER BY room_id ASC LIMIT $offset, $limit");
?>

<main>
    <div class="head-title">
        <div class="left">
            <h1>Create Room Section</h1>
            <ul class="breadcrumb">
                <li><a href="#">Home</a></li>
                <li><i class='bx bx-chevron-right'></i></li>
                <li><a class="active">Management Information</a></li>
            </ul>
        </div>
    </div>

    <!-- CREATE / UPDATE FORM -->
    <div class="table-data">
        <div class="order">
            <div class="head">
                <h3><?php echo isset($editRow) ? "Update Room" : "Create Room Section"; ?></h3>
            </div>

            <?php
            if($statusMsg=="exists") echo "<p class='status error'>Room already exists</p>";
            elseif($statusMsg=="success") echo "<p class='status success'>Room saved successfully</p>";
            elseif($statusMsg=="error") echo "<p class='status error'>Something went wrong</p>";
            ?>

            <form method="POST" class="form-class">
                <input type="hidden" name="room_id" value="<?php echo isset($editRow['room_id']) ? $editRow['room_id'] : ''; ?>">
                <input type="text" name="roomName" placeholder="Enter Room Name" 
                    value="<?php echo isset($editRow['room_name']) ? $editRow['room_name'] : ''; ?>" required>

                <?php if(isset($editRow)){ ?>
                    <button type="submit" name="update" class="btn primary">
                        <i class='bx bx-edit'></i> Update 
                    </button>
                    <a href="create_room_section.php" class="btn danger small">Cancel</a>
                <?php } else { ?>
                    <button type="submit" name="save" class="btn primary">
                        <i class='bx bx-save'></i> Save 
                    </button>
                <?php } ?>
            </form>
        </div>
    </div>

    <!-- ROOM LIST -->
    <div class="table-data">
        <div class="order">
            <div class="head flex-between">
                <h3>All Room Sections</h3>
                <div class="row-controls">
                    <select name="limit" id="limitSelect" class="search-input">
                        <?php foreach($limitOptions as $option){ ?>
                            <option value="<?php echo $option; ?>" <?php if($option==$limit) echo 'selected'; ?>><?php echo $option; ?></option>
                        <?php } ?>
                    </select>

                    <input type="search" id="searchInput" class="search-input" placeholder="Search Room..." 
                        value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Room Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="roomTableBody">
                    <?php if($totalRows>0){ while($row=mysqli_fetch_assoc($dataQuery)){ ?>
                        <tr>
                            <td><?php echo $row['room_name']; ?></td>
                            <td>
                                <a href="create_room_section.php?room_id=<?php echo $row['room_id']; ?>&action=edit" class="btn small primary">Edit</a>
                                <a href="create_room_section.php?room_id=<?php echo $row['room_id']; ?>&action=delete" 
                                   class="btn small danger" 
                                   onclick="return confirm('Delete this room?');">
                                   Delete
                                </a>
                            </td>
                        </tr>
                    <?php } } else { ?>
                        <tr><td colspan="2" class="no-records-center">No records found</td></tr>
                    <?php } ?>
                </tbody>
            </table>

            <!-- PAGINATION -->
            <?php if($totalPages>1){ ?>
                <div class="mt-15">
                    <?php if($page>1){ ?>
                        <a href="?search=<?php echo urlencode($search); ?>&limit=<?php echo $limit; ?>&page=<?php echo $page-1; ?>" class="btn small">Prev</a>
                    <?php } ?>
                    <?php for($i=1; $i<=$totalPages;$i++){ ?>
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
const typingDelay = 500;

searchInput.addEventListener('keyup', () => {
    clearTimeout(typingTimer);
    typingTimer = setTimeout(() => {
        const params = new URLSearchParams(window.location.search);
        params.set('search', searchInput.value);
        params.set('page', 1);
        params.set('limit', limitSelect.value);
        window.location.search = params.toString();
    }, typingDelay);
});

limitSelect.addEventListener('change', () => {
    const params = new URLSearchParams(window.location.search);
    params.set('limit', limitSelect.value);
    params.set('page', 1);
    params.set('search', searchInput.value);
    window.location.search = params.toString();
});
</script>

<?php include 'stms_footer.php'; ?>
</body>
</html>
