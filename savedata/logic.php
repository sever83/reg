<?php
$file = __DIR__ . '/db.json';
$datFile = __DIR__ . '/dmrid.dat';
$json = json_decode(file_get_contents($file), true) ?? ['count' => 0, 'results' => []];
$data = $json['results'];

if (isset($_GET['ajax_check'])) {
    $callsign = strtoupper(trim($_GET['callsign'] ?? ''));
    $response = [
        'callsignInvalid' => !preg_match('/^[A-Z0-9]{4,7}$/', $callsign),
        'callsignExists' => false
    ];
    foreach ($data as $row) {
        if (strtoupper($row['callsign']) === $callsign) $response['callsignExists'] = true;
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if (isset($_GET['sync'])) {
    $lines = [];

    foreach ($data as $item) {
        if (!empty($item['confirmed'])) {
            $id = trim($item['id']);
            $callsign = strtoupper(trim($item['callsign']));
            $lines[] = "{$id};{$callsign};";
        }
    }

    file_put_contents($datFile, implode(PHP_EOL, $lines) . PHP_EOL);

    header("Location: admin.php");
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
    header("Location: admin.php");
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
    header("Location: admin.php");
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
        header("Location: admin.php");
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
    header("Location: admin.php");
    exit;
}

$form_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_id = (int)$_POST['new_id'];
    do {
                $new_id = rand(1410000, 1419999);
                $used = false;
                foreach ($data as $row) {
                    if ($row['id'] == $newId) {
                        $used = true;
                        break;
                    }
                }
            } while ($used);
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

    return $sortOrder === 'desc' ? -$result : $result;
});

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$total = count($confirmed);
$totalPages = ceil($total / $perPage);
$offset = ($page - 1) * $perPage;
$currentPageData = array_slice($confirmed, $offset, $perPage);

function sortLink(string $key, string $label): string {
    
    $currentSort = $_GET['sort'] ?? 'id';
    $currentOrder = $_GET['order'] ?? 'asc';
    $nextOrder = ($currentSort === $key && $currentOrder === 'asc') ? 'desc' : 'asc';
    $page = $_GET['page'] ?? 1;

    return "<a href=\"?sort=$key&order=$nextOrder&page=$page\">$label</a>";
}
?>
