<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/claytonk
 * @since      1.0.0
 *
 * @package    Modx_Import
 * @subpackage Modx_Import/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Modx_Import
 * @subpackage Modx_Import/admin
 * @author     Clayton Kinney <clayton@316creative.com>
 */
class Import_Request {

	public function __construct( $url, $params ) {

		$this->site = $url.'/wp-export/wp-export.php';
        $this->params = http_build_query($params);
        $this->credentials = base64_encode($params['username'].':'.$params['password']);

        $options  = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n"."Authorization: Basic $this->credentials",
                'content' => $this->params
            )
        );

        $context  = stream_context_create($options);

        $result = file_get_contents($this->site, false, $context);

        if (!$result){
                $error = error_get_last();
                print_r($error);
        }

        $this->reply = $result;

	}

}
