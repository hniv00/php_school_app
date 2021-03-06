<?php
  require_once 'inc/user.php';

  if (!empty($_SESSION['user_id'])){
    //uživatel už je přihlášený, nemá smysl, aby se registroval
    header('Location: index.php');
    exit();
  }

  $errors=[];
  if (!empty($_POST)){
    #region zpracování formuláře
    #region kontrola jména
    $name=trim(@$_POST['name']);
    if (empty($name)){
      $errors['name']='Musíte zadat své jméno či přezdívku.';
    }
    #endregion kontrola jména

    #region kontrola emailu
    $email=trim(@$_POST['email']);
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)){
      $errors['name']='Musíte zadat platnou e-mailovou adresu.';
    }else{
      //kontrola, jestli již není e-mail registrovaný
      $mailQuery=$db->prepare('SELECT * FROM users WHERE email=:email LIMIT 1;');
      $mailQuery->execute([
        ':email'=>$email
      ]);
      if ($mailQuery->rowCount()>0){
        $errors['name']='Uživatel s touto e-mailovou adresou již existuje.';
      }
    }
    #endregion kontrola emailu

    #region kontrola hesla
    if (empty($_POST['password']) || (strlen($_POST['password'])<5)){
      $errors['password']='Musíte zadat heslo o délce alespoň 5 znaků.';
    }
    if ($_POST['password']!=$_POST['password2']){
      $errors['password2']='Zadaná hesla se neshodují.';
    }
    #endregion kontrola hesla

    if (empty($errors)){
      //zaregistrování uživatele
      $password=password_hash($_POST['password'],PASSWORD_DEFAULT);

      $query=$db->prepare('INSERT INTO users (name, email, password, active) VALUES (:name, :email, :password, 1);');
      $query->execute([
        ':name'=>$name,
        ':email'=>$email,
        ':password'=>$password
      ]);

      //uživatele rovnou přihlásíme a přesměrujeme na homepage
      $_SESSION['user_id']=$db->lastInsertId();
      $_SESSION['user_name']=$name;
      $_SESSION['admin_rights']='0';
      header('Location: index.php');
      exit();
    }
    #endregion zpracování formuláře
  }

  //vložíme do stránek hlavičku
  include __DIR__.'/inc/header.php';
?>

<h2>Registrace nového uživatele</h2>
<form method="post">
    <div class="form-group">
        <label for="name">Jméno či přezdívka:</label>
        <input type="text" name="name" id="name" required
            class="form-control <?php echo (!empty($errors['name'])?'is-invalid':''); ?>"
            value="<?php echo htmlspecialchars(@$name);?>" />
        <?php
        echo (!empty($errors['name'])?'<div class="invalid-feedback">'.$errors['name'].'</div>':'');
      ?>
    </div>
    <div class="form-group">
        <label for="email">E-mail:</label>
        <input type="email" name="email" id="email" required
            class="form-control <?php echo (!empty($errors['email'])?'is-invalid':''); ?>"
            value="<?php echo htmlspecialchars(@$email);?>" />
        <?php
        echo (!empty($errors['email'])?'<div class="invalid-feedback">'.$errors['email'].'</div>':'');
      ?>
    </div>
    <div class="form-group">
        <label for="password">Heslo:</label>
        <input type="password" name="password" id="password" required
            class="form-control <?php echo (!empty($errors['password'])?'is-invalid':''); ?>" />
        <?php
        echo (!empty($errors['password'])?'<div class="invalid-feedback">'.$errors['password'].'</div>':'');
      ?>
    </div>
    <div class="form-group">
        <label for="password2">Potvrzení hesla:</label>
        <input type="password" name="password2" id="password2" required
            class="form-control <?php echo (!empty($errors['password2'])?'is-invalid':''); ?>" />
        <?php
        echo (!empty($errors['password2'])?'<div class="invalid-feedback">'.$errors['password2'].'</div>':'');
      ?>
    </div>
    <button type="submit" class="btn btn-info">Registrovat se</button>
    <a href="login.php" class="btn btn-light">Přihlásit se</a>
    <a href="index.php" class="btn btn-light">Zrušit</a>
</form>

<?php
  include __DIR__.'/inc/footer.php';