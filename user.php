<?php
include 'db.php';

// Redirect to login if not authenticated
if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Redirect admin to admin panel
if (isAdmin()) {
    header("Location: admin.php");
    exit();
}

// Create bookings table if not exists with enhanced fields
$create_table_sql = "CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_name VARCHAR(255) NOT NULL,
    user_email VARCHAR(255) NOT NULL,
    adults INT NOT NULL,
    children INT NOT NULL,
    contact_no VARCHAR(20) NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    stay_days INT NOT NULL,
    package_items JSON NOT NULL,
    package_total DECIMAL(10,2) NOT NULL,
    children_charges DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) NOT NULL,
    final_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('upi', 'netbanking', 'debitcard') NULL,
    payment_type ENUM('full', 'half') NULL,
    amount_paid DECIMAL(10,2) DEFAULT 0,
    remaining_amount DECIMAL(10,2) DEFAULT 0,
    payment_status ENUM('pending', 'completed', 'cancelled', 'partial') DEFAULT 'pending',
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    booking_status ENUM('confirmed', 'cancelled') DEFAULT 'confirmed',
    transaction_id VARCHAR(100) NULL,
    payment_date TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if (!$conn->query($create_table_sql)) {
    error_log("Error creating bookings table: " . $conn->error);
}

// Check if transaction_id column exists, if not add it
$check_column_sql = "SHOW COLUMNS FROM bookings LIKE 'transaction_id'";
$result = $conn->query($check_column_sql);
if ($result->num_rows == 0) {
    $add_column_sql = "ALTER TABLE bookings ADD COLUMN transaction_id VARCHAR(100) NULL AFTER booking_status";
    if (!$conn->query($add_column_sql)) {
        error_log("Error adding transaction_id column: " . $conn->error);
    }
}

// Check if payment_date column exists, if not add it
$check_column_sql = "SHOW COLUMNS FROM bookings LIKE 'payment_date'";
$result = $conn->query($check_column_sql);
if ($result->num_rows == 0) {
    $add_column_sql = "ALTER TABLE bookings ADD COLUMN payment_date TIMESTAMP NULL AFTER transaction_id";
    if (!$conn->query($add_column_sql)) {
        error_log("Error adding payment_date column: " . $conn->error);
    }
}

// Create payment_history table for tracking payments
$create_payment_history_sql = "CREATE TABLE IF NOT EXISTS payment_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    transaction_id VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('upi', 'netbanking', 'debitcard') NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('success', 'failed') DEFAULT 'success',
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
)";

if (!$conn->query($create_payment_history_sql)) {
    error_log("Error creating payment_history table: " . $conn->error);
}

// Get user account creation date
$user_stmt = $conn->prepare("SELECT created_at FROM users WHERE id = ?");
$user_stmt->bind_param("i", $_SESSION['user_id']);
$user_stmt->execute();
$user_stmt->bind_result($account_created);
$user_stmt->fetch();
$user_stmt->close();

// Initialize session variables if not set
if (!isset($_SESSION['package'])) {
    $_SESSION['package'] = [];
}

if (!isset($_SESSION['booking_details'])) {
    $_SESSION['booking_details'] = [
        'adults' => 1,
        'children' => 0,
        'check_in' => '',
        'check_out' => '',
        'contact_no' => ''
    ];
}

// Initialize booking_updated flag
if (!isset($_SESSION['booking_updated'])) {
    $_SESSION['booking_updated'] = false;
}

// Handle form submissions EXCEPT payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $email, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $success = "Profile updated successfully!";
        } else {
            $error = "Failed to update profile: " . $conn->error;
        }
        $stmt->close();
    }
    
   if (isset($_POST['delete_profile'])) {
    // Start transaction for data consistency
    $conn->begin_transaction();
    
    try {
        // First delete payment history for user's bookings
        $delete_payments_stmt = $conn->prepare("
            DELETE ph FROM payment_history ph 
            INNER JOIN bookings b ON ph.booking_id = b.id 
            WHERE b.user_id = ?
        ");
        $delete_payments_stmt->bind_param("i", $_SESSION['user_id']);
        $delete_payments_stmt->execute();
        $delete_payments_stmt->close();
        
        // Then delete user's bookings
        $delete_bookings_stmt = $conn->prepare("DELETE FROM bookings WHERE user_id = ?");
        $delete_bookings_stmt->bind_param("i", $_SESSION['user_id']);
        $delete_bookings_stmt->execute();
        $delete_bookings_stmt->close();
        
        // Finally delete the user
        $delete_user_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $delete_user_stmt->bind_param("i", $_SESSION['user_id']);
        
        if ($delete_user_stmt->execute()) {
            $conn->commit();
            
            // Clear all session data
            session_unset();
            session_destroy();
            
            // Redirect to login page with success message
            header("Location: index.php?success=Account deleted successfully");
            exit();
        } else {
            throw new Exception("Failed to delete user");
        }
        $delete_user_stmt->close();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error = "Failed to delete account: " . $e->getMessage();
    }
}
    
    if (isset($_POST['add_to_package'])) {
        $item_type = $_POST['item_type'];
        $item_id = $_POST['item_id'];
        $item_name = $_POST['item_name'];
        $item_price = $_POST['item_price'];
        
        $_SESSION['package'][] = [
            'type' => $item_type,
            'id' => $item_id,
            'name' => $item_name,
            'price' => $item_price,
            'daily_rate' => $item_price
        ];
        
        $success = "Item added to package!";
        $_SESSION['booking_updated'] = false; // Reset when new items are added
    }
    
    if (isset($_POST['remove_from_package'])) {
        $index = $_POST['item_index'];
        if (isset($_SESSION['package'][$index])) {
            unset($_SESSION['package'][$index]);
            $_SESSION['package'] = array_values($_SESSION['package']);
            $success = "Item removed from package!";
            $_SESSION['booking_updated'] = false; // Reset when items are removed
        }
    }
    
    if (isset($_POST['clear_package'])) {
        unset($_SESSION['package']);
        unset($_SESSION['booking_details']);
        $_SESSION['booking_updated'] = false;
        $success = "Package cleared!";
    }
    
    // Handle booking details update separately
    if (isset($_POST['update_booking_details'])) {
        $_SESSION['booking_details'] = [
            'adults' => $_POST['adults'],
            'children' => $_POST['children'],
            'check_in' => $_POST['check_in'],
            'check_out' => $_POST['check_out'],
            'contact_no' => $_POST['contact_no']
        ];
        $_SESSION['booking_updated'] = true; // Set flag when booking details are updated
        $success = "Booking details updated!";
        
        // Redirect to refresh page and show updated calculations
        header("Location: user.php#package");
        exit();
    }
}

