<?php
session_start();
require_once('../includes/connection.php');  // DB connection

// Redirect logged-out users or show error on missing token
if (!isset($_GET['token'])) {
    $_SESSION['error'] = "Invalid password reset request.";
    header("Location: login.php");
    exit();
}

$token = $_GET['token'];

// Verify token exists and is not expired
$sql = "SELECT email, expires_at FROM password_resets WHERE token = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION['error'] = "Invalid or expired token.";
    header("Location: login.php");
    exit();
}

$row = $result->fetch_assoc();
$email = $row['email'];
$expires_at = $row['expires_at'];

// Check expiration
if (strtotime($expires_at) < time()) {
    $_SESSION['error'] = "This password reset link has expired.";
    // Optionally: delete expired token here
    header("Location: login.php");
    exit();
}

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($password) || empty($confirm_password)) {
        $errors[] = "Please fill out all fields.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Update user's password in users table
        $updateSql = "UPDATE users SET password = ? WHERE email = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("ss", $password_hash, $email);

        if ($updateStmt->execute()) {
            // Delete used token
            $deleteSql = "DELETE FROM password_resets WHERE token = ?";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->bind_param("s", $token);
            $deleteStmt->execute();

            $success = "Your password has been reset successfully. You can now <a href='login.php'>login</a>.";
        } else {
            $errors[] = "Something went wrong. Please try again.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Reset Password - SmartLib</title>
<link rel="stylesheet" href="../css/styles.css" />
</head>
<body>
  <div class="container">
    <h2>Reset Password</h2>

    <?php if (!empty($errors)): ?>
      <div class="error">
        <ul>
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="success"><?= $success ?></div>
    <?php else: ?>
      <form method="POST" action="">
        <label for="password">New Password:</label>
        <input type="password" name="password" id="password" required minlength="6" />

        <label for="confirm_password">Confirm New Password:</label>
        <input type="password" name="confirm_password" id="confirm_password" required minlength="6" />

        <button type="submit">Reset Password</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
