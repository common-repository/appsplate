<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Appsplate
 * @subpackage Appsplate/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Appsplate
 * @subpackage Appsplate/public
 * @author     Your Name <email@example.com>
 */
class Appsplate_Public
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $appsplate The ID of this plugin.
     */
    private $appsplate;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * @var Api
     */
    private $api;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $appsplate The name of the plugin.
     * @param string $version The version of this plugin.
     * @since    1.0.0
     */
    public function __construct($appsplate, $version)
    {

        $this->appsplate = $appsplate;
        $this->version = $version;
        $this->api = new Api();
    }

    public function api_init()
    {
        register_rest_route(
            Api::API_NAMESPACE,
            '/check-plugin',
            [
                'methods' => 'GET',
                'callback' => [$this->api, 'checkPlugin'],
// todo need implement check permission
//                'permission_callback' => function () {
//                    return parent::checkApiPermission();
//                }
            ]
        );

        register_rest_route(Api::API_NAMESPACE, '/settings', [
            'methods' => 'POST',
            'callback' => [$this->api, 'settings']
        ]);

        register_rest_route(Api::API_NAMESPACE, '/get-settings', [
            'methods' => 'GET',
            'callback' => [$this->api, 'getSettings']
        ]);

        register_rest_route(Api::API_NAMESPACE, '/get-auth-cookie', [
            'methods' => 'POST',
            'callback' => [$this->api, 'getAuthCookie']
        ]);

        register_rest_route(Api::API_NAMESPACE, '/sign-up', [
            'methods' => 'POST',
            'callback' => [$this->api, 'signUp']
        ]);

        register_rest_route(Api::API_NAMESPACE, '/firebase-sms-login', [
            'methods' => 'GET',
            'callback' => [$this->api, 'firebaseSmsLogin']
        ]);

        register_rest_route(Api::API_NAMESPACE, '/facebook-login', [
            'methods' => 'GET',
            'callback' => [$this->api, 'facebookLogin']
        ]);

        register_rest_route(Api::API_NAMESPACE, '/post-comment', [
            'methods' => 'GET',
            'callback' => [$this->api, 'postComment']
        ]);

        register_rest_route(Api::API_NAMESPACE, '/checkout', [
            'methods' => 'POST',
            'callback' => [$this->api, 'checkout']
        ]);

        register_rest_route(Api::API_NAMESPACE, '/cache', [
            'methods' => 'POST',
            'callback' => [$this->api, 'checkout'] // todo need implement
        ]);
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Appsplate_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Appsplate_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style($this->appsplate, plugin_dir_url(__FILE__) . 'css/appsplate-public.css', array(), $this->version, 'all');

    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Appsplate_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Appsplate_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script($this->appsplate, plugin_dir_url(__FILE__) . 'js/appsplate-public.js', array('jquery'), $this->version, false);

    }

    public function prepareCheckout()
    {
        if (isset($_GET['mobile']) && isset($_GET['code'])) {

            $code = sanitize_text_field(wp_unslash($_GET['code']));
            global $wpdb;
            $table_name = $wpdb->prefix . "appsplate_checkout";
            $item = $wpdb->get_row("SELECT * FROM $table_name WHERE code = '$code'");
            if ($item) {
                $data = json_decode(urldecode(base64_decode($item->order)), true);
            } else {
                return var_dump("Can't not get the order");
            }

            $shipping = isset($data['shipping']) ? $data['shipping'] : NULL;
            $billing = isset($data['billing']) ? $data['billing'] : $shipping;

            if (isset($data['token'])) {
                // Validate the cookie token
                $userId = wp_validate_auth_cookie($data['token'], 'logged_in');
                if (isset($billing)) {
                    update_user_meta($userId, 'billing_first_name', $billing["first_name"]);
                    update_user_meta($userId, 'billing_last_name', $billing["last_name"]);
                    update_user_meta($userId, 'billing_company', $billing["company"]);
                    update_user_meta($userId, 'billing_address_1', $billing["address_1"]);
                    update_user_meta($userId, 'billing_address_2', $billing["address_2"]);
                    update_user_meta($userId, 'billing_city', $billing["city"]);
                    update_user_meta($userId, 'billing_state', $billing["state"]);
                    update_user_meta($userId, 'billing_postcode', $billing["postcode"]);
                    update_user_meta($userId, 'billing_country', $billing["country"]);
                    update_user_meta($userId, 'billing_email', $billing["email"]);
                    update_user_meta($userId, 'billing_phone', $billing["phone"]);

                    update_user_meta($userId, 'shipping_first_name', $billing["first_name"]);
                    update_user_meta($userId, 'shipping_last_name', $billing["last_name"]);
                    update_user_meta($userId, 'shipping_company', $billing["company"]);
                    update_user_meta($userId, 'shipping_address_1', $billing["address_1"]);
                    update_user_meta($userId, 'shipping_address_2', $billing["address_2"]);
                    update_user_meta($userId, 'shipping_city', $billing["city"]);
                    update_user_meta($userId, 'shipping_state', $billing["state"]);
                    update_user_meta($userId, 'shipping_postcode', $billing["postcode"]);
                    update_user_meta($userId, 'shipping_country', $billing["country"]);
                    update_user_meta($userId, 'shipping_email', $billing["email"]);
                    update_user_meta($userId, 'shipping_phone', $billing["phone"]);
                } else {
                    $billing = [];
                    $shipping = [];

                    $billing["first_name"] = get_user_meta($userId, 'billing_first_name', true);
                    $billing["last_name"] = get_user_meta($userId, 'billing_last_name', true);
                    $billing["company"] = get_user_meta($userId, 'billing_company', true);
                    $billing["address_1"] = get_user_meta($userId, 'billing_address_1', true);
                    $billing["address_2"] = get_user_meta($userId, 'billing_address_2', true);
                    $billing["city"] = get_user_meta($userId, 'billing_city', true);
                    $billing["state"] = get_user_meta($userId, 'billing_state', true);
                    $billing["postcode"] = get_user_meta($userId, 'billing_postcode', true);
                    $billing["country"] = get_user_meta($userId, 'billing_country', true);
                    $billing["email"] = get_user_meta($userId, 'billing_email', true);
                    $billing["phone"] = get_user_meta($userId, 'billing_phone', true);

                    $shipping["first_name"] = get_user_meta($userId, 'shipping_first_name', true);
                    $shipping["last_name"] = get_user_meta($userId, 'shipping_last_name', true);
                    $shipping["company"] = get_user_meta($userId, 'shipping_company', true);
                    $shipping["address_1"] = get_user_meta($userId, 'shipping_address_1', true);
                    $shipping["address_2"] = get_user_meta($userId, 'shipping_address_2', true);
                    $shipping["city"] = get_user_meta($userId, 'shipping_city', true);
                    $shipping["state"] = get_user_meta($userId, 'shipping_state', true);
                    $shipping["postcode"] = get_user_meta($userId, 'shipping_postcode', true);
                    $shipping["country"] = get_user_meta($userId, 'shipping_country', true);
                    $shipping["email"] = get_user_meta($userId, 'shipping_email', true);
                    $shipping["phone"] = get_user_meta($userId, 'shipping_phone', true);

                    if (isset($billing["first_name"]) && !isset($shipping["first_name"])) {
                        $shipping = $billing;
                    }
                    if (!isset($billing["first_name"]) && isset($shipping["first_name"])) {
                        $billing = $shipping;
                    }
                }

                // Check user and authentication
                $user = get_userdata($userId);
                if ($user && (!is_user_logged_in() || get_current_user_id() != $userId)) {
                    wp_set_current_user($userId, $user->user_login);
                    wp_set_auth_cookie($userId);

                    header("Refresh:0");
                }
            } else {
                if (is_user_logged_in()) {
                    wp_logout();
                    wp_set_current_user(0);
                    header("Refresh:0");
                }
            }

            global $woocommerce;
            WC()->session->set('refresh_totals', true);
            WC()->cart->empty_cart();

            $products = $data['line_items'];
            foreach ($products as $product) {
                $productId = absint($product['product_id']);

                $quantity = $product['quantity'];
                $variationId = isset($product['variation_id']) ? $product['variation_id'] : "";

                $attributes = [];
                if (isset($product["meta_data"])) {
                    foreach ($product["meta_data"] as $item) {
                        $attributes[strtolower($item["key"])] = $item["value"];
                    }
                }

                // Check the product variation
                if (!empty($variationId)) {
                    $productVariable = new WC_Product_Variable($productId);
                    $listVariations = $productVariable->get_available_variations();
                    foreach ($listVariations as $vartiation => $value) {
                        if ($variationId == $value['variation_id']) {
                            $attributes = array_merge($value['attributes'], $attributes);
                            $woocommerce->cart->add_to_cart($productId, $quantity, $variationId, $attributes);
                        }
                    }
                } else {
                    $this->parseMetaDataForBookingProduct($product);
                    $woocommerce->cart->add_to_cart($productId, $quantity, 0, $attributes);
                }
            }

            if (isset($shipping)) {
                $woocommerce->customer->set_shipping_first_name($shipping["first_name"]);
                $woocommerce->customer->set_shipping_last_name($shipping["last_name"]);
                $woocommerce->customer->set_shipping_company($shipping["company"]);
                $woocommerce->customer->set_shipping_address_1($shipping["address_1"]);
                $woocommerce->customer->set_shipping_address_2($shipping["address_2"]);
                $woocommerce->customer->set_shipping_city($shipping["city"]);
                $woocommerce->customer->set_shipping_state($shipping["state"]);
                $woocommerce->customer->set_shipping_postcode($shipping["postcode"]);
                $woocommerce->customer->set_shipping_country($shipping["country"]);
            }

            if (isset($billing)) {
                $woocommerce->customer->set_billing_first_name($billing["first_name"]);
                $woocommerce->customer->set_billing_last_name($billing["last_name"]);
                $woocommerce->customer->set_billing_company($billing["company"]);
                $woocommerce->customer->set_billing_address_1($billing["address_1"]);
                $woocommerce->customer->set_billing_address_2($billing["address_2"]);
                $woocommerce->customer->set_billing_city($billing["city"]);
                $woocommerce->customer->set_billing_state($billing["state"]);
                $woocommerce->customer->set_billing_postcode($billing["postcode"]);
                $woocommerce->customer->set_billing_country($billing["country"]);
                $woocommerce->customer->set_billing_email($billing["email"]);
                $woocommerce->customer->set_billing_phone($billing["phone"]);
            }

            if (!empty($data['coupon_lines'])) {
                $coupons = $data['coupon_lines'];
                foreach ($coupons as $coupon) {
                    $woocommerce->cart->add_discount($coupon['code']);
                }
            }

            if (!empty($data['shipping_lines'])) {
                $shippingLines = $data['shipping_lines'];
                $shippingMethod = $shippingLines[0]['method_id'];
                WC()->session->set('chosen_shipping_methods', array($shippingMethod));
            }
            if (!empty($data['payment_method'])) {
                WC()->session->set('chosen_payment_method', $data['payment_method']);
            }
            if (isset($data['customer_note']) && !empty($data['customer_note'])) {
                $_POST["order_comments"] = sanitize_textarea_field($data['customer_note']);
                $checkout_fields = WC()->checkout->__get("checkout_fields");
                $checkout_fields["order"] = ["order_comments" => ["type" => "textarea", "class" => [], "label" => "Order notes", "placeholder" => "Notes about your order, e.g. special notes for delivery."]];
                WC()->checkout->__set("checkout_fields", $checkout_fields);
            }
        }

        if (isset($_GET['cookie'])) {
            $cookie = sanitize_text_field(urldecode(base64_decode(($_GET['cookie']))));
            $userId = wp_validate_auth_cookie($cookie, 'logged_in');
            if ($userId !== false) {
                $user = get_userdata($userId);
                if ($user !== false) {
                    wp_set_current_user($userId, $user->user_login);
                    wp_set_auth_cookie($userId);
                    if (isset($_GET['vendor_admin'])) {
                        global $wp;
                        $request = $wp->request;
                        wp_redirect(home_url("/" . $request));
                        die;
                    }
                }
            }
        }
    }

    private function parseMetaDataForBookingProduct($product)
    {
        if (is_plugin_active('woocommerce-appointments/woocommerce-appointments.php')) {
            //add meta_data to $_POST to use for booking product
            $meta_data = [];
            foreach ($product["meta_data"] as $key => $value) {
                if ($value["key"] == "staff_ids") {
                    $staffs = json_decode($value["value"], true);
                    if (count($staffs) > 0) {
                        $meta_data["wc_appointments_field_staff"] = sanitize_text_field($staffs[0]);
                    }
                } elseif ($value["key"] == "product_id") {
                    $meta_data["add-to-cart"] = sanitize_text_field($value["value"]);
                } else {
                    $meta_data[$value["key"]] = sanitize_text_field($value["value"]);
                }
            }
            $_POST = $meta_data;
        }
    }

}
