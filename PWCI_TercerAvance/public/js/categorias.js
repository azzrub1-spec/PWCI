"use strict";

const categoryForm = document.querySelector("#formulario-categoria");
const categoryName = document.querySelector("#nombre-categoria");
const categoryDescription = document.querySelector("#descripcion-categoria");
const categoryList = document.querySelector("#lista-categorias");
const categoryStatus = document.querySelector("#estado-categorias");
const saveCategory = document.querySelector("#guardar-categoria");
const cancelCategory = document.querySelector("#cancelar-edicion");
let editingCategory = null;
let categoryBusy = false;

function categoryMessage(message, error = false) {
  categoryStatus.textContent = message;
  categoryStatus.className = error ? "aviso error" : "aviso exito";
}

async function categoryRequest(path = "", options = {}) {
  const response = await fetch(window.KONNEKT_CATEGORIAS.api + path, {
    credentials: "same-origin",
    ...options,
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      "X-CSRF-Token": window.KONNEKT_CATEGORIAS.csrfToken,
    },
  });
  const body = await response.json();

  if (!response.ok) {
    throw new Error(body.message || "No fue posible completar la operación.");
  }

  return body;
}

function clearCategoryForm() {
  editingCategory = null;
  categoryForm.reset();
  categoryName.setCustomValidity("");
  document.querySelector("#titulo-formulario").textContent = "Nueva categoría";
  saveCategory.textContent = "Crear categoría";
  cancelCategory.hidden = true;
}

function categoryDate(value) {
  const match = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}:\d{2}:\d{2})$/.exec(value);
  const months = ["ene", "feb", "mar", "abr", "may", "jun",
    "jul", "ago", "sep", "oct", "nov", "dic"];

  return match
    ? `${match[3]}/${months[Number(match[2]) - 1]}/${match[1]} ${match[4]}`
    : value;
}

function categoryButton(text, className, action) {
  const button = document.createElement("button");
  button.type = "button";
  button.className = className;
  button.textContent = text;
  button.disabled = categoryBusy;
  button.addEventListener("click", action);
  return button;
}

function renderCategories(categories) {
  categoryList.replaceChildren();

  if (categories.length === 0) {
    const row = categoryList.insertRow();
    const cell = row.insertCell();
    cell.colSpan = 5;
    cell.textContent = "No hay categorías registradas.";
    return;
  }

  for (const category of categories) {
    const row = categoryList.insertRow();
    const values = [category.nombre, category.descripcion || "Sin descripción",
      category.creador || "Sin creador registrado", categoryDate(category.fecha_creacion)];

    for (const value of values) {
      row.insertCell().textContent = value;
    }

    const actions = document.createElement("div");
    actions.className = "acciones-categoria";
    actions.append(
      categoryButton("Editar", "boton-secundario", () => {
        if (categoryBusy) return;
        editingCategory = category.id_categoria;
        categoryName.value = category.nombre;
        categoryName.setCustomValidity("");
        categoryDescription.value = category.descripcion;
        document.querySelector("#titulo-formulario").textContent = "Editar categoría";
        saveCategory.textContent = "Guardar cambios";
        cancelCategory.hidden = false;
        categoryName.focus();
      }),
      categoryButton("Eliminar", "boton-peligro", () => deleteCategory(category)),
    );
    row.insertCell().append(actions);
  }
}

async function loadCategories() {
  const body = await categoryRequest();
  renderCategories(body.categorias);
}

function setCategoryBusy(busy) {
  categoryBusy = busy;
  for (const element of categoryForm.elements) {
    element.disabled = busy;
  }
  for (const button of categoryList.querySelectorAll("button")) {
    button.disabled = busy;
  }
}

async function deleteCategory(category) {
  if (categoryBusy || !confirm(`¿Eliminar la categoría "${category.nombre}"?`)) {
    return;
  }

  setCategoryBusy(true);
  try {
    const body = await categoryRequest(`/${category.id_categoria}`, { method: "DELETE" });
    if (editingCategory === category.id_categoria) clearCategoryForm();
    categoryMessage(body.message);
    await reloadAfterChange();
  } catch (error) {
    categoryMessage(error.message, true);
  } finally {
    setCategoryBusy(false);
  }
}

async function reloadAfterChange() {
  try {
    await loadCategories();
  } catch (error) {
    categoryMessage(`El cambio se guardó, pero no se pudo actualizar la lista: ${error.message}`, true);
  }
}

categoryName.addEventListener("input", () => categoryName.setCustomValidity(""));
cancelCategory.addEventListener("click", clearCategoryForm);

categoryForm.addEventListener("submit", async (event) => {
  event.preventDefault();
  if (categoryBusy) return;

  if (categoryName.value.trim() === "") {
    categoryName.setCustomValidity("Escribe el nombre de la categoría.");
    categoryName.reportValidity();
    return;
  }

  setCategoryBusy(true);
  try {
    const body = await categoryRequest(editingCategory ? `/${editingCategory}` : "", {
      method: editingCategory ? "PUT" : "POST",
      body: JSON.stringify({
        nombre: categoryName.value.trim(),
        descripcion: categoryDescription.value.trim(),
      }),
    });
    clearCategoryForm();
    categoryMessage(body.message);
    await reloadAfterChange();
  } catch (error) {
    categoryMessage(error.message, true);
  } finally {
    setCategoryBusy(false);
  }
});

categoryMessage("Consultando categorías...");
loadCategories()
  .then(() => categoryMessage("Categorías cargadas."))
  .catch((error) => categoryMessage(error.message, true));
