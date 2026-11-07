"use strict";

const container = document.querySelector("#cursos");
const statusMessage = document.querySelector("#estado-catalogo");
const searchInput = document.querySelector("#buscar");
const searchButton = document.querySelector("#filtrar");
const categorySelect = document.querySelector("#categoria");

function showCourses(courses) {
  container.replaceChildren();

  if (courses.length === 0) {
    statusMessage.textContent = "No se encontraron cursos activos.";
    return;
  }

  const currency = new Intl.NumberFormat("es-MX", {
    style: "currency", currency: "MXN", minimumFractionDigits: 2,
  });

  for (const course of courses) {
    const card = document.createElement("article");
    card.className = "tarjeta";
    let imagePath = "";

    if (/^uploads\/cursos\/[a-f0-9]{32}\.(jpg|png)$/.test(course.imagen || "")) {
      imagePath = `${window.KONNEKT_PUBLIC_URL}/${course.imagen}`;
    } else if (/^https?:\/\//i.test(course.imagen || "")) {
      imagePath = course.imagen;
    }

    if (imagePath) {
      const image = document.createElement("img");
      image.className = "imagen-tarjeta-curso";
      image.src = imagePath;
      image.alt = course.titulo;
      image.loading = "lazy";
      card.append(image);
    }

    const category = document.createElement("span");
    category.className = "etiqueta";
    category.textContent = course.categoria;
    const title = document.createElement("h2");
    title.textContent = course.titulo;
    const instructor = document.createElement("p");
    instructor.textContent = course.instructor;
    const description = document.createElement("p");
    description.className = "descripcion-tarjeta-curso";
    description.textContent = course.descripcion;
    const price = document.createElement("p");
    price.textContent = `${currency.format(Number(course.costo))} MXN`;
    card.append(category, title, instructor, description, price);
    container.append(card);
  }
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

