<?php
  //přístup jen pro admina
  require 'inc/admin-required.php';

  //vložíme do stránek hlavičku
  include __DIR__.'/inc/header.php';

  $title='';
  $description='';
  $bookId='';

  #region načtení knihy k aktualizaci a výpočet zámku pro pessimistic lock
  // edit_expired je výpočet zámku s boolean hodnotou, jestli již zámek vypršel (starší než 5 minut)
  if (!empty($_REQUEST['id'])){
  $stmt = $db->prepare('SELECT books.*, now() > last_edit_start + INTERVAL 5 MINUTE AS edit_expired FROM books WHERE book_id=:id');
  $stmt->execute([':id'=>@$_REQUEST['id']]);
  $books = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$books){
      //pokud kniha neexistuje (např. bylo mezitím smazáno), nepokračujeme dál - i když chyba by určitě mohla být vypsána hezčeji :)
      die("Kniha neexistuje.");
    }

  $bookId=$books['book_id'];
  $title=$books['title'];
  $description=$books['description'];
  #endregion načtení knihy k aktualizaci a výpočet zámku pro pessimistic lock

  #region vyřešení pesimistického zámku pro úpravu
  /*
   * PESIMISTIC LOCK:
   * U zboží kontrolujeme, jestli jej nemá pro úpravu otevřený jiný uživatel.
   * Pokud ano, tak neumožníme pokračovat k editačnímu formuláři.
   * Pokud ne, zboží zamkneme pro úpravu aktuálně přihlášeným uživatele (a znemožníme tak úpravu ostatním).
   *
   * Zámek má časově omezenou platnost - v tomto případě na 5 minut. Pokud zámek vypršel, tak jej ignorujeme.
   * Pokud má zboží zamčené aktuálně přihlášený uživatel, klidně úpravu umožníme - uživatel nemůže zamknout sám sebe.
   */

  if (
    !empty($books["last_edit_by"]) && 								//toto zboží je právě upravováno
    $books["last_edit_by"] != $currentUser['user_id'] && 	//úpravu provádí jiný než aktuálně přihlášený uživatel
    !$books['edit_expired'] 																	  //zámek ještě nevypršel
  ){
    //zobrazíme uživateli informaci o tom, kdo zboží aktuálně upravuje
    die("Knihu aktuálně upravuje uživatel ".$books['email']);
  }

  //pokud není dané zboží zamčené k úpravě, nebo zámek vypršel, nastavíme zámek nový
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
        </div>
        <div class="form-group">
            <label for="description">Popis</label><br />
            <textarea class="form-control" name="description"
                id="description"><?php echo htmlspecialchars(@$description)?></textarea>

        </div>
        <br />

        <button type="submit" class="btn btn-info">Uložit</button>
        <a href="catalog.php" class="btn btn-light">Zrušit</a>

    </form>
</div>

</body>

</html>