"use strict";

const courseForm = document.querySelector("#formulario-curso");
const courseTitle = document.querySelector("#titulo-curso");
const courseDescription = document.querySelector("#descripcion-curso");
const courseCategory = document.querySelector("#categoria-curso");
const courseCost = document.querySelector("#costo-curso");
const courseFile = document.querySelector("#imagen-curso");
const coursePreview = document.querySelector("#vista-imagen-curso");
const courseList = document.querySelector("#lista-mis-cursos");
const courseStatus = document.querySelector("#estado-cursos");
const courseSave = document.querySelector("#guardar-curso");
const courseCancel = document.querySelector("#cancelar-curso");
let editingCourse = null;
let coursesBusy = false;
let courseCategoriesReady = false;
let previewUrl = null;

function courseMessage(text, error = false) {
  courseStatus.textContent = text;
  courseStatus.className = error ? "aviso error" : "aviso exito";
}

async function courseRequest(path, options = {}) {
  const response = await fetch(path, {
    credentials: "same-origin",
    ...options,
    headers: {
      Accept: "application/json",
      "X-CSRF-Token": window.KONNEKT_CURSOS.csrfToken,
    },
  });
  const body = await response.json();

  if (!response.ok) {
    throw new Error(body.message || "No fue posible completar la operación.");
  }

  return body;
}

function courseImagePath(path) {
  if (/^uploads\/cursos\/[a-f0-9]{32}\.(jpg|png)$/.test(path || "")) {
    return `${window.KONNEKT_CURSOS.publicUrl}/${path}`;
  }

  // Compatibilidad con imágenes de los cursos registrados en el segundo avance.
  if (/^https?:\/\//i.test(path || "")) {
    return path;
  }

  return "";
}

function showCoursePreview(path) {
  if (previewUrl) {
    URL.revokeObjectURL(previewUrl);
    previewUrl = null;
  }
  coursePreview.hidden = !path;
  if (path) coursePreview.src = path;
  else coursePreview.removeAttribute("src");
}

function clearCourseForm() {
  editingCourse = null;
  courseForm.reset();
  courseFile.required = true;
  showCoursePreview("");
  courseTitle.setCustomValidity("");
  courseDescription.setCustomValidity("");
  document.querySelector("#titulo-formulario-curso").textContent = "Nuevo curso";
  document.querySelector("#ayuda-imagen-curso").textContent =
    "JPG o PNG, hasta 2 MB. Obligatoria al crear un curso.";
  courseSave.textContent = "Crear curso";
  courseCancel.hidden = true;
}

function setCoursesBusy(busy) {
  coursesBusy = busy;
  for (const field of courseForm.elements) field.disabled = busy;
  courseSave.disabled = busy || !courseCategoriesReady;
  for (const button of courseList.querySelectorAll("button")) button.disabled = busy;
}

function editCourse(course) {
  if (coursesBusy) return;
  clearCourseForm();
  editingCourse = course.id_curso;
  courseTitle.value = course.titulo;
  courseDescription.value = course.descripcion;
  courseCategory.value = String(course.id_categoria);
  courseCost.value = course.costo;
  courseFile.required = !course.imagen;
  showCoursePreview(courseImagePath(course.imagen));
  document.querySelector("#titulo-formulario-curso").textContent = "Editar curso";
  document.querySelector("#ayuda-imagen-curso").textContent = course.imagen
    ? "JPG o PNG, hasta 2 MB. Si no eliges otra imagen, se conserva la actual."
    : "Este curso todavía no tiene imagen. Selecciona una JPG o PNG de hasta 2 MB.";
  courseSave.textContent = "Guardar cambios";
  courseCancel.hidden = false;
  courseTitle.focus();
}

