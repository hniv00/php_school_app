<?php
  require 'inc/user.php';

  // ověření, že daná kniha je volná k vypůjčení
    $stmt = $db->prepare('SELECT * from loans WHERE currently_borrowed=1 and book_id=:id LIMIT 1');
    $stmt->execute([':id'=>@$_GET['id']]);
    $book_not_available = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($book_not_available){
        die("Daná kniha není k dispozici pro vypůjčení. Zkuste, prosím, jinou knihu.");
    }


  //přidání knihy do výpůjčky
  $stmt = $db->prepare("INSERT INTO loans (loan_id, user_id, book_id, date_borrowed, date_returned, currently_borrowed) VALUES (NULL, :userId, :bookId, current_timestamp(), NULL, '1' )");

  $stmt->execute([':bookId'=>@$_GET['id'], ':userId'=>@$_GET['user']]);

  header('Location: my-borrows.php');