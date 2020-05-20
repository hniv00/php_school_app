<?php
  //přístup jen pro admina
  require 'inc/user.php';

  // aktuální timestamp
  $date = date('Y-m-d H:i:s');

  //označení knihy za vrácenou
  $stmt = $db->prepare("UPDATE loans SET date_returned=:dateTime, currently_borrowed = '0' WHERE loan_id=:loanId LIMIT 1;");
  $stmt->execute([
      ':dateTime'=>$date,
      ':loanId'=>@$_GET['id'],
      ]);

  header('Location: my-borrows.php');