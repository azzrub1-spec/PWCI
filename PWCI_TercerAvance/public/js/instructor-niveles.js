"use strict";

const levelForm = document.querySelector("#formulario-nivel");
const levelTitle = document.querySelector("#titulo-nivel");
const levelOrder = document.querySelector("#orden-nivel");
const levelCost = document.querySelector("#costo-nivel");
const levelText = document.querySelector("#texto-nivel");
const levelLink = document.querySelector("#enlace-nivel");
const levelVideo = document.querySelector("#video-nivel");
const levelPdf = document.querySelector("#pdf-nivel");
const levelImage = document.querySelector("#imagen-nivel");
const levelList = document.querySelector("#lista-niveles");
const levelStatus = document.querySelector("#estado-niveles");
const levelSave = document.querySelector("#guardar-nivel");
const levelCancel = document.querySelector("#cancelar-nivel");
let editingLevel = null;
let levelsBusy = false;
let nextLevelOrder = 1;

function levelMessage(text, error = false) {
  levelStatus.textContent = text;
  levelStatus.className = error ? "aviso error" : "aviso exito";
}

async function levelRequest(path, options = {}) {
  const response = await fetch(path, {
    credentials: "same-origin",
    ...options,
    headers: {
      Accept: "application/json",
      "X-CSRF-Token": window.KONNEKT_NIVELES.csrfToken,
    },
  });
  const body = await response.json();
  if (!response.ok) {
    throw new Error(body.message || "No fue posible completar la operación.");
  }
  return body;
}

function clearLevelForm() {
  editingLevel = null;
  levelForm.reset();
  levelTitle.setCustomValidity("");
  levelOrder.value = nextLevelOrder;
  levelVideo.required = true;
  levelCancel.hidden = true;
  document.querySelector("#titulo-formulario-nivel").textContent = "Nuevo nivel";
  document.querySelector("#ayuda-video-nivel").textContent = "MP4 o WebM, hasta 50 MB.";
  document.querySelector("#opcion-quitar-pdf").hidden = true;
  document.querySelector("#opcion-quitar-imagen").hidden = true;
  levelSave.textContent = "Crear nivel";
}

function setLevelsBusy(busy) {
  levelsBusy = busy;
  const disabled = busy || !window.KONNEKT_NIVELES.activo;
  for (const field of levelForm.elements) field.disabled = disabled;
  for (const button of levelList.querySelectorAll("button")) button.disabled = disabled;
}

function editLevel(level) {
  if (levelsBusy || !window.KONNEKT_NIVELES.activo) return;
  clearLevelForm();
  editingLevel = level.id_nivel;
  levelTitle.value = level.titulo;
  levelOrder.value = level.orden;
  levelCost.value = level.costo_nivel;
  levelText.value = level.contenido_texto || "";
  levelLink.value = level.enlace || "";
  levelVideo.required = !/^[a-f0-9]{32}\.(mp4|webm)$/.test(level.video_url || "");
  levelCancel.hidden = false;
  document.querySelector("#opcion-quitar-pdf").hidden = !level.archivo_pdf;
  document.querySelector("#opcion-quitar-imagen").hidden = !level.imagen;
  document.querySelector("#titulo-formulario-nivel").textContent = "Editar nivel";
  document.querySelector("#ayuda-video-nivel").textContent = levelVideo.required
    ? "Selecciona un video MP4 o WebM para este nivel. Hasta 50 MB."
    : "MP4 o WebM, hasta 50 MB. Si no eliges otro, se conserva el video actual.";
  levelSave.textContent = "Guardar cambios";
  levelTitle.focus();
}

function levelFileUrl(id, type) {
  return `${window.KONNEKT_NIVELES.archivoUrl}&id=${id}&tipo=${type}`;
}

