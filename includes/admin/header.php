<!-- top-header.php -->
<style>
.top-header {
    background-color: #084D4A;
    color: #fff;
    padding: 12px 20px;
    position: fixed;
    left: 240px;
    right: 0;
    top: 0;
    z-index: 900;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
    transition: left 0.3s ease;
    height: 60px;
}

.admin-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1rem;
}

.top-header i {
    font-size: 1.5rem;
}

.top-header span {
    font-size: 1.2rem;
    font-weight: 500;
}

.admin-info i {
    font-size: 1.8rem;
}
</style>

<div class="top-header">
    <span>
        <i class="bi bi-bar-chart-line-fill"></i> 
        Admin Dashboard
    </span>

    <div class="admin-info">
        <i class="bi bi-person-circle"></i>
        <span>Welcome, <?= isset($_SESSION['admin_name']) ? htmlspecialchars($_SESSION['admin_name']) : 'Admin'; ?></span>
    </div>
</div>
