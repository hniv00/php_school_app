<?php
  //načteme připojení k databázi a inicializujeme session
  require_once 'inc/admin-required.php';

  //vložíme do stránek hlavičku
  include __DIR__.'/inc/header.php';

  #region načtení knih pro výpis
  $stmt = $db->prepare("SELECT * FROM books 
                        RIGHT JOIN (SELECT * from loans WHERE currently_borrowed=1) AS CURR on (books.book_id=CURR.book_id) 
                        LEFT JOIN (SELECT * from users) AS US on (CURR.user_id=US.user_id) 
                        ORDER BY CURR.loan_id DESC");
  $stmt->execute();
  $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);   //získáme všechny načtené položky do pole
  #endregion načtení knih pro výpis

  // počet výpůjček
  $count = $db->query("SELECT COUNT(*) from loans WHERE currently_borrowed=1")->fetchColumn(); 
  ?>

<h2>Přehled výpůjček</h2>

Celkový počet aktuálních výpůjček:
<strong><?php echo $count; ?></strong>

<br /><br />

<?php if ($count>0){ ?>
<!--region tabulka s výpisem knih-->
<table class="table table-dark table-hover">
    <tr>
        <th>Kniha</th>
        <th>Vypůjčeno od</th>
        <th>Uživatel</th>
        <th>Doba výpůjčky</th>
        <th></th>
    </tr>

    <?php foreach($loans as $row){ ?>
    <!--region výpis jednoho řádku knihy-->
    <tr>
        <td>
            <?php echo htmlspecialchars($row['title']); ?>
        </td>
        <td><?php echo date('d.m.Y',strtotime(htmlspecialchars($row['date_borrowed']))); ?></td>
        <td><?php echo htmlspecialchars($row['name']); ?></td>
        <td class="center">
            ---
        </td>
    </tr>
    <!--endregion výpis jednoho řádku knihy-->
    <?php } ?>
</table>
<!--endregion tabulka s výpisem knih-->

<br />

<?php }else{
    echo '<p>Aktuálně nejsou žádnému uživateli vypůjčeny žádné knihy.</p>';
}

//vložíme do stránek patičku
include __DIR__.'/inc/footer.php';