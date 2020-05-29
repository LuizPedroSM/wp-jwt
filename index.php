<?php 
/*
Plugin Name: WP JWT
Description: Esse plugin faz o login via JWT e permite utilizar requisições autenticadas sem cookie.
*/

include('jwt.php');

function wp_api_init()
{
    $namespace = 'wpjwt/v1';

    register_rest_route($namespace, '/login', array(
        'methods' => 'POST',
        'callback' => 'wp_api_ep_login'
    ));

    register_rest_route($namespace, '/validate', array(
        'methods' => 'GET',
        'callback' => 'wp_api_ep_validate'
    ));

    add_filter('rest_pre_dispatch', 'wb_rest_pre_dispatch', 10, 3);
}

function wb_rest_pre_dispatch($url, $server, $request)
{
    $params = $request->get_params();

    if (!empty($params['jwt'])) {
        $jwt = new JWT();
        $info = $jwt->validate($params['jwt']);

        if ($info && !empty($info->id)) {
            wp_set_current_user($info->id);
        }
    }

}

function wp_api_ep_validate($request)
{
    $array = array('valid' => false);

    $params = $request->get_params();

    if (!empty($params['jwt'])) {
        $jwt = new JWT();
        $info = $jwt->validate($params['jwt']);

        if ($info && !empty($info->id)) {
            $array['valid'] = true;
        }
    }
    
    return $array;
}

function wp_api_ep_login($request)
{
    $array = array('logged' => false);
    $params = $request->get_params();

    $result = wp_signon(array(
        'user_login' => $params['username'],
        'user_password' => $params['password']
    ));

    if (isset($result->data)) {
        $jwt = new JWT();

        $token = $jwt->create(array('id' => $result->data->ID));
        $array['logged'] = true;
        $array['token'] = $token;        
    }
    
    return $array;
}

add_action('rest_api_init', 'wp_api_init');