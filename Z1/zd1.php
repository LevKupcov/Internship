<?php
declare(strict_types=1);
// Форма обратной связи с возможностью загрузки файла

header('Content-Type: text/html; charset=UTF-8');

$errors = [];
$success = false;

// Значения полей для повторного отображения при ошибках
$old = [
  'name' => '',
  'email' => '',
  'message' => ''
];

// Пути для сохранения загруженных файлов и сообщений
$uploadsDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
$messagesDir = __DIR__ . DIRECTORY_SEPARATOR . 'messages';
if (!is_dir($uploadsDir)) {
  @mkdir($uploadsDir, 0755, true);
}
if (!is_dir($messagesDir)) {
  @mkdir($messagesDir, 0755, true);
}

function sanitizeText(?string $value): string
{
  return trim((string) $value);
}

function generateUniqueFilename(string $directory, string $extension): string
{
  $timestamp = date('Ymd_His');
  $random = bin2hex(random_bytes(4));
  return rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "file_{$timestamp}_{$random}.{$extension}";
}

// Обработка только при отправке формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = sanitizeText($_POST['name'] ?? '');
  $email = sanitizeText($_POST['email'] ?? '');
  $message = sanitizeText($_POST['message'] ?? '');

  $old['name'] = $name;
  $old['email'] = $email;
  $old['message'] = $message;

  // Базовая валидация обязательных полей и e-mail
  if ($name === '') {
    $errors[] = 'Поле «Имя пользователя» обязательно для заполнения.';
  }

  if ($email === '') {
    $errors[] = 'Поле «E-mail» обязательно для заполнения.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Введите корректный адрес E-mail.';
  }

  if ($message === '') {
    $errors[] = 'Поле «Сообщение» обязательно для заполнения.';
  }

  // Необязательная загрузка изображения (JPG/PNG, до 2 МБ)
  $uploadedFilePublicPath = '';
  if (isset($_FILES['attachment']) && is_array($_FILES['attachment']) && ($_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE)) {
    $fileError = (int) ($_FILES['attachment']['error'] ?? UPLOAD_ERR_OK);
    if ($fileError !== UPLOAD_ERR_OK) {
      $errors[] = 'Ошибка загрузки файла.';
    } else {
      $tmpPath = $_FILES['attachment']['tmp_name'];
      $originalName = (string) ($_FILES['attachment']['name'] ?? '');
      $sizeBytes = (int) ($_FILES['attachment']['size'] ?? 0);
      if ($sizeBytes > 2 * 1024 * 1024) {
        $errors[] = 'Размер файла не должен превышать 2 МБ.';
      }
      if (is_uploaded_file($tmpPath)) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpPath) ?: '';
        $allowed = [
          'image/jpeg' => 'jpg',
          'image/png' => 'png'
        ];
        if (!array_key_exists($mime, $allowed)) {
          $errors[] = 'Разрешены только изображения JPG или PNG.';
        }
        if (!$errors) {
          $extension = $allowed[$mime];
          $destination = generateUniqueFilename($uploadsDir, $extension);
          if (!@move_uploaded_file($tmpPath, $destination)) {
            $errors[] = 'Не удалось сохранить загруженный файл.';
          } else {
            $uploadedFilePublicPath = 'uploads/' . basename($destination);
          }
        }
      } else {
        $errors[] = 'Некорректный файл загрузки.';
      }
    }
  }

  // Сохранение сообщения в файл при отсутствии ошибок
  if (!$errors) {
    $timestamp = date('Y-m-d H:i:s');
    $uniqueName = 'message_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.txt';
    $filePath = $messagesDir . DIRECTORY_SEPARATOR . $uniqueName;
    $content = "Дата/время: {$timestamp}\n" .
      "Имя: {$name}\n" .
      "E-mail: {$email}\n" .
      "Сообщение:\n{$message}\n\n" .
      "Путь к файлу: " . ($uploadedFilePublicPath !== '' ? $uploadedFilePublicPath : 'не прикреплён') . "\n";

    if (@file_put_contents($filePath, $content) === false) {
      $errors[] = 'Не удалось сохранить сообщение.';
      if ($uploadedFilePublicPath !== '') {
        @unlink($uploadsDir . DIRECTORY_SEPARATOR . basename($uploadedFilePublicPath));
      }
    } else {
      $success = true;
      $old = ['name' => '', 'email' => '', 'message' => ''];
    }
  }
}
?>
<!doctype html>
<html lang="ru">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Форма обратной связи</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  </head>
  <body>
    <div class="container py-5">
      <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">
          <h1 class="mb-4">Форма обратной связи</h1>

          <!-- Уведомление об успехе -->
          <?php if ($success): ?>
          <div class="alert alert-success" role="alert">
            Сообщение успешно отправлено!
          </div>
          <?php endif; ?>

          <!-- Список ошибок -->
          <?php if ($errors): ?>
          <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
              <?php foreach ($errors as $err): ?>
              <li><?php echo htmlspecialchars($err, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>

          <!-- Форма отправки -->
          <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
            <div class="row g-3">
              <div class="col-12">
                <label for="name" class="form-label">Имя пользователя *</label>
                <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($old['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
              </div>

              <div class="col-12">
                <label for="email" class="form-label">E-mail *</label>
                <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($old['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
              </div>

              <div class="col-12">
                <label for="message" class="form-label">Сообщение *</label>
                <textarea class="form-control" id="message" name="message" rows="5" required><?php echo htmlspecialchars($old['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>
              </div>

              <div class="col-12">
                <label for="attachment" class="form-label">Файл (JPG/PNG, до 2 МБ)</label>
                <input class="form-control" type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
              </div>

              <div class="col-12 d-grid d-sm-flex gap-2">
                <button type="submit" class="btn btn-primary">Отправить</button>
                <button type="reset" class="btn btn-outline-secondary">Сбросить</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </body>
</html>


