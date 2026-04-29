(function () {
  const enrichBtn = document.getElementById("enrichBtn");
  const domainInput = document.getElementById("domain");
  const resultBox = document.getElementById("result");

  const renderResult = (payload, isError = false) => {
    resultBox.style.display = "block";
    resultBox.style.background = isError ? "#fef2f2" : "#f9fafb";
    resultBox.innerHTML = `<pre>${JSON.stringify(payload, null, 2)}</pre>`;
  };

  const detectDomainFromContext = () => {
    // Здесь будет чтение текущей компании из Bitrix24 context/REST.
    // Пока оставляем ручной ввод + заглушку для будущего шага.
    if (window.BX24 && typeof window.BX24.init === "function") {
      window.BX24.init(function () {
        // Можно получить placementInfo и entityId, затем подтянуть компанию через REST.
      });
    }
  };

  enrichBtn.addEventListener("click", async () => {
    const domain = (domainInput.value || "").trim();
    if (!domain) {
      renderResult({ error: "Введите домен компании" }, true);
      return;
    }

    enrichBtn.disabled = true;
    enrichBtn.textContent = "Обогащаем...";

    try {
      const response = await fetch("./enrich.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ domain }),
      });

      const data = await response.json();
      if (!response.ok) {
        renderResult(data, true);
      } else {
        renderResult(data, false);
      }
    } catch (error) {
      renderResult(
        { error: "Не удалось выполнить запрос", details: String(error) },
        true
      );
    } finally {
      enrichBtn.disabled = false;
      enrichBtn.textContent = "Обогатить";
    }
  });

  detectDomainFromContext();
})();
