"use strict";

document.addEventListener("submit", async (event) => {
  const form = event.target;
  if (!form.matches("[data-estudiante-accion]")) return;
  event.preventDefault();

  const button = form.querySelector("button[type=submit]");
  const status = form.querySelector("[data-estado]");
  if (button.disabled) return;
  button.disabled = true;
  status.className = "";
  status.textContent = "Guardando…";
  let saved = false;

  try {
    const response = await fetch(form.action, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
        "X-CSRF-Token": form.dataset.csrf,
      },
    });
    const body = await response.json();
    if (!response.ok || !body.success) {
      throw new Error(body.message || "No se pudo guardar el cambio.");
    }
    saved = true;
    status.textContent = body.message;

    // Actualiza únicamente el contenido visible; conserva la página abierta.
    const pageResponse = await fetch(window.location.href, {
      credentials: "same-origin",
      headers: { Accept: "text/html" },
      cache: "no-store",
    });
    if (!pageResponse.ok || pageResponse.redirected) {
      throw new Error("El cambio se guardó. Actualiza la página para ver su estado.");
    }
    const html = await pageResponse.text();
    const updated = new DOMParser().parseFromString(html, "text/html");
    const section = updated.querySelector("section.pagina");
    const current = document.querySelector("section.pagina");
    if (!section || !current) {
      throw new Error("El cambio se guardó. Actualiza la página para ver su estado.");
    }
    const notice = document.createElement("p");
    notice.className = "aviso exito";
    notice.setAttribute("role", "status");
    notice.textContent = body.message;
    section.prepend(notice);
    current.replaceWith(section);
  } catch (error) {
    status.className = "error-texto";
    status.textContent = saved
      ? "El cambio se guardó, pero no se pudo actualizar la vista. Actualiza la página."
      : error.message;
    button.disabled = saved;
  }
});
