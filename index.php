<?php
$files = [
    'admin.php'    => 'Админка',
    'https://xlx.dmrykt.ru/'    => 'XLX Сервер',
    'db.json'      => 'Файл базы данных для HBLink',
    'dmrid.dat'    => 'Файл базы данных для XLX',
    'https://xlx.dmrykt.ru/pistar/DMRIdsYKT.dat'    => 'Файл базы данных для Pi-star',
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>DMR ID Панель</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Roboto, sans-serif;
            background-color: #f4f4f4;
            color: #222;
        }

        .container {
            max-width: 600px;
            margin: 100px auto;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.05);
            text-align: center;
        }

        h1 {
            font-weight: 600;
            font-size: 28px;
            margin-bottom: 30px;
            color: #2c3e50;
        }

        .link {
            display: block;
            margin: 12px 0;
            padding: 14px 24px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f9f9f9;
            color: #333;
            text-decoration: none;
            font-size: 16px;
            transition: all 0.2s ease;
        }

        .link:hover {
            background: #efefef;
            border-color: #bbb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Панель управления DMR ID</h1>
        <?php foreach ($files as $file => $label): ?>
            <a class="link" href="<?= htmlspecialchars($file) ?>"><?= htmlspecialchars($label) ?></a>
        <?php endforeach; ?>
    </div>
</body>
</html>
