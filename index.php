<?php
  //načteme připojení k databázi a inicializujeme session
  require_once 'inc/user.php';

  //vložíme do stránek hlavičku
  include __DIR__.'/inc/header.php';

  if (!empty($_SESSION['user_id'])){
    echo '<p>Drazí čtenáři, rádi Vás vidíme!</p> <p>Projděte si náš knižní katalog a směle si vypůjčete, na co máte zrovna chuť.</p>';
  }else{
    echo '<p>Vítejte na stránkách knihovny. Bez přihlášení si můžete prohlédnout náš knižní katalog. Pro další akce musíte být přihlášeni.</p>';
  }

  exit(var_dump($_SESSION['admin_rights']));
  exit(var_dump($_SESSION['user_name']));


  //vložíme do stránek patičku
  include __DIR__.'/inc/footer.php';