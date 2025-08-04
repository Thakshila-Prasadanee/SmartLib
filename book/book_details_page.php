<?php
session_start();
$host = 'localhost';
$username = 'root';
$password = '';
$db_name = 'smartlib';
$conn = mysqli_connect($host, $username, $password, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_GET['book_id']) && is_numeric($_GET['book_id'])) {
    $book_id = intval($_GET['book_id']); // prevent SQL injection
    $query = "SELECT * FROM books WHERE book_id = $book_id";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }

    $book = mysqli_fetch_assoc($result); // ✅ fetch row as an associative array

    if (!$book) {
        die("Book not found!");
    }
} else {
    die("Invalid book ID");
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book Details Page</title>
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

  <section class="container-fluid my-md-4 my-4">
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <img src="<?php echo htmlspecialchars($book['image_url']); ?>" style="width: 100%; height: 100%;" alt="Book Cover">
        </div>
        <div class="col-md-6" >
          <div class="border p-4" style="background-color:#8F8484; border-radius: 10px;">
            <h1><?php echo htmlspecialchars($book['title']); ?></h1>
            <h4><?php echo htmlspecialchars($_GET['category']); ?></h4>
          </div>
          <div style="margin-top: 20px; width: 100%;">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($book['isbn']); ?>" alt="QR Code" style="width: 100%; height: auto;"/>
          </div>
          <div  style="margin-top: 20px; width: 100%;">
            <p><?php echo $book['description'] ?></p>
            <div class="row">
                <div class="col-md-6">
                <button class="btn" style="border-radius: 10px; background-color: #524E4E; color: white;"><?php echo ucfirst($book['status']); ?></button>
              </div>
              <div class="col-md-6">
                <p>Written by : <span style="color:#D24BF0;"><?php echo $book['author']; ?></span></p>
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