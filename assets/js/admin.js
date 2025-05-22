// No seu arquivo assets/js/admin.js

jQuery(document).ready(function($) {
    // Função para adicionar um novo item (tamanho, borda, sabor, etc.)
    $(document).on('click', '.comercio-pizzas-e-bolos-add-item', function() {
        var target = $(this).data('target');
        var wrapper = $('#comercio-pizzas-e-bolos-' + target + '-wrapper');
        var index = wrapper.data('index');
        var template = '';

        if (target === 'pizza-sizes') {
            template = `
                <div class="comercio-pizzas-e-bolos-pizza-size-item">
                    <label>Nome do Tamanho: <input type="text" name="comercio_pizzas_e_bolos_pizza_sizes[${index}][name]" value="" required /></label>
                    <label>Preço Adicional: <input type="number" step="0.01" name="comercio_pizzas_e_bolos_pizza_sizes[${index}][price]" value="0" /></label>
                    <button type="button" class="button comercio-pizzas-e-bolos-remove-item">Remover</button>
                </div>
            `;
        } else if (target === 'pizza-crusts') {
            template = `
                <div class="comercio-pizzas-e-bolos-pizza-crust-item">
                    <label>Nome da Borda/Massa: <input type="text" name="comercio_pizzas_e_bolos_pizza_crusts[${index}][name]" value="" required /></label>
                    <label>Preço Adicional: <input type="number" step="0.01" name="comercio_pizzas_e_bolos_pizza_crusts[${index}][price]" value="0" /></label>
                    <button type="button" class="button comercio-pizzas-e-bolos-remove-item">Remover</button>
                </div>
            `;
        } else if (target === 'pizza-flavors') {
            template = `
                <div class="comercio-pizzas-e-bolos-pizza-flavor-item">
                    <label>Nome do Sabor: <input type="text" name="comercio_pizzas_e_bolos_pizza_flavors[${index}][name]" value="" required /></label>
                    <label>Preço Base do Sabor: <input type="number" step="0.01" name="comercio_pizzas_e_bolos_pizza_flavors[${index}][price]" value="0" /></label>
                    <button type="button" class="button comercio-pizzas-e-bolos-remove-item">Remover</button>
                </div>
            `;
        } else if (target === 'pizza-extras') {
            template = `
                <div class="comercio-pizzas-e-bolos-pizza-extra-item">
                    <label>Nome do Ingrediente: <input type="text" name="comercio_pizzas_e_bolos_pizza_extras[${index}][name]" value="" required /></label>
                    <label>Preço Adicional: <input type="number" step="0.01" name="comercio_pizzas_e_bolos_pizza_extras[${index}][price]" value="0" /></label>
                    <button type="button" class="button comercio-pizzas-e-bolos-remove-item">Remover</button>
                </div>
            `;
        } else if (target === 'pizza-removables') {
            template = `
                <div class="comercio-pizzas-e-bolos-pizza-removable-item">
                    <label>Nome do Ingrediente: <input type="text" name="comercio_pizzas_e_bolos_pizza_removables[${index}][name]" value="" required /></label>
                    <button type="button" class="button comercio-pizzas-e-bolos-remove-item">Remover</button>
                </div>
            `;
        }

        wrapper.append(template);
        wrapper.data('index', index + 1); // Incrementa o índice para o próximo item
    });

    // Função para remover um item
    $(document).on('click', '.comercio-pizzas-e-bolos-remove-item', function() {
        $(this).closest('div[class$="-item"]').remove();
    });
});