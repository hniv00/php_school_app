<?php
  //přístup jen pro admina
  require 'inc/admin-required.php';

  #region načtení knih k aktualizaci a výpočet zámku pro pessimistic lock
  // edit_expired je výpočet zámku s boolean hodnotou, jestli již zámek vypršel (starší než 5 minut)
  $stmt = $db->prepare('SELECT books.*, now() > last_edit_start + INTERVAL 5 MINUTE AS edit_expired FROM books WHERE book_id=:id');
  $stmt->execute([':id'=>@$_REQUEST['id']]);
  $books = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$books){
    //pokud kniha neexistuje (např. bylo mezitím smazáno), nepokračujeme dál - i když chyba by určitě mohla být vypsána hezčeji :)
    die("Unable to find books!");
  }

  $title=$books['title'];
  $description=$books['description'];
  $price=$books['price'];
  #endregion načtení knih k aktualizaci a výpočet zámku pro pessimistic lock

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
    $books["last_edit_by"] != $currentUser['id'] && 	//úpravu provádí jiný než aktuálně přihlášený uživatel
    !$books['edit_expired'] 																	  //zámek ještě nevypršel
  ){
    //zobrazíme uživateli informaci o tom, kdo zboží aktuálně upravuje
    die("The books is currently edited by ".$books['email']);
  }

  //pokud není dané zboží zamčené k úpravě, nebo zámek vypršel, nastavíme zámek nový
  $stmt = $db->prepare("UPDATE shop_books SET last_edit_start=NOW(), last_edit_by=:user WHERE id=:id");
  $stmt->execute([':user'=> $currentUser["id"], ':id'=> $_GET['id']]);
  #endregion vyřešení pesimistického zámku pro úpravu

  if (!empty($_POST)) {
    $formErrors='';

    //TODO tady by měly být nějaké kontroly odeslaných dat, že :)

    $title=$_POST['title'];
    $description=$_POST['description'];

    if (empty($formErrors)){
      #region uložení zboží do DB
	    //při uložení zboží kromě změněných dat také vynulujeme zámky nastavené pro editaci (aby mohl zboží případně editovat další uživatel)
      $stmt = $db->prepare('UPDATE shop_books SET title=:title, description=:description, price=:price, last_edit_by=NULL, last_edit_start=NULL WHERE book_id=:id LIMIT 1;');
      $stmt->execute([
        ':title'=> $title,
        ':description'=> $description,
        ':price'=>$price,
        ':id'=> $books['id']
      ]);
      #endregion uložení zboží do DB

      //přesměrování na homepage
      header('Location: index.php');
      exit();
    }
  }
?>

<h2>Úprava knižního titulu</h2>

<?php
      if (!empty($formErrors)){
        echo '<p style="color:red;">'.$formErrors.'</p>';
      }
    ?>

<form method="post">
    <label for="title">Název</label><br />
    <input type="text" name="title" id="title" value="<?php echo htmlspecialchars(@$title);?>" required><br /><br />

    <label for="price">Price<br />
        <input type="number" min="0" name="price" id="price" required
            value="<?php echo htmlspecialchars(@$price)?>"><br /><br />

        <label for="description">Popis</label><br />
        <textarea name="description"
            id="description"><?php echo htmlspecialchars(@$description)?></textarea><br /><br />

        <br />

        <input type="hidden" name="id" value="<?php echo $books['id']; ?>" />

        <input type="submit" value="Save" /> or <a href="index.php">Cancel</a>

</form>

</body>

</html>