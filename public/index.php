<?php

declare(strict_types=1);

/**
 * Встраиваемое приложение Bitrix24: вкладка «Обогатить» в карточке компании.
 * Подключает UI и (после загрузки) public/app.js для REST и BX24.placement.
 */
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
        .actions { display: flex; gap: 8px; margin-top: 12px; }
        .mapping { margin-top: 16px; border-top: 1px solid #e5e7eb; padding-top: 12px; }
        .mapping table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .mapping th, .mapping td { text-align: left; border-bottom: 1px solid #eef2f7; padding: 6px 4px; font-size: 13px; }
        .mapping select { width: 100%; padding: 6px; border: 1px solid #d1d5db; border-radius: 6px; }
        .primary-apply { margin-top: 12px; width: 100%; background: #059669; padding: 12px 16px; font-size: 15px; }
    </style>
    <script src="//api.bitrix24.com/api/v1/"></script>
</head>
<body>
<div class="card">
    <h2 style="margin-top:0;">Обогащение компании</h2>
    <p class="muted">Укажите сайт компании или домен и нажмите "Обогатить".</p>
    <p id="contextInfo" class="muted">Проверяем контекст Bitrix24...</p>

    <div class="row">
        <label for="domain">Сайт / домен</label>
        <input id="domain" placeholder="example.com">
    </div>

    <div class="actions">
        <button id="enrichBtn" type="button" onclick="window.__runEnrichFallback && window.__runEnrichFallback()">Обогатить</button>
        <button id="applyBtnFallback" type="button" style="display:none;background:#059669;" onclick="window.__applyFallbackToCrm && window.__applyFallbackToCrm()">Применить в CRM</button>
        <button id="applyBtn" type="button" style="display:none;background:#059669;">Применить в CRM</button>
        <button id="loadUfBtn" type="button" style="display:none;background:#374151;">UF-поля</button>
    </div>

    <div id="mappingWrap" class="mapping" style="display:none;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <strong>Маппинг кастомных полей</strong>
            <button id="saveMappingBtn" type="button" style="background:#1f2937;">Сохранить маппинг</button>
        </div>
        <p class="muted">Выберите, какие данные обогащения писать в каждое UF_CRM поле.</p>
        <div id="mappingTable"></div>
    </div>

    <div id="result" class="result" style="display:none;"></div>
    <button id="applyBtnBottom" type="button" class="primary-apply" style="display:none;">Применить в CRM</button>
</div>

<script>
/**
 * Только основной домен: без схемы, www, пути, query и hash (как на сервере).
 */
window.__canonicalSiteInput = function(raw) {
    raw = String(raw || '').trim();
    if (!raw) {
        return '';
    }
    var probe = raw;
    try {
        if (!/^https?:\/\//i.test(probe) && !/^\/\//.test(probe)) {
            probe = 'https://' + probe.replace(/^\/+/, '');
        }
        var u = new URL(probe);
        var h = (u.hostname || '').toLowerCase();
        if (h.indexOf('www.') === 0) {
            h = h.slice(4);
        }
        return h || '';
    } catch (e) {
        var s = raw.replace(/^\/\/+/, '').split('?')[0].split('#')[0];
        var slash = s.indexOf('/');
        if (slash >= 0) {
            s = s.slice(0, slash);
        }
        s = s.replace(/^www\./i, '').toLowerCase().trim();
        return s;
    }
};

/** Абсолютный URL к enrich.php (корректно при сложном path в Bitrix). */
window.__resolveEnrichPhpUrl = function () {
    try {
        return new URL('enrich.php', window.location.href).href;
    } catch (e2) {
        return './enrich.php';
    }
};

window.__runEnrichFallback = async function () {
    const btn = document.getElementById('enrichBtn');
    const applyBtnFallback = document.getElementById('applyBtnFallback');
    const input = document.getElementById('domain');
    const box = document.getElementById('result');
    const domainRaw = (input && input.value ? input.value : '').trim();
    const domain = window.__canonicalSiteInput(domainRaw) || domainRaw;
    if (input && domain) {
        input.value = domain;
    }
    if (!domain) {
        box.style.display = 'block';
        box.style.background = '#fef2f2';
        box.innerHTML = '<pre>{"error":"Введите домен компании"}</pre>';
        return;
    }

    if (!btn) {
        if (box) {
            box.style.display = 'block';
            box.style.background = '#fef2f2';
            box.innerHTML = '<pre>{"error":"Не найдена кнопка обогащения на странице"}</pre>';
        }
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Обогащаем...';
    try {
        var enrichUrl = typeof window.__resolveEnrichPhpUrl === 'function'
            ? window.__resolveEnrichPhpUrl()
            : './enrich.php';
        const response = await fetch(enrichUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ domain }),
        });
        const rawText = await response.text();
        var data = null;
        try {
            data = JSON.parse(rawText);
        } catch (parseErr) {
            throw new Error('Сервер вернул не-JSON ответ: ' + rawText.slice(0, 220));
        }
        window.__lastSuggestedFields = data && data.suggestedFields ? data.suggestedFields : null;
        box.style.display = 'block';
        box.style.background = response.ok ? '#f9fafb' : '#fef2f2';
        if (response.ok && data && data.ok && data.suggestedFields) {
            var f = data.suggestedFields || {};
            var deptRaw = String(f.DEPARTMENT_CONTACTS || '');
            var normalizedEmail = String(f.EMAIL || '').trim().toLowerCase();
            var last10digits = function(s) {
                var d = String(s || '').replace(/\D/g, '');
                if (d.length >= 11 && (d[0] === '7' || d[0] === '8')) {
                    return d.slice(-10);
                }
                if (d.length >= 10) {
                    return d.slice(-10);
                }
                return d;
            };
            var seenDeptPhoneDigits = {};
            [f.PHONE, f.DEPT_PROMO_CONTACT, f.DEPT_ADS_CONTACT, f.DEPT_SUPPORT_CONTACT].forEach(function(v) {
                var t = last10digits(v);
                if (t) {
                    seenDeptPhoneDigits[t] = true;
                }
            });
            if (!f.EMAIL && deptRaw) {
                var deptEmailMatch = deptRaw.match(/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i);
                if (deptEmailMatch && deptEmailMatch[0]) {
                    f.EMAIL = String(deptEmailMatch[0]).toLowerCase();
                    if (window.__lastSuggestedFields) {
                        window.__lastSuggestedFields.EMAIL = f.EMAIL;
                    }
                }
            }
            var dynamicDeptFields = [];
            var normalizedPhone = String(f.PHONE || '').replace(/\D+/g, '');
            if (f.DEPARTMENT_CONTACTS) {
                String(f.DEPARTMENT_CONTACTS).split('|').forEach(function(part, idx) {
                    var row = String(part || '').trim();
                    if (!row) return;
                    var sep = row.indexOf(':');
                    if (sep <= 0) return;
                    var label = row.slice(0, sep).trim();
                    var value = row.slice(sep + 1).trim();
                    if (!label || !value) return;
                    if (normalizedEmail && value.indexOf('@') >= 0) {
                        var emailOnly = value.trim().toLowerCase();
                        if (emailOnly === normalizedEmail) return;
                    }
                    if (value.indexOf('@') < 0) {
                        var d10 = last10digits(value);
                        if (d10 && seenDeptPhoneDigits[d10]) return;
                        if (d10) seenDeptPhoneDigits[d10] = true;
                    }
                    // Do not show department row when it just duplicates main phone (full digit string).
                    if (normalizedPhone) {
                        var deptPhone = value.replace(/\D+/g, '');
                        if (deptPhone && deptPhone === normalizedPhone) return;
                    }
                    dynamicDeptFields.push({
                        key: 'DEPT_DYNAMIC_' + idx,
                        label: label,
                        value: value
                    });
                });
            }
            // Remove duplicate department rows.
            var seenDept = {};
            dynamicDeptFields = dynamicDeptFields.filter(function(item) {
                var k = (item.label + '::' + item.value).toLowerCase();
                if (seenDept[k]) return false;
                seenDept[k] = true;
                return true;
            });

            var sameAsMainPhone = function(v) {
                var main = String(f.PHONE || '').replace(/\D+/g, '');
                if (!main) return false;
                var cur = String(v || '').replace(/\D+/g, '');
                return !!cur && cur === main;
            };
            var sameAsMainEmail = function(v) {
                if (!normalizedEmail) return false;
                return String(v || '').trim().toLowerCase() === normalizedEmail;
            };
            var genericDeptValue = '';
            if (dynamicDeptFields.length === 0 && f.DEPARTMENT_CONTACTS) {
                var hasEmailInDept = /@/.test(String(f.DEPARTMENT_CONTACTS));
                if (!f.PHONE || hasEmailInDept) {
                    genericDeptValue = f.DEPARTMENT_CONTACTS;
                }
            }

            var fieldsForChoice = [
                { key: 'TITLE', label: 'Название', value: f.TITLE },
                { key: 'WEB', label: 'Сайт', value: f.WEB },
                { key: 'EMAIL', label: 'Email', value: f.EMAIL },
                { key: 'PHONE', label: 'Телефон', value: f.PHONE },
                { key: 'DEPT_PROMO_CONTACT', label: 'Вопросы по акциям', value: (sameAsMainPhone(f.DEPT_PROMO_CONTACT) || sameAsMainEmail(f.DEPT_PROMO_CONTACT)) ? '' : f.DEPT_PROMO_CONTACT },
                { key: 'DEPT_ADS_CONTACT', label: 'Рекламный отдел', value: (sameAsMainPhone(f.DEPT_ADS_CONTACT) || sameAsMainEmail(f.DEPT_ADS_CONTACT)) ? '' : f.DEPT_ADS_CONTACT },
                { key: 'DEPT_SUPPORT_CONTACT', label: 'Техническая поддержка', value: (sameAsMainPhone(f.DEPT_SUPPORT_CONTACT) || sameAsMainEmail(f.DEPT_SUPPORT_CONTACT)) ? '' : f.DEPT_SUPPORT_CONTACT },
                { key: 'INN', label: 'ИНН', value: f.INN },
                { key: 'KPP', label: 'КПП', value: f.KPP },
                { key: 'OGRN', label: 'ОГРН', value: f.OGRN },
                { key: 'LEGAL_EMAIL', label: 'Юридический email', value: f.LEGAL_EMAIL },
                { key: 'SOCIAL_HANDLES', label: 'Соц. аккаунты', value: f.SOCIAL_HANDLES },
                // Show generic row if there are no parsed rows and either there is no
                // main phone or department contacts contain email data.
                { key: 'DEPARTMENT_CONTACTS', label: 'Контакты отделов', value: genericDeptValue },
                { key: 'INDUSTRY', label: 'Отрасль', value: f.INDUSTRY },
                { key: 'ADDRESS', label: 'Адрес', value: f.ADDRESS },
                { key: 'ADDRESS_CITY', label: 'Город', value: f.ADDRESS_CITY },
                { key: 'COMMENTS', label: 'Комментарий', value: f.COMMENTS }
            ]
            .concat(dynamicDeptFields)
            .filter(function(item) { return item.value && String(item.value).trim() !== ''; });

            window.__selectedFieldKeys = {};
            fieldsForChoice.forEach(function(item) { window.__selectedFieldKeys[item.key] = true; });

            var html = '<div><strong>Данные для заполнения найдены.</strong></div>';
            html += '<div style="margin:8px 0 10px 0;">';
            html += '<button type="button" id="selectAllFieldsBtn" style="margin-right:8px;background:#374151;padding:6px 10px;border-radius:6px;font-size:12px;">Выбрать все</button>';
            html += '<button type="button" id="clearAllFieldsBtn" style="background:#6b7280;padding:6px 10px;border-radius:6px;font-size:12px;">Снять все</button>';
            html += '</div>';
            html += '<div style="margin-top:8px;">';

            fieldsForChoice.forEach(function(item, idx) {
                var safeVal = String(item.value).replace(/</g, '&lt;').replace(/>/g, '&gt;');
                var inputId = 'crm-field-check-' + idx;
                html += '<div style="display:grid;grid-template-columns:18px minmax(0,1fr);column-gap:8px;align-items:start;margin-bottom:8px;">'
                    + '<input id="' + inputId + '" type="checkbox" class="crm-field-check" data-key="' + item.key + '" checked style="margin-top:2px;">'
                    + '<label for="' + inputId + '" style="margin:0;display:block;cursor:pointer;line-height:1.35;">'
                    + '<strong>' + item.label + ':</strong> ' + safeVal
                    + '</label>'
                    + '</div>';
            });
            html += '</div>';
            box.innerHTML = html;

            var bindChecks = function() {
                box.querySelectorAll('.crm-field-check').forEach(function(el) {
                    el.addEventListener('change', function() {
                        var key = el.getAttribute('data-key');
                        if (!key) return;
                        window.__selectedFieldKeys[key] = !!el.checked;
                    });
                });
            };
            bindChecks();

            var selectAllBtn = document.getElementById('selectAllFieldsBtn');
            var clearAllBtn = document.getElementById('clearAllFieldsBtn');
            if (selectAllBtn) {
                selectAllBtn.addEventListener('click', function() {
                    box.querySelectorAll('.crm-field-check').forEach(function(el) {
                        el.checked = true;
                        var key = el.getAttribute('data-key');
                        if (key) window.__selectedFieldKeys[key] = true;
                    });
                });
            }
            if (clearAllBtn) {
                clearAllBtn.addEventListener('click', function() {
                    box.querySelectorAll('.crm-field-check').forEach(function(el) {
                        el.checked = false;
                        var key = el.getAttribute('data-key');
                        if (key) window.__selectedFieldKeys[key] = false;
                    });
                });
            }
        } else {
            box.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }
        if (response.ok && data && data.ok && window.__lastSuggestedFields) {
            applyBtnFallback.style.display = 'inline-block';
        } else {
            applyBtnFallback.style.display = 'none';
        }
    } catch (e) {
        box.style.display = 'block';
        box.style.background = '#fef2f2';
        var enrichUrlDbg = typeof window.__resolveEnrichPhpUrl === 'function' ? window.__resolveEnrichPhpUrl() : './enrich.php';
        var hint = 'Браузер не смог достучаться до ' + enrichUrlDbg + '. Проверьте: сервер/XAMPP запущен, туннель ngrok активен, в адресе приложения в Bitrix указан тот же HTTPS-хост, нет блокировки антивирусом. Откройте enrich.php в новой вкладке — должен отвечать JSON.';
        box.innerHTML = '<pre>' + JSON.stringify({
            error: 'Fallback enrich failed',
            details: String(e),
            enrichUrl: enrichUrlDbg,
            hint: hint
        }, null, 2) + '</pre>';
        applyBtnFallback.style.display = 'none';
    } finally {
        btn.disabled = false;
        btn.textContent = 'Обогатить';
    }
};

window.__applyFallbackToCrm = function () {
    const box = document.getElementById('result');
    const suggested = window.__lastSuggestedFields || null;
    if (!suggested) {
        box.style.display = 'block';
        box.style.background = '#fef2f2';
        box.innerHTML = '<pre>{"error":"Сначала выполните обогащение"}</pre>';
        return;
    }

    if (!window.BX24 || typeof window.BX24.callMethod !== 'function') {
        box.style.display = 'block';
        box.style.background = '#fef2f2';
        box.innerHTML = '<pre>{"error":"BX24 API недоступен в fallback-режиме"}</pre>';
        return;
    }

    const placementInfo = (window.BX24 && window.BX24.placement && window.BX24.placement.info) ? window.BX24.placement.info() : {};
    const findIdInText = function(text) {
        if (!text) return 0;
        var patterns = [
            /\/crm\/company\/details\/(\d+)\//i,
            /[?&](?:id|ID|entityId|ENTITY_ID|COMPANY_ID)=(\d+)/i,
            /(?:ENTITY_ID|ENTITY_VALUE_ID|COMPANY_ID)["'%:\s=]+(\d{1,10})/i,
            /PLACEMENT_OPTIONS.*?(?:ENTITY_ID|ENTITY_VALUE_ID|COMPANY_ID).*?(\d{1,10})/i
        ];
        for (var i = 0; i < patterns.length; i++) {
            var m = String(text).match(patterns[i]);
            if (m && m[1]) {
                var parsed = Number(m[1]);
                if (parsed > 0) return parsed;
            }
        }
        return 0;
    };
    const rawId = placementInfo.ENTITY_ID || placementInfo.ENTITY_VALUE_ID || placementInfo.COMPANY_ID || placementInfo.ID ||
        (placementInfo.options && (placementInfo.options.ID || placementInfo.options.ENTITY_ID || placementInfo.options.ENTITY_VALUE_ID || placementInfo.options.COMPANY_ID)) ||
        (placementInfo.placementOptions && (placementInfo.placementOptions.ID || placementInfo.placementOptions.ENTITY_ID || placementInfo.placementOptions.ENTITY_VALUE_ID || placementInfo.placementOptions.COMPANY_ID));
    var companyId = Number(rawId);
    if (!companyId) {
        companyId = findIdInText(window.location.href || '');
    }
    if (!companyId) {
        companyId = findIdInText(decodeURIComponent(window.location.href || ''));
    }
    if (!companyId) {
        companyId = findIdInText(window.location.search || '');
    }
    if (!companyId) {
        companyId = findIdInText(window.location.hash || '');
    }
    if (!companyId) {
        companyId = findIdInText(document.referrer || '');
    }
    if (!companyId) {
        box.style.display = 'block';
        box.style.background = '#fef2f2';
        box.innerHTML = '<pre>' + JSON.stringify({
            error: 'Не удалось определить ID компании',
            hint: 'Откройте существующую карточку компании и повторите.',
            debug: {
                placementInfo: placementInfo,
                href: window.location.href || '',
                search: window.location.search || '',
                hash: window.location.hash || '',
                referrer: document.referrer || ''
            }
        }, null, 2) + '</pre>';
        return;
    }

    const selected = window.__selectedFieldKeys || {};
    const fields = {};
    if (selected.TITLE && suggested.TITLE) fields.TITLE = String(suggested.TITLE);
    if (selected.COMMENTS && suggested.COMMENTS) fields.COMMENTS = String(suggested.COMMENTS);
    if (selected.ADDRESS && suggested.ADDRESS) fields.ADDRESS = String(suggested.ADDRESS);
    if (selected.ADDRESS_CITY && suggested.ADDRESS_CITY) fields.ADDRESS_CITY = String(suggested.ADDRESS_CITY);
    if (selected.INDUSTRY && suggested.INDUSTRY) fields.INDUSTRY = String(suggested.INDUSTRY);
    if (selected.WEB && suggested.WEB) fields.WEB = [{ VALUE: String(suggested.WEB), VALUE_TYPE: 'WORK' }];
    if (selected.EMAIL && suggested.EMAIL) fields.EMAIL = [{ VALUE: String(suggested.EMAIL), VALUE_TYPE: 'WORK' }];
    if (selected.PHONE && suggested.PHONE) fields.PHONE = [{ VALUE: String(suggested.PHONE), VALUE_TYPE: 'WORK' }];
    if (selected.SOCIAL_HANDLES && suggested.SOCIAL_HANDLES && !suggested.UF_CRM_SOCIAL_HANDLES) {
        if (suggested.UF_CRM_SOCIAL_HANDLES) {
            fields.UF_CRM_SOCIAL_HANDLES = String(suggested.UF_CRM_SOCIAL_HANDLES);
        } else {
            var existingComment = String(fields.COMMENTS || '');
            var socialsLine = 'Соцсети: ' + String(suggested.SOCIAL_HANDLES);
            fields.COMMENTS = existingComment ? (existingComment + '\n' + socialsLine) : socialsLine;
        }
    }
    if (selected.DEPARTMENT_CONTACTS && suggested.DEPARTMENT_CONTACTS) {
        var baseComment = String(fields.COMMENTS || '');
        var deptLine = 'Контакты отделов: ' + String(suggested.DEPARTMENT_CONTACTS);
        fields.COMMENTS = baseComment ? (baseComment + '\n' + deptLine) : deptLine;
    }
    if (selected.DEPT_PROMO_CONTACT && suggested.DEPT_PROMO_CONTACT) {
        var basePromo = String(fields.COMMENTS || '');
        var promoLine = 'Вопросы по акциям: ' + String(suggested.DEPT_PROMO_CONTACT);
        fields.COMMENTS = basePromo ? (basePromo + '\n' + promoLine) : promoLine;
    }
    if (selected.DEPT_ADS_CONTACT && suggested.DEPT_ADS_CONTACT) {
        var baseAds = String(fields.COMMENTS || '');
        var adsLine = 'Рекламный отдел: ' + String(suggested.DEPT_ADS_CONTACT);
        fields.COMMENTS = baseAds ? (baseAds + '\n' + adsLine) : adsLine;
    }
    if (selected.DEPT_SUPPORT_CONTACT && suggested.DEPT_SUPPORT_CONTACT) {
        var baseSupport = String(fields.COMMENTS || '');
        var supportLine = 'Техническая поддержка: ' + String(suggested.DEPT_SUPPORT_CONTACT);
        fields.COMMENTS = baseSupport ? (baseSupport + '\n' + supportLine) : supportLine;
    }
    Object.keys(selected).forEach(function(key) {
        if (!/^DEPT_DYNAMIC_/i.test(key) || !selected[key]) return;
        var idx = Number(String(key).replace('DEPT_DYNAMIC_', ''));
        if (Number.isNaN(idx) || !suggested.DEPARTMENT_CONTACTS) return;
        var rows = String(suggested.DEPARTMENT_CONTACTS).split('|').map(function(v) { return String(v).trim(); }).filter(Boolean);
        var row = rows[idx] || '';
        if (!row) return;
        var baseDyn = String(fields.COMMENTS || '');
        fields.COMMENTS = baseDyn ? (baseDyn + '\n' + row) : row;
    });
    if (selected.INN && suggested.INN) {
        var baseCommentInn = String(fields.COMMENTS || '');
        var innLine = 'ИНН: ' + String(suggested.INN);
        fields.COMMENTS = baseCommentInn ? (baseCommentInn + '\n' + innLine) : innLine;
    }
    if (selected.KPP && suggested.KPP) {
        var baseCommentKpp = String(fields.COMMENTS || '');
        var kppLine = 'КПП: ' + String(suggested.KPP);
        fields.COMMENTS = baseCommentKpp ? (baseCommentKpp + '\n' + kppLine) : kppLine;
    }
    if (selected.OGRN && suggested.OGRN) {
        var baseCommentOgrn = String(fields.COMMENTS || '');
        var ogrnLine = 'ОГРН: ' + String(suggested.OGRN);
        fields.COMMENTS = baseCommentOgrn ? (baseCommentOgrn + '\n' + ogrnLine) : ogrnLine;
    }
    if (selected.LEGAL_EMAIL && suggested.LEGAL_EMAIL) {
        var baseCommentLeg = String(fields.COMMENTS || '');
        var legLine = 'Юридический email: ' + String(suggested.LEGAL_EMAIL);
        fields.COMMENTS = baseCommentLeg ? (baseCommentLeg + '\n' + legLine) : legLine;
    }
    Object.keys(suggested).forEach(function (key) {
        if (/^UF_CRM_/i.test(key) && suggested[key]) {
            fields[key] = String(suggested[key]);
        }
    });
    if (Object.keys(fields).length === 0) {
        box.style.display = 'block';
        box.style.background = '#fef2f2';
        box.innerHTML = '<pre>{"error":"Выберите хотя бы одно поле для сохранения"}</pre>';
        return;
    }

    window.BX24.callMethod('crm.company.update', { id: companyId, fields: fields }, function (result) {
        box.style.display = 'block';
        if (result.error()) {
            box.style.background = '#fef2f2';
            box.innerHTML = '<div><strong>Не удалось сохранить данные в CRM.</strong></div>' +
                '<div style="margin-top:8px;color:#991b1b;">' + String(result.error()).replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</div>';
            return;
        }
        box.style.background = '#f9fafb';
        box.innerHTML = '<div><strong>Готово.</strong></div>' +
            '<div style="margin-top:8px;">Данные успешно сохранены в карточку компании.</div>';
    });
};
</script>
<script src="./app.js?v=20260510-1645"></script>
</body>
</html>
