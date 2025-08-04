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
        $user_id = (int)$_GET['delete'];
        
        // Check if user has any active borrows
        $check_borrows_query = "SELECT COUNT(*) as count FROM borrow_records WHERE user_id = ? AND status = 'borrowed'";
        $stmt = mysqli_prepare($conn, $check_borrows_query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $borrow_count = mysqli_fetch_assoc($result)['count'];
            
            if ($borrow_count > 0) {
                $_SESSION['error_message'] = "Cannot delete user - they have {$borrow_count} active borrow(s). Please ensure all books are returned first.";
            } else {
                // Get user name before deleting
                $get_user_query = "SELECT name FROM users WHERE user_id = ?";
                $user_stmt = mysqli_prepare($conn, $get_user_query);
                
                if ($user_stmt) {
                    mysqli_stmt_bind_param($user_stmt, "i", $user_id);
                    mysqli_stmt_execute($user_stmt);
                    $user_result = mysqli_stmt_get_result($user_stmt);
                    $user_data = mysqli_fetch_assoc($user_result);
                    
                    if ($user_data) {
                        // Delete the user
                        $delete_query = "DELETE FROM users WHERE user_id = ?";
                        $delete_stmt = mysqli_prepare($conn, $delete_query);
                        
                        if ($delete_stmt) {
                            mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
                            
                            if (mysqli_stmt_execute($delete_stmt)) {
                                $_SESSION['success_message'] = "User '{$user_data['name']}' deleted successfully!";
                            } else {
                                $_SESSION['error_message'] = "Error deleting user: " . mysqli_error($conn);
                            }
                            
                            mysqli_stmt_close($delete_stmt);
                        } else {
                            $_SESSION['error_message'] = "Error preparing delete statement: " . mysqli_error($conn);
                        }
                    } else {
                        $_SESSION['error_message'] = "User not found!";
                    }
                    
                    mysqli_stmt_close($user_stmt);
                } else {
                    $_SESSION['error_message'] = "Error preparing query: " . mysqli_error($conn);
                }
            }
            
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['error_message'] = "Error preparing query: " . mysqli_error($conn);
        }
        
        header("Location: user_management.php");
        exit();
    }

    // Search functionality with improved security
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $role_filter = isset($_GET['role']) ? trim($_GET['role']) : '';

    $search_condition = '';
    $search_params = [];

    if (!empty($search)) {
        $search_condition = "WHERE (u.name LIKE ? OR u.email LIKE ? OR u.user_id LIKE ?)";
        $search_term = "%$search%";
        $search_params = [$search_term, $search_term, $search_term];
    }

    if (!empty($role_filter)) {
        if (!empty($search_condition)) {
            $search_condition .= " AND u.role = ?";
        } else {
            $search_condition = "WHERE u.role = ?";
        }
        $search_params[] = $role_filter;
    }

    // Pagination
    $limit = 6;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $start = ($page - 1) * $limit;

    // Fetch users with borrow statistics
    $user_query = "
        SELECT 
            u.user_id,
            u.name,
            u.email,
            u.role,
            u.created_at,
            COUNT(br.record_id) as total_borrows,
            COUNT(CASE WHEN br.status = 'borrowed' THEN 1 END) as active_borrows,
            COUNT(CASE WHEN br.status = 'returned' THEN 1 END) as returned_books,
            COUNT(CASE WHEN br.status = 'overdue' THEN 1 END) as overdue_books
        FROM users u
        LEFT JOIN borrow_records br ON u.user_id = br.user_id
        $search_condition
        GROUP BY u.user_id, u.name, u.email, u.role, u.created_at
        ORDER BY u.created_at DESC
        LIMIT $start, $limit
    ";

    if (!empty($search_params)) {
        $stmt = mysqli_prepare($conn, $user_query);
        if ($stmt) {
            $types = str_repeat('s', count($search_params));
            mysqli_stmt_bind_param($stmt, $types, ...$search_params);
            if (mysqli_stmt_execute($stmt)) {
                $user_result = mysqli_stmt_get_result($stmt);
            } else {
                error_log("Failed to execute user statement: " . mysqli_stmt_error($stmt));
                $user_result = false;
            }
            mysqli_stmt_close($stmt);
        } else {
            error_log("Failed to prepare user query: " . mysqli_error($conn));
            $user_result = false;
        }
    } else {
        $user_result = mysqli_query($conn, $user_query);
    }

    if (!$user_result) {
        error_log("User query failed: " . mysqli_error($conn));
        // Try a simple fallback query
        $simple_query = "SELECT 
            user_id, name, email, role, created_at,
            0 as total_borrows, 0 as active_borrows, 0 as returned_books, 0 as overdue_books
            FROM users 
            ORDER BY created_at DESC 
            LIMIT $start, $limit";
        $user_result = mysqli_query($conn, $simple_query);
        
        if (!$user_result) {
            error_log("Simple user query also failed: " . mysqli_error($conn));
            $user_result = false;
        }
    }

    // Count total users for pagination
    $total_query = "
        SELECT COUNT(*) as total 
        FROM users u
        $search_condition
    ";

    $total_users = 0;
    if (!empty($search_params)) {
        $stmt = mysqli_prepare($conn, $total_query);
        if ($stmt) {
            $types = str_repeat('s', count($search_params));
            mysqli_stmt_bind_param($stmt, $types, ...$search_params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                $total_users = mysqli_fetch_assoc($result)['total'];
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $total_result = mysqli_query($conn, $total_query);
        if ($total_result) {
            $total_users = mysqli_fetch_assoc($total_result)['total'];
        }
    }

    $total_pages = ceil($total_users / $limit);

    // Get role options for filter dropdown - check actual database values
    $role_options = [];
    
    // Get actual role values from database
    $role_query = "SELECT DISTINCT role FROM users WHERE role IS NOT NULL AND role != '' ORDER BY role";
    $role_result = mysqli_query($conn, $role_query);
    if ($role_result) {
        while ($row = mysqli_fetch_assoc($role_result)) {
            $role_value = $row['role'];
            $role_label = ucfirst($role_value); // Capitalize first letter
            $role_options[$role_value] = $role_label;
        }
    }
    
    // If no roles found, use default ones
    if (empty($role_options)) {
        $role_options = [
            'admin' => 'Admin',
            'user' => 'User'
        ];
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>User Management - SmartLib</title>

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Inter', sans-serif;
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                min-height: 100vh;
            }

            .main-content {
                padding-top: 50px;
                margin-left: 250px;
                min-height: 100vh;
                transition: margin-left 0.3s ease;
            }

            .top-header {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                padding: 20px 30px;
                border-bottom: 1px solid rgba(0, 0, 0, 0.1);
                position: sticky;
                top: 0;
                z-index: 100;
            }

            .page-title {
                color: #2c3e50;
                font-weight: 700;
                font-size: 1.8rem;
                margin: 0;
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .page-title i {
                color: #109994;
                font-size: 1.6rem;
            }

            .content-area {
                padding: 30px;
            }

            /* Search and Filter Section */
            .search-add-section {
                background: white;
                border-radius: 15px;
                padding: 25px;
                margin-bottom: 25px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            }

            .search-filters {
                display: flex;
                gap: 15px;
                align-items: center;
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
                font-size: 1.1rem;
            }

            .filter-select {
                border: 2px solid #e9ecef;
                border-radius: 10px;
                height: 45px;
                min-width: 150px;
                transition: all 0.3s ease;
            }

            .filter-select:focus {
                border-color: #109994;
                box-shadow: 0 0 0 0.2rem rgba(16, 153, 148, 0.25);
            }

            .btn-custom {
                background: linear-gradient(135deg, #109994 0%, #0d7377 100%);
                border: none;
                color: white;
                padding: 12px 20px;
                border-radius: 10px;
                font-weight: 500;
                transition: all 0.3s ease;
                height: 45px;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .btn-custom:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(16, 153, 148, 0.4);
                background: #109994;
                color: white;
            }

            /* View Toggle */
            .view-toggle {
                display: flex;
                gap: 10px;
                margin-bottom: 20px;
            }

            .view-btn {
                background: white;
                border: 2px solid #e9ecef;
                color: #6c757d;
                padding: 10px 20px;
                border-radius: 10px;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .view-btn.active {
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

            /* Table Styles */
            .table-container {
                background: white;
                border-radius: 15px;
                overflow: hidden;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            }

            .table th {
                background: linear-gradient(135deg, #109994 0%, #0d7377 100%);
                color: white;
                font-weight: 600;
                padding: 20px 15px;
                border: none;
                font-size: 0.95rem;
            }

            .table td {
                padding: 15px;
                vertical-align: middle;
                border-bottom: 1px solid #f8f9fa;
            }

            .table tbody tr:hover {
                background-color: #f8f9fa;
            }

            /* Card View Styles */
            .user-cards {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                gap: 20px;
            }

            .user-card {
                background: white;
                border-radius: 15px;
                padding: 25px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }

            .user-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            }

            .user-header {
                display: flex;
                align-items: center;
                gap: 15px;
                margin-bottom: 20px;
            }

            .user-avatar {
                width: 60px;
                height: 60px;
                border-radius: 50%;
                background: linear-gradient(135deg, #109994 0%, #0d7377 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 1.5rem;
                font-weight: 600;
            }

            .user-info h6 {
                margin: 0;
                color: #2c3e50;
                font-weight: 600;
            }

            .user-info small {
                color: #6c757d;
            }

            .role-badge {
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 0.8rem;
                font-weight: 500;
                text-transform: uppercase;
            }

            .role-admin {
                background: #dc3545;
                color: white;
            }

            .role-user {
                background: #28a745;
                color: white;
            }

            .borrow-stats {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
                margin: 15px 0;
            }

            .stat-item {
                text-align: center;
                padding: 10px;
                background: #f8f9fa;
                border-radius: 8px;
            }

            .stat-item .number {
                font-size: 1.2rem;
                font-weight: 600;
                color: #109994;
            }

            .stat-item .label {
                font-size: 0.8rem;
                color: #6c757d;
                margin-top: 2px;
            }

            /* Action Buttons */
            .action-buttons {
                display: flex;
                gap: 8px;
                justify-content: center;
            }

            .btn-action {
                padding: 8px 12px;
                border-radius: 8px;
                border: none;
                font-size: 0.9rem;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                gap: 5px;
            }

            .btn-edit {
                background: #ffc107;
                color: #000;
            }

            .btn-edit:hover {
                background: #ffb300;
                transform: translateY(-2px);
            }

            .btn-delete {
                background: #dc3545;
                color: white;
            }

            .btn-delete:hover {
                background: #c82333;
                transform: translateY(-2px);
            }

            .btn-view {
                background: #17a2b8;
                color: white;
            }

            .btn-view:hover {
                background: #138496;
                transform: translateY(-2px);
            }

            /* Alert Styles */
            .alert {
                border-radius: 10px;
                padding: 15px 20px;
                margin-bottom: 20px;
                border: none;
            }

            .alert-success {
                background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
                color: #155724;
            }

            .alert-danger {
                background: linear-gradient(135deg, #f8d7da 0%, #f1aeb5 100%);
                color: #721c24;
            }

            /* No Results */
            .no-results {
                text-align: center;
                padding: 60px 20px;
                color: #6c757d;
            }

            .no-results i {
                font-size: 4rem;
                margin-bottom: 20px;
                opacity: 0.5;
            }

            /* Responsive Design */
            @media (max-width: 768px) {
                .main-content {
                    margin-left: 0;
                }

                .search-filters {
                    flex-direction: column;
                    align-items: stretch;
                }

                .search-box {
                    min-width: auto;
                }

                .user-cards {
                    grid-template-columns: 1fr;
                }

                .borrow-stats {
                    grid-template-columns: 1fr;
                }

                .pagination-container {
                    padding: 20px 15px;
                }

                .sidebar, .top-header, .search-add-section, .pagination-container, .action-buttons {
                    margin: 0;
                    border-radius: 0;
                }
            }

            @media (max-width: 480px) {
                .content-area {
                    padding: 15px;
                }

                .view-toggle {
                    flex-wrap: wrap;
                }

                .table-responsive {
                    font-size: 0.8rem;
                }

                .user-card {
                    padding: 20px;
                }

                .action-buttons {
                    flex-wrap: wrap;
                }
            }
        </style>
    </head>

    <body>
        <?php include '../includes/admin/sidebar.php'; ?>
        <?php include '../includes/admin/header.php'; ?>


        <div class="main-content">

            <div class="content-area">
                <!-- <div class="top-header">
                    <h1 class="page-title">
                        <i class="bi bi-people"></i>
                        User Management
                    </h1>
                </div> -->

                <!-- Alert Messages -->
                <?php if (isset($_SESSION['success_message'])) { ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['success_message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php } ?>

                <?php if (isset($_SESSION['error_message'])) { ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($_SESSION['error_message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php } ?>

                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="page-title">
                        <i class="bi bi-people"></i>
                        User Management
                    </h2>
                </div>

                <!-- Enhanced Search and Filter Section -->
                <div class="search-add-section">
                    <form method="GET" class="search-filters mb-3">
                        <div class="search-box">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by name, email, or user ID..." 
                                   value="<?= htmlspecialchars($search) ?>">
                            <i class="bi bi-search"></i>
                        </div>
                        <select name="role" class="form-select filter-select">
                            <option value="">All Roles</option>
                            <?php foreach ($role_options as $value => $label) { ?>
                                <option value="<?= $value ?>" 
                                        <?= ($role_filter == $value) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php } ?>
                        </select>
                        <button type="submit" class="btn btn-custom">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                        <?php if (!empty($search) || !empty($role_filter)) { ?>
                            <a href="user_management.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle"></i> Clear
                            </a>
                        <?php } ?>
                    </form>

                    <?php if (!empty($search) || !empty($role_filter)) { ?>
                        <div class="search-info">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> 
                                Search Term: "<?= htmlspecialchars($search) ?>"<br>
                                Role Filter: "<?= htmlspecialchars($role_filter) ?>"<br>
                                Found <?= number_format($total_users) ?> result(s)
                            </small>
                        </div>
                    <?php } ?>

                    <!-- Add User Button -->
                    <div class="mt-3">
                        <a href="user_form.php" class="btn btn-custom">
                            <i class="bi bi-plus-circle"></i> Add New User
                        </a>
                    </div>
                </div>

                <!-- View Toggle -->
                <div class="view-toggle">
                    <button class="view-btn active" id="tableView">
                        <i class="bi bi-table"></i> Table View
                    </button>
                    <button class="view-btn" id="cardView">
                        <i class="bi bi-grid-3x3-gap"></i> Card View
                    </button>
                </div>

                <!-- Results Container -->
                <div class="results-container">
                    <!-- Table View -->
                    <div id="tableViewContent" class="view-content">
                        <?php if ($user_result && mysqli_num_rows($user_result) > 0) { ?>
                            <div class="table-container">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>User ID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Total Borrows</th>
                                                <th>Active</th>
                                                <th>Returned</th>
                                                <th>Overdue</th>
                                                <th>Joined</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($user = mysqli_fetch_assoc($user_result)) { ?>
                                                <tr>
                                                    <td>
                                                        <strong>#<?= $user['user_id'] ?></strong>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="user-avatar me-3" style="width: 40px; height: 40px; font-size: 1rem;">
                                                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                                            </div>
                                                            <strong><?= htmlspecialchars($user['name']) ?></strong>
                                                        </div>
                                                    </td>
                                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                                    <td>
                                                        <span class="role-badge role-<?= $user['role'] ?>">
                                                            <?= ucfirst($user['role']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary"><?= $user['total_borrows'] ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-warning"><?= $user['active_borrows'] ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success"><?= $user['returned_books'] ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-danger"><?= $user['overdue_books'] ?></span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?= date('M j, Y', strtotime($user['created_at'])) ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <a href="user_form.php?edit_id=<?= $user['user_id'] ?>" 
                                                               class="btn btn-action btn-edit" title="Edit User">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <a href="user_details.php?id=<?= $user['user_id'] ?>" 
                                                               class="btn btn-action btn-view" title="View Details">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                            <a href="?delete_id=<?= $user['user_id'] ?>" 
                                                               class="btn btn-action btn-delete" 
                                                               onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')"
                                                               title="Delete User">
                                                                <i class="bi bi-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php } else { ?>
                            <div class="no-results">
                                <i class="bi bi-person-x"></i>
                                <h4>No Users Found</h4>
                                <p>No users match your search criteria.</p>
                                <?php if (!empty($search) || !empty($role_filter)) { ?>
                                    <p>Try adjusting your search criteria or filters.</p>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>

                    <!-- Card View -->
                    <div id="cardViewContent" class="view-content" style="display: none;">
                        <?php
                        // Reset result pointer for card view
                        if ($user_result && mysqli_num_rows($user_result) > 0) {
                            mysqli_data_seek($user_result, 0);
                        ?>
                            <div class="user-cards">
                                <?php while ($user = mysqli_fetch_assoc($user_result)) { ?>
                                    <div class="user-card">
                                        <div class="user-header">
                                            <div class="user-avatar">
                                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                            </div>
                                            <div class="user-info">
                                                <h6><?= htmlspecialchars($user['name']) ?></h6>
                                                <small><?= htmlspecialchars($user['email']) ?></small>
                                            </div>
                                            <span class="role-badge role-<?= $user['role'] ?>">
                                                <?= ucfirst($user['role']) ?>
                                            </span>
                                        </div>

                                        <div class="borrow-stats">
                                            <div class="stat-item">
                                                <div class="number"><?= $user['total_borrows'] ?></div>
                                                <div class="label">Total Borrows</div>
                                            </div>
                                            <div class="stat-item">
                                                <div class="number"><?= $user['active_borrows'] ?></div>
                                                <div class="label">Active</div>
                                            </div>
                                            <div class="stat-item">
                                                <div class="number"><?= $user['returned_books'] ?></div>
                                                <div class="label">Returned</div>
                                            </div>
                                            <div class="stat-item">
                                                <div class="number"><?= $user['overdue_books'] ?></div>
                                                <div class="label">Overdue</div>
                                            </div>
                                        </div>

                                        <div class="text-center mb-3">
                                            <small class="text-muted">
                                                <i class="bi bi-calendar3"></i> 
                                                Joined <?= date('M j, Y', strtotime($user['created_at'])) ?>
                                            </small>
                                        </div>

                                        <div class="action-buttons">
                                            <a href="user_form.php?edit_id=<?= $user['user_id'] ?>" 
                                               class="btn btn-action btn-edit">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <a href="user_details.php?id=<?= $user['user_id'] ?>" 
                                               class="btn btn-action btn-view">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <a href="?delete_id=<?= $user['user_id'] ?>" 
                                               class="btn btn-action btn-delete" 
                                               onclick="return confirm('Are you sure you want to delete this user?')">
                                                <i class="bi bi-trash"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } else { ?>
                            <div class="no-results">
                                <i class="bi bi-person-x"></i>
                                <h4>No Users Found</h4>
                                <p>No users match your search criteria.</p>
                                <?php if (!empty($search) || !empty($role_filter)) { ?>
                                    <p>Try adjusting your search criteria or filters.</p>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>

                    <!-- Enhanced Pagination -->
                    <?php if ($total_pages > 1) { ?>
                    <div class="pagination-container">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="text-muted">
                                Showing <?= $start + 1 ?> to <?= min($start + $limit, $total_users) ?> of <?= number_format($total_users) ?> users (6 per page)
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
            });

            cardViewBtn.addEventListener('click', function() {
                cardViewBtn.classList.add('active');
                tableViewBtn.classList.remove('active');
                tableContent.style.display = 'none';
                cardContent.style.display = 'block';
            });

            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const closeBtn = alert.querySelector('.btn-close');
                    if (closeBtn) {
                        closeBtn.click();
                    }
                });
            }, 5000);

            // Add confirmation dialog for delete actions
            const deleteButtons = document.querySelectorAll('a[href*="delete_id"]');
            deleteButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            });

            // Enhanced search functionality
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        this.closest('form').submit();
                    }
                });
            }

            // Role filter change handler
            const roleFilter = document.querySelector('select[name="role"]');
            if (roleFilter) {
                roleFilter.addEventListener('change', function() {
                    this.closest('form').submit();
                });
            }
        });

        // Copy user details functionality
        function copyUserDetails(userId, userName, userEmail) {
            const text = `User ID: ${userId}\nName: ${userName}\nEmail: ${userEmail}`;
            navigator.clipboard.writeText(text).then(function() {
                // Show temporary success message
                const btn = event.target.closest('button') || event.target;
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check"></i> Copied!';
                btn.classList.add('btn-success');
                
                setTimeout(function() {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('btn-success');
                }, 2000);
            });
        }
        </script>
    </body>
</html>
