window.addEventListener("load", () => {
  document.querySelectorAll("select[data-funktion]").forEach((el) => {
    try {
      const spec = JSON.parse(el.dataset.funktion);
      const { "selector-pattern": selectorPattern, values } = spec;

      if (!selectorPattern || !values) {
        return;
      }

      const update = () => {
        const id = el.value;

        if (values[id]) {
          for (const [key, value] of Object.entries(values[id])) {
            const selector = selectorPattern.replace("%key%", key);
            const target = document.querySelector(selector);
            if (target) {
              target.value = value;
            }
          }
        }
      };

      el.addEventListener("change", update);
      update();
    } catch (exception) {
      console.debug(exception);
    }
  });

  document.querySelectorAll("button[data-result-user-id]").forEach((el) => {
    el.addEventListener("click", () => {
      const userId = el.dataset.resultUserId;
      const container = el.closest("fieldset");
      try {
        container.querySelector('[data-name="search-user-id"]').value = userId;
        container.querySelector('[data-name="search-user-apply"]').click();
      } catch (exception) {
        console.debug(exception);
      }
    });
  });

  document.querySelector("input[data-name='search-user-query']").addEventListener("click", (e) => {
      const searchButton = e.target;
      if (searchButton.classList.contains("submit-loading")) {
        e.preventDefault();
        return false;
      }
      searchButton.classList.add("submit-loading");
      searchButton.value = "Henter..";
    });

  document.querySelectorAll("table.os2forms-organisation-search-result-table > tbody > tr > td > button").forEach((el) => {
    el.addEventListener("click", (e) => {
      const searchButton = e.target;
      if (searchButton.classList.contains("submit-loading")) {
        e.preventDefault();
        return false;
      }
      searchButton.classList.add("submit-loading");
      searchButton.innerHTML = "Henter..";
    });
  });

});
