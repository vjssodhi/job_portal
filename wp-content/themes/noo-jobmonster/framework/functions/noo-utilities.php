<?php
/**
 * Utilities Functions for NOO Framework.
 * This file contains various functions for getting and preparing data.
 *
 * @package    NOO Framework
 * @version    1.0.0
 * @author     Kan Nguyen <khanhnq@nootheme.com>
 * @copyright  Copyright (c) 2014, NooTheme
 * @license    http://opensource.org/licenses/gpl-2.0.php GPL v2 or later
 * @link       http://nootheme.com
 */

if(!function_exists('noo_get_endpoint_url')){
	function noo_get_endpoint_url( $endpoint, $value = '', $permalink = '' ) {
		if ( ! $permalink )
			$permalink = get_permalink();
	
		
		if ( get_option( 'permalink_structure' ) ) {
			if ( strstr( $permalink, '?' ) ) {
				$query_string = '?' . parse_url( $permalink, PHP_URL_QUERY );
				$permalink    = current( explode( '?', $permalink ) );
			} else {
				$query_string = '';
			}
			$url = trailingslashit( $permalink ) . $endpoint . '/' . $value . $query_string;
		} else {
			$url = esc_url_raw(add_query_arg( $endpoint, $value, $permalink ));
		}
	
		return apply_filters( 'noo_get_endpoint_url', $url, $endpoint );
	}
}

if (!function_exists('smk_get_all_sidebars')):
	function smk_get_all_sidebars() {
		global $wp_registered_sidebars;
		$sidebars = array();
		$none_sidebars = array();
		for ($i = 1;$i <= 4;$i++) {
			$none_sidebars[] = "noo-top-{$i}";
			$none_sidebars[] = "noo-footer-{$i}";
		}
		if ($wp_registered_sidebars && !is_wp_error($wp_registered_sidebars)) {
			
			foreach ($wp_registered_sidebars as $sidebar) {
				// Don't include Top Bar & Footer Widget Area
				if (in_array($sidebar['id'], $none_sidebars)) continue;
				
				$sidebars[$sidebar['id']] = $sidebar['name'];
			}
		}
		return $sidebars;
	}
endif;

if (!function_exists('get_sidebar_name')):
	function get_sidebar_name($id = '') {
		if (empty($id)) return '';
		
		global $wp_registered_sidebars;
		if ($wp_registered_sidebars && !is_wp_error($wp_registered_sidebars)) {
			foreach ($wp_registered_sidebars as $sidebar) {
				if ($sidebar['id'] == $id) return $sidebar['name'];
			}
		}
		
		return '';
	}
endif;

if (!function_exists('get_sidebar_id')):
	function get_sidebar_id() {
		// Normal Page or Static Front Page
		if ( is_page() || (is_front_page() && get_option('show_on_front') == 'page') ) {
			// Get the sidebar setting from
			$sidebar = noo_get_post_meta(get_the_ID(), '_noo_wp_page_sidebar', 'sidebar-main');
			
			return $sidebar;
		}

		// NOO Resume
		if( is_post_type_archive( 'noo_resume' ) ) {
			$resume_layout = noo_get_option('noo_resumes_layout', 'sidebar');
			if( $resume_layout != 'fullwidth' ) {
				return noo_get_option('noo_resume_list_sidebar', 'sidebar-resume');
			}

			return '';
		}
		if( is_singular( 'noo_resume' ) ) {
			return '';
		}
		
		// NOO Company
		if( is_post_type_archive( 'noo_company' ) || is_singular( 'noo_company' ) ) {
			return '';
		}
		
		// NOO Job
		if( is_post_type_archive( 'noo_job' )
			|| is_tax( 'job_category' )
			|| is_tax( 'job_type' )
			|| is_tax( 'job_location' ) ) {

			$jobs_layout = noo_get_option('noo_jobs_layout', 'sidebar');
			if( $jobs_layout != 'fullwidth' ) {
				return noo_get_option('noo_jobs_sidebar', 'sidebar-job');
			}

			return '';
		}
		
		// Single Job
		if( is_singular( 'noo_job' ) ) {
			return '';
		}

		// NOO Portfolio
		if( is_post_type_archive( 'portfolio_project' )
			|| is_tax( 'portfolio_category' )
			|| is_tax( 'portfolio_tag' )
			|| is_singular( 'portfolio_project' ) ) {

			$portfolio_layout = noo_get_option('noo_portfolio_layout', 'fullwidth');
			if ($portfolio_layout != 'fullwidth') {
				return noo_get_option('noo_portfolio_sidebar', '');
			}

			return '';
		}

		// WooCommerce Product
		if( NOO_WOOCOMMERCE_EXIST ) {
			if( is_product() ) {
				$product_layout = noo_get_option('noo_woocommerce_product_layout', 'same_as_shop');
				$sidebar = '';
				if ( $product_layout == 'same_as_shop' ) {
					$product_layout = noo_get_option('noo_shop_layout', 'fullwidth');
					$sidebar = noo_get_option('noo_shop_sidebar', '');
				} else {
					$sidebar = noo_get_option('noo_woocommerce_product_sidebar', '');
				}
				
				if ( $product_layout == 'fullwidth' ) {
					return '';
				}
				
				return $sidebar;
			}

			// Shop, Product Category, Product Tag, Cart, Checkout page
			if( is_shop() || is_product_category() || is_product_tag() ) {
				$shop_layout = noo_get_option('noo_shop_layout', 'fullwidth');
				if($shop_layout != 'fullwidth'){
					return noo_get_option('noo_shop_sidebar', '');
				}

				return '';
			}
		}
		
		// Single post page
		if (is_single()) {
			// Check if there's overrode setting in this post.
			$post_id = get_the_ID();
			$override_setting = noo_get_post_meta($post_id, '_noo_wp_post_override_layout', false);
			if ($override_setting) {
				// overrode
				$overrode_layout = noo_get_post_meta($post_id, '_noo_wp_post_layout', 'fullwidth');
				if ($overrode_layout != 'fullwidth') {
					return noo_get_post_meta($post_id, '_noo_wp_post_sidebar', 'sidebar-main');
				}
			} else{

				$post_layout = noo_get_option('noo_blog_post_layout', 'same_as_blog');
				$sidebar = '';
				if ($post_layout == 'same_as_blog') {
					$post_layout = noo_get_option('noo_blog_layout', 'sidebar');
					$sidebar = noo_get_option('noo_blog_sidebar', 'sidebar-main');
				} else {
					$sidebar = noo_get_option('noo_blog_post_sidebar', 'sidebar-main');
				}
				
				if($post_layout == 'fullwidth'){
					return '';
				}
				
				return $sidebar;
			}

			return '';
		}

		// Archive page
		if( is_archive() ) {
			$archive_layout = noo_get_option('noo_blog_archive_layout', 'same_as_blog');
			$sidebar = '';
			if ($archive_layout == 'same_as_blog') {
				$archive_layout = noo_get_option('noo_blog_layout', 'sidebar');
				$sidebar = noo_get_option('noo_blog_sidebar', 'sidebar-main');
			} else {
				$sidebar = noo_get_option('noo_blog_archive_sidebar', 'sidebar-main');
			}
			
			if($archive_layout == 'fullwidth'){
				return '';
			}
			
			return $sidebar;
		}

		// Archive, Index or Home
		if (is_home() || is_archive() || (is_front_page() && get_option('show_on_front') == 'posts')) {
			
			$blog_layout = noo_get_option('noo_blog_layout', 'sidebar');
			if ($blog_layout != 'fullwidth') {
				return noo_get_option('noo_blog_sidebar', 'sidebar-main');
			}
			
			return '';
		}
		
		return '';
	}
