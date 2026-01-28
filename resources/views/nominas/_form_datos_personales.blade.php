<label class="block mb-1 font-semibold dark:text-gray-100">Edad:</label>
<input type="number" name="edad" class="form-control dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" required>

<label class="block mb-1 font-semibold dark:text-gray-100">Estado civil:</label>
<select name="estado_civil" class="form-control dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
    <option value="soltero">Soltero/a</option>
    <option value="casado">Casado/a</option>
</select>

<label class="block mb-1 font-semibold dark:text-gray-100">Hijos a cargo:</label>
<input type="number" name="hijos_a_cargo" class="form-control dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" value="0">

<label class="block mb-1 font-semibold dark:text-gray-100">Hijos menores de 3 años:</label>
<input type="number" name="hijos_menores_3" class="form-control dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" value="0">

<label class="block mb-1 font-semibold dark:text-gray-100">Discapacidad (%):</label>
<input type="number" name="discapacidad_porcentaje" class="form-control dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" value="0">

<label class="block mb-1 font-semibold dark:text-gray-100">Ascendientes mayores de 65:</label>
<input type="checkbox" name="ascendientes_mayores_65" value="1" class="mb-2 dark:bg-gray-700 dark:border-gray-600">

<label class="block mb-1 font-semibold dark:text-gray-100">Ascendientes mayores de 75:</label>
<input type="checkbox" name="ascendientes_mayores_75" value="1" class="mb-3 dark:bg-gray-700 dark:border-gray-600">

<label class="block mb-2 font-semibold dark:text-gray-100">¿Cuánto ha ganado hasta ahora en el año? (€)</label>
<small class="text-sm text-gray-500 dark:text-gray-400">
    Introduce el salario acumulado en bruto (antes de retenciones y cotizaciones).
</small>
<input type="number" name="acumulado_actual" class="form-control dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" value="0">
