<?php
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $callsign = strtoupper(trim($_POST['callsign']));
    $telegram = strtoupper(trim($_POST['remarks']));

    if (!preg_match('/^[A-Z0-9]{4,7}$/', $callsign)) {
        $errorMessage = "Позывной должен содержать только латиницу и цифры (4–7 символов).";
    } else if (!preg_match('/^[a-zA-Z0-9_]{5,32}$/', $telegram)) {
        $errorMessage = "Телеграм быть от 5 до 32 символов, содержать только латинские буквы, цифры и подчёркивания.";
    } else {
        $file = __DIR__ . '/db.json';
        $json = json_decode(file_get_contents($file), true) ?? ['count' => 0, 'results' => []];
        $data = $json['results'];

        foreach ($data as $row) {
            if (strcasecmp($row['callsign'], $callsign) === 0) {
                $errorMessage = "Такой позывной уже существует!";
                break;
            }
        }

        if (!$errorMessage) {
            do {
                $newId = rand(1410000, 1419999);
                $used = false;
                foreach ($data as $row) {
                    if ($row['id'] == $newId) {
                        $used = true;
                        break;
                    }
                }
            } while ($used);

            $data[] = [
                "id" => $newId,
                "callsign" => $callsign,
                "fname" => $_POST['fname'] ?? "",
                "surname" => $_POST['surname'] ?? "",
                "city" => $_POST['city'] ?? "",
                "state" => $_POST['state'] ?? "",
                "country" => $_POST['country'] ?? "",
                "remarks" => $_POST['remarks'] ?? "",
                "confirmed" => false
            ];
	    
            $json['results'] = $data;
            $json['count'] = count($data);
            file_put_contents($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            header("Location: register.php?success=1");
            exit;
        }
    }
}
if (isset($_GET['success'])) {
    $successMessage = "Заявка отправлена! Ожидайте подтверждения.";
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация DMR</title>
    <style>
        body {
            font-family: sans-serif;
            background: #f8f8f8;
            padding: 30px;
            text-align: center;
        }
        form {
            background: white;
            display: inline-block;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
            max-width: 400px;
        }
        input {
            padding: 10px;
            margin: 5px 0;
            width: 90%;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        .error {
            color: red;
            font-size: 13px;
            margin: 0;
            display: none;
        }
        .success {
            color: green;
            font-size: 14px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<h2>Заявка на DMR ID</h2>

<?php if ($successMessage): ?>
    <p class="success"><?= $successMessage ?></p>
<?php endif; ?>

<?php if ($errorMessage): ?>
    <p class="error" style="display:block"><?= $errorMessage ?></p>
<?php endif; ?>

<form method="post" id="regForm" novalidate>
    <input oninput="this.value = this.value.toUpperCase()" type="text" name="callsign" placeholder="Позывной (4–7 символов)" required minlength="4" maxlength="7">
    <div class="error" id="callsign-error">Позывной должен содержать только латинские буквы и цифры (4–7 символов)</div>
    <input type="text" name="remarks" placeholder="Telegram: (Пример: DMRYKT)" required>
    <div class="error" id="telegram-error">Telegram Обязательное поле, только латинские буквы и цифры.</div>
    <input type="text" name="fname" placeholder="Имя (необязательно)">
    <input type="text" name="surname" placeholder="Фамилия (необязательно)">
    <input type="text" name="city" placeholder="Город (необязательно)">
    <input type="text" name="state" placeholder="Регион (необязательно)">
    <input type="text" name="country" placeholder="Страна (необязательно)">
    <div class="error" id="latin-error">Дополнительные поля должны содержать только латиницу (A-Z).</div>

    <button type="submit" id="submit-btn">Отправить</button>
</form>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const callsignInput = document.querySelector('input[name="callsign"]');
    const remarksInput = document.querySelector('input[name="remarks"]');
    const latinInputs = document.querySelectorAll('input[name="fname"], input[name="surname"], input[name="city"], input[name="state"], input[name="country"]');
    const submitBtn = document.getElementById('submit-btn');
    const callsignError = document.getElementById('callsign-error');
    const latinError = document.getElementById('latin-error');
    const telegramError = document.getElementById('telegram-error');

    function validateForm() {
        let valid = true;
        const remarks = remarksInput.value.trim().toUpperCase();
        const callsign = callsignInput.value.trim();
        const latinRegex = /^[A-Za-z\s]*$/;

        // Проверка позывного
        if (!/^[A-Z0-9]{4,7}$/.test(callsign)) {
            callsignError.style.display = 'block';
            valid = false;
        } else {
            callsignError.style.display = 'none';
        }

        // Проверка всех остальных полей
        let latinValid = true;
        latinInputs.forEach(input => {
            if (input.value && !latinRegex.test(input.value)) {
                latinValid = false;
            }
        });

        if (!latinValid) {
            latinError.style.display = 'block';
            valid = false;
        } else {
            latinError.style.display = 'none';
        }

        if (!/^[a-zA-Z0-9_]{5,32}$/.test(remarks)) {
            telegramError.style.display = 'block';
            valid = false;
        } else {
            telegramError.style.display = 'none';
        }

        submitBtn.disabled = !valid;
    }

    // Проверка при вводе
    callsignInput.addEventListener('input', validateForm);
    remarksInput.addEventListener('input', validateForm);
    latinInputs.forEach(input => input.addEventListener('input', validateForm));
});
</script>

</body>
</html>
