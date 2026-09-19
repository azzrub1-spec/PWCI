const form = document.querySelector("#registro");
const errorsBox = document.querySelector("#errores");

form.addEventListener("submit", (event) => {
  event.preventDefault();
  const data = new FormData(form);
  const email = String(data.get("correo") || "");
  const password = String(data.get("password") || "");
  const confirmation = String(data.get("confirmar") || "");
  const birthDate = String(data.get("nacimiento") || "");
  const errors = [];

  if (!form.checkValidity()) errors.push("Completa todos los campos obligatorios.");
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.push("Escribe un correo válido.");
  if (password.length < 8) errors.push("La contraseña debe tener al menos 8 caracteres.");
  if (!/[A-Z]/.test(password)) errors.push("Agrega una letra mayúscula.");
  if (!/\d/.test(password)) errors.push("Agrega un número.");
  if (!/[^A-Za-z0-9]/.test(password)) errors.push("Agrega un carácter especial.");
  if (password !== confirmation) errors.push("Las contraseñas no coinciden.");
  if (birthDate && new Date(birthDate) >= new Date()) errors.push("La fecha debe ser anterior a hoy.");

  errorsBox.innerHTML = errors.length
    ? `<div class="errores"><ul>${errors.map((error) => `<li>${error}</li>`).join("")}</ul></div>`
    : "";

  if (!errors.length) window.location.href = "../estudiante/kardex.html";
});
