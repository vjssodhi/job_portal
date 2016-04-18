<?php
/**
 * Initialize Theme functions for NOO Framework.
 * This file include the framework functions, it should remain intact between themes.
 * For theme specified functions, see file functions-<theme name>.php
 *
 * @package    NOO Framework
 * @version    1.0.0
 * @author     Kan Nguyen <khanhnq@nootheme.com>
 * @copyright  Copyright (c) 2014, NooTheme
 * @license    http://opensource.org/licenses/gpl-2.0.php GPL v2 or later
 * @link       http://nootheme.com
 */

// Content Width
if ( ! isset( $content_width ) ) :
	$content_width = 1170;//noo_thumbnail_width();
endif;

// Initialize Theme
if (!function_exists('noo_init_theme')):
	function noo_init_theme() {
		load_theme_textdomain( 'noo', get_template_directory() . '/languages' );

		require_once( 'noo-check-version.php' );
 
        if ( is_admin() ) {     
            $license_manager = new Noo_Check_Version(
                'noo-jobmonster',
                'Noo JobMonster',
                'http://update.nootheme.com/api/license-manager/v1',
                'theme',
                '',
                false
            );
        }

		// Title Tag -- From WordPress 4.1.
		add_theme_support('title-tag');
		// @TODO: Automatic feed links.
		add_theme_support('automatic-feed-links');
		// Add support for some post formats.
		add_theme_support('post-formats', array(
			'image',
			'gallery',
			'video',
			'link',
			'quote',
			'audio'
		));
		add_theme_support( 'woocommerce' );
		// WordPress menus location.
		$menu_list = array();
		
		$menu_list['primary'] = __('Primary Menu', 'noo');

		// Register Menu
		register_nav_menus($menu_list);

		// Define image size
		add_theme_support('post-thumbnails');

		add_image_size('noo-thumbnail-square',600,450, true);
		add_image_size('company-logo', 160, 160, true);
		add_image_size('cover-image', 1400, 300, false);
		
	}
	add_action('after_setup_theme', 'noo_init_theme');
endif;

// Enqueue style for admin
if ( ! function_exists( 'noo_enqueue_admin_assets' ) ) :
	function noo_enqueue_admin_assets() {

		wp_register_style( 'noo-admin-css', NOO_FRAMEWORK_ADMIN_URI . '/assets/css/noo-admin.css', null, null, 'all' );
		wp_enqueue_style( 'noo-admin-css' );

		wp_register_style( 'vendor-font-awesome-css', NOO_FRAMEWORK_URI . '/vendor/fontawesome/css/font-awesome.min.css',array(),'4.1.0');
		wp_register_style( 'noo-icon-bootstrap-modal-css', NOO_FRAMEWORK_ADMIN_URI . '/assets/css/noo-icon-bootstrap-modal.css', null, null, 'all' );
		wp_register_style( 'noo-jquery-ui-slider', NOO_FRAMEWORK_ADMIN_URI . '/assets/css/noo-jquery-ui.slider.css', null, '1.10.4', 'all' );
		wp_register_style( 'vendor-chosen-css', NOO_FRAMEWORK_ADMIN_URI . '/assets/css/noo-chosen.css', null, null, 'all' );

		wp_register_style( 'vendor-alertify-core-css', NOO_FRAMEWORK_ADMIN_URI . '/assets/css/alertify.core.css', null, null, 'all' );
		wp_register_style( 'vendor-alertify-default-css', NOO_FRAMEWORK_ADMIN_URI . '/assets/css/alertify.default.css', array('vendor-alertify-core-css'), null, 'all' );
		
		wp_register_style( 'vendor-datetimepicker', NOO_FRAMEWORK_URI . '/vendor/datetimepicker/jquery.datetimepicker.css', '2.4.5' );
		wp_register_script( 'vendor-datetimepicker', NOO_FRAMEWORK_URI . '/vendor/datetimepicker/jquery.datetimepicker.js', array( 'jquery' ), '2.4.5', true );

		wp_localize_script( 'vendor-datetimepicker', 'datetime', array( 'lang' => substr(get_bloginfo ( 'language' ), 0, 2), 'rtl' => is_rtl() ) );
		
		// Main script
		wp_register_script( 'noo-admin-js', NOO_FRAMEWORK_ADMIN_URI . '/assets/js/noo-admin.js', array( 'jquery', 'jquery-ui-slider' ), null, true );
		wp_enqueue_script( 'noo-admin-js' );

		wp_register_script( 'noo-bootstrap-modal-js', NOO_FRAMEWORK_ADMIN_URI . '/assets/js/bootstrap-modal.js', array('jquery'), '2.3.2', true );
		wp_register_script( 'noo-bootstrap-tab-js',NOO_FRAMEWORK_ADMIN_URI . '/assets/js/bootstrap-tab.js',array('jquery'), '2.3.2', true);
		wp_register_script( 'noo-font-awesome-js', NOO_FRAMEWORK_ADMIN_URI . '/assets/js/font-awesome.js', array( 'noo-bootstrap-modal-js', 'noo-bootstrap-tab-js'), null, true );
		wp_register_script( 'vendor-chosen-js', NOO_FRAMEWORK_ADMIN_URI . '/assets/js/chosen.jquery.min.js', array( 'jquery'), null, true );
		wp_register_script( 'vendor-fileDownload-js', NOO_FRAMEWORK_ADMIN_URI . '/assets/js/jquery.fileDownload.js', array( 'jquery' ), null, true );
		wp_register_script( 'vendor-alertify-js', NOO_FRAMEWORK_ADMIN_URI . '/assets/js/alertify.mod.min.js', null, null, true );

	}
	add_action( 'admin_enqueue_scripts', 'noo_enqueue_admin_assets' );
endif;

// Deactivate libraries plugins
if ( ! function_exists( 'noo_deactivate_plugins' ) ) :
	function noo_deactivate_plugins() {
		if ( is_plugin_active( 'noo_jobmonster_libs/plugin.php' ) ) {
			deactivate_plugins( 'noo_jobmonster_libs/plugin.php' );
		}

		$indeed_plugin_path = defined( WP_PLUGIN_DIR ) ? WP_PLUGIN_DIR . '/noo-import-indeed/noo-import-indeed.php' : ABSPATH . 'wp-content/plugins/noo-import-indeed/noo-import-indeed.php';
		if( file_exists( $indeed_plugin_path ) ){
			$plugin_data = get_plugin_data( $indeed_plugin_path );
			if( version_compare($plugin_data['Version'], '1.1.0', '<' ) ) {
				// deactivate_plugins( 'noo-import-indeed/noo-import-indeed.php' );
				add_action('admin_notices', 'noo_import_indeed_plugin_check_notice' );
			}
		}
	}
	add_action( 'admin_init', 'noo_deactivate_plugins' );
endif;

// Notice about updating Indeed plugins
if ( ! function_exists( 'noo_import_indeed_plugin_check_notice' ) ) :
	function noo_import_indeed_plugin_check_notice() {
		$screen = get_current_screen();

		if ( $screen->id != 'plugins' ) {
			return;
		}
	    ?>
		    <div class="update-nag">
		    	<p><strong><?php  _e('An old version of Noo Indeed Integration plugin was detected, please <em>remove</em> it and reinstall the newer version included in this theme.', 'noo'); ?></strong></p>
		    </div>
	    <?php
	}
endif;