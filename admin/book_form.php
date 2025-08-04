<?php
    session_start();
    // include '../includes/connection.php';

    $host = 'localhost';
    $username = 'root';
    $password= '';
    $db_name = 'smartlib';

    $conn = new mysqli($host,$username,$password,$db_name);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $title       = mysqli_real_escape_string($conn, $_POST['title']);
        $author      = mysqli_real_escape_string($conn, $_POST['author']);
        $isbn        = mysqli_real_escape_string($conn, $_POST['isbn']);
        $year        = mysqli_real_escape_string($conn, $_POST['year']);
        $category    = mysqli_real_escape_string($conn, $_POST['category']);
        $publisher   = mysqli_real_escape_string($conn, $_POST['publisher']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);

        // Handle image upload
        $image_url = null;
        if (!empty($_FILES['image']['name'])) {
            $targetDir = "uploads/books/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            $fileName = time() . "_" . basename($_FILES["image"]["name"]);
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                $image_url = $targetFile;
            }
        }

        // Check or insert category
        $cat_query = "SELECT category_id FROM categories WHERE name = '$category' LIMIT 1";
        $cat_result = mysqli_query($conn, $cat_query);

        if (mysqli_num_rows($cat_result) > 0) {
            $cat_row = mysqli_fetch_assoc($cat_result);
            $category_id = $cat_row['category_id'];
        } else {
            $insert_cat = "INSERT INTO categories (name) VALUES ('$category')";
            if (mysqli_query($conn, $insert_cat)) {
                $category_id = mysqli_insert_id($conn);
            } else {
                $_SESSION['error'] = "Error inserting category: " . mysqli_error($conn);
                header("Location: book_form.php");
                exit();
            }
            }

        // Insert book
        $sql = "INSERT INTO books (title, author, isbn, published_year, category_id, description, image_url) 
                VALUES ('$title', '$author', '$isbn', '$year', '$category_id', '$description', '$image_url')";

        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "Book added successfully!";
        } else {
            $_SESSION['error'] = "Error: " . mysqli_error($conn);
        }

        header("Location: book_form.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Book Management</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
        <style>
            body {
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                min-height: 100vh;
            }
            
            .content {
                margin-left: 240px;
                padding: 80px 30px 30px;
                transition: margin-left 0.3s ease;
                min-height: 100vh;
            }
            
            .card {
                border-radius: 10px;
                box-shadow: 0 15px 35px rgba(0,0,0,0.1);
                border: none;
                background: white;
                overflow: hidden;
                transition: all 0.3s ease;
            }
            
            .card:hover {
                transform: translateY(-5px);
                box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            }
            
            .card-body {
                padding: 30px;
                background: #fafbfc;
            }
            
            .page-title {
                color: #2c3e50;
                font-weight: 700;
                font-size: 1.5rem;
                margin-bottom: 25px;
            }
            
            .form-label {
                font-weight: 600;
                color: #2c3e50;
                margin-bottom: 8px;
                font-size: 0.95rem;
            }
            
            .form-control, .form-select {
                border: 2px solid #e9ecef;
                border-radius: 10px;
                padding: 12px 16px;
                font-size: 0.95rem;
                transition: all 0.3s ease;
                background: white;
            }
            
            .form-control:focus, .form-select:focus {
                border-color: #109994;
                box-shadow: 0 0 0 0.2rem rgba(16, 153, 148, 0.25);
                background: white;
            }
            
            .form-control:hover, .form-select:hover {
                border-color: #109994;
            }
            
            textarea.form-control {
                resize: vertical;
                min-height: 120px;
            }
            
            .btn-custom {
                min-width: 120px;
                padding: 12px 25px;
                border-radius: 10px;
                font-weight: 600;
                font-size: 0.95rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                transition: all 0.3s ease;
                border: none;
            }
            
            .btn-secondary.btn-custom {
                background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
                color: white;
                box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
            }
            
            .btn-secondary.btn-custom:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
                background: linear-gradient(135deg, #5a6268 0%, #4e555b 100%);
            }
            
            .btn-success.btn-custom {
                background: linear-gradient(135deg, #109994 0%, #0d7377 100%);
                color: white;
                box-shadow: 0 4px 15px rgba(16, 153, 148, 0.3);
            }
            
            .btn-success.btn-custom:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(16, 153, 148, 0.4);
                background: linear-gradient(135deg, #0d7377 0%, #0a5d61 100%);
            }
            
            .alert {
                border-radius: 15px;
                border: none;
                padding: 16px 20px;
                margin-bottom: 25px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            }
            
            .alert-success {
                background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
                border-left: 4px solid #28a745;
                color: #155724;
            }
            
            .alert-danger {
                background: linear-gradient(135deg, #f8d7da 0%, #f1c2c7 100%);
                border-left: 4px solid #dc3545;
                color: #721c24;
            }
            
            .row.g-3 > .col-md-6,
            .row.g-3 > .col-md-12 {
                margin-bottom: 20px;
            }
            
            .form-floating {
                margin-bottom: 20px;
            }
            
            .input-group {
                margin-bottom: 20px;
            }
            
            @media (max-width: 991px) {
                .content {
                    margin-left: 0;
                    padding: 100px 20px 30px;
                }
                
                .card-body {
                    padding: 20px;
                }
                
                .btn-custom {
                    min-width: 100px;
                    padding: 10px 20px;
                }
            }
            
            @media (max-width: 576px) {
                .content {
                    padding: 90px 15px 20px;
                }
                
                .d-flex.gap-2 {
                    flex-direction: column;
                    gap: 10px !important;
                }
                
                .btn-custom {
                    width: 100%;
                }
            }
        </style>
    </head>
    <body>
        <?php include '../includes/admin/sidebar.php'; ?>
        <?php include '../includes/admin/header.php'; ?>

        <div class="content">
            <div class="container">
                <!-- Alerts -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Page Title -->
                <h2 class="page-title">
                    Add New Book
                </h2>

                <!-- Book Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form action="book_form.php" method="POST" enctype="multipart/form-data">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label"><i class="bi bi-book me-1"></i>Book Title</label>
                                    <input type="text" name="title" class="form-control" placeholder="Enter book title" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><i class="bi bi-person me-1"></i>Author</label>
                                    <input type="text" name="author" class="form-control" placeholder="Enter author name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><i class="bi bi-upc me-1"></i>ISBN Number</label>
                                    <input type="text" name="isbn" class="form-control" placeholder="Enter ISBN number" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><i class="bi bi-calendar me-1"></i>Published Year</label>
                                    <input type="number" name="year" class="form-control" placeholder="YYYY" min="1000" max="2099" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><i class="bi bi-tag me-1"></i>Category</label>
                                    <input type="text" name="category" class="form-control" placeholder="Enter book category" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><i class="bi bi-building me-1"></i>Publisher</label>
                                    <input type="text" name="publisher" class="form-control" placeholder="Enter publisher name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><i class="bi bi-image me-1"></i>Book Cover Image</label>
                                    <input type="file" name="image" class="form-control" accept="image/*">
                                    <div class="form-text">Optional: Upload book cover image (JPG, PNG, GIF)</div>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="w-100">
                                        <div class="alert alert-info mb-0">
                                            <i class="bi bi-info-circle me-2"></i>
                                            <small>Fill in all required fields marked with (*)</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label"><i class="bi bi-text-paragraph me-1"></i>Description</label>
                                    <textarea name="description" class="form-control" rows="4" placeholder="Enter book description (optional)"></textarea>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-3 mt-4">
                                <button type="reset" class="btn btn-secondary btn-custom">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Reset
                                </button>
                                <button type="submit" class="btn btn-success btn-custom">
                                    <i class="bi bi-check-circle me-1"></i>Save Book
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
