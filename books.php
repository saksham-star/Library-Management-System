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
                $title = $_POST['title'];
                $author = $_POST['author'];
                $isbn = $_POST['isbn'];
                $category = $_POST['category'];
                $publication_year = $_POST['publication_year'];
                $quantity = $_POST['quantity'];
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO books (title, author, isbn, category, publication_year, quantity, available_quantity) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $author, $isbn, $category, $publication_year, $quantity, $quantity]);
                    $message = "Book added successfully!";
                } catch (PDOException $e) {
                    $error = "Error adding book: " . $e->getMessage();
                }
                break;
                
            case 'edit':
                $book_id = $_POST['book_id'];
                $title = $_POST['title'];
                $author = $_POST['author'];
                $isbn = $_POST['isbn'];
                $category = $_POST['category'];
                $publication_year = $_POST['publication_year'];
                $quantity = $_POST['quantity'];
                
                try {
                    $stmt = $pdo->prepare("UPDATE books SET title = ?, author = ?, isbn = ?, category = ?, publication_year = ?, quantity = ? WHERE book_id = ?");
                    $stmt->execute([$title, $author, $isbn, $category, $publication_year, $quantity, $book_id]);
                    $message = "Book updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating book: " . $e->getMessage();
                }
                break;
                
            case 'delete':
                $book_id = $_POST['book_id'];
                
                try {
                    // Check if book is currently borrowed
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE book_id = ? AND status = 'borrowed'");
                    $stmt->execute([$book_id]);
                    $borrowed_count = $stmt->fetchColumn();
                    
                    if ($borrowed_count > 0) {
                        $error = "Cannot delete book: It is currently borrowed.";
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM books WHERE book_id = ?");
                        $stmt->execute([$book_id]);
                        $message = "Book deleted successfully!";
                    }
                } catch (PDOException $e) {
                    $error = "Error deleting book: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get books with search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_filter) {
    $where_conditions[] = "category = ?";
    $params[] = $category_filter;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

$stmt = $pdo->prepare("SELECT * FROM books $where_clause ORDER BY title");
$stmt->execute($params);
$books = $stmt->fetchAll();

// Get categories for filter
$stmt = $pdo->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-book me-2"></i>Books Management</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
        <i class="fas fa-plus me-2"></i>Add New Book
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
                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by title, author, or ISBN">
            </div>
            <div class="col-md-3">
                <label for="category" class="form-label">Category</label>
                <select class="form-select" id="category" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $category_filter == $category ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category); ?>
                        </option>
                    <?php endforeach; ?>
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
                <a href="books.php" class="btn btn-secondary w-100">
                    <i class="fas fa-times me-2"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Books Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Books List (<?php echo count($books); ?> books)</h5>
    </div>
    <div class="card-body">
        <?php if (empty($books)): ?>
            <p class="text-muted text-center">No books found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>ISBN</th>
                            <th>Category</th>
                            <th>Year</th>
                            <th>Total</th>
                            <th>Available</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($book['category']); ?></span>
                                </td>
                                <td><?php echo $book['publication_year']; ?></td>
                                <td><?php echo $book['quantity']; ?></td>
                                <td>
                                    <?php if ($book['available_quantity'] > 0): ?>
                                        <span class="badge bg-success"><?php echo $book['available_quantity']; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="editBook(<?php echo $book['book_id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteBook(<?php echo $book['book_id']; ?>, '<?php echo htmlspecialchars($book['title']); ?>')">
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

<!-- Add Book Modal -->
<div class="modal fade" id="addBookModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Book</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Title *</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="author" class="form-label">Author *</label>
                        <input type="text" class="form-control" id="author" name="author" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="isbn" class="form-label">ISBN</label>
                        <input type="text" class="form-control" id="isbn" name="isbn">
                    </div>
                    
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <input type="text" class="form-control" id="category" name="category">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="publication_year" class="form-label">Publication Year</label>
                                <input type="number" class="form-control" id="publication_year" name="publication_year" min="1800" max="<?php echo date('Y'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Quantity *</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Book</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Book Modal -->
<div class="modal fade" id="editBookModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Book</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="book_id" id="edit_book_id">
                    
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Title *</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_author" class="form-label">Author *</label>
                        <input type="text" class="form-control" id="edit_author" name="author" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_isbn" class="form-label">ISBN</label>
                        <input type="text" class="form-control" id="edit_isbn" name="isbn">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_category" class="form-label">Category</label>
                        <input type="text" class="form-control" id="edit_category" name="category">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_publication_year" class="form-label">Publication Year</label>
                                <input type="number" class="form-control" id="edit_publication_year" name="publication_year" min="1800" max="<?php echo date('Y'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_quantity" class="form-label">Quantity *</label>
                                <input type="number" class="form-control" id="edit_quantity" name="quantity" min="1" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Book</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteBookModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the book "<span id="delete_book_title"></span>"?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <form method="POST">
                <div class="modal-footer">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="book_id" id="delete_book_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editBook(bookId) {
    // Fetch book data and populate modal
    fetch(`api/get_book.php?id=${bookId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_book_id').value = data.book_id;
            document.getElementById('edit_title').value = data.title;
            document.getElementById('edit_author').value = data.author;
            document.getElementById('edit_isbn').value = data.isbn;
            document.getElementById('edit_category').value = data.category;
            document.getElementById('edit_publication_year').value = data.publication_year;
            document.getElementById('edit_quantity').value = data.quantity;
            
            new bootstrap.Modal(document.getElementById('editBookModal')).show();
        });
}

function deleteBook(bookId, bookTitle) {
    document.getElementById('delete_book_id').value = bookId;
    document.getElementById('delete_book_title').textContent = bookTitle;
    new bootstrap.Modal(document.getElementById('deleteBookModal')).show();
}
</script>

<?php include 'includes/footer.php'; ?> 