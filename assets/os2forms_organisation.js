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
        container.querySelector('[name="search-user-apply"]').click();
      } catch (exception) {
        console.debug(exception);
      }
    });
  });
});
