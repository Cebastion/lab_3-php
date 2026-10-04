<?php
declare(strict_types=1);

const UPLOAD_DIR   = __DIR__ . '/uploads/';
const UPLOAD_URL   = 'uploads/';
const MAX_SIZE     = 2 * 1024 * 1024;               // 2 МБ
const ALLOWED_EXT  = ['png', 'jpg', 'jpeg'];
const ALLOWED_MIME = ['image/png', 'image/jpeg'];

/** Екранування виводу (захист від XSS) */
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/** Вивід HTML-сторінки з результатом та завершення скрипта */
function render(string $title, string $body): void
{
    echo '<!DOCTYPE html><html lang="uk"><head><meta charset="UTF-8">'
       . '<title>' . e($title) . '</title>'
       . '<style>body{font-family:Arial,sans-serif;max-width:640px;margin:30px auto;padding:0 15px}'
       . '.err{color:#b00020}.ok{color:#1b7a2b}.warn{color:#a65e00}'
       . 'td{padding:4px 12px 4px 0}img{max-width:300px;margin-top:10px;border:1px solid #ccc}</style>'
       . '</head><body>' . $body
       . '<p><a href="index.html">&larr; Назад до форми</a> | <a href="list.php">Список файлів</a></p>'
       . '</body></html>';
    exit;
}

function fail(string $message): void
{
    render('Помилка', '<h2 class="err">Помилка</h2><p>' . e($message) . '</p>');
}

$uploadErrors = [
    UPLOAD_ERR_INI_SIZE   => 'Розмір файлу перевищує upload_max_filesize у php.ini.',
    UPLOAD_ERR_FORM_SIZE  => 'Розмір файлу перевищує обмеження форми.',
    UPLOAD_ERR_PARTIAL    => 'Файл завантажено лише частково.',
    UPLOAD_ERR_NO_FILE    => 'Файл не було обрано.',
    UPLOAD_ERR_NO_TMP_DIR => 'Відсутня тимчасова директорія на сервері.',
    UPLOAD_ERR_CANT_WRITE => 'Не вдалося записати файл на диск.',
    UPLOAD_ERR_EXTENSION  => 'Завантаження зупинено розширенням PHP.',
];

// 1. Перевірка методу та наявності файлу у $_FILES
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Скористайтеся формою на сторінці index.html.');
}
if (!isset($_FILES['file'])) {
    fail('Файл не передано.');
}

$file = $_FILES['file'];

// 2. Код помилки завантаження
if ($file['error'] !== UPLOAD_ERR_OK) {
    fail($uploadErrors[$file['error']] ?? 'Невідома помилка завантаження.');
}

// 3. Чи файл справді завантажений через HTTP POST
if (!is_uploaded_file($file['tmp_name'])) {
    fail('Файл не був завантажений через HTTP POST.');
}

// 4. Перевірка розміру
if ($file['size'] > MAX_SIZE) {
    fail('Файл завеликий: ' . round($file['size'] / 1024, 2) . ' КБ. Максимум — 2048 КБ (2 МБ).');
}

// 5. Перевірка розширення
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ALLOWED_EXT, true)) {
    fail('Недозволене розширення «' . $ext . '». Дозволено: ' . implode(', ', ALLOWED_EXT) . '.');
}

// 6. Перевірка реального MIME-типу та вмісту (розширення можна підробити)
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
if (!in_array($mime, ALLOWED_MIME, true) || getimagesize($file['tmp_name']) === false) {
    fail('Файл не є коректним зображенням PNG або JPEG.');
}

// 7. Формування безпечного імені файлу
$custom = trim((string)($_POST['new_name'] ?? ''));
$base   = $custom !== '' ? pathinfo($custom, PATHINFO_FILENAME)
                         : pathinfo($file['name'], PATHINFO_FILENAME);
$base   = trim(preg_replace('/[^\p{L}\p{N}_-]+/u', '_', $base) ?? '', '_');
if ($base === '') {
    $base = 'image';
}
$finalName = $base . '.' . $ext;

// 8. Директорія uploads
if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
    fail('Не вдалося створити директорію uploads.');
}

// 9. Перевірка наявності файлу з таким ім'ям
$renamed = false;
if (file_exists(UPLOAD_DIR . $finalName)) {
    $suffix    = date('Ymd_His') . '_' . random_int(100, 999);
    $finalName = $base . '_' . $suffix . '.' . $ext;
    $renamed   = true;
}

// 10. Збереження файлу
if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $finalName)) {
    fail('Не вдалося зберегти файл на сервері.');
}

// 11. Вивід інформації
$url    = UPLOAD_URL . rawurlencode($finalName);
$sizeKb = round($file['size'] / 1024, 2);

$body  = '<h2 class="ok">Файл успішно завантажено</h2>';
if ($renamed) {
    $body .= '<p class="warn">Файл із такою назвою вже існував, тому до імені додано унікальний суфікс. '
           . 'Щоб задати власну назву, завантажте файл ще раз і заповніть поле «Нова назва файлу».</p>';
}
$body .= '<table>'
       . '<tr><td><b>Оригінальне ім\'я:</b></td><td>' . e($file['name']) . '</td></tr>'
       . '<tr><td><b>Збережено як:</b></td><td>' . e($finalName) . '</td></tr>'
       . '<tr><td><b>Тип:</b></td><td>' . e($mime) . '</td></tr>'
       . '<tr><td><b>Розмір:</b></td><td>' . $sizeKb . ' КБ</td></tr>'
       . '</table>'
       . '<p><a href="' . $url . '" download="' . e($finalName) . '">Завантажити файл</a></p>'
       . '<img src="' . $url . '" alt="Завантажене зображення">';

render('Результат завантаження', $body);