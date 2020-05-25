<?php
  require_once 'inc/user.php';
  // načteme inicializaci knihovny pro Facebook
  require_once 'inc/facebook.php';

  #region zpracování callbacku z Facebooku
  // inicializujeme helper pro vytvoření odkazu
  $fbHelper = $fb->getRedirectLoginHelper();

  // získáme access token z aktuálního přihlášení
  try {
    $accessToken = $fbHelper->getAccessToken();
  } catch(Exception $e) {
    echo 'Přihlášení pomocí Facebooku selhalo. Došlo k chybě: ' . $e->getMessage();
    exit();
  }

  if (!$accessToken){
    // nebyl vrácen access token
    exit('Přihlášení pomocí Facebooku se nezdařilo. Zkuste to znovu.');
  }

  // OAuth 2.0 client pro správu access tokenů
  $oAuth2Client = $fb->getOAuth2Client();

  // získáme údaje k tokenu, který jsme získali z přihlášení
  $accessTokenMetadata = $oAuth2Client->debugToken($accessToken);

  // získáme ID uživatele z Facebooku
  $fbUserId = $accessTokenMetadata->getUserId();

  // získáme jméno a e-mail uživatele
  $response=$fb->get('/me?fields=name,email', $accessToken);
  $graphUser=$response->getGraphUser();
  $fbUserEmail=$graphUser->getEmail();
  $fbUserName=$graphUser->getName();

  #endregion zpracování callbacku z Facebooku

  #region registrace uživatele v DB a načtení odpovídajících údajů
  // nejprve se pokusíme daného uživatele načíst podle FB User ID
  $query=$db->prepare('SELECT * FROM users WHERE facebook_id=:facebookId LIMIT 1;');
  $query->execute([
    ':facebookId'=>$fbUserId
  ]);

  if ($query->rowCount()>0){ // uživatele jsme našli v DB podle jeho Facebook User ID
    $user = $query->fetch(PDO::FETCH_ASSOC);
  }else{ // uživatel nebyl nalezen v DB - pokusíme se jej najít pomocí e-mailu
    $query = $db->prepare('SELECT * FROM users WHERE email=:email LIMIT 1;');
    $query->execute([
      ':email'=>$fbUserEmail
    ]);

    if ($query->rowCount()>0){ // uživatele jsme našli podle e-mailu, připíšeme do DB jeho Facebook User ID
      $user = $query->fetch(PDO::FETCH_ASSOC);
      $updateQuery = $db->prepare('UPDATE users SET facebook_id=:facebookId WHERE user_id=:id LIMTI 1;');
      $updateQuery->execute([
        ':facebookId'=>$fbUserId,
        ':id'=>$user['user_id']
      ]);
    }else{ // uživatele jsme vůbec nenašli, zapíšeme ho do DB jako nového
      $insertQuery = $db->prepare('INSERT INTO users (name, email, facebook_id) VALUES (:name, :email, :facebookId);');
      $insertQuery->execute([
        ':name'=>$fbUserName,
        ':email'=>$fbUserEmail,
        ':facebookId'=>$fbUserId
      ]);

      //vuživatele zpětně načteme z DB pro získání jeho user_id
      $query=$db->prepare('SELECT * FROM users WHERE facebook_id=:facebookId LIMIT 1;');
      $query->execute([
        ':facebookId'=>$fbUserId
      ]);
      $user=$query->fetch(PDO::FETCH_ASSOC);
    }
  }

  #endregion registrace uživatele v DB a načtení odpovídajících údajů

  #region přihlášení uživatele
  if (!empty($user)){
    //přihlásíme uživatele (uložíme si jeho údaje do session)
    $_SESSION['user_id']=$user['user_id'];
    $_SESSION['user_name']=$user['name'];
    $_SESSION['admin_rights']='0';
  }

  header('Location: index.php');
  #endregion přihlášení uživatele