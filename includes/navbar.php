<?php
// Start session if not started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<style>

    .navbar{
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 1050;
        height: 80px;
    }

    .navbar-color {
        background-color: rgba(0, 0, 0, 0.5); 
        padding: 0.02rem 0;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .nav-item .nav-link {
        color: #ffffff;
    }

    .navbar-toggler:hover .bi-list {
        color: #183130 !important;
    }

    .navbar-toggler:focus {
        outline: none !important;
        box-shadow: none !important;
    }

    .navbar-nav .nav-link {
        font-weight: 500;
        margin: 0 15px;
        transition: color 0.3s ease;
    }

    .navbar-nav .nav-link:hover {
        color: #f1d791ff !important;
    }

    .btn-signup {
        background-color: #183130;
        border: 2px solid #ffffff;
        color: #ffffff;
        padding: 8px 20px;
        border-radius: 10px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-signup:hover {
        background-color: #245f5b;
        border: 2px solid #000000;
        color: #000000;
    }

    /* Mobile specific styles */
    @media (max-width: 991.98px) {
        .navbar-collapse {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background-color: rgba(0, 0, 0, 0.9);
            padding: 1rem;
            margin: 0;
            z-index: 1000;
            border-radius: 0;
        }
        
        .navbar-nav {
            margin-bottom: 1rem;
        }
        
        .nav-item {
            margin: 0.5rem 0;
        }
        
        .nav-link {
            padding: 0.5rem 1rem;
        }
        
        .d-flex {
            justify-content: center;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .dropdown-menu {
            background-color: rgba(0, 0, 0, 0.7);
        }
        
        .dropdown-item {
            color: #fff;
        }
        
        .dropdown-item:hover {
            background-color: rgba(255,255,255,0.1);
            color: #fff;
        }
    }
</style>

<nav class="navbar navbar-expand-lg navbar-color">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="images/logo.svg" alt="SmartLib Logo" height="90" class="me-2">
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler border-0 p-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <i class="bi bi-list text-white fs-2 fw-bold"></i>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="book/books_page.php">Our Books</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#about-section">About Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#footer-section">Contact Us</a>
                </li>
            </ul>

            <!-- Right Side (Auth Button / Profile) -->
            <div class="d-flex align-items-center">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- After Login -->
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" 
                           id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?= $_SESSION['profile_img'] ?? 'assets/profile.jpg'; ?>" 
                                 alt="Profile" width="50" height="50" class="rounded-circle border border-2 border-white">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="profile.php">
                                    <i class="bi bi-person me-2"></i> Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center text-danger" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i> Sign Out
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <!-- Before Login -->
                    <a href="auth/signup.php" class="btn btn-signup">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>