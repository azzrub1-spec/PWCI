"use strict";

const container = document.querySelector("#cursos");
const statusMessage = document.querySelector("#estado-catalogo");
const searchInput = document.querySelector("#buscar");
const searchButton = document.querySelector("#filtrar");
const categorySelect = document.querySelector("#categoria");

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
  const category = encodeURIComponent(categorySelect.value);
  statusMessage.textContent = "Consultando cursos...";
  statusMessage.classList.remove("error-texto");

  fetch(`${window.KONNEKT_API}?titulo=${title}&categoria=${category}`, {
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
categorySelect.addEventListener("change", loadCourses);

fetch(window.KONNEKT_CATEGORIAS_API, {
  credentials: "same-origin",
  headers: { Accept: "application/json" },
})
  .then(async (response) => {
    const body = await response.json();
    if (!response.ok) throw new Error(body.message || "No se pudieron cargar las categorías.");
    for (const category of body.categorias) {
      const option = document.createElement("option");
      option.value = category.id_categoria;
      option.textContent = category.nombre;
      categorySelect.append(option);
    }
  })
  .catch((error) => {
    categorySelect.disabled = true;
    const message = document.createElement("p");
    message.className = "aviso error";
    message.setAttribute("role", "alert");
    message.textContent = error.message;
    categorySelect.closest(".filtros").after(message);
  });

loadCourses();

