<?php
  //přístup jen pro admina
  require 'inc/admin-required.php';

  //odebrání autora z DB
  $stmt = $db->prepare("DELETE FROM authors WHERE author_id=?");
  $stmt->execute([$_GET['id']]);

  header('Location: authors.php');