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
            <div class="col-6">
                <?php 
              if (!empty($_SESSION['user_id'])){
                echo '<div class="row py-4"><div class="col-6"><p>Přihlášený uživatel: <strong>'.htmlspecialchars($_SESSION['user_name']).'</strong></p></div>';
                echo '<div class="col-6"><a href="logout.php" class="btn btn-outline-light text-light btn-sm">Odhlásit se</a></div></div>';
              }
            ?>
            </div>
        </div>
    </header>
    <main class="container pt-2">