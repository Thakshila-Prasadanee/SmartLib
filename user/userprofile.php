<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user'])) {
   header("Location: login.php");
   exit();
}

// DB connection - adjust with your own config
$host = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "smartlib";

$conn = new mysqli($host, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
   die("Connection failed: " . $conn->connect_error);
}

$email = $_SESSION['user'];

// Make sure all needed columns exist (you can remove this if DB is already ready)
$columnsToAdd = [
   'gender' => "VARCHAR(10) DEFAULT NULL",
   'nickname' => "VARCHAR(50) DEFAULT NULL",
   'contact' => "VARCHAR(20) DEFAULT NULL",
   'profile_image' => "VARCHAR(255) DEFAULT NULL"
];
$result = $conn->query("SHOW COLUMNS FROM users");
$existingColumns = [];
while ($row = $result->fetch_assoc()) {
   $existingColumns[] = $row['Field'];
}
foreach ($columnsToAdd as $column => $type) {
   if (!in_array($column, $existingColumns)) {
      $conn->query("ALTER TABLE users ADD COLUMN $column $type");
   }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   $full_name = $_POST['full_name'] ?? '';
   $email_input = $_POST['email'] ?? '';
   $gender = $_POST['gender'] ?? '';
   $contact = $_POST['contact'] ?? '';
   $nickname = $_POST['nickname'] ?? '';

   // Handle profile image upload if exists
   $profile_image = null;
   if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
      $fileTmpPath = $_FILES['profile_image']['tmp_name'];
      $fileName = basename($_FILES['profile_image']['name']);
      $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

      $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
      if (in_array($ext, $allowed_ext)) {
         // Create uploads directory if not exist
         $uploadDir = __DIR__ . '/uploads/';
         if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
         }

         // Generate unique file name
         $newFileName = uniqid('profile_', true) . '.' . $ext;
         $dest_path = $uploadDir . $newFileName;

         if (move_uploaded_file($fileTmpPath, $dest_path)) {
            $profile_image = 'uploads/' . $newFileName;
         }
      }
   }

   // Update query
   if ($profile_image !== null) {
      $stmt = $conn->prepare("UPDATE users SET name=?, email=?, gender=?, contact=?, nickname=?, profile_image=? WHERE email=?");
      $stmt->bind_param("sssssss", $full_name, $email_input, $gender, $contact, $nickname, $profile_image, $email);
   } else {
      // Do not update profile_image if no new upload
      $stmt = $conn->prepare("UPDATE users SET name=?, email=?, gender=?, contact=?, nickname=? WHERE email=?");
      $stmt->bind_param("ssssss", $full_name, $email_input, $gender, $contact, $nickname, $email);
   }

   $stmt->execute();

   // If email changed, update session user email
   if ($email_input !== $email) {
      $_SESSION['user'] = $email_input;
      $email = $email_input;
   }

   // Redirect to avoid resubmission
   header("Location: " . $_SERVER['PHP_SELF']);
   exit();
}

// Fetch user info from DB
$stmt = $conn->prepare("SELECT name, email, gender, nickname, contact, profile_image FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
   echo "User not found.";
   exit();
}

