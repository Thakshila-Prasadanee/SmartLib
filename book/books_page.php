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

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Books Page</title>
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
                  <?php 
                    if(isset($_SESSION['name'])){
                        echo '<a class="nav-link" href="history_page.php"><span style="color: white;">History</span></a>';
                    }else{
                        echo '<a class="nav-link" href="#" onclick="alert(\'Please Log in First\'); return false;"><span style="color: white;">History</span></a>';
                    }
                    ?>
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

<section class="container-fluid" >
  <div class =" container">
    <div class="row justify-content-center">
      <div class="col-12">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#fairy_tales" type="button" role="tab" aria-controls="home-tab-pane" aria-selected="true">Fairy Tales</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#little_life_stories" type="button" role="tab" aria-controls="profile-tab-pane" aria-selected="false">Little Life Stories</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#a_pet_tale" type="button" role="tab" aria-controls="contact-tab-pane" aria-selected="false">A Pet's Tale</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#adventure" type="button" role="tab" aria-controls="contact-tab-pane" aria-selected="false">Adventure</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#mystery" type="button" role="tab" aria-controls="contact-tab-pane" aria-selected="false">Mystery</button>
          </li>
        </ul>

        <div class="tab-content" id="myTabContent">
          <div class="tab-pane fade show active" id="fairy_tales" role="tabpanel" aria-labelledby="home-tab" tabindex="0">

            <div class="container mt-4">
              <div class="row">
                <?php
                $query = "SELECT image_url , book_id FROM `books` WHERE category_id = 3;";
                $result = mysqli_query($conn, $query);

                if ($result && mysqli_num_rows($result) > 0) {
                  while ($x = mysqli_fetch_assoc($result)) {
                    if (isset($_SESSION['name'])) {
                         echo "<div class='col-md-3 mb-4'>
                                 <div class='card h-100'>
                                     <a href='book_details_page.php?book_id=".$x['book_id']."&category=Fairy Tales'>
                                       <img src='".$x['image_url']."' class='card-img-top' alt='Book Cover'>
                                     </a>
                                 </div>
                               </div>";
                     } else {
                         echo "<div class='col-md-3 mb-4'>
                                 <div class='card h-100'>
                                     <a href='#' onclick=\"alert('Please Log in First'); return false;\">
                                       <img src='".$x['image_url']."' class='card-img-top' alt='Book Cover'>
                                     </a>
                                 </div>
                               </div>";
                     }
                  }
                } else {
                  // Show 404 image if no results
                  echo "<div class='col-md-12'>
                          <div class='card h-100'>
                            <img src='https://ik.imagekit.io/nimantha/Smartlib/404%20image.jpg?updatedAt=1754041781388' 
                                 class='card-img-top' alt='No Books Found'>
                          </div>
                        </div>";
                }
                ?>
              </div>
            </div>
          
          </div>
          <div class="tab-pane fade" id="little_life_stories" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">

            <div class="container mt-4">
              <div class="row">
                <?php
                $query = "SELECT image_url ,book_id FROM `books` WHERE category_id = 4;";
                $result = mysqli_query($conn, $query);

                if ($result && mysqli_num_rows($result) > 0) {
                  while ($x = mysqli_fetch_assoc($result)) {
                    if (isset($_SESSION['name'])) {
                         echo "<div class='col-md-3 mb-4'>
                                 <div class='card h-100'>
                                   <a href='book_details_page.php?book_id=".$x['book_id']."&category=Little Life Stories'>
                                     <img src='".$x['image_url']."' class='card-img-top' alt='Book Cover'>
                                   </a>
                                 </div>
                               </div>";
                     } else {
                         echo "<div class='col-md-3 mb-4'>
                                 <div class='card h-100'>
                                   <a href='#' onclick=\"alert('Please Log in First'); return false;\">
                                     <img src='".$x['image_url']."' class='card-img-top' alt='Book Cover'>
                                   </a>
                                 </div>
                               </div>";
                     }

                  }
                } else {
                  // Show 404 image if no results
                  echo "<div class='col-md-12'>
                          <div class='card h-100'>
                            <img src='https://ik.imagekit.io/nimantha/Smartlib/404%20image.jpg?updatedAt=1754041781388' 
                                 class='card-img-top' alt='No Books Found'>
                          </div>
                        </div>";
                }
                ?>
              </div>
            </div>

          </div>
          <div class="tab-pane fade" id="a_pet_tale" role="tabpanel" aria-labelledby="contact-tab" tabindex="0">

            <div class="container mt-4">
              <div class="row">
                <?php
                $query = "SELECT image_url , book_id FROM `books` WHERE category_id = 2;";
                $result = mysqli_query($conn, $query);

                if ($result && mysqli_num_rows($result) > 0) {
                  while ($x = mysqli_fetch_assoc($result)) {
                    if (isset($_SESSION['name'])) {
                          echo "<div class='col-md-3 mb-4'>
                                  <div class='card h-100'>
                                    <a href='book_details_page.php?book_id=".$x['book_id']."&category=A Pet&#39;s Tale'>
                                      <img src='".$x['image_url']."' class='card-img-top' alt='Book Cover'>
                                    </a>
                                  </div>
                                </div>";
                      } else {
                          echo "<div class='col-md-3 mb-4'>
                                  <div class='card h-100'>
                                    <a href='#' onclick=\"alert('Please Log in First'); return false;\">
                                      <img src='".$x['image_url']."' class='card-img-top' alt='Book Cover'>
                                    </a>
                                  </div>
                                </div>";
                      }

                  }
                } else {
                  // Show 404 image if no results
                  echo "<div class='col-md-12'>
                          <div class='card h-100'>
                            <img src='https://ik.imagekit.io/nimantha/Smartlib/404%20image.jpg?updatedAt=1754041781388' 
                                 class='card-img-top' alt='No Books Found'>
                          </div>
                        </div>";
                }
                ?>
              </div>
            </div>

          </div>
          <div class="tab-pane fade" id="adventure" role="tabpanel" aria-labelledby="disabled-tab" tabindex="0">

            <div class="container mt-4">
              <div class="row">
                <?php
                $query = "SELECT image_url , book_id FROM `books` WHERE category_id = 1;";
                $result = mysqli_query($conn, $query);

                if ($result && mysqli_num_rows($result) > 0) {
                  while ($x = mysqli_fetch_assoc($result)) {
                    if (isset($_SESSION['name'])) {
                          echo "<div class='col-md-3 mb-4'>
                                  <div class='card h-100'>
                                    <a href='book_details_page.php?book_id=".$x['book_id']."&category=Adventure'>
                                      <img src='".$x['image_url']."' class='card-img-top' alt='Book Cover'>
                                    </a>
                                  </div>
                                </div>";
                      } else {
                          echo "<div class='col-md-3 mb-4'>
                                  <div class='card h-100'>
                                    <a href='#' onclick=\"alert('Please Log in First'); return false;\">
                                      <img src='".$x['image_url']."' class='card-img-top' alt='Book Cover'>
                                    </a>
                                  </div>
                                </div>";
                      }

                  }
                } else {
                  // Show 404 image if no results
                  echo "<div class='col-md-12'>
                          <div class='card h-100'>
                            <img src='https://ik.imagekit.io/nimantha/Smartlib/404%20image.jpg?updatedAt=1754041781388' 
                                 class='card-img-top' alt='No Books Found'>
                          </div>
                        </div>";
                }
                ?>
              </div>
            </div>

          </div>
          <div class="tab-pane fade" id="mystery" role="tabpanel" aria-labelledby="disabled-tab" tabindex="0">

            <div class="container mt-4">
              <div class="row">
                <?php
                $query = "SELECT image_url , book_id FROM `books` WHERE category_id = 5;";
                $result = mysqli_query($conn, $query);

                if ($result && mysqli_num_rows($result) > 0) {
                  while ($x = mysqli_fetch_assoc($result)) {
                    if (isset($_SESSION['name'])) {
                          echo "<div class='col-md-3 mb-4'>
                                  <div class='card h-100'>
                                    <a href='book_details_page.php?book_id=".$x['book_id']."&category=Mystery'>
                                      <img src='".$x['image_url']."' class='card-img-top' alt='Book Cover'>
                                    </a>
                                  </div>
                                </div>";
                      } else {
                          echo "<div class='col-md-3 mb-4'>
                                  <div class='card h-100'>
                                    <a href='#' onclick=\"alert('Please Log in First'); return false;\">
                                      <img src='".$x['image_url']."' class='card-img-top' alt='Book Cover'>
                                    </a>
                                  </div>
                                </div>";
                      }

                  }
                } else {
                  // Show 404 image if no results
                  echo "<div class='col-md-12'>
                          <div class='card h-100'>
                            <img src='https://ik.imagekit.io/nimantha/Smartlib/404%20image.jpg?updatedAt=1754041781388' 
                                 class='card-img-top' alt='No Books Found'>
                          </div>
                        </div>";
                }
                ?>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </div>
</section>





  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
</body>
</html>