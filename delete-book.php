<?php
  require 'inc/admin-required.php';

  //odebrání knihy z DB
  $stmt = $db->prepare("DELETE FROM books WHERE book_id=?");
  $stmt->execute([$_GET['id']]);

  header('Location: catalog.php');