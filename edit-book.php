<?php
  //přístup jen pro admina
  require 'inc/admin-required.php';

  include __DIR__.'/inc/header.php';

  $title='';
  $description='';
  $bookId='';

  #region načtení knihy k aktualizaci a výpočet zámku pro pessimistic lock
  // edit_expired je výpočet zámku s boolean hodnotou, jestli již zámek vypršel (starší než 5 minut)
  if (!empty($_REQUEST['id'])){
  $stmt = $db->prepare('SELECT books.*, users.email, now() > last_edit_start + INTERVAL 5 MINUTE AS edit_expired FROM books left join users on (books.last_edit_by=users.user_id) WHERE book_id=:id');
  $stmt->execute([':id'=>@$_REQUEST['id']]);
  $books = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$books){
      // kniha neexistuje 
      die("Zadaná kniha neexistuje. Zkontrolujte, že jste vybrali správně a zkuste to, prosím, znovu.");
    }

  $bookId=$books['book_id'];
  $title=$books['title'];
  $description=$books['description'];
  #endregion načtení knihy k aktualizaci a výpočet zámku pro pessimistic lock

  #region vyřešení pesimistického zámku pro úpravu
  // zámek s časově omezenou platností 5 minut
  if (
    !empty($books["last_edit_by"]) && 								
    $books["last_edit_by"] != $currentUser['user_id'] && 	
    !$books['edit_expired'] 																	  
  ){
    // pokud knihu někdo jiný upravuje a zámek ještě nevypršel - zobrazíme, kdo zboží aktuálně upravuje
    die("Knihu aktuálně upravuje uživatel ".$books['email']);
  }

  // pokud kniha není zamčená k úpravě, nebo zámek vypršel, nastavíme zámek nový
  $stmt = $db->prepare("UPDATE books SET last_edit_start=NOW(), last_edit_by=:user WHERE book_id=:id");
  $stmt->execute([':user'=> $currentUser["user_id"], ':id'=> $_GET['id']]);
  #endregion vyřešení pesimistického zámku pro úpravu
}

  if (!empty($_POST)) {
    $formErrors=[];

    $title=trim(@$_POST['title']);
    $description=trim(@$_POST['description']);

    #region kontrola zaslaných dat
    if (empty($title)){
        $formErrors['title']='Musíte zadat název knihy.';
      }
    if (empty($description)){
        $formErrors['description']='Musíte zadat popisek knihy.';
    }
    // TODO: kontrola pro autory
    #endregion kontrola zaslaných dat

    if (empty($formErrors)){
      if ($bookId){
        #region aktualizace knihy
        // při uložení knihy kromě změněných dat také vynulujeme zámky nastavené pro editaci
        $stmt = $db->prepare('UPDATE books SET title=:title, description=:description, last_edit_by=NULL, last_edit_start=NULL WHERE book_id=:id LIMIT 1;');
        $stmt->execute([
          ':title'=> $title,
          ':description'=> $description,
          ':id'=> $books['book_id']
        ]);
        #endregion aktualizace knihy
      }else{
        #region uložení nové knihy
        $saveQuery=$db->prepare('INSERT INTO books (title, description) VALUES (:title, :description);');
        $saveQuery->execute([
          ':title'=> $title,
          ':description'=> $description,
        ]);
        #endregion uložení nové knihy
      }

      //přesměrování na katalog
      header('Location: catalog.php');
      exit();
    }
  }
?>

<?php
  if ($bookId){
    echo '<h2>Úprava knižního titulu</h2>';
  }else{
    echo '<h2>Nový knižní titul</h2>';
  }
?>

<?php
      if (!empty($formErrors)){
        echo '<p style="color:red;">'.$formErrors.'</p>';
      }
    ?>

<div class="container">
    <form method="post">
        <input type="hidden" name="id" value="<?php echo $bookId; ?>" />

        <div class="form-group">
            <label for="title">Název</label><br />
            <input type="text" class="form-control" name="title" id="title"
                value="<?php echo htmlspecialchars(@$title);?>" required>
            <?php
                  if (!empty($errors['title'])){
                    echo '<div class="invalid-feedback">'.$errors['title'].'</div>';
                  }
                ?>
        </div>
        <div class="form-group">
            <label for="description">Popis</label><br />
            <textarea class="form-control" name="description"
                id="description"><?php echo htmlspecialchars(@$description)?></textarea>
            <?php
              if (!empty($errors['description'])){
                echo '<div class="invalid-feedback">'.$errors['description'].'</div>';
              }
            ?>
        </div>
        <br />

        <button type="submit" class="btn btn-info">Uložit</button>
        <a href="catalog.php" class="btn btn-light">Zrušit</a>

    </form>
</div>

</body>

</html>

<?php
  include __DIR__.'/inc/footer.php';