function renderLevels(levels) {
  levelList.replaceChildren();
  document.querySelector("#cantidad-niveles").textContent = `Cantidad de niveles: ${levels.length}`;
  nextLevelOrder = levels.reduce((max, level) => Math.max(max, Number(level.orden)), 0) + 1;
  if (editingLevel === null) levelOrder.value = nextLevelOrder;

  if (levels.length === 0) {
    const message = document.createElement("p");
    message.textContent = "Este curso todavía no tiene niveles.";
    levelList.append(message);
    return;
  }

  const currency = new Intl.NumberFormat("es-MX", {
    style: "currency", currency: "MXN", minimumFractionDigits: 2,
  });

  for (const level of levels) {
    const card = document.createElement("article");
    card.className = "panel";
    const title = document.createElement("h3");
    title.textContent = `${level.orden}. ${level.titulo}`;
    const price = document.createElement("p");
    price.textContent = Number(level.costo_nivel) === 0
      ? "Nivel gratuito"
      : `${currency.format(Number(level.costo_nivel))} MXN`;
    card.append(title, price);

    if (/^[a-f0-9]{32}\.(mp4|webm)$/.test(level.video_url || "")) {
      const video = document.createElement("video");
      video.controls = true;
      video.preload = "none";
      video.src = levelFileUrl(level.id_nivel, "video");
      video.className = "video-nivel";
      video.textContent = "Tu navegador no puede reproducir este video.";
      card.append(video);
    } else {
      const message = document.createElement("p");
      message.className = "error-texto";
      message.textContent = "Edita el nivel y selecciona su video obligatorio.";
      card.append(message);
    }

    if (level.contenido_texto) {
      const text = document.createElement("p");
      text.className = "texto-nivel";
      text.textContent = level.contenido_texto;
      card.append(text);
    }
    if (level.imagen) {
      const image = document.createElement("img");
      image.src = levelFileUrl(level.id_nivel, "imagen");
      image.alt = `Imagen del nivel ${level.titulo}`;
      image.className = "imagen-contenido-nivel";
      image.loading = "lazy";
      card.append(image);
    }
    if (level.archivo_pdf) {
      const paragraph = document.createElement("p");
      const pdf = document.createElement("a");
      pdf.href = levelFileUrl(level.id_nivel, "pdf");
      pdf.textContent = "Abrir material PDF";
      pdf.target = "_blank";
      pdf.rel = "noopener";
      paragraph.append(pdf);
      card.append(paragraph);
    }
    if (/^https?:\/\//i.test(level.enlace || "")) {
      const paragraph = document.createElement("p");
      const link = document.createElement("a");
      link.href = level.enlace;
      link.textContent = "Abrir enlace del nivel";
      link.target = "_blank";
      link.rel = "noopener noreferrer";
      paragraph.append(link);
      card.append(paragraph);
    }

    if (window.KONNEKT_NIVELES.activo) {
      const actions = document.createElement("div");
      actions.className = "acciones";
      const edit = document.createElement("button");
      edit.type = "button";
      edit.textContent = "Editar";
      edit.className = "boton-secundario";
      edit.disabled = levelsBusy;
      edit.addEventListener("click", () => editLevel(level));
      const remove = document.createElement("button");
      remove.type = "button";
      remove.textContent = "Eliminar nivel";
      remove.className = "boton-peligro";
      remove.disabled = levelsBusy;
      remove.addEventListener("click", () => deleteLevel(level));
      actions.append(edit, remove);
      card.append(actions);
    }
    levelList.append(card);
  }
}

async function loadLevels() {
  const body = await levelRequest(`${window.KONNEKT_NIVELES.api}?curso_id=${window.KONNEKT_NIVELES.cursoId}`);
  renderLevels(body.niveles);
}

async function reloadLevelsAfterSave() {
  try {
    await loadLevels();
  } catch (error) {
    levelMessage(`El cambio se guardó, pero no se pudo actualizar la lista: ${error.message}`, true);
  }
}

async function deleteLevel(level) {
  if (levelsBusy || !window.KONNEKT_NIVELES.activo
      || !confirm(`¿Eliminar el nivel "${level.titulo}" y sus materiales?`)) return;

  setLevelsBusy(true);
  try {
    const body = await levelRequest(`${window.KONNEKT_NIVELES.api}/${level.id_nivel}`, { method: "DELETE" });
    if (editingLevel === level.id_nivel) clearLevelForm();
    levelMessage(body.message);
    await reloadLevelsAfterSave();
  } catch (error) {
    levelMessage(error.message, true);
  } finally {
    setLevelsBusy(false);
  }
}

const levelFileRules = [
  { field: levelVideo, types: ["video/mp4", "video/webm"], max: 50, label: "video MP4 o WebM de hasta 50 MB" },
  { field: levelPdf, types: ["application/pdf"], max: 5, label: "archivo PDF de hasta 5 MB" },
  { field: levelImage, types: ["image/jpeg", "image/png"], max: 2, label: "imagen JPG o PNG de hasta 2 MB" },
];

function validateLevelFiles() {
  for (const rule of levelFileRules) {
    const file = rule.field.files[0];
    if (file && (!rule.types.includes(file.type) || file.size < 1 || file.size > rule.max * 1024 * 1024)) {
      levelMessage(`Selecciona un ${rule.label}.`, true);
      rule.field.value = "";
      return false;
    }
  }
  return true;
}

for (const rule of levelFileRules) rule.field.addEventListener("change", validateLevelFiles);
levelTitle.addEventListener("input", () => levelTitle.setCustomValidity(""));
levelCancel.addEventListener("click", clearLevelForm);

levelForm.addEventListener("submit", async (event) => {
  event.preventDefault();
  if (levelsBusy || !window.KONNEKT_NIVELES.activo) return;

  if (levelTitle.value.trim() === "") {
    levelTitle.setCustomValidity("El título del nivel es obligatorio.");
    levelTitle.reportValidity();
    return;
  }
  if (!levelForm.reportValidity() || !validateLevelFiles()) return;

  const data = new FormData(levelForm);
  data.set("titulo", levelTitle.value.trim());
  data.set("curso_id", String(window.KONNEKT_NIVELES.cursoId));
  if (editingLevel !== null) data.set("_method", "PUT");
  const path = window.KONNEKT_NIVELES.api + (editingLevel !== null ? `/${editingLevel}` : "");

  setLevelsBusy(true);
  try {
    const body = await levelRequest(path, { method: "POST", body: data });
    clearLevelForm();
    levelMessage(body.message);
    await reloadLevelsAfterSave();
  } catch (error) {
    levelMessage(error.message, true);
  } finally {
    setLevelsBusy(false);
  }
});

async function initializeLevels() {
  setLevelsBusy(true);
  levelMessage("Consultando niveles...");
  try {
    await loadLevels();
    levelMessage(window.KONNEKT_NIVELES.activo
      ? "Niveles cargados."
      : "El curso está dado de baja. Sus niveles solo se pueden consultar.");
  } catch (error) {
    levelMessage(error.message, true);
  } finally {
    setLevelsBusy(false);
  }
}

initializeLevels();
