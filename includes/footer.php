
<style>
    .footer-section {
        background-color: #245f5b;
        color: #eaeaea;
        padding: 50px;
        font-family: 'Inria Sans', sans-serif;
        margin-top: auto;
    }

    .footer-container {
        background-color: #183130;
        padding: 40px 40px 0px 40px;
        margin: 0 auto;
        border-radius: 8px;
    }

    .footer-logo img {
        height: 80px;
        object-fit: contain;
    }

    .footer-title {
        font-weight: 600;
        margin: 0 0 20px 0;
        color: #ffffff;
        font-size: 1.2rem;
    }

    .footer-contact,
    .footer-links {
        list-style: none;
        padding: 0;
    }

    .footer-contact li,
    .footer-links li {
        margin-bottom: 12px;
        font-size: 0.95rem;
        line-height: 1.5;
    }

    .footer-contact a,
    .footer-links a {
        text-decoration: none;
        color: #eaeaea;
        transition: color 0.3s ease;
        display: inline-block;
    }

    .footer-contact a:hover,
    .footer-links a:hover {
        color: #ffd254;
        transform: translateX(5px);
    }

    .footer-contact i {
        margin-right: 10px;
        color: #fcf6bd;
        width: 20px;
        text-align: center;
    }

    .footer-social {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 15px;
    }

    .footer-social a {
        color: #ffffff;
        font-size: 1.3rem;
        transition: all 0.3s ease;
        padding: 8px;
    }

    .footer-social a:hover {
        color: #ffd254;
        transform: translateY(-3px);
    }

    .footer-bottom {
        border-top: 1px solid rgba(255, 255, 255, 0.2);
        font-size: 0.9rem;
        margin-top: 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    .footer-bottom p {
        text-align: left;
        margin: 0;
    }

    .footer-description {
        line-height: 1.6;
        margin-bottom: 20px;
        color: #d0d0d0;
    }

    .footer-columns {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        align-items: start;
    }

    .footer-main {
        padding-right: 20px;
    }

    .footer-side-columns {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
    }

    /* Tablet Styles */
    @media (max-width: 991.98px) {
        .footer-section {
            padding: 40px 15px;
        }
            
        .footer-container {
            padding: 30px 25px;
        }
            
        .footer-columns {
            grid-template-columns: 1fr;
            gap: 30px;
        }
            
        .footer-main {
            padding-right: 0;
            text-align: center;
        }
            
        .footer-side-columns {
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
            
        .footer-logo {
            text-align: center;
        }
    }

    /* Mobile Styles */
    @media (max-width: 767.98px) {
        .footer-section {
            padding: 30px 10px;
        }
            
        .footer-container {
            padding: 25px 20px;
        }
            
        .footer-columns {
            gap: 25px;
        }
            
        .footer-side-columns {
            grid-template-columns: 1fr;
            gap: 25px;
            text-align: center;
        }
            
        .footer-title {
            font-size: 1.1rem;
            margin-bottom: 15px;
        }
            
        .footer-contact li,
        .footer-links li {
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
            
        .footer-description {
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
            
        .footer-bottom {
            flex-direction: column;
            text-align: center;
            gap: 10px;
        }

        .footer-bottom p {
            text-align: center;
        }
            
        .footer-social {
            justify-content: center;
            margin-top: 10px;
        }
            
        .footer-logo img {
            height: 80px;
        }
    }

    /* Extra Small Mobile */
    @media (max-width: 479.98px) {
        .footer-section {
            padding: 25px 8px;
        }
            
        .footer-container {
            padding: 20px 15px;
        }
            
        .footer-title {
            font-size: 1rem;
        }
            
        .footer-contact li,
        .footer-links li {
            font-size: 0.85rem;
        }
            
        .footer-description {
            font-size: 0.85rem;
        }
            
        .footer-social a {
            font-size: 1.1rem;
            padding: 6px;
        }
            
        .footer-logo img {
            height: 80px;
        }
            
        .footer-contact i {
            width: 18px;
            font-size: 0.9rem;
        }
    }

    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
        .footer-section {
            background-color: #1a4b47;
        }
            
        .footer-container {
            background-color: #0f2524;
        }
    }

    /* High contrast mode support */
    @media (prefers-contrast: high) {
        .footer-contact a,
        .footer-links a {
            color: #ffffff;
        }
            
        .footer-contact a:hover,
        .footer-links a:hover {
            color: #ffcb3c;
        }
    }

    /* Reduced motion support */
    @media (prefers-reduced-motion: reduce) {
        .footer-contact a,
        .footer-links a,
        .footer-social a {
            transition: none;
        }
            
        .footer-contact a:hover,
        .footer-links a:hover,
        .footer-social a:hover {
            transform: none;
        }
    }
</style>

<footer id="footer-section" class="footer-section">
    <div class="footer-container">
        <div class="footer-columns">
            <!-- Main Column with Logo and Description -->
            <div class="footer-main">
                <div class="footer-logo">
                    <img src="images/logo.svg" alt="SmartLib Logo">
                </div>
                <p class="footer-description">
                    Our Library Management System simplifies the way you organize, manage, and enjoy books. 
                    From powerful admin tools to user-friendly borrowing features, we make your library accessible anytime, anywhere.
                </p>
            </div>

            <!-- Side Columns Container -->
            <div class="footer-side-columns">
                <!-- Contact Us Column -->
                <div class="footer-side-column">
                    <h5 class="footer-title">Contact Us</h5>
                    <ul class="footer-contact">
                        <li><i class="bi bi-envelope"></i> <a href="mailto:email@example.com">email@example.com</a></li>
                        <li><i class="bi bi-telephone"></i> <a href="tel:+1555000000">+1 (555) 000-0000</a></li>
                        <li><i class="bi bi-geo-alt"></i> <span>123 Sample St, Sydney NSW 2000 AU</span></li>
                    </ul>
                </div>

                <!-- Quick Links Column -->
                <div class="footer-side-column">
                    <h5 class="footer-title">Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="#">Home</a></li>
                        <li><a href="book/books_page.php">Our Books</a></li>
                        <li><a href="#about-section">About Us</a></li>
                        <li><a href="#footer-section">Contact Us</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="footer-bottom">
            <p>© 2025 Group 04. All rights reserved.</p>
            <div class="footer-social">
                <a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                <a href="#" aria-label="Twitter"><i class="bi bi-twitter"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                <a href="#" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
            </div>
        </div>
    </div>
</footer>
