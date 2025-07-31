<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="media.css">
</head>
<body>
  <!-- navbar -->
  <header class="container-fluid bg-primary m-0 p-0" style="height: 70px;">
    <div class="container ">
      <div class="row  m-0 mb-2 p-0">
        <div class="col-12">
          <nav class="navbar navbar-expand-lg" data-bs-theme="dark" >
            <a class="navbar-brand" href="#"><img style="height: 90px; margin-top: -20px;" src="../images/logo.svg" alt="SmartLib Logo"></a>
            <button style="margin-top: -20px;" class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
              <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
              <ul class="navbar-nav" style="margin-top: -20px;">
                <li class="nav-item">
                  <a class="nav-link" href="#"><span style="color: black;">Home</span></a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="#"><span style="color: black;">History</span></a>
                </li>
                <li class="nav-item">
                <!-- Search -->
                <form class="d-flex" role="search">
                  <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search"/>
                  <button class="btn btn-outline-success" type="submit">Search</button>
                </form>
                <!-- Search -->
                </li>
                <li class="nav-item">
                  <input class="btn btn-secondary" style="background-color: #8F8484; color: white;" type="button" value="Generate history pdf">
                  <input class="btn btn-secondary " style="background-color: #FF000D; color: white;" type="button" value="LogOut">
                </li>
              </ul>
            </div>
          </nav>
        </div>
      </div>
    </div>
  </header>
<!-- navbar -->

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
</body>
</html>