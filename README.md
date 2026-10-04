# Звіт з лабораторної роботи

**Тема:** Файли та файлова структура

**Мета:** Ознайомитися з роботою з файлами у PHP, навчитися завантажувати файли на сервер за допомогою HTML-форм і масиву `$_FILES`, а також виконувати операції з файлами у файловій системі.

**Виконав:** Косенко Дмитро Романович, група ІКМ-224Д

**Дата:** 04 . 10 . 2026

---

## 1. Короткі теоретичні відомості

| Функція / елемент | Призначення |
|---|---|
| `enctype="multipart/form-data"` | Атрибут форми, без якого файл не передається на сервер. |
| `$_FILES['file']` | Масив з ключами `name`, `type`, `tmp_name`, `error`, `size`. |
| `is_uploaded_file()` | Перевіряє, що файл завантажено саме через HTTP POST (захист від підміни шляху). |
| `move_uploaded_file()` | Переміщує файл із тимчасової директорії у постійну. |
| `file_exists()` | Перевіряє існування файлу. |
| `file_put_contents()` з `FILE_APPEND` | Дописує дані в кінець файлу (`LOCK_EX` — блокування від одночасного запису). |
| `file()` | Читає файл у масив рядків. |
| `scandir()` | Повертає список елементів директорії. |

## 2. Структура проєкту

```
lab_php/
├── index.html     # форма завантаження файлу + форма введення тексту
├── process.php    # обробка завантаження файлу
├── text.php       # запис у log.txt та відображення його вмісту
├── list.php       # список файлів з директорії uploads
├── log.txt        # створюється автоматично
└── uploads/       # директорія для завантажених зображень
```

## 3. Хід роботи

### 3.1. HTML-форми (`index.html`)

Створено дві окремі форми:

1. Форма завантаження файлу: `<input type="file">`, кнопка `<input type="submit">`, атрибут `enctype="multipart/form-data"`, `method="post"`. Додатково є необов'язкове поле для власної назви файлу.
2. Форма введення тексту (`<textarea>`) для запису у `log.txt`.

### 3.2. Обробка завантаження (`process.php`)

Скрипт виконує перевірки у такій послідовності:

1. Запит має бути POST, у `$_FILES` має бути елемент `file`.
2. Код помилки `$_FILES['file']['error']` дорівнює `UPLOAD_ERR_OK` (для кожного коду є зрозуміле повідомлення).
3. `is_uploaded_file()` підтверджує, що файл завантажено через POST.
4. Розмір не перевищує **2 МБ** (`2 * 1024 * 1024` байт).
5. Розширення належить списку `png`, `jpg`, `jpeg`.
6. Додатково перевіряється справжній MIME-тип (`finfo`) і вміст (`getimagesize()`), оскільки розширення легко підробити.
7. Ім'я файлу очищується від небезпечних символів (захист від *path traversal*).
8. `move_uploaded_file()` зберігає файл у `uploads/`.

### 3.3. Виведення інформації про файл

Після успішного завантаження виводяться: оригінальне ім'я, ім'я збереження, MIME-тип, розмір у КБ (`round($size / 1024, 2)`), посилання для завантаження (атрибут `download`) та попередній перегляд зображення. Усі значення екрануються через `htmlspecialchars()`.

### 3.4. Перевірка наявності файлу

Перед збереженням виконується `file_exists()`. Якщо файл з такою назвою вже є, користувача повідомляють про це, а до імені автоматично додається унікальний суфікс `_ГГГГММДД_ГГММСС_NNN` (дата, час і випадкове число), наприклад `a_20261004_103302_851.png`. Користувач також може сам задати нову назву у відповідному полі форми.

### 3.5. Читання та запис у файл (`text.php`)

- Текст з другої форми дописується у `log.txt` у форматі `[дата час] текст`, кожен запис в окремому рядку.
- Порожній текст та текст довший за 1000 символів відхиляються.
- Після запису виконується перенаправлення (шаблон *Post/Redirect/Get*), щоб оновлення сторінки не дублювало запис.
- Вміст файлу зчитується функцією `file()` і виводиться на сторінку.

### 3.6. Список файлів (`list.php`)

Функція `scandir()` отримує вміст `uploads/`; службові елементи (`.`, `..`, `.gitkeep`) та піддиректорії пропускаються. Для кожного файлу виводяться номер, ім'я, розмір, дата зміни та посилання для завантаження.

