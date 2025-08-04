<?php
// Debug script to check profile image paths and files
session_start();

echo "<h3>Profile Image Debug Information</h3>";

if (isset($_SESSION['user'])) {
    echo "<p><strong>Current User:</strong> " . $_SESSION['user'] . "</p>";
    
    // Database connection
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $db_name = 'smartlib';
    $conn = mysqli_connect($host, $username, $password, $db_name);
    
    if ($conn) {
        $stmt = $conn->prepare("SELECT profile_image FROM users WHERE email = ?");
        $stmt->bind_param("s", $_SESSION['user']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows === 1) {
            $user_data = $result->fetch_assoc();
            $profile_image_path = $user_data['profile_image'];
            
            echo "<p><strong>Database Profile Image Path:</strong> " . ($profile_image_path ? $profile_image_path : 'NULL') . "</p>";
            
            if ($profile_image_path) {
                // Check different path combinations
                $paths_to_check = [
                    $profile_image_path,
                    'user/' . $profile_image_path,
                    '../user/' . $profile_image_path,
                    __DIR__ . '/' . $profile_image_path,
                    __DIR__ . '/user/' . $profile_image_path
                ];
                
                echo "<h4>File Existence Check:</h4>";
                foreach ($paths_to_check as $path) {
                    $exists = file_exists($path);
                    echo "<p><strong>$path:</strong> " . ($exists ? "✅ EXISTS" : "❌ NOT FOUND") . "</p>";
                }
                
                // List actual files in user/uploads directory
                $upload_dir = __DIR__ . '/user/uploads/';
                if (is_dir($upload_dir)) {
                    echo "<h4>Files in user/uploads/:</h4>";
                    $files = scandir($upload_dir);
                    foreach ($files as $file) {
                        if ($file != '.' && $file != '..') {
                            echo "<p>📁 $file</p>";
                        }
                    }
                }
            }
        }
        
        $stmt->close();
        mysqli_close($conn);
    } else {
        echo "<p>❌ Database connection failed</p>";
    }
} else {
    echo "<p>❌ No user logged in</p>";
}
?>
