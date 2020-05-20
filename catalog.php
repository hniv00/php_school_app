<?php
  //načteme připojení k databázi a inicializujeme session
  require_once 'inc/user.php';

  //vložíme do stránek hlavičku
  include __DIR__.'/inc/header.php';

    #region zjištění hodnoty offsetu pro stránkování knih
    if (isset($_GET['offset'])) {
      $offset = (int)$_GET['offset'];
    } else {
      $offset = 0;
    }
    #endregion zjištění hodnoty offsetu pro stránkování knih
  
    #region zjištění počtu knih pro stránkování
    $count = $db->query("SELECT COUNT(book_id) FROM books")->fetchColumn(); //pro zjištění jednoho výsledku to jde i bez pomocné proměnné pro uložení dotazu
    #endregion zjištění počtu knih pro stránkování
  
    #region načtení knih pro výpis
    $stmt = $db->prepare("SELECT * FROM books LEFT JOIN (SELECT currently_borrowed, book_id as loan_book_id from loans WHERE currently_borrowed=1) AS CURR on (books.book_id=CURR.loan_book_id) ORDER BY books.book_id DESC LIMIT 10 OFFSET ?");//načítáme maximálně 10 položek z databáze
    $stmt->bindValue(1, $offset, PDO::PARAM_INT); //offset předáváme s uvedením datového typu; s ohledem na to, že ale máme ověřeno, že v proměnné $offset je číslo, mohli bychom ho i přímo připojit do dotazu
    $stmt->execute();
  
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);   //získáme všechny načtené položky do pole
    #endregion načtení knih pro výpis
  ?>

<h2>Katalog knih</h2>

Celkový počet titulů:
<strong><?php echo $count;/*v proměnné $count máme číslo, nemusíme tedy ošetřovat speciální znaky*/ ?></strong>

<br /><br />

<!--odkaz pro přidání nové knihy-->
<?php if(!empty($_SESSION['user_id']) && ($_SESSION['admin_rights']=='1')){ ?>
<a class="text-info" href="edit-book.php">Přidat nový titul</a>
<?php  
} ?>

<br /><br />

<?php if ($count>0){ ?>
<!--region tabulka s výpisem knih-->
<table class="table table-dark table-hover">
    <tr>
        <th>Název</th>
        <th>Autor</th>
        <th>Popis</th>
        <th></th>
    </tr>

    <?php foreach($books as $row){ ?>
    <!--region výpis jednoho řádku knihy-->
    <tr>

        <td>
            <strong><?php echo htmlspecialchars($row['title']); ?> </strong>
        </td>
        <td><?php 
          #region výpis autorů
          $stmt = $db->prepare('SELECT * FROM books left join book_author on (books.book_id=book_author.book_id) left join authors on (book_author.author_id=authors.author_id) where books.book_id=:id');
          $stmt->execute([':id'=>@$row['book_id']]);
          $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);
          foreach($authors as $author){
            echo htmlspecialchars($author['name']).'<br/>';
          }
          #region výpis autorů
        ?></td>
        <td><?php echo mb_strimwidth(htmlspecialchars($row['description']), 0, 70, "..."); ?></td>

        <td class="center">
            <!--pro přihlášeného uživatele-->
            <?php
            if (!empty($_SESSION['user_id']) && ($_SESSION['admin_rights']=='0') && ($row['currently_borrowed']!=='1')){?>
            <a class="text-info"
                href='borrow.php?id=<?php echo $row['book_id']; ?>&user=<?php echo $_SESSION['user_id']; ?>'>Vypůjčit</a>
            <?php
          }            ?>
            <!--pro přihlášeného administrátora-->
            <?php
            if(!empty($_SESSION['user_id']) && ($_SESSION['admin_rights']=='1')){?>
            <a class="text-info" href='edit-book.php?id=<?php echo $row['book_id']; ?>'>Editovat</a>
            |
            <a class="text-info" href='delete-book.php?id=<?php echo $row['book_id']; ?>'
                onclick="return confirm('Opravdu si přejete záznam odstranit?')">Odstranit</a>
            <?php
          }  ?>

        </td>
    </tr>
    <!--endregion výpis jednoho řádku knihy-->
    <?php } ?>
</table>
<!--endregion tabulka s výpisem knih-->

<br />

<!--region výpis stránkování-->
<div>
    <ul class="pagination justify-content-center">
        <?php
          for($i=1; $i<=ceil($count/10); $i++){
            echo '<li class="page-item"><a class="text-info page-link '.($offset/10+1==$i?'active':'').'" href="catalog.php?offset='.(($i-1)*10).'">'.$i.'</a></li>';
          }
        ?>
    </ul>
</div>
<br />
<!--endregion výpis stránkování-->
<?php }

//vložíme do stránek patičku
include __DIR__.'/inc/footer.php';