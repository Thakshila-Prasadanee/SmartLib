<?php
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
            <a class="navbar-brand" href="#"><img style="height: 90px; margin-top: -20px;" src="../images/logo.svg" alt="SmartLib Logo"></a>
            <button style="margin-top: -20px;" class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenue">
              <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end mx-4" >
              <ul class="navbar-nav" style="margin-top: -20px;">
                <li class="nav-item">
                  <a class="nav-link" href="#"><span style="color: white;">Home</span></a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="#"><span style="color: white;">History</span></a>
                </li>
                <li class="nav-item">
                <!-- Search -->
                <div class="d-flex mb-1"   role="search">
                  <input class="form-control me-2" type="search" style="padding-left:50px; border-radius: 50px; width: 400px; background-color:#524E4E; border:2px solid white;" placeholder="Search ..." aria-label="Search"/>
                  <i class="fa-solid fa-magnifying-glass " style="margin-top:10px; width:50px;color:#ffffff;  position: absolute; "></i>
                </div>
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
                <div class="d-flex mb-1"   role="search">
                  <input class="form-control me-2" type="search" style="padding-left:50px; border-radius: 50px; width: 400px; background-color:#524E4E; border:2px solid white;" placeholder="Search ..." aria-label="Search"/>
                  <i class="fa-solid fa-magnifying-glass " style="margin-top:10px; width:50px;color:#ffffff;  position: absolute; "></i>
                </div>
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
          <img src="<?php echo htmlspecialchars($book['image_url']); ?>" style="width: 100%;" alt="Book Cover">
        </div>
        <div class="col-md-6" >
          <div class="border p-4" style="background-color:#8F8484; border-radius: 10px;">
            <h1><?php echo htmlspecialchars($book['title']); ?></h1>
            <h4><?php echo htmlspecialchars($_GET['category']); ?></h4>
          </div>
          
        </div>
      </div>
    </div>
  </section>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
</body>
</html>