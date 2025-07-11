<?php
$file = __DIR__ . '/db.json';
$datFile = __DIR__ . '/dmrid.dat';
$json = json_decode(file_get_contents($file), true) ?? ['count' => 0, 'results' => []];
$data = $json['results'];

if (isset($_GET['ajax_check'])) {
    $id = $_GET['id'] ?? '';
    $callsign = strtoupper(trim($_GET['callsign'] ?? ''));
    $response = [
        'idInvalid' => !preg_match('/^\d{6,7}$/', $id),
        'idExists' => false,
        'callsignInvalid' => !preg_match('/^[A-Z0-9]{4,7}$/', $callsign),
        'callsignExists' => false
    ];
    foreach ($data as $row) {
        if ($row['id'] == $id) $response['idExists'] = true;
        if (strtoupper($row['callsign']) === $callsign) $response['callsignExists'] = true;
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if (isset($_GET['sync'])) {
    $confirmed = [];
    foreach ($data as $item) {
        if (!empty($item['confirmed'])) {
            $confirmed["{$item['id']};{$item['callsign']};"] = true;
        }
    }

    $lines = file($datFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $filtered = array_filter($lines, function($line) use ($confirmed) {
        return isset($confirmed[trim($line)]);
    });
    
    $existingLines = array_flip(array_map('trim', $lines));
    foreach ($confirmed as $entry => $val) {
        if (!isset($existingLines[$entry])) {
            $filtered[] = $entry;
        }
    }

    file_put_contents($datFile, implode(PHP_EOL, $filtered) . PHP_EOL);

    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

if (isset($_GET['confirm'])) {
    $confirmId = (int)$_GET['confirm'];

    foreach ($data as &$item) {
        if ($item['id'] === $confirmId) {
            $item['confirmed'] = true;

            $lines = file($datFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $entry = "{$item['id']};{$item['callsign']};";

            if (!in_array($entry, $lines)) {
                file_put_contents($datFile, $entry . PHP_EOL, FILE_APPEND);
            }

            break;
        }
    }
    unset($item);
    $json['results'] = $data;
    $json['count'] = count($data);
    file_put_contents($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

if (isset($_GET['block'])) {
    $confirmId = (int)$_GET['block'];

    foreach ($data as &$item) {
        if ($item['id'] === $confirmId) {
            $item['confirmed'] = false;

	    $lines = file($datFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $entry = "{$item['id']};{$item['callsign']};";

            $filtered = array_filter($lines, function($line) use ($entry) {
                return trim($line) !== $entry;
            });

            file_put_contents($datFile, implode(PHP_EOL, $filtered) . PHP_EOL);

            break;
        }
    }
    unset($item);
    $json['results'] = $data;
    $json['count'] = count($data);
    file_put_contents($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];

    $callsign = null;

    foreach ($data as $index => $item) {
    if ($item['id'] === $deleteId) {
        $callsign = $item['callsign'];
        unset($data[$index]); 
        break;
       }
    }

    $data = array_values($data);

    if ($callsign === null) {
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit;
    }

    $json['results'] = $data;
    $json['count'] = count($data);
    file_put_contents($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    $lines = file($datFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $filtered = array_filter($lines, function($line) use ($deleteId, $callsign) {
        return trim($line) !== "{$deleteId};{$callsign};";
    });

    file_put_contents($datFile, implode(PHP_EOL, $filtered) . PHP_EOL);
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

$form_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_id = (int)$_POST['new_id'];
    $callsign = strtoupper(trim($_POST['new_callsign']));
    $fname = trim($_POST['fname'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    if ($new_id < 100000 || $new_id > 9999999) {
        $form_error = 'ID должен быть 6–7 цифр.';
    } elseif (!preg_match('/^[A-Z0-9]{4,7}$/', $callsign)) {
        $form_error = 'Позывной: 4–7 латинских символов/цифр.';
    } elseif (array_filter($data, fn($r) => $r['id'] == $new_id || strtoupper($r['callsign']) === $callsign)) {
        $form_error = 'Такой ID или позывной уже существует.';
    } elseif (preg_match('/[^a-zA-Z\s]/', $fname . $surname . $city . $state . $country . $remarks)) {
        $form_error = 'Дополнительные поля должны содержать только латиницу.';
    } else {
        $data[] = [
            'id' => $new_id,
            'callsign' => $callsign,
            'fname' => $fname,
            'surname' => $surname,
            'city' => $city,
            'state' => $state,
            'country' => $country,
            'remarks' => $remarks,
            'confirmed' => true
        ];
        $json['results'] = $data;
        $json['count'] = count($data);
        file_put_contents($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $dat_line = "{$new_id};{$callsign};" . PHP_EOL;
        file_put_contents($datFile, $dat_line, FILE_APPEND);

        header("Location: admin.php");
        exit;
    }
}
$confirmed = array_values(array_filter($data, fn($d) => !empty($d['confirmed'])));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$total = count($confirmed);
$totalPages = ceil($total / $perPage);
$offset = ($page - 1) * $perPage;
$currentPageData = array_slice($confirmed, $offset, $perPage);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админка DMR</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 0;
        }
        header {
            background: #1f2937;
            color: #fff;
            padding: 15px 30px;
            font-size: 20px;
            font-weight: bold;
        }
        .container {
            display: flex;
            padding: 30px;
            gap: 30px;
            align-items: flex-start;
        }
        .left-section { flex: 2; }
        .right-section {
            flex: 1;
            position: sticky;
            top: 30px;
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .card {
            background: #fff;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            box-shadow: 0 0 4px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card span { font-size: 14px; }
        .confirm { color: #10b981; font-weight: bold; text-decoration: none; }
        .delete { color: #ef4444; text-decoration: none; }
        .add-form input {
            padding: 10px;
            margin-bottom: 10px;
            width: 100%;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .add-form button {
            padding: 10px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            width: 100%;
            cursor: pointer;
        }
        .form-error {
            color: red;
            font-size: 13px;
            margin-top: -8px;
            margin-bottom: 10px;
        }
        .sync-button {
            background: white;
            color: #1f2937;
            border: none;
            padding: 8px 14px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
        }
        .pagination { margin-top: 15px; text-align: center; }
        .pagination a { margin: 0 5px; text-decoration: none; padding: 5px 10px; background: #eee; border-radius: 5px; }
        .pagination .current { background: #1f2937; color: #fff; }
        h2, h3 { margin-top: 0; }
    </style>
</head>
<body>

<header>Панель администратора DMR
<form method="get" style="display:inline-block; float: right;" onsubmit="return confirm('Синхронизировать список с JSON?')">
        <input type="hidden" name="sync" value="1">
        <button type="submit" class="sync-button">Синхронизировать</button>
    </form>
</header>

<div class="container">
    <div class="left-section">
        <h2>Заявки</h2>
        <div id="cards">
            <?php foreach ($currentPageData as $row): ?>
                <div class="card">
                    <span>  <b>ID:</b> <?= $row['id'] ?></br>
 			<b>Позывной:</b> <a href="https://xlxsmk.ru/index.php?subaction=userinfo&user=<?= htmlspecialchars($row['callsign']) ?>" class="confirm"><?= htmlspecialchars($row['callsign']) ?></a></br>
			<b>Имя:</b> <?= htmlspecialchars($row['fname']) ?></br>
			<b>Фамилия:</b> <?= htmlspecialchars($row['surname']) ?></br>
			<b>Город:</b> <?= htmlspecialchars($row['city']) ?></br>
			<b>Регион:</b> <?= htmlspecialchars($row['state']) ?></br>
			<b>Страна:</b> <?= htmlspecialchars($row['country']) ?></br>
			<b>Примечание:</b> <?= htmlspecialchars($row['remarks']) ?></br>
                    </span>
                    <span>
                    <a href="?delete=<?= $row['id'] ?>" class="delete" onclick="return confirm('Удалить эту запись?')">Удалить</a>
                    <a href="?block=<?= $row['id'] ?>" class="delete">Заблокировать</a>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
         <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="?page=<?= $p ?>" class="<?= $p == $page ? 'current' : '' ?>"><?= $p ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="right-section">
        <h3>Добавить вручную</h3>
        <?php if (!empty($form_error)): ?>
            <div class="form-error"><?= htmlspecialchars($form_error) ?></div>
        <?php endif; ?>
        <form method="post" class="add-form">
            <input type="number" name="new_id" placeholder="DMR ID" required>
            <div class="form-error" id="id-error"></div>

            <input oninput="this.value = this.value.toUpperCase()" type="text" name="new_callsign" placeholder="Позывной" required>
            <div class="form-error" id="callsign-error"></div>

            <input type="text" name="fname" placeholder="Имя (латиница)">
            <input type="text" name="surname" placeholder="Фамилия (латиница)">
            <input type="text" name="city" placeholder="Город (латиница)">
            <input type="text" name="state" placeholder="Регион (латиница)">
            <input type="text" name="country" placeholder="Страна (латиница)">
            <input type="text" name="remarks" placeholder="Примечание (латиница)">
            <div class="form-error" id="latin-error"></div>
            <button type="submit" id="submit-btn">Добавить</button>
        </form>

	<div id="unconfirmed-cards" style="margin-top: 30px;">
            <h4>Ожидают подтверждения</h4>
            <?php foreach ($data as $row): ?>
                <?php if (empty($row['confirmed'])): ?>
                    <div class="card">
                        <span>⏳ <?= htmlspecialchars($row['callsign']) ?> </span>
                        <a href="?confirm=<?= $row['id'] ?>" class="confirm">Подтвердить</a>
                        <a href="?delete=<?= $row['id'] ?>" class="delete" onclick="return confirm('Удалить эту запись?')">Удалить</a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const idInput = document.querySelector('input[name="new_id"]');
    const csInput = document.querySelector('input[name="new_callsign"]');
    const submitBtn = document.getElementById('submit-btn');
    const idError = document.getElementById('id-error');
    const csError = document.getElementById('callsign-error');
    const latinError = document.getElementById('latin-error');
    const optionalInputs = document.querySelectorAll('.add-form input[type="text"]:not([name="new_callsign"])');

    async function check() {
        const id = idInput.value.trim();
        const callsign = csInput.value.trim();
        idError.textContent = '';
        csError.textContent = '';
        latinError.textContent = '';
        submitBtn.disabled = false;

        const res = await fetch(`?ajax_check=1&id=${encodeURIComponent(id)}&callsign=${encodeURIComponent(callsign)}`);
        const json = await res.json();

        if (json.idInvalid) {
            idError.textContent = 'ID должен быть 6–7 цифр.';
        } else if (json.idExists) {
            idError.textContent = 'Такой ID уже существует.';
        }

        if (json.callsignInvalid) {
            csError.textContent = 'Позывной: 4–7 латинских символов/цифр.';
        } else if (json.callsignExists) {
            csError.textContent = 'Такой позывной уже существует.';
        }

        if (json.idInvalid || json.idExists || json.callsignInvalid || json.callsignExists) {
            submitBtn.disabled = true;
        }

        for (const input of optionalInputs) {
            if (input.value && !/^[a-zA-Z\s]*$/.test(input.value)) {
                latinError.textContent = 'Дополнительные поля должны содержать только латиницу.';
                submitBtn.disabled = true;
                break;
            }
        }
    }

    idInput.addEventListener('input', check);
    csInput.addEventListener('input', check);
    optionalInputs.forEach(input => input.addEventListener('input', check));
});
</script>

</body>
</html>
