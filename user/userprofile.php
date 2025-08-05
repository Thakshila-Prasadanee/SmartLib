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

// Fetch current user info (needed for profile image deletion)
$stmt = $conn->prepare("SELECT name, email, gender, nickname, contact, profile_image FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$current_user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   $full_name = $_POST['full_name'] ?? '';
   $email_input = $_POST['email'] ?? '';
   $gender = $_POST['gender'] ?? '';
   $contact = $_POST['contact'] ?? '';
   $nickname = $_POST['nickname'] ?? '';

   // Handle profile image upload if exists
   $profile_image = null;
   $upload_status = "No file selected";
   
   if (isset($_FILES['profile_image'])) {
      $upload_status = "File input detected";
      
      if ($_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
         $upload_status = "File upload OK";
         $fileTmpPath = $_FILES['profile_image']['tmp_name'];
         $fileName = basename($_FILES['profile_image']['name']);
         $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
         
         $upload_status = "Processing file: $fileName (.$ext)";

         $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
         if (in_array($ext, $allowed_ext)) {
            $upload_status = "File extension allowed";
            
            // Create uploads directory if not exist
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
               mkdir($uploadDir, 0755, true);
               $upload_status = "Created upload directory";
            } else {
               $upload_status = "Upload directory exists";
            }

            // Generate unique file name
            $newFileName = uniqid('profile_', true) . '.' . $ext;
            $dest_path = $uploadDir . $newFileName;
            
            $upload_status = "Attempting to move file to: $dest_path";

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
               // Store the relative path from the user directory
               $profile_image = 'uploads/' . $newFileName;
               $upload_status = "SUCCESS: File uploaded as $profile_image";
               
               // Delete old profile image if it exists
               if (!empty($current_user['profile_image']) && file_exists(__DIR__ . '/' . $current_user['profile_image'])) {
                  unlink(__DIR__ . '/' . $current_user['profile_image']);
                  $upload_status .= " | Old image deleted";
               }
            } else {
               $upload_status = "FAILED: Could not move uploaded file";
            }
         } else {
            $upload_status = "ERROR: File extension not allowed ($ext)";
         }
      } else {
         $upload_status = "ERROR: Upload error code " . $_FILES['profile_image']['error'];
      }
   }
   
   // Store upload status for debugging
   $_SESSION['upload_status'] = $upload_status;
   
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

   // Check if update was successful
   if ($stmt->affected_rows > 0) {
      // If email changed, update session user email
      if ($email_input !== $email) {
         $_SESSION['user'] = $email_input;
         $email = $email_input;
      }
      
      // Set success message in session
      $_SESSION['profile_update_success'] = true;
      if ($profile_image !== null) {
         $_SESSION['profile_image_updated'] = true;
      }
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

// Function to generate random color based on user name (same as navbar)
function generateProfileColor($name) {
    $colors = [
        '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7',
        '#DDA0DD', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E9',
        '#F8C471', '#82E0AA', '#F1948A', '#85C1E9', '#F4D03F'
    ];
    $index = ord($name[0]) % count($colors);
    return $colors[$index];
}

// Function to get user initials (same as navbar)
function getUserInitials($name) {
    $words = explode(' ', trim($name));
    if (count($words) >= 2) {
        return strtoupper($words[0][0] . $words[1][0]);
    } else {
        return strtoupper(substr($name, 0, 2));
    }
}

// Simple and reliable profile image display logic
$profile_image_path = null;
$show_colored_circle = true; // Default to circle

if (!empty($user['profile_image'])) {
    // Try to find the image file
    $imagePath = $user['profile_image']; // e.g., "uploads/profile_123.jpg"
    $fullImagePath = __DIR__ . '/' . $imagePath; // Full server path
    
    if (file_exists($fullImagePath)) {
        // File exists, show the real image
        $profile_image_path = $imagePath . '?v=' . time(); // Cache busting with current time
        $show_colored_circle = false;
    }
}

// Generate circle data for fallback
$userName = $user['name'] ?? 'User';
$userColor = generateProfileColor($userName);
$userInitials = getUserInitials($userName);

?>

<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8" />
   <meta name="viewport" content="width=device-width, initial-scale=1" />
   <title>User Profile</title>
   <style>
      /* Global styles */
body {
   font-family: 'Inter', sans-serif;
   background: linear-gradient(135deg, #f3f7fc, #eef1f6);
   margin: 0;
   padding: 20px;
   color: #2f3542;
}

/* Main container */
.profile-container {
   max-width: 900px;
   margin: auto;
   background: #ffffff;
   padding: 35px;
   border-radius: 18px;
   box-shadow: 0 20px 50px rgba(0, 0, 0, 0.05);
   border: 1px solid #e4e9f0;
   transition: transform 0.3s ease;
}

.profile-container:hover {
   transform: scale(1.01);
}

/* Header */
.header {
   display: flex;
   align-items: center;
   justify-content: space-between;
   flex-wrap: wrap;
   margin-bottom: 30px;
}

.header h2 {
   font-size: 28px;
   font-weight: 700;
   color: #34495e;
   margin: 0;
}

/* Profile image */
.profile-img {
   width: 110px;
   height: 110px;
   border-radius: 50%;
   object-fit: cover;
   border: 3px solid #6c63ff;
   cursor: pointer;
   transition: all 0.3s ease;
}

.profile-img:hover {
   transform: scale(1.05);
   border-color: #4e4eff;
}

/* Button styles */
.edit-button {
   background: linear-gradient(135deg, #6c63ff, #4e4eff);
   color: #fff;
   border: none;
   padding: 12px 20px;
   border-radius: 10px;
   font-size: 14px;
   font-weight: 600;
   cursor: pointer;
   transition: background 0.3s ease, transform 0.2s;
   box-shadow: 0 4px 12px rgba(108, 99, 255, 0.3);
}

.edit-button:hover {
   background: linear-gradient(135deg, #5a54e3, #3d3dd9);
   transform: translateY(-2px);
}

/* Grid layout */
.info-grid {
   display: grid;
   grid-template-columns: 1fr 1fr;
   gap: 25px;
   margin-top: 30px;
}

.form-group {
   display: flex;
   flex-direction: column;
}

/* Labels and inputs */
label {
   font-weight: 600;
   margin-bottom: 8px;
   font-size: 15px;
   color: #444;
}

input,
select {
   padding: 12px 16px;
   border: 1px solid #d5dbe0;
   border-radius: 12px;
   font-size: 14px;
   background-color: #f8fafc;
   transition: all 0.3s ease;
   color: #333;
}

input:focus,
select:focus {
   border-color: #6c63ff;
   background-color: #fff;
   box-shadow: 0 0 0 4px rgba(108, 99, 255, 0.15);
   outline: none;
}

/* Save & Cancel button tweaks */
button[type="submit"] {
   background-color: #2ecc71;
   font-weight: 600;
   transition: all 0.3s;
}

button[type="submit"]:hover {
   background-color: #27ae60;
   transform: translateY(-1px);
}

button[type="button"] {
   background-color: #b0bec5;
   font-weight: 600;
}

button[type="button"]:hover {
   background-color: #90a4ae;
}

/* Responsive design */
@media (max-width: 768px) {
   .info-grid {
      grid-template-columns: 1fr;
   }

   .profile-img {
      width: 90px;
      height: 90px;
   }

   .header {
      flex-direction: column;
      align-items: flex-start;
      gap: 12px;
   }
}

/* Global styles */
body {
   font-family: 'Inter', sans-serif;
   background: linear-gradient(135deg, #f3f7fc, #eef1f6);
   margin: 0;
   padding: 20px;
   color: #2f3542;
}

/* Main container */
.profile-container {
   max-width: 900px;
   margin: auto;
   background: #ffffff;
   padding: 35px;
   border-radius: 18px;
   box-shadow: 0 20px 50px rgba(0, 0, 0, 0.05);
   border: 1px solid #e4e9f0;
   transition: transform 0.3s ease;
}

.profile-container:hover {
   transform: scale(1.01);
}

/* Header */
.header {
   display: flex;
   align-items: center;
   justify-content: space-between;
   flex-wrap: wrap;
   margin-bottom: 30px;
}

.header h2 {
   font-size: 28px;
   font-weight: 700;
   color: #34495e;
   margin: 0;
}

/* Profile image */
.profile-img {
   width: 110px;
   height: 110px;
   border-radius: 50%;
   object-fit: cover;
   border: 3px solid #6c63ff;
   cursor: pointer;
   transition: all 0.3s ease;
}

.profile-img:hover {
   transform: scale(1.05);
   border-color: #4e4eff;
}

/* Button styles */
.edit-button {
   background: linear-gradient(135deg, #6c63ff, #4e4eff);
   color: #fff;
   border: none;
   padding: 12px 20px;
   border-radius: 10px;
   font-size: 14px;
   font-weight: 600;
   cursor: pointer;
   transition: background 0.3s ease, transform 0.2s;
   box-shadow: 0 4px 12px rgba(108, 99, 255, 0.3);
}

.edit-button:hover {
   background: linear-gradient(135deg, #5a54e3, #3d3dd9);
   transform: translateY(-2px);
}

/* Grid layout */
.info-grid {
   display: grid;
   grid-template-columns: 1fr 1fr;
   gap: 25px;
   margin-top: 30px;
}

.form-group {
   display: flex;
   flex-direction: column;
}

/* Labels and inputs */
label {
   font-weight: 600;
   margin-bottom: 8px;
   font-size: 15px;
   color: #444;
}

input,
select {
   padding: 12px 16px;
   border: 1px solid #d5dbe0;
   border-radius: 12px;
   font-size: 14px;
   background-color: #f8fafc;
   transition: all 0.3s ease;
   color: #333;
}

input:focus,
select:focus {
   border-color: #6c63ff;
   background-color: #fff;
   box-shadow: 0 0 0 4px rgba(108, 99, 255, 0.15);
   outline: none;
}

/* Save & Cancel button tweaks */
button[type="submit"] {
   background-color: #2ecc71;
   font-weight: 600;
   transition: all 0.3s;
}

button[type="submit"]:hover {
   background-color: #27ae60;
   transform: translateY(-1px);
}

button[type="button"] {
   background-color: #b0bec5;
   font-weight: 600;
}

button[type="button"]:hover {
   background-color: #90a4ae;
}

/* Responsive design */
@media (max-width: 768px) {
   .info-grid {
      grid-template-columns: 1fr;
   }

   .profile-img {
      width: 90px;
      height: 90px;
   }

   .header {
      flex-direction: column;
      align-items: flex-start;
      gap: 12px;
   }
}
/* Light gray background for the container */
.profile-container {
  background: #f0f0f0; /* light gray */
  border-radius: 20px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  padding: 30px;
  max-width: 700px;
  margin: 40px auto;
  font-family: Arial, sans-serif;
  transition: box-shadow 0.3s ease, transform 0.3s ease;
}

/* Hover shadow effect */
.profile-container:hover {
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

/* Header with a different but simple color */
.header h2 {
  font-size: 24px;
  font-weight: bold;
  color: #444; /* dark gray instead of black */
  margin: 0;
}

.header h2::after {
  content: "";
  display: block;
  height: 2px;
  width: 0;
  background-color: #444; /* matching underline */
  margin-top: 8px;
  transition: width 0.3s ease;
}

.header h2:hover::after {
  width: 100%;
}

/* Button with a softer color */
.edit-button {
  padding: 10px 20px;
  background-color: #007BFF; /* blue */
  color: #fff; /* white text */
  border: none;
  border-radius: 8px;
  font-size: 14px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.edit-button:hover {
  background-color: #0056b3; /* darker blue */
}

/* Profile image border color changed to a softer tone */
.profile-img {
  width: 150px;
  height: 150px;
  object-fit: cover;
  border-radius: 50%;
  border: 2px solid #ddd; /* light gray border */
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  cursor: pointer;
  transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
}

.profile-img:hover {
  transform: scale(1.1);
  box-shadow: 0 8px 16px rgba(0,0,0,0.2);
  border-color: #ccc; /* gray border on hover */
}

/* Info grid with light text and slightly darker labels */
.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
  margin-top: 20px;
}

.form-group {
  background-color: #fff; /* white background */
  padding: 12px 16px;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(147, 130, 186, 0.05);
}

.form-group label {
  font-weight: bold;
  font-size: 14px;
  color: #333s; /* medium gray for labels */
  margin-bottom: 6px;
  display: block;
}

.form-group div {
  font-size: 14px;
  color: #2f3542; /* darker gray for text */
}



/* Profile circle for users without profile image */
.profile-circle {
   width: 110px;
   height: 110px;
   border-radius: 50%;
   display: flex;
   align-items: center;
   justify-content: center;
   color: white;
   font-weight: bold;
   font-size: 32px;
   margin: 0 auto;
   cursor: pointer;
   transition: all 0.3s ease;
   border: 3px solid #6c63ff;
}

.profile-circle:hover {
   transform: scale(1.05);
   border-color: #4e4eff;
}

   </style>
   </head>

<body>

   <div class="profile-container">
      <div class="header">
         <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?></h2>
         <button class="edit-button" onclick="toggleEdit()">Edit</button>
      </div>

      <?php if (isset($_SESSION['profile_update_success'])): ?>
         <div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 15px 0; text-align: center;">
            ✅ Profile updated successfully!
            <?php if (isset($_SESSION['profile_image_updated'])): ?>
               Profile image has been updated.
            <?php endif; ?>
         </div>
         <?php 
            unset($_SESSION['profile_update_success']);
            unset($_SESSION['profile_image_updated']);
         ?>
      <?php endif; ?>

      <!-- Simple debug info -->
      <div style="background-color: #e7f3ff; color: #0066cc; padding: 10px; border-radius: 5px; margin: 15px 0; font-size: 12px;">
         <strong>Debug:</strong> 
         Profile Image in DB: <?php echo !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'None'; ?> | 
         Showing: <?php echo $show_colored_circle ? 'Colored Circle' : 'Real Image'; ?>
         <?php if (!$show_colored_circle): ?>
            | Image Path: <?php echo htmlspecialchars($profile_image_path); ?>
         <?php endif; ?>
         <br><strong>PHP Upload Settings:</strong> 
         Max Upload: <?php echo ini_get('upload_max_filesize'); ?> | 
         Max Post: <?php echo ini_get('post_max_size'); ?> | 
         File Uploads: <?php echo ini_get('file_uploads') ? 'Enabled' : 'Disabled'; ?>
         <?php if (isset($_SESSION['upload_status'])): ?>
            <br><strong>Upload Status:</strong> <?php echo htmlspecialchars($_SESSION['upload_status']); ?>
            <?php unset($_SESSION['upload_status']); ?>
         <?php endif; ?>
      </div>

      <!-- <div style="text-align:center; margin-top:15px;">
         <img id="profileImg" src="<?php echo htmlspecialchars($profile_image_path); ?>" alt="Profile Image"
            class="profile-img" title="" />
         <input type="file" id="profileImageInput" name="profile_image" accept="image/*" style="display:none;">
      </div> -->

      <div style="text-align:center; margin-top:15px;">
         <?php if (!$show_colored_circle && !empty($profile_image_path)): ?>
            <!-- Display actual profile image -->
            <img id="profileImg" src="<?php echo htmlspecialchars($profile_image_path); ?>" alt="Profile Image"
               class="profile-img" title="Click to change profile image" onclick="triggerImageInput()" />
         <?php else: ?>
            <!-- Display colored circle with initials -->
            <div id="profileImg" class="profile-circle" 
                 style="background-color: <?php echo $userColor; ?>;" 
                 title="Click to add profile image" onclick="triggerImageInput()">
               <?php echo $userInitials; ?>
            </div>
         <?php endif; ?>
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
         
         <!-- File input for profile image -->
         <div class="form-group" style="text-align: center; margin: 20px 0;">
            <label for="profile_image">Profile Image</label>
            <input type="file" id="profileImageInput" name="profile_image" accept="image/*" style="margin-top: 10px;">
         </div>
         
         <div style="margin-top:15px;">
            <button type="submit" class="edit-button" style="background-color:#28a745;">Save Changes</button>
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

      // Show preview of selected image
      document.getElementById('profileImageInput').addEventListener('change', function (event) {
         const profileElement = document.getElementById('profileImg');
         const file = event.target.files[0];
         
         if (file) {
            // Create a new image element to replace the current profile element
            const newImg = document.createElement('img');
            newImg.id = 'profileImg';
            newImg.src = URL.createObjectURL(file);
            newImg.alt = 'Profile Image';
            newImg.className = 'profile-img';
            newImg.title = 'Click to change profile image';
            newImg.onclick = function() { triggerImageInput(); };
            
            // Replace the current profile element (whether it's an image or circle) with the new image
            profileElement.parentNode.replaceChild(newImg, profileElement);
         }
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




