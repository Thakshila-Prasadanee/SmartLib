<?php
    // Start session only if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }


    $host = 'localhost';
    $username = 'root';
    $password= '';
    $db_name = 'smartlib';

    // Recreate connection for this page
    $conn = mysqli_connect($host, $username, $password, $db_name);

    // Handle connection errors 
    if (!$conn) {
        // Log the error
        error_log("Database connection failed: " . mysqli_connect_error());
        
        // Show a user-friendly error message instead of technical details
        die("
        <div style='
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        '>
            <div style='
                text-align: center; 
                padding: 40px; 
                background: white; 
                border-radius: 20px; 
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                max-width: 500px;
                margin: 20px;
            '>
                <div style='color: #dc3545; font-size: 3rem; margin-bottom: 20px;'>⚠️</div>
                <h2 style='color: #495057; margin-bottom: 15px;'>Service Temporarily Unavailable</h2>
                <p style='color: #6c757d; margin-bottom: 25px;'>
                    We're experiencing technical difficulties. Please try again in a few moments.
                </p>
                <button onclick='window.location.reload()' style='
                    background: #109994; 
                    color: white; 
                    border: none; 
                    padding: 12px 24px; 
                    border-radius: 25px; 
                    cursor: pointer; 
                    font-size: 1rem;
                    transition: background 0.3s ease;
                ' onmouseover='this.style.background=\"#0d7377\"' onmouseout='this.style.background=\"#109994\"'>
                    Try Again
                </button>
            </div>
        </div>");
    }

    // Set charset to prevent character encoding issues
    mysqli_set_charset($conn, "utf8");

    // Handle delete action
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $record_id = (int)$_GET['delete'];
        
        // Get book_id before deleting to update book status
        $get_book_query = "SELECT book_id FROM borrow_records WHERE record_id = ?";
        $stmt = mysqli_prepare($conn, $get_book_query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $record_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $book_data = mysqli_fetch_assoc($result);
            
            if ($book_data) {
                // Delete the borrow record
                $delete_query = "DELETE FROM borrow_records WHERE record_id = ?";
                $delete_stmt = mysqli_prepare($conn, $delete_query);
                
                if ($delete_stmt) {
                    mysqli_stmt_bind_param($delete_stmt, "i", $record_id);
                    
                    if (mysqli_stmt_execute($delete_stmt)) {
                        // Update book status to available (if needed)
                        $update_book_query = "UPDATE books SET status = 'available' WHERE book_id = ? AND status = 'borrowed'";
                        $update_stmt = mysqli_prepare($conn, $update_book_query);
                        
                        if ($update_stmt) {
                            mysqli_stmt_bind_param($update_stmt, "i", $book_data['book_id']);
                            mysqli_stmt_execute($update_stmt);
                            mysqli_stmt_close($update_stmt);
                        }
                        
                        $_SESSION['success_message'] = "Borrow record deleted successfully!";
                    } else {
                        $_SESSION['error_message'] = "Error deleting record: " . mysqli_error($conn);
                    }
                    
                    mysqli_stmt_close($delete_stmt);
                } else {
                    $_SESSION['error_message'] = "Error preparing delete statement: " . mysqli_error($conn);
                }
            } else {
                $_SESSION['error_message'] = "Record not found!";
            }
            
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['error_message'] = "Error preparing query: " . mysqli_error($conn);
        }
        
        header("Location: borrow_management.php");
        exit();
    }

    // Search functionality with improved security
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

    $search_condition = '';
    $search_params = [];

    if (!empty($search)) {
        $search_condition = "WHERE (br.record_id LIKE ? OR u.name LIKE ? OR b.title LIKE ? OR b.author LIKE ?)";
        $search_term = "%$search%";
        $search_params = [$search_term, $search_term, $search_term, $search_term];
    }

    if (!empty($status_filter)) {
        if (!empty($search_condition)) {
            $search_condition .= " AND br.status = ?";
        } else {
            $search_condition = "WHERE br.status = ?";
        }
        $search_params[] = $status_filter;
    }

    // Pagination
    $limit = 6;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $start = ($page - 1) * $limit;

    // Fetch borrow records
    $borrow_query = "
        SELECT 
            br.record_id,
            br.user_id,
            br.book_id,
            br.borrow_date,
            br.return_date,
            br.status,
            COALESCE(b.title, 'Unknown Book') as book_title,
            COALESCE(b.author, 'Unknown Author') as book_author,
            COALESCE(b.isbn, 'N/A') as book_isbn,
            COALESCE(u.name, 'Unknown User') as user_name,
            COALESCE(u.email, 'No Email') as user_email
        FROM borrow_records br
        LEFT JOIN books b ON br.book_id = b.book_id
        LEFT JOIN users u ON br.user_id = u.user_id
        $search_condition
        ORDER BY br.borrow_date DESC
        LIMIT $start, $limit
    ";

    if (!empty($search_params)) {
        $stmt = mysqli_prepare($conn, $borrow_query);
        if ($stmt) {
            $types = str_repeat('s', count($search_params));
            mysqli_stmt_bind_param($stmt, $types, ...$search_params);
            if (mysqli_stmt_execute($stmt)) {
                $borrow_result = mysqli_stmt_get_result($stmt);
            } else {
                error_log("Failed to execute borrow statement: " . mysqli_stmt_error($stmt));
                $borrow_result = false;
            }
            mysqli_stmt_close($stmt);
        } else {
            error_log("Failed to prepare borrow query: " . mysqli_error($conn));
            $borrow_result = false;
        }
    } else {
        $borrow_result = mysqli_query($conn, $borrow_query);
    }

    if (!$borrow_result) {
        error_log("Borrow query failed: " . mysqli_error($conn));
        // Try a simple fallback query to see if there are any records at all
        $simple_query = "SELECT 
            record_id, user_id, book_id, borrow_date, return_date, status,
            'Unknown Book' as book_title, 'Unknown Author' as book_author, 'N/A' as book_isbn,
            'Unknown User' as user_name, 'No Email' as user_email
            FROM borrow_records 
            ORDER BY borrow_date DESC 
            LIMIT $start, $limit";
        $borrow_result = mysqli_query($conn, $simple_query);
        
        if (!$borrow_result) {
            error_log("Simple borrow query also failed: " . mysqli_error($conn));
            $borrow_result = false;
        }
    }

    // Debug: Add some debugging information
    $debug_count_query = "SELECT COUNT(*) as total FROM borrow_records";
    $debug_result = mysqli_query($conn, $debug_count_query);
    if ($debug_result) {
        $debug_count = mysqli_fetch_assoc($debug_result)['total'];
        error_log("Total borrow records in database: " . $debug_count);
        mysqli_free_result($debug_result);
    }

    // Count total borrow records for pagination
    $total_query = "
        SELECT COUNT(*) as total 
        FROM borrow_records br
        LEFT JOIN books b ON br.book_id = b.book_id
        LEFT JOIN users u ON br.user_id = u.user_id
        $search_condition
    ";

    $total_borrows = 0;
    if (!empty($search_params)) {
        $stmt = mysqli_prepare($conn, $total_query);
        if ($stmt) {
            $types = str_repeat('s', count($search_params));
            mysqli_stmt_bind_param($stmt, $types, ...$search_params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                $total_borrows = mysqli_fetch_assoc($result)['total'];
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $total_result = mysqli_query($conn, $total_query);
        if ($total_result) {
            $total_borrows = mysqli_fetch_assoc($total_result)['total'];
        }
    }

    $total_pages = ceil($total_borrows / $limit);

    // ✅ Get status options for filter dropdown - check actual database values
    $status_options = [];
    
    // Get actual status values from database
    $status_query = "SELECT DISTINCT status FROM borrow_records WHERE status IS NOT NULL AND status != '' ORDER BY status";
    $status_result = mysqli_query($conn, $status_query);
    if ($status_result) {
        while ($row = mysqli_fetch_assoc($status_result)) {
            $status_value = $row['status'];
            $status_label = ucfirst($status_value); // Capitalize first letter
            $status_options[$status_value] = $status_label;
        }
    }
    
    // If no statuses found, use default ones
    if (empty($status_options)) {
        $status_options = [
            'borrowed' => 'Borrowed',
            'returned' => 'Returned', 
            'overdue' => 'Overdue'
        ];
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Borrow Management - SmartLib</title>

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
        <style>
            body { 
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            
            .dashboard-container { 
                display: flex; 
                min-height: 100vh; 
            }
            
            .sidebar { 
                background: #109994; 
                width: 240px; 
                color: #000; 
                padding: 10px; 
                position: fixed; 
                top: 0; 
                bottom: 0; 
            }
            
            .sidebar a { 
                display: flex; 
                align-items: center; 
                gap: 8px; 
                text-decoration: none; 
                color: #000; 
                margin: 8px 0; 
                padding: 8px; 
                border-radius: 6px; 
                font-weight: 500; 
            }
            
            .sidebar a:hover, .sidebar a.active { 
                background: rgba(255, 255, 255, 0.7); 
            }
            
            .top-header { 
                background: #084D4A; 
                color: #fff; 
                padding: 12px 20px; 
                position: fixed; 
                left: 240px; 
                right: 0; 
                top: 0; 
                height: 60px; 
                display: flex; 
                justify-content: space-between; 
                align-items: center; 
            }
            
            .main-content { 
                margin-left: 240px; 
                padding: 100px 30px 30px; 
                width: calc(100% - 240px); 
                min-height: 100vh;
            }
            
            /* Enhanced Main Content Styles */
            .page-header {
                color: black;
                margin-bottom: 30px;
            }
            
            .page-header h2 {
                margin: 0;
                font-weight: 700;
                font-size: 2rem;
            }
            
            .page-header p {
                margin: 10px 0 0;
                opacity: 0.9;
                font-size: 1.1rem;
            }
            
            .content-card {
                background: white;
                border-radius: 20px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.08);
                overflow: hidden;
                margin-bottom: 30px;
            }
            
            .card-header-custom {
                background: linear-gradient(135deg, #109994 0%, #0d7377 100%);
                color: white;
                padding: 25px 30px;
                border: none;
            }
            
            .card-header-custom h4 {
                margin: 0;
                font-weight: 600;
                font-size: 1.3rem;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .search-add-section {
                padding: 30px;
                background: #f8f9fa;
                border-bottom: 1px solid #e9ecef;
            }
            
            .search-filters {
                display: flex;
                gap: 15px;
                align-items: end;
                flex-wrap: wrap;
            }
            
            .search-box {
                position: relative;
                flex: 1;
                min-width: 300px;
            }
            
            .search-box input {
                padding-left: 45px;
                border: 2px solid #e9ecef;
                border-radius: 10px;
                font-size: 0.95rem;
                height: 45px;
                transition: all 0.3s ease;
            }
            
            .search-box input:focus {
                border-color: #109994;
                box-shadow: 0 0 0 0.2rem rgba(16, 153, 148, 0.25);
            }
            
            .search-box i {
                position: absolute;
                left: 15px;
                top: 50%;
                transform: translateY(-50%);
                color: #6c757d;
                font-size: 1rem;
            }
            
            .filter-select {
                min-width: 180px;
                border: 2px solid #e9ecef;
                border-radius: 10px;
                height: 45px;
                font-size: 0.95rem;
            }
            
            .filter-select:focus {
                border-color: #109994;
                box-shadow: 0 0 0 0.2rem rgba(16, 153, 148, 0.25);
            }
            
            .btn-add-new {
                background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                color: white;
                border: none;
                border-radius: 10px;
                padding: 12px 25px;
                font-weight: 600;
                text-decoration: none;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                gap: 8px;
                height: 45px;
            }
            
            .btn-add-new:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
                color: white;
            }
            
            .table-container {
                padding: 30px;
            }
            
            .modern-table {
                margin: 0;
                border: none;
            }
            
            .modern-table thead th {
                background: #109994;
                color: white;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.85rem;
                letter-spacing: 0.5px;
                padding: 18px 15px;
                border: none;
            }
            
            .modern-table tbody tr {
                border-bottom: 1px solid #f1f3f4;
                transition: all 0.2s ease;
            }
            
            .modern-table tbody tr:hover {
                background: #f8f9fa;
                transform: scale(1.01);
            }
            
            .modern-table tbody td {
                padding: 18px 15px;
                vertical-align: middle;
                border: none;
            }
            
            .record-details {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }
            
            .record-title {
                font-weight: 600;
                color: #2c3e50;
                font-size: 0.95rem;
            }
            
            .record-subtitle {
                color: #6c757d;
                font-size: 0.85rem;
            }
            
            .badge-status {
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 0.8rem;
                font-weight: 500;
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }
            
            .badge-borrowed {
                background: #fff3cd;
                color: #856404;
            }
            
            .badge-returned {
                background: #d1ecf1;
                color: #0c5460;
            }
            
            .badge-overdue {
                background: #f8d7da;
                color: #721c24;
            }
            
            .action-buttons {
                display: flex;
                gap: 8px;
                justify-content: center;
            }
            
            .btn-action {
                width: 35px;
                height: 35px;
                border-radius: 8px;
                border: none;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
                font-size: 0.9rem;
            }
            
            .btn-danger {
                background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
                color: white;
            }
            
            .btn-danger:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
            }
            
            .view-toggle {
                display: flex;
                gap: 10px;
                margin-bottom: 20px;
            }
            
            .view-toggle .btn {
                padding: 8px 16px;
                border-radius: 20px;
                font-size: 0.9rem;
                font-weight: 500;
                transition: all 0.3s ease;
            }
            
            .view-toggle .btn.active {
                background: #109994;
                color: white;
                border-color: #109994;
            }
            
            /* Enhanced Pagination */
            .pagination-container {
                padding: 30px;
                background: #f8f9fa;
                border-top: 1px solid #e9ecef;
            }
            
            .pagination .page-link {
                border: none;
                padding: 12px 18px;
                margin: 0 3px;
                border-radius: 10px;
                color: #109994;
                font-weight: 500;
                transition: all 0.3s ease;
            }
            
            .pagination .page-item.active .page-link {
                background: linear-gradient(135deg, #109994 0%, #0d7377 100%);
                color: white;
                box-shadow: 0 4px 15px rgba(16, 153, 148, 0.3);
            }
            
            .pagination .page-link:hover {
                background: #109994;
                color: white;
                transform: translateY(-2px);
            }
            
            .pagination .page-item.disabled .page-link {
                color: #adb5bd;
                background: #f8f9fa;
            }
            
            .empty-state {
                text-align: center;
                padding: 60px 30px;
                color: #6c757d;
            }
            
            .empty-state i {
                font-size: 4rem;
                margin-bottom: 20px;
                opacity: 0.5;
            }
            
            .empty-state h5 {
                margin-bottom: 10px;
                color: #495057;
            }
            
            .empty-state p {
                margin-bottom: 25px;
                font-size: 0.95rem;
            }
            
            .record-card {
                background: white;
                border-radius: 15px;
                box-shadow: 0 8px 25px rgba(0,0,0,0.08);
                overflow: hidden;
                transition: all 0.3s ease;
                border: 1px solid #f1f3f4;
            }
            
            .record-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 35px rgba(0,0,0,0.12);
            }
            
            .record-card-header {
                padding: 20px;
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                border-bottom: 1px solid #e9ecef;
            }
            
            .record-card-body {
                padding: 20px;
            }
            
            .record-card-footer {
                padding: 15px 20px;
                background: #f8f9fa;
                border-top: 1px solid #e9ecef;
            }
            
            .record-card-title {
                font-size: 1.1rem;
                font-weight: 600;
                color: #2c3e50;
                margin-bottom: 8px;
                line-height: 1.3;
            }
            
            .record-card-subtitle {
                color: #6c757d;
                font-style: italic;
                font-size: 0.9rem;
                margin-bottom: 15px;
            }
            
            .record-info-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 10px;
                font-size: 0.9rem;
            }
            
            .record-info-label {
                color: #6c757d;
                font-weight: 500;
            }
            
            .record-info-value {
                color: #495057;
                font-weight: 400;
            }
            
            /* Alert Styles */
            .alert {
                border: none;
                border-radius: 10px;
                padding: 15px 20px;
                margin-bottom: 25px;
                font-weight: 500;
            }
            
            .alert-success {
                background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
                color: #155724;
                border-left: 4px solid #28a745;
            }
            
            .alert-danger {
                background: linear-gradient(135deg, #f8d7da 0%, #f1c2c7 100%);
                color: #721c24;
                border-left: 4px solid #dc3545;
            }
            
            /* Responsive Design */
            @media (max-width: 768px) {
                .sidebar {
                    width: 60px;
                }
                
                .sidebar a span {
                    display: none;
                }
                
                .top-header {
                    left: 60px;
                }
                
                .main-content {
                    margin-left: 60px;
                    padding: 80px 15px 15px;
                }
                
                .search-filters {
                    flex-direction: column;
                    gap: 10px;
                }
                
                .search-box {
                    min-width: auto;
                }
                
                .record-card-header, .record-card-body, .record-card-footer {
                    padding: 15px;
                }
                
                .table-container {
                    padding: 15px;
                    overflow-x: auto;
                }
                
                .modern-table {
                    min-width: 800px;
                }
            }
        </style>
    </head>
    <body>

        <div class="dashboard-container">
            <?php include '../includes/admin/sidebar.php'; ?>

            <?php include '../includes/admin/header.php'; ?>

            <!-- Enhanced Main Content -->
            <div class="main-content">
                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php 
                            echo htmlspecialchars($_SESSION['success_message']); 
                            unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php 
                            echo htmlspecialchars($_SESSION['error_message']); 
                            unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Page Header -->
                <div class="page-header">
                    <h2><i class="bi bi-arrow-left-right"></i> Borrow Management</h2>
                    <p>Manage library borrowing records with ease and efficiency</p>
                </div>

                <!-- Main Content Card -->
                <div class="content-card">
                    <div class="card-header-custom">
                        <h4><i class="bi bi-journal-bookmark"></i> Borrow Records</h4>
                    </div>

                    <!-- Enhanced Search and Filter Section -->
                    <div class="search-add-section">
                        <form method="GET" class="search-filters mb-3">
                            <div class="search-box">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search by record ID, user name, book title, or author..." 
                                       value="<?= htmlspecialchars($search) ?>">
                                <i class="bi bi-search"></i>
                            </div>
                            <select name="status" class="form-select filter-select">
                                <option value="">All Status</option>
                                <?php foreach ($status_options as $value => $label) { ?>
                                    <option value="<?= $value ?>" 
                                            <?= ($status_filter == $value) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                            <?php if (!empty($search) || !empty($status_filter)) { ?>
                                <a href="borrow_management.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Clear
                                </a>
                            <?php } ?>
                        </form>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="view-toggle">
                                <button type="button" class="btn btn-outline-secondary active" id="tableView">
                                    <i class="bi bi-table me-1"></i> Table View
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="cardView">
                                    <i class="bi bi-grid me-1"></i> Card View
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Enhanced Table Container with View Toggle -->
                    <div class="table-container" id="tableContainer">
                        <!-- Debug Information (remove in production) -->
                        <?php if (isset($_GET['debug'])) { ?>
                            <div class="alert alert-info">
                                <strong>Debug Info:</strong><br>
                                Search: "<?= htmlspecialchars($search) ?>"<br>
                                Status Filter: "<?= htmlspecialchars($status_filter) ?>"<br>
                                Search Condition: "<?= htmlspecialchars($search_condition) ?>"<br>
                                Search Params Count: <?= count($search_params) ?><br>
                                Borrow Result Type: <?= gettype($borrow_result) ?><br>
                                <?php if ($borrow_result && is_object($borrow_result)) { ?>
                                    Row Count: <?= mysqli_num_rows($borrow_result) ?><br>
                                <?php } else { ?>
                                    Borrow Result is FALSE or not an object<br>
                                <?php } ?>
                                Query: <?= htmlspecialchars($borrow_query) ?><br>
                            </div>
                        <?php } ?>
                        
                        <!-- Always show record count for debugging -->
                        <div class="alert alert-secondary">
                            <small>
                                <?php if ($borrow_result && mysqli_num_rows($borrow_result) > 0) { ?>
                                    Found <?= mysqli_num_rows($borrow_result) ?> records
                                <?php } else { ?>
                                    No records found or query failed
                                <?php } ?>
                            </small>
                        </div>
                        
                        <?php if ($borrow_result && mysqli_num_rows($borrow_result) > 0) { ?>
                            <!-- Table View -->
                            <div id="tableViewContent">
                                <table class="table modern-table">
                                    <thead>
                                        <tr>
                                            <th><i class="bi bi-hash me-1"></i>Record ID</th>
                                            <th><i class="bi bi-person me-1"></i>User Details</th>
                                            <th><i class="bi bi-book me-1"></i>Book Details</th>
                                            <th><i class="bi bi-calendar me-1"></i>Borrow Date</th>
                                            <th><i class="bi bi-calendar-check me-1"></i>Return Date</th>
                                            <th><i class="bi bi-clock me-1"></i>Days</th>
                                            <th><i class="bi bi-info-circle me-1"></i>Status</th>
                                            <th><i class="bi bi-gear me-1"></i>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        mysqli_data_seek($borrow_result, 0); // Reset result pointer
                                        while ($row = mysqli_fetch_assoc($borrow_result)) { 
                                            ?>
                                            <tr>
                                                <td>
                                                    <strong>#<?= htmlspecialchars($row['record_id']) ?></strong>
                                                </td>
                                                <td>
                                                    <div class="record-details">
                                                        <span class="record-title"><?= htmlspecialchars($row['user_name']) ?></span>
                                                        <span class="record-subtitle">ID: <?= htmlspecialchars($row['user_id']) ?></span>
                                                        <span class="record-subtitle"><?= htmlspecialchars($row['user_email']) ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="record-details">
                                                        <span class="record-title"><?= htmlspecialchars($row['book_title']) ?></span>
                                                        <span class="record-subtitle">by <?= htmlspecialchars($row['book_author']) ?></span>
                                                        <span class="record-subtitle">ISBN: <?= htmlspecialchars($row['book_isbn']) ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <i class="bi bi-calendar-date text-muted me-1"></i>
                                                    <?= date('M d, Y', strtotime($row['borrow_date'])) ?>
                                                </td>
                                                <td>
                                                    <?php if ($row['return_date']): ?>
                                                        <i class="bi bi-calendar-check text-success me-1"></i>
                                                        <?= date('M d, Y', strtotime($row['return_date'])) ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">
                                                            <i class="bi bi-clock me-1"></i>
                                                            Not returned
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $borrow_date = new DateTime($row['borrow_date']);
                                                    $current_date = new DateTime();
                                                    $days_borrowed = $current_date->diff($borrow_date)->days;
                                                    ?>
                                                    <span class="badge bg-secondary">
                                                        <?= $days_borrowed ?> days
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status = strtolower($row['status']);
                                                    $badge_class = '';
                                                    $icon = '';
                                                    
                                                    switch($status) {
                                                        case 'borrowed':
                                                            $badge_class = 'badge-borrowed';
                                                            $icon = 'bi-book-half';
                                                            break;
                                                        case 'returned':
                                                            $badge_class = 'badge-returned';
                                                            $icon = 'bi-check-circle';
                                                            break;
                                                        case 'overdue':
                                                            $badge_class = 'badge-overdue';
                                                            $icon = 'bi-exclamation-triangle';
                                                            break;
                                                        default:
                                                            $badge_class = 'badge-borrowed';
                                                            $icon = 'bi-question-circle';
                                                    }
                                                    ?>
                                                    <span class="badge-status <?= $badge_class ?>">
                                                        <i class="bi <?= $icon ?>"></i>
                                                        <?= ucfirst($status) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button type="button" class="btn btn-danger btn-action" 
                                                                onclick="confirmDelete(<?= $row['record_id'] ?>)"
                                                                title="Delete Record">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Card View -->
                            <div id="cardViewContent" style="display: none;">
                                <div class="row">
                                    <?php 
                                    mysqli_data_seek($borrow_result, 0); // Reset result pointer for card view
                                    while ($row = mysqli_fetch_assoc($borrow_result)) { 
                                        $status = strtolower($row['status']);
                                        $badge_class = '';
                                        $icon = '';
                                        
                                        switch($status) {
                                            case 'borrowed':
                                                $badge_class = 'badge-borrowed';
                                                $icon = 'bi-book-half';
                                                break;
                                            case 'returned':
                                                $badge_class = 'badge-returned';
                                                $icon = 'bi-check-circle';
                                                break;
                                            case 'overdue':
                                                $badge_class = 'badge-overdue';
                                                $icon = 'bi-exclamation-triangle';
                                                break;
                                            default:
                                                $badge_class = 'badge-borrowed';
                                                $icon = 'bi-question-circle';
                                        }
                                        ?>
                                        <div class="col-lg-6 mb-4">
                                            <div class="record-card">
                                                <div class="record-card-header">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <div class="record-card-title">Record #<?= htmlspecialchars($row['record_id']) ?></div>
                                                            <div class="record-card-subtitle"><?= htmlspecialchars($row['user_name']) ?></div>
                                                        </div>
                                                        <span class="badge-status <?= $badge_class ?>">
                                                            <i class="bi <?= $icon ?>"></i>
                                                            <?= ucfirst($status) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="record-card-body">
                                                    <div class="record-info-row">
                                                        <span class="record-info-label">Book:</span>
                                                        <span class="record-info-value"><?= htmlspecialchars($row['book_title']) ?></span>
                                                    </div>
                                                    <div class="record-info-row">
                                                        <span class="record-info-label">Author:</span>
                                                        <span class="record-info-value"><?= htmlspecialchars($row['book_author']) ?></span>
                                                    </div>
                                                    <div class="record-info-row">
                                                        <span class="record-info-label">User Email:</span>
                                                        <span class="record-info-value"><?= htmlspecialchars($row['user_email']) ?></span>
                                                    </div>
                                                    <div class="record-info-row">
                                                        <span class="record-info-label">Borrow Date:</span>
                                                        <span class="record-info-value"><?= date('M d, Y', strtotime($row['borrow_date'])) ?></span>
                                                    </div>
                                                    <div class="record-info-row">
                                                        <span class="record-info-label">Return Date:</span>
                                                        <span class="record-info-value">
                                                            <?php if ($row['return_date']): ?>
                                                                <?= date('M d, Y', strtotime($row['return_date'])) ?>
                                                            <?php else: ?>
                                                                Not returned
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                    <div class="record-info-row">
                                                        <span class="record-info-label">Days Borrowed:</span>
                                                        <span class="record-info-value">
                                                            <?php 
                                                            $borrow_date = new DateTime($row['borrow_date']);
                                                            $current_date = new DateTime();
                                                            $days_borrowed = $current_date->diff($borrow_date)->days;
                                                            ?>
                                                            <?= $days_borrowed ?> days
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="record-card-footer">
                                                    <div class="action-buttons">
                                                        <button type="button" class="btn btn-danger btn-action" 
                                                                onclick="confirmDelete(<?= $row['record_id'] ?>)"
                                                                title="Delete Record">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } else { ?>
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <h5>No borrow records found</h5>
                                <?php if (!empty($search) || !empty($status_filter)) { ?>
                                    <p>Try adjusting your search criteria or filters.</p>
                                    <a href="borrow_management.php" class="btn btn-outline-primary">
                                        <i class="bi bi-arrow-clockwise me-2"></i>View All Records
                                    </a>
                                <?php } else { ?>
                                    <p>No borrow records have been created yet.</p>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>

                    <!-- Enhanced Pagination -->
                    <?php if ($total_pages > 1) { ?>
                    <div class="pagination-container">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="text-muted">
                                Showing <?= $start + 1 ?> to <?= min($start + $limit, $total_borrows) ?> of <?= number_format($total_borrows) ?> borrow records (6 per page)
                            </div>
                            <nav>
                                <ul class="pagination mb-0">
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                            <i class="bi bi-chevron-left"></i> Previous
                                        </a>
                                    </li>
                                    
                                    <?php 
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1) { ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                                        </li>
                                        <?php if ($start_page > 2) { ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php } ?>
                                    <?php }
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++) { ?>
                                        <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                        </li>
                                    <?php }
                                    
                                    if ($end_page < $total_pages) {
                                        if ($end_page < $total_pages - 1) { ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php } ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>"><?= $total_pages ?></a>
                                        </li>
                                    <?php } ?>
                                    
                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                            Next <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- Bootstrap JavaScript -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

        <script>
        // View toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tableViewBtn = document.getElementById('tableView');
            const cardViewBtn = document.getElementById('cardView');
            const tableContent = document.getElementById('tableViewContent');
            const cardContent = document.getElementById('cardViewContent');

            tableViewBtn.addEventListener('click', function() {
                tableViewBtn.classList.add('active');
                cardViewBtn.classList.remove('active');
                tableContent.style.display = 'block';
                cardContent.style.display = 'none';
                localStorage.setItem('borrowViewMode', 'table');
            });

            cardViewBtn.addEventListener('click', function() {
                cardViewBtn.classList.add('active');
                tableViewBtn.classList.remove('active');
                tableContent.style.display = 'none';
                cardContent.style.display = 'block';
                localStorage.setItem('borrowViewMode', 'card');
            });

            // Restore view mode from localStorage
            const savedViewMode = localStorage.getItem('borrowViewMode');
            if (savedViewMode === 'card') {
                cardViewBtn.click();
            }
        });

        // Confirm delete function
        function confirmDelete(recordId) {
            if (confirm('Are you sure you want to delete this borrow record? This action cannot be undone.')) {
                window.location.href = 'borrow_management.php?delete=' + recordId;
            }
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Add some utility functions for better UX
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                showTemporaryTooltip('Copied to clipboard!');
            });
        }

        function showTemporaryTooltip(message) {
            const tooltip = document.createElement('div');
            tooltip.className = 'position-fixed bg-dark text-white px-3 py-2 rounded';
            tooltip.style.cssText = `
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                z-index: 1060;
                font-size: 0.9rem;
            `;
            tooltip.textContent = message;
            
            document.body.appendChild(tooltip);
            
            setTimeout(() => {
                tooltip.remove();
            }, 2000);
        }

        // Add record ID copy functionality
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('td strong').forEach(strongElement => {
                if (strongElement.textContent.startsWith('#')) {
                    strongElement.style.cursor = 'pointer';
                    strongElement.title = 'Click to copy Record ID';
                    strongElement.addEventListener('click', function() {
                        copyToClipboard(this.textContent);
                    });
                }
            });
        });
        </script>

        <style>
            /* Additional styles for enhanced UX */
            mark {
                background-color: #fff3cd;
                padding: 1px 2px;
                border-radius: 2px;
            }

            /* Loading spinner styles */
            .spinner-border-sm {
                width: 1rem;
                height: 1rem;
            }

            /* Improved accessibility */
            .btn-action:focus {
                outline: 2px solid #109994;
                outline-offset: 2px;
            }

            .search-box input:focus + i {
                color: #109994;
            }

            /* Print styles */
            @media print {
                .sidebar, .top-header, .search-add-section, .pagination-container, .action-buttons {
                    display: none !important;
                }
                
                .main-content {
                    margin-left: 0;
                    padding: 20px;
                    width: 100%;
                }
                
                .modern-table {
                    font-size: 12px;
                }
                
                .page-header {
                    background: none !important;
                    color: black !important;
                    box-shadow: none !important;
                }
            }
        </style>
    </body>
</html>

<?php 
    // Clean up
    if (isset($borrow_result) && $borrow_result !== false) {
        mysqli_free_result($borrow_result);
    }
    if (isset($stats_result) && $stats_result !== false) {
        mysqli_free_result($stats_result);
    }
    if (isset($categories_result) && $categories_result !== false) {
        mysqli_free_result($categories_result);
    }
    mysqli_close($conn); 
?>