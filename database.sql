-- Library Management System Database Schema

-- Create database
CREATE DATABASE IF NOT EXISTS library_management;
USE library_management;

-- Books table
CREATE TABLE books (
    book_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    isbn VARCHAR(13) UNIQUE,
    category VARCHAR(100),
    publication_year INT,
    quantity INT DEFAULT 1,
    available_quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Members table
CREATE TABLE members (
    member_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    membership_date DATE DEFAULT CURRENT_DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Borrowings table
CREATE TABLE borrowings (
    borrowing_id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT,
    member_id INT,
    borrow_date DATE DEFAULT CURRENT_DATE,
    due_date DATE,
    return_date DATE NULL,
    status ENUM('borrowed', 'returned', 'overdue') DEFAULT 'borrowed',
    fine_amount DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(book_id),
    FOREIGN KEY (member_id) REFERENCES members(member_id)
);

-- Users table for admin login
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    role ENUM('admin', 'librarian') DEFAULT 'librarian',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO books (title, author, isbn, category, publication_year, quantity, available_quantity) VALUES
('The Great Gatsby', 'F. Scott Fitzgerald', '9780743273565', 'Fiction', 1925, 3, 3),
('To Kill a Mockingbird', 'Harper Lee', '9780446310789', 'Fiction', 1960, 2, 2),
('1984', 'George Orwell', '9780451524935', 'Fiction', 1949, 4, 4),
('Pride and Prejudice', 'Jane Austen', '9780141439518', 'Romance', 1813, 2, 2),
('The Hobbit', 'J.R.R. Tolkien', '9780547928241', 'Fantasy', 1937, 3, 3),
('The Catcher in the Rye', 'J.D. Salinger', '9780316769488', 'Fiction', 1951, 2, 2),
('Lord of the Flies', 'William Golding', '9780399501487', 'Fiction', 1954, 2, 2),
('Animal Farm', 'George Orwell', '9780451526342', 'Fiction', 1945, 3, 3),
('The Alchemist', 'Paulo Coelho', '9780062315007', 'Fiction', 1988, 2, 2),
('Brave New World', 'Aldous Huxley', '9780060850524', 'Fiction', 1932, 2, 2);

INSERT INTO members (name, email, phone, address) VALUES
('John Doe', 'john.doe@email.com', '1234567890', '123 Main St, City'),
('Jane Smith', 'jane.smith@email.com', '0987654321', '456 Oak Ave, Town'),
('Mike Johnson', 'mike.johnson@email.com', '5551234567', '789 Pine Rd, Village'),
('Sarah Wilson', 'sarah.wilson@email.com', '5559876543', '321 Elm St, Borough'),
('David Brown', 'david.brown@email.com', '5554567890', '654 Maple Dr, County');

-- Insert admin user (password: admin123)
INSERT INTO users (username, password, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@library.com', 'admin'); 