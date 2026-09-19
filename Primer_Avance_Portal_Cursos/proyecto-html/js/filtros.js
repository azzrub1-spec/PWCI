const search = document.querySelector("#buscar");
const category = document.querySelector("#categoria");
const button = document.querySelector("#filtrar");
const cards = [...document.querySelectorAll("#cursos .tarjeta")];

button.addEventListener("click", () => {
  const term = search.value.trim().toLowerCase();
  const selected = category.value;
  cards.forEach((card) => {
    const titleMatches = card.dataset.titulo.includes(term);
    const categoryMatches = selected === "todas" || card.dataset.categoria === selected;
    card.hidden = !(titleMatches && categoryMatches);
  });
});