// CORRECT calculation function - rooms calculated per night, others as fixed prices
function calculatePackageTotal($package_items, $check_in, $check_out) {
    $total_amount = 0;
    
    if (!empty($check_in) && !empty($check_out)) {
        $check_in_date = new DateTime($check_in);
        $check_out_date = new DateTime($check_out);
        $stay_days = $check_out_date->diff($check_in_date)->days;
        
        if ($stay_days > 0) {
            foreach ($package_items as $item) {
                if ($item['type'] === 'room') {
                    // For rooms, calculate based on stay duration
                    $total_amount += $item['daily_rate'] * $stay_days;
                } else {
                    // For transport and activities, add fixed price
                    $total_amount += $item['price'];
                }
            }
        } else {
            // If no valid dates, calculate without stay duration
            foreach ($package_items as $item) {
                $total_amount += $item['price'];
            }
        }
    } else {
        // If no dates selected, calculate without stay duration
        foreach ($package_items as $item) {
            $total_amount += $item['price'];
        }
    }
    
    return $total_amount;
}

// Generate unique transaction ID
function generateTransactionId() {
    return 'TXN' . date('YmdHis') . rand(1000, 9999);
}

// Calculate current package total
$package_total = 0;
$stay_days = 0;
$children_charges = 0;
$tax_amount = 0;
$final_amount = 0;
$all_fields_filled = false;

if (!empty($_SESSION['package'])) {
    $check_in = $_SESSION['booking_details']['check_in'] ?? '';
    $check_out = $_SESSION['booking_details']['check_out'] ?? '';
    $adults = $_SESSION['booking_details']['adults'] ?? 0;
    $children = $_SESSION['booking_details']['children'] ?? 0;
    
    if (!empty($check_in) && !empty($check_out)) {
        $check_in_date = new DateTime($check_in);
        $check_out_date = new DateTime($check_out);
        $stay_days = $check_out_date->diff($check_in_date)->days;
    }
    
    // Calculate package total - rooms per night, others fixed
    $package_total = calculatePackageTotal($_SESSION['package'], $check_in, $check_out);
    
    // Calculate children charges
    if ($children > 2) {
        $children_charges = ($children - 2) * 500;
    }
    
    $total_with_children = $package_total + $children_charges;
    $tax_amount = $total_with_children * 0.18;
    $final_amount = $total_with_children + $tax_amount;
    
    // Check if all required fields are filled for payment AND booking details were updated
    $contact_no = $_SESSION['booking_details']['contact_no'] ?? '';
    
    if ($adults > 0 && !empty($contact_no) && !empty($check_in) && !empty($check_out) && $stay_days > 0 && $_SESSION['booking_updated']) {
        $all_fields_filled = true;
    }
}

// NOW Handle payment processing AFTER variables are calculated
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $payment_method = $_POST['payment_method'];
    $payment_type = $_POST['payment_type'];
    
    // Calculate amounts based on payment type
    $amount_to_pay = ($payment_type === 'half') ? ($final_amount / 2) : $final_amount;
    $remaining_amount = ($payment_type === 'half') ? ($final_amount / 2) : 0;
    $payment_status = ($payment_type === 'full') ? 'completed' : 'partial';
    $transaction_id = generateTransactionId();
    
    // Insert booking into database - FIXED: removed payment_date from initial insert
    $stmt = $conn->prepare("INSERT INTO bookings (
        user_id, user_name, user_email, adults, children, contact_no, 
        check_in, check_out, stay_days, package_items, package_total, 
        children_charges, subtotal, tax_amount, final_amount, 
        payment_method, payment_type, amount_paid, remaining_amount, payment_status,
        transaction_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $package_items_json = json_encode($_SESSION['package']);
    
    $stmt->bind_param(
        "issiisssisddddddssdss", 
        $_SESSION['user_id'],                    // i
        $_SESSION['user_name'],                  // s
        $_SESSION['user_email'],                 // s
        $_SESSION['booking_details']['adults'],  // i
        $_SESSION['booking_details']['children'],// i
        $_SESSION['booking_details']['contact_no'], // s
        $_SESSION['booking_details']['check_in'],   // s
        $_SESSION['booking_details']['check_out'],  // s
        $stay_days,                              // i
        $package_items_json,                     // s
        $package_total,                          // d
        $children_charges,                       // d
        $total_with_children,                    // d
        $tax_amount,                             // d
        $final_amount,                           // d
        $payment_method,                         // s
        $payment_type,                           // s
        $amount_to_pay,                          // d
        $remaining_amount,                       // d
        $payment_status,                         // s
        $transaction_id                          // s
    );
    
    if ($stmt->execute()) {
        $booking_id = $conn->insert_id;
        
        // Update payment date for the booking
        $update_stmt = $conn->prepare("UPDATE bookings SET payment_date = NOW() WHERE id = ?");
        $update_stmt->bind_param("i", $booking_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Insert into payment history
        $payment_stmt = $conn->prepare("INSERT INTO payment_history (booking_id, transaction_id, amount, payment_method) VALUES (?, ?, ?, ?)");
        $payment_stmt->bind_param("isds", $booking_id, $transaction_id, $amount_to_pay, $payment_method);
        $payment_stmt->execute();
        $payment_stmt->close();
        
        $success = "Booking confirmed! Payment of ₹" . number_format($amount_to_pay, 2) . " processed successfully. Transaction ID: " . $transaction_id;
        
        // Clear session data after successful booking
        unset($_SESSION['package']);
        unset($_SESSION['booking_details']);
        $_SESSION['booking_updated'] = false;
        
        header("Location: user.php?success=" . urlencode($success));
        exit();
    } else {
        $error = "Failed to process booking: " . $conn->error;
        error_log("SQL Error: " . $stmt->error);
    }
    $stmt->close();
}

// Handle complete payment for partial bookings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_payment'])) {
    $booking_id = $_POST['booking_id'];
    $payment_method = $_POST['payment_method'];
    
    // Get booking details
    $booking_stmt = $conn->prepare("SELECT remaining_amount, final_amount FROM bookings WHERE id = ? AND user_id = ?");
    $booking_stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
    $booking_stmt->execute();
    $booking_stmt->bind_result($remaining_amount, $final_amount);
    $booking_stmt->fetch();
    $booking_stmt->close();
    
    if ($remaining_amount > 0) {
        $transaction_id = generateTransactionId();
        
        // Update booking
        $update_stmt = $conn->prepare("UPDATE bookings SET 
            payment_method = ?, 
            amount_paid = final_amount, 
            remaining_amount = 0, 
            payment_status = 'completed',
            payment_date = NOW()
            WHERE id = ?");
        $update_stmt->bind_param("si", $payment_method, $booking_id);
        
        if ($update_stmt->execute()) {
            // Insert into payment history
            $payment_stmt = $conn->prepare("INSERT INTO payment_history (booking_id, transaction_id, amount, payment_method) VALUES (?, ?, ?, ?)");
            $payment_stmt->bind_param("isds", $booking_id, $transaction_id, $remaining_amount, $payment_method);
            $payment_stmt->execute();
            $payment_stmt->close();
            
            $success = "Payment completed successfully! Transaction ID: " . $transaction_id;
            header("Location: user.php?success=" . urlencode($success));
            exit();
        } else {
            $error = "Failed to complete payment: " . $conn->error;
        }
        $update_stmt->close();
    }
}

// Handle payment cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_payment'])) {
    $booking_id = $_POST['booking_id'];
    $stmt = $conn->prepare("UPDATE bookings SET payment_status = 'cancelled', booking_status = 'cancelled' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $success = "Payment cancelled successfully!";
        header("Location: user.php?success=" . urlencode($success));
        exit();
    } else {
        $error = "Failed to cancel payment: " . $conn->error;
    }
    $stmt->close();
}

