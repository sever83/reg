<?php
header('Content-Type: application/json');

$new_id = $_GET['id'] ?? '';
$new_callsign = $_GET['callsign'] ?? '';

$response = [
    'idExists' => false,
    'callsignExists' => false,
    'idInvalid' => false,
    'callsignInvalid' => false,
];

// Валидация ID
if (!preg_match('/^\d{6,7}$/', $new_id)) {
    $response['idInvalid'] = true;
}

// Валидация позывного
if (!preg_match('/^[A-Z0-9]{4,7}$/', $new_callsign)) {
    $response['callsignInvalid'] = true;
}

// Проверка в db.json
$dbPath = __DIR__ . '/db.json';
if (file_exists($dbPath)) {
    $json = json_decode(file_get_contents($dbPath), true);
    foreach ($json as $entry) {
        if (isset($entry['id']) && $entry['id'] == $new_id) {
            $response['idExists'] = true;
        }
        if (isset($entry['callsign']) && strcasecmp($entry['callsign'], $new_callsign) === 0) {
            $response['callsignExists'] = true;
        }
    }
}

// Проверка в dmrid.dat
$dmrPath = __DIR__ . '/dmrid.dat';
if (file_exists($dmrPath)) {
    $lines = file($dmrPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parts = explode(';', $line);
        if (count($parts) >= 2) {
            $id = trim($parts[0]);
            $cs = trim($parts[1]);

            if ($id === $new_id) {
                $response['idExists'] = true;
            }
            if (strcasecmp($cs, $new_callsign) === 0) {
                $response['callsignExists'] = true;
            }
        }
    }
}

echo json_encode($response);