endif;

if ( !function_exists('noo_default_primary_color') ) :
	function noo_default_primary_color() {
		return '#e6b706';
	}
endif;
if ( !function_exists('noo_default_font_family') ) :
	function noo_default_font_family() {
		return 'Droid Serif';
	}
endif;
if ( !function_exists('noo_default_text_color') ) :
	function noo_default_text_color() {
		return '#44494b';
	}
endif;
if ( !function_exists('noo_default_headings_font_family') ) {
	function noo_default_headings_font_family() {
		return 'Montserrat';
	}
}
if ( !function_exists('noo_default_headings_color') ) {
	function noo_default_headings_color() {
		return noo_default_text_color();
	}
}
if ( !function_exists('noo_default_header_bg') ) {
	function noo_default_header_bg() {
		if( noo_get_option( 'noo_site_skin', 'light' ) == 'dark' ) {
			return '#000000';
		}

		return '#FFFFFF';
	}
}
if ( !function_exists('noo_default_nav_font_family') ) {
	function noo_default_nav_font_family() {
		return noo_default_headings_font_family();
	}
}
if ( !function_exists('noo_default_logo_font_family') ) {
	function noo_default_logo_font_family() {
		return noo_default_headings_font_family();
	}
}
if ( !function_exists('noo_default_logo_color') ) {
	function noo_default_logo_color() {
		return noo_default_headings_color();
	}
}
if ( !function_exists('noo_default_font_size') ) {
	function noo_default_font_size() {
		return '14';
	}
}
if ( !function_exists('noo_default_font_weight') ) {
	function noo_default_font_weight() {
		return '400';
	}
}

//
// This function help to create the dynamic thumbnail width,
// but we don't use it at the moment.
// 
if (!function_exists('noo_thumbnail_width')) :
	function noo_thumbnail_width() {
		$site_layout	= noo_get_option('noo_site_layout', 'fullwidth');
		$page_layout	= get_page_layout();
		$width			= 1200; // max width

		if($site_layout == 'boxed') {
			$site_width = (int) noo_get_option('noo_layout_site_width', '90');
			$site_max_width = (int) noo_get_option('noo_layout_site_max_width', '1200');
			$width = min($width * $site_width / 100, $site_max_width);
		}

		if($page_layout != 'fullwidth') {
			$width = $width * 75 / 100; // 75% of col-9
		}

		return $width;
	}
endif;

if (!function_exists('get_thumbnail_width')) :
	function get_thumbnail_width() {

		// if( is_admin()) {
		// 	return 'admin-thumb';
		// }

		// NOO Portfolio
		if( is_post_type_archive( 'portfolio_project' ) ) {
			// if it's portfolio page, check if the masonry size is fixed or original
			if(noo_get_option('noo_portfolio_masonry_item_size', 'original' ) == 'fixed') {
				$masonry_size = noo_get_post_meta($post_id, '_noo_portfolio_image_masonry_size', 'regular');
				return "masonry-fixed-{$masonry_size}";
			}
		}

		$site_layout	= noo_get_option('noo_site_layout', 'fullwidth');
		$page_layout	= get_page_layout();

		if($site_layout == 'boxed') {
			if($page_layout == 'fullwidth') {
				return 'boxed-fullwidth';
			} else {
				return 'boxed-sidebar';
			}
		} else {
			if($page_layout == 'fullwidth') {
				return 'fullwidth-fullwidth';
			} else {
				return 'fullwidth-sidebar';
			}
		}

		return 'fullwidth-fullwidth';
	}
endif;

if (!function_exists('get_page_layout')):
	function get_page_layout() {
		
		// Normal Page or Static Front Page
		if (is_page() || (is_front_page() && get_option('show_on_front') == 'page')) {
			// WP page,
			// get the page template setting
			$page_id = get_the_ID();
			$page_template = noo_get_post_meta($page_id, '_wp_page_template', 'default');
			
			if (strpos($page_template, 'sidebar') !== false) {
				if (strpos($page_template, 'left') !== false) {
					return 'left_sidebar';
				}
				
				return 'sidebar';
			}
			
			return 'fullwidth';
		}
		
		// NOO Resume
		if( is_post_type_archive( 'noo_resume' ) ) {
			return noo_get_option('noo_resumes_layout', 'sidebar');
		}
		if( is_singular( 'noo_resume' ) ) {
			return 'fullwidth';
		}
		
		// NOO Company
		if( is_post_type_archive( 'noo_company' ) || is_singular( 'noo_company' ) ) {
			return 'fullwidth';
		}
		
		// NOO Job
		if( is_post_type_archive( 'noo_job' )
			|| is_tax( 'job_category' )
			|| is_tax( 'job_type' )
			|| is_tax( 'job_location' ) ) {

			return noo_get_option('noo_jobs_layout', 'sidebar');
		}
		
		// Single Job
		if( is_singular( 'noo_job' ) ) {
			return noo_get_option('noo_single_jobs_layout', 'right_company');
		}
		
		// NOO Portfolio
		if( is_post_type_archive( 'portfolio_project' )
			|| is_tax( 'portfolio_category' )
			|| is_tax( 'portfolio_tag' )
			|| is_singular( 'portfolio_project' ) ) {

			return noo_get_option('noo_portfolio_layout', 'fullwidth');
		}

		// WooCommerce
		if( NOO_WOOCOMMERCE_EXIST ) {
			if( is_shop() || is_product_category() || is_product_tag() ){
				return noo_get_option('noo_shop_layout', 'fullwidth');
			}

			if( is_product() ) {
				$product_layout = noo_get_option('noo_woocommerce_product_layout', 'same_as_shop');
				if ($product_layout == 'same_as_shop') {
					$product_layout = noo_get_option('noo_shop_layout', 'fullwidth');
				}
				
				return $product_layout;
			}
		}
		
		// Single post page
		if (is_single()) {

			// WP post,
			// check if there's overrode setting in this post.
			$post_id = get_the_ID();
			$override_setting = noo_get_post_meta($post_id, '_noo_wp_post_override_layout', false);
			
			if ( !$override_setting ) {
				$post_layout = noo_get_option('noo_blog_post_layout', 'same_as_blog');
				if ($post_layout == 'same_as_blog') {
					$post_layout = noo_get_option('noo_blog_layout', 'sidebar');
				}
				
				return $post_layout;
			}

			// overrode
			return noo_get_post_meta($post_id, '_noo_wp_post_layout', 'sidebar-main');
		}

		// Archive
		if (is_archive()) {
			$archive_layout = noo_get_option('noo_blog_archive_layout', 'same_as_blog');
			if ($archive_layout == 'same_as_blog') {
				$archive_layout = noo_get_option('noo_blog_layout', 'sidebar');
			}
			
			return $archive_layout;
		}

		// Index or Home
		if (is_home() || (is_front_page() && get_option('show_on_front') == 'posts')) {
			
			return noo_get_option('noo_blog_layout', 'sidebar');
		}
		
		return '';
	}
