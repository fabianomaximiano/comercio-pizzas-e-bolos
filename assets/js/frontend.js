jQuery(document).ready(function($) {
    var ajax_url = comercio_pizzas_e_bolos_vars.ajax_url;
    var product_id = comercio_pizzas_e_bolos_vars.product_id;
    var invalid_cep_message = comercio_pizzas_e_bolos_vars.invalid_cep_message;
    var checking_cep_message = comercio_pizzas_e_bolos_vars.checking_cep_message;
    var delivery_available_message = comercio_pizzas_e_bolos_vars.delivery_available_message;
    var delivery_unavailable_message = comercio_pizzas_e_bolos_vars.delivery_unavailable_message;
    var error_checking_cep_message = comercio_pizzas_e_bolos_vars.error_checking_cep_message;

    var cepInput = $('#comercio_pizzas_e_bolos_cep_input');
    var checkCepButton = $('#comercio_pizzas_e_bolos_check_cep_button');
    var cepResultDiv = $('#comercio_pizzas_e_bolos_cep_result');
    var addToCartButton = $('.single_add_to_cart_button'); 

    // Função para formatar o CEP
    cepInput.on('keyup', function() {
        var value = $(this).val().replace(/\D/g, ''); 
        if (value.length > 5) {
            value = value.substring(0, 5) + '-' + value.substring(5, 8);
        }
        $(this).val(value);
    });

    checkCepButton.on('click', function() {
        var cep = cepInput.val().replace(/\D/g, ''); 

        if (cep.length !== 8) {
            cepResultDiv.html('<p style="color: red;">' + invalid_cep_message + '</p>');
            return;
        }

        cepResultDiv.html('<p style="color: blue;">' + checking_cep_message + '</p>');

        $.ajax({
            url: ajax_url, // Esta variável já contém o endpoint wc-ajax com a action
            type: 'POST',
            data: {
                // A linha 'action' foi removida, pois a action já está na URL (wc-ajax endpoint)
                cep: cep,
                product_id: product_id
            },
            success: function(response) {
                console.log("Resposta AJAX completa:", response); // Log da resposta completa para depuração
                if (response.success) {
                    var message = delivery_available_message
                        .replace('{cost}', response.data.cost_html)
                        .replace('{method}', response.data.method_title);
                    cepResultDiv.html('<p style="color: green;">' + message + '</p>');
                } else {
                    cepResultDiv.html('<p style="color: red;">' + response.data.message + '</p>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Erro na requisição AJAX:", textStatus, errorThrown, jqXHR.responseText);
                cepResultDiv.html('<p style="color: red;">' + error_checking_cep_message + '</p>');
            }
        });
    });
});