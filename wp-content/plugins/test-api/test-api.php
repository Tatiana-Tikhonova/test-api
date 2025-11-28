<?php

/**
 * Plugin Name: Test Api
 * Version: 1.0.0
 */


namespace Test_Api;

use Test_Api\Test_Api_Data_Handler;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once __DIR__ . "/autoload.php";
class Test_Api
{
    public function __construct(
        private Test_Api_Data_Handler $data_handler
    ) {
        add_action('wp_enqueue_scripts', [$this, 'test_api_enqueue_scripts']);

        if (wp_doing_ajax()) {
            add_action('wp_ajax_test_api_action', [$this, 'test_api_ajax_action']);
            add_action('wp_ajax_nopriv_test_api_action', [$this, 'test_api_ajax_action']);
        }
        add_action('woocommerce_single_product_summary', [$this, 'test_api_product_html'], 15);
    }
    public function test_api_enqueue_scripts()
    {
        if (!is_product()) return;
        wp_enqueue_script('my-test-api-script', plugin_dir_url(__FILE__) . 'assets/script.js', [], null, [
            'in_footer' => true,
        ]);
        $product_id = get_queried_object_id();
        wp_localize_script('my-test-api-script', 'testApiScriptData', [
            'ajax_url'  => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('test-api-data'),
            'product_id' => $product_id,
            'defaults' => $this->data_handler->get($product_id),
        ]);
        wp_enqueue_style('my-test-api-style', plugin_dir_url(__FILE__) . 'assets/styles.css', [], null);
    }
    public function test_api_ajax_action()
    {
        $check_nonce = check_ajax_referer('test-api-data', $_POST['_wpnonce'], false);

        if (!$check_nonce) {
            wp_send_json_error(['message' => 'Ошибка запроса. Обновите страницу и попробуйте еще раз'], 403);
        }
        $product_id = intval($_POST['product_id'] ?? '');
        $variation = esc_sql(sanitize_text_field($_POST['variation'] ?? ''));
        $get_color = esc_sql(sanitize_text_field($_POST['get_color'] ?? ''));
        if (empty($product_id) || !preg_match('/^[a-z]+_[A-Z]$/', $variation)) {
            wp_send_json_error(['message' => 'Неверные параметры запроса'], 400);
        }
        $response = $this->data_handler->get($product_id, $variation, $get_color);
        if (empty($response)) {
            wp_send_json_error(['message' => 'Товара нет в наличии'], 404);
        }
        wp_send_json_success($response);
    }

    public function test_api_product_html()
    {
        ob_start();
?>
        <div class="test-api-block">
            <div class="test-api-block__row test-api-item">
                <p id="test-api-item__message" class="test-api-item__message"></p>
            </div>
            <div class="test-api-block__row test-api-item">
                <button data-color="red" class="test-api-item__button test-api-item__button_red"></button>
                <button data-color="green" class="test-api-item__button test-api-item__button_green"></button>
                <button data-color="yellow" class="test-api-item__button test-api-item__button_yellow"></button>
            </div>
            <div class="test-api-block__row test-api-item">
                <button data-size="S" class=" test-api-item__button">S</button>
                <button data-size="M" class="test-api-item__button">M</button>
                <button data-size="L" class="test-api-item__button">L</button>
            </div>
        </div>
<?php
        echo ob_get_clean();
    }
}
new Test_Api(new Test_Api_Data_Handler());
