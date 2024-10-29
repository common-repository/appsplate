<?php


class Api
{
    const DEFAULT_AUTH_COOKIE_SECOND = 1209600;

    const OPTION_NAMESPACE = 'appsplate_';
    const API_NAMESPACE = 'appsplate/api/v1';

    public function checkPlugin(WP_REST_Request $request)
    {
        return rest_ensure_response([
            'host' => $_SERVER['HTTP_HOST']
        ]);
    }


    public function settings(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        if (empty($params['hash']) || empty($params['data'])) {
            return new WP_Error('incorrect', 'Something went wrong!');
        }

        update_option(self::OPTION_NAMESPACE . 'hash', sanitize_text_field($params['hash']));
        update_option(self::OPTION_NAMESPACE . 'data', sanitize_text_field($params['data']));

        return rest_ensure_response([
            'status' => 'success'
        ]);
    }

    public function getSettings(WP_REST_Request $request)
    {
        $params = $request->get_query_params();
        if (empty($params['hash']) || empty($params['token'])) {
            return new WP_Error('incorrect', 'Something went wrong!');
        }

        $wpSideHash = get_option(self::OPTION_NAMESPACE . 'hash');

        if ($wpSideHash !== $params['hash']) {
            return rest_ensure_response([
                'hasChange' => true,
                'data' => get_option(self::OPTION_NAMESPACE . 'data'),
            ]);
        }

        return rest_ensure_response([
            'hasChange' => false,
            'data' => ''
        ]);
    }

    public function getAuthCookie()
    {
        $json = file_get_contents('php://input');
        $params = json_decode($json, TRUE);
        if (!isset($params["username"]) || !isset($params["password"])) {
            return self::sendError("invalid_login", "Invalid params", 400);
        }
        $username = $params["username"];
        $password = $params["password"];

        if (isset($params["seconds"])) {
            $seconds = (int)$params["seconds"];
        } else {
            $seconds = self::DEFAULT_AUTH_COOKIE_SECOND;
        }

        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            return self::sendError($user->get_error_code(), "Invalid username/email and/or password.", 401);
        }

        $expiration = time() + apply_filters('auth_cookie_expiration', $seconds, $user->ID, true);
        $cookie = wp_generate_auth_cookie($user->ID, $expiration, 'logged_in');

