<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_books FROM books");
$total_books = $stmt->fetch()['total_books'];

$stmt = $pdo->query("SELECT COUNT(*) as total_members FROM members WHERE status = 'active'");
$total_members = $stmt->fetch()['total_members'];

$stmt = $pdo->query("SELECT COUNT(*) as borrowed_books FROM borrowings WHERE status = 'borrowed'");
$borrowed_books = $stmt->fetch()['borrowed_books'];

$stmt = $pdo->query("SELECT COUNT(*) as overdue_books FROM borrowings WHERE status = 'borrowed' AND due_date < CURDATE()");
$overdue_books = $stmt->fetch()['overdue_books'];

// Get recent borrowings
$stmt = $pdo->query("
    SELECT b.borrowing_id, b.borrow_date, b.due_date, b.status,
           bk.title as book_title, m.name as member_name
    FROM borrowings b
    JOIN books bk ON b.book_id = bk.book_id
    JOIN members m ON b.member_id = m.member_id
    ORDER BY b.created_at DESC
    LIMIT 5
");
$recent_borrowings = $stmt->fetchAll();

// Get popular books
$stmt = $pdo->query("
    SELECT bk.title, bk.author, COUNT(b.borrowing_id) as borrow_count
    FROM books bk
    LEFT JOIN borrowings b ON bk.book_id = b.book_id
    GROUP BY bk.book_id
    ORDER BY borrow_count DESC
    LIMIT 5
");
$popular_books = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h2>
    <div class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?php echo $total_books; ?></h4>
                        <p class="card-text">Total Books</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-book fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?php echo $total_members; ?></h4>
                        <p class="card-text">Active Members</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?php echo $borrowed_books; ?></h4>
                        <p class="card-text">Borrowed Books</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-exchange-alt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?php echo $overdue_books; ?></h4>
                        <p class="card-text">Overdue Books</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity and Popular Books -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clock me-2"></i>Recent Borrowings
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_borrowings)): ?>
                    <p class="text-muted">No recent borrowings</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>Member</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_borrowings as $borrowing): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($borrowing['book_title']); ?></td>
                                        <td><?php echo htmlspecialchars($borrowing['member_name']); ?></td>
                                        <td><?php echo $borrowing['due_date']; ?></td>
                                        <td>
                                            <?php if ($borrowing['status'] == 'borrowed'): ?>
                                                <span class="badge bg-primary">Borrowed</span>
                                            <?php elseif ($borrowing['status'] == 'returned'): ?>
                                                <span class="badge bg-success">Returned</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Overdue</span>
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
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-star me-2"></i>Popular Books
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($popular_books)): ?>
                    <p class="text-muted">No books borrowed yet</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>Author</th>
                                    <th>Borrow Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($popular_books as $book): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $book['borrow_count']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <a href="books.php?action=add" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-2"></i>Add Book
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="members.php?action=add" class="btn btn-success w-100">
                            <i class="fas fa-user-plus me-2"></i>Add Member
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="borrowings.php?action=borrow" class="btn btn-info w-100">
                            <i class="fas fa-hand-holding me-2"></i>Borrow Book
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="borrowings.php?action=return" class="btn btn-warning w-100">
                            <i class="fas fa-undo me-2"></i>Return Book
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