endif;

if(!function_exists('is_fullwidth')){
	function is_fullwidth(){
		return get_page_layout() == 'fullwidth';
	}
}

if (!function_exists('is_one_page_enabled')):
	function is_one_page_enabled() {
		if( (is_front_page() && get_option('show_on_front' == 'page')) || is_page()) {
			$page_id = get_the_ID();
			return ( noo_get_post_meta( $page_id, '_noo_wp_page_enable_one_page', false ) );
		}

		return false;
	}
endif;

if (!function_exists('get_one_page_menu')):
	function get_one_page_menu() {
		if( is_one_page_enabled() ) {
			if( (is_front_page() && get_option('show_on_front' == 'page')) || is_page()) {
				$page_id = get_the_ID();
				return noo_get_post_meta( $page_id, '_noo_wp_page_one_page_menu', '' );
			}
		}

		return '';
	}
endif;

if (!function_exists('has_home_slider')):
	function has_home_slider() {
		if (class_exists( 'RevSlider' )) {
			if( (is_front_page() && get_option('show_on_front' == 'page')) || is_page()) {
				$page_id = get_the_ID();
				return ( noo_get_post_meta( $page_id, '_noo_wp_page_enable_home_slider', false ) )
					&& ( noo_get_post_meta( $page_id, '_noo_wp_page_slider_rev', '' ) != '' );
			}
		}

		return false;
	}
endif;

if (!function_exists('home_slider_position')):
	function home_slider_position() {
		if (has_home_slider()) {
			return noo_get_post_meta( get_the_ID(), '_noo_wp_page_slider_position', 'below' );
		}

		return '';
	}
endif;

if (!function_exists('is_masonry_style')):
	function is_masonry_style() {
		if( is_post_type_archive( 'portfolio_project' ) || is_tax('portfolio_category') || is_tax('portfolio_tag')  ) {
			return true;
		}

		if(is_home()) {
			return (noo_get_option( 'noo_blog_style' ) == 'masonry');
		}
		
		if(is_archive()) {
			$archive_style = noo_get_option( 'noo_blog_archive_style', 'same_as_blog' );
			if ($archive_style == 'same_as_blog') {
				return (noo_get_option( 'noo_blog_style', 'standard' ) == 'masonry');
			} else {
				return ($archive_style == 'masonry');
			}
		}

		return false;
	}
endif;

if (!function_exists('get_page_heading')):
	function get_page_heading() {
		$heading = '';
		$sub_heading = '';
		if ( is_home() ) {
			$heading = noo_get_option('noo_blog_heading_title', __( 'Blog', 'noo' ) );
		} elseif ( is_search() ) {
			$heading = __( 'Search Results', 'noo' );
			global $wp_query;
			$search_query = get_search_query();
			$search_query = (isset($_GET['s']) && empty($search_query) ? $_GET['s'] : $search_query);
			// if(!empty($wp_query->found_posts) ) {
			// 	if( !empty($search_query ) ) {
			// 		if($wp_query->found_posts > 1) {
			// 			$heading =  $wp_query->found_posts ." ". __('Search Results for:','noo')." ".esc_attr( $search_query );
			// 		} else {
			// 			$heading =  $wp_query->found_posts ." ". __('Search Results for:','noo')." ".esc_attr( $search_query );
			// 		}
			// 	}
			// } else {
				if(!empty($search_query)) {
					$heading = __('Search Results for:','noo')." ".esc_attr( $search_query );
				}
			// }
		} elseif ( is_post_type_archive( 'noo_job' ) ) {
			$heading = noo_get_option('noo_job_heading_title', __( 'Jobs', 'noo' ) );
		} elseif ( is_post_type_archive( 'noo_company' ) ) {
			$heading = __( 'Companies', 'noo' );
		} elseif ( is_post_type_archive( 'noo_resume' ) ) {
			$heading = noo_get_option('noo_resume_heading_title', __( 'Resume Listing', 'noo' ) );
		} elseif ( NOO_WOOCOMMERCE_EXIST && is_shop() ) {
			$heading = noo_get_option( 'noo_shop_heading_title', __( 'Shop', 'noo' ) );
		} elseif ( is_author() ) {
			$curauth = (get_query_var('author_name')) ? get_user_by('slug', get_query_var('author_name')) : get_userdata(get_query_var('author'));
			$heading = __('Author Archive','noo');

			if(isset($curauth->nickname)) $heading .= ' ' . __('for:','noo')." ".$curauth->nickname;
		}elseif ( is_year() ) {
    		$heading = __( 'Post Archive by Year: ', 'noo' ) . get_the_date( 'Y' );
		} elseif ( is_month() ) {
    		$heading = __( 'Post Archive by Month: ', 'noo' ) . get_the_date( 'F,Y' );
		} elseif ( is_day() ) {
    		$heading = __( 'Post Archive by Day: ', 'noo' ) . get_the_date( 'F j, Y' );
		} elseif ( is_404() ) {
    		$heading = __( 'Oops! We could not find anything to show to you.', 'noo' );
    		$sub_heading =  __( 'Would you like going else where to find your stuff.', 'noo' );
		} elseif ( is_archive() ) {
			$heading        = single_cat_title( '', false );
			$sub_heading   = term_description();
		} elseif( is_page() ) {
			$page_temp = get_page_template_slug();
			if( noo_get_post_meta(get_the_ID(), '_noo_wp_page_hide_page_title', false) ) {
				$heading = '';
			} elseif(get_the_ID() == Noo_Member::get_member_page_id()){
				$heading = get_the_title();
				$current_user = wp_get_current_user();
				if( 'username' == Noo_Member::get_setting('member_title', 'page_title') && 0 != $current_user->ID ) {
					$heading = Noo_Member::get_display_name( $current_user->ID );
				}
				$sub_heading = Noo_Member::get_member_heading_label();
				if(empty($sub_heading) && !is_user_logged_in()){
					$sub_heading = Noo_Member::can_register() ? __('Login or create an account','noo') : __('Login', 'noo');
				}
			}elseif('page-post-job.php' === $page_temp){
				$heading = __('Post a Job','noo');
				$step = isset($_GET['action']) ? $_GET['action'] : '';
				if($step == 'login'){
					$sub_heading = Noo_Member::can_register() ? __('Login or create an account','noo') : __('Login', 'noo');
				}elseif ($step == 'job_package'){
					$sub_heading = __('Choose a package','noo');
				}elseif ($step == 'post_job'){
					$sub_heading = __('Describe your company and vacancy','noo');
				}elseif ($step == 'preview_job'){
					$sub_heading = __('Preview and submit your job','noo');
				}else{
					$sub_heading = Noo_Member::can_register() ? __('Login or create an account','noo') : __('Login', 'noo');
				}
			} elseif('page-post-resume.php' === $page_temp){
				$heading = __('Post a Resume','noo');
				$step = isset($_GET['action']) ? $_GET['action'] : '';
				if($step == 'login'){
					$sub_heading = Noo_Member::can_register() ? __('Login or create an account','noo') : __('Login', 'noo');
				}elseif ($step == 'resume_general'){
					$sub_heading = __('General Information','noo');
				}elseif ($step == 'resume_detail'){
					$sub_heading = __('Resume Detail','noo');
				}elseif ($step == 'resume_preview'){
					$sub_heading = __('Preview and Finish','noo');
				}else{
					$sub_heading = Noo_Member::can_register() ? __('Login or create an account','noo') : __('Login', 'noo');
				}
			} else {
				$heading = get_the_title();
			}
		} elseif ( is_singular() ) {
			$heading = get_the_title();
		}

		return array($heading, $sub_heading);
	}
