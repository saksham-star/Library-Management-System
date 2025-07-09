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
            case 'borrow':
                $book_id = $_POST['book_id'];
                $member_id = $_POST['member_id'];
                $due_date = $_POST['due_date'];
                
                try {
                    // Check if book is available
                    $stmt = $pdo->prepare("SELECT available_quantity FROM books WHERE book_id = ?");
                    $stmt->execute([$book_id]);
                    $book = $stmt->fetch();
                    
                    if ($book['available_quantity'] > 0) {
                        // Check if member is active
                        $stmt = $pdo->prepare("SELECT status FROM members WHERE member_id = ?");
                        $stmt->execute([$member_id]);
                        $member = $stmt->fetch();
                        
                        if ($member['status'] == 'active') {
                            // Create borrowing record
                            $stmt = $pdo->prepare("INSERT INTO borrowings (book_id, member_id, due_date) VALUES (?, ?, ?)");
                            $stmt->execute([$book_id, $member_id, $due_date]);
                            
                            // Update book availability
                            $stmt = $pdo->prepare("UPDATE books SET available_quantity = available_quantity - 1 WHERE book_id = ?");
                            $stmt->execute([$book_id]);
                            
                            $message = "Book borrowed successfully!";
                        } else {
                            $error = "Member is inactive and cannot borrow books.";
                        }
                    } else {
                        $error = "Book is not available for borrowing.";
                    }
                } catch (PDOException $e) {
                    $error = "Error borrowing book: " . $e->getMessage();
                }
                break;
                
            case 'return':
                $borrowing_id = $_POST['borrowing_id'];
                
                try {
                    // Get borrowing details
                    $stmt = $pdo->prepare("SELECT book_id, due_date FROM borrowings WHERE borrowing_id = ? AND status = 'borrowed'");
                    $stmt->execute([$borrowing_id]);
                    $borrowing = $stmt->fetch();
                    
                    if ($borrowing) {
                        // Calculate fine if overdue
                        $due_date = new DateTime($borrowing['due_date']);
                        $return_date = new DateTime();
                        $fine_amount = 0;
                        
                        if ($return_date > $due_date) {
                            $days_overdue = $return_date->diff($due_date)->days;
                            $fine_amount = $days_overdue * 1.00; // $1 per day
                        }
                        
                        // Update borrowing record
                        $stmt = $pdo->prepare("UPDATE borrowings SET status = 'returned', return_date = CURDATE(), fine_amount = ? WHERE borrowing_id = ?");
                        $stmt->execute([$fine_amount, $borrowing_id]);
                        
                        // Update book availability
                        $stmt = $pdo->prepare("UPDATE books SET available_quantity = available_quantity + 1 WHERE book_id = ?");
                        $stmt->execute([$borrowing['book_id']]);
                        
                        if ($fine_amount > 0) {
                            $message = "Book returned successfully! Fine amount: $" . number_format($fine_amount, 2);
                        } else {
                            $message = "Book returned successfully!";
                        }
                    } else {
                        $error = "Borrowing record not found or already returned.";
                    }
                } catch (PDOException $e) {
                    $error = "Error returning book: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get borrowings with search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(bk.title LIKE ? OR m.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where_conditions[] = "b.status = ?";
    $params[] = $status_filter;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

$stmt = $pdo->prepare("
    SELECT b.*, bk.title as book_title, bk.author, m.name as member_name, m.email as member_email
    FROM borrowings b
    JOIN books bk ON b.book_id = bk.book_id
    JOIN members m ON b.member_id = m.member_id
    $where_clause
    ORDER BY b.created_at DESC
");
$stmt->execute($params);
$borrowings = $stmt->fetchAll();

// Get available books for borrowing
$stmt = $pdo->query("SELECT book_id, title, author, available_quantity FROM books WHERE available_quantity > 0 ORDER BY title");
$available_books = $stmt->fetchAll();

// Get active members for borrowing
$stmt = $pdo->query("SELECT member_id, name, email FROM members WHERE status = 'active' ORDER BY name");
$active_members = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-exchange-alt me-2"></i>Borrowings Management</h2>
    <div>
        <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#borrowBookModal">
            <i class="fas fa-hand-holding me-2"></i>Borrow Book
        </button>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#returnBookModal">
            <i class="fas fa-undo me-2"></i>Return Book
        </button>
    </div>
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
                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by book title or member name">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="borrowed" <?php echo $status_filter == 'borrowed' ? 'selected' : ''; ?>>Borrowed</option>
                    <option value="returned" <?php echo $status_filter == 'returned' ? 'selected' : ''; ?>>Returned</option>
                    <option value="overdue" <?php echo $status_filter == 'overdue' ? 'selected' : ''; ?>>Overdue</option>
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
                <a href="borrowings.php" class="btn btn-secondary w-100">
                    <i class="fas fa-times me-2"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Borrowings Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Borrowings List (<?php echo count($borrowings); ?> records)</h5>
    </div>
    <div class="card-body">
        <?php if (empty($borrowings)): ?>
            <p class="text-muted text-center">No borrowings found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Book</th>
                            <th>Member</th>
                            <th>Borrow Date</th>
                            <th>Due Date</th>
                            <th>Return Date</th>
                            <th>Status</th>
                            <th>Fine</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($borrowings as $borrowing): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($borrowing['book_title']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($borrowing['author']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($borrowing['member_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($borrowing['member_email']); ?></small>
                                </td>
                                <td><?php echo $borrowing['borrow_date']; ?></td>
                                <td>
                                    <?php 
                                    $due_date = new DateTime($borrowing['due_date']);
                                    $today = new DateTime();
                                    $is_overdue = $borrowing['status'] == 'borrowed' && $today > $due_date;
                                    ?>
                                    <span class="<?php echo $is_overdue ? 'text-danger fw-bold' : ''; ?>">
                                        <?php echo $borrowing['due_date']; ?>
                                    </span>
                                </td>
                                <td><?php echo $borrowing['return_date'] ?: '-'; ?></td>
                                <td>
                                    <?php if ($borrowing['status'] == 'borrowed'): ?>
                                        <?php if ($is_overdue): ?>
                                            <span class="badge bg-danger">Overdue</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">Borrowed</span>
                                        <?php endif; ?>
                                    <?php elseif ($borrowing['status'] == 'returned'): ?>
                                        <span class="badge bg-success">Returned</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning"><?php echo ucfirst($borrowing['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($borrowing['fine_amount'] > 0): ?>
                                        <span class="text-danger fw-bold">$<?php echo number_format($borrowing['fine_amount'], 2); ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Borrow Book Modal -->
<div class="modal fade" id="borrowBookModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Borrow Book</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="borrow">
                    
                    <div class="mb-3">
                        <label for="book_id" class="form-label">Book *</label>
                        <select class="form-select" id="book_id" name="book_id" required>
                            <option value="">Select a book</option>
                            <?php foreach ($available_books as $book): ?>
                                <option value="<?php echo $book['book_id']; ?>">
                                    <?php echo htmlspecialchars($book['title']); ?> by <?php echo htmlspecialchars($book['author']); ?> 
                                    (<?php echo $book['available_quantity']; ?> available)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="member_id" class="form-label">Member *</label>
                        <select class="form-select" id="member_id" name="member_id" required>
                            <option value="">Select a member</option>
                            <?php foreach ($active_members as $member): ?>
                                <option value="<?php echo $member['member_id']; ?>">
                                    <?php echo htmlspecialchars($member['name']); ?> (<?php echo htmlspecialchars($member['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="due_date" class="form-label">Due Date *</label>
                        <input type="date" class="form-control" id="due_date" name="due_date" 
                               min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>" required>
                        <small class="text-muted">Default is 14 days from today</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Borrow Book</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Return Book Modal -->
<div class="modal fade" id="returnBookModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Return Book</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="return">
                    
                    <div class="mb-3">
                        <label for="borrowing_id" class="form-label">Select Borrowing *</label>
                        <select class="form-select" id="borrowing_id" name="borrowing_id" required>
                            <option value="">Select a borrowing record</option>
                            <?php foreach ($borrowings as $borrowing): ?>
                                <?php if ($borrowing['status'] == 'borrowed'): ?>
                                    <option value="<?php echo $borrowing['borrowing_id']; ?>">
                                        <?php echo htmlspecialchars($borrowing['book_title']); ?> - 
                                        <?php echo htmlspecialchars($borrowing['member_name']); ?> 
                                        (Due: <?php echo $borrowing['due_date']; ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Fine amount will be calculated automatically if the book is overdue ($1 per day).
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Return Book</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 