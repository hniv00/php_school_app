<?php
  //přístup jen pro admina
  require 'inc/admin-required.php';

  include __DIR__.'/inc/header.php';

  $title='';
  $description='';
  $bookId='';
  $authorsIds=[];

  #region načtení knihy k aktualizaci a výpočet zámku pro pessimistic lock
  // edit_expired je výpočet zámku s boolean hodnotou, jestli již zámek vypršel (starší než 5 minut)
  if (!empty($_REQUEST['id'])){
  $stmt = $db->prepare('SELECT books.*, users.email, now() > last_edit_start + INTERVAL 5 MINUTE AS edit_expired FROM books left join users on (books.last_edit_by=users.user_id) WHERE book_id=:id');
  $stmt->execute([':id'=>@$_REQUEST['id']]);
  $books = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$books){ // kniha neexistuje 
    die("Zadaná kniha neexistuje. Zkontrolujte, že jste vybrali správně a zkuste to, prosím, znovu.");
  }

  $bookId=$books['book_id'];
  $title=$books['title'];
  $description=$books['description'];

  // načtení autorů
  $query = $db->prepare('SELECT author_id FROM book_author WHERE book_id=:id');
  $query->execute([':id'=>@$_REQUEST['id']]);
  $authorsIds = $query->fetch(PDO::FETCH_ASSOC);

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

    $authorsIds=[];
    $title=trim(@$_POST['title']);
    $description=trim(@$_POST['description']);
    foreach ($_POST['authors'] as $a){
      array_push($authorsIds, $a);
    }

    #region kontrola zaslaných dat
    if (empty($title)){
        $formErrors['title']='Musíte zadat název knihy.';
      }
    if (empty($description)){
        $formErrors['description']='Musíte zadat popisek knihy.';
    }
    if (empty($authorsIds)){ // speciálně kontrola autorů
        $formErrors['authors']='Musíte vybrat alespoň jednoho autora.';
    }else {
      foreach ($_POST['authors'] as $a){
        $qry=$db->prepare('SELECT * FROM authors WHERE author_id=:id LIMIT 1;');
        $qry->execute([
          ':id'=>$a
        ]);
        if ($qry->rowCount()==0){
          $formErrors['authors']='Některý ze zvolených autorů neexistuje.';
          $_POST['authors']=[];
        }
      }
    }
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

        // aktualizace pomocné tabulky pro autory
        // nejprve smazat dosavadní autory knihy
        $deleteQuery = $db->prepare("DELETE FROM book_author WHERE book_id=?");
        $deleteQuery->execute([$books['book_id']]);
        // poté vložit nové autory
        foreach ($_POST['authors'] as $a){
          $insertQuery=$db->prepare('INSERT INTO book_author (book_id, author_id) VALUES (:book, :author);');
          $insertQuery->execute([
            ':book'=> $books['book_id'],
            ':author'=> $a,
          ]);
        }
        #endregion aktualizace knihy
      }else{
        #region uložení nové knihy
        $saveQuery=$db->prepare('INSERT INTO books (title, description) VALUES (:title, :description);');
        $saveQuery->execute([
          ':title'=> $title,
          ':description'=> $description,
        ]);


        // vložit autory do pomocné tabulky
        foreach ($_POST['authors'] as $a){
          // vybrat dané id knihy
          $last_id = $db->lastInsertId();

          $insertQuery=$db->prepare('INSERT INTO book_author (book_id, author_id) VALUES (:book, :author);');
          $insertQuery->execute([
            ':book'=> $last_id,
            ':author'=> $a,
          ]);
        }
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


<div class="container">
    <form method="post">
        <input type="hidden" name="id" value="<?php echo $bookId; ?>" />

        <div class="form-group">
            <label for="title">Název</label><br />
            <input type="text" class="form-control" name="title" id="title"
                value="<?php echo htmlspecialchars(@$title);?>" required>
            <?php
                  if (!empty($formErrors['title'])){
                    echo '<div class="invalid-feedback">'.$formErrors['title'].'</div>';
                  }
                ?>
        </div>

        <div class="form-group">
            <label for="description">Popis</label><br />
            <textarea class="form-control" name="description" required
                id="description"><?php echo htmlspecialchars(@$description)?></textarea>
            <?php
              if (!empty($formErrors['description'])){
                echo '<div class="invalid-feedback">'.$formErrors['description'].'</div>';
              }
            ?>
        </div>
        <br />

        <div class="form-group">
            <label for="authors">Autor/autoři</label>
            <select name="authors[ ]" id="authors" required multiple="multiple" size="3"
                class="form-control <?php echo (!empty($formErrors['authors'])?'is-invalid':''); ?>">
                <?php
                  $authorQuery=$db->prepare('SELECT * FROM authors ORDER BY name;');
                  $authorQuery->execute();
                  $authors=$authorQuery->fetchAll(PDO::FETCH_ASSOC);
                  if (!empty($authors)){
                    foreach ($authors as $author){
                      echo '<option value="'.$author['author_id'].'" '.(in_array($author['author_id'], $authorsIds)?'selected="selected"':'').'>'.htmlspecialchars($author['name']).'</option>';
                    }
                  }
                ?>
            </select>
            <?php
              if (!empty($formErrors['authors'])){
                echo '<div class="invalid-feedback">'.$formErrors['authors'].'</div>';
              }
            ?>
        </div>

        <button type="submit" class="btn btn-info">Uložit</button>
        <a href="catalog.php" class="btn btn-light">Zrušit</a>

    </form>
</div>

<br />

<?php
  include __DIR__.'/inc/footer.php';