# Bitrix24 Company Enricher

Приложение для Bitrix24 с кнопкой **"Обогатить"** в карточке компании.
Пользователь нажимает кнопку, приложение получает домен/сайт компании, собирает базовые данные и предлагает заполнить поля CRM.

## Что уже в проекте

- каркас embedded-приложения Bitrix24;
- UI с кнопкой "Обогатить";
- endpoint для запуска обогащения;
- заготовка сервиса обогащения (пока с mock-логикой);
- endpoint установки, где можно привязать placement.

## Структура

- `public/index.php` — UI внутри Bitrix24;
- `public/enrich.php` — API для кнопки "Обогатить";
- `public/install.php` — обработка события установки;
- `src/CompanyEnricher.php` — сервис подготовки данных;
- `config/config.php.example` — пример конфигурации.

## Быстрый старт

1. Скопировать проект в веб-директорию (у вас уже `c:\xampp\htdocs\work\b24-company-enricher`).
2. Создать `config/config.php` из `config/config.php.example`.
3. Открыть в браузере:
   - `http://localhost/work/b24-company-enricher/public/index.php`
4. При подключении в Bitrix24 указать URL приложения и обработчик установки:
   - app: `/public/index.php`
   - install handler: `/public/install.php`

## Следующие шаги (MVP)

1. Реализовать получение домена из карточки компании через REST Bitrix24.
2. Подключить реальный парсинг сайта.
3. Подключить AI в Bitrix24 для структурирования данных.
4. Сделать предпросмотр и подтверждение заполнения полей.
5. Записывать поля в CRM через `crm.company.update`.
