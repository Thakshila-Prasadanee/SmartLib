<?php

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // ✅ Ensure only logged-in admins can access this page
    // if (!isset($_SESSION['admin_id'])) {
    //     header("Location: login.php");
    //     exit();
    // }

    // ✅ Get admin name from session
    // $adminName = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';

    // ✅ Database connection
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $db_name = 'smartlib';
    $conn = mysqli_connect($host, $username, $password, $db_name);

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // ✅ Total Users
    $user_query = "SELECT COUNT(user_id) AS total_users FROM users";
    $user_result = mysqli_query($conn, $user_query) or die("User Query Failed: " . mysqli_error($conn));
    $total_users = mysqli_fetch_assoc($user_result)['total_users'] ?? 0;

    // ✅ Total Books
    $book_query = "SELECT COUNT(book_id) AS total_books FROM books";
    $book_result = mysqli_query($conn, $book_query) or die("Book Query Failed: " . mysqli_error($conn));
    $total_books = mysqli_fetch_assoc($book_result)['total_books'] ?? 0;

    // ✅ Borrowed Books (Books not returned yet)
    $borrowed_query = "SELECT COUNT(record_id) AS borrowed_books FROM borrow_records WHERE return_date IS NULL";
    $borrowed_result = mysqli_query($conn, $borrowed_query) or die("Borrowed Query Failed: " . mysqli_error($conn));
    $borrowed_books = mysqli_fetch_assoc($borrowed_result)['borrowed_books'] ?? 0;

    // ✅ Overdue Books (if you don't track due dates, mark overdue as not returned older than 14 days)
    $overdue_query = "SELECT COUNT(record_id) AS overdue_books 
                    FROM borrow_records 
                    WHERE return_date IS NULL 
                    AND borrow_date < DATE_SUB(CURDATE(), INTERVAL 14 DAY)";
    $overdue_result = mysqli_query($conn, $overdue_query) or die("Overdue Query Failed: " . mysqli_error($conn));
    $overdue_books = mysqli_fetch_assoc($overdue_result)['overdue_books'] ?? 0;


    // ✅ Fetch categories and their book count
    $category_query = "
        SELECT c.name AS category_name, COUNT(b.book_id) AS book_count
        FROM categories c
        LEFT JOIN books b ON c.category_id = b.category_id
        GROUP BY c.category_id
    ";
    $category_result = mysqli_query($conn, $category_query);

    $categories = [];
    $bookCounts = [];
    while ($row = mysqli_fetch_assoc($category_result)) {
        $categories[] = $row['category_name'];
        $bookCounts[] = $row['book_count'];
    }

    // ✅ Fetch borrowing trends for the last 6 months
    $trend_query = "
        SELECT DATE_FORMAT(br.borrow_date, '%b') AS month, COUNT(br.record_id) AS borrow_count
        FROM borrow_records br
        WHERE br.borrow_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month
        ORDER BY br.borrow_date
    ";
    $trend_result = mysqli_query($conn, $trend_query);

    $trendMonths = [];
    $trendCounts = [];
    while ($row = mysqli_fetch_assoc($trend_result)) {
        $trendMonths[] = $row['month'];
        $trendCounts[] = $row['borrow_count'];
    }

    // Fetch top 3 trending books based on borrow count
    $trending_query = "
        SELECT b.title, b.author, c.name AS category, COUNT(br.record_id) AS borrow_count
        FROM borrow_records br
        INNER JOIN books b ON br.book_id = b.book_id
        INNER JOIN categories c ON b.category_id = c.category_id
        GROUP BY b.book_id
        ORDER BY borrow_count DESC
        LIMIT 3
    ";
    $trending_result = mysqli_query($conn, $trending_query);


    mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SmartLib</title>
    
    <!-- ✅ Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inria+Sans:wght@400;600&display=swap" rel="stylesheet">

    <!-- ✅ Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- ✅ Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- ✅ Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body { 
            background-color: #f4f6f9; 
            margin: 0; 
            padding: 0; 
            overflow-x: hidden; 
        }

        .dashboard-container { 
            display: flex; 
            min-height: 100vh; 
            width: 100%; 
        }

        .main-content { 
            margin-left: 240px; 
            padding: 80px 20px 20px; 
            width: calc(100% - 240px); 
            transition: margin-left 0.3s ease; 
        }

        .card-summary { 
            border-radius: 10px; 
            text-align: center; 
            font-weight: 600; 
            padding: 25px; 
            color: #fff; 
            transition: transform 0.3s ease, box-shadow 0.3s ease; 
            font-size: 2rem; 
            min-height: 120px; 
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2), 0 6px 20px rgba(0, 0, 0, 0.15); 
            cursor: pointer;
        }

        .card-summary:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3), 0 12px 30px rgba(0, 0, 0, 0.2);
        }

        .card-summary small { 
            font-size: 0.9rem; 
        }

        .pink { 
            background: linear-gradient(135deg, #fe9496, #f76568); 
        }

        .blue { 
            background: linear-gradient(135deg, #4bcbeb, #219ebc); 
        }

        .green { 
            background: linear-gradient(135deg, #1bcfb4, #129a8b); 
        }

        .purple { 
            background: linear-gradient(135deg, #9e58ff, #6f2edb); 
        }

        .card-shadow { 
            background: #fff; 
            border-radius: 10px; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.1); 
            padding: 12px; 
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }

        .card-shadow:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2), 0 12px 25px rgba(0, 0, 0, 0.15);
        }

        .charts-row { 
            display: flex; 
            gap: 15px; 
            margin-bottom: 15px; 
            flex-wrap: wrap; 
        }

        .chart-card, .pie-card { 
            flex: 1; 
            min-width: 250px; 
            max-height: 260px; 
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            padding: 15px; 
            background: #fff; 
            border-radius: 10px; 
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2), 0 6px 20px rgba(0, 0, 0, 0.15); 
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }

        .chart-card:hover, .pie-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3), 0 12px 30px rgba(0, 0, 0, 0.2);
        }

        .chart-card canvas, .pie-card canvas { 
            max-height: 220px; 
        }

        .table-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            padding: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .table-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .table-card h5 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.3rem;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
        }

        .table-card h5 i {
            color: #e74c3c;
            margin-right: 8px;
            font-size: 1.2em;
        }

        /* Enhanced Table Styling */
        .trending-table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin: 0;
        }

        .trending-table thead { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        .trending-table th {
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 15px 12px;
            border: none;
            text-align: center;
        }

        .trending-table td {
            padding: 12px;
            vertical-align: middle;
            text-align: center;
            border-bottom: 1px solid #f1f3f4;
            font-size: 0.9rem;
            transition: background-color 0.2s ease;
        }

        .trending-table tbody tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        .trending-table tbody tr:nth-child(even) {
            background-color: #fbfbfb;
        }

        .trending-table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        /* Serial Number Styling */
        .serial-number {
            background: linear-gradient(135deg, #ffeaa7, #fdcb6e);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.8rem;
            color: #2d3436;
        }

        /* Book Title Styling */
        .book-title {
            font-weight: 600;
            color: #2c3e50;
            text-align: left;
        }

        /* Author Styling */
        .book-author {
            color: #636e72;
            font-style: italic;
            text-align: left;
        }

        /* Count Badge Styling */
        .borrow-count {
            background: linear-gradient(135deg, #00b894, #00a085);
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 0.85rem;
            display: inline-block;
            min-width: 35px;
        }

        /* Category Pills */
        .category-pill {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table td, table th { 
            vertical-align: middle; 
            text-align: center; 
            padding: 8px; 
        }

        /* Chart title icons bold styling */
        .chart-card h5 i, .pie-card h5 i {
            font-weight: 900 !important;
            font-size: 1.2em;
        }

        .chart-title-icon {
            font-weight: 900 !important;
            font-size: 1.2em;
            text-shadow: 0.5px 0.5px 0px currentColor, -0.5px -0.5px 0px currentColor !important;
            /* Alternative: use transform to make icons appear bolder */
            transform: scaleX(1.1);
        }

        /* Responsive Table Design */
        @media (max-width: 768px) {
            .trending-table {
                font-size: 0.8rem;
            }
            
            .trending-table th,
            .trending-table td {
                padding: 8px 4px;
            }
            
            .book-title,
            .book-author {
                text-align: center;
            }
            
            .serial-number {
                width: 25px;
                height: 25px;
                font-size: 0.7rem;
            }
            
            .category-pill {
                padding: 2px 8px;
                font-size: 0.7rem;
            }
            
            .borrow-count {
                padding: 2px 6px;
                font-size: 0.75rem;
            }
        }

        /* Smooth animations for better UX */
        .trending-table tbody tr {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .trending-table tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar -->
    <?php include '../includes/admin/sidebar.php'; ?>

    <!-- Header -->
    <?php include '../includes/admin/header.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <h3>Overview</h3>
        
        <div class="row g-3 mb-3">
            <div class="col-md-3 col-6">
                <div class="card-summary pink">
                    <?= $total_users ?><br>
                    <small>Total Users</small>
                </div>
            </div>
            
            <div class="col-md-3 col-6">
                <div class="card-summary blue">
                    <?= $total_books ?><br>
                    <small>Total Books</small>
                </div>
            </div>
            
            <div class="col-md-3 col-6">
                <div class="card-summary green">
                    <?= $borrowed_books ?><br>
                    <small>Borrowed Books</small>
                </div>
            </div>
            
            <div class="col-md-3 col-6">
                <div class="card-summary purple">
                    <?= $overdue_books ?><br>
                    <small>Overdue Books</small>
                </div>
            </div>
        </div>


        <!-- Charts Row -->
        <div class="charts-row">
            <div class="chart-card card-shadow">
                <h5 class="fw-bold mb-2 text-center">
                    <i class="bi bi-graph-up fw-bold chart-title-icon"></i> 
                    Borrowing Trends
                </h5>
                <canvas id="borrowChart"></canvas>
            </div>
            
            <div class="pie-card card-shadow">
                <h5 class="fw-bold mb-2 text-center">
                    <i class="bi bi-pie-chart fw-bold chart-title-icon"></i> 
                    Books by Categories
                </h5>
                <canvas id="categoryChart"></canvas>
            </div>
        </div>

        <!-- Trending Books -->
        <div class="table-card card-shadow">
            <h5 class="fw-bold mb-3">
                <i class="bi bi-fire"></i>Trending Books
            </h5>
            
            <table class="table trending-table table-hover">
                <thead>
                    <tr>
                        <th width="10%">Rank</th>
                        <th width="35%">Book Name</th>
                        <th width="25%">Author</th>
                        <th width="20%">Category</th>
                        <th width="10%">Borrows</th>
                    </tr>
                </thead>
                
                <tbody>
                    <?php 
                    $sl_no = 1;
                    while ($row = mysqli_fetch_assoc($trending_result)) {
                        // Assign Bootstrap classes and custom pill colors based on category
                        $categoryClass = 'text-secondary';
                        $pillStyle = 'background: linear-gradient(135deg, #74b9ff, #0984e3);';
                        
                        if (strtolower($row['category']) === 'fantasy') {
                            $categoryClass = 'text-success';
                            $pillStyle = 'background: linear-gradient(135deg, #00b894, #00a085);';
                        } elseif (strtolower($row['category']) === 'adventure') {
                            $categoryClass = 'text-primary';
                            $pillStyle = 'background: linear-gradient(135deg, #74b9ff, #0984e3);';
                        } elseif (strtolower($row['category']) === 'thriller') {
                            $categoryClass = 'text-warning';
                            $pillStyle = 'background: linear-gradient(135deg, #fdcb6e, #e17055);';
                        } elseif (strtolower($row['category']) === 'horror') {
                            $categoryClass = 'text-danger';
                            $pillStyle = 'background: linear-gradient(135deg, #fd79a8, #e84393);';
                        }
                        
                        echo "<tr>
                                <td><span class='serial-number'>" . $sl_no . "</span></td>
                                <td class='book-title'>" . htmlspecialchars($row['title']) . "</td>
                                <td class='book-author'>" . htmlspecialchars($row['author']) . "</td>
                                <td><span class='category-pill' style='{$pillStyle} color: white;'>" . htmlspecialchars($row['category']) . "</span></td>
                                <td><span class='borrow-count'>" . $row['borrow_count'] . "</span></td>
                              </tr>";
                        $sl_no++;
                    }
                    ?>
                    
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ✅ Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- ✅ Charts Script -->
<script>
    // Data from PHP
    const categories = <?php echo json_encode($categories); ?>;
    const bookCounts = <?php echo json_encode($bookCounts); ?>;
    const trendMonths = <?php echo json_encode($trendMonths); ?>;
    const trendCounts = <?php echo json_encode($trendCounts); ?>;

    // Pie Chart - Books by Category
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    
    new Chart(categoryCtx, {
        type: 'pie',
        data: {
            labels: categories,  // From DB
            datasets: [{
                data: bookCounts, // From DB
                backgroundColor: ['#283593', '#fb8c00', '#e040fb', '#1e88e5', '#43a047'],
                offset: bookCounts.map((v, i) => i % 2 === 0 ? 20 : 0) // Alternate offset slices
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { 
                    display: true,
                    position: 'right',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: { size: 14 }
                    }
                },
                tooltip: { 
                    enabled: true,
                    callbacks: {
                        label: function (tooltipItem) {
                            let dataset = tooltipItem.chart.data.datasets[0];
                            let total = dataset.data.reduce((sum, value) => sum + parseInt(value || 0), 0);
                            let currentValue = dataset.data[tooltipItem.dataIndex];
                            let percentage = total > 0 ? ((currentValue / total) * 100).toFixed(1) : 0;
                            return `${tooltipItem.label}: ${currentValue} (${percentage}%)`;
                        }
                    }
                }
            }
        },
        plugins: [{
            id: 'labels',
            afterDraw: (chart) => {
                const ctx = chart.ctx;
                ctx.save();

                const dataset = chart.data.datasets[0];
                const total = dataset.data.reduce((sum, value) => sum + parseInt(value || 0), 0);

                chart.getDatasetMeta(0).data.forEach((element, index) => {
                    const { x, y } = element.tooltipPosition();
                    const value = parseInt(dataset.data[index] || 0); 
                    const percent = total > 0 ? ((value / total) * 100).toFixed(0) : 0;
                });

                ctx.restore();
            }
        }]
    });

    // Line Chart - Borrowing Trends
    const borrowCtx = document.getElementById('borrowChart').getContext('2d');
    
    new Chart(borrowCtx, {
        type: 'line',
        data: {
            labels: trendMonths,
            datasets: [{
                label: 'Borrowed Books (Last 6 Months)',
                data: trendCounts,
                borderColor: '#42a5f5',
                backgroundColor: 'rgba(66, 165, 245, 0.2)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#1e88e5',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: { 
            responsive: true, 
            plugins: { 
                legend: { 
                    display: false 
                } 
            }, 
            scales: { 
                y: { 
                    beginAtZero: true, 
                    ticks: { 
                        stepSize: 5 
                    } 
                } 
            } 
        }
    });
</script>

</body>
</html>
