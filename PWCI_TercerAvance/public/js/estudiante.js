"use strict";

for (const form of document.querySelectorAll("[data-estudiante-accion]")) {
  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    const button = form.querySelector("button[type=submit]");
    const status = form.querySelector("[data-estado]");
    if (button.disabled) return;
    button.disabled = true;
    status.className = "";
    status.textContent = "Guardando…";

    try {
      const response = await fetch(form.action, {
        method: "POST",
        credentials: "same-origin",
        headers: { Accept: "application/json", "X-CSRF-Token": form.dataset.csrf },
      });
      const body = await response.json();
      if (!response.ok || !body.success) {
        throw new Error(body.message || "No se pudo guardar el cambio.");
      }
      window.location.reload();
    } catch (error) {
      status.className = "error-texto";
      status.textContent = error.message;
      button.disabled = false;
    }
  });
}
