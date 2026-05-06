<?php

declare(strict_types=1);

?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Обогащение компании</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 16px; color: #1f2937; }
        .card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; max-width: 760px; }
        .row { margin-bottom: 12px; }
        label { display: block; margin-bottom: 6px; font-size: 14px; color: #4b5563; }
        input { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; }
        button {
            background: #2563eb; color: #fff; border: 0; border-radius: 8px;
            padding: 10px 16px; cursor: pointer; font-weight: 600;
        }
        button:disabled { opacity: 0.6; cursor: not-allowed; }
        .result { margin-top: 16px; background: #f9fafb; border-radius: 8px; padding: 12px; }
        .muted { color: #6b7280; font-size: 13px; }
        pre { margin: 0; white-space: pre-wrap; word-break: break-word; }
    </style>
    <script src="//api.bitrix24.com/api/v1/"></script>
</head>
<body>
<div class="card">
    <h2 style="margin-top:0;">Обогащение компании</h2>
    <p class="muted">Укажите сайт компании или домен и нажмите "Обогатить".</p>

    <div class="row">
        <label for="domain">Сайт / домен</label>
        <input id="domain" placeholder="example.com">
    </div>

    <button id="enrichBtn" type="button">Обогатить</button>

    <div id="result" class="result" style="display:none;"></div>
</div>

<script src="./app.js"></script>
</body>
</html>
