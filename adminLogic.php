<?php
//Файл может импортироваться в другие страницы и все переменные и методы будут видны
$file = __DIR__ . '/db.json';
$datFile = __DIR__ . '/dmrid.dat';
$json = json_decode(file_get_contents($file), true) ?? ['count' => 0, 'results' => []];
$data = $json['results'];

//Запрос, который отправляется сервером на форму создания
if (isset($_GET['ajax_check'])) {
    $id = $_GET['id'] ?? '';
    $callsign = strtoupper(trim($_GET['callsign'] ?? ''));
    $response = [
        'idInvalid' => !preg_match('/^\d{6,7}$/', $id),
        'idExists' => false,
        'callsignInvalid' => !preg_match('/^[A-Z0-9]{4,7}$/', $callsign),
        'callsignExists' => false
    ];
    //Проверяет id и callsign на существование
    foreach ($data as $row) {
        if ($row['id'] == $id) $response['idExists'] = true;
        if (strtoupper($row['callsign']) === $callsign) $response['callsignExists'] = true;
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

//Метод синхронизации
if (isset($_GET['sync'])) {
    $lines = [];

    //Читает json - берет из него id, callsign в нужном формате и добавляет в строки
    foreach ($data as $item) {
        if (!empty($item['confirmed'])) {
            $id = trim($item['id']);
            $callsign = strtoupper(trim($item['callsign']));
            $lines[] = "{$id};{$callsign};";
        }
    }

    //Перезаписывем файл
    file_put_contents($datFile, implode(PHP_EOL, $lines) . PHP_EOL);

    //Совершаем перенаправление на страницу админки
    header("Location: admin.php");
    exit;
}

//Редактирование
if (isset($_GET['edit'])) {
    //Отправляем не в форме а в json запросе, поэтому сначала должны получить формат
    $rawData = file_get_contents("php://input");
    $postData = json_decode($rawData, true);

    $edit_id = (int)$postData['id'];
    $callsign = strtoupper(trim($postData['callsign']));
    $fname = trim($postData['fname'] ?? '');
    $surname = trim($postData['surname'] ?? '');
    $city = trim($postData['city'] ?? '');
    $state = trim($postData['state'] ?? '');
    $country = trim($postData['country'] ?? '');
    $remarks = trim($postData['remarks'] ?? '');
 
    //Ищем среди данных по айди то поле, которое редактировали
    foreach ($data as &$item) {
            if ($item['id'] == $edit_id) {
            $entry = "{$item['id']};{$item['callsign']};";
                $item['callsign'] = $callsign;
                $item['fname'] = $fname;
                $item['surname'] = $surname;
                $item['city'] = $city;
                $item['state'] = $state;
                $item['country'] = $country;
                $item['remarks'] = $remarks;
           //$entry присвоена до редактирования - фильтруем и удаляем, после - добавляем (FILE_APPEND)
           $lines = file($datFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
           $filtered = array_filter($lines, function($line) use ($entry) {
                return trim($line) !== $entry;
            });
	   
           file_put_contents($datFile, implode(PHP_EOL, $filtered) . PHP_EOL);

           $entry = "{$item['id']};{$item['callsign']};";
           file_put_contents($datFile, $entry . PHP_EOL, FILE_APPEND);
         }
        }

        //Так очищается переменная, чтоб использовать ее дальше. После записываем все в json
        unset($item);
        $json['results'] = $data;
        $json['count'] = count($data);
        file_put_contents($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        file_put_contents($datFile, implode(PHP_EOL, array_map(fn($r) => "{$r['id']};{$r['callsign']};", $data)) . PHP_EOL);

        header("Location: admin.php");
        exit;
}


//Подтверждение. GET - без формы и json, просто ищем запись по id
if (isset($_GET['confirm'])) {
    $confirmId = (int)$_GET['confirm'];

    foreach ($data as &$item) {
        if ($item['id'] === $confirmId) {
            $item['confirmed'] = true;

            $lines = file($datFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $entry = "{$item['id']};{$item['callsign']};";
            //нет необходимости удалять - до подтверждения записи не было
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
    header("Location: admin.php");
    exit;
}

//Блокировка. Аналогично одобрению, меняем на false
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
    header("Location: admin.php");
    exit;
}

//Удаление
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];

    $callsign = null;

    foreach ($data as $index => $item) {
    if ($item['id'] === $deleteId) {
        //Получаем позывной для строки в фильтре и очищаем json от найденной переменной
        $callsign = $item['callsign'];
        unset($data[$index]); 
        break;
       }
    }

    $data = array_values($data);

    if ($callsign === null) {
        header("Location: admin.php");
        exit;
    }
    
    //Перезаписываем
    $json['results'] = $data;
    $json['count'] = count($data);
    file_put_contents($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    $lines = file($datFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $filtered = array_filter($lines, function($line) use ($deleteId, $callsign) {
        return trim($line) !== "{$deleteId};{$callsign};";
    });

    file_put_contents($datFile, implode(PHP_EOL, $filtered) . PHP_EOL);
    header("Location: admin.php");
    exit;
}

//Переменная - используется в форме добавления. Аналогично редактированию, на есть форма - не надо доставать json
$form_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
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
    } elseif (preg_match('/[^a-zA-Z\s]/', $fname . $surname . $city . $state . $country)) {
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

//Дальше идут поля, которые используются для пагинации в таблице.
//Сначала идет поиск и фильтрация на подтвержденных
$searchQuery = trim($_GET['search'] ?? '');

$confirmed = array_values(array_filter($data, fn($d) => !empty($d['confirmed'])));

if ($searchQuery !== '') {
    $confirmed = array_filter($confirmed, function($row) use ($searchQuery) {
        foreach (['id', 'callsign', 'fname', 'surname', 'city', 'state', 'country', 'remarks'] as $field) {
            if (stripos((string)($row[$field] ?? ''), $searchQuery) !== false) {
                return true;
            }
        }
        return false;
    });
}

//Среди подтвержденных полей идет сортировка
$validSortKeys = ['id', 'callsign', 'fname', 'surname', 'city', 'state', 'country', 'remarks'];
$sortKey = $_GET['sort'] ?? 'id';
$sortOrder = $_GET['order'] ?? 'asc';

if (!in_array($sortKey, $validSortKeys)) {
    $sortKey = 'id';
}

usort($confirmed, function($a, $b) use ($sortKey, $sortOrder) {
    $valA = $a[$sortKey] ?? '';
    $valB = $b[$sortKey] ?? '';

    $result = is_numeric($valA) && is_numeric($valB)
        ? $valA <=> $valB
        : strcasecmp((string)$valA, (string)$valB);
    //asc - desc : направление сортировки
    return $sortOrder === 'desc' ? -$result : $result;
});

//Пагинация
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$total = count($confirmed);
$totalPages = ceil($total / $perPage);
$offset = ($page - 1) * $perPage;
//$confirmed - сортированный массив; $offset - на сколько сдвинуть от начала; $perPage - сколько на странице
$currentPageData = array_slice($confirmed, $offset, $perPage);

//Используем, чтоб сохранить пагинацию и сортировку - используется на главной странице
function sortLink(string $key, string $label): string {
    $currentSort = $_GET['sort'] ?? 'id';
    $currentOrder = $_GET['order'] ?? 'asc';
    $nextOrder = ($currentSort === $key && $currentOrder === 'asc') ? 'desc' : 'asc';
    $page = $_GET['page'] ?? 1;

    return "<a href=\"?sort=$key&order=$nextOrder&page=$page\">$label</a>";
}
?>
