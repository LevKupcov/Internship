/**
 * Полноценный UI Bitrix24: placement, crm.company.*, обогащение, UF-маппинг, применение в CRM.
 * Загружается после public/index.php; использует enrich.php / mapping.php на том же хосте.
 */
(() => {
  window.__enricherLoaded = false;
  const bootSafe = (fn) => {
    try {
      fn();
    } catch (error) {
      const box = document.getElementById("result");
      if (box) {
        box.style.display = "block";
        box.style.background = "#fef2f2";
        box.innerHTML = `<pre>${JSON.stringify(
          {
            ok: false,
            error: "Ошибка инициализации интерфейса",
            details: String(error),
          },
          null,
          2
        )}</pre>`;
      }
    }
  };

  bootSafe(() => {
  const enrichBtn = document.getElementById("enrichBtn");
  if (!enrichBtn) {
    throw new Error("Не найден элемент #enrichBtn");
  }
  const applyBtn = document.getElementById("applyBtn");
  const applyBtnBottom = document.getElementById("applyBtnBottom");
  const loadUfBtn = document.getElementById("loadUfBtn");
  const saveMappingBtn = document.getElementById("saveMappingBtn");
  const domainInput = document.getElementById("domain");
  const resultBox = document.getElementById("result");
  const contextInfo = document.getElementById("contextInfo");
  const mappingWrap = document.getElementById("mappingWrap");
  const mappingTable = document.getElementById("mappingTable");

  let currentCompanyId = null;
  let latestSuggestedFields = null;
  let runtimeMapping = {};

  const MAPPING_SOURCE_OPTIONS = [
    { key: "", label: "Не заполнять" },
    { key: "industry", label: "Отрасль (INDUSTRY)" },
    { key: "city", label: "Город (ADDRESS_CITY)" },
    { key: "socials_raw", label: "Соцсети (SOCIALS_RAW)" },
    { key: "social_handles", label: "Соц. аккаунты (SOCIAL_HANDLES)" },
    { key: "department_contacts", label: "Контакты отделов (DEPARTMENT_CONTACTS)" },
    { key: "inn", label: "ИНН (INN)" },
    { key: "kpp", label: "КПП (KPP)" },
    { key: "ogrn", label: "ОГРН (OGRN)" },
    { key: "legal_email", label: "Юридический email (LEGAL_EMAIL)" },
    { key: "telegram_url", label: "Telegram ссылка (TELEGRAM)" },
    { key: "telegram_username", label: "Telegram username (TELEGRAM_USERNAME)" },
    { key: "profile_summary", label: "Краткая сводка (PROFILE_SUMMARY)" },
    { key: "comments", label: "Комментарий (COMMENTS)" },
  ];

  const renderResult = (payload, isError = false) => {
    resultBox.style.display = "block";
    resultBox.style.background = isError ? "#fef2f2" : "#f9fafb";
    if (isError) {
      resultBox.innerHTML = `<pre>${JSON.stringify(payload, null, 2)}</pre>`;
      return;
    }

    const fields = payload && payload.suggestedFields ? payload.suggestedFields : {};
    const rows = [
      ["Название", fields.TITLE || ""],
      ["Сайт", fields.WEB || ""],
      ["Email", fields.EMAIL || ""],
      ["Телефон", fields.PHONE || ""],
      ["Вопросы по акциям", fields.DEPT_PROMO_CONTACT || ""],
      ["Рекламный отдел", fields.DEPT_ADS_CONTACT || ""],
      ["Техническая поддержка", fields.DEPT_SUPPORT_CONTACT || ""],
      ["ИНН", fields.INN || ""],
      ["КПП", fields.KPP || ""],
      ["ОГРН", fields.OGRN || ""],
      ["Юридический email", fields.LEGAL_EMAIL || ""],
      ["Соц. аккаунты", fields.SOCIAL_HANDLES || ""],
      ["Контакты отделов", fields.DEPARTMENT_CONTACTS || ""],
      ["Отрасль", fields.INDUSTRY || ""],
      ["Адрес", fields.ADDRESS || ""],
      ["Город", fields.ADDRESS_CITY || ""],
      ["Комментарий", fields.COMMENTS || ""],
    ]
      .filter(([, value]) => String(value).trim() !== "")
      .map(
        ([label, value]) =>
          `<div style="margin-bottom:8px;"><strong>${label}:</strong> ${String(value)
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")}</div>`
      )
      .join("");

    resultBox.innerHTML = `
      <div><strong>Данные для заполнения найдены.</strong></div>
      <div style="margin-top:8px;">${rows || '<span class="muted">Нет данных для отображения</span>'}</div>
    `;
  };

  const setApplyButtonsState = (visible, disabled = false, label = "Применить в CRM") => {
    [applyBtn, applyBtnBottom].forEach((btn) => {
      if (!btn) return;
      btn.style.display = visible ? "inline-block" : "none";
      btn.disabled = disabled;
      btn.textContent = label;
    });
  };

  const bx24Call = (method, params) =>
    new Promise((resolve, reject) => {
      if (!window.BX24 || typeof window.BX24.callMethod !== "function") {
        reject(new Error("BX24 API недоступен"));
        return;
      }

      window.BX24.callMethod(method, params || {}, (result) => {
        if (result.error()) {
          reject(new Error(result.error()));
          return;
        }
        resolve(result.data());
      });
    });

  const extractDomain = (value) => {
    if (!value) return "";
    const cleaned = String(value).trim().replace(/^https?:\/\//i, "");
    const host = cleaned.split("/")[0].split("?")[0].split("#")[0].toLowerCase();
    return host.startsWith("www.") ? host.slice(4) : host;
  };

  const canonicalDomain = (raw) => {
    const s = String(raw || "").trim();
    if (!s) return "";
    if (typeof window.__canonicalSiteInput === "function") {
      return window.__canonicalSiteInput(s) || "";
    }
    return extractDomain(s);
  };

  const normalizeDomainInput = () => {
    if (!domainInput) return;
    const c = canonicalDomain(domainInput.value);
    if (c) domainInput.value = c;
  };

  if (domainInput) {
    domainInput.addEventListener("blur", normalizeDomainInput);
    domainInput.addEventListener("paste", () => setTimeout(normalizeDomainInput, 0));
  }

  const extractCompanyId = (placementInfo) => {
    if (!placementInfo || typeof placementInfo !== "object") {
      return null;
    }

    const candidates = [
      placementInfo.ENTITY_ID,
      placementInfo.ENTITY_VALUE_ID,
      placementInfo.COMPANY_ID,
      placementInfo.ID,
      placementInfo.options && placementInfo.options.ID,
      placementInfo.options && placementInfo.options.ENTITY_ID,
      placementInfo.options && placementInfo.options.ENTITY_VALUE_ID,
      placementInfo.options && placementInfo.options.COMPANY_ID,
      placementInfo.placementOptions && placementInfo.placementOptions.ID,
      placementInfo.placementOptions && placementInfo.placementOptions.ENTITY_ID,
      placementInfo.placementOptions &&
        placementInfo.placementOptions.ENTITY_VALUE_ID,
      placementInfo.placementOptions &&
        placementInfo.placementOptions.COMPANY_ID,
    ];

    for (const id of candidates) {
      const parsed = Number(id);
      if (Number.isInteger(parsed) && parsed > 0) {
        return parsed;
      }
    }

    return null;
  };

  const toCrmFields = (source, mapping) => {
    const fields = {};
    const safeTextFields = [
      "TITLE",
      "COMMENTS",
      "ADDRESS",
      "ADDRESS_CITY",
      "ADDRESS_REGION",
      "ADDRESS_PROVINCE",
      "ADDRESS_COUNTRY",
      "ADDRESS_POSTAL_CODE",
    ];

    safeTextFields.forEach((key) => {
      const value = (source[key] || "").toString().trim();
      if (value) {
        fields[key] = value;
      }
    });

    const webValue = (source.WEB || "").toString().trim();
    if (webValue) {
      fields.WEB = [{ VALUE: webValue, VALUE_TYPE: "WORK" }];
    }

    const emailValue = (source.EMAIL || "").toString().trim();
    if (emailValue) {
      fields.EMAIL = [{ VALUE: emailValue, VALUE_TYPE: "WORK" }];
    }

    const phoneValue = (source.PHONE || "").toString().trim();
    if (phoneValue) {
      fields.PHONE = [{ VALUE: phoneValue, VALUE_TYPE: "WORK" }];
    }

    Object.keys(source).forEach((key) => {
      if (!/^UF_CRM_/i.test(key)) {
        return;
      }

      const value = source[key];
      if (typeof value === "string" && value.trim()) {
        fields[key] = value.trim();
      }
    });

    const prepared = {
      industry: (source.INDUSTRY || "").toString().trim(),
      city: (source.ADDRESS_CITY || "").toString().trim(),
      socials_raw: (
        source.SOCIALS_RAW ||
        source.UF_CRM_SOCIALS_RAW ||
        ""
      )
        .toString()
        .trim(),
      social_handles: (source.SOCIAL_HANDLES || "").toString().trim(),
      department_contacts: (source.DEPARTMENT_CONTACTS || "").toString().trim(),
      dept_promo_contact: (source.DEPT_PROMO_CONTACT || "").toString().trim(),
      dept_ads_contact: (source.DEPT_ADS_CONTACT || "").toString().trim(),
      dept_support_contact: (source.DEPT_SUPPORT_CONTACT || "").toString().trim(),
      inn: (source.INN || "").toString().trim(),
      kpp: (source.KPP || "").toString().trim(),
      ogrn: (source.OGRN || "").toString().trim(),
      legal_email: (source.LEGAL_EMAIL || "").toString().trim(),
      profile_summary: (
        source.PROFILE_SUMMARY ||
        source.AI_SUMMARY ||
        ""
      )
        .toString()
        .trim(),
      telegram_url: (source.TELEGRAM || "").toString().trim(),
      telegram_username: (source.TELEGRAM_USERNAME || "").toString().trim(),
      comments: (source.COMMENTS || "").toString().trim(),
    };

    // If there are no dedicated UF mappings for socials/department contacts,
    // keep the data in COMMENTS so it is not lost on save.
    if (prepared.social_handles) {
      const socialsLine = `Соцсети: ${prepared.social_handles}`;
      prepared.comments = prepared.comments
        ? `${prepared.comments}\n${socialsLine}`
        : socialsLine;
      fields.COMMENTS = prepared.comments;
    }
    if (prepared.department_contacts) {
      const deptLine = `Контакты отделов: ${prepared.department_contacts}`;
      prepared.comments = prepared.comments
        ? `${prepared.comments}\n${deptLine}`
        : deptLine;
      fields.COMMENTS = prepared.comments;
    }
    if (prepared.dept_promo_contact) {
      prepared.comments = prepared.comments
        ? `${prepared.comments}\nВопросы по акциям: ${prepared.dept_promo_contact}`
        : `Вопросы по акциям: ${prepared.dept_promo_contact}`;
      fields.COMMENTS = prepared.comments;
    }
    if (prepared.dept_ads_contact) {
      prepared.comments = prepared.comments
        ? `${prepared.comments}\nРекламный отдел: ${prepared.dept_ads_contact}`
        : `Рекламный отдел: ${prepared.dept_ads_contact}`;
      fields.COMMENTS = prepared.comments;
    }
    if (prepared.dept_support_contact) {
      prepared.comments = prepared.comments
        ? `${prepared.comments}\nТехническая поддержка: ${prepared.dept_support_contact}`
        : `Техническая поддержка: ${prepared.dept_support_contact}`;
      fields.COMMENTS = prepared.comments;
    }
    if (prepared.inn) {
      prepared.comments = prepared.comments
        ? `${prepared.comments}\nИНН: ${prepared.inn}`
        : `ИНН: ${prepared.inn}`;
      fields.COMMENTS = prepared.comments;
    }
    if (prepared.kpp) {
      prepared.comments = prepared.comments
        ? `${prepared.comments}\nКПП: ${prepared.kpp}`
        : `КПП: ${prepared.kpp}`;
      fields.COMMENTS = prepared.comments;
    }
    if (prepared.ogrn) {
      prepared.comments = prepared.comments
        ? `${prepared.comments}\nОГРН: ${prepared.ogrn}`
        : `ОГРН: ${prepared.ogrn}`;
      fields.COMMENTS = prepared.comments;
    }
    if (prepared.legal_email) {
      prepared.comments = prepared.comments
        ? `${prepared.comments}\nЮридический email: ${prepared.legal_email}`
        : `Юридический email: ${prepared.legal_email}`;
      fields.COMMENTS = prepared.comments;
    }

    Object.keys(mapping || {}).forEach((ufField) => {
      const sourceKey = mapping[ufField];
      const value = prepared[sourceKey] || "";
      if (/^UF_CRM_/i.test(ufField) && value) {
        fields[ufField] = value;
      }
    });

    return fields;
  };

  const getPortalHost = () => {
    if (window.BX24 && typeof window.BX24.getDomain === "function") {
      return window.BX24.getDomain();
    }
    return window.location.host;
  };

  const storageKey = () => `b24-enricher-mapping-${getPortalHost()}`;

  const loadMappingLocal = () => {
    try {
      const raw = localStorage.getItem(storageKey());
      if (!raw) return {};
      const parsed = JSON.parse(raw);
      return parsed && typeof parsed === "object" ? parsed : {};
    } catch (e) {
      return {};
    }
  };

  const saveMappingLocal = () => {
    localStorage.setItem(storageKey(), JSON.stringify(runtimeMapping));
  };

  const loadMappingServer = async () => {
    const portal = encodeURIComponent(getPortalHost());
    const response = await fetch(`./mapping.php?portal=${portal}`, {
      method: "GET",
      headers: { "Accept": "application/json" },
    });
    const data = await response.json();
    if (!response.ok || !data.ok) {
      throw new Error(data.error || "Failed to load mapping");
    }
    return data.mapping || {};
  };

  const saveMappingServer = async () => {
    const response = await fetch("./mapping.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        portal: getPortalHost(),
        mapping: runtimeMapping,
      }),
    });
    const data = await response.json();
    if (!response.ok || !data.ok) {
      throw new Error(data.error || "Failed to save mapping");
    }
    return data.mapping || {};
  };

  const loadSavedMapping = async () => {
    const local = loadMappingLocal();
    try {
      const server = await loadMappingServer();
      return { ...local, ...server };
    } catch (error) {
      return local;
    }
  };

  const saveMapping = async () => {
    try {
      const serverMapping = await saveMappingServer();
      runtimeMapping = { ...runtimeMapping, ...serverMapping };
      saveMappingLocal();
      renderResult(
        { ok: true, message: "Маппинг сохранён (сервер + локально)" },
        false
      );
    } catch (error) {
      saveMappingLocal();
      renderResult(
        {
          ok: true,
          warning: "Серверное сохранение недоступно, сохранено локально",
          details: String(error),
        },
        false
      );
    }
  };

  const renderMappingTable = (ufFields) => {
    if (!Array.isArray(ufFields) || ufFields.length === 0) {
      mappingTable.innerHTML = '<p class="muted">UF-поля компании не найдены.</p>';
      return;
    }

    const rows = ufFields
      .map((field) => {
        const options = MAPPING_SOURCE_OPTIONS.map((opt) => {
          const selected = (runtimeMapping[field.code] || "") === opt.key ? "selected" : "";
          return `<option value="${opt.key}" ${selected}>${opt.label}</option>`;
        }).join("");

        return `<tr>
          <td><code>${field.code}</code></td>
          <td>${field.title}</td>
          <td>
            <select data-uf="${field.code}" class="map-select">
              ${options}
            </select>
          </td>
        </tr>`;
      })
      .join("");

    mappingTable.innerHTML = `<table>
      <thead>
        <tr>
          <th>Поле</th>
          <th>Название</th>
          <th>Источник</th>
        </tr>
      </thead>
      <tbody>${rows}</tbody>
    </table>`;

    mappingTable.querySelectorAll(".map-select").forEach((selectEl) => {
      selectEl.addEventListener("change", (event) => {
        const ufCode = event.target.getAttribute("data-uf");
        const sourceKey = event.target.value;
        if (!ufCode) return;

        if (!sourceKey) {
          delete runtimeMapping[ufCode];
        } else {
          runtimeMapping[ufCode] = sourceKey;
        }
      });
    });
  };

  const loadUfFields = async () => {
    try {
      const fieldsMeta = await bx24Call("crm.company.fields", {});
      const ufFields = Object.keys(fieldsMeta || {})
        .filter((key) => /^UF_CRM_/i.test(key))
        .map((key) => ({
          code: key,
          title: (fieldsMeta[key] && fieldsMeta[key].title) || key,
        }))
        .sort((a, b) => a.code.localeCompare(b.code));

      runtimeMapping = { ...(await loadSavedMapping()), ...runtimeMapping };
      mappingWrap.style.display = "block";
      renderMappingTable(ufFields);
    } catch (error) {
      renderResult(
        {
          ok: false,
          error: "Не удалось загрузить поля компании",
          details: String(error),
        },
        true
      );
    }
  };

  const detectDomainFromContext = async () => {
    if (!window.BX24 || typeof window.BX24.init !== "function") {
      contextInfo.textContent =
        "Локальный режим: BX24-контекст недоступен, введите домен вручную.";
      return;
    }

    window.BX24.init(async function () {
      try {
        const placementInfo = window.BX24.placement && window.BX24.placement.info
          ? window.BX24.placement.info()
          : {};

        currentCompanyId = extractCompanyId(placementInfo);
        if (!currentCompanyId) {
          contextInfo.textContent =
            "Не удалось определить ID компании из контекста. Введите домен вручную.";
          return;
        }

        contextInfo.textContent = `Компания ID: ${currentCompanyId}`;

        const company = await bx24Call("crm.company.get", { id: currentCompanyId });
        const webList = Array.isArray(company.WEB) ? company.WEB : [];
        const firstWeb = webList.length > 0 ? webList[0].VALUE : "";
        const rawWeb = String(company.WEBSITE || firstWeb || "").trim();
        const fallbackDomain =
          canonicalDomain(rawWeb) || extractDomain(company.WEBSITE || firstWeb);

        if (fallbackDomain) {
          domainInput.value = fallbackDomain;
        }

        loadUfBtn.style.display = "inline-block";
      } catch (error) {
        contextInfo.textContent =
          "Контекст прочитан, но не удалось загрузить данные компании.";
        renderResult(
          {
            error: "Ошибка загрузки карточки компании",
            details: String(error),
          },
          true
        );
      }
    });
  };

  const runEnrich = async () => {
    normalizeDomainInput();
    const domain = (domainInput.value || "").trim();
    if (!domain) {
      renderResult({ error: "Введите домен компании" }, true);
      return;
    }

    enrichBtn.disabled = true;
    enrichBtn.textContent = "Обогащаем...";

    try {
      let aiContext = null;
      if (window.BX24 && typeof window.BX24.getAuth === "function") {
        const auth = window.BX24.getAuth();
        if (auth && auth.access_token) {
          aiContext = {
            portalDomain: getPortalHost(),
            authToken: auth.access_token,
          };
        }
      }

      const enrichUrl =
        typeof window.__resolveEnrichPhpUrl === "function"
          ? window.__resolveEnrichPhpUrl()
          : new URL("enrich.php", window.location.href).href;
      const response = await fetch(enrichUrl, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ domain, aiContext }),
      });

      const data = await response.json();
      if (!response.ok || !data.ok) {
        renderResult(data, true);
        setApplyButtonsState(false);
        latestSuggestedFields = null;
      } else {
        latestSuggestedFields = data.suggestedFields || null;
        renderResult(data, false);
        setApplyButtonsState(!!currentCompanyId);
      }
    } catch (error) {
      const enrichUrl =
        typeof window.__resolveEnrichPhpUrl === "function"
          ? window.__resolveEnrichPhpUrl()
          : "";
      renderResult(
        {
          error: "Не удалось выполнить запрос",
          details: String(error),
          enrichUrl: enrichUrl || undefined,
          hint:
            "Проверьте доступность сервера приложения (XAMPP/ngrok), URL в настройках placement и вкладку Сеть в инструментах разработчика.",
        },
        true
      );
      setApplyButtonsState(false);
      latestSuggestedFields = null;
    } finally {
      enrichBtn.disabled = false;
      enrichBtn.textContent = "Обогатить";
    }
  };
  window.__runEnrichFallback = runEnrich;

  // Fallback: in some embedded contexts addEventListener may not attach as expected.
  enrichBtn.addEventListener("click", runEnrich);
  enrichBtn.onclick = runEnrich;

  const runApplyToCrm = async () => {
    if (!currentCompanyId) {
      renderResult(
        { error: "Не найден ID компании в контексте Bitrix24" },
        true
      );
      return;
    }

    if (!latestSuggestedFields) {
      renderResult({ error: "Сначала выполните обогащение" }, true);
      return;
    }

    const fields = toCrmFields(latestSuggestedFields, runtimeMapping);
    if (Object.keys(fields).length === 0) {
      renderResult(
        { error: "Нет валидных полей для обновления CRM-компании" },
        true
      );
      return;
    }

    const confirmed = window.confirm(
      "Применить предложенные данные в карточку компании?"
    );
    if (!confirmed) {
      return;
    }

    setApplyButtonsState(true, true, "Сохраняем...");

    try {
      const updateResult = await bx24Call("crm.company.update", {
        id: currentCompanyId,
        fields,
      });

      renderResult(
        {
          ok: true,
          message: "Данные успешно сохранены в CRM",
          companyId: currentCompanyId,
          updatedFields: fields,
          result: updateResult,
        },
        false
      );
    } catch (error) {
      renderResult(
        {
          ok: false,
          error: "Не удалось обновить компанию в CRM",
          details: String(error),
          attemptedFields: fields,
        },
        true
      );
    } finally {
      setApplyButtonsState(true, false, "Применить в CRM");
    }
  };

  applyBtn.addEventListener("click", runApplyToCrm);
  if (applyBtnBottom) {
    applyBtnBottom.addEventListener("click", runApplyToCrm);
  }

  loadUfBtn.addEventListener("click", async () => {
    loadUfBtn.disabled = true;
    loadUfBtn.textContent = "Загрузка...";
    try {
      await loadUfFields();
    } finally {
      loadUfBtn.disabled = false;
      loadUfBtn.textContent = "UF-поля";
    }
  });

  saveMappingBtn.addEventListener("click", async () => {
    saveMappingBtn.disabled = true;
    saveMappingBtn.textContent = "Сохраняем...";
    try {
      await saveMapping();
    } finally {
      saveMappingBtn.disabled = false;
      saveMappingBtn.textContent = "Сохранить маппинг";
    }
  });

  detectDomainFromContext();
  window.__enricherLoaded = true;
  });
})();