        return [
            "cookie" => $cookie,
            "cookie_name" => LOGGED_IN_COOKIE,
            "user" => $this->getResponseUserInfo($user),
        ];
    }

    public function signUp()
    {
        $json = file_get_contents('php://input');
        $params = json_decode($json, TRUE);
        $usernameReq = $params["username"];
        $emailReq = $params["email"];
        if (isset($params["role"]) && $params["role"] != "subscriber" && $params["role"] != "wcfm_vendor" && $params["role"] != "seller") {
            return self::sendError("invalid_role", "Role is invalid.", 400);
        }
        $userPassReq = $params["user_pass"];

        $username = sanitize_user($usernameReq);

        $email = sanitize_email($emailReq);
        if (isset($params["seconds"])) {
            $seconds = (int)$params["seconds"];
        } else {
            $seconds = self::DEFAULT_AUTH_COOKIE_SECOND;
        }

        if (!validate_username($username)) {
            return self::sendError("invalid_username", "Username is invalid.", 400);
        } elseif (username_exists($username)) {
            return self::sendError("existed_username", "Username already exists.", 400);
        } else {
            if (!is_email($email)) {
                return self::sendError("invalid_email", "E-mail address is invalid.", 400);
            } elseif (email_exists($email)) {
                return self::sendError("existed_email", "E-mail address is already in use.", 400);
            } else {
                if (!$userPassReq) {
                    $params->user_pass = wp_generate_password();
                }

                $allowed_params = ['user_login', 'user_email', 'user_pass', 'display_name', 'user_nicename', 'user_url', 'nickname', 'first_name',
                    'last_name', 'description', 'rich_editing', 'user_registered', 'role', 'jabber', 'aim', 'yim',
                    'comment_shortcuts', 'admin_color', 'use_ssl', 'show_admin_bar_front',
                ];

                $dataRequest = $params;

                foreach ($dataRequest as $field => $value) {
                    if (in_array($field, $allowed_params)) {
                        $user[$field] = trim(sanitize_text_field($value));
                    }
                }

                $user['role'] = isset($params["role"]) ? sanitize_text_field($params["role"]) : get_option('default_role');
                $user_id = wp_insert_user($user);

                if (is_wp_error($user_id)) {
                    return self::sendError($user_id->get_error_code(), $user_id->get_error_message(), 400);
                }
            }
        }

        $expiration = time() + apply_filters('auth_cookie_expiration', $seconds, $user_id, true);
        $cookie = wp_generate_auth_cookie($user_id, $expiration, 'logged_in');

        return [
            "cookie" => $cookie,
            "user_id" => $user_id,
        ];
    }

    /**
     * @param $request
     * @return array|WP_Error
     */
    public function firebaseSmsLogin($request)
    {
        $phone = $request["phone"];
        if (!isset($phone)) {
            return self::sendError("invalid_login", "You must include a 'phone' variable.", 400);
        }
        $domain = $_SERVER['SERVER_NAME'];
        if (count(explode(".", $domain)) == 1) {
            $domain = "appsplate.com";
        }
        $user_name = $phone;
        $user_email = $phone . "@" . $domain;
        return $this->createSocialAccount($user_email, $user_name, $user_name, "", $user_name);
    }

    public function facebookLogin($request)
    {
        $fields = 'id,name,first_name,last_name,email';
        $enable_ssl = true;
        $access_token = $request["access_token"];
        if (!isset($access_token)) {
            return self::sendError("invalid_login", "You must include a 'access_token' variable. Get the valid access_token for this app from Facebook API.", 400);
        }
        $url = 'https://graph.facebook.com/me/?fields=' . $fields . '&access_token=' . $access_token;

        $result = wp_remote_retrieve_body(wp_remote_get($url));

        $result = json_decode($result, true);

        if (isset($result["email"])) {
            $user_name = strtolower($result['first_name'] . '.' . $result['last_name']);
            return $this->createSocialAccount($result["email"], $result['name'], $result['first_name'], $result['last_name'], $user_name);
        }
        return self::sendError("invalid_login", "Your 'access_token' did not return email of the user. Without 'email' user can't be logged in or registered. Get user email extended permission while joining the Facebook app.", 400);
    }

    public function postComment($request)
    {
        $cookie = $request["cookie"];
        if (!isset($cookie)) {
            return self::sendError("invalid_login", "You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.", 401);
        }
        $user_id = wp_validate_auth_cookie($cookie, 'logged_in');
        if (!$user_id) {
            return self::sendError("invalid_login", "Invalid cookie. Use the `generate_auth_cookie` method.", 401);
        }
        if (!$request["post_id"]) {
            return self::sendError("invalid_data", "No post specified. Include 'post_id' var in your request.", 400);
        } elseif (!$request["content"]) {
            return self::sendError("invalid_data", "Please include 'content' var in your request.", 400);
        }

        $comment_approved = 0;
        $user_info = get_userdata($user_id);
        $time = current_time('mysql');
        $agent = filter_has_var(INPUT_SERVER, 'HTTP_USER_AGENT') ? filter_input(INPUT_SERVER, 'HTTP_USER_AGENT') : 'Mozilla';
        $ips = filter_has_var(INPUT_SERVER, 'REMOTE_ADDR') ? filter_input(INPUT_SERVER, 'REMOTE_ADDR') : '127.0.0.1';
        $data = [
            'comment_post_ID' => $request["post_id"],
            'comment_author' => $user_info->user_login,
            'comment_author_email' => $user_info->user_email,
            'comment_author_url' => $user_info->user_url,
            'comment_content' => $request["content"],
            'comment_type' => '',
            'comment_parent' => 0,
            'user_id' => $user_info->ID,
            'comment_author_IP' => $ips,
            'comment_agent' => $agent,
            'comment_date' => $time,
            'comment_approved' => $comment_approved,
        ];
        $comment_id = wp_insert_comment($data);
        //add metafields
        $meta = json_decode(stripcslashes($request["meta"]), true);
        //extra function
        add_comment_meta($comment_id, 'rating', $meta['rating']);
        add_comment_meta($comment_id, 'verified', 0);

        return [
            "comment_id" => $comment_id,
        ];
    }

    public function checkout()
    {
        global $json_api;
        $json = file_get_contents('php://input');
        $params = json_decode($json);
        $order = $params->order;
        if (!isset($order)) {
            return self::sendError("invalid_checkout", "You must include a 'order' var in your request", 400);
        }
        global $wpdb;
        $table_name = $wpdb->prefix . "appsplate_checkout";

        $code = md5(mt_rand() . strtotime("now"));
        $success = $wpdb->insert($table_name, [
                'code' => $code,
                'order' => $order
            ]
        );
        if ($success) {
            return $code;
        }
        return self::sendError("error_insert_database", "Can't insert to database", 400);
    }

    /**
     * @param $email
     * @param $name
     * @param $firstName
     * @param $lastName
     * @param $userName
     * @return array
     */
    protected function createSocialAccount($email, $name, $firstName, $lastName, $userName)
    {
        $email_exists = email_exists($email);
        if ($email_exists) {
            $user = get_user_by('email', $email);
            $user_id = $user->ID;
        } else {
            $i = 0;
            while (username_exists($userName)) {
                $i++;
                $userName = strtolower($userName) . '.' . $i;
            }
            $random_password = wp_generate_password($length = 12, $include_standard_special_chars = false);
            $userdata = [
                'user_login' => $userName,
                'user_email' => $email,
                'user_pass' => $random_password,
                'display_name' => $name,
                'first_name' => $firstName,
                'last_name' => $lastName
            ];
            $user_id = wp_insert_user($userdata);
        }

        $expiration = time() + apply_filters('auth_cookie_expiration', self::DEFAULT_AUTH_COOKIE_SECOND, $user_id, true);
        $cookie = wp_generate_auth_cookie($user_id, $expiration, 'logged_in');
        $user = get_userdata($user_id);

        $response['wp_user_id'] = $user_id;
        $response['cookie'] = $cookie;
        $response['user_login'] = $user->user_login;
        $response['user'] = $this->getResponseUserInfo($user);
        return $response;
    }

    protected function getResponseUserInfo($user)
    {
        $shipping = $this->getShippingAddress($user->ID);
        $billing = $this->getBillingAddress($user->ID);
        $avatar = get_user_meta($user->ID, 'user_avatar', true);
        if (!isset($avatar) || $avatar == "" || is_bool($avatar)) {
            $avatar = get_avatar_url($user->ID);
        } else {
            $avatar = $avatar[0];
        }
        return [
            "id" => $user->ID,
            "username" => $user->user_login,
            "nicename" => $user->user_nicename,
            "email" => $user->user_email,
            "url" => $user->user_url,
            "registered" => $user->user_registered,
            "displayname" => $user->display_name,
            "firstname" => $user->user_firstname,
            "lastname" => $user->last_name,
            "nickname" => $user->nickname,
            "description" => $user->user_description,
            "capabilities" => $user->wp_capabilities,
            "role" => $user->roles,
            "shipping" => $shipping,
            "billing" => $billing,
            "avatar" => $avatar,
            "dokan_enable_selling" => $user->dokan_enable_selling
        ];
    }

    private function getShippingAddress($userId)
    {
        $shipping = [];

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

        if (empty($shipping["first_name"]) && empty($shipping["last_name"])
            && empty($shipping["company"]) && empty($shipping["address_1"])
            && empty($shipping["address_2"]) && empty($shipping["city"])
            && empty($shipping["state"]) && empty($shipping["postcode"])
            && empty($shipping["country"]) && empty($shipping["email"])
            && empty($shipping["phone"])
        ) {
            return null;
        }
        return $shipping;
    }

    private function getBillingAddress($userId)
    {
        $billing = [];

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

        if (empty($billing["first_name"]) && empty($billing["last_name"]) && empty($billing["company"])
            && empty($billing["address_1"]) && empty($billing["address_2"]) && empty($billing["city"])
            && empty($billing["state"]) && empty($billing["postcode"]) && empty($billing["country"])
            && empty($billing["email"]) && empty($billing["phone"])
        ) {
            return null;
        }

        return $billing;
    }

    /**
     * @param $code
     * @param $message
     * @param $statusCode
     * @return WP_Error
     */
    public static function sendError($code, $message, $statusCode)
    {
        return new WP_Error($code, $message, ['status' => $statusCode]);
    }
}