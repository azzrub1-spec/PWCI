"use strict";

const container = document.querySelector("#cursos");
const statusMessage = document.querySelector("#estado-catalogo");
const searchInput = document.querySelector("#buscar");
const searchButton = document.querySelector("#filtrar");

function escapeHtml(value) {
  const element = document.createElement("div");
  element.textContent = value;
  return element.innerHTML;
}

function showCourses(courses) {
  if (courses.length === 0) {
    container.innerHTML = "";
    statusMessage.textContent = "No se encontraron cursos activos.";
    return;
  }

  container.innerHTML = courses.map((course) => `
    <article class="tarjeta">
      <span class="etiqueta">${escapeHtml(course.categoria)}</span>
      <h2>${escapeHtml(course.titulo)}</h2>
      <p>${escapeHtml(course.instructor)}</p>
      <p>$${Number(course.costo).toFixed(2)}</p>
    </article>
  `).join("");
  statusMessage.textContent = `${courses.length} curso(s) encontrado(s).`;
}

function loadCourses() {
  const title = encodeURIComponent(searchInput.value.trim());
  statusMessage.textContent = "Consultando cursos...";

  fetch(`${window.KONNEKT_API}?titulo=${title}`, {
    credentials: "same-origin",
    headers: { Accept: "application/json" },
  })
    .then((response) => response.json().then((body) => ({ response, body })))
    .then(({ response, body }) => {
      if (!response.ok) {
        throw new Error(body.message || "No fue posible consultar los cursos.");
      }
      showCourses(body.cursos);
    })
    .catch((error) => {
      container.innerHTML = "";
      statusMessage.textContent = error.message;
      statusMessage.classList.add("error-texto");
    });
}

searchButton.addEventListener("click", loadCourses);
loadCourses();

