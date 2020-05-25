<?php
  require 'inc/admin-required.php';
  
  include __DIR__.'/inc/header.php';

  $authorId='';
  $name='';

  #region načtení existujícího autora z DB
  if (!empty($_REQUEST['id'])){
    $query=$db->prepare('SELECT * FROM authors WHERE author_id=:id LIMIT 1;');
    $query->execute([':id'=>$_REQUEST['id']]);
    if ($author=$query->fetch(PDO::FETCH_ASSOC)){
      //naplníme pomocné proměnné daty autora
      $authorId=$author['author_id'];
      $name=$author['name'];
    }else{
      exit('Autor neexistuje.');
    }
  }
  #endregion načtení existujícího autora z DB

  $errors=[];
  if (!empty($_POST)){
    #region kontrola textu
    $name=trim(@$_POST['name']);
    if (empty($name)){
      $errors['name']='Musíte zadat jméno autora.';
    }
    #endregion kontrola textu

    if (empty($errors)){
      if ($authorId){
        #region aktualizace existujícího autora
        $saveQuery=$db->prepare('UPDATE authors SET name=:name WHERE author_id=:id LIMIT 1;');
        $saveQuery->execute([
          ':name'=>$name,
          ':id'=>$authorId,
        ]);
        #endregion aktualizace existujícího autora
      }else{
        #region uložení nového autora
        $saveQuery=$db->prepare('INSERT INTO authors (name) VALUES (:name);');
        $saveQuery->execute([
          ':name'=>$name
        ]);
        #endregion uložení nového autora
      }
      
      #region přesměrování
      header('Location: authors.php');
      exit();
      #endregion přesměrování
    }
  }
?>

<?php
  if ($authorId){
    echo '<h2>Úprava autora</h2>';
  }else{
    echo '<h2>Nový autor</h2>';
  }
?>

<form method="post">
    <input type="hidden" name="id" value="<?php echo $authorId;?>" />

    <div class="form-group">
        <label for="name">Jméno autora</label>
        <textarea name="name" id="name" required
            class="form-control <?php echo (!empty($errors['name'])?'is-invalid':''); ?>"><?php echo htmlspecialchars(@$name);?></textarea>
        <?php
        if (!empty($errors['name'])){
          echo '<div class="invalid-feedback">'.$errors['name'].'</div>';
        }
      ?>
    </div>

    <button type="submit" class="btn btn-info">Uložit</button>
    <a href="authors.php" class="btn btn-light">Zrušit</a>
</form>

<?php
  include 'inc/footer.php';