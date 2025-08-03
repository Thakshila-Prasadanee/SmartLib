<?php
// Initialize variables
$name = $email = $password = "";
$nameErr = $emailErr = $passwordErr = "";
$successMsg = "";

// Database connection settings
$host = "localhost";
$db = "smartlib";
$dbUser = "root";
$dbPass = "";

// Create connection
$conn = new mysqli($host, $dbUser, $dbPass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate Name
    if (empty(trim($_POST["name"]))) {
        $nameErr = "Name is required.";
    } else {
        $name = trim($_POST["name"]);
    }

    // Validate Email
    if (empty(trim($_POST["email"]))) {
        $emailErr = "Email is required.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $emailErr = "Invalid email format.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Validate Password
    if (empty(trim($_POST["password"]))) {
        $passwordErr = "Password is required.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $passwordErr = "Password must be at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    }

    // If no errors, insert into database
    if (empty($nameErr) && empty($emailErr) && empty($passwordErr)) {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $hashedPassword);

        if ($stmt->execute()) {
            $successMsg = "✅ Registration successful!";
            $name = $email = $password = "";
        } else {
            $emailErr = "❌ Email already exists or error occurred.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Sign Up</title>
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
.signup-container {
  background-color: #fff;
  padding: 50px;
  border-radius: 10px;
  box-shadow: 0 8px 20px rgba(0,0,0,0.15);
  width: 100%;
  max-width: 400px;
  transition: box-shadow 0.3s ease;
}
.signup-container:hover {
  box-shadow: 0 12px 24px rgba(0,0,0,0.2);
}
h2 {
  text-align: center;
  font-size: 28px;
  margin-bottom: 20px;
  font-weight: bold;
}
.error {
  color: red;
  font-size: 13px;
  margin-top: 5px;
}
.success {
  color: green;
  text-align: center;
  font-size: 14px;
  margin-bottom: 15px;
}
label {
  display: block;
  margin-bottom: 6px;
  font-weight: 600;
  font-size: 14px;
  color: #555;
}
input[type="text"],
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
input[type="text"]:focus,
input[type="email"]:focus,
input[type="password"]:focus {
  border-color: #007bff;
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
  background-color: #333;
}
a {
  display: block;
  margin-top: 16px;
  text-align: center;
  font-size: 14px;
  color: #007bff;
  text-decoration: none;
}
a:hover {
  color: #0056b3;
}
</style>
</head>
<body>
<div class="signup-container">
    <h2>Sign Up</h2>
    <?php if ($successMsg): ?><div class="success"><?php echo $successMsg; ?></div><?php endif; ?>
    <form method="post" action="">
        <label for="name">Name*</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
        <?php if ($nameErr): ?><div class="error"><?php echo $nameErr; ?></div><?php endif; ?>

        <label for="email">Email*</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        <?php if ($emailErr): ?><div class="error"><?php echo $emailErr; ?></div><?php endif; ?>

        <label for="password">Password*</label>
        <input type="password" id="password" name="password" required>
        <?php if ($passwordErr): ?><div class="error"><?php echo $passwordErr; ?></div><?php endif; ?>

        <button type="submit">Sign Up</button>
    </form>
    <a href="/LibProj/SmartLib/auth/login.php">Already have an account? Log In</a>
</div>
</body>
</html>
