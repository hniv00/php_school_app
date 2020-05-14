<?php
  //načteme připojení k databázi a inicializujeme session
  require_once 'inc/user.php';

  //vložíme do stránek hlavičku
  include __DIR__.'/inc/header.php';

  if (!empty($_SESSION['user_id'])){
    echo '<p>Drazí čtenáři, rádi Vás vidíme. Projděte si knižní katalog a směle si vypůjčete, na co máte zrovna chuť.</p>';
    echo '<p>Katalog knih</p>';
    echo '<p>Moje výpůjčky</p>';
  }else{
    echo '<p>Vítejte na stránkách knihovny. Pro další akce musíte být přihlášeni.</p>';
    echo '<div class="row"><a href="login.php" class="btn btn-info text-light">Přihlásit se</a></div>';
  }

  //vložíme do stránek patičku
  include __DIR__.'/inc/footer.php';