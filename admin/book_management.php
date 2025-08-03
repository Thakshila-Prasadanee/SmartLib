<?php
    // Start session only if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }


    $host = 'localhost';
    $username = 'root';
    $password= '';
    $db_name = 'smartlib';

    // Recreate connection for this page (since connection.php closes it)
    $conn = mysqli_connect($host, $username, $password, $db_name);

    // Handle connection errors silently (log them instead of showing to user)
    if (!$conn) {
        // Log the error for debugging (you can write to a log file)
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

    // ✅ Search functionality with improved security
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

    $search_condition = '';
    $search_params = [];

    if (!empty($search)) {
        $search_condition = "WHERE (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ? OR c.name LIKE ?)";
        $search_term = "%$search%";
        $search_params = [$search_term, $search_term, $search_term, $search_term];
    }

    if ($category_filter > 0) {
        if (!empty($search_condition)) {
            $search_condition .= " AND b.category_id = ?";
        } else {
            $search_condition = "WHERE b.category_id = ?";
        }
        $search_params[] = $category_filter;
    }

    // ✅ Pagination - Updated to show 6 books per page
    $limit = 6; // books per page (changed from 8 to 6)
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $start = ($page - 1) * $limit;

    // ✅ Fetch books with prepared statement for security
    $book_query = "
        SELECT 
            b.book_id, 
            b.title, 
            b.author, 
            b.isbn, 
            b.published_year,
            b.quantity,
            b.status,
            b.description,
            b.image_url,
            b.created_at,
            c.name AS category,
            c.category_id,
            COALESCE((SELECT COUNT(*) FROM borrow_records br WHERE br.book_id = b.book_id AND br.status = 'borrowed'), 0) as borrowed_count
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.category_id
        $search_condition
        ORDER BY b.created_at DESC
        LIMIT $start, $limit
    ";

    if (!empty($search_params)) {
        $stmt = mysqli_prepare($conn, $book_query);
        if ($stmt) {
            $types = str_repeat('s', count($search_params) - ($category_filter > 0 ? 1 : 0));
            if ($category_filter > 0) $types .= 'i';
            
            mysqli_stmt_bind_param($stmt, $types, ...$search_params);
            mysqli_stmt_execute($stmt);
            $book_result = mysqli_stmt_get_result($stmt);
            mysqli_stmt_close($stmt);
        } else {
            error_log("Failed to prepare book query: " . mysqli_error($conn));
            $book_result = false;
        }
    } else {
        $book_result = mysqli_query($conn, $book_query);
    }

    if (!$book_result) {
        error_log("Book query failed: " . mysqli_error($conn));
        // Show user-friendly error instead of technical details
        $book_result = false;
    }

    // ✅ Count total books for pagination
    $total_query = "
        SELECT COUNT(*) as total 
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.category_id
        $search_condition
    ";

    $total_books = 0;
    if (!empty($search_params)) {
        $stmt = mysqli_prepare($conn, $total_query);
        if ($stmt) {
            $types = str_repeat('s', count($search_params) - ($category_filter > 0 ? 1 : 0));
            if ($category_filter > 0) $types .= 'i';
            
            mysqli_stmt_bind_param($stmt, $types, ...$search_params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                $total_books = mysqli_fetch_assoc($result)['total'];
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $total_result = mysqli_query($conn, $total_query);
        if ($total_result) {
            $total_books = mysqli_fetch_assoc($total_result)['total'];
        }
    }

    $total_pages = ceil($total_books / $limit);

    // ✅ Get statistics with error handling
    $stats_query = "
        SELECT 
            (SELECT COUNT(*) FROM books) as total_books,
            (SELECT COUNT(DISTINCT book_id) FROM borrow_records WHERE status = 'borrowed') as borrowed_books,
            (SELECT COUNT(*) FROM categories) as total_categories,
            (SELECT COUNT(*) FROM borrow_records WHERE status = 'borrowed') as active_borrows
    ";

    $stats_result = mysqli_query($conn, $stats_query);
    $stats = [
        'total_books' => 0,
        'borrowed_books' => 0,
        'available_books' => 0,
        'total_categories' => 0,
        'active_borrows' => 0
    ];

    if ($stats_result) {
        $stats = mysqli_fetch_assoc($stats_result);
        $stats['available_books'] = $stats['total_books'] - $stats['borrowed_books'];
    } else {
        error_log("Stats query failed: " . mysqli_error($conn));
    }

    // ✅ Get categories for filter dropdown
    $categories_query = "SELECT category_id, name FROM categories ORDER BY name";
    $categories_result = mysqli_query($conn, $categories_query);

    if (!$categories_result) {
        error_log("Categories query failed: " . mysqli_error($conn));
        // Create empty result set
        $categories_result = false;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Management - SmartLib</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { background: #109994; width: 240px; color: #000; padding: 10px; position: fixed; top: 0; bottom: 0; }
        .sidebar a { display: flex; align-items: center; gap: 8px; text-decoration: none; color: #000; margin: 8px 0; padding: 8px; border-radius: 6px; font-weight: 500; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255, 255, 255, 0.7); }
        .top-header { background: #084D4A; color: #fff; padding: 12px 20px; position: fixed; left: 240px; right: 0; top: 0; height: 60px; display: flex; justify-content: space-between; align-items: center; }
        .main-content { 
            margin-left: 240px; 
            padding: 100px 30px 30px; 
            width: calc(100% - 240px); 
            min-height: 100vh;
        }
        
        /* Enhanced Main Content Styles */
        .page-header {
            /* background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
            color: black;
            /* padding: 15px; */
            /* border-radius: 20px; */
            margin-bottom: 30px;
            /* box-shadow: 0 10px 30px rgba(0,0,0,0.1); */
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
            font-size: 1.4rem;
        }
        
        .search-add-section {
            padding: 25px 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
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
            padding: 12px 45px 12px 20px;
            border: 2px solid #e9ecef;
            border-radius: 50px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .search-box input:focus {
            border-color: #109994;
            box-shadow: 0 0 0 0.2rem rgba(16, 153, 148, 0.25);
        }
        
        .search-box i {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .filter-select {
            padding: 12px 20px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            min-width: 150px;
        }
        
        .filter-select:focus {
            border-color: #109994;
            box-shadow: 0 0 0 0.2rem rgba(16, 153, 148, 0.25);
        }
        
        .btn-add-new {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            white-space: nowrap;
        }
        
        .btn-add-new:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
            color: white;
        }
        
        /* Enhanced Table and Card Layout for Better Book Display */
        .table-container {
            padding: 0;
            overflow-x: auto;
        }
        
        /* Alternative Card View for Better Book Display */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            padding: 30px;
        }
        
        .book-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #f1f3f4;
        }
        
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.12);
        }
        
        .book-card-header {
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid #e9ecef;
        }
        
        .book-card-body {
            padding: 20px;
        }
        
        .book-card-footer {
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
        
        .book-card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        
        .book-card-author {
            color: #6c757d;
            font-style: italic;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .book-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .book-info-label {
            color: #6c757d;
            font-weight: 500;
        }
        
        .book-info-value {
            color: #495057;
            font-weight: 400;
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
            transition: all 0.3s ease;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .modern-table tbody tr:hover {
            background: #f8f9ff;
            transform: scale(1.01);
        }
        
        .modern-table td {
            padding: 18px 15px;
            vertical-align: middle;
            border: none;
            font-size: 0.95rem;
        }
        
        .book-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 2px;
        }
        
        .book-author {
            color: #6c757d;
            font-style: italic;
            font-size: 0.9rem;
        }
        
        .category-badge {
            display: inline-block;
            padding: 6px 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-available {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .status-borrowed {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
        }
        
        .quantity-info {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .quantity-total {
            font-weight: bold;
            color: #109994;
        }
        
        .quantity-available {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .btn-action {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .btn-edit {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
        }
        
        .btn-edit:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(23, 162, 184, 0.4);
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .btn-delete:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
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
        
        .error-state {
            text-align: center;
            padding: 60px 20px;
            color: #dc3545;
        }
        
        .error-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.7;
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
        
        @media (max-width: 768px) {
            .search-filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                min-width: auto;
            }
            
            .books-grid {
                grid-template-columns: 1fr;
                padding: 20px;
            }
            
            .view-toggle {
                flex-direction: column;
                gap: 5px;
            }
            
            .view-toggle .btn {
                padding: 10px 16px;
            }
        }
        
        @media (max-width: 576px) {
            .books-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .book-card-header, .book-card-body, .book-card-footer {
                padding: 15px;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo-scanner text-center mb-4">
            <img src="../images/logo.svg" alt="SmartLib Logo" style="max-height:60px;">
        </div>
        <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="book_management.php" class="active"><i class="bi bi-journal-bookmark-fill"></i> Book Management</a>
        <a href="borrow_management.php"><i class="bi bi-layers"></i> Borrow Management</a>
        <a href="user_management.php"><i class="bi bi-people"></i> User Management</a>
        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <!-- Top Header -->
    <div class="top-header">
        <span><i class="bi bi-book"></i> Book Management</span>
        <div class="admin-info"><i class="bi bi-person-circle"></i> Welcome, Admin</div>
    </div>

    <!-- Enhanced Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="bi bi-book-half"></i> Book Management</h2>
            <p>Manage your library's book collection with ease and efficiency</p>
        </div>

        <!-- Main Content Card -->
        <div class="content-card">
            <div class="card-header-custom">
                <h4><i class="bi bi-collection"></i> Book Collection</h4>
            </div>

            <!-- Enhanced Search and Filter Section -->
            <div class="search-add-section">
                <form method="GET" class="search-filters mb-3">
                    <div class="search-box">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search books by title, author, ISBN, or category..." 
                               value="<?= htmlspecialchars($search) ?>">
                        <i class="bi bi-search"></i>
                    </div>
                    <select name="category" class="form-select filter-select">
                        <option value="">All Categories</option>
                        <?php 
                        if ($categories_result) {
                            while ($category = mysqli_fetch_assoc($categories_result)) { ?>
                                <option value="<?= $category['category_id'] ?>" 
                                        <?= ($category_filter == $category['category_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php }
                        } ?>
                    </select>
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <?php if (!empty($search) || $category_filter > 0) { ?>
                        <a href="book_management.php" class="btn btn-outline-secondary">
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
                    <a href="add_book.php" class="btn btn-add-new">
                        <i class="bi bi-plus-circle me-2"></i>Add New Book
                    </a>
                </div>
            </div>

            <!-- Enhanced Table Container with View Toggle -->
            <div class="table-container" id="tableContainer">
                <?php if ($book_result && mysqli_num_rows($book_result) > 0) { ?>
                    <!-- Table View -->
                    <div id="tableViewContent">
                        <table class="table modern-table">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-hash me-1"></i>ID</th>
                                    <th><i class="bi bi-book me-1"></i>Book Details</th>
                                    <th><i class="bi bi-upc me-1"></i>ISBN</th>
                                    <th><i class="bi bi-tag me-1"></i>Category</th>
                                    <th><i class="bi bi-calendar me-1"></i>Year</th>
                                    <th><i class="bi bi-box me-1"></i>Quantity</th>
                                    <th><i class="bi bi-info-circle me-1"></i>Status</th>
                                    <th><i class="bi bi-gear me-1"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                mysqli_data_seek($book_result, 0); // Reset result pointer
                                while ($row = mysqli_fetch_assoc($book_result)) { 
                                    $available_qty = $row['quantity'] - $row['borrowed_count'];
                                    ?>
                                <tr>
                                    <td><strong><?= $row['book_id']; ?></strong></td>
                                    <td>
                                        <div class="book-title"><?= htmlspecialchars($row['title']); ?></div>
                                        <div class="book-author">by <?= htmlspecialchars($row['author']); ?></div>
                                    </td>
                                    <td><code><?= htmlspecialchars($row['isbn']); ?></code></td>
                                    <td>
                                        <?php if ($row['category']) { ?>
                                            <span class="category-badge"><?= htmlspecialchars($row['category']); ?></span>
                                        <?php } else { ?>
                                            <span class="text-muted">No Category</span>
                                        <?php } ?>
                                    </td>
                                    <td><?= $row['published_year']; ?></td>
                                    <td>
                                        <div class="quantity-info">
                                            <span class="quantity-total"><?= $row['quantity']; ?></span>
                                            <span class="quantity-available"><?= $available_qty; ?> available</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $available_qty > 0 ? 'status-available' : 'status-borrowed' ?>">
                                            <?= $available_qty > 0 ? 'Available' : 'All Borrowed' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_book.php?id=<?= $row['book_id']; ?>" 
                                               class="btn-action btn-edit" 
                                               title="Edit Book">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            <a href="delete_book.php?id=<?= $row['book_id']; ?>" 
                                               class="btn-action btn-delete" 
                                               title="Delete Book"
                                               onclick="return confirm('Are you sure you want to delete &quot;<?= htmlspecialchars($row['title']) ?>&quot;? This action cannot be undone and will also delete all related borrow records.');">
                                                <i class="bi bi-trash-fill"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Card View -->
                    <div id="cardViewContent" class="d-none">
                        <div class="books-grid">
                            <?php 
                            mysqli_data_seek($book_result, 0); // Reset result pointer
                            while ($row = mysqli_fetch_assoc($book_result)) { 
                                $available_qty = $row['quantity'] - $row['borrowed_count'];
                                ?>
                            <div class="book-card">
                                <div class="book-card-header">
                                    <div class="book-card-title"><?= htmlspecialchars($row['title']); ?></div>
                                    <div class="book-card-author">by <?= htmlspecialchars($row['author']); ?></div>
                                </div>
                                
                                <div class="book-card-body">
                                    <div class="book-info-row">
                                        <span class="book-info-label">Book ID:</span>
                                        <span class="book-info-value">#<?= $row['book_id']; ?></span>
                                    </div>
                                    
                                    <div class="book-info-row">
                                        <span class="book-info-label">ISBN:</span>
                                        <span class="book-info-value">
                                            <code style="cursor: pointer;" onclick="copyToClipboard('<?= htmlspecialchars($row['isbn']); ?>')" title="Click to copy">
                                                <?= htmlspecialchars($row['isbn']); ?>
                                            </code>
                                        </span>
                                    </div>
                                    
                                    <div class="book-info-row">
                                        <span class="book-info-label">Category:</span>
                                        <span class="book-info-value">
                                            <?php if ($row['category']) { ?>
                                                <span class="category-badge"><?= htmlspecialchars($row['category']); ?></span>
                                            <?php } else { ?>
                                                <span class="text-muted">No Category</span>
                                            <?php } ?>
                                        </span>
                                    </div>
                                    
                                    <div class="book-info-row">
                                        <span class="book-info-label">Published Year:</span>
                                        <span class="book-info-value"><?= $row['published_year']; ?></span>
                                    </div>
                                    
                                    <div class="book-info-row">
                                        <span class="book-info-label">Total Copies:</span>
                                        <span class="book-info-value">
                                            <span class="quantity-total"><?= $row['quantity']; ?></span>
                                        </span>
                                    </div>
                                    
                                    <div class="book-info-row">
                                        <span class="book-info-label">Available:</span>
                                        <span class="book-info-value">
                                            <span class="quantity-available"><?= $available_qty; ?> copies</span>
                                        </span>
                                    </div>
                                    
                                    <div class="book-info-row">
                                        <span class="book-info-label">Status:</span>
                                        <span class="book-info-value">
                                            <span class="status-badge <?= $available_qty > 0 ? 'status-available' : 'status-borrowed' ?>">
                                                <?= $available_qty > 0 ? 'Available' : 'All Borrowed' ?>
                                            </span>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="book-card-footer">
                                    <div class="action-buttons">
                                        <a href="edit_book.php?id=<?= $row['book_id']; ?>" 
                                           class="btn-action btn-edit" 
                                           title="Edit Book">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                        <a href="delete_book.php?id=<?= $row['book_id']; ?>" 
                                           class="btn-action btn-delete" 
                                           title="Delete Book"
                                           onclick="return confirm('Are you sure you want to delete &quot;<?= htmlspecialchars($row['title']) ?>&quot;? This action cannot be undone and will also delete all related borrow records.');">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                <?php } elseif ($book_result === false) { ?>
                    <div class="error-state">
                        <i class="bi bi-exclamation-triangle"></i>
                        <h5>Unable to Load Books</h5>
                        <p>There was an issue loading the book data. Please try refreshing the page.</p>
                        <button onclick="window.location.reload()" class="btn btn-outline-primary mt-3">
                            <i class="bi bi-arrow-clockwise me-2"></i>Refresh Page
                        </button>
                    </div>
                <?php } else { ?>
                    <div class="empty-state">
                        <i class="bi bi-book"></i>
                        <?php if (!empty($search) || $category_filter > 0) { ?>
                            <h5>No Books Found</h5>
                            <p>No books match your search criteria<?= !empty($search) ? ': "' . htmlspecialchars($search) . '"' : '' ?></p>
                            <a href="book_management.php" class="btn btn-outline-primary mt-3">
                                <i class="bi bi-arrow-left me-2"></i>View All Books
                            </a>
                        <?php } else { ?>
                            <h5>No Books Found</h5>
                            <p>Start building your library by adding your first book!</p>
                            <a href="add_book.php" class="btn btn-add-new mt-3">
                                <i class="bi bi-plus-circle me-2"></i>Add Your First Book
                            </a>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>

            <!-- Enhanced Pagination -->
            <?php if ($total_pages > 1) { ?>
            <div class="pagination-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-muted">
                        Showing <?= $start + 1 ?> to <?= min($start + $limit, $total_books) ?> of <?= number_format($total_books) ?> books (6 per page)
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

<?php 
// Clean up resources
if ($book_result) mysqli_free_result($book_result);
if ($categories_result) mysqli_free_result($categories_result);
mysqli_close($conn); 
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // View toggle functionality
    const tableViewBtn = document.getElementById('tableView');
    const cardViewBtn = document.getElementById('cardView');
    const tableViewContent = document.getElementById('tableViewContent');
    const cardViewContent = document.getElementById('cardViewContent');
    
    // Load saved view preference
    const savedView = localStorage.getItem('bookViewPreference') || 'table';
    if (savedView === 'card') {
        switchToCardView();
    }
    
    tableViewBtn.addEventListener('click', switchToTableView);
    cardViewBtn.addEventListener('click', switchToCardView);
    
    function switchToTableView() {
        tableViewBtn.classList.add('active');
        cardViewBtn.classList.remove('active');
        tableViewContent.classList.remove('d-none');
        cardViewContent.classList.add('d-none');
        localStorage.setItem('bookViewPreference', 'table');
    }
    
    function switchToCardView() {
        cardViewBtn.classList.add('active');
        tableViewBtn.classList.remove('active');
        tableViewContent.classList.add('d-none');
        cardViewContent.classList.remove('d-none');
        localStorage.setItem('bookViewPreference', 'card');
    }
    
    // Auto-submit search form on input change (with debouncing)
    const searchInput = document.querySelector('input[name="search"]');
    const categorySelect = document.querySelector('select[name="category"]');
    let searchTimeout;
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 3 || this.value.length === 0) {
                    this.form.submit();
                }
            }, 500);
        });
    }
    
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            this.form.submit();
        });
    }
    
    // Enhanced delete confirmation
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            const bookTitle = this.closest('tr').querySelector('.book-title').textContent;
            
            // Create custom confirmation dialog
            const confirmDelete = confirm(
                `Are you sure you want to delete "${bookTitle}"?\n\n` +
                `This action cannot be undone and will also delete all related borrow records.`
            );
            
            if (confirmDelete) {
                // Show loading state
                this.innerHTML = '<i class="bi bi-spinner-border spinner-border-sm"></i>';
                this.style.pointerEvents = 'none';
                
                // Navigate to delete URL
                window.location.href = href;
            }
        });
    });
    
    // Add loading states for edit buttons
    document.querySelectorAll('.btn-edit').forEach(button => {
        button.addEventListener('click', function() {
            this.innerHTML = '<i class="bi bi-spinner-border spinner-border-sm"></i>';
            this.style.pointerEvents = 'none';
        });
    });
    
    // Add success message handling if coming from add/edit/delete operations
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');
    const status = urlParams.get('status');
    
    if (message && status) {
        showNotification(message, status);
        
        // Clean URL without reloading page
        const cleanUrl = window.location.origin + window.location.pathname;
        window.history.replaceState({}, document.title, cleanUrl);
    }
    
    function showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
        notification.style.cssText = `
            top: 80px;
            right: 20px;
            z-index: 1050;
            min-width: 300px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        `;
        
        notification.innerHTML = `
            <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
        
        // Escape to clear search
        if (e.key === 'Escape') {
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput && searchInput === document.activeElement) {
                searchInput.value = '';
                searchInput.form.submit();
            }
        }
    });
    
    // Add search highlighting
    function highlightSearchTerms() {
        const searchTerm = searchInput ? searchInput.value.trim() : '';
        if (searchTerm.length >= 2) {
            const bookTitles = document.querySelectorAll('.book-title, .book-author');
            bookTitles.forEach(element => {
                const text = element.textContent;
                const regex = new RegExp(`(${searchTerm})`, 'gi');
                if (regex.test(text)) {
                    element.innerHTML = text.replace(regex, '<mark>$1</mark>');
                }
            });
        }
    }
    
    // Apply search highlighting on page load
    highlightSearchTerms();
});

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

// Add ISBN copy functionality
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('td code').forEach(codeElement => {
        codeElement.style.cursor = 'pointer';
        codeElement.title = 'Click to copy ISBN';
        codeElement.addEventListener('click', function() {
            copyToClipboard(this.textContent);
        });
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

.alert {
    border: none;
    border-radius: 10px;
}

.alert-success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border-left: 4px solid #28a745;
}

.alert-danger {
    background: linear-gradient(135deg, #f8d7da 0%, #f1c2c7 100%);
    border-left: 4px solid #dc3545;
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