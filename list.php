<?php
declare(strict_types=1);

const UPLOAD_DIR = __DIR__ . '/uploads/';
const UPLOAD_URL = 'uploads/';

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$files = [];
if (is_dir(UPLOAD_DIR)) {
    foreach (scandir(UPLOAD_DIR) ?: [] as $name) {
        // пропускаємо ".", "..", службові файли (.gitkeep, .htaccess) та піддиректорії
        if ($name[0] === '.' || !is_file(UPLOAD_DIR . $name)) {
            continue;
        }
        $files[] = [
            'name'  => $name,
            'size'  => round(filesize(UPLOAD_DIR . $name) / 1024, 2),
            'mtime' => date('Y-m-d H:i:s', filemtime(UPLOAD_DIR . $name)),
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Список файлів</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 720px; margin: 30px auto; padding: 0 15px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 6px 10px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>Файли у директорії uploads</h1>

    <?php if ($files): ?>
        <table>
            <tr><th>№</th><th>Ім'я файлу</th><th>Розмір, КБ</th><th>Змінено</th><th>Дія</th></tr>
            <?php foreach ($files as $i => $f): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($f['name']) ?></td>
                    <td><?= $f['size'] ?></td>
                    <td><?= $f['mtime'] ?></td>
                    <td><a href="<?= UPLOAD_URL . rawurlencode($f['name']) ?>"
                           download="<?= e($f['name']) ?>">Завантажити</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <p>Усього файлів: <b><?= count($files) ?></b></p>
    <?php else: ?>
        <p>Директорія uploads порожня.</p>
    <?php endif; ?>

    <p><a href="index.html">&larr; Назад до форми</a> | <a href="text.php">Переглянути log.txt</a></p>
</body>
</html>
