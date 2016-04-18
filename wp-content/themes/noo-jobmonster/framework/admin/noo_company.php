<?php
if(!class_exists('Noo_Company')):
	if( !class_exists('Noo_CPT') ) {
		require_once dirname(__FILE__) . '/noo_cpt.php';
	}
	class Noo_Company extends Noo_CPT {

		static $instance = false;
		static $employers = array();

		public function __construct(){

			$this->post_type = 'noo_company';
			$this->slug = 'companies';
			$this->prefix = 'company';
			$this->option_key = 'noo_company';

			add_action( 'init', array( &$this, 'register_post_type' ),0 );
			add_filter( 'template_include', array( $this, 'template_loader' ) );
			add_action( 'pre_get_posts', array (&$this, 'pre_get_posts'), 1 );
			add_shortcode( 'noo_companies', array (&$this, 'noo_companies_shortcode'), 2 );
			add_shortcode( 'noo_company_feature', array (&$this, 'noo_company_feature_shortcode'), 3 );
			add_filter( 'redirect_canonical', array (&$this, 'custom_disable_redirect_canonical' ) );

			if( is_admin() ) {
				add_action('admin_init', array(&$this,'admin_init'));
				$this->_company_featured();
				add_action( 'add_meta_boxes', array(&$this, 'remove_meta_boxes' ), 20 );
				add_action( 'add_meta_boxes', array(&$this, 'add_meta_boxes' ), 30 );
				add_filter( 'enter_title_here', array (&$this,'custom_enter_title') );

				add_filter('manage_edit-' . $this->post_type . '_columns', array(&$this, 'manage_edit_columns'));
				add_action('manage_posts_custom_column', array(&$this, 'manage_posts_custom_column'));


				add_filter('noo_job_settings_tabs_array', array(&$this,'add_seting_company_tab'));
				add_action('noo_job_setting_company', array(&$this,'setting_page'));

				add_filter('wp_insert_post', array(&$this,'default_company_data'), 10, 3);

				// add_filter('months_dropdown_results', '__return_empty_array');
				// add_action( 'restrict_manage_posts', array(&$this, 'restrict_manage_posts') );
				// add_filter( 'parse_query', array(&$this, 'posts_filter') );
			}
		}

		public static function get_setting($id = null ,$default = null){
			$company_setting = get_option('noo_company');
			if(isset($company_setting[$id]))
				return $company_setting[$id];
			return $default;
		}
	
		public function admin_init(){
			register_setting('noo_company','noo_company');
		}

		public function template_loader($template){
			if(is_post_type_archive( $this->post_type ) ){
				$template       = locate_template( "archive-{$this->post_type}.php" );
			}
			return $template;
		}

		public function register_post_type() {
			// Sample register post type
			$archive_slug = self::get_setting('archive_slug', 'companies');
			$archive_slug = empty( $archive_slug ) ? 'companies' : $archive_slug;

			register_post_type( 
				$this->post_type,
				array( 
					'labels' => array( 
						'name' => __( 'Companies', 'noo' ), 
						'singular_name' => __( 'Company', 'noo' ), 
						'menu_name' => __( 'Companies', 'noo' ),
						'all_items' => __( 'Companies', 'noo' ),
						'add_new' => __( 'Add New', 'noo' ),
						'add_new_item' => __( 'Add Company', 'noo' ),
						'edit' => __( 'Edit', 'noo' ), 
						'edit_item' => __( 'Edit Company', 'noo' ), 
						'new_item' => __( 'New Company', 'noo' ), 
						'view' => __( 'View', 'noo' ), 
						'view_item' => __( 'View Company', 'noo' ), 
						'search_items' => __( 'Search Company', 'noo' ), 
						'not_found' => __( 'No Companies found', 'noo' ), 
						'not_found_in_trash' => __( 'No Companies found in Trash', 'noo' )
					), 
					'public' => true,
					'has_archive' => true,
					'show_in_menu' => 'edit.php?post_type=noo_job',
					'rewrite' => array( 'slug' => $archive_slug, 'with_front' => false ), 
					'supports' => array(
						'title',
						'editor'
					),
				)
			);
		}

		public function remove_meta_boxes() {
			// Remove slug and revolution slider
			remove_meta_box( 'mymetabox_revslider_0', $this->post_type, 'normal' );
		}

		public function add_meta_boxes() {
			// Declare helper object
			$prefix = '';
			$helper = new NOO_Meta_Boxes_Helper( $prefix, array( 'page' => $this->post_type ) );

			// General Info
			$meta_box = array(
				'id'           => '_general_info',
				'title'        => __( 'Company Infomation', 'noo' ),
				'context'      => 'normal',
				'priority'     => 'core',
				'description'  => '',
				'fields'       => array(
					array( 'id' => '_logo', 'label' => __( 'Company Logo', 'noo' ), 'type' => 'image' ), 
					array( 'id' => '_cover_image', 'label' => __( 'Cover Image', 'noo' ), 'type' => 'image' ), 
					array( 
						'id' => '_website', 
						'label' => __( 'Company Website', 'noo' ), 
						'type' => 'text' ), 
					array( 
						'id' => '_facebook', 
						'label' => __( 'Facebook URL', 'noo' ), 
						'type' => 'text' ), 
					array( 
						'id' => '_twitter', 
						'label' => __( 'Twitter URL', 'noo' ), 
						'type' => 'text' ), 
					array( 
						'id' => '_googleplus', 
						'label' => __( 'Google + URL', 'noo' ), 
						'type' => 'text' ), 
					array( 
						'id' => '_linkedin', 
						'label' => __( 'Linkedin URL', 'noo' ), 
						'type' => 'text' ), 
					array( 
						'id' => '_instagram', 
						'label' => __( 'Instagram URL', 'noo' ), 
						'type' => 'text' )
				),
			);

			$helper->add_meta_box($meta_box);
		}

		public function custom_enter_title( $input ) {
			global $post_type;

			if ( $this->post_type == $post_type )
				return __( 'Company Name', 'noo' );

			return $input;
		}

		public function manage_edit_columns($columns) {
			
			if ( ! is_array( $columns ) ) $columns = array();

			$before = array_slice($columns, 0, 2);
			$after = array_slice($columns, 2);
			
			$new_columns = array(
				'company_featured' => '<span class="tips" data-tip="' . __( "Is Company Featured?", 'noo' ) . '">' . __( "Featured?", 'noo' ) . '</span>',
			);

			$columns = array_merge($before, $new_columns, $after);
			return $columns;
		}

		public function manage_posts_custom_column($column) {
			global $post, $wpdb;
			switch ( $column ) {
				case "company_featured" :
					$featured = noo_get_post_meta($post->ID,'_company_featured');
					// Update old data
					if( empty( $featured ) ) update_post_meta( $post->ID, '_company_featured', 'no' );

					$url = wp_nonce_url( admin_url( 'admin-ajax.php?action=noo_company_feature&company_id=' . $post->ID ), 'noo-company-feature' );
					echo '<a href="' . esc_url( $url ) . '" title="'. __( 'Toggle featured', 'noo' ) . '">';
					if ( 'yes' === $featured ) {
						echo '<span class="noo-company-feature" title="'.esc_attr__('Yes','noo').'"><i class="dashicons dashicons-star-filled "></i></span>';
					} else {
						echo '<span class="noo-company-feature not-featured"  title="'.esc_attr__('No','noo').'"><i class="dashicons dashicons-star-empty"></i></span>';
					}
					echo '</a>';
				
				break;

			}
		}
	
		public function add_seting_company_tab($tabs){
			$temp1 = array_slice($tabs, 0, 3);
			$temp2 = array_slice($tabs, 3);

			$company_tab = array( 'company' => __('Company','noo') );
			return array_merge($temp1, $company_tab, $temp2);
		}

		public function setting_page(){
			if(isset($_GET['settings-updated']) && $_GET['settings-updated']) {
				flush_rewrite_rules();
			}
			?>
				<?php settings_fields('noo_company'); ?>
				<h3><?php echo __('Company Options','noo')?></h3>
				<table class="form-table" cellspacing="0">
					<tbody>
						<tr>
							<th>
								<?php esc_html_e('Companies Archive base (slug)','noo')?>
							</th>
							<td>
								<?php $archive_slug = self::get_setting('archive_slug', 'companies'); ?>
								<input type="text" name="noo_company[archive_slug]" value="<?php echo ($archive_slug ? $archive_slug : 'companies') ?>">
							</td>
						</tr>
						<tr>
							<th>
								<?php esc_html_e('Show Company with no Jobs','noo')?>
							</th>
							<td>
								<?php $show_no_jobs = self::get_setting('show_no_jobs',1); ?>
								<input type="hidden" name="noo_company[show_no_jobs]" value="">
								<input type="checkbox" <?php checked( $show_no_jobs, '1' ); ?> name="noo_company[show_no_jobs]" value="1">
							</td>
						</tr>
						<tr>
							<th>
								<?php esc_html_e('Enable Cover Image','noo')?>
							</th>
							<td>
								<?php $cover_image = self::get_setting('cover_image', 'yes'); ?>
								<input type="hidden" name="noo_company[cover_image]" value="">
								<input type="checkbox" <?php checked( $cover_image, 'yes' ); ?> name="noo_company[cover_image]" value="yes">
								<p><small><?php echo __('Allow Employers to change cover image. This image will be used on a company\'s page as well as a default value for its jobs cover images.','noo') ?></small></p>
							</td>
						</tr>
						<?php do_action( 'noo_setting_company_fields' ); ?>
					</tbody>
				</table>
			<?php 
		}

		public function default_company_data($post_ID = 0, $post = null, $update = false) {

			if( !$update && !empty( $post_ID ) && $post->post_type == 'noo_company' ) {
				update_post_meta( $post_ID, '_company_featured', 'no' );
			}
		}

		public function pre_get_posts( $query ) {
			if ( is_admin() || $query->is_singular ) {
				return;
			}

			//if is querying noo_company
			if(isset($query->query_vars['post_type']) && $query->query_vars['post_type'] == 'noo_company' ) {

				$query->query_vars['posts_per_page'] = -1;
				$query->query_vars['orderby'] = 'title';
				$query->query_vars['order'] = 'ASC';
			}

			return;
		}

		public function noo_companies_shortcode($atts, $content = null){
			extract(shortcode_atts(array(
				'title' => __( 'Companies', 'noo' )
			), $atts));
			$args = array(
				'post_type'			  => 'noo_company',
				'post_status'         => 'publish',
				'posts_per_page'	  => -1,
				'orderby'			  => 'title',
				'order'				  => 'ASC',
			);
			
			$r = new WP_Query($args);
			ob_start();
			self::loop_display(array('query'=>$r, 'title'=>$title));
			$output = ob_get_clean();
			return $output;
		}

		public function noo_company_feature_shortcode($atts, $content = null){
			extract(shortcode_atts(array(
				'title'            => __( 'Featured Employer', 'noo' ),
				'posts_per_page'   => -1,
				'featured_content' => '',
			), $atts));
			wp_enqueue_script( 'vendor-carouFredSel' );
			$args = array(
				'post_type'			  => 'noo_company',
				'post_status'         => 'publish',
				'posts_per_page'	  => $posts_per_page,
				'orderby'			  => 'title',
				'order'				  => 'DESC',
			);

			$args['meta_query'][] = array(
				'key'   => '_company_featured',
				'value' => 'yes'
			);
			
			$r = new WP_Query($args);
			ob_start();
			self::loop_display(array('query'=>$r, 'title'=>$title, 'style'=> 'slider', 'featured_content' => $content));
			$output = ob_get_clean();
			return $output;
		}

		public static function get_employer_id( $company_id = null ) {
			if( empty( $company_id ) ) return 0;

			if( isset( self::$employers[$company_id] ) ) {
				return self::$employers[$company_id];
			}

			$employers = get_users( array( 'meta_key' => 'employer_company', 'meta_value' => $company_id, 'fields' => 'id' ) );

			if( empty( $employers ) ) {
				self::$employers[$company_id] = 0;
			} else {
				self::$employers[$company_id] = $employers[0];
			}

			return self::$employers[$company_id];
		}

		public static function count_jobs( $company_id = null ) {
			if( empty( $company_id ) ) return 0;

			$employer = self::get_employer_id( $company_id );

			$count_posts = count_many_users_posts( array( $employer ), 'noo_job', true );

			return array_sum($count_posts);
		}

		public static function get_more_jobs( $company_id = null, $exclude_job_ids = array(), $number_of_jobs = 3 ) {
			if( empty( $company_id ) ) return array();

			$employer = self::get_employer_id( $company_id );

			if( empty( $employer ) || !is_array($employer) ) return array();

			$args = array(
					'post_type' => 'noo_job',
					'post_status' => 'publish',
					'author' => $employer,
					'ignore_sticky_posts' => true,
					'posts_per_page' => $number_of_jobs,
					'nopaging' => true
				);

			if( !empty( $exclude_job_ids ) ) {
				$args['post__not_in'] = (array) $exclude_job_ids;
			}

			return get_posts( $args );
		}

		public function custom_disable_redirect_canonical( $redirect_url ){
			global $post;
			$ptype = get_post_type( $post );
			if ( $ptype == 'noo_company' ) $redirect_url = false;
			return $redirect_url;
		}

		public static function get_company_logo( $company_id = 0, $size = 'company-logo' ) {
			if( empty( $company_id ) ) return '';

			$company_name	= get_the_title( $company_id );
			$company_featured	= noo_get_post_meta($company_id, '_company_featured') == 'yes';
			$class			= $company_featured ? 'featured-company' : '';
			$thumbnail_id	= noo_get_post_meta($company_id, '_logo', '');
			$company_logo	= wp_get_attachment_image($thumbnail_id, $size, false, array( 'class' => $class, 'alt' => $company_name ) );
			if(empty($company_logo)){
				$company_logo = '<img src="'.NOO_ASSETS_URI.'/images/company-logo.png'.'" class="' . $class . '" alt="' . $company_name . '">';	
			}

			return $company_logo;
			// return '<span class="' . $class . '">' . $company_logo . '</span>';
		}

		public static function loop_display( $args = '' ) {
			$defaults = array( 
				'query' => '', 
				'title' => '',
				'style' => '',
				'content' => ''
			);
			$p = wp_parse_args($args,$defaults);
			extract($p);
			global $wp_query;
			if(!empty($query))
				$wp_query = $query;

			ob_start();
	        include(locate_template("layouts/noo_company-loop.php"));
	        echo ob_get_clean();

			wp_reset_postdata();
			wp_reset_query();
		}
		public static function display_sidebar( $company_id = null, $show_more_job = false ){

			if(empty($company_id)) {
				return;
			}

			ob_start();
	        include(locate_template("layouts/noo_company-info.php"));
	        echo ob_get_clean();

			wp_reset_postdata();
			wp_reset_query();
		}
		protected function _company_featured(){
			if(isset($_GET['action']) && $_GET['action'] == 'noo_company_feature'){
				// echo 'ok'; die;
				if ( ! current_user_can( 'edit_posts' ) ) {
					wp_die( __( 'You do not have sufficient permissions to access this page.', 'noo' ), '', array( 'response' => 403 ) );
				}
		
				if ( ! check_admin_referer( 'noo-company-feature' ) ) {
					wp_die( __( 'You have taken too long. Please go back and retry.', 'noo' ), '', array( 'response' => 403 ) );
				}
		
				$post_id = ! empty( $_GET['company_id'] ) ? (int) $_GET['company_id'] : '';
		
				if ( ! $post_id || get_post_type( $post_id ) !== 'noo_company' ) {
					die;
				}
		
				$featured = noo_get_post_meta( $post_id, '_company_featured' );
		
				if ( 'yes' === $featured ) {
					update_post_meta( $post_id, '_company_featured', 'no' );
				} else {
					update_post_meta( $post_id, '_company_featured', 'yes' );
				}
		
		
				wp_safe_redirect( esc_url_raw( remove_query_arg( array( 'trashed', 'untrashed', 'deleted', 'ids' ), wp_get_referer() ) ) );
				die();
			}
		}
	}

	new Noo_Company();
endif;