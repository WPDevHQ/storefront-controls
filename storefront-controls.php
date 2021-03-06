<?php
/**
 * Plugin Name: Storefront Controls
 * Plugin URI: http://wpdevhq.com/portfolio/storefront-control/
 * Description: I'm here to help you take control of Storefront theme with options to re-ordering or disable components of your theme's design i.e. header, footer e.t.c elements.
 * Version: 1.0.0
 * Author: WPDevHQ
 * Author URI: http://wpdevhq.com/
 * Requires at least: 3.8.1
 * Tested up to: 4.5.1
 *
 * Text Domain: storefront-controls
 * Domain Path: /languages/
 *
 * @package Storefront_Controls
 * @category Core
 * @author WPDevHQ
 *
 * This plugin is a fork of Homepage Control by WooThemes - https://github.com/woothemes/homepage-control
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Returns the main instance of Storefront_Controls to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Storefront_Controls
 */
function Storefront_Controls() {
	return Storefront_Controls::instance();
} // End Storefront_Controls()

Storefront_Controls();

/**
 * Main Storefront_Controls Class
 *
 * @class Storefront_Controls
 * @version	1.0.0
 * @since 1.0.0
 * @package	Kudos
 * @author Matty
 */
final class Storefront_Controls {
	
	/**
	 * Storefront_Controls The single instance of Storefront_Controls.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $token;
	public $header_token;
	public $footer_token;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $version;

	/**
	 * An instance of the Storefront_Controls_Admin class.
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $admin;

	/**
	 * The name of the hook on which we will be working our magic.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $hook;
	public $header_hook;
	public $footer_hook;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct () {
		$this->version 			= '1.0.0';
		$this->plugin_url 		= plugin_dir_url( __FILE__ );
		$this->plugin_path 		= plugin_dir_path( __FILE__ );
		$this->header_token 	= 'header-control';
		$this->footer_token 	= 'footer-control';
		$this->header_hook 		= (string)apply_filters( 'header_control_hook', 'storefront_header' );
		$this->footer_hook 		= (string)apply_filters( 'footer_control_hook', 'storefront_footer' );

		add_action( 'plugins_loaded', array( $this, 'header_migrate_data' ) );
		add_action( 'plugins_loaded', array( $this, 'footer_migrate_data' ) );

		register_activation_hook( __FILE__, array( $this, 'install' ) );

		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		/* Setup Customizer. */
		include_once( plugin_dir_path( __FILE__ ) . 'classes/class-header-control-customizer.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'classes/class-footer-control-customizer.php' );

		/* Reorder Components. */
		if ( ! is_admin() ) {
			add_action( 'get_header', array( $this, 'header_apply_restructuring_filter' ) );
			add_action( 'get_header', array( $this, 'footer_apply_restructuring_filter' ) );
		}
	} // End __construct()
	
	/**
	 * Main Storefront_Controls Instance
	 *
	 * Ensures only one instance of Storefront_Controls is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Storefront_Controls()
	 * @return Main Kudos instance
	 */
	public static function instance () {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	} // End instance()

	/**
	 * Load the localisation file.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'storefront-controls', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	} // End load_plugin_textdomain()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'storefront-control' ), '1.0.0' );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'storefront-control' ), '1.0.0' );
	} // End __wakeup()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
	} // End install()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number() {
		// Log the version number.
		update_option( $this->token . '_version', $this->version );
	} // End _log_version_number()
	
	// Header Control
	/**
	 * Migrate data from versions prior to 2.0.0.
	 * @access  public
	 * @since   2.0.0
	 * @return  void
	 */
	public function header_migrate_data () {
		$options = get_theme_mod( 'header_control' );

		if ( ! isset( $options ) ) {
			return; // Option is empty, probably first time installing the plugin.
		}

		if ( is_array( $options ) ) {
			$order = '';
			$disabled = '';
			$components = array();

			if ( isset( $options['component_order'] ) ) {
				$order = explode( ',', $options['component_order'] );

				if ( isset( $options['disabled_components'] ) ) {
					$disabled = explode( ',', $options['disabled_components'] );
				}

				if ( 0 < count( $order ) ) {
					foreach ( $order as $k => $v ) {
						if ( in_array( $v, $disabled ) ) {
							$components[] = '[disabled]' . $v; // Add disabled tag
						} else {
							$components[] = $v;
						}
					}
				}
			}

			$components = join( ',', $components );

			// Replace old data
			set_theme_mod( 'header_control', $components );
		}
	} // End maybe_migrate_data()

