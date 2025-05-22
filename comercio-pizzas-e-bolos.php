<?php
/**
 * Plugin Name: Comércio de Pizzas e Bolos
 * Description: Plugin para gerenciar pedidos de pizzas e bolos com verificação de entrega por CEP no WooCommerce.
 * Version: 1.0.0
 * Author: Seu Nome
 * Author URI: Sua URL (opcional)
 * Text Domain: comercio-pizzas-e-bolos
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * WC requires at least: 4.0
 * WC tested up to: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'ComercioPizzasBolos' ) ) {

    class ComercioPizzasBolos {

        public function __construct() {
            // Verifica se o WooCommerce está ativo
            if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
                add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
                add_action( 'add_meta_boxes', array( $this, 'add_pizza_builder_meta_box' ) );
                add_action( 'save_post_product', array( $this, 'save_pizza_builder_meta_box_data' ) );

                add_action( 'woocommerce_single_product_summary', array( $this, 'handle_frontend_output_and_scripts' ), 25 );

                // HOOK AJAX para WooCommerce (substitui wp_ajax_ e wp_ajax_nopriv_)
                add_action( 'woocommerce_ajax_cpe_check_cep_delivery_unique', array( $this, 'check_cep_delivery_callback' ) );

                // Filtro para desativar o botão de adicionar ao carrinho
                add_filter( 'woocommerce_is_purchasable', array( $this, 'disable_add_to_cart_if_builder_enabled' ), 10, 2 );

                // Adiciona um hook para verificar o status do construtor no admin (para depuração)
                add_action( 'admin_notices', array( $this, 'check_builder_status_admin_notice' ) );

            } else {
                add_action( 'admin_notices', array( $this, 'woocommerce_not_active_notice' ) );
            }
        }

        /**
         * Aviso se o WooCommerce não estiver ativo.
         */
        public function woocommerce_not_active_notice() {
            ?>
            <div class="notice notice-error">
                <p><?php _e( 'O plugin "Comércio de Pizzas e Bolos" requer o WooCommerce para funcionar. Por favor, instale e ative o WooCommerce.', 'comercio-pizzas-e-bolos' ); ?></p>
            </div>
            <?php
        }

        /**
         * Enfileira scripts e estilos para o admin.
         */
        public function enqueue_admin_scripts() {
            global $typenow;
            if ( 'product' === $typenow ) {
                wp_enqueue_style( 'comercio-pizzas-e-bolos-admin-style', plugin_dir_url( __FILE__ ) . 'assets/css/admin-style.css', array(), '1.0.0' );
                wp_enqueue_script( 'comercio-pizzas-e-bolos-admin-script', plugin_dir_url( __FILE__ ) . 'assets/js/admin.js', array( 'jquery' ), '1.0.0', true );
            }
        }

        /**
         * Adiciona o metabox na tela de edição do produto.
         */
        public function add_pizza_builder_meta_box() {
            add_meta_box(
                'comercio_pizzas_e_bolos_builder_meta_box',
                __( 'Configurações de Pizza/Bolo', 'comercio-pizzas-e-bolos' ),
                array( $this, 'render_pizza_builder_meta_box' ),
                'product',
                'side',
                'high'
            );
        }

        /**
         * Renderiza o conteúdo do metabox.
         */
        public function render_pizza_builder_meta_box( $post ) {
            // Use nonce para segurança
            wp_nonce_field( 'comercio_pizzas_e_bolos_builder_meta_box', 'comercio_pizzas_e_bolos_builder_meta_box_nonce' );

            $enable_builder = get_post_meta( $post->ID, '_comercio_pizzas_e_bolos_enable_pizza_builder', true );
            ?>
            <p>
                <label for="comercio_pizzas_e_bolos_enable_pizza_builder">
                    <input type="checkbox" id="comercio_pizzas_e_bolos_enable_pizza_builder" name="_comercio_pizzas_e_bolos_enable_pizza_builder" value="yes" <?php checked( $enable_builder, 'yes' ); ?>>
                    <?php _e( 'Ativar Construtor de Pizza/Bolo para este produto', 'comercio-pizzas-e-bolos' ); ?>
                </label>
            </p>
            <?php
        }

        /**
         * Salva os dados do metabox.
         */
        public function save_pizza_builder_meta_box_data( $post_id ) {
            // Verifica se o nonce é válido
            if ( ! isset( $_POST['comercio_pizzas_e_bolos_builder_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['comercio_pizzas_e_bolos_builder_meta_box_nonce'], 'comercio_pizzas_e_bolos_builder_meta_box' ) ) {
                return;
            }

            // Verifica as permissões do usuário
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }

            // Salva o valor do checkbox
            $enable_builder = isset( $_POST['_comercio_pizzas_e_bolos_enable_pizza_builder'] ) ? 'yes' : 'no';
            update_post_meta( $post_id, '_comercio_pizzas_e_bolos_enable_pizza_builder', $enable_builder );
        }

        /**
         * Exibe um aviso no admin se o construtor estiver ativado para um produto específico.
         */
        public function check_builder_status_admin_notice() {
            global $pagenow, $post;
            if ( 'post.php' === $pagenow && isset( $_GET['post'] ) && 'product' === get_post_type( $_GET['post'] ) ) {
                $product_id = intval( $_GET['post'] );
                $enable_builder = get_post_meta( $product_id, '_comercio_pizzas_e_bolos_enable_pizza_builder', true );
                if ( 'yes' === $enable_builder ) {
                    echo '<div class="notice notice-success is-dismissible">';
                    echo '<p><strong>DEBUG (ADMIN): Construtor de Pizza/Bolo está ATIVO para este produto (ID: ' . $product_id . ').</strong></p>';
                    echo '</div>';
                } else {
                    echo '<div class="notice notice-info is-dismissible">';
                    echo '<p><strong>DEBUG (ADMIN): Construtor de Pizza/Bolo está INATIVO para este produto (ID: ' . $product_id . ').</strong></p>';
                    echo '</div>';
                }
            }
        }

        /**
         * Lida com a exibição do HTML e enfileiramento de scripts no frontend.
         */
        public function handle_frontend_output_and_scripts() {
            global $product; 

            if ( $product instanceof WC_Product ) {
                $product_id = $product->get_id();
                $enable_builder = get_post_meta( $product_id, '_comercio_pizzas_e_bolos_enable_pizza_builder', true );

                if ( 'yes' === $enable_builder ) {
                    // 1. Enfileirar Styles (CSS)
                    wp_enqueue_style( 'comercio-pizzas-e-bolos-frontend-style', plugin_dir_url( __FILE__ ) . 'assets/css/frontend.css', array(), '1.0.0' );

                    // INJEÇÃO DIRETA DO JAVASCRIPT E VARS (SOLUÇÃO TEMPORÁRIA PARA TESTE DE ENFILEIRAMENTO)
                    echo '<script type="text/javascript">';
                    echo 'var comercio_pizzas_e_bolos_vars = ' . json_encode(array(
                        'ajax_url'                   => WC_AJAX::get_endpoint( 'cpe_check_cep_delivery_unique' ),
                        'product_id'                 => $product_id,
                        'invalid_cep_message'        => __( 'Por favor, digite um CEP válido (8 dígitos).', 'comercio-pizzas-e-bolos' ),
                        'checking_cep_message'       => __( 'Verificando CEP...', 'comercio-pizzas-e-bolos' ),
                        'delivery_available_message' => __( 'Entrega disponível! Custo: {cost} ({method})', 'comercio-pizzas-e-bolos' ),
                        'delivery_unavailable_message' => __( 'Entrega não disponível para este CEP.', 'comercio-pizzas-e-bolos' ),
                        'error_checking_cep_message' => __( 'Ocorreu um erro ao verificar o CEP. Tente novamente.', 'comercio-pizzas-e-bolos' ),
                    )) . ';';
                    // Garante que o arquivo frontend.js existe e pode ser lido
                    $js_file_path = plugin_dir_path( __FILE__ ) . 'assets/js/frontend.js';
                    if ( file_exists( $js_file_path ) ) {
                        echo file_get_contents( $js_file_path );
                    } else {
                        // Se o arquivo não for encontrado, loga um erro para depuração
                        error_log( 'Erro: frontend.js não encontrado em ' . $js_file_path );
                        echo 'console.error("Erro: Arquivo frontend.js não encontrado ou inacessível.");';
                    }
                    echo '</script>';
                    // FIM DA INJEÇÃO DIRETA

                    // 2. Exibir o campo de verificação de CEP (e futuro construtor)
                    echo '<div id="comercio-pizzas-e-bolos-builder-wrapper">';
                    echo '<h2>' . esc_html__( 'Verifique a entrega no seu CEP', 'comercio-pizzas-e-bolos' ) . '</h2>';
                    echo '<div class="comercio-pizzas-e-bolos-cep-checker">';
                    echo '<input type="text" id="comercio_pizzas_e_bolos_cep_input" placeholder="' . esc_attr__( 'Digite seu CEP (ex: 00000-000)', 'comercio-pizzas-e-bolos' ) . '" maxlength="9">';
                    echo '<button type="button" id="comercio_pizzas_e_bolos_check_cep_button">' . esc_html__( 'Verificar CEP', 'comercio-pizzas-e-bolos' ) . '</button>';
                    echo '</div>';
                    echo '<div id="comercio_pizzas_e_bolos_cep_result"></div>';
                    echo '</div>';
                }
            }
        }

        /**
         * Desativa o botão "Adicionar ao Carrinho" se o construtor estiver ativo.
         */
        public function disable_add_to_cart_if_builder_enabled( $purchasable, $product ) {
            if ( $product instanceof WC_Product ) {
                $enable_builder = get_post_meta( $product->get_id(), '_comercio_pizzas_e_bolos_enable_pizza_builder', true );
                if ( 'yes' === $enable_builder ) {
                    return false;
                }
            }
            return $purchasable;
        }

        /**
         * Callback da requisição AJAX para verificar a entrega por CEP.
         */
        public function check_cep_delivery_callback() {
            // ***** PONTOS DE DEPURACAO CRITICOS: VERIFIQUE QUAL MENSAGEM APARECE *****
            error_log( 'DEBUG: check_cep_delivery_callback() foi atingida!' );
            wp_die( 'DEBUG 1: check_cep_delivery_callback() foi atingida!' ); // Este deve aparecer primeiro

            $response = array(
                'success' => false,
                'data'    => array(
                    'message' => __( 'Erro desconhecido.', 'comercio-pizzas-e-bolos' ),
                ),
            );

            if ( ! isset( $_POST['cep'] ) || ! isset( $_POST['product_id'] ) ) {
                error_log( 'DEBUG: Dados POST incompletos. CEP: ' . (isset($_POST['cep']) ? $_POST['cep'] : 'N/A') . ', Product ID: ' . (isset($_POST['product_id']) ? $_POST['product_id'] : 'N/A') );
                $response['data']['message'] = __( 'Dados incompletos.', 'comercio-pizzas-e-bolos' );
                wp_send_json( $response );
                wp_die();
            }

            error_log( 'DEBUG 2: Dados POST recebidos. CEP: ' . $_POST['cep'] . ', Product ID: ' . $_POST['product_id'] );
            wp_die( 'DEBUG 2: Dados POST recebidos!' ); // Se este aparecer, os dados POST estão ok

            $cep        = sanitize_text_field( $_POST['cep'] );
            $product_id = intval( $_POST['product_id'] );

            $cep = preg_replace( '/[^0-9]/', '', $cep );

            if ( strlen( $cep ) !== 8 ) {
                error_log( 'DEBUG: CEP inválido após sanitização: ' . $cep );
                $response['data']['message'] = __( 'CEP inválido.', 'comercio-pizzas-e-bolos' );
                wp_send_json( $response );
                wp_die();
            }

            error_log( 'DEBUG 3: CEP sanitizado e válido: ' . $cep );
            wp_die( 'DEBUG 3: CEP sanitizado e válido!' ); // Se este aparecer, CEP está ok

            $package = array(
                'destination' => array(
                    'country'   => 'BR', 
                    'state'     => '',   
                    'postcode'  => $cep,
                    'city'      => '',
                    'address_1' => '',
                    'address_2' => '',
                ),
                'contents'    => array(),
            );

            $product = wc_get_product( $product_id );
            if ( $product ) {
                $package['contents'][] = array(
                    'product_id' => $product->get_id(),
                    'variation_id' => 0, 
                    'quantity'   => 1,
                    'data'       => $product,
                    'weight'     => $product->get_weight() ? $product->get_weight() : 1.5,
                    'length'     => $product->get_length() ? $product->get_length() : 20,
                    'width'      => $product->get_width() ? $product->get_width() : 20,
                    'height'     => $product->get_height() ? $product->get_height() : 10,
                    'line_total' => $product->get_price(), 
                    'line_tax'   => 0,
                );
            } else {
                error_log( 'DEBUG: Produto não encontrado para o ID: ' . $product_id );
                $response['data']['message'] = __( 'Produto não encontrado.', 'comercio-pizzas-e-bolos' );
                wp_send_json( $response );
                wp_die();
            }

            error_log( 'DEBUG 4: Produto encontrado e pacote preenchido.' );
            wp_die( 'DEBUG 4: Produto encontrado e pacote preenchido!' ); // Se este aparecer, pacote está ok

            $shipping_methods = WC()->shipping->get_shipping_methods();
            $calculated_rates = array();

            foreach ( $shipping_methods as $method_id => $method ) {
                // Adiciona um log para ver qual método de envio está sendo processado
                error_log( 'DEBUG: Processando método de envio: ' . $method_id );
                $rates = $method->get_rates_for_package( $package );
                if ( ! empty( $rates ) ) {
                    $calculated_rates = array_merge( $calculated_rates, $rates );
                }
            }

            error_log( 'DEBUG 5: Métodos de envio calculados. Total de taxas: ' . count($calculated_rates) );
            wp_die( 'DEBUG 5: Métodos de envio calculados!' ); // Se este aparecer, cálculo inicial está ok

            if ( ! empty( $calculated_rates ) ) {
                uasort( $calculated_rates, 'wc_shipping_method_rate_sort' );

                $cheapest_rate = null;
                foreach ( $calculated_rates as $rate_id => $rate ) {
                    if ( is_null( $cheapest_rate ) || $rate->cost < $cheapest_rate->cost ) {
                        $cheapest_rate = $rate;
                    }
                }

                if ( $cheapest_rate ) {
                    $response['success'] = true;
                    $response['data']['available']    = true;
                    $response['data']['cost']         = $cheapest_rate->cost;
                    $response['data']['cost_html']    = wc_price( $cheapest_rate->cost );
                    $response['data']['method_title'] = $cheapest_rate->label;
                    $response['data']['method_id']    = $cheapest_rate->method_id;
                    $response['data']['instance_id']  = $cheapest_rate->instance_id;
                    $response['data']['message']      = __( 'Entrega disponível!', 'comercio-pizzas-e-bolos' );
                    error_log( 'DEBUG: Entrega disponível! Cost: ' . $cheapest_rate->cost );
                } else {
                    $response['success'] = false; 
                    $response['data']['available'] = false;
                    $response['data']['message']   = __( 'Nenhuma opção de entrega válida encontrada para este CEP.', 'comercio-pizzas-e-bolos' );
                    error_log( 'DEBUG: Nenhuma opção de entrega válida encontrada.' );
                }

            } else {
                $response['success'] = false;
                $response['data']['available'] = false;
                $response['data']['message']   = __( 'Nenhuma opção de entrega encontrada para este CEP.', 'comercio-pizzas-e-bolos' );
                error_log( 'DEBUG: Nenhuma opção de entrega encontrada (calculated_rates vazio).' );
            }

            // A chamada final wp_send_json e wp_die sem a mensagem de teste
            wp_send_json( $response );
            wp_die(); 
        }
    } 
}

new ComercioPizzasBolos();