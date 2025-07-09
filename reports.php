<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get overall statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_books FROM books");
$total_books = $stmt->fetch()['total_books'];

$stmt = $pdo->query("SELECT COUNT(*) as total_members FROM members WHERE status = 'active'");
$total_members = $stmt->fetch()['total_members'];

$stmt = $pdo->query("SELECT COUNT(*) as total_borrowings FROM borrowings");
$total_borrowings = $stmt->fetch()['total_borrowings'];

$stmt = $pdo->query("SELECT COUNT(*) as overdue_books FROM borrowings WHERE status = 'borrowed' AND due_date < CURDATE()");
$overdue_books = $stmt->fetch()['overdue_books'];

// Get monthly borrowing statistics for the last 6 months
$stmt = $pdo->query("
    SELECT DATE_FORMAT(borrow_date, '%Y-%m') as month, COUNT(*) as borrow_count
    FROM borrowings
    WHERE borrow_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(borrow_date, '%Y-%m')
    ORDER BY month DESC
");
$monthly_stats = $stmt->fetchAll();

// Get popular books
$stmt = $pdo->query("
    SELECT bk.title, bk.author, COUNT(b.borrowing_id) as borrow_count
    FROM books bk
    LEFT JOIN borrowings b ON bk.book_id = b.book_id
    GROUP BY bk.book_id
    ORDER BY borrow_count DESC
    LIMIT 10
");
$popular_books = $stmt->fetchAll();

// Get popular categories
$stmt = $pdo->query("
    SELECT bk.category, COUNT(b.borrowing_id) as borrow_count
    FROM books bk
    LEFT JOIN borrowings b ON bk.book_id = b.book_id
    WHERE bk.category IS NOT NULL
    GROUP BY bk.category
    ORDER BY borrow_count DESC
    LIMIT 10
");
$popular_categories = $stmt->fetchAll();

// Get top borrowers
$stmt = $pdo->query("
    SELECT m.name, m.email, COUNT(b.borrowing_id) as borrow_count
    FROM members m
    LEFT JOIN borrowings b ON m.member_id = b.member_id
    WHERE m.status = 'active'
    GROUP BY m.member_id
    ORDER BY borrow_count DESC
    LIMIT 10
");
$top_borrowers = $stmt->fetchAll();

// Get overdue books details
$stmt = $pdo->query("
    SELECT b.borrowing_id, b.borrow_date, b.due_date, 
           bk.title as book_title, m.name as member_name, m.email as member_email,
           DATEDIFF(CURDATE(), b.due_date) as days_overdue
    FROM borrowings b
    JOIN books bk ON b.book_id = bk.book_id
    JOIN members m ON b.member_id = m.member_id
    WHERE b.status = 'borrowed' AND b.due_date < CURDATE()
    ORDER BY b.due_date ASC
");
$overdue_details = $stmt->fetchAll();

// Get recent activity
$stmt = $pdo->query("
    SELECT b.borrowing_id, b.borrow_date, b.due_date, b.status,
           bk.title as book_title, m.name as member_name
    FROM borrowings b
    JOIN books bk ON b.book_id = bk.book_id
    JOIN members m ON b.member_id = m.member_id
    ORDER BY b.created_at DESC
    LIMIT 10
");
$recent_activity = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-chart-bar me-2"></i>Reports & Analytics</h2>
    <button class="btn btn-primary" onclick="window.print()">
        <i class="fas fa-print me-2"></i>Print Report
    </button>
</div>

<!-- Overall Statistics -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3 class="card-title"><?php echo $total_books; ?></h3>
                <p class="card-text">Total Books</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3 class="card-title"><?php echo $total_members; ?></h3>
                <p class="card-text">Active Members</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3 class="card-title"><?php echo $total_borrowings; ?></h3>
                <p class="card-text">Total Borrowings</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h3 class="card-title"><?php echo $overdue_books; ?></h3>
                <p class="card-text">Overdue Books</p>
            </div>
        </div>
    </div>
</div>

<!-- Popular Books and Categories -->
<div class="row mb-4">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-star me-2"></i>Most Popular Books
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($popular_books)): ?>
                    <p class="text-muted">No borrowing data available</p>
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
                                            <span class="badge bg-primary"><?php echo $book['borrow_count']; ?></span>
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
                    <i class="fas fa-tags me-2"></i>Popular Categories
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($popular_categories)): ?>
                    <p class="text-muted">No category data available</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Borrow Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($popular_categories as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['category']); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $category['borrow_count']; ?></span>
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

<!-- Top Borrowers and Recent Activity -->
<div class="row mb-4">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-users me-2"></i>Top Borrowers
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($top_borrowers)): ?>
                    <p class="text-muted">No borrowing data available</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Email</th>
                                    <th>Borrow Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_borrowers as $borrower): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($borrower['name']); ?></td>
                                        <td><?php echo htmlspecialchars($borrower['email']); ?></td>
                                        <td>
                                            <span class="badge bg-success"><?php echo $borrower['borrow_count']; ?></span>
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
                    <i class="fas fa-clock me-2"></i>Recent Activity
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_activity)): ?>
                    <p class="text-muted">No recent activity</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>Member</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_activity as $activity): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($activity['book_title']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['member_name']); ?></td>
                                        <td>
                                            <?php if ($activity['status'] == 'borrowed'): ?>
                                                <span class="badge bg-primary">Borrowed</span>
                                            <?php elseif ($activity['status'] == 'returned'): ?>
                                                <span class="badge bg-success">Returned</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning"><?php echo ucfirst($activity['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $activity['borrow_date']; ?></td>
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

<!-- Overdue Books -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>Overdue Books (<?php echo count($overdue_details); ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($overdue_details)): ?>
                    <p class="text-muted text-center">No overdue books!</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>Member</th>
                                    <th>Borrow Date</th>
                                    <th>Due Date</th>
                                    <th>Days Overdue</th>
                                    <th>Fine Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($overdue_details as $overdue): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($overdue['book_title']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($overdue['member_name']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($overdue['member_email']); ?></small>
                                        </td>
                                        <td><?php echo $overdue['borrow_date']; ?></td>
                                        <td class="text-danger fw-bold"><?php echo $overdue['due_date']; ?></td>
                                        <td>
                                            <span class="badge bg-danger"><?php echo $overdue['days_overdue']; ?> days</span>
                                        </td>
                                        <td class="text-danger fw-bold">
                                            $<?php echo number_format($overdue['days_overdue'] * 1.00, 2); ?>
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

<!-- Monthly Statistics Chart -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>Monthly Borrowing Statistics (Last 6 Months)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($monthly_stats)): ?>
                    <p class="text-muted text-center">No monthly data available</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Borrow Count</th>
                                    <th>Progress Bar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $max_count = max(array_column($monthly_stats, 'borrow_count'));
                                foreach ($monthly_stats as $stat): 
                                    $percentage = $max_count > 0 ? ($stat['borrow_count'] / $max_count) * 100 : 0;
                                ?>
                                    <tr>
                                        <td><?php echo date('F Y', strtotime($stat['month'] . '-01')); ?></td>
                                        <td><?php echo $stat['borrow_count']; ?></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-primary" style="width: <?php echo $percentage; ?>%">
                                                    <?php echo $stat['borrow_count']; ?>
                                                </div>
                                            </div>
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

<style>
@media print {
    .sidebar, .btn, .modal {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
        width: 100% !important;
    }
    .card {
        break-inside: avoid;
    }
}
</style>

<?php include 'includes/footer.php'; ?> 