endif;

if (!function_exists('get_page_heading_image')):
	function get_page_heading_image() {
		$image = '';
		// if( ! noo_get_option( 'noo_page_heading', true ) ) {
		// 	return $image;
		// }
		if( NOO_WOOCOMMERCE_EXIST && is_shop() ) {
			$image = noo_get_image_option( 'noo_shop_heading_image', '' );
		} elseif ( is_home() ) {
			$image = noo_get_image_option( 'noo_blog_heading_image', '' );
		} elseif( is_category() || is_tag() ) {
			// $queried_object = get_queried_object();
			// $image			= noo_get_term_meta( $queried_object->term_id, 'heading_image', '' );
			// $image			= empty( $image ) ? noo_get_image_option( 'noo_blog_heading_image', '' ) : $image;
		} elseif( NOO_WOOCOMMERCE_EXIST && ( is_product_category() || is_product_tag() ) ) {
			// $queried_object = get_queried_object();
			// $image			= noo_get_term_meta( $queried_object->term_id, 'heading_image', '' );
			// $image			= empty( $image ) ? noo_get_image_option( 'noo_shop_heading_image', '' ) : $image;
		} elseif ( is_singular('noo_job' ) ) {
			if( Noo_Job::get_setting('noo_job_general', 'cover_image','yes') == 'yes' ) {
				$image = noo_get_post_meta(get_the_ID(), '_cover_image', '');
			}
			$image = noo_get_post_meta(get_the_ID(), '_cover_image', '');
			if ( empty($image) && Noo_Company::get_setting('cover_image', 'yes') == 'yes' ) {
				$company_id = Noo_Job::get_employer_company(get_post_field( 'post_author', get_the_ID() ));
				$image = noo_get_post_meta($company_id, '_cover_image', '');
			}
			if ( empty($image) ) {
				$image = noo_get_image_option( 'noo_job_heading_image', '' );
			}
		} elseif ( is_singular('noo_company' ) ) {
			if( Noo_Company::get_setting('cover_image', 'yes') == 'yes' ) {
				$image = noo_get_post_meta(get_the_ID(), '_cover_image', '');
			}
		} elseif ( is_singular('product' ) || is_page() ) {
			$image = noo_get_post_meta(get_the_ID(), '_heading_image', '');
		} elseif (is_singular ( 'post' )) {
			$image = noo_get_image_option( 'noo_blog_heading_image', '' );
		} elseif( is_tax('class_category') ) {
			$image = noo_get_image_option( 'noo_class_heading_image', '' );
		} elseif( is_post_type_archive('noo_job') || is_tax('job_location') || is_tax('job_category') ) {
			$image = noo_get_image_option( 'noo_job_heading_image', '' );
		} elseif( is_post_type_archive('noo_resume') || is_singular( 'noo_resume') ) {
			$image = noo_get_image_option( 'noo_resume_heading_image', '' );
		}
		if (is_numeric( $image )) {
			if( !empty( $image ) ) {
				$image = wp_get_attachment_image_src( $image, 'cover-image' );
				return $image[0];
			}
		}

		if( empty($image) ) {
			$image = NOO_ASSETS_URI . '/images/heading-bg.png';
		}
		return $image;
	}
endif;

if (!function_exists('noo_get_post_format')):
	function noo_get_post_format($post_id = null, $post_type = '') {
		$post_id = (null === $post_id) ? get_the_ID() : $post_id;
		$post_type = ('' === $post_type) ? get_post_type($post_id) : $post_type;

		$post_format = '';
		
		if ($post_type == 'post') {
			$post_format = get_post_format($post_id);
		}
		
		if ($post_type == 'portfolio_project') {
			$post_format = noo_get_post_meta($post_id, '_noo_portfolio_media_type', 'image');
		}

		return $post_format;
	}
endif;

if (!function_exists('has_featured_content')):
	function has_featured_content($post_id = null) {
		$post_id = (null === $post_id) ? get_the_ID() : $post_id;

		$post_type = get_post_type($post_id);
		$prefix = '';
		$post_format = '';
		
		if ($post_type == 'post') {
			$prefix = '_noo_wp_post';
			$post_format = get_post_format($post_id);
		}
		
		if ($post_type == 'portfolio_project') {
			$prefix = '_noo_portfolio';
			$post_format = noo_get_post_meta($post_id, "{$prefix}_media_type", 'image');
		}
		
		switch ($post_format) {
			case 'image':
				$main_image = noo_get_post_meta($post_id, "{$prefix}_main_image", 'featured');
				if( $main_image == 'featured') {
					return has_post_thumbnail($post_id);
				}

				return has_post_thumbnail($post_id) || ( (bool)noo_get_post_meta($post_id, "{$prefix}_image", '') );
			case 'gallery':
				if (!is_singular()) {
					$preview_content = noo_get_post_meta($post_id, "{$prefix}_gallery_preview", 'slideshow');
					if ($preview_content == 'featured') {
						return has_post_thumbnail($post_id);
					}
				}
				
				return (bool)noo_get_post_meta($post_id, "{$prefix}_gallery", '');
			case 'video':
				if (!is_singular()) {
					$preview_content = noo_get_post_meta($post_id, "{$prefix}_preview_video", 'both');
					if ($preview_content == 'featured') {
						return has_post_thumbnail($post_id);
					}
				}
				
				$m4v_video = (bool)noo_get_post_meta($post_id, "{$prefix}_video_m4v", '');
				$ogv_video = (bool)noo_get_post_meta($post_id, "{$prefix}_video_ogv", '');
				$embed_video = (bool)noo_get_post_meta($post_id, "{$prefix}_video_embed", '');
				
				return $m4v_video || $ogv_video || $embed_video;
			case 'link':
			case 'quote':
				return false;
				
			case 'audio':
				$mp3_audio = (bool)noo_get_post_meta($post_id, "{$prefix}_audio_mp3", '');
				$oga_audio = (bool)noo_get_post_meta($post_id, "{$prefix}_audio_oga", '');
				$embed_audio = (bool)noo_get_post_meta($post_id, "{$prefix}_audio_embed", '');
				return $mp3_audio || $oga_audio || $embed_audio;
			default: // standard post format
				return has_post_thumbnail($post_id);
		}
		
		return false;
	}
