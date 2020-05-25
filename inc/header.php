<!DOCTYPE html>
<html lang="cs">

<head>
    <title>Knihovna - semestrální práce z předmětu 4IZ278</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
</head>

<body>
    <header class="container bg-info">
        <div class="row">
            <div class="col-6">
                <h1 class="text-white py-4 px-2">Knihovna</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-6">
                <nav class="navbar navbar-expand-sm">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link text-light" href="index.php">Domů</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="catalog.php">Katalog knih</a>
                        </li>
                        <?php 
                          if (!empty($_SESSION['user_id']) && ($_SESSION['admin_rights']=='0')){
                            echo '<li class="nav-item">
                                    <a class="nav-link text-light" href="my-borrows.php">Moje výpůjčky</a>
                                  </li>';
                          }
                        ?>
                        <?php 
                          if (!empty($_SESSION['user_id']) && ($_SESSION['admin_rights']=='1')){
                              echo '<li class="nav-item">
                                      <a class="nav-link text-light" href="authors.php">Seznam autorů</a>
                                    </li>';
                              echo '<li class="nav-item">
                                    <a class="nav-link text-light" href="current-loans.php">Přehled výpůjček</a>
                                  </li>';
                              echo '<li class="nav-item">
                                    <a class="nav-link text-light" href="all-users.php">Uživatelé</a>
                                  </li>';
                          }
                        ?>
                    </ul>
                </nav>
            </div>
            <div class="col-6">
                <?php 
                echo '<div class="row mt-10">';
              if (!empty($_SESSION['user_id'])){
                echo '<div class="col-9"><p>Přihlášený uživatel: <strong>'.htmlspecialchars($_SESSION['user_name']).'</strong></p></div>';
                echo '<div class="col-3"><a href="logout.php" class="text-light btn-sm">Odhlásit se</a></div>';
              } else {
                echo '<div class="col-9"></div><div class="col-3"><a href="login.php" class="btn btn-outline-light text-light btn-sm mt-12">Přihlásit se</a></div>';
              }
              echo '</div>';
            ?>
            </div>
        </div>
    </header>
    <main class="container pt-2">