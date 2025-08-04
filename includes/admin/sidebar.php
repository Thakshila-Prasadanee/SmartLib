<!-- sidebar.php -->
<style>
.sidebar {
    background: #109994;
    width: 240px;
    color: #000;
    padding: 10px;
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    transition: width 0.3s ease;
    z-index: 1000;
}

.logo-scanner {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 30px;
    width: 100%;
}

.logo-scanner img {
    max-height: 80px;
    object-fit: contain;
}

.logo-scanner i {
    font-size: 22px;
    color: #000;
    cursor: pointer;
    -webkit-text-stroke: 0.4px #000;
}

.sidebar a {
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    color: #000;
    margin: 8px 0;
    padding: 8px;
    width: 100%;
    border-radius: 6px;
    transition: all 0.3s ease;
    font-size: 1.1rem;
    font-weight: 500;
}

.sidebar a i {
    font-size: 1.4rem;
    -webkit-text-stroke: 0.7px #000;
}

.sidebar a:hover,
.sidebar a.active {
    background: rgba(255, 255, 255, 0.7);
    transform: translateX(5px);
}
</style>

<div class="sidebar" id="sidebar">
    <div class="logo-scanner">
        <img src="../images/logo.svg" alt="SmartLib Logo">
        <i class="bi bi-qr-code-scan" title="Scan"></i>
    </div>

    <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
        <i class="bi bi-speedometer2"></i>
        <span>Dashboard</span>
    </a>

    <a href="book_management.php" class="<?= basename($_SERVER['PHP_SELF']) == 'book_management.php' ? 'active' : '' ?>">
        <i class="bi bi-journal-bookmark-fill"></i>
        <span>Book Management</span>
    </a>

    <a href="borrow_management.php" class="<?= basename($_SERVER['PHP_SELF']) == 'borrow_management.php' ? 'active' : '' ?>">
        <i class="bi bi-layers"></i>
        <span>Borrow Management</span>
    </a>

    <a href="user_management.php" class="<?= basename($_SERVER['PHP_SELF']) == 'user_management.php' ? 'active' : '' ?>">
        <i class="bi bi-people"></i>
        <span>User Management</span>
    </a>

    <a href="admin/logout.php">
        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>
    </a>
</div>
