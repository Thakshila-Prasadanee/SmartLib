<?php
session_start();

// --- DATABASE CONNECTION ---
$host = "localhost";
$db = "smartlib";  
$dbUser = "root";           
$dbPass = "";              

$conn = new mysqli($host, $dbUser, $dbPass, $db);
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// --- LOGIN PROCESSING ---
$loginError = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $conn->prepare("SELECT user_id, name, email, password FROM users WHERE email = ?");
    if (!$stmt) {
        die("❌ Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user['email'];
            $_SESSION['name'] = $user['name'];
            header("Location: ../index.php");
            exit();
        } else {
            $loginError = "❌ Incorrect password.";
        }
    } else {
        $loginError = "❌ Email not found.";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Log In</title>
<style>
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}
body {
  background-color: #f4f4f4;
  font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
  height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
}
.login-container {
  background-color: #ffffff;
  padding: 50px;
  border-radius: 10px;
  box-shadow: 0 8px 20px rgba(0,0,0,0.15);
  width: 100%;
  max-width: 380px;
  transition: box-shadow 0.3s ease;
}
.login-container:hover {
  box-shadow: 0 12px 24px rgba(0,0,0,0.2);
}
h2 {
  text-align: center;
  font-size: 28px;
  margin-bottom: 20px;
  color: #333;
}
label {
  display: block;
  margin-bottom: 6px;
  font-weight: 600;
  font-size: 14px;
  color: #555;
}
input[type="email"],
input[type="password"] {
  width: 100%;
  padding: 12px 15px;
  margin-bottom: 20px;
  border: 1px solid #ccc;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.2s;
}
input[type="email"]:focus,
input[type="password"]:focus {
  border-color: #007BFF;
  outline: none;
}
button {
  width: 100%;
  padding: 14px;
  background-color: #000;
  border: none;
  border-radius: 8px;
  color: #fff;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.3s;
}
button:hover {
  background-color: #0056b3;
}
a {
  display: block;
  margin-top: 16px;
  text-align: center;
  font-size: 14px;
  color: #007BFF;
  text-decoration: none;
  transition: color 0.2s;
}
a:hover {
  color: #0056b3;
}
.error {
  color: red;
  text-align: center;
  margin-bottom: 15px;
  font-size: 14px;
}
</style>
</head>
<body>

<div class="login-container">
    <h2>Log In</h2>
    <?php if (!empty($loginError)): ?>
        <div class="error"><?php echo $loginError; ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <label for="email">Email*</label>
        <input type="email" id="email" name="email" required />

        <label for="password">Password*</label>
        <input type="password" id="password" name="password" required />

        <button type="submit" name="login">Log In</button>
    </form>

    <a href="forgot_password.php">Forgot your password?</a>
    <p style="text-align:center; margin-top:15px;">
        Don't have an account? <a href="signup.php">Sign Up</a>
    </p>
</div>

</body>
</html>
