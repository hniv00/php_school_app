<?php
  require 'inc/admin-required.php';

  //odebrání knihy z DB
  $stmt = $db->prepare("DELETE FROM users WHERE user_id=?");
  $stmt->execute([$_GET['id']]);

  header('Location: all-users.php');