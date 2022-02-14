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
class Modx_Import_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Modx_Import_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Modx_Import_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/modx-import-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Modx_Import_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Modx_Import_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/modx-import-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	* Register Import Form Page
	*/
	public function register_import_form() {
		add_menu_page(
			__( 'Import from MODX ALPS Site', $this->plugin_name ),
			__( 'MODX Import', $this->plugin_name ),
			'read',
			'admin-modx-import',
			array( $this, 'include_form_partial' ),
			'dashicons-wordpress-alt',
			9999
		);
	}

	/**
	* Include Import Form Partial
	*/
	public function include_form_partial() {
		include_once( MODX_IMPORT_PATH . 'admin/partials/form-import.php' );
	}

}
