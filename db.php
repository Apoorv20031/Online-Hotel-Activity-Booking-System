<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$database = "travel";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Authentication functions - USING PLAIN TEXT PASSWORDS
function registerUser($name, $email, $password, $role = 'user') {
    global $conn;
    
    // Check if email already exists
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        return "Email already exists";
    }
    $check_stmt->close();
    
    // Store password as plain text (for development only)
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $password, $role);
    
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        $error = $conn->error;
        $stmt->close();
        return "Registration failed: " . $error;
    }
}

function loginUser($email, $password) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $name, $db_email, $db_password, $role);
    
    if ($stmt->fetch()) {
        // Debug: Log password comparison
        error_log("Password comparison - Input: '$password', Stored: '$db_password', Match: " . ($password === $db_password ? 'YES' : 'NO'));
        
        // Compare plain text passwords (for development only)
        if ($password === $db_password) {
            $_SESSION['user_id'] = $id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $db_email;
            $_SESSION['user_role'] = $role;
            $stmt->close();
            return true;
        }
    } else {
        error_log("No user found with email: $email");
    }
    
    $stmt->close();
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function logoutUser() {
    session_destroy();
    header("Location: index.php");
    exit();
}

function redirectBasedOnRole() {
    if (isLoggedIn()) {
        if (isAdmin()) {
            header("Location: admin.php");
        } else {
            header("Location: user.php");
        }
        exit();
    }
}

// Check if user is logged in and update header accordingly
function checkAuthStatus() {
    if (isLoggedIn()) {
        return [
            'logged_in' => true,
            'user_name' => $_SESSION['user_name'],
            'user_role' => $_SESSION['user_role']
        ];
    }
    return ['logged_in' => false];
}
?>