endif;

if (!function_exists('noo_get_page_id_by_template')):
	function noo_get_page_id_by_template( $page_template = '' ) {
		$pages = get_pages(array(
			'meta_key' => '_wp_page_template',
			'meta_value' => $page_template
		));

		if( $pages ){
			return $pages[0]->ID;
		}
		return false;
	}
endif;

if (!function_exists('noo_get_page_link_by_template')):
	function noo_get_page_link_by_template( $page_template ) {
		$pages = get_pages(array(
			'meta_key' => '_wp_page_template',
			'meta_value' => $page_template
		));

		if( $pages ){
			$link = get_permalink( $pages[0]->ID );
		}else{
			$link = home_url();
		}
		return $link;
	}
endif;

if (!function_exists('noo_current_url')):
	function noo_current_url($encoded = false) {
		global $wp;
		$current_url = esc_url( add_query_arg( $wp->query_string, '', home_url( $wp->request ) ) );
		if( $encoded ) {
			return urlencode($current_url);
		}
		return $current_url;
	}
endif;

if (!function_exists('noo_upload_dir_name')):
	function noo_upload_dir_name() {
		return 'noo_jobmonster';
	}
endif;

if (!function_exists('noo_upload_dir')):
	function noo_upload_dir() {
		$upload_dir = wp_upload_dir();

		return $upload_dir['basedir'] . '/' . noo_upload_dir_name();
	}
endif;

if (!function_exists('noo_upload_url')):
	function noo_upload_url() {
		$upload_dir = wp_upload_dir();

		return $upload_dir['baseurl'] . '/' . noo_upload_dir_name();
	}
endif;

if (!function_exists('noo_create_upload_dir')):
	function noo_create_upload_dir( $wp_filesystem = null ) {
		if( empty( $wp_filesystem ) ) {
			return false;
		}

		$upload_dir = wp_upload_dir();
		global $wp_filesystem;

		$noo_upload_dir = $wp_filesystem->find_folder( $upload_dir['basedir'] ) . noo_upload_dir_name();
		if ( ! $wp_filesystem->is_dir( $noo_upload_dir ) ) {
			if ( $wp_filesystem->mkdir( $noo_upload_dir, 0777 ) ) {
				return $noo_upload_dir;
			}

			return false;
		}

		return $noo_upload_dir;
	}
endif;

/**
 * This function is original from Visual Composer. Redeclare it here so that it could be used for site without VC.
 */
if ( !function_exists('noo_handler_shortcode_content') ):
	function noo_handler_shortcode_content( $content, $autop = false ) {
		if ( $autop ) {
			$content = wpautop( preg_replace( '/<\/?p\>/', "\n", $content ) . "\n" );
		}
		return do_shortcode( shortcode_unautop( $content) );
	}
endif;

if ( ! function_exists( '_wp_render_title_tag' ) ) {
	function noo_theme_slug_render_title() {
?>
<title><?php wp_title( '|', true, 'right' ); ?></title>
<?php
	}
	add_action( 'wp_head', 'noo_theme_slug_render_title' );
}

if (!function_exists('noo_mail')) :
	function noo_mail( $to = '', $subject = '', $body = '', $headers = '', $key = '', $attachments = '' ) {

		if( empty( $headers ) ) {
			$headers = array();
			$from_name = Noo_Job::get_setting( 'noo_email', 'from_name', '' );
			$from_email = Noo_Job::get_setting( 'noo_email', 'from_email', '' );

			// if( empty( $from_name ) ) {
			// 	if ( is_multisite() )
			// 		$from_name = $GLOBALS['current_site']->site_name;
			// 	else
			// 		$from_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
			// }

			if( !empty( $from_name ) || !empty( $from_email ) ) {
				$headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
			}
		}

		$headers   = apply_filters( $key . '_header', apply_filters( 'noo_mail_header', $headers ) );

		if( !empty( $key ) ) {
			if( Noo_Job::get_setting( 'noo_email', $key ) === 'disable' ) {
				return false;
			}

			$subject = apply_filters( $key . '_subject', apply_filters( 'noo_mail_subject', $subject ) );
			$body = apply_filters( $key . '_body', apply_filters( 'noo_mail_body', $body ) );
		}
		
		add_filter( 'wp_mail_content_type', 'noo_mail_set_html_content' );

		$result = wp_mail( $to, $subject, $body, $headers, $attachments );

		// Reset content-type to avoid conflicts -- http://core.trac.wordpress.org/ticket/23578
		remove_filter( 'wp_mail_content_type', 'noo_mail_set_html_content' );

		return $result;
	}
endif;

if (!function_exists('noo_mail_set_html_content')) :
	function noo_mail_set_html_content() {
		return 'text/html';
	}
endif;

if (!function_exists('noo_mail_do_not_reply')) :
	function noo_mail_do_not_reply(){
		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) === 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}
		return apply_filters( 'noo_mail_do_not_reply', 'noreply@' . $sitename );
	}
endif;

/* -------------------------------------------------------
 * Create functions noo_set_post_views
 * ------------------------------------------------------- */

if ( ! function_exists( 'noo_set_post_views' ) ) :
	
	function noo_set_post_views( $id ) {

		$key_meta = '_noo_views_count';
		// echo($id); die;
		$count = noo_get_post_meta( $id, $key_meta );
		// echo $count; die;
		if ( $count == '' ) :
			$count = 1;
		else :
			$count ++;
		endif;
		update_post_meta( $id, $key_meta, $count );
		// return $content;

	}

	// add_action( 'the_content', 'noo_set_post_views' );

endif;

/** ====== END noo_set_post_views ====== **/

/* -------------------------------------------------------
 * Create functions noo_get_post_views
 * ------------------------------------------------------- */

if ( ! function_exists( 'noo_get_post_views' ) ) :
	
	function noo_get_post_views( $id ) {
		// echo($id); die;
		$key_meta = '_noo_views_count';
		$count = noo_get_post_meta( $id, $key_meta );
		if ( $count == '' ) :
			delete_post_meta( $id, $key_meta );
	        add_post_meta( $id, $key_meta, '0' );
	        return 0;
		endif;
		return $count;
	}

