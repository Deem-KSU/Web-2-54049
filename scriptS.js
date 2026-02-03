//====== Add & Edit recipe page=====
// ===== Ingredients =====
const ingredientsList = document.getElementById("ingredients-list");
const addIngredientBtn = document.getElementById("add-ingredient");

addIngredientBtn.addEventListener("click", function () {
  const count = ingredientsList.querySelectorAll(".ingredient-row").length + 1;

  const row = document.createElement("div");
  // to aplay css code
  row.className = "ingredient-row";

  row.innerHTML = `
    <span class="ingredient-num">Ingredient ${count}:</span>
    <div class="ingredient-inputs">
      <input type="text" class="ing-name" placeholder="Name" required>
      <input type="text" class="ing-qty" placeholder="Quantity" required>
    </div>
  `;
   // add in the last 
  ingredientsList.appendChild(row);
});


// ===== Steps =====
const stepsList = document.getElementById("steps-list");
const addStepBtn = document.getElementById("add-step");

addStepBtn.addEventListener("click", function () {
  const count = stepsList.querySelectorAll(".step-row").length + 1;

  const row = document.createElement("div");
  // to aplay css code
  row.className = "step-row";

  row.innerHTML = `
    <span class="step-num">Step ${count}:</span>
    <input type="text" class="step-input" placeholder="Write step ${count}" required>
  `;
  // add in the last 
  stepsList.appendChild(row);
});
