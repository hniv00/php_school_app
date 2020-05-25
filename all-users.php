<?php
  require 'inc/admin-required.php';

  include __DIR__.'/inc/header.php';

    #region zjištění hodnoty offsetu pro stránkování
    if (isset($_GET['offset'])) {
      $offset = (int)$_GET['offset'];
    } else {
      $offset = 0;
    }
    #endregion zjištění hodnoty offsetu pro stránkování
  
    #region zjištění počtu uživatelů pro stránkování
    $count = $db->query("SELECT COUNT(user_id) FROM users")->fetchColumn(); 
    #endregion zjištění počtu knih pro stránkování
  
    if (!empty($_GET['userAdmin'])){
      #region načtení uživatelů - podle práv
      $stmt = $db->prepare("SELECT * FROM users WHERE admin_rights=? ORDER BY user_id DESC LIMIT 10 OFFSET ?");
      $stmt->bindValue(1, $_GET['userAdmin'], PDO::PARAM_INT);
      $stmt->bindValue(2, $offset, PDO::PARAM_INT); 
      $stmt->execute();
    
      $users = $stmt->fetchAll(PDO::FETCH_ASSOC);  
      #endregion načtení uživatelů - podle práv
    }else{
      #region načtení uživatelů 
      $stmt = $db->prepare("SELECT * FROM users ORDER BY user_id DESC LIMIT 10 OFFSET ?");
      $stmt->bindValue(1, $offset, PDO::PARAM_INT); 
      $stmt->execute();
    
      $users = $stmt->fetchAll(PDO::FETCH_ASSOC);  
      #endregion načtení uživatelů 
    }

  ?>

<h2>Seznam uživatelů</h2>

Celkový počet uživatelů v databázi:
<strong><?php echo $count;?></strong>

<br /><br />
<?php
#region formulář s výběrem admin
  echo '<form method="get" id="adminFilterForm">
          <select name="userAdmin" id="userAdmin" class="form-control" onchange="document.getElementById(\'adminFilterForm\').submit();">
            <option value=""';
            if (''==@$_GET['userAdmin']){
             echo ' selected="selected" ';
            } 
            echo '     >-- všichni uživatelé --</option>
            <option value="1" ';
             if ('1'==@$_GET['userAdmin']){
              echo ' selected="selected" ';
            } 
            echo '>administrátoři</option>
            <option value="0"  ';
            if ('0'==@$_GET['userAdmin']){
             echo ' selected="selected" ';
           } 
           echo '>běžní uživatelé</option>
          </select>
          <input type="submit" value="OK" class="d-none" />
        </form>';
#endregion formulář s výběrem admin
?>
<br /><br />

<?php if ($count>0){ ?>
<table class="table table-dark table-hover">
    <tr>
        <th>Jméno</th>
        <th>Email</th>
        <th>Aktivní</th>
        <th>Administrátor</th>
        <th></th>
    </tr>

    <?php foreach($users as $row){ ?>
    <tr>
        <td>
            <?php echo htmlspecialchars($row['name']); ?>
        </td>
        <td>
            <?php echo htmlspecialchars($row['email']); ?>
        </td>
        <td>
            <?php echo (htmlspecialchars($row['active'])=='1'?'ano':'ne'); ?>
        </td>
        <td>
            <?php echo (htmlspecialchars($row['admin_rights'])=='1'?'ano':'ne'); ?>
        </td>
        <td class="center">
            <a class="text-info" href='delete-user.php?id=<?php echo $row['user_id']; ?>'
                onclick="return confirm('Opravdu si přejete záznam uživatele odstranit?')">Odstranit</a>
        </td>
    </tr>
    <?php } ?>
</table>

<br />

<!--region výpis stránkování-->
<div>
    <ul class=" pagination justify-content-center">
        <?php
          for($i=1; $i<=ceil($count/10); $i++){
            echo '<li class="page-item"><a class="text-info page-link '.($offset/10+1==$i?'active':'').'" href="authors.php?offset='.(($i-1)*10).'">'.$i.'</a></li>';
          }
        ?>
    </ul>
</div>
<br />
<!--endregion výpis stránkování-->
<?php }

include __DIR__.'/inc/footer.php';