<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>


<style>

    body {
        background-color: #f8f9fa;
    }

    /* Hero Section CSS */
    * {
        font-family:'Inria Sans', sans-serif;
    }

    h1 {
        color: rgba(255, 255, 255, 0.8);
    }

    p {
        color: rgba(255, 255, 255, 0.8);
        font-size: 1.1rem !important;
        max-width: 900px;
        margin: 0 auto 20px auto;
        line-height: 1.6;
    }

    .hero-section {
        position: relative;
        height: 90vh;
        background: url('assets/hero.avif') center center/cover no-repeat;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .hero-section::after {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.5); /* Overlay for text readability */
        z-index: 1;
    }
    .hero-section .container {
        position: relative;
        z-index: 2;
    }
    .btn {
        font-weight: 500;
        transition: all 0.3s ease;
    }
    .btn:hover {
        transform: scale(1.05);
        border-color: #000000;
    }

    .btn-dark:hover {
        background-color: #245f5b;
    }

    /* About Section Styles */
    .about-section {
        padding: 60px 0;
    }

    .about-section h2 {
        color: #183130;
        font-weight: 700;
        margin-bottom: 20px;
        text-align: left;
    }

    .about-section p {
        font-size: 1rem;
        line-height: 1.6;
        color: #245f5b;
        text-align: left;
        margin: 0;
        max-width: 500px;
    }

    .about-section img {
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        width: 100%;
        height: auto;
        object-fit: cover;
    }

    @media (max-width: 768px) {
        .about-section {
            text-align: center;
        }
        .about-section p {
            margin: 0 auto 20px;
        }
    }

    /* Features Section Styles */
    .features-section {
        padding: 80px 0;
        text-align: center;
    }

    .features-section h2 {
        font-weight: 700;
        margin-bottom: 15px;
        font-size: 2rem;
    }

    .features-section p.subtitle {
        font-size: 1.1rem;
        color: #666;
        margin-bottom: 50px;
    }

    .feature-card {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 10px;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
        min-height: 420px;
        display: flex;
        flex-direction: column;
    }

    .feature-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }

    .feature-card img {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }

    .feature-card-body {
        padding: 20px;
        text-align: left;
        flex: 1;
    }

    .feature-card-body:hover {
        background-color: #fcf6bd;
    }

    .feature-card-body h5 {
        font-weight: 600;
        margin-bottom: 10px;
        font-size: 1.5rem;
        margin: 20px 0;
    }

    .feature-card-body p {
        font-size: 0.95rem;
        color: #555;
        margin: 0;
    }
</style>

<!-- Hero Section -->
<section class="hero-section d-flex align-items-center text-white">
    <div class="container text-center">
        <!-- Heading -->
        <h1 class="fw-bold display-5 mb-3">Your Library. Anywhere. Anytime.</h1>
        
        <!-- Description -->
        <p class="lead mb-4">
            Turn your library into a digital hub where readers explore stunning book collections, 
            borrow their favorites, and track every read with ease. Librarians can effortlessly 
            organize books, upload vibrant cover images, generate QR codes, and manage everything seamlessly.
        </p>

        <!-- Buttons -->
        <div class="d-flex justify-content-center gap-3">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <!-- Before Login -->
                <a href="login.php" class="btn btn-dark btn-outline-light px-4 py-2">Login</a>
            <?php endif; ?>

            <!-- Explore -->
            <a href="books.php" class="btn btn-outline-light px-4 py-2">Explore</a>
        </div>
    </div>
</section>

<section class="about-section">
    <div class="container">
        <div class="row align-items-center">
            <!-- Text Content -->
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h2>About the Library</h2>
                <p>
                    Welcome to SmartLib, your intelligent and accessible library portal. 
                    Designed to support a modern reading experience, SmartLib offers users a convenient 
                    way to explore books, borrow them, and manage their reading activity—all in one place.
                </p>
            </div>
            <!-- Image Content -->
            <div class="col-lg-6 text-center">
                <img src="assets/about.avif" alt="About Library" class="img-fluid">
            </div>
        </div>
    </div>
</section>

<section class="features-section">
    <div class="container">
        <!-- Title -->
        <h2>Powerful Features to Elevate Your Library</h2>
        <p class="subtitle">
            SmartLib brings you modern tools to simplify library management and enhance the reading experience.
        </p>

        <!-- Feature Grid -->
        <div class="row g-4">
            <!-- Feature 1 -->
            <div class="col-md-4">
                <div class="feature-card">
                    <img src="assets/feature1.avif" alt="Easy Book Management">
                    <div class="feature-card-body">
                        <h5>Easy Book Management</h5>
                        <p>Easily add, edit, and organize books with cover images and categories.</p>
                    </div>
                </div>
            </div>

            <!-- Feature 2 -->
            <div class="col-md-4">
                <div class="feature-card">
                    <img src="assets/feature2.avif" alt="Secure Role Access">
                    <div class="feature-card-body">
                        <h5>Secure Role Access</h5>
                        <p>Manage admin and user roles with secure, personalized access.</p>
                    </div>
                </div>
            </div>

            <!-- Feature 3 -->
            <div class="col-md-4">
                <div class="feature-card">
                    <img src="assets/feature3.avif" alt="QR Code Integration">
                    <div class="feature-card-body">
                        <h5>QR Code Integration</h5>
                        <p>Scan QR codes for quick access to book details and borrowing.</p>
                    </div>
                </div>
            </div>

            <!-- Feature 4 -->
            <div class="col-md-4">
                <div class="feature-card">
                    <img src="assets/feature4.avif" alt="PDF Report Generation">
                    <div class="feature-card-body">
                        <h5>PDF Report Generation</h5>
                        <p>Generate and download borrow history and library reports in PDF.</p>
                    </div>
                </div>
            </div>

            <!-- Feature 5 -->
            <div class="col-md-4">
                <div class="feature-card">
                    <img src="assets/feature5.avif" alt="Responsive Web Design">
                    <div class="feature-card-body">
                        <h5>Responsive Web Design</h5>
                        <p>Enjoy a clean, responsive design on all devices and screen sizes.</p>
                    </div>
                </div>
            </div>

            <!-- Feature 6 -->
            <div class="col-md-4">
                <div class="feature-card">
                    <img src="assets/feature6.avif" alt="Advanced Book Search">
                    <div class="feature-card-body">
                        <h5>Advanced Book Search</h5>
                        <p>Find books instantly with advanced search and filter options.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>