endif;

/** ====== END noo_get_post_views ====== **/

/* -------------------------------------------------------
 * Create functions track_post_views
 * ------------------------------------------------------- */

if ( ! function_exists( 'noo_track_post_views' ) ) :
	
	function noo_track_post_views( $post_id = '' ) {
		
		if ( !is_single() ) return;

    	if ( empty ( $post_id) ) {
	        global $post;
	        $post_id = $post->ID;
	    }
	    if( get_post_status( $post_id ) !== 'publish' ) {
	    	return;
	    }

	    if ( is_singular( 'noo_job' ) ) $name_cookie = 'noo_jobs_' . $post_id;
	    if ( is_singular( 'noo_resume' ) ) $name_cookie = 'noo_resume_' . $post_id;
	    if ( is_singular( 'noo_company' ) ) $name_cookie = 'noo_company_' . $post_id;
	    if ( isset( $name_cookie ) ) {
		    if ( !isset ( $_COOKIE[$name_cookie] ) ) {
		    	noo_set_post_views($post_id);
		    }
		    setcookie( $name_cookie, $post_id, time() + (86400 * 3), "/");
		}
	}

	add_action( 'wp_head', 'noo_track_post_views');

endif;

/** ====== END track_post_views ====== **/

/* -------------------------------------------------------
 * Create functions noo_caroufredsel_slider
 * ------------------------------------------------------- */

if ( ! function_exists( 'noo_caroufredsel_slider' ) ) :
	
	// function noo_caroufredsel_slider( $r, $id, $show = 'company', $max = 6 ) {
	function noo_caroufredsel_slider( $r, $options = array() ) {
		// Default config options.
			$defaults = array(
				'id'                => uniqid() . '_show_slider',
				'show'              => 'company',
				'style'             => 1,
				'min'               => 1,
				'max'               => 6,
				'autoplay'          => 'false',
				'width'             => 180,
				'height'            => 'variable',
				'hidden_pagination' => 'false'
			);

			$options = wp_parse_args( $options, $defaults );

			if( $options['show'] == 'testimonial' ) {
				$options['width'] = 767;
			}
		// -- Check query
		if( $r->have_posts() ):
			wp_enqueue_script( 'vendor-carouFredSel' );
			echo '
			<div class="featured_slider">
				<div id="slider_' . $options['id'] . '">';
				if( $options['style'] == 1 ) :
		 			while ( $r->have_posts() ): $r->the_post(); global $post;
		 				if ( $options['show'] == 'company' ) :
							$logo_company = Noo_Company::get_company_logo( $post->ID );

			 				echo "<div class='bg_images'><a href='" . get_permalink( $post->ID ) . "' target='_blank'>{$logo_company}</a></div>";
			 			elseif ( $options['show'] == 'testimonial' ) :
			 				$name     = get_post_meta(get_the_ID(),'_noo_wp_post_name', true);
							$position = get_post_meta(get_the_ID(),'_noo_wp_post_position', true);
							$url = get_post_meta(get_the_ID(),'_noo_wp_post_image', true);
							?>
								<div class="box_testimonial">
									<div class="box-content">
										<?php the_content(); ?>
									</div>
									<div class="icon"></div>
									<div class="box-info">
										<div class="box-info-image">
											<img src="<?php echo wp_get_attachment_url(esc_attr($url)); ?>" alt="<?php the_title(); ?>" />
										</div>
										<div class="box-info-entry">
											<h4><?php echo $name; ?></h4>
											<h5><?php echo $position ?></h5>
										</div>
									</div>
								</div>
							<?php
			 			endif;

		 			endwhile;
		 		elseif ( $options['style'] == 2 ) :
					while ( $r->have_posts() ): $r->the_post(); global $post;
						if ( $options['show'] == 'testimonial' ) :
			 				$name     = get_post_meta(get_the_ID(),'_noo_wp_post_name', true);
							$position = get_post_meta(get_the_ID(),'_noo_wp_post_position', true);
							$url = get_post_meta(get_the_ID(),'_noo_wp_post_image', true);
							?>
								<div class="box_testimonial_single">
									<div class="box-info">
										<div class="box-info-image">
											<img src="<?php echo wp_get_attachment_url(esc_attr($url)); ?>" alt="<?php the_title(); ?>" />
										</div>
										<div class="box-info-entry">
											<h4><?php echo $name; ?></h4>
											<h5><?php echo $position ?></h5>
										</div>
									</div>
									<div class="box-content">
										<?php the_content(); ?>
									</div>
								</div>
							<?php
			 			endif;
					endwhile;
		 		endif;
		 	$pagination = ( $options['hidden_pagination'] == 'true' ? '' : 'pagination: { container : ".pag_slider_' . $options['id'] . '", keys	: true }' );
	 		echo '</div>
	 			<div class="clearfix"></div>
				<div class="page pag_slider_' . $options['id'] . '"></div>
	 		</div>
			<script type="text/javascript">
			jQuery(\'document\').ready(function ($) {
				$("#slider_' . $options['id'] . '").each(function() {
					var $this = $(this);
					imagesLoaded($this, function() {
						$this.carouFredSel({
							responsive	: true,
							auto 		: ' . $options['autoplay'] .',
							items		: {
								width		: ' . $options['width'] .',
								height		: "' . $options['height'] .'",
								visible		: {
									min			: ' . $options['min'] .',
									max			: ' . $options['max'] .'
								}
							},
							' . $pagination . '
						});
					});
				});
			});
			</script>
	 		';
		endif;

	}

endif;

/** ====== END noo_caroufredsel_slider ====== **/	

/* -------------------------------------------------------
 * Create functions check_view_application
 * ------------------------------------------------------- */

if ( ! function_exists( 'check_view_application' ) ) :
	
	function check_view_application() {
		if ( Noo_Member::is_employer() ) :
			// -- get id employer
				$id_employer = get_current_user_id();
			// -- default meta
				$key_meta = '_check_view_applications';
			// get value in meta -> array
				$check_view = get_user_meta( $id_employer, $key_meta, true ) ? (array) get_user_meta( $id_employer, $key_meta, true ) : array();
				
				$id_applications = array($_POST['application_id']);
				$arr_value = array_merge($check_view, $id_applications);
				
				if ( !in_array ( $_POST['application_id'], $check_view ) ):
					update_user_meta( $id_employer, $key_meta, $arr_value);
				endif;
				
		endif;

	}
	// add_action('noo_check_view_applications', 'check_view_application');
	add_action( 'wp_ajax_nopriv_check_view_application', 'check_view_application' );
	add_action( 'wp_ajax_check_view_application', 'check_view_application' );
endif;

/** ====== END check_view_application ====== **/

/* -------------------------------------------------------
 * Create functions check_view_application
 * ------------------------------------------------------- */