function renderMyCourses(courses) {
  courseList.replaceChildren();

  if (courses.length === 0) {
    const message = document.createElement("p");
    message.textContent = "Todavía no has registrado cursos.";
    courseList.append(message);
    return;
  }

  const currency = new Intl.NumberFormat("es-MX", {
    style: "currency", currency: "MXN", minimumFractionDigits: 2,
  });

  for (const course of courses) {
    const card = document.createElement("article");
    card.className = "tarjeta";
    const imagePath = courseImagePath(course.imagen);

    if (imagePath) {
      const image = document.createElement("img");
      image.className = "imagen-tarjeta-curso";
      image.src = imagePath;
      image.alt = course.titulo;
      image.loading = "lazy";
      card.append(image);
    }

    const title = document.createElement("h3");
    title.textContent = course.titulo;
    const category = document.createElement("p");
    category.className = "muted";
    category.textContent = course.categoria;
    const description = document.createElement("p");
    description.className = "descripcion-tarjeta-curso";
    description.textContent = course.descripcion;
    const price = document.createElement("p");
    price.textContent = `${currency.format(Number(course.costo))} MXN`;
    const status = document.createElement("p");
    status.textContent = Number(course.activo) === 1 ? "Activo" : "Dado de baja";
    const levelCount = document.createElement("p");
    levelCount.textContent = `Niveles: ${Number(course.total_niveles || 0)}`;
    card.append(title, category, description, price, status, levelCount);

    if (Number(course.activo) === 1) {
      const actions = document.createElement("div");
      actions.className = "acciones";
      const levels = document.createElement("a");
      levels.className = "boton-secundario";
      levels.textContent = "Niveles";
      levels.href = `${window.KONNEKT_CURSOS.publicUrl}/index.php?r=niveles&curso=${course.id_curso}`;
      const edit = document.createElement("button");
      edit.type = "button";
      edit.className = "boton-secundario";
      edit.textContent = "Editar";
      edit.addEventListener("click", () => editCourse(course));
      const remove = document.createElement("button");
      remove.type = "button";
      remove.className = "boton-peligro";
      remove.textContent = "Dar de baja";
      remove.addEventListener("click", () => deactivateCourse(course));
      edit.disabled = coursesBusy;
      remove.disabled = coursesBusy;
      actions.append(edit, levels, remove);
      card.append(actions);
    }

    courseList.append(card);
  }
}

async function loadMyCourses() {
  const body = await courseRequest(`${window.KONNEKT_CURSOS.api}?propios=1`);
  renderMyCourses(body.cursos);
}

async function reloadCoursesAfterSave() {
  try {
    await loadMyCourses();
  } catch (error) {
    courseMessage(`El cambio se guardó, pero no se pudo actualizar la lista: ${error.message}`, true);
  }
}

async function deactivateCourse(course) {
  if (coursesBusy || !confirm(`¿Dar de baja el curso "${course.titulo}"? Dejará de aparecer en el catálogo.`)) {
    return;
  }

  setCoursesBusy(true);
  try {
    const body = await courseRequest(`${window.KONNEKT_CURSOS.api}/${course.id_curso}`, {
      method: "DELETE",
    });
    if (editingCourse === course.id_curso) clearCourseForm();
    courseMessage(body.message);
    await reloadCoursesAfterSave();
  } catch (error) {
    courseMessage(error.message, true);
  } finally {
    setCoursesBusy(false);
  }
}

courseFile.addEventListener("change", () => {
  const file = courseFile.files[0];
  if (!file) {
    showCoursePreview("");
    return;
  }
  if (!["image/jpeg", "image/png"].includes(file.type) || file.size > 2 * 1024 * 1024) {
    courseMessage("Selecciona una imagen JPG o PNG de hasta 2 MB.", true);
    courseFile.value = "";
    showCoursePreview("");
    return;
  }
  showCoursePreview("");
  previewUrl = URL.createObjectURL(file);
  coursePreview.src = previewUrl;
  coursePreview.hidden = false;
});

for (const field of [courseTitle, courseDescription]) {
  field.addEventListener("input", () => field.setCustomValidity(""));
}
courseCancel.addEventListener("click", clearCourseForm);

courseForm.addEventListener("submit", async (event) => {
  event.preventDefault();
  if (coursesBusy || !courseCategoriesReady) return;

  for (const field of [courseTitle, courseDescription]) {
    if (field.value.trim() === "") {
      field.setCustomValidity("Este campo es obligatorio.");
      field.reportValidity();
      return;
    }
  }
  if (!courseForm.reportValidity()) return;

  // FormData debe construirse antes de deshabilitar los campos.
  const data = new FormData(courseForm);
  data.set("titulo", courseTitle.value.trim());
  data.set("descripcion", courseDescription.value.trim());
  if (editingCourse !== null) data.set("_method", "PUT");
  const path = window.KONNEKT_CURSOS.api + (editingCourse !== null ? `/${editingCourse}` : "");

  setCoursesBusy(true);
  try {
    const body = await courseRequest(path, { method: "POST", body: data });
    clearCourseForm();
    courseMessage(body.message);
    await reloadCoursesAfterSave();
  } catch (error) {
    courseMessage(error.message, true);
  } finally {
    setCoursesBusy(false);
  }
});

async function initializeCourses() {
  courseMessage("Consultando cursos y categorías...");
  setCoursesBusy(true);
  try {
    const body = await courseRequest(window.KONNEKT_CURSOS.categoriasApi);
    for (const category of body.categorias) {
      const option = document.createElement("option");
      option.value = category.id_categoria;
      option.textContent = category.nombre;
      courseCategory.append(option);
    }
    courseCategoriesReady = body.categorias.length > 0;
    await loadMyCourses();
    courseMessage(courseCategoriesReady
      ? "Cursos cargados."
      : "Pide al administrador que registre una categoría antes de crear cursos.");
  } catch (error) {
    courseMessage(error.message, true);
  } finally {
    setCoursesBusy(false);
  }
}

initializeCourses();
