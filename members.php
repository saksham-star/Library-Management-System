<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = $_POST['name'];
                $email = $_POST['email'];
                $phone = $_POST['phone'];
                $address = $_POST['address'];
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO members (name, email, phone, address) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $email, $phone, $address]);
                    $message = "Member added successfully!";
                } catch (PDOException $e) {
                    $error = "Error adding member: " . $e->getMessage();
                }
                break;
                
            case 'edit':
                $member_id = $_POST['member_id'];
                $name = $_POST['name'];
                $email = $_POST['email'];
                $phone = $_POST['phone'];
                $address = $_POST['address'];
                $status = $_POST['status'];
                
                try {
                    $stmt = $pdo->prepare("UPDATE members SET name = ?, email = ?, phone = ?, address = ?, status = ? WHERE member_id = ?");
                    $stmt->execute([$name, $email, $phone, $address, $status, $member_id]);
                    $message = "Member updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating member: " . $e->getMessage();
                }
                break;
                
            case 'delete':
                $member_id = $_POST['member_id'];
                
                try {
                    // Check if member has active borrowings
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE member_id = ? AND status = 'borrowed'");
                    $stmt->execute([$member_id]);
                    $active_borrowings = $stmt->fetchColumn();
                    
                    if ($active_borrowings > 0) {
                        $error = "Cannot delete member: They have active book borrowings.";
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM members WHERE member_id = ?");
                        $stmt->execute([$member_id]);
                        $message = "Member deleted successfully!";
                    }
                } catch (PDOException $e) {
                    $error = "Error deleting member: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get members with search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

$stmt = $pdo->prepare("SELECT * FROM members $where_clause ORDER BY name");
$stmt->execute($params);
$members = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-users me-2"></i>Members Management</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
        <i class="fas fa-plus me-2"></i>Add New Member
    </button>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Search and Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, email, or phone">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Search
                </button>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <a href="members.php" class="btn btn-secondary w-100">
                    <i class="fas fa-times me-2"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Members Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Members List (<?php echo count($members); ?> members)</h5>
    </div>
    <div class="card-body">
        <?php if (empty($members)): ?>
            <p class="text-muted text-center">No members found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Membership Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($member['name']); ?></td>
                                <td><?php echo htmlspecialchars($member['email']); ?></td>
                                <td><?php echo htmlspecialchars($member['phone']); ?></td>
                                <td><?php echo htmlspecialchars($member['address']); ?></td>
                                <td><?php echo $member['membership_date']; ?></td>
                                <td>
                                    <?php if ($member['status'] == 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="editMember(<?php echo $member['member_id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteMember(<?php echo $member['member_id']; ?>, '<?php echo htmlspecialchars($member['name']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone">
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Member Modal -->
<div class="modal fade" id="editMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="member_id" id="edit_member_id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name *</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="edit_phone" name="phone">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_address" class="form-label">Address</label>
                        <textarea class="form-control" id="edit_address" name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the member "<span id="delete_member_name"></span>"?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <form method="POST">
                <div class="modal-footer">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="member_id" id="delete_member_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editMember(memberId) {
    // Fetch member data and populate modal
    fetch(`api/get_member.php?id=${memberId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_member_id').value = data.member_id;
            document.getElementById('edit_name').value = data.name;
            document.getElementById('edit_email').value = data.email;
            document.getElementById('edit_phone').value = data.phone;
            document.getElementById('edit_address').value = data.address;
            document.getElementById('edit_status').value = data.status;
            
            new bootstrap.Modal(document.getElementById('editMemberModal')).show();
        });
}

function deleteMember(memberId, memberName) {
    document.getElementById('delete_member_id').value = memberId;
    document.getElementById('delete_member_name').textContent = memberName;
    new bootstrap.Modal(document.getElementById('deleteMemberModal')).show();
}
</script>

<?php include 'includes/footer.php'; ?> 