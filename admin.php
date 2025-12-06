<?php
error_reporting(E_ALL & ~E_NOTICE);
?>

<?php include 'db.php'; ?>

<!DOCTYPE html>
<html>
<head>
  <title>DevbagStayz - Admin Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #2563eb;
      --secondary: #1d4ed8;
      --success: #10b981;
      --danger: #ef4444;
      --warning: #f59e0b;
      --info: #06b6d4;
      --light: #f8fafc;
      --dark: #1e293b;
      --gray: #64748b;
      --sidebar-width: 260px;
      --header-height: 70px;
      --accent: #3b82f6;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f1f5f9;
      color: #334155;
      line-height: 1.6;
      font-size: 14px;
    }
    
    .dashboard-container {
      display: flex;
      min-height: 100vh;
    }
    
    /* Sidebar Styles */
    .sidebar {
      width: var(--sidebar-width);
      background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
      color: white;
      position: fixed;
      height: 100vh;
      overflow-y: auto;
      transition: all 0.3s ease;
      z-index: 1000;
      box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    }
    
    .sidebar-header {
      padding: 25px 20px;
      text-align: center;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .sidebar-header h2 {
      font-size: 1.4rem;
      margin-bottom: 5px;
      font-weight: 700;
    }
    
    .sidebar-header p {
      font-size: 0.8rem;
      opacity: 0.9;
      font-weight: 400;
    }
    
    .sidebar-menu {
      padding: 15px 0;
    }
    
    .menu-item {
      padding: 14px 20px;
      display: flex;
      align-items: center;
      color: white;
      text-decoration: none;
      transition: all 0.3s ease;
      border-left: 3px solid transparent;
      font-size: 0.95rem;
      margin: 2px 10px;
      border-radius: 8px;
    }
    
    .menu-item:hover {
      background: rgba(255, 255, 255, 0.1);
      border-left-color: var(--accent);
    }
    
    .menu-item.active {
      background: rgba(255, 255, 255, 0.15);
      border-left-color: var(--accent);
    }
    
    .menu-item i {
      margin-right: 12px;
      width: 20px;
      text-align: center;
      font-size: 1.1rem;
    }
    
    /* Main Content Styles */
    .main-content {
      flex: 1;
      margin-left: var(--sidebar-width);
      transition: all 0.3s ease;
    }
    
    .header {
      height: var(--header-height);
      background: white;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 30px;
      position: sticky;
      top: 0;
      z-index: 100;
      border-bottom: 1px solid #e2e8f0;
    }
    
    .header-title {
      font-size: 1.3rem;
      font-weight: 600;
      color: var(--dark);
    }
    
    .header-actions {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    .logout-btn {
      background: var(--danger);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      font-size: 0.9rem;
      font-weight: 500;
    }
    
    .logout-btn:hover {
      background: #dc2626;
    }
    
    /* Content Area */
    .content {
      padding: 25px;
    }
    
    .section {
      display: none;
      background: white;
      border-radius: 12px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      padding: 25px;
      margin-bottom: 20px;
      border: 1px solid #e2e8f0;
    }
    
    .section.active {
      display: block;
      animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 1px solid #e2e8f0;
    }
    
    .section-title {
      font-size: 1.4rem;
      font-weight: 600;
      color: var(--dark);
    }
    
    .add-btn {
      background: var(--success);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      font-weight: 500;
      font-size: 0.9rem;
    }
    
    .add-btn:hover {
      background: #059669;
    }
    
    /* Payment Analytics Styles */
    .payment-analytics {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-bottom: 25px;
    }
    
    .analytics-card {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .analytics-card h3 {
      font-size: 0.85rem;
      margin-bottom: 10px;
      opacity: 0.95;
      font-weight: 500;
    }
    
    .analytics-card .value {
      font-size: 1.8rem;
      font-weight: 700;
      margin-bottom: 5px;
    }
    
    .analytics-card .label {
      font-size: 0.8rem;
      opacity: 0.9;
    }
    
    /* Search Bar Styles */
    .search-container {
      margin-bottom: 20px;
    }
    
    .search-box {
      display: flex;
      gap: 10px;
      align-items: center;
    }
    
    .search-input {
      flex: 1;
      padding: 12px 16px;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      font-size: 0.9rem;
      transition: all 0.3s ease;
      background: white;
    }
    
    .search-input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
      outline: none;
    }
    
    .search-btn {
      background: var(--primary);
      color: white;
      border: none;
      padding: 12px 20px;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 0.9rem;
      font-weight: 500;
    }
    
    .search-btn:hover {
      background: var(--secondary);
    }
    
    /* Table Styles */
    .table-container {
      overflow-x: auto;
      border-radius: 8px;
      border: 1px solid #e2e8f0;
      background: white;
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.85rem;
    }
    
    th, td {
      padding: 14px 16px;
      text-align: left;
      border-bottom: 1px solid #e2e8f0;
      white-space: nowrap;
    }
    
    th {
      background: #f8fafc;
      font-weight: 600;
      color: var(--dark);
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    tr:hover {
      background-color: #f8fafc;
    }
    
    .action-buttons {
      display: flex;
      gap: 6px;
    }
    
    .update-btn, .delete-btn {
      padding: 6px 12px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 0.75rem;
      transition: all 0.3s ease;
      font-weight: 500;
    }
    
    .update-btn {
      background: var(--info);
      color: white;
    }
    
    .update-btn:hover {
      background: #0891b2;
    }
    
    .delete-btn {
      background: var(--danger);
      color: white;
      text-decoration: none;
      display: inline-block;
    }
    
    .delete-btn:hover {
      background: #dc2626;
    }
    
    .status-badge {
      padding: 4px 8px;
      border-radius: 6px;
      font-size: 0.7rem;
      font-weight: 600;
    }
    
    .status-completed {
      background: #dcfce7;
      color: #166534;
    }
    
    .status-pending {
      background: #fef3c7;
      color: #92400e;
    }
    
    .status-cancelled {
      background: #fee2e2;
      color: #991b1b;
    }
    
    .status-partial {
      background: #dbeafe;
      color: #1e40af;
    }
    
    /* Form Styles */
    input, select {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      margin-bottom: 12px;
      font-size: 0.9rem;
      transition: all 0.3s ease;
      background: white;
    }
    
    input:focus, select:focus {
      border-color: var(--primary);
      outline: none;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    
    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      justify-content: center;
      align-items: center;
      z-index: 2000;
    }
    
    .modal-content {
      background: white;
      padding: 25px;
      border-radius: 12px;
      width: 90%;
      max-width: 450px;
      position: relative;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      animation: modalSlideIn 0.3s ease;
    }
    
    @keyframes modalSlideIn {
      from { opacity: 0; transform: scale(0.9); }
      to { opacity: 1; transform: scale(1); }
    }
    
    .close {
      position: absolute;
      top: 15px;
      right: 20px;
      font-size: 20px;
      cursor: pointer;
      color: var(--gray);
      transition: all 0.3s ease;
    }
    
    .close:hover {
      color: var(--dark);
    }
    
    .modal-title {
      font-size: 1.3rem;
      margin-bottom: 15px;
      color: var(--dark);
      font-weight: 600;
    }
    
    .submit-btn {
      background: var(--primary);
      color: white;
      border: none;
      padding: 12px 20px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 0.95rem;
      font-weight: 500;
      width: 100%;
      transition: all 0.3s ease;
    }
    
    .submit-btn:hover {
      background: var(--secondary);
    }
    
    /* Date Filter Styles */
    .date-filter {
      display: flex;
      gap: 10px;
      align-items: center;
    }
    
    .date-filter input {
      width: auto;
      margin-bottom: 0;
      padding: 8px 12px;
    }
    
    /* Responsive Styles */
    @media (max-width: 992px) {
      .sidebar {
        width: 70px;
        overflow: visible;
      }
      
      .sidebar-header h2, .sidebar-header p, .menu-item span {
        display: none;
      }
      
      .menu-item {
        justify-content: center;
        padding: 16px;
        margin: 2px 5px;
      }
      
      .menu-item i {
        margin-right: 0;
        font-size: 1.2rem;
      }
      
      .main-content {
        margin-left: 70px;
      }
    }
    
    @media (max-width: 768px) {
      .header {
        padding: 0 20px;
      }
      
      .content {
        padding: 20px;
      }
      
      .section {
        padding: 20px;
      }
      
      .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
      }
      
      .add-btn {
        width: 100%;
        justify-content: center;
      }
      
      .payment-analytics {
        grid-template-columns: 1fr;
      }
      
      th, td {
        padding: 10px 12px;
        font-size: 0.8rem;
      }
      
      .date-filter {
        width: 100%;
        justify-content: space-between;
      }
    }
    
    @media (max-width: 576px) {
      .sidebar {
        width: 0;
      }
      
      .main-content {
        margin-left: 0;
      }
    }
  </style>

  <script>
    function showSection(sectionId){
      document.querySelectorAll('.section').forEach(sec => sec.classList.remove('active'));
      document.getElementById(sectionId).classList.add('active');
      document.querySelectorAll('.menu-item').forEach(link => link.classList.remove('active'));
      document.querySelector('.menu-item[href="#'+sectionId+'"]').classList.add('active');
      
      // Update header title
      const sectionTitle = document.getElementById(sectionId).querySelector('.section-title').textContent;
      document.querySelector('.header-title').textContent = sectionTitle;
    }

    function openModal(modalId){ 
      document.getElementById(modalId).style.display='flex'; 
    }
    
    function closeModal(modalId){ 
      document.getElementById(modalId).style.display='none'; 
    }

    window.onclick = function(event){
      document.querySelectorAll('.modal').forEach(modal => { 
        if(event.target == modal) modal.style.display='none'; 
      });
    }

    function toggleSidebar() {
      const sidebar = document.querySelector('.sidebar');
      const mainContent = document.querySelector('.main-content');
      
      if (sidebar.style.width === '0px' || sidebar.style.width === '') {
        sidebar.style.width = 'var(--sidebar-width)';
        mainContent.style.marginLeft = 'var(--sidebar-width)';
      } else {
        sidebar.style.width = '0';
        mainContent.style.marginLeft = '0';
      }
    }

    function searchPayments() {
      const searchTerm = document.getElementById('searchInput').value.toLowerCase();
      const rows = document.querySelectorAll('#paymentsTable tbody tr');
      
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    }

    function filterSchedulesByDate() {
      const filterDate = document.getElementById('scheduleDateFilter').value;
      const rows = document.querySelectorAll('#schedulesTable tbody tr');
      
      rows.forEach(row => {
        const checkinDate = row.getAttribute('data-checkin');
        if (!filterDate || checkinDate === filterDate) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    }

    function clearDateFilter() {
      document.getElementById('scheduleDateFilter').value = '';
      filterSchedulesByDate();
    }

    // Handle Enter key in search input
    document.addEventListener('DOMContentLoaded', function() {
      const searchInput = document.getElementById('searchInput');
      if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
          if (e.key === 'Enter') {
            searchPayments();
          }
        });
      }
    });

    window.onload = function(){ 
      showSection('rooms'); 
    }
  </script>
</head>
<body>

<div class="dashboard-container">
  <!-- Sidebar -->
  <div class="sidebar">
    <div class="sidebar-header">
      <h2>DevbagStayz</h2>
      <p>Admin Dashboard</p>
    </div>
    
    <div class="sidebar-menu">
      <a href="#rooms" class="menu-item active" onclick="showSection('rooms')">
        <i class="fas fa-bed"></i>
        <span>Rooms</span>
      </a>
      <a href="#dining" class="menu-item" onclick="showSection('dining')">
        <i class="fas fa-utensils"></i>
        <span>Dining</span>
      </a>
      <a href="#transport" class="menu-item" onclick="showSection('transport')">
        <i class="fas fa-bus"></i>
        <span>Transport</span>
      </a>
      <a href="#activities" class="menu-item" onclick="showSection('activities')">
        <i class="fas fa-hiking"></i>
        <span>Activities</span>
      </a>
      <a href="#customers" class="menu-item" onclick="showSection('customers')">
        <i class="fas fa-users"></i>
        <span>Customers</span>
      </a>
      <a href="#payments" class="menu-item" onclick="showSection('payments')">
        <i class="fas fa-credit-card"></i>
        <span>Payments</span>
      </a>
      <a href="#schedules" class="menu-item" onclick="showSection('schedules')">
        <i class="fas fa-calendar-alt"></i>
        <span>Schedules</span>
      </a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Header -->
    <div class="header">
      <div class="header-title">Rooms Management</div>
      <div class="header-actions">
        <button class="logout-btn" onclick="window.location.href='index.php'">
          <i class="fas fa-sign-out-alt"></i>
          Logout
        </button>
      </div>
    </div>

    <!-- Content -->
    <div class="content">
      <?php
      function safePost($key){ return isset($_POST[$key]) ? htmlspecialchars($_POST[$key]) : ''; }

      // ----------------- ADD -----------------
      if(isset($_POST['add_room'])){
          $stmt = $conn->prepare("INSERT INTO rooms (room_no, block, beds, ac, wifi, available, price) VALUES (?, ?, ?, ?, ?, ?, ?)");
          $stmt->bind_param("ssisssd", safePost('room_no'), safePost('block'), safePost('beds'), safePost('ac'), safePost('wifi'), safePost('available'), safePost('price'));
          $stmt->execute();
      }
      if(isset($_POST['add_dining'])){
          $stmt = $conn->prepare("INSERT INTO dining (name, price) VALUES (?, ?)");
          $stmt->bind_param("sd", safePost('name'), safePost('price'));
          $stmt->execute();
      }
      if(isset($_POST['add_transport'])){
          $stmt = $conn->prepare("INSERT INTO transport (name, price_per_person) VALUES (?, ?)");
          $stmt->bind_param("sd", safePost('name'), safePost('price_per_person'));
          $stmt->execute();
      }
      if(isset($_POST['add_activity'])){
          $stmt = $conn->prepare("INSERT INTO activities (name, price_per_person) VALUES (?, ?)");
          $stmt->bind_param("sd", safePost('name'), safePost('price_per_person'));
          $stmt->execute();
      }
      if(isset($_POST['add_user'])){
          $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
          $stmt->bind_param("sss", safePost('name'), safePost('email'), safePost('password'));
          $stmt->execute();
      }
      if(isset($_POST['add_payment'])){
          $stmt = $conn->prepare("INSERT INTO payments (customer_id, amount, payment_date) VALUES (?, ?, ?)");
          $stmt->bind_param("ids", safePost('cust_id'), safePost('amount'), safePost('pay_date'));
          $stmt->execute();
      }
      if(isset($_POST['add_schedule'])){
          $stmt = $conn->prepare("INSERT INTO schedules (customer_id, room_id, activity_id, transport_id, schedule_date) VALUES (?, ?, ?, ?, ?)");
          $stmt->bind_param("iiiis", safePost('cust_id'), safePost('room_id'), safePost('activity_id'), safePost('transport_id'), safePost('schedule_date'));
          $stmt->execute();
      }

      // ----------------- DELETE -----------------
      foreach(['room','dining','transport','activity','user','payment','schedule'] as $type){
          if(isset($_GET["delete_$type"])){
              $conn->query("DELETE FROM {$type}s WHERE id=".$_GET["delete_$type"]);
          }
      }

      // ----------------- UPDATE -----------------
      if(isset($_POST['update_room'])){
          $stmt = $conn->prepare("UPDATE rooms SET room_no=?, block=?, beds=?, ac=?, wifi=?, available=?, price=? WHERE id=?");
          $stmt->bind_param("ssisssdi", safePost('room_no'), safePost('block'), safePost('beds'), safePost('ac'), safePost('wifi'), safePost('available'), safePost('price'), safePost('id'));
          $stmt->execute();
      }
      if(isset($_POST['update_dining'])){
          $stmt = $conn->prepare("UPDATE dining SET name=?, price=? WHERE id=?");
          $stmt->bind_param("sdi", safePost('name'), safePost('price'), safePost('id'));
          $stmt->execute();
      }
      if(isset($_POST['update_transport'])){
          $stmt = $conn->prepare("UPDATE transport SET name=?, price_per_person=? WHERE id=?");
          $stmt->bind_param("sdi", safePost('name'), safePost('price_per_person'), safePost('id'));
          $stmt->execute();
      }
      if(isset($_POST['update_activity'])){
          $stmt = $conn->prepare("UPDATE activities SET name=?, price_per_person=? WHERE id=?");
          $stmt->bind_param("sdi", safePost('name'), safePost('price_per_person'), safePost('id'));
          $stmt->execute();
      }
      if(isset($_POST['update_user'])){
          $stmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
          $stmt->bind_param("ssi", safePost('name'), safePost('email'), safePost('id'));
          $stmt->execute();
      }
      if(isset($_POST['update_booking_payment'])){
          $stmt = $conn->prepare("UPDATE bookings SET payment_status=?, amount_paid=?, remaining_amount=? WHERE id=?");
          $stmt->bind_param("sddi", safePost('payment_status'), safePost('amount_paid'), safePost('remaining_amount'), safePost('id'));
          $stmt->execute();
      }
      if(isset($_POST['update_schedule'])){
          $stmt = $conn->prepare("UPDATE schedules SET customer_id=?, room_id=?, activity_id=?, transport_id=?, schedule_date=? WHERE id=?");
          $stmt->bind_param("iiiisi", safePost('cust_id'), safePost('room_id'), safePost('activity_id'), safePost('transport_id'), safePost('schedule_date'), safePost('id'));
          $stmt->execute();
      }

      // Fetch payment analytics data
      $total_revenue = $conn->query("SELECT SUM(final_amount) as total FROM bookings WHERE payment_status != 'cancelled'")->fetch_assoc()['total'] ?? 0;
      $completed_payments = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE payment_status = 'completed'")->fetch_assoc()['count'] ?? 0;
      $partial_payments = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE payment_status = 'partial'")->fetch_assoc()['count'] ?? 0;
      $half_payments = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE payment_type = 'half'")->fetch_assoc()['count'] ?? 0;
      $total_transactions = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'] ?? 0;
      ?>

      <!-- ----------------- ROOMS ----------------- -->
      <div id="rooms" class="section active">
        <div class="section-header">
          <div class="section-title">Rooms Management</div>
          <button class="add-btn" onclick="openModal('addRoomModal')">
            <i class="fas fa-plus"></i>
            Add Room
          </button>
        </div>
        
        <div id="addRoomModal" class="modal">
          <div class="modal-content">
            <span class="close" onclick="closeModal('addRoomModal')">&times;</span>
            <div class="modal-title">Add New Room</div>
            <form method="POST">
              <input type="text" name="room_no" placeholder="Room No" required>
              <input type="text" name="block" placeholder="Block" required>
              <input type="number" name="beds" placeholder="Beds" required>
              <select name="ac">
                <option value="Yes">AC: Yes</option>
                <option value="No">AC: No</option>
              </select>
              <select name="wifi">
                <option value="Yes">WiFi: Yes</option>
                <option value="No">WiFi: No</option>
              </select>
              <select name="available">
                <option value="Yes">Available: Yes</option>
                <option value="No">Available: No</option>
              </select>
              <input type="number" step="0.01" name="price" placeholder="Price" required>
              <button class="submit-btn" name="add_room">Add Room</button>
            </form>
          </div>
        </div>

        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Room No</th>
                <th>Block</th>
                <th>Beds</th>
                <th>AC</th>
                <th>WiFi</th>
                <th>Available</th>
                <th>Price</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $rooms = $conn->query("SELECT * FROM rooms");
              while($r = $rooms->fetch_assoc()){
                echo "<tr>
                  <form method='POST'>
                  <td>{$r['id']}<input type='hidden' name='id' value='{$r['id']}'></td>
                  <td><input name='room_no' value='{$r['room_no']}'></td>
                  <td><input name='block' value='{$r['block']}'></td>
                  <td><input name='beds' value='{$r['beds']}'></td>
                  <td>
                    <select name='ac'>
                      <option ".($r['ac']=='Yes'?'selected':'').">Yes</option>
                      <option ".($r['ac']=='No'?'selected':'').">No</option>
                    </select>
                  </td>
                  <td>
                    <select name='wifi'>
                      <option ".($r['wifi']=='Yes'?'selected':'').">Yes</option>
                      <option ".($r['wifi']=='No'?'selected':'').">No</option>
                    </select>
                  </td>
                  <td>
                    <select name='available'>
                      <option ".($r['available']=='Yes'?'selected':'').">Yes</option>
                      <option ".($r['available']=='No'?'selected':'').">No</option>
                    </select>
                  </td>
                  <td><input name='price' value='{$r['price']}'></td>
                  <td class='action-buttons'>
                    <button class='update-btn' name='update_room'>Update</button>
                    <a class='delete-btn' href='?delete_room={$r['id']}' onclick='return confirm(\"Delete this room?\")'>Delete</a>
                  </td>
                  </form>
                </tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ----------------- DINING ----------------- -->
      <div id="dining" class="section">
        <div class="section-header">
          <div class="section-title">Dining Management</div>
          <button class="add-btn" onclick="openModal('addDiningModal')">
            <i class="fas fa-plus"></i>
            Add Dining
          </button>
        </div>
        
        <div id="addDiningModal" class="modal">
          <div class="modal-content">
            <span class="close" onclick="closeModal('addDiningModal')">&times;</span>
            <div class="modal-title">Add New Dining Option</div>
            <form method="POST">
              <input type="text" name="name" placeholder="Name" required>
              <input type="number" step="0.01" name="price" placeholder="Price" required>
              <button class="submit-btn" name="add_dining">Add Dining</button>
            </form>
          </div>
        </div>

        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Price</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $dining = $conn->query("SELECT * FROM dining");
              while($d = $dining->fetch_assoc()){
                echo "<tr>
                  <form method='POST'>
                  <td>{$d['id']}<input type='hidden' name='id' value='{$d['id']}'></td>
                  <td><input name='name' value='{$d['name']}'></td>
                  <td><input name='price' value='{$d['price']}'></td>
                  <td class='action-buttons'>
                    <button class='update-btn' name='update_dining'>Update</button>
                    <a class='delete-btn' href='?delete_dining={$d['id']}' onclick='return confirm(\"Delete?\")'>Delete</a>
                  </td>
                  </form>
                </tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ----------------- TRANSPORT ----------------- -->
      <div id="transport" class="section">
        <div class="section-header">
          <div class="section-title">Transport Management</div>
          <button class="add-btn" onclick="openModal('addTransportModal')">
            <i class="fas fa-plus"></i>
            Add Transport
          </button>
        </div>
        
        <div id="addTransportModal" class="modal">
          <div class="modal-content">
            <span class="close" onclick="closeModal('addTransportModal')">&times;</span>
            <div class="modal-title">Add New Transport</div>
            <form method="POST">
              <input type="text" name="name" placeholder="Name" required>
              <input type="number" step="0.01" name="price_per_person" placeholder="Price (per person)" required>
              <button class="submit-btn" name="add_transport">Add Transport</button>
            </form>
          </div>
        </div>

        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Price</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $t = $conn->query("SELECT * FROM transport");
              while($tr = $t->fetch_assoc()){
                echo "<tr>
                  <form method='POST'>
                  <td>{$tr['id']}<input type='hidden' name='id' value='{$tr['id']}'></td>
                  <td><input name='name' value='{$tr['name']}'></td>
                  <td><input name='price_per_person' value='{$tr['price_per_person']}'></td>
                  <td class='action-buttons'>
                    <button class='update-btn' name='update_transport'>Update</button>
                    <a class='delete-btn' href='?delete_transport={$tr['id']}' onclick='return confirm(\"Delete?\")'>Delete</a>
                  </td>
                  </form>
                </tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ----------------- ACTIVITIES ----------------- -->
      <div id="activities" class="section">
        <div class="section-header">
          <div class="section-title">Activities Management</div>
          <button class="add-btn" onclick="openModal('addActivityModal')">
            <i class="fas fa-plus"></i>
            Add Activity
          </button>
        </div>
        
        <div id="addActivityModal" class="modal">
          <div class="modal-content">
            <span class="close" onclick="closeModal('addActivityModal')">&times;</span>
            <div class="modal-title">Add New Activity</div>
            <form method="POST">
              <input type="text" name="name" placeholder="Name" required>
              <input type="number" step="0.01" name="price_per_person" placeholder="Price (per person)" required>
              <button class="submit-btn" name="add_activity">Add Activity</button>
            </form>
          </div>
        </div>

        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Price</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $a = $conn->query("SELECT * FROM activities");
              while($act = $a->fetch_assoc()){
                echo "<tr>
                  <form method='POST'>
                  <td>{$act['id']}<input type='hidden' name='id' value='{$act['id']}'></td>
                  <td><input name='name' value='{$act['name']}'></td>
                  <td><input name='price_per_person' value='{$act['price_per_person']}'></td>
                  <td class='action-buttons'>
                    <button class='update-btn' name='update_activity'>Update</button>
                    <a class='delete-btn' href='?delete_activity={$act['id']}' onclick='return confirm(\"Delete?\")'>Delete</a>
                  </td>
                  </form>
                </tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ----------------- CUSTOMERS ----------------- -->
      <div id="customers" class="section">
        <div class="section-header">
          <div class="section-title">Customers Management</div>
          <button class="add-btn" onclick="openModal('addUserModal')">
            <i class="fas fa-plus"></i>
            Add User
          </button>
        </div>
        
        <div id="addUserModal" class="modal">
          <div class="modal-content">
            <span class="close" onclick="closeModal('addUserModal')">&times;</span>
            <div class="modal-title">Add New User</div>
            <form method="POST">
              <input type="text" name="name" placeholder="Full Name" required>
              <input type="email" name="email" placeholder="Email" required>
              <input type="password" name="password" placeholder="Password" required>
              <button class="submit-btn" name="add_user">Add User</button>
            </form>
          </div>
        </div>

        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Contact No</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $users = $conn->query("SELECT u.id, u.name, u.email, b.contact_no 
                                   FROM users u 
                                   LEFT JOIN bookings b ON u.id = b.user_id 
                                   WHERE u.role = 'user' 
                                   GROUP BY u.id");
              while($user = $users->fetch_assoc()){
                $contact_no = $user['contact_no'] ? $user['contact_no'] : 'Not Available';
                echo "<tr>
                  <form method='POST'>
                  <td>{$user['id']}<input type='hidden' name='id' value='{$user['id']}'></td>
                  <td><input name='name' value='{$user['name']}'></td>
                  <td><input name='email' value='{$user['email']}'></td>
                  <td>{$contact_no}</td>
                  <td class='action-buttons'>
                    <button class='update-btn' name='update_user'>Update</button>
                    <a class='delete-btn' href='?delete_user={$user['id']}' onclick='return confirm(\"Delete this user?\")'>Delete</a>
                  </td>
                  </form>
                </tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ----------------- PAYMENTS ----------------- -->
      <div id="payments" class="section">
        <div class="section-header">
          <div class="section-title">Payments Management</div>
          <button class="add-btn" onclick="openModal('addPaymentModal')">
            <i class="fas fa-plus"></i>
            Add Payment
          </button>
        </div>

        <!-- Payment Analytics -->
        <div class="payment-analytics">
          <div class="analytics-card">
            <h3>TOTAL REVENUE</h3>
            <div class="value">₹<?php echo number_format($total_revenue, 2); ?></div>
            <div class="label">All Time</div>
          </div>
          <div class="analytics-card" style="background: linear-gradient(135deg, #10b981, #059669);">
            <h3>COMPLETED</h3>
            <div class="value"><?php echo $completed_payments; ?></div>
            <div class="label">Fully Paid</div>
          </div>
          <div class="analytics-card" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
            <h3>HALF PAYMENTS</h3>
            <div class="value"><?php echo $half_payments; ?></div>
            <div class="label">Partial Payments</div>
          </div>
          <div class="analytics-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
            <h3>TOTAL TRANSACTIONS</h3>
            <div class="value"><?php echo $total_transactions; ?></div>
            <div class="label">All Bookings</div>
          </div>
        </div>

        <!-- Search Bar -->
        <div class="search-container">
          <div class="search-box">
            <input type="text" id="searchInput" class="search-input" placeholder="Search by user name, email, amount, transaction ID..." onkeypress="if(event.key === 'Enter') searchPayments()">
            <button class="search-btn" onclick="searchPayments()">
              <i class="fas fa-search"></i> Search
            </button>
          </div>
        </div>
        
        <div id="addPaymentModal" class="modal">
          <div class="modal-content">
            <span class="close" onclick="closeModal('addPaymentModal')">&times;</span>
            <div class="modal-title">Add New Payment</div>
            <form method="POST">
              <select name="cust_id" required>
                <option value="">Select Customer</option>
                <?php 
                $custs = $conn->query("SELECT * FROM users WHERE role='user'"); 
                while($cu = $custs->fetch_assoc()){ 
                  echo "<option value='{$cu['id']}'>{$cu['name']}</option>"; 
                } 
                ?>
              </select>
              <input type="number" step="0.01" name="amount" placeholder="Amount" required>
              <input type="date" name="pay_date" required>
              <button class="submit-btn" name="add_payment">Add Payment</button>
            </form>
          </div>
        </div>

        <div class="table-container">
          <table id="paymentsTable">
            <thead>
              <tr>
                <th>ID</th>
                <th>Transaction No</th>
                <th>User</th>
                <th>Email</th>
                <th>Contact</th>
                <th>Days</th>
                <th>Total</th>
                <th>Final</th>
                <th>Paid</th>
                <th>Due</th>
                <th>Method</th>
                <th>Type</th>
                <th>Payment Date</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $bookings = $conn->query("SELECT * FROM bookings ORDER BY booking_date DESC");
              while($booking = $bookings->fetch_assoc()){
                // Use actual transaction_id from database
                $transaction_no = $booking['transaction_id'] ? $booking['transaction_id'] : 'N/A';
                // Use payment_date from database
                $payment_date = $booking['payment_date'] ? date('M j, Y', strtotime($booking['payment_date'])) : 'N/A';
                echo "<tr>
                  <form method='POST'>
                  <td>{$booking['id']}<input type='hidden' name='id' value='{$booking['id']}'></td>
                  <td><strong>{$transaction_no}</strong></td>
                  <td>{$booking['user_name']}</td>
                  <td>{$booking['user_email']}</td>
                  <td>{$booking['contact_no']}</td>
                  <td>{$booking['stay_days']}</td>
                  <td>₹{$booking['package_total']}</td>
                  <td>₹{$booking['final_amount']}</td>
                  <td>
                    <input type='number' step='0.01' name='amount_paid' value='{$booking['amount_paid']}' style='width: 70px; font-size: 0.8rem; padding: 4px;'>
                  </td>
                  <td>₹{$booking['remaining_amount']}</td>
                  <td>{$booking['payment_method']}</td>
                  <td>{$booking['payment_type']}</td>
                  <td>{$payment_date}</td>
                  <td class='action-buttons'>
                    <button class='update-btn' name='update_booking_payment'>Update</button>
                    <a class='delete-btn' href='?delete_payment={$booking['id']}' onclick='return confirm(\"Delete this payment record?\")'>Delete</a>
                  </td>
                  </form>
                </tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>

<!-- ----------------- SCHEDULES ----------------- -->
<div id="schedules" class="section">
    <div class="section-header">
        <div class="section-title">Booking Schedules</div>
        <div class="date-filter">
            <input type="date" id="scheduleDateFilter" onchange="filterSchedulesByDate()">
            <button class="search-btn" onclick="clearDateFilter()">
                <i class="fas fa-times"></i> Clear
            </button>
        </div>
    </div>

    <div class="table-container">
        <table id="schedulesTable">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>User Name</th>
                    <th>User Email</th>
                    <th>Adults</th>
                    <th>Children</th>
                    <th>Contact No</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Stay Days</th>
                    <th>Room Name</th>
                    <th>Booking Date</th>
                    <th>Booking Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $schedules = $conn->query("SELECT 
                    user_id,
                    user_name, 
                    user_email,
                    adults,
                    children,
                    contact_no,
                    check_in,
                    check_out,
                    stay_days,
                    package_items,
                    booking_date,
                    booking_status
                    FROM bookings ORDER BY check_in ASC");
                while($schedule = $schedules->fetch_assoc()){
                    $status_class = '';
                    switch($schedule['booking_status']){
                        case 'confirmed': $status_class = 'status-completed'; break;
                        case 'pending': $status_class = 'status-pending'; break;
                        case 'cancelled': $status_class = 'status-cancelled'; break;
                        default: $status_class = 'status-pending';
                    }
                    
                    // Extract room name from package_items
                    $room_name = 'N/A';
                    if (!empty($schedule['package_items'])) {
                        // Look for name field in package_items
                        if (preg_match('/"name":"([^"]+)"/', $schedule['package_items'], $matches)) {
                            $room_name = $matches[1];
                        }
                        // Alternative pattern for name field
                        elseif (preg_match("/name:'([^']+)'/", $schedule['package_items'], $matches)) {
                            $room_name = $matches[1];
                        }
                        // Another pattern for name field
                        elseif (preg_match('/name:([^,\]]+)/', $schedule['package_items'], $matches)) {
                            $room_name = trim($matches[1]);
                        }
                    }
                    
                    echo "<tr data-checkin='{$schedule['check_in']}'>
                        <td>{$schedule['user_id']}</td>
                        <td>{$schedule['user_name']}</td>
                        <td>{$schedule['user_email']}</td>
                        <td>{$schedule['adults']}</td>
                        <td>{$schedule['children']}</td>
                        <td>{$schedule['contact_no']}</td>
                        <td>".date('M j, Y', strtotime($schedule['check_in']))."</td>
                        <td>".date('M j, Y', strtotime($schedule['check_out']))."</td>
                        <td>{$schedule['stay_days']}</td>
                        <td>{$room_name}</td>
                        <td>".date('M j, Y', strtotime($schedule['booking_date']))."</td>
                        <td><span class='status-badge {$status_class}'>{$schedule['booking_status']}</span></td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
    </div>
  </div>
</div>

</body>
</html>