// Use default profile image if none set
$profile_image_path = !empty($user['profile_image']) ? $user['profile_image'] : 'assets/profile.jpg';

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

      .header h2 {
         margin: 0;
      }

      .profile-img {
         width: 80px;
         height: 80px;
         border-radius: 50%;
         object-fit: cover;
         border: 2px solid #ddd;
         cursor: pointer;
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
   </style>
</head>

<body>

   <div class="profile-container">
      <div class="header">
         <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?></h2>
         <button class="edit-button" onclick="toggleEdit()">Edit</button>
      </div>

      <!-- <div style="text-align:center; margin-top:15px;">
         <img id="profileImg" src="<?php echo htmlspecialchars($profile_image_path); ?>" alt="Profile Image"
            class="profile-img" title="" />
         <input type="file" id="profileImageInput" name="profile_image" accept="image/*" style="display:none;">
      </div> -->

      <div style="text-align:center; margin-top:15px;">
         <img id="profileImg" src="<?php echo htmlspecialchars($profile_image_path); ?>" alt="Profile Image"
            class="profile-img" title="" onclick="triggerImageInput()" />

         <input type="file" id="profileImageInput" name="profile_image" accept="image/*" style="display:none;"
            form="profileForm">
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
      <form method="post" id="profileForm" enctype="multipart/form-data" style="display:none; margin-top:20px;">
         <div class="info-grid">
            <div class="form-group">
               <label for="full_name">Full Name</label>
               <input type="text" id="full_name" name="full_name"
                  value="<?php echo htmlspecialchars($user['name']); ?>">
            </div>
            <div class="form-group">
               <label for="email">Email</label>
               <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>
            <div class="form-group">
               <label for="gender">Gender</label>
               <select id="gender" name="gender">
                  <option value="">-- Select --</option>
                  <option value="Male" <?php echo ($user['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                  <option value="Female" <?php echo ($user['gender'] == 'Female') ? 'selected' : ''; ?>>Female
                  </option>
                  <option value="Other" <?php echo ($user['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
               </select>
            </div>
            <div class="form-group">
               <label for="contact">Contact</label>
               <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($user['contact']); ?>">
            </div>
            <div class="form-group">
               <label for="nickname">Nick Name</label>
               <input type="text" id="nickname" name="nickname"
                  value="<?php echo htmlspecialchars($user['nickname']); ?>">
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
         const imageInput = document.getElementById('profileImageInput');

         const isEditing = form.style.display === 'block';

         if (isEditing) {
            form.style.display = 'none';
            preview.style.display = 'grid';
            imageInput.style.display = 'none';
         } else {
            form.style.display = 'block';
            preview.style.display = 'none';
            imageInput.style.display = 'inline-block';  // Show image input only in edit mode
         }
      }

      function triggerImageInput() {
         const form = document.getElementById('profileForm');
         if (form.style.display === 'block') {
            document.getElementById('profileImageInput').click();
         }
      }

      // Optional: show preview of selected image
      document.getElementById('profileImageInput').addEventListener('change', function (event) {
         const img = document.getElementById('profileImg');
         img.src = URL.createObjectURL(event.target.files[0]);
      });
   </script>

   <!-- <script>
      const profileImg = document.getElementById('profileImg');
      const profileImageInput = document.getElementById('profileImageInput');
      const form = document.getElementById('profileForm');
      const preview = document.getElementById('profilePreview');

      function toggleEdit() {
         const isFormVisible = window.getComputedStyle(form).display === 'block';

         if (isFormVisible) {
            // Switch to preview mode
            form.style.display = 'none';
            preview.style.display = 'grid';

            // Disable clicking on profile image
            profileImg.style.pointerEvents = 'none';
            profileImg.title = '';
         } else {
            // Switch to edit mode
            form.style.display = 'block';
            preview.style.display = 'none';

            // Enable clicking on profile image
            profileImg.style.pointerEvents = 'auto';
            profileImg.title = 'Click to change profile image';
         }
      }

      // When user clicks profile image, trigger file input click (only in edit mode)
      profileImg.addEventListener('click', () => {
         if (window.getComputedStyle(form).display === 'block') {
            profileImageInput.click();
         }
      });

      // Preview selected image immediately
      profileImageInput.addEventListener('change', (e) => {
         const file = e.target.files[0];
         if (file) {
            const reader = new FileReader();
            reader.onload = function (evt) {
               profileImg.src = evt.target.result;
            };
            reader.readAsDataURL(file);
         }
      });

      // Start in preview mode: disable image click
      window.onload = () => {
         profileImg.style.pointerEvents = 'none';
      };
   </script> -->

</body>

</html>