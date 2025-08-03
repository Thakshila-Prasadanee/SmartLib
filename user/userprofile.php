<?php
session_start();

// Sample user data (replace with your database logic)
$user = [
    'full_name' => 'Amanda',
    'email' => 'amanda@example.com',
    'gender' => 'Female',
    'contact' => '987-654-3210',
    'nickname' => 'AlexRaw',
    'profile_image' => 'assets/profile.jpg' // Path to profile picture
];

// Handle form submission to update user data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user['full_name'] = $_POST['full_name'];
    $user['email'] = $_POST['email'];
    $user['gender'] = $_POST['gender'];
    $user['contact'] = $_POST['contact'];
    $user['nickname'] = $_POST['nickname'];

    // Normally, save to database here...

    // Redirect to avoid resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>User Profile</title>
<style>
body {
    font-family: Arial, sans-serif;
    background-color: #f0f2f5;
    margin: 0;
    padding: 20px;
}
.profile-container {
    max-width: 900px;
    margin: 0 auto;
    background: #fff;
    padding: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.header h2 {
    margin: 0;
}
.profile-img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #ddd;
}
.edit-button {
    background-color: #007bff;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    color: #fff;
    cursor: pointer;
}
.edit-button:hover {
    background-color: #0056b3;
}
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 20px;
}
.form-group {
    display: flex;
    flex-direction: column;
}
label {
    margin-bottom: 5px;
    font-weight: bold;
}
input, select {
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
}
</style>
</head>
<body>

<div class="profile-container">
    <div class="header">
        <h2>Welcome, <?php echo htmlspecialchars($user['full_name']); ?></h2>
        <button class="edit-button" onclick="toggleEdit()">Edit</button>
    </div>

    <div style="text-align:center; margin-top:15px;">
        <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Image" class="profile-img"/>
    </div>

    <!-- Preview Mode -->
    <div id="profilePreview" class="info-grid" style="margin-top:20px;">
        <div class="form-group">
            <label>Full Name</label>
            <div><?php echo htmlspecialchars($user['full_name']); ?></div>
        </div>
        <div class="form-group">
            <label>Email</label>
            <div><?php echo htmlspecialchars($user['email']); ?></div>
        </div>
        <div class="form-group">
            <label>Gender</label>
            <div><?php echo htmlspecialchars($user['gender']); ?></div>
        </div>
        <div class="form-group">
            <label>Contact</label>
            <div><?php echo htmlspecialchars($user['contact']); ?></div>
        </div>
        <div class="form-group">
            <label>Nick Name</label>
            <div><?php echo htmlspecialchars($user['nickname']); ?></div>
        </div>
    </div>

    <!-- Edit Form -->
    <form method="post" id="profileForm" style="display:none; margin-top:20px;">
        <div class="info-grid">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>
            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender">
                    <option value="Male" <?php echo ($user['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo ($user['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo ($user['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            <div class="form-group">
                <label for="contact">Contact</label>
                <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($user['contact']); ?>">
            </div>
            <div class="form-group">
                <label for="nickname">Nick Name</label>
                <input type="text" id="nickname" name="nickname" value="<?php echo htmlspecialchars($user['nickname']); ?>">
            </div>
        </div>
        <div style="margin-top:15px;">
            <button type="submit" class="edit-button" style="background-color:#28a745;">Save</button>
            <button type="button" class="edit-button" style="background-color:#6c757d; margin-left:10px;" onclick="toggleEdit()">Cancel</button>
        </div>
    </form>
</div>

<script>
function toggleEdit() {
    const form = document.getElementById('profileForm');
    const preview = document.getElementById('profilePreview');

    const isFormVisible = window.getComputedStyle(form).display === 'block';

    if (isFormVisible) {
        form.style.display = 'none';
        preview.style.display = 'grid';
    } else {
        form.style.display = 'block';
        preview.style.display = 'none';
    }
}
</script>

</body>
</html>
