<?php
require __DIR__ . '/adminLogic.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админка DMR</title>
    <link rel="stylesheet" href="/reg/assets/css/style.css">
</head>
<body>

<header>Панель администратора DMR ID
<a href="https://xlx.dmrykt.ru/reg/" style="display:inline-block; float: right; color: white;">НАЗАД</a>
</header>

<div class="container">
    <!--Поле поиска и синхронизации над таблицей.-->
    <div class="left-section">
        <h2>Кореcпонденты:</h2>
     <div class="card-space">
      <form method="get" class="search-form">
       <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Поиск...">
       <button type="submit">Найти</button>
    </form>
    <form method="get" class="search-form" style="display:inline-block; float: right;" onsubmit="return confirm('Синхронизировать список с JSON?')">
        <input type="hidden" name="sync" value="1">
        <button type="submit">Синхронизировать</button>
    </form>
    </div>

    <!--Таблица, в теле таблицы обработка каждой строки-->
    <table class="user-table">
    <thead>
        <tr data-id="<?= $row['id'] ?>">
            <th><?= sortLink('id', 'ID') ?></th>
            <th><?= sortLink('callsign', 'Позывной') ?></th>
            <th><?= sortLink('fname', 'Имя') ?></th>
            <th><?= sortLink('surname', 'Фамилия') ?></th>
            <th><?= sortLink('city', 'Город') ?></th>
            <th><?= sortLink('state', 'Регион') ?></th>
            <th><?= sortLink('country', 'Страна') ?></th>
            <th><?= sortLink('remarks', 'Телеграм') ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($currentPageData as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td>
                    <a href="https://dmrykt.ru/index.php?subaction=userinfo&user=<?= urlencode($row['callsign']) ?>"
                       onmouseover="this.style.fontWeight='bold';"
                       onmouseout="this.style.fontWeight='normal';">
                        <?= htmlspecialchars($row['callsign']) ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($row['fname']) ?></td>
                <td><?= htmlspecialchars($row['surname']) ?></td>
                <td><?= htmlspecialchars($row['city']) ?></td>
                <td><?= htmlspecialchars($row['state']) ?></td>
                <td><?= htmlspecialchars($row['country']) ?></td>
		<td><a href="https://t.me/<?= htmlspecialchars($row['remarks']) ?>"
                       onmouseover="this.style.fontWeight='bold';"
                       onmouseout="this.style.fontWeight='normal';"><?= htmlspecialchars($row['remarks']) ?>

</a> 
		</td>
                <!--Ячейка с кнопками, отправляет соотвествующие методы, частично обрабатывается через js-->
                <td class="button-group">
                    <a href="#" class="edit-btn" title="Редактировать" >✏️</a>
                    <a href="?delete=<?= $row['id'] ?>" title="Удалить" onclick="return confirm ('Вы действительно хотите удалить кореспондента?')">🗑️</a>
                    <a href="?block=<?= $row['id'] ?>" title="Заблокировать" style="color=white" >✖️</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
       <!--Пагинация + отправка запроса на сервер. Проверяет количество страниц и в зависимости от выбранной редактирует переменную-->
       <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?sort=<?= $sortKey ?>&order=<?= $sortOrder ?>&page=<?= $page - 1 ?>">&laquo; Назад</a>
        <?php endif; ?>
        <a href="?sort=<?= $sortKey ?>&order=<?= $sortOrder ?>&page=1" class="<?= $page == 1 ? 'current' : '' ?>">1</a>

        <?php
        $start = max(2, $page - 1);
        $end = min($totalPages - 1, $page + 1);
        if ($start > 2) {
            echo '<span class="dots">...</span>';
        }
        for ($i = $start; $i <= $end; $i++): ?>
            <a href="?sort=<?= $sortKey ?>&order=<?= $sortOrder ?>&page=<?= $i ?>"
               class="<?= $i == $page ? 'current' : '' ?>"><?= $i ?></a>
        <?php endfor;
        if ($end < $totalPages - 1) {
            echo '<span class="dots">...</span>';
        }
        ?>
        <?php if ($totalPages > 1): ?>
            <a href="?sort=<?= $sortKey ?>&order=<?= $sortOrder ?>&page=<?= $totalPages ?>"
               class="<?= $page == $totalPages ? 'current' : '' ?>"><?= $totalPages ?></a>
        <?php endif; ?>
        <?php if ($page < $totalPages): ?>
            <a href="?sort=<?= $sortKey ?>&order=<?= $sortOrder ?>&page=<?= $page + 1 ?>">Вперед &raquo;</a>
        <?php endif; ?>

    </div>
    <?php endif; ?>
    </div>
    <!--Правая часть страницы с формой создания и подтверждения-->
    <div class="right-section">
        <h3>Добавить кореспондента</h3>
        <?php if (!empty($form_error)): ?>
            <div class="form-error"><?= htmlspecialchars($form_error) ?></div>
        <?php endif; ?>
        <!--Форма создания, по имени отправляет метод на сервер, выше отображает ошибку, если есть-->
        <form method="post" class="add-form">
            <input type="hidden" name="add" value="1">
            <input type="number" name="new_id" placeholder="DMR ID" required>
            <div class="form-error" id="id-error"></div>

            <input oninput="this.value = this.value.toUpperCase()" type="text" name="new_callsign" placeholder="Позывной" required>
            <div class="form-error" id="callsign-error"></div>

            <input type="text" name="fname" placeholder="Имя (латиница)">
            <input type="text" name="surname" placeholder="Фамилия (латиница)">
            <input type="text" name="city" placeholder="Город (латиница)">
            <input type="text" name="state" placeholder="Регион (латиница)">
            <input type="text" name="country" placeholder="Страна (латиница)">

            <input type="text" name="remarks" placeholder="Телеграм">

            <div class="form-error" id="latin-error"></div>
            <button type="submit" id="submit-btn">Добавить</button>
        </form>
        <!--Заявки, ожидающие подтверждения-->
	<div id="unconfirmed-cards" style="margin-top: 30px;">
            <h4>Ожидают подтверждения</h4>
            <?php foreach ($data as $row): ?>
                <?php if (empty($row['confirmed'])): ?>
                    <div class="unconfirmed-card">
                        <span><?= htmlspecialchars($row['callsign']) ?> (<?= htmlspecialchars($row['id']) ?>) - 
		<a href="https://t.me/<?= htmlspecialchars($row['remarks']) ?>"
                       onmouseover="this.style.fontWeight='bold';"
                       onmouseout="this.style.fontWeight='normal';"> Написать в ТГ

</a> 
</span>
                        <div class="button-group">
                          <a href="?confirm=<?= $row['id'] ?>" title="Активировать" onclick="return confirm('Активировать кореспондента?')">✔️</a>
                          <a href="?delete=<?= $row['id'] ?>" title="Удалить" onclick="return confirm('Вы действительно хотите удалить кореспондента?')">🗑️</a>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<script src="/reg/assets/js/main.js"></script>
</body>
</html>
