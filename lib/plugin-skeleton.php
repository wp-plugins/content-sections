<?php
/**
 * Plugin Template
 * 
 * This is meant to act as a template for creating a new WordPress plugin.
 * 
 * @package PluginTemplate
 * 
 * @global    object    $wpdb
 * 
 * @author kynatro
 * @version 1.0.0
 */
/*
Plugin Name: Plugin Template
Plugin URI: http://kynatro.com/
Description: A simple WordPress plugin template to base your plugin structure on
Version: 1.0.0
Author: kynatro
Author URI: http://kynatro.com
License: GPL3

Copyright 2011 Dave Shepard  (email : dave@kynatro.com)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class plugin_skeleton {
	var $friendly_name = "Plugin Template";
	var $namespace = "plugin-template";
	var $version = "0.1";
	public static $const;
	public static $instance;
	
	// Default plugin options
	var $settings = array(
		array(	'name'=>'Example Setting',
				'slug'=>'example_setting',
				'type'=>'text', 				// can be 'text', 'checkbox', 'textarea'
				'default'=>'Example Default'	// enter false for no default
		),
	);
	
	/**
	 * Instantiation construction
	 * 
	 * @uses add_action()
	 * @uses PluginTemplate::wp_register_scripts()
	 * @uses PluginTemplate::wp_register_styles()
	 */
	function __construct($cfg = null) {
		if($cfg)
			self::$const = $cfg;
		// Load all library files used by this plugin
		
		$libs = glob( constant(self::$const . '_PATH') . '/lib/*.php' );
		
		foreach( $libs as $lib ) {
			include_once( $lib );
		}
		/**
		 * Make this plugin available for translation.
		 * Translations can be added to the /languages/ directory.
		 */
		load_theme_textdomain( $this->namespace, constant(self::$const . '_PATH') . '/languages' );

		// Add all action, filter and shortcode hooks
		$this->add_hooks();
	}
	
	/**
	 * Add in various hooks
	 * 
	 * Place all add_action, add_filter, add_shortcode hook-ins here
	 */
	public function add_hooks() {
		// Add a settings link next to the "Deactivate" link on the plugin listing page
		add_filter( 'plugin_action_links', array( &$this, 'plugin_action_links' ), 10, 2 );
		// Register all JavaScripts for this plugin
		add_action( 'init', array( &$this, 'wp_register_scripts' ), 1 );
		// Register all Stylesheets for this plugin
		add_action( 'init', array( &$this, 'wp_register_styles' ), 1 );
		// Options page for configuration
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
	}
	
	/**
	 * Sanitize data
	 * 
	 * @param mixed $str The data to be sanitized
	 * 
	 * @uses wp_kses()
	 * 
	 * @return mixed The sanitized version of the data
	 */
	private function _sanitize( $str ) {
		if ( !function_exists( 'wp_kses' ) ) {
			require_once( ABSPATH . 'wp-includes/kses.php' );
		}
		global $allowedposttags;
		global $allowedprotocols;
		
		if ( is_string( $str ) ) {
			$str = wp_kses( $str, $allowedposttags, $allowedprotocols );
		} elseif( is_array( $str ) ) {
			$arr = array();
			foreach( (array) $str as $key => $val ) {
				$arr[$key] = $this->_sanitize( $val );
			}
			$str = $arr;
		}
		
		return $str;
	}

	/**
	 * Hook into register_activation_hook action
	 * 
	 * Put code here that needs to happen when your plugin is first activated (database
	 * creation, permalink additions, etc.)
	 */
	static function activate() {
		// Do activation actions
	}
	
	/**
	 * Define the admin menu options for this plugin
	 * 
	 * @uses add_action()
	 * @uses add_options_page()
	 */
	function admin_menu() {
		$page_hook = add_options_page( $this->friendly_name, $this->friendly_name, 'administrator', $this->namespace, array( &$this, 'admin_options_page' ) );
		register_setting($this->namespace.'_options', $this->namespace);
		// Add print scripts and styles action based off the option page hook
		add_action( 'admin_print_scripts-' . $page_hook, array( &$this, 'admin_print_scripts' ) );
		add_action( 'admin_print_styles-' . $page_hook, array( &$this, 'admin_print_styles' ) );
	}
	
	
	/**
	 * The admin section options page rendering method
	 * 
	 * @uses current_user_can()
	 * @uses wp_die()
	 */
	function admin_options_page() {
		if( !current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have sufficient permissions to access this page' );
		}
		
		$page_title 	= $this->friendly_name . ' Options';
		$namespace 		= $this->namespace;
		$settings 		= $this->settings;
		
		include( constant(self::$const . '_PATH') . "/views/options.php" );
	}
	
	/**
	 * Load JavaScript for the admin options page
	 * 
	 * @uses wp_enqueue_script()
	 */
	function admin_print_scripts() {
		wp_enqueue_script( "{$this->namespace}-admin" );
	}
	
	/**
	 * Load Stylesheet for the admin options page
	 * 
	 * @uses wp_enqueue_style()
	 */
	function admin_print_styles() {
		wp_enqueue_style( "{$this->namespace}-admin" );
	}
	
	/**
	 * Hook into register_deactivation_hook action
	 * 
	 * Put code here that needs to happen when your plugin is deactivated
	 */
	static function deactivate() {
		// Do deactivation actions
	}
	
	/**
	 * Retrieve the stored plugin option or the default if no user specified value is defined
	 * 
	 * @param string $option_name The name of the TrialAccount option you wish to retrieve
	 * 
	 * @uses get_option()
	 * 
	 * @return mixed Returns the option value or false(boolean) if the option is not found
	 */
	function get_option( $option_name ) {
		// Load option values if they haven't been loaded already
		if( !isset( $this->options ) || empty( $this->options ) ) {
			$defaults = array();
	    	foreach($this->settings as $setting) {
	    		if ( $setting['default'] != false )
	    			$defaults[$setting['slug']] = $setting['default'];
	    	}
			$this->options =  wp_parse_args(get_option($this->namespace), $defaults);
		}
		
		if( isset( $this->options[$option_name] ) )
			return $this->options[$option_name];

		return false;
	}
	
	/**
	 * Initialization function to return the plugin singleton via function call,
	 * and set the whole thing up in the first place.
	 * 
	 */
	public static function instance($cfg = null) {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self($cfg);
				return;
			}
			return self::$instance;
	}
	
	/**
	 * Hook into plugin_action_links filter
	 * 
	 * Adds a "Settings" link next to the "Deactivate" link in the plugin listing page
	 * when the plugin is active.
	 * 
	 * @param object $links An array of the links to show, this will be the modified variable
	 * @param string $file The name of the file being processed in the filter
	 */
	function plugin_action_links( $links, $file ) {
		if( $file == plugin_basename( constant(self::$const . '_PATH') . '/' . basename( __FILE__ ) ) ) {
			$old_links = $links;
			$new_links = array(
				"settings" => '<a href="options-general.php?page=' . $this->namespace . '">' . __( 'Settings' ) . '</a>'
			);
			$links = array_merge( $new_links, $old_links );
		}
		
		return $links;
	}
	
	/**
	 * Register scripts used by this plugin for enqueuing elsewhere
	 * 
	 * @uses wp_register_script()
	 */
	function wp_register_scripts() {
		// Admin JavaScript
		wp_register_script( "{$this->namespace}-admin", constant(self::$const . '_URL') . "/js/admin.js", array( 'jquery' ), $this->version, true );
	}
	
	/**
	 * Register styles used by this plugin for enqueuing elsewhere
	 * 
	 * @uses wp_register_style()
	 */
	function wp_register_styles() {
		// Admin Stylesheet
		wp_register_style( "{$this->namespace}-admin", constant(self::$const . '_URL') . "/css/admin.css", array(), $this->version, 'screen' );
	}
}

//register_activation_hook( __FILE__, array( 'PluginTemplate', 'activate' ) );
//register_deactivation_hook( __FILE__, array( 'PluginTemplate', 'deactivate' ) );
