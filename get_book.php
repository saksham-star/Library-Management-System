<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

if (isset($_GET['id'])) {
    $book_id = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = ?");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($book) {
            header('Content-Type: application/json');
            echo json_encode($book);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Book not found']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Book ID required']);
}
?> 