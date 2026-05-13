# Bitrix24 — обогащение карточки компании

Встраиваемое приложение для Bitrix24: вкладка **«Обогатить»** в карточке компании (CRM). По домену сайта собираются контакты, соцсети, адрес, реквизиты и предлагаются поля для записи в CRM.

## Структура репозитория

| Путь | Назначение |
|------|------------|
| `bootstrap.php` | Константы корня проекта и путей (`ENRICHER_ROOT`, `ENRICHER_SRC`, `ENRICHER_STORAGE`, `ENRICHER_CONFIG`). Подключается из `public/` и `scripts/`. |
| `public/` | **Корень для веб-сервера** (DocumentRoot): `index.php` (UI), `enrich.php` (API), `install.php` (установка), `mapping.php` (маппинг UF), `app.js`. |
| `src/` | PHP: парсинг сайта, обогащение, Bitrix REST/AI, логирование. |
| `config/` | `config.php.example` — шаблон; `config.php` создаётся локально (не в git). |
| `storage/` | Записываемые данные: `logs/enrichment-history.jsonl`, `mapping/mapping-store.json`. В git только `.gitkeep`. |
| `scripts/` | CLI и Node: `debug-extractor.php`, `render-contact-data.js` (Puppeteer для SPA), `start-ngrok.js`. |

Зависимости Node (`puppeteer`, `@ngrok/ngrok`) нужны для fallback-рендера контактов и туннеля; каталог `node_modules/` в git не попадает.

## Требования

- PHP 8.1+ с расширениями: `curl`, `dom`, `json`, `mbstring`.
- Веб-сервер (Apache в XAMPP или nginx) с **DocumentRoot = `public/`** либо URL вида `…/public/index.php`.
- Для Bitrix24 в облаке — публичный **HTTPS** (часто ngrok к локальному Apache).
- Node.js 18+ для `npm install` (если используете Puppeteer-fallback и `npm run ngrok`).

## Установка

1. Клонировать репозиторий, перейти в каталог проекта.
2. `copy config\config.php.example config\config.php` (Windows) и при необходимости заполнить секции `ai`, `crm` (см. комментарии в example).
3. `npm install` — если нужны скрипты Node.
4. Убедиться, что веб-сервер отдаёт `public/index.php` по HTTPS-URL, который вы укажете в Bitrix.

## Bitrix24: локальное приложение

1. **Приложения → Разработчикам → Локальное приложение.**
2. URL приложения: `https://<ваш-хост>/public/index.php`  
   URL установки: `https://<ваш-хост>/public/install.php`
3. Права: CRM (`crm.company.get`, `crm.company.update`, `crm.company.fields`), при необходимости placement (`placement.bind` / `unbind`).
4. После установки откройте карточку компании → вкладка **Обогатить** → при необходимости **UF-поля** / сохранение маппинга → **Обогатить** → **Применить в CRM**.

## API

- **POST** `public/enrich.php` — тело JSON: `{ "domain": "example.com", "aiContext": { "portalDomain", "authToken" }, "contactUrl": "" }`. Ответ: `{ "ok", "suggestedFields": { ... } }`.
- **GET/POST** `public/mapping.php` — серверное хранение маппинга UF (см. `public/app.js`).

## Отладка парсера (CLI)

```bash
php scripts/debug-extractor.php example.com
```

## ngrok (Windows)

1. Токен: [ngrok dashboard](https://dashboard.ngrok.com/get-started/your-authtoken).
2. `setx NGROK_AUTHTOKEN "ваш_токен"` и новый терминал.
3. В каталоге проекта: `npm run ngrok` (проброс порта по умолчанию 80 — настройте под свой Apache).

## Лицензия

Используйте и дорабатывайте под свои нужды; при публикации форка укажите ссылку на исходный репозиторий по желанию.
