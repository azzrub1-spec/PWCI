const message = document.querySelector("#mensaje");

function notify(text) {
  message.textContent = text;
  message.classList.add("visible");
  window.setTimeout(() => message.classList.remove("visible"), 3000);
}

document.addEventListener("click", (event) => {
  const button = event.target.closest("[data-mensaje]");
  if (button) notify(button.dataset.mensaje);
});

document.addEventListener("submit", (event) => {
  event.preventDefault();
  const form = event.target;
  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }
  const destination = form.dataset.destino;
  if (destination) {
    window.location.href = destination;
    return;
  }
  form.reset();
  notify("Información guardada correctamente.");
});
