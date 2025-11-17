<?php
$uploadsDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadsDir)) {
	@mkdir($uploadsDir, 0775, true);
}

// Определение наиболее вероятного разделителя CSV по первым строкам
function detectDelimiter(string $filePath): string {
	$delimiters = [',', ';', "\t", '|'];
	$bestDelimiter = ',';
	$bestScore = -1;
	foreach ($delimiters as $delimiter) {
		$handle = @fopen($filePath, 'r');
		if (!$handle) {
			continue;
		}
		$rowsChecked = 0;
		$totalCols = 0;
		while (($row = fgetcsv($handle, 0, $delimiter)) !== false && $rowsChecked < 8) {
			if ($row === [null] || $row === false) {
				break;
			}
			$totalCols += count($row);
			$rowsChecked++;
		}
		fclose($handle);
		if ($rowsChecked > 0) {
			$avg = $totalCols / $rowsChecked;
			if ($avg > $bestScore) {
				$bestScore = $avg;
				$bestDelimiter = $delimiter;
			}
		}
	}
	return $bestDelimiter;
}

// Чтение CSV с нормализацией строк по числу заголовков
function readCsv(string $filePath, ?string $forcedDelimiter = null): array {
	$result = [
		'headers' => [],
		'rows' => [],
		'delimiter' => ',',
		'error' => null,
	];
	if (!is_file($filePath)) {
		$result['error'] = 'Файл не найден: ' . htmlspecialchars(basename($filePath), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
		return $result;
	}
	$delimiter = $forcedDelimiter ?: detectDelimiter($filePath);
	$result['delimiter'] = $delimiter;
	$handle = @fopen($filePath, 'r');
	if (!$handle) {
		$result['error'] = 'Не удалось открыть файл для чтения.';
		return $result;
	}
	$headers = fgetcsv($handle, 0, $delimiter);
	if ($headers === false) {
		$result['error'] = 'Не удалось прочитать заголовок CSV.';
		fclose($handle);
		return $result;
	}
	$result['headers'] = $headers;
	while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
		if (count($row) < count($headers)) {
			$row = array_pad($row, count($headers), '');
		} elseif (count($row) > count($headers)) {
			$row = array_slice($row, 0, count($headers));
		}
		$result['rows'][] = $row;
	}
	fclose($handle);
	return $result;
}

// Загрузка файла CSV и сохранение в папку uploads
$uploadMessage = null;
$displayFile = __DIR__ . DIRECTORY_SEPARATOR . 'example_4kb.csv';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvFile'])) {
	$file = $_FILES['csvFile'];
	if ($file['error'] !== UPLOAD_ERR_OK) {
		$uploadMessage = ['type' => 'danger', 'text' => 'Ошибка загрузки файла (код ' . (int)$file['error'] . ').'];
	} else {
		$originalName = (string)$file['name'];
		$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
		$allowedExt = ['csv'];
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$mime = $finfo->file($file['tmp_name']) ?: '';
		$allowedMime = [
			'text/plain',
			'text/csv',
			'application/vnd.ms-excel',
		];
		if (!in_array($ext, $allowedExt, true)) {
			$uploadMessage = ['type' => 'danger', 'text' => 'Разрешены только файлы .csv'];
		} elseif ($file['size'] > 5 * 1024 * 1024) { // 5 MB limit
			$uploadMessage = ['type' => 'danger', 'text' => 'Размер файла слишком большой (макс. 5 МБ).'];
		} elseif (!in_array($mime, $allowedMime, true)) {
			$uploadMessage = ['type' => 'warning', 'text' => 'Может быть не CSV (MIME: ' . htmlspecialchars($mime, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '). Попытка обработать.'];
		}
		$targetName = date('Ymd_His') . '_' . preg_replace('/[^a-zA-Z0-9_.-]+/u', '_', $originalName);
		$targetPath = $uploadsDir . DIRECTORY_SEPARATOR . $targetName;
		if (@move_uploaded_file($file['tmp_name'], $targetPath)) {
			$displayFile = $targetPath;
			if ($uploadMessage === null) {
				$uploadMessage = ['type' => 'success', 'text' => 'Файл успешно загружен.'];
			} else {
				$uploadMessage['text'] .= ' Файл загружен.';
				$uploadMessage['type'] = $uploadMessage['type'] === 'danger' ? 'danger' : 'warning';
			}
		} else {
			$uploadMessage = ['type' => 'danger', 'text' => 'Не удалось сохранить загруженный файл.'];
		}
	}
}

$csv = readCsv($displayFile);

// Вывод HTML
?>
<!doctype html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>CSV Просмотр</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<style>
		.table-responsive { max-height: 70vh; }
		code.small { font-size: .85rem; }
	</style>
</head>
<body>
<div class="container py-4">
	<h1 class="mb-4">Просмотр CSV</h1>

	<div class="card mb-4">
		<div class="card-body">
			<form method="post" enctype="multipart/form-data" class="row gy-2 gx-3 align-items-center">
				<div class="col-sm-8">
					<input class="form-control" type="file" name="csvFile" accept=".csv" required>
				</div>
				<div class="col-auto">
					<button class="btn btn-primary" type="submit">Загрузить и показать</button>
				</div>
				<div class="col-12">
					<div class="form-text">Файл будет сохранён в <code class="small">Z2/uploads</code> и отображён ниже.</div>
				</div>
			</form>
			<?php if ($uploadMessage): ?>
				<div class="alert alert-<?php echo htmlspecialchars($uploadMessage['type'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?> mt-3" role="alert">
					<?php echo $uploadMessage['text']; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="d-flex align-items-center mb-2">
		<div class="me-2">Источник:</div>
		<code class="small me-3"><?php echo htmlspecialchars(str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $displayFile), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></code>
		<span class="badge bg-secondary">Разделитель: <?php echo $csv['delimiter'] === "\t" ? 'TAB' : htmlspecialchars($csv['delimiter'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
	</div>

	<?php if ($csv['error']): ?>
		<div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($csv['error'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
	<?php else: ?>
		<div class="table-responsive border rounded">
			<table class="table table-striped table-hover align-middle mb-0">
				<thead class="table-dark">
					<tr>
						<?php foreach ($csv['headers'] as $header): ?>
							<th scope="col"><?php echo htmlspecialchars((string)$header, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($csv['rows'] as $row): ?>
						<tr>
							<?php foreach ($row as $cell): ?>
								<td><?php echo nl2br(htmlspecialchars((string)$cell, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')); ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<footer class="mt-4 text-muted">
		<small>Задание: чтение и вывод CSV с использованием Bootstrap. Папка: <code class="small">Z2</code>.</small>
	</footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>



