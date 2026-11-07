"use strict";

const form = document.querySelector("#registro");
const errorsContainer = document.querySelector("#errores");
const passwordRule = /^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;

form.addEventListener("submit", (event) => {
  const data = new FormData(form);
  const errors = [];
  const birthDate = new Date(`${data.get("fecha_nacimiento")}T00:00:00`);

  if (!form.checkValidity()) {
    errors.push("Completa todos los campos con información válida.");
  }

  if (!passwordRule.test(data.get("password"))) {
    errors.push("La contraseña debe tener 8 caracteres, una mayúscula, un número y un símbolo.");
  }

  if (data.get("password") !== data.get("password_confirmation")) {
    errors.push("Las contraseñas no coinciden.");
  }

  if (Number.isNaN(birthDate.getTime()) || birthDate > new Date()) {
    errors.push("La fecha de nacimiento no es válida.");
  }

  errorsContainer.innerHTML = errors.map((error) => `<p>${error}</p>`).join("");

  if (errors.length > 0) {
    event.preventDefault();
  }
});

