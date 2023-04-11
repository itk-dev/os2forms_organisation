document.querySelectorAll("select[data-funktion]").forEach((el) => {
  try {
    const spec = JSON.parse(el.dataset["funktion"]);
    const { ["selector-pattern"]: selectorPattern, values } = spec;

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
    window.addEventListener("load", update);
  } catch (exception) {
    console.debug(exception);
  }
});