## 4. Запуск

Потрібен PHP 7.1 або новіший. З директорії проєкту:

```bash
php -S localhost:8000
```

Далі відкрити у браузері `http://localhost:8000/index.html`. (Також можна розмістити папку в `htdocs` у XAMPP/OpenServer.) Директорія `uploads` та файл `log.txt` мають бути доступними для запису.

## 5. Результати тестування

| № | Дія | Очікуваний результат | Результат |
|---|---|---|---|
| 1 | Завантаження `a.png` (0,13 КБ) | Файл збережено, виведено ім'я, тип `image/png`, розмір у КБ, посилання | Успішно |
| 2 | Повторне завантаження `a.png` | Повідомлення про існування файлу, додано суфікс `a_20261004_103302_851.png` | Успішно |
| 3 | Завантаження з новою назвою `my_photo` | Файл збережено як `my_photo.png` | Успішно |
| 4 | Завантаження `bad.txt` | Помилка: недозволене розширення | Успішно |
| 5 | Текстовий файл, перейменований на `.png` | Помилка: файл не є коректним зображенням | Успішно |
| 6 | Файл більший за 2 МБ | Помилка: файл завеликий | Відхиляється (перевірка розміру) |
| 7 | Запис тексту «Привіт світ» | Рядок з датою додано в `log.txt` та показано на сторінці | Успішно |
| 8 | Відкриття `list.php` | Таблиця з усіма файлами та посиланнями | Успішно |

> Тестування виконано на PHP 8.3 (вбудований веб-сервер). Сюди можна додати власні скриншоти.

## 6. Висновки

У ході роботи вивчено механізм завантаження файлів у PHP: форма з `enctype="multipart/form-data"`, масив `$_FILES`, функції `is_uploaded_file()` та `move_uploaded_file()`. Реалізовано перевірку розширення, MIME-типу, вмісту та розміру файлу, вирішено проблему збігу імен через унікальний суфікс. Опановано читання й запис текстових файлів (`file_put_contents`, `file`), а також виведення вмісту директорії за допомогою `scandir()`. Враховано базові вимоги безпеки: екранування виводу, очищення імені файлу, перевірка реального вмісту зображення.

## 7. Додаток. Листинги програм

### index.html

```html
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Файли та файлова структура</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 640px; margin: 30px auto; padding: 0 15px; color: #222; }
        fieldset { margin-bottom: 25px; padding: 15px 20px; border: 1px solid #bbb; border-radius: 6px; }
        legend { font-weight: bold; }
        label { display: block; margin: 10px 0 4px; }
        textarea, input[type="text"] { width: 100%; box-sizing: border-box; padding: 6px; }
        input[type="submit"] { margin-top: 12px; padding: 7px 18px; cursor: pointer; }
        small { color: #666; }
    </style>
</head>
<body>
    <h1>Робота з файлами у PHP</h1>

    <!-- Форма 1: завантаження файлу (пп. 1-3) -->
    <form action="process.php" method="post" enctype="multipart/form-data">
        <fieldset>
            <legend>1. Завантаження зображення</legend>

            <label for="file">Оберіть файл (png, jpg, jpeg, до 2 МБ):</label>
            <input type="file" name="file" id="file" accept=".png,.jpg,.jpeg,image/png,image/jpeg" required>

            <label for="new_name">Нова назва файлу (необов'язково):</label>
            <input type="text" name="new_name" id="new_name" placeholder="наприклад, my_photo">
            <small>Розширення вказувати не потрібно. Якщо файл із такою назвою вже існує,
                до імені буде додано унікальний суфікс.</small>

            <br>
            <input type="submit" value="Завантажити">
        </fieldset>
    </form>

    <!-- Форма 2: запис тексту у log.txt (п. 5) -->
    <form action="text.php" method="post">
        <fieldset>
            <legend>2. Запис тексту у log.txt</legend>

            <label for="text">Введіть текст:</label>
            <textarea name="text" id="text" rows="4" maxlength="1000" required></textarea>

            <input type="submit" value="Записати у файл">
        </fieldset>
    </form>

    <p>
        <a href="text.php">Переглянути log.txt</a> |
        <a href="list.php">Список завантажених файлів</a>
    </p>
</body>
</html>
```

### process.php

```php
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
```

### text.php

```php
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
```

### list.php

```php
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
```