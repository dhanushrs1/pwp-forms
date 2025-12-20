<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ProWPKit_Forms {

	/**
	 * Single instance of the class.
	 */
	protected static $_instance = null;

	/**
	 * Main Instance
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files
	 */
	private function includes() {
		// Core Logic
		require_once PWP_FORMS_PATH . 'includes/class-database.php';
		// Future includes will go here (Form Manager, Render, Submit, etc.)
		require_once PWP_FORMS_PATH . 'includes/class-form-manager.php';
		require_once PWP_FORMS_PATH . 'includes/class-form-render.php';
		require_once PWP_FORMS_PATH . 'includes/class-form-submit.php';
		require_once PWP_FORMS_PATH . 'includes/class-email-manager.php';
		require_once PWP_FORMS_PATH . 'includes/class-upload-handler.php';
		require_once PWP_FORMS_PATH . 'includes/class-admin-dashboard.php';
		require_once PWP_FORMS_PATH . 'includes/class-settings.php';
	}

	/**
	 * Initialize Hooks
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', [ $this, 'on_plugins_loaded' ] );
	}

	/**
	 * On Plugins Loaded
	 */
	public function on_plugins_loaded() {
		// Instantiate classes
		new PWP_Form_Manager();
		new PWP_Form_Render();
		new PWP_Form_Submit();
		new PWP_Admin_Dashboard();
		new PWP_Settings();
	}

	/**
	 * Activation Hook
	 */
	public static function activate() {
		require_once PWP_FORMS_PATH . 'includes/class-database.php';
		PWP_Database::create_tables();
	}
}
