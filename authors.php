<?php
  //přístup jen pro admina
  require 'inc/admin-required.php';

  //vložíme do stránek hlavičku
  include __DIR__.'/inc/header.php';

    #region zjištění hodnoty offsetu pro stránkování
    if (isset($_GET['offset'])) {
      $offset = (int)$_GET['offset'];
    } else {
      $offset = 0;
    }
    #endregion zjištění hodnoty offsetu pro stránkování
  
    #region zjištění počtu autorů pro stránkování
    $count = $db->query("SELECT COUNT(author_id) FROM authors")->fetchColumn(); 
    #endregion zjištění počtu knih pro stránkování
  
    #region načtení autorů pro výpis
    $stmt = $db->prepare("SELECT * FROM authors ORDER BY author_id DESC LIMIT 10 OFFSET ?");
    $stmt->bindValue(1, $offset, PDO::PARAM_INT); 
    $stmt->execute();
  
    $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);  
    #endregion načtení autorů pro výpis
  ?>

<h2>Seznam autorů</h2>

Celkový počet autorů v databázi:
<strong><?php echo $count;?></strong>

<br /><br />

<!--odkaz pro přidání nového autora-->
<a class="text-info" href="edit-author.php">Přidat nového autora</a>

<br /><br />

<?php if ($count>0){ ?>
<table class="table table-dark table-hover">
    <tr>
        <th>Jméno</th>
        <th></th>
    </tr>

    <?php foreach($authors as $row){ ?>
    <tr>

        <td>
            <?php echo htmlspecialchars($row['name']); ?>
        </td>
        <td class="center">
            <a class="text-info" href='edit-author.php?id=<?php echo $row['author_id']; ?>'>Editovat</a> |
            <a class="text-info" href='delete-author.php?id=<?php echo $row['author_id']; ?>'
                onclick="return confirm('Opravdu si přejete záznam odstranit?')">Odstranit</a>
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

//vložíme do stránek patičku
include __DIR__.'/inc/footer.php';