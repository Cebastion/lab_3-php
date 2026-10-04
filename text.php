<?php
declare(strict_types=1);

const LOG_FILE = __DIR__ . '/log.txt';
const MAX_LEN  = 1000;

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// ---- Запис даних (обробка другої форми) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = trim((string)($_POST['text'] ?? ''));

    if ($text === '') {
        header('Location: text.php?status=empty');
        exit;
    }
    // mb_strlen потребує розширення mbstring; якщо його немає — рахуємо символи через preg_match_all
    $length = function_exists('mb_strlen') ? mb_strlen($text) : (int)preg_match_all('/./us', $text);
    if ($length > MAX_LEN) {
        header('Location: text.php?status=long');
        exit;
    }

    // Переноси рядків замінюємо пробілами: один запис = один рядок файлу
    $text = str_replace(["\r\n", "\r", "\n"], ' ', $text);
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $text . PHP_EOL;

    $written = file_put_contents(LOG_FILE, $line, FILE_APPEND | LOCK_EX);

    // Post/Redirect/Get — щоб оновлення сторінки не дублювало запис
    header('Location: text.php?status=' . ($written === false ? 'error' : 'saved'));
    exit;
}

// ---- Читання даних з log.txt ----
$lines = [];
if (is_readable(LOG_FILE)) {
    $lines = file(LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
}

$messages = [
    'saved' => ['ok',  'Текст успішно записано у log.txt.'],
    'empty' => ['err', 'Помилка: текст не може бути порожнім.'],
    'long'  => ['err', 'Помилка: текст довший за ' . MAX_LEN . ' символів.'],
    'error' => ['err', 'Помилка: не вдалося записати файл.'],
];
$status = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Вміст log.txt</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 640px; margin: 30px auto; padding: 0 15px; }
        .ok { color: #1b7a2b; } .err { color: #b00020; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>Вміст файлу log.txt</h1>

    <?php if (isset($messages[$status])): ?>
        <p class="<?= $messages[$status][0] ?>"><?= e($messages[$status][1]) ?></p>
    <?php endif; ?>

    <?php if ($lines): ?>
        <p>Кількість записів: <b><?= count($lines) ?></b></p>
        <pre><?php foreach ($lines as $l) { echo e($l), "\n"; } ?></pre>
    <?php else: ?>
        <p>Файл log.txt поки що порожній або не існує.</p>
    <?php endif; ?>

    <p><a href="index.html">&larr; Назад до форми</a> | <a href="list.php">Список файлів</a></p>
</body>
</html>