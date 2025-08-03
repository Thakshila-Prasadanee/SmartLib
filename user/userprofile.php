<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// DB connection info
$host = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "smartlib";

$conn = new mysqli($host, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = $_SESSION['user'];

// Ensure extra columns exist (run once)
$columnsToAdd = [
    'gender' => "VARCHAR(10) DEFAULT NULL",
    'nickname' => "VARCHAR(50) DEFAULT NULL",
    'contact' => "VARCHAR(20) DEFAULT NULL"
];

$result = $conn->query("SHOW COLUMNS FROM users");
$existingColumns = [];
while ($row = $result->fetch_assoc()) {
    $existingColumns[] = $row['Field'];
}

foreach ($columnsToAdd as $col => $type) {
    if (!in_array($col, $existingColumns)) {
        $conn->query("ALTER TABLE users ADD COLUMN $col $type");
    }
}

// Handle form submission to update user data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $nickname = $_POST['nickname'] ?? '';

    // Update user info in DB (email assumed unique and not editable)
    $updateStmt = $conn->prepare("UPDATE users SET name = ?, gender = ?, contact = ?, nickname = ? WHERE email = ?");
    $updateStmt->bind_param("sssss", $full_name, $gender, $contact, $nickname, $email);
    $updateStmt->execute();

    // Redirect to prevent resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch user info from DB
$stmt = $conn->prepare("SELECT name, email, gender, contact, nickname FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "User not found.";
    exit();
}

// Use a default profile image or your own logic
$profile_image = 'assets/profile.jpg';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
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
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
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

        input,
        select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        #profilePreview div {
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
    </style>
</head>

<body>

    <div class="profile-container">
        <div class="header">
            <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?></h2>
            <button class="edit-button" onclick="toggleEdit()">Edit</button>
        </div>
        <div style="text-align:center; margin-top:15px;">
            <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Image" class="profile-img" />
        </div>

        <!-- Preview Mode -->
        <div id="profilePreview" class="info-grid" style="margin-top:20px;">
            <div class="form-group">
                <label>Full Name</label>
                <div><?php echo htmlspecialchars($user['name']); ?></div>
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
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['name']); ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email (cannot change)</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="">-- Select --</option>
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
                <button type="button" class="edit-button" style="background-color:#6c757d; margin-left:10px;"
                    onclick="toggleEdit()">Cancel</button>
            </div>
        </form>
    </div>

    <script>
        function toggleEdit() {
            const form = document.getElementById('profileForm');
            const preview = document.getElementById('profilePreview');

            if (window.getComputedStyle(form).display === 'block') {
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
