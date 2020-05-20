<?php
  //načteme připojení k databázi a inicializujeme session
  require_once 'inc/user.php';

  //vložíme do stránek hlavičku
  include __DIR__.'/inc/header.php';

  #region načtení knih pro výpis
  $stmt = $db->prepare("SELECT * FROM books RIGHT JOIN (SELECT * from loans WHERE currently_borrowed=1 and user_id=:userId) AS CURR on (books.book_id=CURR.book_id) ORDER BY books.title DESC");
  $stmt->execute([':userId'=>@$_SESSION['user_id']]);

  $books = $stmt->fetchAll(PDO::FETCH_ASSOC);   //získáme všechny načtené položky do pole
  #endregion načtení knih pro výpis

  // počet knih
  $countQuery = $db->prepare("SELECT COUNT(*) from loans WHERE currently_borrowed=1 and user_id=:userId");
  $countQuery->execute([':userId'=>@$_SESSION['user_id']]);
  $booksCount = $countQuery->fetchAll(PDO::FETCH_ASSOC);
  $count = $booksCount[0];
  ?>

<h2>Moje výpůjčky</h2>

Celkový počet aktuálně vypůjčených knih:
<strong><?php echo implode($count);/*v proměnné $count máme číslo, nemusíme tedy ošetřovat speciální znaky*/ ?></strong>

<br /><br />

<?php if ($count>0){ ?>
<!--region tabulka s výpisem knih-->
<table class="table table-dark table-hover">
    <tr>
        <th>Název</th>
        <th>Vypůjčeno od</th>
        <th></th>
    </tr>

    <?php foreach($books as $row){ ?>
    <!--region výpis jednoho řádku knihy-->
    <tr>
        <td>
            <strong><?php echo htmlspecialchars($row['title']); ?> </strong>
        </td>
        <td><?php echo date('d.m.Y',strtotime(htmlspecialchars($row['date_borrowed']))); ?></td>
        <td class="center">
            <a class="text-info" onclick="return confirm('Opravdu chcete tuto knihu vrátit?')"
                href='return.php?id=<?php echo $row['loan_id']; ?>'>Vrátit</a>
        </td>
    </tr>
    <!--endregion výpis jednoho řádku knihy-->
    <?php } ?>
</table>
<!--endregion tabulka s výpisem knih-->

<br />

<?php }else{
    echo '<p>Aktuálně nemáte vypůjčené žádné knihy.</p>';
}

//vložíme do stránek patičku
include __DIR__.'/inc/footer.php';