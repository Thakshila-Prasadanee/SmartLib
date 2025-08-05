<?php 
    session_start();
    $host = 'localhost';
    $username = 'root';
    $password= '';
    $db_name = 'smartlib';
    $conn = mysqli_connect($host, $username, $password, $db_name);
    if(!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
?>

<?php 
  $query = "SELECT user_id FROM `users` WHERE name = '" . $_SESSION['name'] . "';";
  $result = mysqli_query($conn, $query);
  if ($result) {
      $row = mysqli_fetch_assoc($result);
      $user_id = $row['user_id'];
  } else {
      echo "Error: " . mysqli_error($conn);
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>History Page</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css">
  
</head>
<body>
  <!-- navbar -->
  <header class="container-fluid  m-0 p-0" style="height: 70px; background-color:#524E4E;">
    <div class="container ">
      <div class="row  m-0 mb-2 p-0">
        <div class="col-12">
          <nav class="navbar navbar-expand-lg" data-bs-theme="dark" >
            <a class="navbar-brand" href="../"><img style="height: 90px; margin-top: -20px;" src="../images/logo.svg" alt="SmartLib Logo"></a>
            <button style="margin-top: -20px;" class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenue">
              <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end mx-4" >
              <ul class="navbar-nav" style="margin-top: -20px;">
                <li class="nav-item">
                  <a class="nav-link" href="books_page.php"><span style="color: white;">Home</span></a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="history_page.php"><span style="color: white;">History</span></a>
                </li>
                <li class="nav-item">
                <!-- Search -->
                <form action="search_page.php" method="GET" class="d-flex mb-1" role="search" style="position: relative;">
                    <input 
                      class="form-control me-2" 
                      type="search" 
                      name="query"
                      style="padding-left:50px; border-radius: 50px; width: 400px; background-color:#524E4E; border:2px solid white; color: white;" 
                      placeholder="Search ..." 
                      aria-label="Search"
                    />
                    <button type="submit" style="position: absolute; top: 50%; left: 15px; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                      <i class="fa-solid fa-magnifying-glass" style="color:#ffffff;"></i>
                    </button>
                  </form>
                <!-- Search -->
                </li>
                <li class="nav-item">
                  <?php
                    if (isset($_SESSION['name'])) {
                        echo "<input class='btn btn-secondary' 
                                    style='background-color: #8F8484; color: white; border:2px solid white;' 
                                    type='button' 
                                    value='Generate history pdf' 
                                    onclick=\"window.open('generate_history_pdf.php', '_blank')\">";
                    } else {
                        echo "<input class='btn btn-secondary' 
                                    style='background-color: #8F8484; color: white; border:2px solid white;' 
                                    type='button' 
                                    value='Generate history pdf' 
                                    onclick=\"alert('Please Log in First');\">";
                    }
                  ?>

                  <?php
                    if (isset($_SESSION['name'])) {
                        echo "<input class='btn btn-secondary' 
                                    style='background-color: #FF000D; color: white; border:2px solid white;' 
                                    type='button' 
                                    value='LogOut' 
                                    onclick=\"window.location.href='logout.php'\">";
                    } else {
                        echo "<input class='btn btn-secondary' 
                                    style='background-color: #FF000D; color: white; border:2px solid white;' 
                                    type='button' 
                                    value='LogOut' 
                                    onclick=\"alert('You are not logged in');\">";
                    }
                  ?>
                </li>
              </ul>
            </div>
          </nav>

        <div class="offcanvas offcanvas-start" style="background-color:#524E4E;" tabindex="-1" id="mobileMenue" aria-labelledby="offcanvasExampleLabel">
          <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasExampleLabel">
              <a class="navbar-brand" href="#"><img style="height: 90px;  margin-top: -20px;" src="../images/logo.svg" alt="SmartLib Logo"></a>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
          </div>
           <div class="offcanvas-body">
             <ul class="navbar-nav" style="margin-top: -20px;">
                <li class="nav-item">
                  <a class="nav-link" href="#"><span style="color: white;">Home</span></a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="#"><span style="color: white;">History</span></a>
                </li>
                <li class="nav-item">
                <!-- Search -->
                <form action="search_page.php" method="GET" class="d-flex mb-1" role="search" style="position: relative;">
                    <input 
                      class="form-control me-2" 
                      type="search" 
                      name="query"
                      style="padding-left:50px; border-radius: 50px; width: 400px; background-color:#524E4E; border:2px solid white; color: white;" 
                      placeholder="Search ..." 
                      aria-label="Search"
                    />
                    <button type="submit" style="position: absolute; top: 50%; left: 15px; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                      <i class="fa-solid fa-magnifying-glass" style="color:#ffffff;"></i>
                    </button>
                  </form>
                <!-- Search -->
                </li>
                <li class="nav-item">
                  <input class="btn btn-secondary" style="background-color: #8F8484; color: white; border:2px solid white;" type="button" value="Generate history pdf">
                  <input class="btn btn-secondary " style="background-color: #FF000D; color: white; border:2px solid white;" type="button" value="LogOut">
                </li>
              </ul>
           </div>
        </div>

        </div>
      </div>
    </div>
  </header>
<!-- navbar -->

    <div class="container-fluid">
      <div class="row">

          <?php 
            $query = "SELECT b.image_url,b.title,c.name ,b.description, r.borrow_date,r.return_date ,r.status FROM books AS b JOIN borrow_records AS r ON b.book_id = r.book_id JOIN categories AS c ON c.category_id = b.category_id JOIN users AS u ON u.user_id = r.user_id WHERE u.user_id = '$user_id' ;";
            $result = mysqli_query($conn, $query);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<div class='d-flex flex-column flex-md-row border g-3 mb-3 p-3'  >";
                        // Left side: image
                        echo "<div class='col-md-3 col-12'>";
                        echo "<img src='" . $row['image_url'] . "' alt='" . $row['title'] . "' class='img-fluid ms-5 rounded' style='height:300px;'  />";
                        echo "</div>";
                        // Right side: details
                        echo "<div class='col-md-9 col-12 mt-3 mt-md-0 ms-md-4' style='align-self: center;'>";
                        echo "<h2>" . $row['title'] . "</h2>";
                        echo "<p>Category: " . $row['name'] . "</p>";
                        echo "<p>Description: " . $row['description'] . "</p>";
                        echo "<p>Borrowed on: " . $row['borrow_date'] . "</p>";
                        echo "<p>Returned on: " . $row['return_date'] . "</p>";
                        echo "<p>Status: " . $row['status'] . "</p>";
                        echo "</div>";
                    echo "</div>";

                }
            } else {
                echo "Error: " . mysqli_error($conn);
            }
          ?>
        
      </div>
    </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
</body>
</html>