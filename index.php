<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartLib</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" 
          rel="stylesheet" 
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" 
          crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- AOS CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
</head>
<body>
   
    <!-- AOS JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000, // Animation duration in ms
            once: false,
        });
    </script>

    <?php
    // Start session
    session_start();

    // Handle logout message display
    $show_logout_message = false;
    if (isset($_SESSION['user_logged_out']) && $_SESSION['user_logged_out'] === true) {
        $show_logout_message = true;
        // Clear the logout flag after capturing it
        unset($_SESSION['user_logged_out']);
        unset($_SESSION['show_logout_message']);
    }

    // Check login and redirect based on role
    if (isset($_SESSION['user']) || isset($_SESSION['admin_name'])) {
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            // Admin Dashboard
            include 'admin/dashboard.php';
        } else {
            // User Homepage
            include 'user/homepage.php';
        }
    } else {
        // Guest view (homepage for visitors)
        include 'user/homepage.php';
    }
    ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" 
            crossorigin="anonymous">
    </script>
</body>
</html>