// Get user bookings
$bookings = [];
$bookings_stmt = $conn->prepare("SELECT * FROM bookings WHERE user_id = ? ORDER BY booking_date DESC");
$bookings_stmt->bind_param("i", $_SESSION['user_id']);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();
while ($booking = $bookings_result->fetch_assoc()) {
    $bookings[] = $booking;
}
$bookings_stmt->close();

// Get payment history for bookings
$booking_payments = [];
foreach ($bookings as $booking) {
    $payment_stmt = $conn->prepare("SELECT * FROM payment_history WHERE booking_id = ? ORDER BY payment_date DESC");
    $payment_stmt->bind_param("i", $booking['id']);
    $payment_stmt->execute();
    $payment_result = $payment_stmt->get_result();
    $booking_payments[$booking['id']] = $payment_result->fetch_all(MYSQLI_ASSOC);
    $payment_stmt->close();
}

// Check for success message in URL
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - DevbagStayz</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a73e8;
            --secondary: #0d47a1;
            --accent: #ff6d00;
            --light: #f8f9fa;
            --dark: #2c3e50;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --card-bg: #ffffff;
            --shadow: rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .container {
            width: 90%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header Styles */
        .dashboard-header {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-bottom: 3px solid var(--accent);
        }
        
        .brand-section {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .brand-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 0.5rem;
        }
        
        .brand-logo i {
            font-size: 2.5rem;
            color: var(--primary);
        }
        
        .brand-logo h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .header-content {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 2rem;
            align-items: center;
        }
        
        .user-welcome h2 {
            font-size: 1.6rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .user-info {
            background: var(--light);
            padding: 1.2rem;
            border-radius: 10px;
            border-left: 4px solid var(--accent);
        }
        
        .user-info p {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--dark);
        }
        
        /* Navigation */
        .dashboard-nav {
            background: white;
            padding: 1rem 0;
            margin-bottom: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px var(--shadow);
        }
        
        .dashboard-nav ul {
            display: flex;
            justify-content: center;
            list-style: none;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .dashboard-nav a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .dashboard-nav a:hover,
        .dashboard-nav a.active {
            background: var(--primary);
            color: white;
        }

        /* Cart Icon */
        .cart-icon {
            position: relative;
            cursor: pointer;
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        /* Cart Sidebar */
        .cart-sidebar {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -5px 0 15px rgba(0,0,0,0.1);
            transition: right 0.3s ease;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        
        .cart-sidebar.active {
            right: 0;
        }
        
        .cart-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--primary);
            color: white;
        }
        
        .cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 0.8rem;
            background: var(--light);
        }
        
        .cart-item-info {
            flex: 1;
        }
        
        .cart-item-name {
            font-weight: 600;
            color: var(--dark);
        }
        
        .cart-item-details {
            display: flex;
            gap: 1rem;
            margin-top: 0.3rem;
        }
        
        .cart-item-type {
            background: #e9ecef;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            color: #666;
        }
        
        .cart-item-price {
            font-weight: 600;
            color: var(--accent);
        }
        
        .cart-footer {
            padding: 1.5rem;
            border-top: 1px solid #e9ecef;
            background: #f8f9fa;
        }
        
        .cart-total {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            display: none;
        }
        
        .overlay.active {
            display: block;
        }
        
        /* Main Content */
        .dashboard-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .main-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .sidebar {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px var(--shadow);
            height: fit-content;
            position: sticky;
            top: 2rem;
        }
        
        .card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
        }
        
        .card h2 {
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
        }
        
        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.8rem;
            margin-top: 1.5rem;
        }
        
        .service-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }
        
        .service-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        
        .service-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 0.3rem;
        }
        
        .service-price {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--accent);
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .service-features {
            margin: 1.5rem 0;
        }
        
        .service-features p {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 0.8rem;
            color: #555;
            font-size: 0.95rem;
        }
        
        .service-features i {
            color: var(--primary);
            width: 20px;
        }
        
        /* Buttons */
        .btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 0.95rem;
            box-shadow: 0 4px 15px rgba(26, 115, 232, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 115, 232, 0.4);
        }
        
        .btn-sm {
            padding: 10px 20px;
            font-size: 0.9rem;
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success), #1e7e34);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #c82333);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, var(--warning), #e0a800);
        }
        
        .btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .btn:disabled:hover {
            transform: none;
            box-shadow: none;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 1.2rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
            outline: none;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.2rem;
        }
        
        /* Package Items */
        .package-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 0.8rem;
            background: var(--light);
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: var(--dark);
        }
        
        .item-details {
            display: flex;
            gap: 1rem;
            margin-top: 0.3rem;
        }
        
        .item-type {
            background: #e9ecef;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            color: #666;
        }
        
        .item-price {
            font-weight: 600;
            color: var(--accent);
        }
        
        /* Alerts */
        .alert {
            padding: 1.2rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            animation: slideIn 0.5s ease;
            position: relative;
            overflow: hidden;
        }
        
        .alert::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
        }
        
        .alert-success {
            background: #f8fff9;
            color: #155724;
            border-left: 5px solid var(--success);
        }
        
        .alert-error {
            background: #fffafa;
            color: #721c24;
            border-left: 5px solid var(--danger);
        }
        
        .alert i {
            font-size: 1.2rem;
        }
        
        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #e9ecef;
            margin-bottom: 1.5rem;
            opacity: 0.6;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #555;
        }
        
        /* Payment Modal Styles */
        .payment-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .payment-modal.active {
            display: flex;
        }
        
        .payment-content {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 50px rgba(0,0,0,0.3);
        }
        
        .payment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .payment-methods {
            display: grid;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .payment-method {
            padding: 1.2rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .payment-method:hover {
            border-color: var(--primary);
            background: #f8f9fa;
        }
        
        .payment-method.selected {
            border-color: var(--primary);
            background: #e3f2fd;
        }
        
        .payment-type {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .payment-type-option {
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-type-option:hover {
            border-color: var(--primary);
        }
        
        .payment-type-option.selected {
            border-color: var(--primary);
            background: #e3f2fd;
        }
        
        .payment-summary {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        
        .payment-actions {
            display: flex;
            gap: 1rem;
        }
        
        .payment-actions .btn {
            flex: 1;
        }

        /* Card enhancements */
        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f8f9fa;
        }

        .card-header i {
            font-size: 1.8rem;
            color: var(--primary);
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .feature-badge {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 8px;
        }

        .calculation-details {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .calculation-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.3rem;
        }

        .calculation-line:last-child {
            margin-bottom: 0;
            font-weight: bold;
            border-top: 1px solid #dee2e6;
            padding-top: 0.3rem;
        }
        
        .date-validation {
            font-size: 0.85rem;
            margin-top: 5px;
            display: block;
        }
        
        .date-validation.error {
            color: var(--danger);
        }
        
        .date-validation.success {
            color: var(--success);
        }
        
        /* Booking Cards */
        .booking-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary);
        }
        
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .booking-id {
            font-weight: bold;
            color: var(--primary);
            font-size: 1.1rem;
        }
        
        .booking-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-partial {
            background: #cce7ff;
            color: #004085;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-weight: 600;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }
        
        .detail-value {
            font-weight: 500;
            color: var(--dark);
        }
        
        .package-summary {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .package-items {
            margin-top: 0.5rem;
        }
        
        .package-item-small {
            display: flex;
            justify-content: space-between;
            padding: 0.3rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .package-item-small:last-child {
            border-bottom: none;
        }
        
        .payment-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .payment-history {
            margin-top: 1rem;
        }
        
        .payment-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem;
            border-bottom: 1px solid #e9ecef;
            font-size: 0.9rem;
        }
        
        .payment-item:last-child {
            border-bottom: none;
        }
        
        .complete-payment-form {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #dee2e6;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .dashboard-content {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                position: static;
            }
            
            .cart-sidebar {
                width: 350px;
            }
        }
        
        @media (max-width: 768px) {
            .cards-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-nav ul {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .header-content {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .service-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .service-price {
                align-self: flex-start;
            }
            
            .cart-sidebar {
                width: 100%;
                right: -100%;
            }
            
            .payment-type {
                grid-template-columns: 1fr;
            }
            
            .booking-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .booking-details {
                grid-template-columns: 1fr;
            }
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }

        .alert.fade-out {
            animation: fadeOut 0.5s ease forwards;
        }
    </style>
</head>
<body>
    <!-- Payment Modal -->
    <div class="payment-modal" id="paymentModal">
        <div class="payment-content">
            <div class="payment-header">
                <h3 style="color: var(--primary); margin: 0;">
                    <i class="fas fa-credit-card"></i> Complete Payment
                </h3>
                <button class="btn btn-sm" onclick="closePaymentModal()" style="background: #f8f9fa; color: var(--dark);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" id="paymentForm">
                <div class="payment-methods">
                    <div class="payment-method" onclick="selectPaymentMethod('upi')">
                        <i class="fas fa-mobile-alt" style="font-size: 1.5rem; color: var(--primary);"></i>
                        <div>
                            <div style="font-weight: 600;">UPI Payment</div>
                            <small style="color: #666;">Pay using UPI apps</small>
                        </div>
                        <input type="radio" name="payment_method" value="upi" style="display: none;">
                    </div>
                    
                    <div class="payment-method" onclick="selectPaymentMethod('netbanking')">
                        <i class="fas fa-university" style="font-size: 1.5rem; color: var(--primary);"></i>
                        <div>
                            <div style="font-weight: 600;">Net Banking</div>
                            <small style="color: #666;">Internet banking</small>
                        </div>
                        <input type="radio" name="payment_method" value="netbanking" style="display: none;">
                    </div>
                    
                    <div class="payment-method" onclick="selectPaymentMethod('debitcard')">
                        <i class="fas fa-credit-card" style="font-size: 1.5rem; color: var(--primary);"></i>
                        <div>
                            <div style="font-weight: 600;">Debit Card</div>
                            <small style="color: #666;">Visa, MasterCard, RuPay</small>
                        </div>
                        <input type="radio" name="payment_method" value="debitcard" style="display: none;">
                    </div>
                </div>
                
                <div class="payment-type">
                    <div class="payment-type-option" onclick="selectPaymentType('full')">
                        <div style="font-weight: 600; margin-bottom: 0.5rem;">Full Payment</div>
                        <div style="color: var(--accent); font-size: 1.2rem; font-weight: 700;">₹<?php echo number_format($final_amount, 2); ?></div>
                        <input type="radio" name="payment_type" value="full" style="display: none;">
                    </div>
                    
                    <div class="payment-type-option" onclick="selectPaymentType('half')">
                        <div style="font-weight: 600; margin-bottom: 0.5rem;">Half Payment</div>
                        <div style="color: var(--accent); font-size: 1.2rem; font-weight: 700;">₹<?php echo number_format($final_amount / 2, 2); ?></div>
                        <small style="color: #666;">Pay now, rest later</small>
                        <input type="radio" name="payment_type" value="half" style="display: none;">
                    </div>
                </div>
                
                <div class="payment-summary">
                    <h4 style="color: var(--primary); margin-bottom: 1rem;">Payment Summary</h4>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Final Amount:</span>
                        <span>₹<?php echo number_format($final_amount, 2); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Payment Type:</span>
                        <span id="selectedPaymentType">-</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.1rem;">
                        <span>Amount to Pay:</span>
                        <span id="amountToPay" style="color: var(--accent);">₹0.00</span>
                    </div>
                </div>
                
                <div class="payment-actions">
                    <button type="button" class="btn btn-danger" onclick="closePaymentModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" name="process_payment" class="btn btn-success" id="confirmPaymentBtn" disabled>
                        <i class="fas fa-lock"></i> Proceed to Pay
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Complete Payment Modal -->
    <div class="payment-modal" id="completePaymentModal">
        <div class="payment-content">
            <div class="payment-header">
                <h3 style="color: var(--primary); margin: 0;">
                    <i class="fas fa-credit-card"></i> Complete Remaining Payment
                </h3>
                <button class="btn btn-sm" onclick="closeCompletePaymentModal()" style="background: #f8f9fa; color: var(--dark);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" id="completePaymentForm">
                <input type="hidden" name="booking_id" id="completePaymentBookingId">
                
                <div class="payment-methods">
                    <div class="payment-method" onclick="selectCompletePaymentMethod('upi')">
                        <i class="fas fa-mobile-alt" style="font-size: 1.5rem; color: var(--primary);"></i>
                        <div>
                            <div style="font-weight: 600;">UPI Payment</div>
                            <small style="color: #666;">Pay using UPI apps</small>
                        </div>
                        <input type="radio" name="payment_method" value="upi" style="display: none;">
                    </div>
                    
                    <div class="payment-method" onclick="selectCompletePaymentMethod('netbanking')">
                        <i class="fas fa-university" style="font-size: 1.5rem; color: var(--primary);"></i>
                        <div>
                            <div style="font-weight: 600;">Net Banking</div>
                            <small style="color: #666;">Internet banking</small>
                        </div>
                        <input type="radio" name="payment_method" value="netbanking" style="display: none;">
                    </div>
                    
                    <div class="payment-method" onclick="selectCompletePaymentMethod('debitcard')">
                        <i class="fas fa-credit-card" style="font-size: 1.5rem; color: var(--primary);"></i>
                        <div>
                            <div style="font-weight: 600;">Debit Card</div>
                            <small style="color: #666;">Visa, MasterCard, RuPay</small>
                        </div>
                        <input type="radio" name="payment_method" value="debitcard" style="display: none;">
                    </div>
                </div>
                
                <div class="payment-summary">
                    <h4 style="color: var(--primary); margin-bottom: 1rem;">Payment Summary</h4>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Remaining Amount:</span>
                        <span id="remainingAmountDisplay">₹0.00</span>
                    </div>
                </div>
                
                <div class="payment-actions">
                    <button type="button" class="btn btn-danger" onclick="closeCompletePaymentModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" name="complete_payment" class="btn btn-success" id="confirmCompletePaymentBtn" disabled>
                        <i class="fas fa-lock"></i> Pay Now
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cart Overlay -->
    <div class="overlay" id="cartOverlay"></div>
    
    <!-- Cart Sidebar -->
    <div class="cart-sidebar" id="cartSidebar">
        <div class="cart-header">
            <h3><i class="fas fa-shopping-cart"></i> Your Package</h3>
            <button class="btn btn-sm" onclick="closeCart()" style="background: rgba(255,255,255,0.2);">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="cart-items" id="cartItems">
            <?php if (empty($_SESSION['package'])): ?>
                <div class="empty-state" style="padding: 2rem 1rem;">
                    <i class="fas fa-shopping-cart"></i>
                    <h4>Package Empty</h4>
                    <p>Add items to build your package</p>
                </div>
            <?php else: ?>
                <?php foreach ($_SESSION['package'] as $index => $item): ?>
                <div class="cart-item">
                    <div class="cart-item-info">
                        <div class="cart-item-name"><?php echo $item['name']; ?></div>
                        <div class="cart-item-details">
                            <span class="cart-item-type"><?php echo $item['type']; ?></span>
                            <span class="cart-item-price">₹<?php echo $item['price']; ?></span>
                        </div>
                    </div>
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="item_index" value="<?php echo $index; ?>">
                        <button type="submit" name="remove_from_package" class="btn btn-danger btn-sm">
                            <i class="fas fa-times"></i>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="cart-footer">
            <div class="cart-total">
                <span>Total Items:</span>
                <span id="cartTotalCount"><?php echo count($_SESSION['package']); ?></span>
            </div>
            <a href="#package" class="btn" style="width: 100%; text-align: center;" onclick="closeCart()">
                <i class="fas fa-eye"></i> View Full Package
            </a>
        </div>
    </div>

    <!-- Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="brand-section">
                <div class="brand-logo">
                    <i class="fas fa-umbrella-beach"></i>
                    <h1>DevbagStayz</h1>
                </div>
                <p style="color: #666;">Luxury Beachfront Experiences</p>
            </div>
            
            <div class="header-content">
                <div class="user-welcome">
                    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
                    <p style="color: #666;">Explore our premium services and accommodations</p>
                </div>
                <div class="user-info">
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                    <p><i class="fas fa-calendar"></i> Member since: <?php echo date('F j, Y', strtotime($account_created)); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Navigation -->
        <nav class="dashboard-nav">
            <ul>
                <li><a href="#rooms" class="active"><i class="fas fa-bed"></i> Rooms</a></li>
                <li><a href="#transport"><i class="fas fa-taxi"></i> Transport</a></li>
                <li><a href="#activities"><i class="fas fa-water"></i> Activities</a></li>
                <li><a href="#bookings"><i class="fas fa-history"></i> My Bookings</a></li>
                <li><a href="#profile"><i class="fas fa-user"></i> Profile</a></li>
                <li>
                    <a href="javascript:void(0)" onclick="openCart()" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if (!empty($_SESSION['package'])): ?>
                            <span class="cart-count"><?php echo count($_SESSION['package']); ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li><a href="auth.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
        
        <!-- Alerts -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success" id="successAlert">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error" id="errorAlert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="dashboard-content">
            <!-- Main Content -->
            <div class="main-content">
                <!-- Available Rooms -->
                <div class="card" id="rooms">
                    <div class="card-header">
                        <i class="fas fa-bed"></i>
                        <h2>Luxury Accommodations</h2>
                    </div>
                    <p style="color: #666; margin-bottom: 1rem; font-size: 1.1rem;">Choose from our premium beachfront rooms with stunning ocean views</p>
                    
                    <div class="cards-grid">
                        <?php
                        $rooms = $conn->query("SELECT * FROM rooms WHERE available = 'Yes'");
                        if ($rooms->num_rows > 0):
                            while($room = $rooms->fetch_assoc()):
                        ?>
                        <div class="service-card">
                            <div class="service-header">
                                <div>
                                    <div class="service-title">
                                        Room <?php echo $room['room_no']; ?> - Block <?php echo $room['block']; ?>
                                        <span class="feature-badge">Sea View</span>
                                    </div>
                                    <div style="color: #666; font-size: 0.95rem;">Luxury Beachfront Room</div>
                                </div>
                                <div class="service-price">₹<?php echo $room['price']; ?><span style="font-size: 0.9rem; color: #666;">/night</span></div>
                            </div>
                            
                            <div class="service-features">
                                <p><i class="fas fa-bed"></i> <?php echo $room['beds']; ?> Comfortable Beds</p>
                                <p><i class="fas fa-snowflake"></i> <?php echo $room['ac']; ?> Air Conditioning</p>
                                <p><i class="fas fa-wifi"></i> <?php echo $room['wifi']; ?> High-Speed WiFi</p>
                                <p><i class="fas fa-tv"></i> Smart TV & Entertainment</p>
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="item_type" value="room">
                                <input type="hidden" name="item_id" value="<?php echo $room['id']; ?>">
                                <input type="hidden" name="item_name" value="Room <?php echo $room['room_no']; ?> (Block <?php echo $room['block']; ?>)">
                                <input type="hidden" name="item_price" value="<?php echo $room['price']; ?>">
                                <button type="submit" name="add_to_package" class="btn" style="width: 100%;">
                                    <i class="fas fa-plus-circle"></i> Add to Package
                                </button>
                            </form>
                        </div>
                        <?php endwhile; else: ?>
                        <div class="empty-state">
                            <i class="fas fa-bed"></i>
                            <h3>No Rooms Available</h3>
                            <p>All our luxury rooms are currently booked. Please check back later.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Transport Services -->
                <div class="card" id="transport">
                    <div class="card-header">
                        <i class="fas fa-taxi"></i>
                        <h2>Transport Services</h2>
                    </div>
                    <p style="color: #666; margin-bottom: 1rem; font-size: 1.1rem;">Comfortable and reliable transportation options for your convenience</p>
                    
                    <div class="cards-grid">
                        <?php
                        $transport = $conn->query("SELECT * FROM transport");
                        while($item = $transport->fetch_assoc()):
                        ?>
                        <div class="service-card">
                            <div class="service-header">
                                <div class="service-title"><?php echo $item['name']; ?></div>
                                <div class="service-price">₹<?php echo $item['price_per_person']; ?></div>
                            </div>
                            
                            <div class="service-features">
                                <p><i class="fas fa-clock"></i> 24/7 Available Service</p>
                                <p><i class="fas fa-shield-alt"></i> Fully Insured & Safe</p>
                                <p><i class="fas fa-user-tie"></i> Professional Drivers</p>
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="item_type" value="transport">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="hidden" name="item_name" value="<?php echo $item['name']; ?>">
                                <input type="hidden" name="item_price" value="<?php echo $item['price_per_person']; ?>">
                                <button type="submit" name="add_to_package" class="btn" style="width: 100%;">
                                    <i class="fas fa-plus-circle"></i> Add to Package
                                </button>
                            </form>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <!-- Activities -->
                <div class="card" id="activities">
                    <div class="card-header">
                        <i class="fas fa-water"></i>
                        <h2>Adventure Activities</h2>
                    </div>
                    <p style="color: #666; margin-bottom: 1rem; font-size: 1.1rem;">Exciting water sports and adventure activities for unforgettable experiences</p>
                    
                    <div class="cards-grid">
                        <?php
                        $activities = $conn->query("SELECT * FROM activities");
                        while($item = $activities->fetch_assoc()):
                        ?>
                        <div class="service-card">
                            <div class="service-header">
                                <div class="service-title"><?php echo $item['name']; ?></div>
                                <div class="service-price">₹<?php echo $item['price_per_person']; ?></div>
                            </div>
                            
                            <div class="service-features">
                                <p><i class="fas fa-life-ring"></i> Premium Safety Equipment</p>
                                <p><i class="fas fa-clock"></i> 2-3 Hours Duration</p>
                                <p><i class="fas fa-camera"></i> Photo Session Included</p>
                                <p><i class="fas fa-swimmer"></i> Professional Instructors</p>
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="item_type" value="activity">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="hidden" name="item_name" value="<?php echo $item['name']; ?>">
                                <input type="hidden" name="item_price" value="<?php echo $item['price_per_person']; ?>">
                                <button type="submit" name="add_to_package" class="btn" style="width: 100%;">
                                    <i class="fas fa-plus-circle"></i> Add to Package
                                </button>
                            </form>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <!-- My Bookings -->
                <div class="card" id="bookings">
                    <div class="card-header">
                        <i class="fas fa-history"></i>
                        <h2>My Bookings</h2>
                    </div>
                    
                    <?php if (empty($bookings)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Bookings Yet</h3>
                            <p>Start by creating a package and making a booking to see your reservations here.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): 
                            $package_items = json_decode($booking['package_items'], true);
                            $payments = $booking_payments[$booking['id']] ?? [];
                        ?>
                        <div class="booking-card">
                            <div class="booking-header">
                                <div>
                                    <div class="booking-id">Booking #<?php echo $booking['id']; ?></div>
                                    <div style="color: #666; font-size: 0.9rem; margin-top: 0.3rem;">
                                        Booked on: <?php echo date('F j, Y g:i A', strtotime($booking['booking_date'])); ?>
                                    </div>
                                </div>
                                <div class="booking-status status-<?php echo $booking['payment_status']; ?>">
                                    <?php echo ucfirst($booking['payment_status']); ?>
                                </div>
                            </div>
                            
                            <div class="booking-details">
                                <div class="detail-item">
                                    <span class="detail-label">Check-in</span>
                                    <span class="detail-value"><?php echo date('M j, Y', strtotime($booking['check_in'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Check-out</span>
                                    <span class="detail-value"><?php echo date('M j, Y', strtotime($booking['check_out'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Stay Duration</span>
                                    <span class="detail-value"><?php echo $booking['stay_days']; ?> nights</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Guests</span>
                                    <span class="detail-value"><?php echo $booking['adults']; ?> Adults, <?php echo $booking['children']; ?> Children</span>
                                </div>
                            </div>
                            
                            <div class="package-summary">
                                <h4 style="color: var(--primary); margin-bottom: 0.5rem;">Package Summary</h4>
                                <div class="package-items">
                                    <?php foreach ($package_items as $item): ?>
                                    <div class="package-item-small">
                                        <span><?php echo $item['name']; ?></span>
                                        <span>
                                            <?php if ($item['type'] === 'room'): ?>
                                                ₹<?php echo $item['daily_rate']; ?> × <?php echo $booking['stay_days']; ?> nights
                                            <?php else: ?>
                                                ₹<?php echo $item['price']; ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="payment-section">
                                <h4 style="color: var(--primary); margin-bottom: 0.5rem;">Payment Details</h4>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                                    <div class="detail-item">
                                        <span class="detail-label">Final Amount</span>
                                        <span class="detail-value">₹<?php echo number_format($booking['final_amount'], 2); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Amount Paid</span>
                                        <span class="detail-value">₹<?php echo number_format($booking['amount_paid'], 2); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Remaining</span>
                                        <span class="detail-value">₹<?php echo number_format($booking['remaining_amount'], 2); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Payment Type</span>
                                        <span class="detail-value"><?php echo ucfirst($booking['payment_type']); ?></span>
                                    </div>
                                </div>
                                
                                <?php if (!empty($payments)): ?>
                                <div class="payment-history">
                                    <h5 style="color: var(--dark); margin-bottom: 0.5rem;">Payment History</h5>
                                    <?php foreach ($payments as $payment): ?>
                                    <div class="payment-item">
                                        <div>
                                            <span style="font-weight: 600;"><?php echo $payment['transaction_id']; ?></span>
                                            <div style="font-size: 0.8rem; color: #666;">
                                                <?php echo date('M j, Y g:i A', strtotime($payment['payment_date'])); ?>
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <div style="font-weight: 600; color: var(--success);">₹<?php echo number_format($payment['amount'], 2); ?></div>
                                            <div style="font-size: 0.8rem; color: #666;"><?php echo ucfirst($payment['payment_method']); ?></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($booking['payment_status'] === 'partial' && $booking['remaining_amount'] > 0): ?>
                                <div class="complete-payment-form">
                                    <button type="button" class="btn btn-success btn-sm" onclick="openCompletePaymentModal(<?php echo $booking['id']; ?>, <?php echo $booking['remaining_amount']; ?>)">
                                        <i class="fas fa-credit-card"></i> Complete Payment (₹<?php echo number_format($booking['remaining_amount'], 2); ?>)
                                    </button>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($booking['payment_status'] === 'pending' || $booking['payment_status'] === 'partial'): ?>
                                <form method="POST" style="margin-top: 1rem;">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <button type="submit" name="cancel_payment" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to cancel this booking?')">
                                        <i class="fas fa-times"></i> Cancel Booking
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Profile Management -->
                <div class="card" id="profile">
                    <div class="card-header">
                        <i class="fas fa-user-cog"></i>
                        <h2>Profile Settings</h2>
                    </div>
                    <form method="POST">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                            <div class="form-group">
                                <label for="name"><i class="fas fa-user"></i> Full Name</label>
                                <input type="text" id="name" name="name" class="form-control" 
                                       value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($_SESSION['user_email']); ?>" required>
                            </div>
                        </div>
                        <button type="submit" name="update_profile" class="btn" style="width: 100%;">
                            <i class="fas fa-save"></i> Update Profile Information
                        </button>
                    </form>
                    
                    <div style="margin-top: 2.5rem; padding-top: 2rem; border-top: 2px solid #f8f9fa;">
                        <h4 style="color: var(--danger); margin-bottom: 1rem; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-exclamation-triangle"></i> Danger Zone
                        </h4>
                        <p style="color: #666; margin-bottom: 1.5rem; line-height: 1.6;">
                            Once you delete your account, there is no going back. Please be certain.
                        </p>
                        <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete your account? This action cannot be undone.');">
                            <button type="submit" name="delete_profile" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Delete My Account Permanently
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar - Package Summary -->
            <div class="sidebar" id="package">
                <h3 style="color: var(--primary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-shopping-cart"></i> Your Package
                </h3>
                
                <?php if (empty($_SESSION['package'])): ?>
                    <div class="empty-state" style="padding: 2rem 1rem;">
                        <i class="fas fa-shopping-cart"></i>
                        <h4>Package Empty</h4>
                        <p>Add items to build your package</p>
                    </div>
                <?php else: ?>
                    <!-- Booking Details Form -->
                    <div style="margin-bottom: 1.5rem; padding: 1.5rem; background: var(--light); border-radius: 8px;">
                        <h4 style="color: var(--primary); margin-bottom: 1rem;">Booking Details</h4>
                        <form method="POST" id="bookingDetailsForm">
                            <div class="form-group">
                                <label for="adults">Number of Adults *</label>
                                <input type="number" id="adults" name="adults" class="form-control" min="1" max="10" 
                                       value="<?php echo $_SESSION['booking_details']['adults'] ?? 1; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="children">Number of Children (0-15 years) *</label>
                                <input type="number" id="children" name="children" class="form-control" min="0" max="10" 
                                       value="<?php echo $_SESSION['booking_details']['children'] ?? 0; ?>" required>
                                <small style="color: #666; display: block; margin-top: 5px;">
                                    First 2 children free, ₹500 per additional child
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_no">Contact Number *</label>
                                <input type="tel" id="contact_no" name="contact_no" class="form-control" 
                                       value="<?php echo $_SESSION['booking_details']['contact_no'] ?? ''; ?>" 
                                       placeholder="Enter your contact number" required>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="check_in">Check-in Date *</label>
                                    <input type="date" id="check_in" name="check_in" class="form-control" 
                                           value="<?php echo $_SESSION['booking_details']['check_in'] ?? ''; ?>" required>
                                    <span class="date-validation" id="checkInValidation"></span>
                                </div>
                                <div class="form-group">
                                    <label for="check_out">Check-out Date *</label>
                                    <input type="date" id="check_out" name="check_out" class="form-control" 
                                           value="<?php echo $_SESSION['booking_details']['check_out'] ?? ''; ?>" required>
                                    <span class="date-validation" id="checkOutValidation"></span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <span class="date-validation" id="stayDuration"></span>
                            </div>
                            
                            <button type="submit" name="update_booking_details" class="btn" style="width: 100%;">
                                <i class="fas fa-sync-alt"></i> Update Booking Details
                            </button>
                        </form>
                    </div>
                    
                    <!-- Package Items -->
                    <div style="margin-bottom: 2rem;">
                        <h4 style="color: var(--primary); margin-bottom: 1rem;">Package Items</h4>
                        <?php foreach ($_SESSION['package'] as $index => $item): ?>
                        <div class="package-item">
                            <div class="item-info">
                                <div class="item-name"><?php echo $item['name']; ?></div>
                                <div class="item-details">
                                    <span class="item-type"><?php echo $item['type']; ?></span>
                                    <span class="item-price">
                                        <?php if ($item['type'] === 'room' && $stay_days > 0): ?>
                                            ₹<?php echo $item['daily_rate']; ?>/night × <?php echo $stay_days; ?> nights
                                        <?php else: ?>
                                            ₹<?php echo $item['price']; ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php if ($item['type'] === 'room' && $stay_days > 0): ?>
                                <div class="calculation-details">
                                    <div class="calculation-line">
                                        <span>Daily Rate:</span>
                                        <span>₹<?php echo $item['daily_rate']; ?>/night</span>
                                    </div>
                                    <div class="calculation-line">
                                        <span>Stay Duration:</span>
                                        <span><?php echo $stay_days; ?> nights</span>
                                    </div>
                                    <div class="calculation-line">
                                        <span>Total:</span>
                                        <span>₹<?php echo $item['daily_rate'] * $stay_days; ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="item_index" value="<?php echo $index; ?>">
                                <button type="submit" name="remove_from_package" class="btn btn-danger btn-sm">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Package Summary -->
                    <div style="border-top: 2px solid var(--primary); padding-top: 1.5rem;">
                        <h4 style="color: var(--primary); margin-bottom: 1rem;">Package Summary</h4>
                        
                        <?php if ($stay_days > 0): ?>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span>Stay Duration:</span>
                                <span><?php echo $stay_days; ?> nights</span>
                        </div>
                        <?php endif; ?>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Package Total:</span>
                            <strong>₹<?php echo $package_total; ?></strong>
                        </div>
                        
                        <?php if ($children_charges > 0): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Children Charges:</span>
                            <span>₹<?php echo $children_charges; ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Subtotal:</span>
                            <span>₹<?php echo $package_total + $children_charges; ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Tax (18% GST):</span>
                            <span>₹<?php echo number_format($tax_amount, 2); ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; font-weight: bold; margin-bottom: 1.5rem; font-size: 1.1rem;">
                            <span>Final Amount:</span>
                            <span style="color: var(--accent);">₹<?php echo number_format($final_amount, 2); ?></span>
                        </div>
                        
                        <div style="display: flex; gap: 1rem;">
                            <form method="POST" style="flex: 1;">
                                <button type="submit" name="clear_package" class="btn btn-danger" style="width: 100%;">
                                    <i class="fas fa-trash"></i> Clear Package
                                </button>
                            </form>
                            
                            <button id="makePaymentBtn" class="btn btn-success" style="flex: 1;" <?php echo $all_fields_filled ? '' : 'disabled'; ?> onclick="openPaymentModal()">
                                <i class="fas fa-credit-card"></i> Make Payment
                            </button>
                        </div>
                        
                        <?php if (!$all_fields_filled): ?>
                            <div style="margin-top: 1rem; padding: 0.8rem; background: #fff3cd; border-radius: 5px; border-left: 4px solid var(--warning);">
                                <small style="color: #856404;">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    <?php if (!$_SESSION['booking_updated']): ?>
                                        Please update booking details to enable payment
                                    <?php else: ?>
                                        Please fill all booking details to enable payment
                                    <?php endif; ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Cart functionality
        function openCart() {
            document.getElementById('cartSidebar').classList.add('active');
            document.getElementById('cartOverlay').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeCart() {
            document.getElementById('cartSidebar').classList.remove('active');
            document.getElementById('cartOverlay').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Close cart when clicking overlay
        document.getElementById('cartOverlay').addEventListener('click', closeCart);

        // Payment Modal Functions
        function openPaymentModal() {
            document.getElementById('paymentModal').classList.add('active');
            document.body.style.overflow = 'hidden';
            resetPaymentSelection();
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        function selectPaymentMethod(method) {
            // Remove selected class from all methods
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Add selected class to clicked method
            event.currentTarget.classList.add('selected');
            
            // Set the radio value
            document.querySelector(`input[name="payment_method"][value="${method}"]`).checked = true;
            
            validatePaymentForm();
        }

        function selectPaymentType(type) {
            // Remove selected class from all types
            document.querySelectorAll('.payment-type-option').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Add selected class to clicked type
            event.currentTarget.classList.add('selected');
            
            // Set the radio value
            document.querySelector(`input[name="payment_type"][value="${type}"]`).checked = true;
            
            // Update payment summary
            const finalAmount = <?php echo $final_amount; ?>;
            const amountToPay = (type === 'half') ? finalAmount / 2 : finalAmount;
            
            document.getElementById('selectedPaymentType').textContent = type === 'full' ? 'Full Payment' : 'Half Payment';
            document.getElementById('amountToPay').textContent = '₹' + amountToPay.toFixed(2);
            
            validatePaymentForm();
        }

        function resetPaymentSelection() {
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            document.querySelectorAll('.payment-type-option').forEach(el => {
                el.classList.remove('selected');
            });
            document.getElementById('selectedPaymentType').textContent = '-';
            document.getElementById('amountToPay').textContent = '₹0.00';
            document.getElementById('confirmPaymentBtn').disabled = true;
        }

        function validatePaymentForm() {
            const paymentMethodSelected = document.querySelector('input[name="payment_method"]:checked');
            const paymentTypeSelected = document.querySelector('input[name="payment_type"]:checked');
            
            if (paymentMethodSelected && paymentTypeSelected) {
                document.getElementById('confirmPaymentBtn').disabled = false;
            } else {
                document.getElementById('confirmPaymentBtn').disabled = true;
            }
        }

        // Complete Payment Modal Functions
        function openCompletePaymentModal(bookingId, remainingAmount) {
            document.getElementById('completePaymentBookingId').value = bookingId;
            document.getElementById('remainingAmountDisplay').textContent = '₹' + remainingAmount.toFixed(2);
            document.getElementById('completePaymentModal').classList.add('active');
            document.body.style.overflow = 'hidden';
            resetCompletePaymentSelection();
        }

        function closeCompletePaymentModal() {
            document.getElementById('completePaymentModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        function selectCompletePaymentMethod(method) {
            // Remove selected class from all methods
            document.querySelectorAll('#completePaymentModal .payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Add selected class to clicked method
            event.currentTarget.classList.add('selected');
            
            // Set the radio value
            document.querySelector(`#completePaymentModal input[name="payment_method"][value="${method}"]`).checked = true;
            
            validateCompletePaymentForm();
        }

        function resetCompletePaymentSelection() {
            document.querySelectorAll('#completePaymentModal .payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            document.getElementById('confirmCompletePaymentBtn').disabled = true;
        }

        function validateCompletePaymentForm() {
            const paymentMethodSelected = document.querySelector('#completePaymentModal input[name="payment_method"]:checked');
            
            if (paymentMethodSelected) {
                document.getElementById('confirmCompletePaymentBtn').disabled = false;
            } else {
                document.getElementById('confirmCompletePaymentBtn').disabled = true;
            }
        }

        // Close modals when clicking outside
        document.getElementById('paymentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePaymentModal();
            }
        });

        document.getElementById('completePaymentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCompletePaymentModal();
            }
        });

        // Smooth scrolling for navigation
        document.querySelectorAll('.dashboard-nav a').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                if (this.getAttribute('href').startsWith('#')) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        closeCart(); // Close cart when navigating
                    }
                }
            });
        });

        // Auto-hide alerts after 5 seconds
        function autoHideAlerts() {
            const successAlert = document.getElementById('successAlert');
            const errorAlert = document.getElementById('errorAlert');
            
            if (successAlert) {
                setTimeout(() => {
                    successAlert.classList.add('fade-out');
                    setTimeout(() => successAlert.remove(), 500);
                }, 5000);
            }
            
            if (errorAlert) {
                setTimeout(() => {
                    errorAlert.classList.add('fade-out');
                    setTimeout(() => errorAlert.remove(), 500);
                }, 5000);
            }
        }

        // Date validation and calculation
        function validateDates() {
            const checkInInput = document.getElementById('check_in');
            const checkOutInput = document.getElementById('check_out');
            const checkInValidation = document.getElementById('checkInValidation');
            const checkOutValidation = document.getElementById('checkOutValidation');
            const stayDuration = document.getElementById('stayDuration');
            
            const today = new Date().toISOString().split('T')[0];
            
            // Set minimum date for check-in to today
            if (checkInInput) {
                checkInInput.min = today;
                
                // Update check-out min date when check-in changes
                checkInInput.addEventListener('change', function() {
                    if (checkOutInput) {
                        checkOutInput.min = this.value;
                        validateDates();
                    }
                });
            }
            
            if (checkOutInput) {
                checkOutInput.addEventListener('change', validateDates);
            }
            
            // Validate dates
            if (checkInInput && checkOutInput && checkInInput.value && checkOutInput.value) {
                const checkInDate = new Date(checkInInput.value);
                const checkOutDate = new Date(checkOutInput.value);
                
                // Reset validation messages
                checkInValidation.textContent = '';
                checkOutValidation.textContent = '';
                checkInValidation.className = 'date-validation';
                checkOutValidation.className = 'date-validation';
                
                // Validate check-in date
                if (checkInDate < new Date(today)) {
                    checkInValidation.textContent = 'Check-in date cannot be in the past';
                    checkInValidation.className = 'date-validation error';
                }
                
                // Validate check-out date
                if (checkOutDate <= checkInDate) {
                    checkOutValidation.textContent = 'Check-out date must be after check-in date';
                    checkOutValidation.className = 'date-validation error';
                }
                
                // Calculate stay duration
                if (checkOutDate > checkInDate) {
                    const timeDiff = checkOutDate.getTime() - checkInDate.getTime();
                    const dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
                    
                    stayDuration.textContent = `Stay Duration: ${dayDiff} night(s)`;
                    stayDuration.className = 'date-validation success';
                } else {
                    stayDuration.textContent = 'Please select valid dates';
                    stayDuration.className = 'date-validation error';
                }
            } else {
                stayDuration.textContent = 'Please select both dates';
                stayDuration.className = 'date-validation error';
            }
        }

        // Update active nav link on scroll
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('.card');
            const navLinks = document.querySelectorAll('.dashboard-nav a');
            
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 100;
                if (pageYOffset >= sectionTop) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active');
                }
            });
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            autoHideAlerts();
            validateDates();
            
            // Add hover effects to cards
            const cards = document.querySelectorAll('.service-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
            
            // Validate dates when form inputs change
            const formInputs = document.querySelectorAll('#bookingDetailsForm input');
            formInputs.forEach(input => {
                input.addEventListener('change', validateDates);
            });
        });

        // Enhanced button interactions
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('mousedown', function() {
                if (!this.disabled) {
                    this.style.transform = 'translateY(0)';
                }
            });
            
            button.addEventListener('mouseup', function() {
                if (!this.disabled) {
                    this.style.transform = 'translateY(-2px)';
                }
            });
        });

        // Update cart count dynamically
        function updateCartCount() {
            const cartCount = document.querySelector('.cart-count');
            const cartTotalCount = document.getElementById('cartTotalCount');
            const itemCount = <?php echo count($_SESSION['package']); ?>;
            
            if (cartCount) {
                if (itemCount > 0) {
                    cartCount.textContent = itemCount;
                    cartCount.style.display = 'flex';
                } else {
                    cartCount.style.display = 'none';
                }
            }
            
            if (cartTotalCount) {
                cartTotalCount.textContent = itemCount;
            }
        }

        // Initialize cart count
        updateCartCount();
    </script>
</body>
</html>