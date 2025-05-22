// assets/js/busca-cep.js

/**
 * Função para consultar CEP na ViaCEP e preencher campos de endereço.
 * @param {string} cep - O CEP a ser consultado.
 * @param {function} callbackSuccess - Callback a ser executado em caso de sucesso na consulta ViaCEP. Recebe os dados do endereço.
 * @param {function} callbackError - Callback a ser executado em caso de erro na consulta ViaCEP. Recebe a mensagem de erro.
 */
function consultarCepViaCEP(cep, callbackSuccess, callbackError) {
    console.log('busca-cep.js: Consultando ViaCEP para o CEP:', cep);
    jQuery.ajax({
        url: 'https://viacep.com.br/ws/' + cep + '/json/',
        type: 'GET',
        dataType: 'json',
        success: function(addressResponse) {
            console.log('busca-cep.js: Resposta ViaCEP:', addressResponse);
            if (!addressResponse.erro) {
                // Preenche os campos do formulário de checkout, se existirem
                // Estes são os IDs padrão dos campos do WooCommerce. Ajuste se o seu tema os altera.
                jQuery('input#billing_address_1').val(addressResponse.logradouro);
                jQuery('input#billing_neighborhood').val(addressResponse.bairro); // WooCommerce usa 'billing_neighborhood' para bairro
                jQuery('input#billing_city').val(addressResponse.localidade);
                // Dispara 'change' para que o WooCommerce atualize as cidades baseadas no estado
                jQuery('select#billing_state').val(addressResponse.uf).trigger('change');
                jQuery('input#billing_postcode').val(addressResponse.cep);

                // Se você tem campos de entrega separados (geralmente quando o cliente marca "Enviar para um endereço diferente?")
                jQuery('input#shipping_address_1').val(addressResponse.logradouro);
                jQuery('input#shipping_neighborhood').val(addressResponse.bairro);
                jQuery('input#shipping_city').val(addressResponse.localidade);
                jQuery('select#shipping_state').val(addressResponse.uf).trigger('change');
                jQuery('input#shipping_postcode').val(addressResponse.cep);

                if (typeof callbackSuccess === 'function') {
                    callbackSuccess(addressResponse);
                }
            } else {
                console.log('busca-cep.js: CEP não encontrado pela ViaCEP.');
                if (typeof callbackError === 'function') {
                    callbackError(comercio_pizzas_e_bolos_vars.delivery_unavailable_message + ' (CEP não encontrado)');
                }
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('busca-cep.js: Erro na consulta ViaCEP:', textStatus, errorThrown);
            if (typeof callbackError === 'function') {
                callbackError(comercio_pizzas_e_bolos_vars.error_checking_cep_message + ' (Erro na consulta de CEP)');
            }
        }
    });
}