if ( ! function_exists( 'check_view_applied' ) ) :
	
	function check_view_applied() {
		if ( Noo_Member::is_employer() ) :
			// -- get id candidate
				$user = wp_get_current_user();
			// -- default meta
				$key_meta = '_check_view_applied';
			// get value in meta -> array
				$check_view = get_user_meta( $user, $key_meta, true ) ? (array) get_user_meta( $user, $key_meta, true ) : array();
				
				$id_applications = array($_POST['application_id']);
				$arr_value = array_merge($check_view, $id_applications);
				
				if ( !in_array ( $_POST['application_id'], $check_view ) ):
					update_user_meta( $user, $key_meta, $arr_value);
				endif;
				
		endif;

	}
	// add_action('noo_check_view_applications', 'check_view_application');
	add_action( 'wp_ajax_nopriv_check_view_applied', 'check_view_applied' );
	add_action( 'wp_ajax_check_view_applied', 'check_view_applied' );
endif;

/** ====== END check_view_application ====== **/

/* -------------------------------------------------------
 * Create functions unseen_applications_number
 * ------------------------------------------------------- */

if ( ! function_exists( 'unseen_applications_number' ) ) :
	
	function unseen_applications_number( $html = true ) {
		$count_view = 0;

		if ( Noo_Member::is_employer() ) :
			$job_ids = get_posts(array(
				'post_type'=>'noo_job',
				'post_status'=>array('publish','pending','expired'),
				'author'=>get_current_user_id(),
				'posts_per_page'=>-1,
				'fields' => 'ids'
			));
			$args_total = array(
				'post_type'=>'noo_application',
				'post_parent__in'=>array_merge($job_ids, array(0)),
				'post_status'=>array('publish','pending','expired'),
				
			);
			$args = array(
				'post_type'=>'noo_application',
				'post_parent__in'=>array_merge($job_ids, array(0)),
				'post_status'=>array('pending'),
				
			);
			$total_applicaitons = new WP_Query($args_total);
			$pending_applications = new WP_Query($args);
			$total_applicaitons = $total_applicaitons->found_posts;
			$pending_applications = $pending_applications->found_posts;
			$count_view = ($total_applicaitons = $pending_applications) ? $pending_applications : ($total_applicaitons - $pending_applications);
		elseif( Noo_Member::is_candidate() ) :
			$user = wp_get_current_user();

			$args = array(
				'post_type'=>'noo_application',
				'post_status'=>array('publish','rejected'),
				'meta_query'=>array(
					array(
						'key' => '_candidate_email',
						'value' => $user->user_email,
					),
				)
			);

			$total_applied = new WP_Query($args);
			$total_applied = $total_applied->found_posts;
			$view_applications = count(get_user_meta( $user->ID, '_check_view_applied', true ));

			$count_view = $total_applied - $view_applications;
		endif;

		$count_view = apply_filters( 'noo-unseen-applications-number', $count_view );

		if ( $count_view > 0 ) {
			return $html ? '<span class="badge">' . $count_view .'</span>' : absint( $count_view );
		} else {
			return $html ? '' : 0;
		}
	}

endif;

/** ====== END unseen_applications_number ====== **/

/* -------------------------------------------------------
 * Create functions user_notifications_number
 * ------------------------------------------------------- */

if ( ! function_exists( 'user_notifications_number' ) ) :
	
	function user_notifications_number( $html = true ) {
		$count_view = unseen_applications_number( false );
		$count_view = apply_filters( 'noo-user-notifications-number', $count_view );

		if ( $count_view > 0 ) {
			return $html ? '<span class="badge">' . $count_view .'</span>' : $count_view;
		} else {
			return $html ? '' : 0;
		}
	}

endif;

/** ====== END user_notifications_number ====== **/

/* -------------------------------------------------------
 * Create functions noo_get_default_fields
 * ------------------------------------------------------- */

if ( ! function_exists( 'noo_get_default_fields' ) ) :
	
	function noo_get_default_fields( $name_filters , $args = array() ) {
		
		

	}

endif;

/** ====== END noo_get_default_fields ====== **/

/* -------------------------------------------------------
 * Create functions noo_get_custom_fields
 * ------------------------------------------------------- */

if ( ! function_exists( 'noo_get_custom_fields' ) ) :
	
	function noo_get_custom_fields( $custom_fields, $name ) {
		
		// $custom_fields = self::get_setting('noo_job_custom_field', array());
		// if ( $custom_fields == 'noo_resume[custom_field]' )
			$custom_fields = isset( $custom_fields['custom_field'] ) ? $custom_fields['custom_field'] : $custom_fields;
		
		if( !$custom_fields || !is_array($custom_fields) ) {
			$custom_fields = array();
		}

		// __option__ is resevasion for other setting
		if( isset( $custom_fields['__options__'] ) ) {
			unset( $custom_fields['__options__'] );
		}
	
		if( function_exists('icl_object_id') ) {
			foreach ($custom_fields as $index => $custom_field) {
				if( !is_array($custom_field) ) continue;
				
				$custom_fields[$index]['label_translated'] = apply_filters('wpml_translate_single_string', @$custom_field['label'], 'NOO Custom Fields', $name. sanitize_title(@$custom_field['name']), apply_filters( 'wpml_current_language', null ) );
				//icl_translate('noo', $name . sanitize_title(@$custom_field['name']), @$custom_field['label'] );
			}
		}
		return $custom_fields;

	}

endif;

/** ====== END noo_get_custom_fields ====== **/

/* -------------------------------------------------------
 * Create functions noo_request_live_search
 * ------------------------------------------------------- */

