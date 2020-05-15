<?php

  //nejprve si vynutíme, aby byl uživatel přihlášený
  require 'user.php';

      //načteme záznam z DB do proměnné $currentUser, která následně bude dostupná v celé aplikaci
      $fullUserQuery=$db->prepare('SELECT * FROM users WHERE user_id=:id LIMIT 1;');
      $fullUserQuery->execute([
        ':id'=>$_SESSION['user_id']
      ]);
      $currentUser = $fullUserQuery->fetch(PDO::FETCH_ASSOC);

  //ověříme, jestli je uživatel v roli admin - pokud ne, tak mu zabráníme v přístupu
  if(empty($currentUser) || ($currentUser['admin_rights']!='1')){
    die('Tato stránka je dostupná pouze administrátorům. Prosím, vraťte se zpět.');
  }