<?php
session_start();
require_once('../includes/connection.php'); // Your DB connection

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            // Generate token and expiry time (1 hour)
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", time() + 3600);

            $stmt->close(); // close previous statement

            // Update user record with reset token and expiry
            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $stmt->bind_param("sss", $token, $expires, $email);
            $stmt->execute();
            $stmt->close();

            // Prepare and send email
            $resetLink = "http://localhost/SmartLib/auth/reset_password.php?token=$token";

            $subject = "Password Reset Request";
            $message = "Hello,\n\nYou requested a password reset. Click the link below to reset your password:\n\n$resetLink\n\nIf you didn't request this, you can ignore this email.\n\nThanks,\nSmartLib Team";

            $headers = "From: no-reply@smartlib.com\r\n";
            $headers .= "Reply-To: no-reply@smartlib.com\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            if (mail($email, $subject, $message, $headers)) {
                $success = "A reset link has been sent to your email.";
            } else {
                $error = "Failed to send email. Please try again.";
            }
        } else {
            $error = "Email not found.";
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; padding: 40px; }
        .form-box {
            max-width: 400px; background: #fff; padding: 20px;
            margin: auto; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        input[type="email"], button {
            width: 100%; padding: 10px; margin-top: 10px;
            border-radius: 4px; border: 1px solid #ccc;
            box-sizing: border-box;
        }
        button {
            background-color: #007bff; color: white; border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #0056b3;
        }
        .msg { margin-top: 10px; color: green; }
        .error { margin-top: 10px; color: red; }
    </style>
</head>
<body>

<div class="form-box">
    <h2>Forgot Password</h2>

    <?php if ($success) echo "<p class='msg'>" . htmlspecialchars($success) . "</p>"; ?>
    <?php if ($error) echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; ?>

    <form method="post" novalidate>
        <label for="email">Enter your email address:</label>
        <input type="email" name="email" id="email" required>
        <button type="submit">Send Reset Link</button>
    </form>

    <p style="margin-top: 10px;">
        <a href="login.php">Back to Login</a>
    </p>
</div>

</body>
</html>