if ( ! function_exists( 'noo_request_live_search' ) ) :
	
	function noo_request_live_search() {

		check_ajax_referer('noo-advanced-live-search', 'live-search-nonce1');

		$args = array(
			'post_type'   => $_GET['post_type'],
			'post_status' => 'publish',
			's' => $_GET['s']
		);

		if( $args['post_type'] == 'noo_resume' && !Noo_Resume::can_view_resume(null,true) ) {
			wp_die();
		}

		$args['tax_query']  = array('relation' => 'AND');
		$args['meta_query'] = array('relation' => 'AND');
		
		if( isset( $_GET['category'] ) && $_GET['category'] !='') :
			if ( $_GET['post_type'] == 'noo_job' ) :

				$args['tax_query'][] = array(
						'taxonomy'     => 'job_category',
						'field'        => 'slug',
						'terms'        => $_GET['category']
					);

			else :
				// if($c = get_term_by('ID', $_GET['category'], 'job_category')):

					$args['meta_query'][]	= array(
							'key'     => '_job_category',
							'value'   => '"' . $_GET['category'] . '"',
							'compare' =>'LIKE'
						);

				// endif;

			endif;
		endif;
		unset($_GET['category']);

		if( isset( $_GET['location'] ) && $_GET['location'] !='') :

			if ( $_GET['post_type'] == 'noo_job' ) :

				$args['tax_query'][] = array(
						'taxonomy'     => 'job_location',
						'field'        => 'slug',
						'terms'        => $_GET['location']
					);

			else :
				// if($l = get_term_by('ID', $_GET['location'], 'job_location')):

					$args['meta_query'][]	= array(
							'key'     => '_job_location',
							'value'   => '"' . $_GET['location'] . '"',
							'compare' =>'LIKE'
						);

				// endif;

			endif;
			unset($_GET['location']);

		endif;

		if( isset( $_GET['type'] ) && $_GET['type'] !='') :

			if ( $_GET['post_type'] == 'noo_job' ) :

				$args['tax_query'][] = array(
						'taxonomy'     => 'job_type',
						'field'        => 'slug',
						'terms'        => $_GET['type']
					);
		
			// else :

				// if($t = get_term_by('ID', $_GET['type'], 'job_type')):

					// $args['meta_query'][]	= array(
					// 		'key'     => '_job_type',
					// 		'value'   => '"' . $_GET['type'] . '"',
					// 		'compare' =>'LIKE'
					// 	);

				// endif;

			endif;
			unset($_GET['type']);

		endif;

		$get_keys = array_keys($_GET);
				
		$resume_default_fields = noo_get_custom_fields( get_option( 'noo_resume' ), 'noo_resume_custom_fields_' );
		

		foreach ($get_keys as $get_key) {
			if ( $_GET['post_type'] == 'noo_job' ) {
				if(strstr( $get_key, '_noo_job_field_' )) {
					$value = $_GET[$get_key];
					if(!empty($value)){
						$args['meta_query'][]	= array(
								'key'     =>$get_key,
								'value'   =>$value,
								'compare' =>'LIKE'
							);
					}
				}
			} else {
				if(array_key_exists($get_key, $resume_default_fields) || strstr( $get_key, '_noo_resume_field_' )) {
					$value = $_GET[$get_key];
					if(!empty($value)){
						$args['meta_query'][]	= array(
								'key'     =>$get_key,
								'value'   =>$value,
								'compare' =>'LIKE'
							);
					}
				}
			}
		}

		$query = new WP_Query( $args );

		if ( $args['post_type'] == 'noo_resume' ) :
			$loop_args = apply_filters( 'noo_resume_search_args', array(
					'query'    => $query,
					'live_search' => true
				), $args );
			Noo_Resume::loop_display( $loop_args );

		elseif ( $args['post_type'] == 'noo_job' ) :
			$loop_args = apply_filters( 'noo_job_search_args', array(
					'query'    => $query,
					'paginate' =>'loadmore',
					'title'    =>''
				), $args );
			Noo_Job::loop_display( $loop_args );

		endif;

		wp_die();

	}

	add_action( 'wp_ajax_nopriv_live_search', 'noo_request_live_search' );
	add_action( 'wp_ajax_live_search', 'noo_request_live_search' );

endif;

/** ====== END noo_request_live_search ====== **/


// --- Add captcha

	/* -------------------------------------------------------
	 * Create functions noo_show_captcha_image
	 * ------------------------------------------------------- */

	if ( ! function_exists( 'noo_show_captcha_image' ) ) :
		
		function noo_show_captcha_image() {
			$noo_job_linkedin = get_option('noo_job_linkedin');
			$md5_hash = md5(rand(0,999)); 
    		$security_code = substr($md5_hash, 15, 5);
			$image_captcha = NOO_FRAMEWORK_FUNCTION_URI . '/noo-captcha.php?code=' . $security_code;

			?>
			<div class="form-group row">
				<div class="col-sm-3" style="margin-top: 3px;text-align: right;min-width: 130px;">
					<img class="security_code" data-security-code="<?php echo $security_code; ?>" src="<?php echo $image_captcha; ?>" alt="<?php echo $image_captcha; ?>" />
					<?php if ( isset($noo_job_linkedin['apply_job_using_captcha']) ) : ?>
						<input type="hidden" name="security_code" value="<?php echo $security_code; ?>" />
					<?php endif; ?>
				</div>
				<div class="col-sm-8">
					<input class="form-control security_input" type="text" name="noo_captcha" placeholder="<?php _e( 'Enter the text you see', 'noo' ); ?>" required />
				</div>
			</div>
			<?php

		}

		$noo_member_setting = get_option('noo_member');
		$noo_job_linkedin = get_option('noo_job_linkedin');

		if ( isset($noo_member_setting['register_using_captcha']) ) :

			add_action( 'noo_register_form', 'noo_show_captcha_image' );

		endif;

		if ( isset($noo_job_linkedin['apply_job_using_captcha']) ) :

			add_action( 'after_apply_job_form', 'noo_show_captcha_image' );

		endif;

	endif;

	/** ====== END noo_show_captcha_image ====== **/


// --- / Add captcha


/* -------------------------------------------------------
 * Create functions noo_auto_create_order_free_package
 * ------------------------------------------------------- */

if ( ! function_exists( 'noo_auto_create_order_free_package' ) ) :
	
	function noo_auto_create_order_free_package() {
		check_ajax_referer('noo-free-package','security');

		if( !is_user_logged_in() ) {
			wp_die();
		}

		$product_id = absint( $_POST['package_id'] );
		$user_id = absint( $_POST['user_id'] );
		$login_user_id = get_current_user_id();
		if( $user_id != $login_user_id ) {
			wp_die();
		}

		$user_info = get_userdata($user_id);
		$new_order_ID = create_new_order( $user_info->display_name, $_POST['user_id'] );
		$order = new WC_Order( $new_order_ID );
		$order->update_status( 'completed' );

		// $info_package_free = array(
		// 	'product_id'   => $_POST['package_id'],
		// 	'created'      => date('Y-m-d') . date(' H:i:s', time()),
		// 	'job_duration' => 1,
		// 	'job_limit'    => 3,
		// 	'job_featured' => 0
		// );
		$product = get_product( $product_id  );

		if ($product && $product->is_type( 'job_package' ) && $product->get_price() == 0 && is_user_logged_in() ) {

			$user_id = $order->customer_user;
			$package_data = array(
				'product_id'   => $product->id,
				'created'      => current_time('mysql'),
				'job_duration' => absint($product->get_job_display_duration()),
				'job_limit'    => absint($product->get_post_job_limit()),
				'job_featured' => absint($product->get_job_feature_limit())
			);

			update_user_meta( $_POST['user_id'], '_free_package_bought', 1 );
			update_user_meta( $_POST['user_id'], '_job_added', 0 );
			update_user_meta( $_POST['user_id'], '_job_featured', 0 );
			update_user_meta( $_POST['user_id'], '_job_package', $package_data );
		}

		wp_die();
	}

	add_action( 'wp_ajax_auto_create_order', 'noo_auto_create_order_free_package' );

endif;

/** ====== END noo_auto_create_order_free_package ====== **/
