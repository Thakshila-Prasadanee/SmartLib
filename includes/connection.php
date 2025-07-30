<?php

    // SmarLib Database connection file

    $host = 'localhost';
    $username = 'root';
    $password= '';
    $db_name = 'smartlib';

    // Create connection
    $conn = mysqli_connect($host, $username, $password, $db_name);

    // Check connection
    if(!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    } else {
        echo "Database connected successfully <br>";
    }

    // Select the database
    mysqli_select_db($conn, $db_name);

    // Create Tables

    // User Table
    $user_table = "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'user') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    // Check the user table creation
    if (mysqli_query($conn, $user_table)) {
        echo "Users table created successfully <br>";
    } else {
        echo "Error creating user table: " . mysqli_error($conn) . "<br>";
    }


    // Category Table
    $category_table = "CREATE TABLE IF NOT EXISTS categories (
        category_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    // Check the category table creation
    if (mysqli_query($conn, $category_table)) {
        echo "Categories table created successfully <br>";
    } else {
        echo "Error creating category table: " . mysqli_error($conn) . "<br>";
    }

    // Book Table
    $book_table = "CREATE TABLE IF NOT EXISTS books (
        book_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        author VARCHAR(100) NOT NULL,
        isbn VARCHAR(20) UNIQUE,
        published_year YEAR,
        category_id INT,
        description TEXT,
        image_url VARCHAR(255),
        quantity INT DEFAULT 1,
        status ENUM('available', 'borrowed') DEFAULT 'available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
    )";

    // Check the book table creation
    if (mysqli_query($conn, $book_table)) {
        echo "Books table created successfully <br>";
    } else {
        echo "Error creating book table: " . mysqli_error($conn) . "<br>";
    }

    // Borrow Record Table
    $borrow_record_table = "CREATE TABLE IF NOT EXISTS borrow_records (
        record_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        book_id INT NOT NULL,
        borrow_date DATE DEFAULT CURRENT_TIMESTAMP,
        return_date DATE NULL,
        status ENUM('borrowed', 'returned', 'overdue') DEFAULT 'borrowed',
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
    )";

    // Check the borrow record table creation
    if (mysqli_query($conn, $borrow_record_table)) {
        echo "Borrow records table created successfully <br>";
    } else {
        echo "Error creating borrow records table: " . mysqli_error($conn) . "<br>";
    }

    // Close the connection
    mysqli_close($conn);

?>