	/**
	 * Work through the stored data and display the components in the desired order, without the disabled components.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function header_apply_restructuring_filter () {
		$options = get_theme_mod( 'header_control' );
		$components = array();

		if ( isset( $options ) && '' != $options ) {
			$components = explode( ',', $options );

			// Remove all existing actions on woo_homepage.
			remove_all_actions( $this->header_hook );

			// Remove disabled components
			$components = $this->_maybe_remove_disabled_items( $components );

			// Perform the reordering!
			if ( 0 < count( $components ) ) {
				$count = 5;
				foreach ( $components as $k => $v ) {
					if (strpos( $v, '@' ) !== FALSE) {
						$obj_v = explode( '@' , $v );
						if ( class_exists( $obj_v[0] ) && method_exists( $obj_v[0], $obj_v[1] ) ) {
							add_action( $this->header_hook, array( $obj_v[0], $obj_v[1] ), $count );
						} // End If Statement
					} else {
						if ( function_exists( $v ) ) {
							add_action( $this->header_hook, esc_attr( $v ), $count );
						}
					} // End If Statement

					$count + 5;
				}
			}
		}
	} // End maybe_apply_restructuring_filter()
	
	// Footer Control
	/**
	 * Migrate data from versions prior to 2.0.0.
	 * @access  public
	 * @since   2.0.0
	 * @return  void
	 */
	public function footer_migrate_data () {
		$options = get_theme_mod( 'footer_control' );

		if ( ! isset( $options ) ) {
			return; // Option is empty, probably first time installing the plugin.
		}

		if ( is_array( $options ) ) {
			$order = '';
			$disabled = '';
			$components = array();

			if ( isset( $options['component_order'] ) ) {
				$order = explode( ',', $options['component_order'] );

				if ( isset( $options['disabled_components'] ) ) {
					$disabled = explode( ',', $options['disabled_components'] );
				}

				if ( 0 < count( $order ) ) {
					foreach ( $order as $k => $v ) {
						if ( in_array( $v, $disabled ) ) {
							$components[] = '[disabled]' . $v; // Add disabled tag
						} else {
							$components[] = $v;
						}
					}
				}
			}

			$components = join( ',', $components );

			// Replace old data
			set_theme_mod( 'footer_control', $components );
		}
	} // End maybe_migrate_data()

	/**
	 * Work through the stored data and display the components in the desired order, without the disabled components.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function footer_apply_restructuring_filter () {
		$options = get_theme_mod( 'footer_control' );
		$components = array();

		if ( isset( $options ) && '' != $options ) {
			$components = explode( ',', $options );

			// Remove all existing actions on woo_homepage.
			remove_all_actions( $this->footer_hook );

			// Remove disabled components
			$components = $this->_maybe_remove_disabled_items( $components );

			// Perform the reordering!
			if ( 0 < count( $components ) ) {
				$count = 5;
				foreach ( $components as $k => $v ) {
					if (strpos( $v, '@' ) !== FALSE) {
						$obj_v = explode( '@' , $v );
						if ( class_exists( $obj_v[0] ) && method_exists( $obj_v[0], $obj_v[1] ) ) {
							add_action( $this->footer_hook, array( $obj_v[0], $obj_v[1] ), $count );
						} // End If Statement
					} else {
						if ( function_exists( $v ) ) {
							add_action( $this->footer_hook, esc_attr( $v ), $count );
						}
					} // End If Statement

					$count + 5;
				}
			}
		}
	} // End maybe_apply_restructuring_filter()

    //private function includes() {
	    //require_once( 'header-control.php' );
        //require_once( 'footer-control.php' );
	//}

    /**
	 * Maybe remove disabled items from the main ordered array.
	 * @access  private
	 * @since   1.0.0
	 * @param   array $components 	Array with components order.
	 * @return  array           	Re-ordered components with disabled components removed.
	 */
	private function _maybe_remove_disabled_items( $components ) {
		if ( 0 < count( $components ) ) {
			foreach ( $components as $k => $v ) {
				if ( false !== strpos( $v, '[disabled]' ) ) {
					unset( $components[ $k ] );
				}
			}
		}
		return $components;
	} // End _maybe_remove_disabled_items()
} // End Class