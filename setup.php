<?php
// Library Management System Setup Script
// This script helps you configure the database connection

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Library Management System - Setup</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .setup-card { max-width: 600px; margin: 50px auto; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='card setup-card'>
            <div class='card-header bg-primary text-white text-center'>
                <h3><i class='fas fa-cog me-2'></i>Library Management System Setup</h3>
            </div>
            <div class='card-body'>";

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $host = $_POST['host'];
    $dbname = $_POST['dbname'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Test database connection
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if tables exist
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('books', $tables) && in_array('members', $tables) && in_array('borrowings', $tables)) {
            echo "<div class='alert alert-success'>
                <h5><i class='fas fa-check-circle me-2'></i>Setup Complete!</h5>
                <p>Database connection successful and all tables are present.</p>
                <p><strong>Next steps:</strong></p>
                <ol>
                    <li>Update the database configuration in <code>config/database.php</code> with your credentials</li>
                    <li>Delete this setup file for security</li>
                    <li>Access the application at <a href='login.php'>login.php</a></li>
                    <li>Login with: <strong>admin</strong> / <strong>admin123</strong></li>
                </ol>
                <a href='login.php' class='btn btn-success'>Go to Login</a>
            </div>";
        } else {
            echo "<div class='alert alert-warning'>
                <h5><i class='fas fa-exclamation-triangle me-2'></i>Database Connected but Tables Missing</h5>
                <p>Please import the database schema from <code>database.sql</code> file.</p>
                <p>You can do this through phpMyAdmin or using the command:</p>
                <code>mysql -u $username -p $dbname < database.sql</code>
            </div>";
        }
        
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>
            <h5><i class='fas fa-times-circle me-2'></i>Connection Failed</h5>
            <p>Error: " . $e->getMessage() . "</p>
            <p>Please check your database credentials and try again.</p>
        </div>";
    }
    
    echo "<a href='setup.php' class='btn btn-primary'>Try Again</a>";
    
} else {
    // Show setup form
    echo "<form method='POST'>
        <div class='mb-3'>
            <label for='host' class='form-label'>Database Host</label>
            <input type='text' class='form-control' id='host' name='host' value='localhost' required>
        </div>
        
        <div class='mb-3'>
            <label for='dbname' class='form-label'>Database Name</label>
            <input type='text' class='form-control' id='dbname' name='dbname' value='library_management' required>
        </div>
        
        <div class='mb-3'>
            <label for='username' class='form-label'>Database Username</label>
            <input type='text' class='form-control' id='username' name='username' value='root' required>
        </div>
        
        <div class='mb-3'>
            <label for='password' class='form-label'>Database Password</label>
            <input type='password' class='form-control' id='password' name='password'>
        </div>
        
        <div class='alert alert-info'>
            <h6><i class='fas fa-info-circle me-2'></i>Setup Instructions</h6>
            <ol>
                <li>Create a MySQL database named 'library_management'</li>
                <li>Import the database schema from 'database.sql' file</li>
                <li>Enter your database credentials below</li>
                <li>Click 'Test Connection' to verify setup</li>
            </ol>
        </div>
        
        <button type='submit' class='btn btn-primary w-100'>
            <i class='fas fa-database me-2'></i>Test Connection
        </button>
    </form>";
}

echo "</div>
        </div>
    </div>
    
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js'></script>
</body>
</html>";
?> 