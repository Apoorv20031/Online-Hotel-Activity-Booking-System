<?php
include 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Debug: Log the login attempt
        error_log("Login attempt - Email: $email, Password: $password");
        
        // Debug: Check what's in the database
        $debug_stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
        $debug_stmt->bind_param("s", $email);
        $debug_stmt->execute();
        $debug_stmt->store_result();
        $debug_stmt->bind_result($db_id, $db_name, $db_email, $db_password, $db_role);
        
        if ($debug_stmt->fetch()) {
            error_log("Database record found:");
            error_log("ID: $db_id, Name: $db_name, Email: $db_email, Password: $db_password, Role: $db_role");
            error_log("Input password: '$password'");
            error_log("Stored password: '$db_password'");
            error_log("Passwords match: " . ($password === $db_password ? 'YES' : 'NO'));
        } else {
            error_log("No user found with email: $email");
        }
        $debug_stmt->close();
        
        if (loginUser($email, $password)) {
            error_log("Login successful - User: " . $_SESSION['user_name'] . ", Role: " . $_SESSION['user_role']);
            
            // Redirect based on role
            if (isAdmin()) {
                header("Location: admin.php");
            } else {
                header("Location: user.php");
            }
            exit();
        } else {
            error_log("Login failed for email: $email");
            header("Location: index.php?error=Invalid email or password");
            exit();
        }
    }
    
    elseif ($action === 'register') {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Debug registration attempt
        error_log("Registration attempt - Name: $name, Email: $email");
        
        // Validation
        if (empty($name) || empty($email) || empty($password)) {
            header("Location: index.php?error=All fields are required");
            exit();
        }
        
        if ($password !== $confirm_password) {
            header("Location: index.php?error=Passwords do not match");
            exit();
        }
        
        if (strlen($password) < 6) {
            header("Location: index.php?error=Password must be at least 6 characters");
            exit();
        }
        
        $result = registerUser($name, $email, $password);
        
        if ($result === true) {
            error_log("Registration successful for: $email");
            
            // Auto-login after registration
            if (loginUser($email, $password)) {
                header("Location: user.php");
                exit();
            } else {
                error_log("Auto-login failed after registration for: $email");
                header("Location: index.php?error=Registration successful but login failed. Please try logging in.");
                exit();
            }
        } else {
            error_log("Registration failed: " . $result);
            header("Location: index.php?error=" . urlencode($result));
            exit();
        }
    }
}

// Handle logout via GET

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_start();
    session_destroy();
    header('Location: index.php');
    exit();
}
header("Location: index.php");
exit();
?>