<?php
if(!class_exists('Noo_Job')) :
	class Noo_Job {
		
		protected static $_instance = null;
		
		public function __construct() {
			add_action( 'init', array( &$this, 'register_post_type' ), 0 );
			add_action('init', array(&$this,'init'));
			add_action('noo_job_check_expired_jobs', array(&$this,'check_expired_jobs'));
			add_filter( 'template_include', array( $this, 'template_loader' ) );
			add_action( 'after_switch_theme', array(&$this,'switch_theme_hook'));
			add_action( 'wp_enqueue_scripts', array(&$this,'enqueue_scripts') );
			add_action('pre_get_posts', array(&$this,'pre_get_posts'));

			// 
			add_filter( 'wp_head', array( $this, 'fb_share_media' ) );

			// schema
			add_filter( 'noo_site_schema', array( $this, 'job_site_schema' ) );
			add_filter( 'noo_page_title_schema', array( $this, 'job_title_schema' ) );

			//noo jobs short code
			add_shortcode('noo_jobs', array(&$this,'noo_jobs_shortcode'));
			add_shortcode('noo_job_map', array(&$this,'noo_job_map_shortcode'));
			add_shortcode('noo_job_search', array(&$this,'noo_job_search_shortcode'));
			add_shortcode('noo_step_icon', array(&$this,'noo_step_icon_shortcode'));

			//ajax search location
			add_action( 'wp_ajax_nopriv_noo_ajax_search_job_location', array(&$this, 'ajax_search_job_location') );
			add_action( 'wp_ajax_noo_ajax_search_job_location', array(&$this, 'ajax_search_job_location') );
			
			//noo_nextajax
			add_action('wp_ajax_nopriv_noo_nextajax', array(&$this,'noo_jobs_shortcode'));
			add_action('wp_ajax_noo_nextajax', array(&$this,'noo_jobs_shortcode'));

			// Post class
			add_filter('post_class', array(&$this,'post_class'));
			add_filter('wp_insert_post', array(&$this,'default_job_data'), 10, 3);
			
			if ( is_admin() ) {
				add_action('admin_init', array(&$this,'admin_init'));
				add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
				add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes' ), 30 );
				add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );
				add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
				add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 1, 2 );
				add_filter( 'manage_edit-noo_job_columns', array( $this, 'columns' ) );
				add_action( 'manage_noo_job_posts_custom_column', array( $this, 'custom_columns' ), 2 );

				//change post meta expires to timespan
				add_action( 'noo_save_meta_box', array(&$this,'save_meta_box_expires'),10,1 );
				
				//Label
				add_action('job_type_add_form_fields',array(&$this,'add_job_type_color'));
				add_action('job_type_edit_form_fields',array(&$this,'edit_job_type_color'),10,3);
				
				add_action( 'created_term', array($this,'save_type_color'), 10,3 );
				add_action( 'edit_term', array($this,'save_type_color'), 10,3 );
				
				add_action( 'created_term', array($this,'save_location_geo_data'), 10,3 );
				add_action( 'edit_term', array($this,'save_location_geo_data'), 10,3 );
				
				// Admin Filter
				add_action( 'restrict_manage_posts', array(&$this, 'restrict_manage_posts') );
				add_filter( 'parse_query', array(&$this, 'posts_filter') );

				add_filter( 'views_edit-noo_job', array( &$this,'modified_views_status' ) );
				foreach ( array( 'post', 'post-new' ) as $hook ) {
					add_action( "admin_footer-{$hook}.php", array( &$this,'extend_job_status' ) );
				}
			}
		}
		
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}
		
		public function init(){
		}
		
		public function switch_theme_hook($newname = '', $newtheme = ''){
			$this->_insert_default_data();
		}
		
		public function admin_init(){
			$this->_create_cron_jobs();

			$this->_feature_job();
			$this->_approve_job();
			register_setting('noo_job_general','noo_job_general');
			register_setting('noo_job_custom_field', 'noo_job_custom_field');
			register_setting('noo_job_custom_field_display', 'noo_job_custom_field_display');
			register_setting('noo_job_linkedin','noo_job_linkedin');
			register_setting('noo_email','noo_email');
			add_action('noo_job_setting_general', array(&$this,'setting_general'));
			// add_action('noo_job_setting_custom_field',array(&$this,'setting_custom_field'));
			add_action('noo_job_setting_application', array(&$this,'setting_application'));
			add_action('noo_job_setting_email', array(&$this,'setting_email'));
			if( class_exists('TablePress') ) {
				add_action( "load-noo_job_page_manage_noo_job", array( TablePress::$controller, 'add_editor_buttons' ) );
			}
		}
		
		public function template_loader( $template ) {
			if ( is_post_type_archive( 'noo_job' ) || is_tax( 'job_category' ) || is_tax( 'job_type' ) ||
				is_tax( 'job_location' ) ) {
					$template = locate_template( 'archive-noo_job.php' );
				}
				return $template;
		}
		
		protected function _get_job_map_data(){
			$args = array(
				'post_type'		=>'noo_job',
				'nopaging'		=>true,
				'post_status'   =>'publish',
			);
			$markers = array();
			$noo_job_geolocation = get_option('noo_job_geolocation');
			$r = new WP_Query($args);
			if($r->have_posts()):
				while ($r->have_posts()):
					$r->the_post();
					global $post;
						
					$job_locations = get_the_terms($post->ID, 'job_location');
					if($job_locations && !is_wp_error($job_locations)){
						$need_update = false;
						foreach($job_locations as $job_location){
							if(empty($job_location->slug))
								continue;

							if(isset($noo_job_geolocation[$job_location->slug])){
								$job_location_geo_data = $noo_job_geolocation[$job_location->slug];
							} else {
								$job_location_geo_data = Noo_Job::get_job_geolocation($job_location->slug);
								if($job_location_geo_data && !is_wp_error($job_location_geo_data)){
									$need_update = true;
									$noo_job_geolocation[$job_location->slug] = $job_location_geo_data;
								} else {
									continue;
								}
							}

							$company_logo		= '';
							$company_url		= '';
							$company_name		= '';
							$company_id			= Noo_Job::get_employer_company($post->post_author);
							$type_name			= '';
							$type_url			= '';
							$type_color			= '';
			
							$type				= Noo_Job::get_job_type( $post );
							if( $type ) {
								$type_name		= $type->name;
								$type_url		= get_term_link($type,'job_type');
								$type_color		= $type->color;
							}

							if( !empty( $company_id ) ) {
								$company_logo   = Noo_Company::get_company_logo( $company_id );
								$company_url	= get_permalink( $company_id );
								$company_name	= get_the_title( $company_id );
							}

							$marker = array(
								'latitude'=>$job_location_geo_data['lat'],
								'longitude'=>$job_location_geo_data['long'],
								'title'=>get_the_title($post->ID),
								'image'=>$company_logo,
								'type'=>$type_name,
								'type_url'=>$type_url,
								'type_color'=>$type_color,
								'url'=>get_permalink($post->ID),
								'company_url'=>$company_url,
								'company'=>$company_name,
								'term'=>$job_location->slug,
								'term_url'=>get_term_link($job_location,'job_location')
							);
							$markers[] = $marker;
						}

						//update geo option
						if( $need_update ) {
							update_option('noo_job_geolocation', $noo_job_geolocation);
						}
					}
				endwhile;
				wp_reset_postdata();
				wp_reset_query();
			endif;
			
			return json_encode($markers);
		}
		
		public function enqueue_scripts(){
			$js_folder_uri = SCRIPT_DEBUG ? NOO_ASSETS_URI . '/js' : NOO_ASSETS_URI . '/js/min';
			$js_suffix = SCRIPT_DEBUG ? '' : '.min';

			wp_register_script( 'google-map','http'.(is_ssl() ? 's':'').'://maps.googleapis.com/maps/api/js?v=3.exp&sensor=true&libraries=places',array('jquery'), null , false);
			wp_register_script( 'google-map-infobox', $js_folder_uri . '/infobox' . $js_suffix . '.js', array( 'jquery' , 'google-map' ), null, true );
			wp_register_script( 'google-map-markerclusterer', $js_folder_uri . '/markerclusterer' . $js_suffix . '.js', array( 'jquery' , 'google-map' ), null, true );
			wp_register_script( 'noo-job-map', $js_folder_uri . '/job-map' . $js_suffix . '.js', array( 'google-map-infobox','google-map-markerclusterer',), null, true );
			$nooJobGmapL10n = array(
				'zoom'=>10,
				'latitude'=>40.714398,
				'longitude'=>-74.005279,
				'draggable'=>0,
				'theme_dir'=> get_template_directory(),
				'theme_uri'=> get_template_directory_uri(),
				'marker_icon'=>NOO_ASSETS_URI.'/images/map-marker.png',
				'marker_data'=>$this->_get_job_map_data(),
				'primary_color'=>noo_get_option('noo_site_link_color',noo_default_primary_color())
			);
			wp_localize_script('noo-job-map','nooJobGmapL10n', $nooJobGmapL10n);
			
			$is_rtl = noo_get_option( 'noo_enable_rtl_support', 0 );
			wp_register_style( 'vendor-wysihtml5-css', NOO_FRAMEWORK_URI . '/vendor/bootstrap-wysihtml5/bootstrap-wysihtml5'.($is_rtl ? '-rtl.css' : '.css'), array( 'noo-main-style' ), null );
			wp_register_script( 'vendor-bootstrap-wysihtml5', NOO_FRAMEWORK_URI . '/vendor/bootstrap-wysihtml5/bootstrap3-wysihtml5.custom.min.js', array( 'jquery', 'vendor-bootstrap'), null, true );
			$wysihtml5L10n = array(
				'normal' => __('Normal', 'noo'),
				'h1' => __('Heading 1', 'noo'),
				'h2' => __('Heading 2', 'noo'),
				'h3' => __('Heading 3', 'noo'),
				'h4' => __('Heading 4', 'noo'),
				'h5' => __('Heading 5', 'noo'),
				'h6' => __('Heading 6', 'noo'),
				'bold' => __('Bold', 'noo'),
				'italic' => __('Italic', 'noo'),
				'underline' => __('Underline', 'noo'),
				'small' => __('Small', 'noo'),
				'unordered' => __('Unordered list', 'noo'),
				'ordered' => __('Ordered list', 'noo'),
				'outdent' => __('Outdent', 'noo'),
				'indent' => __('Indent', 'noo'),
				'insert_link' => __('Insert link', 'noo'),
				'cancel' => __('Cancel', 'noo'),
				'target' => __('Open link in new window', 'noo'),
				'insert_image' => __('Insert image', 'noo'),
				'cancel' => __('Cancel', 'noo'),
				'edit_html' => __('Edit HTML', 'noo'),
				'black' => __('Black', 'noo'),
				'silver' => __('Silver', 'noo'),
				'gray' => __('Grey', 'noo'),
				'maroon' => __('Maroon', 'noo'),
				'red' => __('Red', 'noo'),
				'purple' => __('Purple', 'noo'),
				'green' => __('Green', 'noo'),
				'olive' => __('Olive', 'noo'),
				'navy' => __('Navy', 'noo'),
				'blue' => __('Blue', 'noo'),
				'orange' => __('Orange', 'noo'),
				'stylesheet_rtl' => $is_rtl ? NOO_FRAMEWORK_URI . '/vendor/bootstrap-wysihtml5/stylesheet-rtl.css' : ''
			);
			wp_localize_script('vendor-bootstrap-wysihtml5', 'wysihtml5L10n', $wysihtml5L10n);

			wp_register_style( 'vendor-datetimepicker', NOO_FRAMEWORK_URI . '/vendor/datetimepicker/jquery.datetimepicker.css', '2.4.5' );
			wp_register_script( 'vendor-datetimepicker', NOO_FRAMEWORK_URI . '/vendor/datetimepicker/jquery.datetimepicker.js', array( 'jquery' ), '2.4.5', true );
			
			wp_localize_script( 'vendor-datetimepicker', 'datetime', array( 'lang' => substr(get_bloginfo ( 'language' ), 0, 2), 'rtl' => $is_rtl ) );
			
			wp_register_script( 'vendor-jquery-validate', NOO_FRAMEWORK_URI . '/vendor/jquery-validate/jquery.validate.min.js', array( 'jquery'), null, true );
			
			wp_register_script( 'noo-job', $js_folder_uri . '/job' . $js_suffix . '.js', array( 'vendor-bootstrap-wysihtml5','vendor-ajax-chosen','vendor-jquery-validate','vendor-datetimepicker'), null, true );

			wp_enqueue_style('vendor-datetimepicker');
			wp_enqueue_style('vendor-chosen');
			wp_enqueue_style('vendor-wysihtml5-css');
			$nooJobL10n = array(
				'ajax_url'        => admin_url( 'admin-ajax.php', 'relative' ),
				'ajax_finishedMsg'=>__('All jobs displayed','noo'),
				'validate_messages'=>array(
					'required'=>__("This field is required.",'noo'),
					'remote'=>__("Please fix this field.",'noo'),
					'email'=>__("Please enter a valid email address.",'noo'),
					'url'=>__("Please enter a valid URL.",'noo'),
					'date'=>__("Please enter a valid date.",'noo'),
					'dateISO'=>__("Please enter a valid date (ISO).",'noo'),
					'number'=>__("Please enter a valid number.",'noo'),
					'digits'=>__("Please enter only digits.",'noo'),
					'creditcard'=>__("Please enter a valid credit card number.",'noo'),
					'equalTo'=>__("Please enter the same value again.",'noo'),
					'maxlength'=>__("Please enter no more than {0} characters.",'noo'),
					'minlength'=>__("Please enter at least {0} characters.",'noo'),
					'rangelength'=>__("Please enter a value between {0} and {1} characters long.",'noo'),
					'range'=>__("Please enter a value between {0} and {1}.",'noo'),
					'max'=>__("Please enter a value less than or equal to {0}.",'noo'),
					'min'=>__("Please enter a value greater than or equal to {0}.",'noo'),
					'chosen'=>__('Please choose a option','noo'),
					'uploadimage'=>__('Please select a image file','noo'),
					'extension'=>__('Please enter a value with a valid extension.','noo')
				),
			);
			wp_localize_script('noo-job', 'nooJobL10n', $nooJobL10n);
			wp_enqueue_script('noo-job');
		}

		private function enqueue_map_script() {
			$nooJobGmapL10n = array(
				'zoom'=>10,
				'latitude'=>40.714398,
				'longitude'=>-74.005279,
				'draggable'=>0,
				'theme_dir'=> get_template_directory(),
				'theme_uri'=> get_template_directory_uri(),
				'marker_icon'=>NOO_ASSETS_URI.'/images/map-marker.png',
				'marker_data'=>$this->_get_job_map_data(),
				'primary_color'=>noo_get_option('noo_site_link_color',noo_default_primary_color())
			);
			wp_localize_script('noo-job-map','nooJobGmapL10n', $nooJobGmapL10n);
			wp_enqueue_script('noo-job-map');
		}
		
		public static function get_setting($group, $id = null ,$default = null){
			global $noo_job_setting_group;
			if(!isset($noo_job_setting_group[$group])){
				$noo_job_setting_group[$group] = get_option($group);
			}
			$group_setting_value = $noo_job_setting_group[$group];
			if(empty($id))
				return $group_setting_value;
			if(isset($group_setting_value[$id]))
				return $group_setting_value[$id];
			return $default;
		}

		public function add_meta_boxes() {

			$meta_box = array( 
				'id' => "job_settings", 
				'title' => __( 'Job Settings', 'noo' ), 
				'page' => 'noo_job', 
				'context' => 'normal', 
				'priority' => 'high', 
				'fields' => array( 
					
					array( 'id' => '_cover_image', 'label' => __( 'Cover Image', 'noo' ), 'type' => 'image' ), 
					array( 
						'id' => '_application_email', 
						'label' => __( 'Application Notify Email', 'noo' ), 
						'type' => 'text',
						'desc'=>__('Email to receive application notification. Leave it blank to use Employer\'s profile email.','noo')
					),
					// array(
					// 	'id' => '_company_id',
					// 	'label' => __( 'Company', 'noo' ),
					// 	'type' => 'select',
					// 	'options' => $company_option_arr ), 
					array( 'id' => 'author', 'label' => __( 'Post by', 'noo' ), 'type' => 'author','callback' => array( &$this, 'meta_box_author' ) ),
					array(
						'id' => '_closing',
						'label' => __( 'Job Closing Date', 'noo' ),
						'type' => 'datetimepicker',
						'callback' => array( &$this, 'meta_box_datetimepicker' ) ),
					array(
						'id' => '_expires',
						'label' => __( 'Job Expires Date', 'noo' ),
						'type' => 'datetimepicker',
						'callback' => array( &$this, 'meta_box_expires_datetimepicker' ) ),
					) );

			$custom_apply_link = self::get_setting('noo_job_linkedin', 'custom_apply_link' );
			if( !empty( $custom_apply_link ) ) {
				$meta_box['fields'][] = array(
						'id' => '_custom_application_url', 
						'label' => __( 'Custom Application link', 'noo' ), 
						'type' => 'text',
						'desc'=>__('Job seekers will be redirected to this URL when they want to apply for this job.','noo')
					);
			}
			// Create a callback function
			$callback = create_function( '$post,$meta_box', 'noo_create_meta_box( $post, $meta_box["args"] );' );
			add_meta_box( 
				$meta_box['id'], 
				$meta_box['title'], 
				$callback, 
				$meta_box['page'], 
				$meta_box['context'], 
				$meta_box['priority'], 
				$meta_box );
			$prefix = '';
			$helper = new NOO_Meta_Boxes_Helper( $prefix, array( 'page' => 'noo_job' ) );
			$default_fields = self::get_default_fields();
			$custom_fields = noo_get_custom_fields( self::get_setting('noo_job_custom_field', array()), 'noo_jobs_custom_fields_' );
			$fields = array_merge( array_diff_key($default_fields, $custom_fields), $custom_fields );
			if($fields){
				foreach ($fields as $field){
					if( !isset( $field['name'] ) || empty( $field['name'] ) ) continue;
					$field['type'] = !isset( $field['type'] ) || empty( $field['type'] ) ? 'text' : $field['type'];
					if( array_key_exists($field['name'], $default_fields) ) {
						if( isset( $field['is_disabled'] ) && ($field['is_disabled'] == 'yes') )
							continue;
						$id = $field['name'];
					// } else {
					// 	if( !isset($field['name']) || empty($field['name'])) continue;
					// 	$id = '_noo_resume_field_'.sanitize_title($field['name']);
					// 	$meta_box['fields'][] = array(
					// 		'label' => isset( $field['label_translated'] ) ? $field['label_translated'] : @$field['label'] ,
					// 		'id' => $id,
					// 		'type' => 'text',
					// 	);
					} else {
						$id = '_noo_job_field_'.sanitize_title($field['name']);
					}

					$type = $field['type'];
					if( $field['type'] == 'multiple_select' ) {
						$type = 'select';
						$field['multiple'] = true;
					}

					if( $field['type'] == 'number' ) {
						$type = 'text';
					}

					if( in_array( $field['type'], array( 'multiple_select', 'select', 'checkbox', 'radio', '') ) ) {
						$field['options'] = array();
						$list_value = isset( $field['value'] ) ? $field['value'] : '';
						$list_option = explode( "\n", $list_value );
						foreach ($list_option as $index => $option) {
							$option_key = explode( '|', $option );
							$option_key[0] = trim( $option_key[0] );
							$option_key[1] = isset( $option_key[1] ) ? $option_key[1] : $option_key[0];

							$field['options'][] = array(
								'label' => $option_key[1],
								'value' => $option_key[0]
								);
						}

						if( $field['type'] == 'checkbox' ) {
							$type = 'multiple_checkbox';
						}
					}

					$new_field = array(
						'label' => isset( $field['label_translated'] ) ? $field['label_translated'] : @$field['label'] ,
						'id' => $id,
						'type' => $type,
						'options' => isset( $field['options'] ) ? $field['options'] : '',
						'std' => isset( $field['std'] ) ? $field['std'] : '',
					);

					if( isset( $field['multiple'] ) && $field['multiple'] ) {
						$new_field['multiple'] = true;
					}

					$meta_box['fields'][] = $new_field;
				}
			}

			$helper->add_meta_box($meta_box);
			$job_detail_fields = array();
			// if($custom_fields){
			// 	foreach ($custom_fields as $custom_field){
			// 		$id = '_noo_job_field_'.sanitize_title(@$custom_field['name']);
			// 		$job_detail_fields[] = array(
			// 			'label' => isset( $custom_field['label_translated'] ) ? $custom_field['label_translated'] : @$custom_field['label'] ,
			// 			'id' => $id,
			// 			'type' => 'textarea',
			// 		);
			// 	}
			
			
			// 	$meta_box = array(
			// 		'id' => "job_custom_field",
			// 		'title' => __('Custom fields', 'noo') ,
			// 		'page' => 'noo_job',
			// 		'context' => 'normal',
			// 		'priority' => 'high',
			// 		'fields' => $job_detail_fields
			// 	);
					
			// 	// Create a callback function
			// 	$callback = create_function( '$post,$meta_box', 'noo_create_meta_box( $post, $meta_box["args"] );' );
			// 	add_meta_box( $meta_box['id'], $meta_box['title'], $callback, $meta_box['page'], $meta_box['context'], $meta_box['priority'], $meta_box );
					
			// }
		}

		public function meta_box_author( $post, $id, $type, $meta, $std, $field){
		
			// $meta = !empty($meta) ? $meta : $std;
			$user_list = get_users( array( 'role' => Noo_Member::EMPLOYER_ROLE ) );
			$admin_list = get_users( array( 'role' => 'administrator' ) );
			$user_list = array_merge($admin_list, $user_list);

			echo'<select name="post_author_override" id="post_author_override" >';
			echo'	<option value="" '. selected( $post->post_author, '', true ) . '>' . __('- Select an Employer - ', 'noo') . '</option>';
			foreach ( $user_list as $user ) {
				$company_id = self::get_employer_company($user->ID);
				echo'<option value="' . $user->ID . '"';
				selected( $post->post_author, $user->ID, true );
				echo '>' . $user->display_name;
				if( !empty($company_id) ) {
					$company_name = get_the_title( $company_id );
					echo ( !empty($company_name) ? ' - ' . $company_name : '' );
				}
				echo '</option>';
			}
			echo '</select>';
		}
		
		public function save_meta_box_expires($post_id){
			foreach ( $_POST['noo_meta_boxes'] as $key=>$val ) {
				if($key === '_expires'){
					update_post_meta( $post_id, $key, strtotime ( $val ) );
				}
			}
		}
		
		public function meta_box_expires_datetimepicker($post, $id, $type, $meta, $std, $field){
			$meta = !empty($meta) ? date_i18n('Y-m-d',$meta) : '';
			echo '<div>';
			echo '<input type="text" readonly class="input_text" placeholder="' . __('YYY-MM-DD', 'noo') . '" name="noo_meta_boxes[' . $id . ']" id="' . $id .
			'" value="' . esc_attr( $meta ) . '" /> ';
			echo '</div>';
			?>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					$('#<?php echo esc_html($id); ?>').datetimepicker({
					 format:'Y-m-d',
					 timepicker: false,
					 scrollMonth: false,
					 scrollTime: false,
					 scrollInput: false,
					 step:15
					});
				});
			</script>
			<?php
		}

		public function get_thumb_cover_image( $post, $id, $type, $meta, $std, $field ) {
			$company_cover_image = wp_get_attachment_image_src($meta, 'cover-image', false );
			echo '<div>';
			echo '<input type="text" readonly class="input_text" name="noo_meta_boxes[' . $id . ']" id="' . $id .
				 '" value="' . $company_cover_image[0] . '" /> ';
			echo '</div>';
		}

		public function meta_box_datetimepicker( $post, $id, $type, $meta, $std, $field ) {
			wp_enqueue_script( 'vendor-datetimepicker' );
			wp_enqueue_style( 'vendor-datetimepicker' );
			echo '<div>';
			echo '<input type="text" readonly placeholder="' . __('YYY-MM-DD', 'noo') . '" class="input_text" name="noo_meta_boxes[' . $id . ']" id="' . $id .
				 '" value="' . esc_attr( $meta ) . '" /> ';
			echo '</div>';
			?>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					$('#<?php echo esc_html($id); ?>').datetimepicker({
					 format:'Y-m-d',
					 timepicker: false,
					 scrollMonth: false,
					 scrollTime: false,
					 scrollInput: false,
					 step:15
					});
				});
			</script>
			<?php
		}
		
		public function post_updated_messages($messages){
			global $post, $post_ID, $wp_post_types;
			
			$messages['noo_job'] = array(
				0 => '',
				1 => sprintf( __( '%s updated. <a href="%s">View</a>', 'noo' ), $wp_post_types['noo_job']->labels->singular_name, esc_url( get_permalink( $post_ID ) ) ),
				2 => __( 'Custom field updated.', 'noo' ),
				3 => __( 'Custom field deleted.', 'noo' ),
				4 => sprintf( __( '%s updated.', 'noo' ), $wp_post_types['noo_job']->labels->singular_name ),
				5 => isset( $_GET['revision'] ) ? sprintf( __( '%s restored to revision from %s', 'noo' ), $wp_post_types['noo_job']->labels->singular_name, wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6 => sprintf( __( '%s published. <a href="%s">View</a>', 'noo' ), $wp_post_types['noo_job']->labels->singular_name, esc_url( get_permalink( $post_ID ) ) ),
				7 => sprintf( __( '%s saved.', 'noo' ), $wp_post_types['noo_job']->labels->singular_name ),
				8 => sprintf( __( '%s submitted. <a target="_blank" href="%s">Preview</a>', 'noo' ), $wp_post_types['noo_job']->labels->singular_name, esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
				9 => sprintf( __( '%s scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview</a>', 'noo' ), $wp_post_types['noo_job']->labels->singular_name,
					date_i18n( __( 'M j, Y @ G:i', 'noo' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
				10 => sprintf( __( '%s draft updated. <a target="_blank" href="%s">Preview</a>', 'noo' ), $wp_post_types['noo_job']->labels->singular_name, esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
			);
			
			return $messages;
		}
		
		public function enter_title_here($text, $post){
			if ( $post->post_type == 'noo_job' )
				return __( 'Job Title', 'noo' );
			return $text;
		}
		
		public function columns($columns){
			if ( ! is_array( $columns ) )
				$columns = array();

			unset( $columns['title'], $columns['date'], $columns['author'] );
			
			$columns["job_type"]     		 = __( "Type", 'noo' );
			$columns["job_position"]         = __( "Position", 'noo' );
			$columns["job_category"] 		 = __( "Categories", 'noo' );
			$columns["job_posted"]           = __( "Posted", 'noo' );
			$columns["job_closing"]          = __( "Closing", 'noo' );
			$columns["job_expires"]          = __( "Expired", 'noo' );
			$columns['featured_job']         = '<span class="tips" data-tip="' . __( "Is Job Featured?", 'noo' ) . '">' . __( "Featured?", 'noo' ) . '</span>';
			$columns['application']          = '<span class="tips" data-tip="' . __( "Number of Application", 'noo' ) . '">' . __( "Application", 'noo' ) . '</span>';
			$columns['job_status']           = __( "Status", 'noo' );
			if( isset( $columns['comments'] ) ) {
				$temp = $columns['comments'];
				unset( $columns['comments'] );
				$columns['comments']         = $temp;
			}
			$columns['job_actions']          = __( "Actions", 'noo' );
			
			return $columns;
		}
		
		public function custom_columns($column){
			global $post, $wpdb;
			switch ( $column ) {
				case "job_type" :
					$type = self::get_job_type( $post );
					if ( $type ) {
						edit_term_link( $type->name, '<span class="job-type ' . $type->slug . '" style="background-color:' . $type->color . ';">', '</span>', $type );
					}
				break;
				case "job_position" :
					echo '<div class="job_position">';
					echo '<a href="' . admin_url('post.php?post=' . $post->ID . '&action=edit') . '" class="tips job_title" data-tip="' . sprintf( __( 'ID: %d', 'noo' ), $post->ID ) . '"><b>' . get_the_title( $post ) . '<b/></a>';
				
					echo '<div class="location">';
				
					$company_id = self::get_employer_company($post->post_author);
					if ( $company_id ) {
						$company_name = get_the_title( $company_id );
						echo '<span>' . __( 'for', 'noo' ) . '&nbsp;<a href="' . get_edit_post_link( $company_id ) . '">'.$company_name.'</a></span>';
					}
				
					echo '</div>';
					echo '</div>';
					break;
				case "job_category" :
					if ( ! $terms = get_the_terms( $post->ID, $column ) ) {
						echo '<span class="na">&ndash;</span>'; 
					} else {
						$terms_edit = array();
						foreach( $terms as $term ) {
							$terms_edit[] = edit_term_link( $term->name, '', '', $term, false );
						}
						echo implode(', ', $terms_edit);
					}
					break;
				case "job_posted" :
					echo '<strong>' . date_i18n( get_option('date_format'), strtotime( $post->post_date ) ) . '</strong><span>';
					echo ( empty( $post->post_author ) ? __( 'by a guest', 'noo' ) : sprintf( __( 'by %s', 'noo' ), '<a href="' . get_edit_user_link( $post->post_author ) . '">' . get_the_author() . '</a>' ) ) . '</span>';
					break;
				case "job_closing" :
					if ( $post->_closing )
						echo '<strong>' . date_i18n( get_option('date_format'), strtotime( $post->_closing ) ) . '</strong>';
					else
						echo '&ndash;';
					break;
				case "job_expires" :
					if ( $post->_expires )
						echo '<strong>' . date_i18n( get_option('date_format'), $post->_expires ) . '</strong>';
					else
						echo '&ndash;';
					break;
				case "featured_job" :
					$featured = noo_get_post_meta($post->ID, '_featured' );
					if( empty( $featured ) ) {
						// Update old data
						update_post_meta( $post->ID, '_featured', 'no' );
					}
					$url = wp_nonce_url( admin_url( 'admin-ajax.php?action=noo_job_feature&job_id=' . $post->ID ), 'noo-job-feature' );
					echo '<a href="' . esc_url( $url ) . '" title="'. __( 'Toggle featured', 'noo' ) . '">';
					if ( 'yes' === $featured ) {
						echo '<span class="noo-job-feature" title="'.esc_attr__('Yes','noo').'"><i class="dashicons dashicons-star-filled "></i></span>';
					} else {
						echo '<span class="noo-job-feature not-featured"  title="'.esc_attr__('No','noo').'"><i class="dashicons dashicons-star-empty"></i></span>';
					}
					echo '</a>';
					
					break;
				case "application" :
					$application_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'noo_application' AND post_parent = {$post->ID}" );
					if ( $application_count > 0 ) {
						$url_args = array( 's' => '', 'post_status' => 'all', 'post_type' => 'noo_application', 'job' => $post->ID, 'action' => -1, 'action2' => -1 );
						$application_link = esc_url( add_query_arg( $url_args,admin_url( 'edit.php' ) ) ); 
						echo '<strong><a href="' . $application_link . '">' . $application_count . '</a></strong>';
					} else {
						echo '&ndash;';
					}
					break;
				case "job_status" :
					$status   = $post->post_status;
					$statuses = self::get_job_status();
					if ( isset( $statuses[ $status ] ) ) {
						$status = $statuses[ $status ];
					} else {
						$status = __( 'Inactive', 'noo' );
					}
					echo esc_html( $status );
					break;
				case "job_actions" :
					echo '<div class="actions">';
					$admin_actions           = array();
					if ( in_array( $post->post_status, array( 'pending', 'pending_payment' ) ) && current_user_can ( 'publish_post', $post->ID ) ) {
						$url = wp_nonce_url( admin_url( 'admin-ajax.php?action=noo_job_approve&job_id=' . $post->ID ), 'noo-job-approve' );
						$admin_actions['approve']   = array(
							'action'  => 'approve',
							'name'    => __( 'Approve', 'noo' ),
							'url'     =>  $url,
							'icon'	  => 'yes',
						);
					}
					if ( $post->post_status !== 'trash' ) {
						if ( current_user_can( 'read_post', $post->ID ) ) {
							$admin_actions['view']   = array(
								'action'  => 'view',
								'name'    => __( 'View', 'noo' ),
								'url'     => get_permalink( $post->ID ),
								'icon'	  => 'visibility',
							);
						}
						if ( current_user_can( 'edit_post', $post->ID ) ) {
							$admin_actions['edit']   = array(
								'action'  => 'edit',
								'name'    => __( 'Edit', 'noo' ),
								'url'     => get_edit_post_link( $post->ID ),
								'icon'	  => 'edit',
							);
						}
						if ( current_user_can( 'delete_post', $post->ID ) ) {
							$admin_actions['delete'] = array(
								'action'  => 'delete',
								'name'    => __( 'Delete', 'noo' ),
								'url'     => get_delete_post_link( $post->ID ),
								'icon'	  => 'trash',
							);
						}
					}
				
					$admin_actions = apply_filters( 'job_manager_admin_actions', $admin_actions, $post );
				
					foreach ( $admin_actions as $action ) {
						printf( '<a class="button tips icon-%1$s" href="%2$s" data-tip="%3$s">%4$s</a>', $action['action'], esc_url( $action['url'] ), esc_attr( $action['name'] ), '<i class="dashicons dashicons-'.$action['icon'].'"></i>' );
					}
				
					echo '</div>';
				
					break;
			}
		}

		public function restrict_manage_posts() {
			$type = 'post';
			if (isset($_GET['post_type'])) {
				$type = $_GET['post_type'];
			}

			//only add filter to post type you want
			if ('noo_job' == $type){
				global $post;

				// Company
				$companies = get_posts( array( 'post_type' => 'noo_company', 'posts_per_page' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );
				?>
				<select name="company">
					<option value=""><?php _e('All Companies', 'noo'); ?></option>
					<?php
					$current_v = isset($_GET['company'])? $_GET['company']:'';
					foreach ($companies as $company) {
						printf
						(
							'<option value="%s"%s>%s</option>',
							$company->ID,
							$company->ID == $current_v ? ' selected="selected"':'',
							$company->post_title
						);
					}
					?>
				</select>
				<?php
				// Job Category
				$job_categories = get_terms( 'job_category' );
				?>
				<select name="job_category">
					<option value=""><?php _e('All Categories', 'noo'); ?></option>
					<?php
					$current_v = isset($_GET['job_category'])? $_GET['job_category']:'';
					foreach ($job_categories as $job_category) {
						printf
						(
							'<option value="%s"%s>%s</option>',
							$job_category->slug,
							$job_category->slug == $current_v ? ' selected="selected"':'',
							$job_category->name
						);
					}
					?>
				</select>
				<?php
				// Job Location
				$job_locations = get_terms( 'job_location' );
				?>
				<select name="job_location">
					<option value=""><?php _e('All Locations', 'noo'); ?></option>
					<?php
					$current_v = isset($_GET['job_location'])? $_GET['job_location']:'';
					foreach ($job_locations as $job_location) {
						printf
						(
							'<option value="%s"%s>%s</option>',
							$job_location->slug,
							$job_location->slug == $current_v ? ' selected="selected"':'',
							$job_location->name
						);
					}
					?>
				</select>
				<?php
				// Job Type
				$job_types = get_terms( 'job_type' );
				?>
				<select name="job_type">
					<option value=""><?php _e('All Types', 'noo'); ?></option>
					<?php
					$current_v = isset($_GET['job_type'])? $_GET['job_type']:'';
					foreach ($job_types as $job_type) {
						printf
						(
							'<option value="%s"%s>%s</option>',
							$job_type->slug,
							$job_type->slug == $current_v ? ' selected="selected"':'',
							$job_type->name
						);
					}
					?>
				</select>
				<?php
			}
		}

		public function posts_filter( $query ){
			global $pagenow;
			$type = 'post';
			if (isset($_GET['post_type'])) {
				$type = $_GET['post_type'];
			}
			if ( 'noo_job' == $type && is_admin() && $pagenow=='edit.php' ) {
				if( !isset($query->query_vars['post_type']) || $query->query_vars['post_type'] == 'noo_job' ) {
					if( isset($_GET['company']) && $_GET['company'] != '') {
						$company_id = absint( $_GET['company'] );

						$employer = Noo_Company::get_employer_id( $company_id );
						if( !empty( $employer ) ) {
							$query->query_vars['author'] = $employer;
						}
					}
				}
			}
		}

		public function add_job_type_color(){
			wp_enqueue_style( 'wp-color-picker');
			wp_enqueue_script( 'wp-color-picker');
			?>
			<div class="form-field">
				<label><?php _e( 'Color', 'noo' ); ?></label>
				<input id="noo_job_type_color" type="text" size="40" value="" name="noo_job_type_color">
				<script type="text/javascript">
					jQuery(document).ready(function($){
					    $("#noo_job_type_color").wpColorPicker();
					});
				 </script>
			</div>
			<?php
		}
		
		public function edit_job_type_color($term, $taxonomy){
			wp_enqueue_style( 'wp-color-picker');
			wp_enqueue_script( 'wp-color-picker');
			$noo_job_type_colors = get_option('noo_job_type_colors');
			$color 	= isset($noo_job_type_colors[$term->term_id]) ? $noo_job_type_colors[$term->term_id] : '';
			?>
			<tr class="form-field">
				<th scope="row" valign="top"><label><?php _e('Color', 'noo'); ?></label></th>
				<td>
					<input id="noo_job_type_color" type="text" size="40" value="<?php echo esc_attr($color);?>" name="noo_job_type_color">
					<script type="text/javascript">
						jQuery(document).ready(function($){
						    $("#noo_job_type_color").wpColorPicker();
						});
					 </script>
				</td>
			</tr>
			<?php
		}
		
		public function save_location_geo_data($term_id, $tt_id, $taxonomy){
			if('job_location' === $taxonomy){
				$noo_job_geolocation = get_option('noo_job_geolocation');
				if ( ! $noo_job_geolocation )
					$noo_job_geolocation = array();
				
				$term = get_term($term_id, 'job_location');
				if($term && !is_wp_error($term) ){
					if(!isset($noo_job_geolocation[$term->slug])){
						$location_geo_data = self::get_job_geolocation($term->name);
						if($location_geo_data && !is_wp_error($location_geo_data)){
							$noo_job_geolocation[$term->slug] = $location_geo_data;
						}
					}
				}
			}
		}
		
		public function save_type_color($term_id, $tt_id, $taxonomy){
			if ( isset( $_POST['noo_job_type_color'] ) ){
				$noo_job_type_colors = get_option( 'noo_job_type_colors' );
				if ( ! $noo_job_type_colors )
					$noo_job_type_colors = array();
				$noo_job_type_colors[$term_id] = $_POST['noo_job_type_color'];
				update_option('noo_job_type_colors', $noo_job_type_colors);
			}
		}

		public function admin_menu() {
			add_submenu_page( 'edit.php?post_type=noo_job', __( 'Custom Fields', 'noo' ), __( 'Custom Fields', 'noo' ), 'edit_theme_options', 'job_custom_field', array( &$this, 'setting_custom_field' ) );
			add_submenu_page( 'edit.php?post_type=noo_job', __( 'Settings', 'noo' ), __( 'Settings', 'noo' ), 'edit_theme_options', 'manage_noo_job', array( &$this, 'setting_page' ) );
		}

		public function setting_page() {
			$current_tab     = empty( $_GET['tab'] ) ? 'general' : sanitize_title( $_GET['tab'] );
			$tabs = apply_filters( 'noo_job_settings_tabs_array', array(
				'general'=>__('Jobs','noo'),
				'application'=>__('Job Application','noo'),
				'email'=>__('Emails','noo'),
			));
			?>
			<div class="wrap">
				<form action="options.php" method="post">
					<h2 class="nav-tab-wrapper">
						<?php
							foreach ( $tabs as $name => $label )
								echo '<a href="' . admin_url( 'edit.php?post_type=noo_job&page=manage_noo_job&tab=' . $name ) . '" class="nav-tab ' . ( $current_tab == $name ? 'nav-tab-active' : '' ) . '">' . $label . '</a>';
						?>
					</h2>
					<?php 
					do_action( 'noo_job_setting_' . $current_tab );
					submit_button(__('Save Changes','noo'));
					?>
				</form>
			</div>
			<?php
		}
		
		public function setting_general(){
			if(isset($_GET['settings-updated']) && $_GET['settings-updated']) {
				flush_rewrite_rules();
			}
			$archive_slug = self::get_setting('noo_job_general', 'archive_slug');
			$job_approve = self::get_setting('noo_job_general', 'job_approve','');
			$cover_image = self::get_setting('noo_job_general', 'cover_image','');
			$default_job_content = self::get_setting('noo_job_general', 'default_job_content', '');
			$submit_agreement = self::get_setting('noo_job_general', 'submit_agreement', null);
			$job_posting_limit = self::get_setting('noo_job_general', 'job_posting_limit',5);
			$job_display_duration = self::get_setting('noo_job_general', 'job_display_duration',30);
			$job_feature_limit = self::get_setting('noo_job_general', 'job_feature_limit',1);

			?>
			<?php settings_fields('noo_job_general'); ?>
			<h3><?php echo __('Job Display','noo')?></h3>
			<table class="form-table" cellspacing="0">
				<tbody>
					<tr>
						<th>
							<?php esc_html_e('Job Archive base (slug)','noo')?>
						</th>
						<td>
							<input type="text" name="noo_job_general[archive_slug]" value="<?php echo ($archive_slug ? $archive_slug :'jobs') ?>">
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e('Job Display','noo')?>
						</th>
						<td>
							<p><?php 
							$customizer_job_link = esc_url( add_query_arg( array('autofocus%5Bpanel%5D' => 'noo_customizer_section_job'), admin_url( '/customize.php' ) ) );
							echo sprintf( __('Go to <a href="%s">Customizer</a> to change settings for Job(s) layout or displayed sections.','noo'), $customizer_job_link); ?></p>
						</td>
					</tr>
					<?php do_action( 'noo_setting_job_display_fields' ); ?>
				</tbody>
			</table>
			<br/><hr/><br/>
			<h3><?php echo __('Job Submission','noo')?></h3>
			<table class="form-table" cellspacing="0">
				<tbody>
					<tr>
						<th>
							<?php esc_html_e('Job Approval','noo')?>
						</th>
						<td>
							<input type="hidden" name="noo_job_general[job_approve]" value="">
							<input type="checkbox" <?php checked( $job_approve, 'yes' ); ?> name="noo_job_general[job_approve]" value="yes">
							<p><small><?php echo __('Each newly submitted job needs manual approve from Admin.','noo') ?></small></p>
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e('Enable Cover Image','noo')?>
						</th>
						<td>
							<input type="hidden" name="noo_job_general[cover_image]" value="">
							<input type="checkbox" <?php checked( $cover_image, 'yes' ); ?> name="noo_job_general[cover_image]" value="yes">
							<p><small><?php echo __('Allow Employers to change cover image for each job.','noo') ?></small></p>
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e('Default Job Content','noo')?>
							<p><small><?php echo __('Default content that will auto populated when Employers post new Job.','noo') ?></small></p>
						</th>
						<td>
							<?php
							$default_text = __('<h3>Job Description</h3><p>What is the job about? Enter the overall description of your job.</p>', 'noo');
							$default_text .= __('<h3>Benefits</h3><ul><li>What candidate can get from the position?</li><li>What candidate can get from the position?</li><li>What candidate can get from the position?</li></ul>', 'noo');
							$default_text .= __('<h3>Job Requirements</h3><ol><li>Detailed requirement for the vacancy.?</li><li>Detailed requirement for the vacancy.?</li><li>Detailed requirement for the vacancy.?</li></ol>', 'noo');
					        $default_text .= __('<h3>How To Apply</h3><p>How candidate can apply for your job. You can leave your contact information to receive hard copy application or any detailed guide for application.</p>', 'noo');

					        $text = !empty( $default_job_content ) ? $default_job_content : $default_text;
							
							$editor_id = 'textblock' . uniqid();
					        // add_filter( 'wp_default_editor', create_function('', 'return "tinymce";') );
					        wp_editor( $text, $editor_id, array(
					                    'media_buttons' => false,
					                    'quicktags' => true,
					                    'textarea_rows' => 15,
					                    'textarea_cols' => 80,
					                    'textarea_name' => 'noo_job_general[default_job_content]',
					                    'wpautop' => false)); ?>						
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e('Job submit condition','noo')?>
							<p><small><?php echo __('The condition that Employer must agree to before submitting a new job. Leave it blank for no condition.','noo') ?></small></p>
						</th>
						<td>
							<?php
							$submit_agreement = !is_null( $submit_agreement ) ? $submit_agreement : sprintf(__('Job seekers can find your job and contact you via email or %s regarding your application options. Preview all information thoroughly before submit your job for approval.','noo'), get_bloginfo('name') );
							?>	
							<textarea name="noo_job_general[submit_agreement]" rows="5" cols="80"><?php echo esc_html($submit_agreement); ?></textarea>					
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e('Job Limit','noo')?>
						</th>
						<td>
							<input type="text" name="noo_job_general[job_posting_limit]" value="<?php echo absint($job_posting_limit) ?>">
							<p><small><?php echo __('If you don\'t use Woocommerce Job Package for manage job submission, you can use this setting for limiting the number of jobs per employer.','noo') ?></small></p>
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e('Job Duration (day)','noo')?>
						</th>
						<td>
							<input type="text" name="noo_job_general[job_display_duration]" value="<?php echo absint($job_display_duration) ?>">
							<p><small><?php echo __('If you don\'t use Woocommerce Job Package for manage job submission, you can use this setting for job duration.','noo') ?></small></p>
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e('Job Featured Limit','noo')?>
						</th>
						<td>
							<input type="text" name="noo_job_general[job_feature_limit]" value="<?php echo absint($job_feature_limit) ?>">
							<p><small><?php echo __('If you don\'t use Woocommerce Job Package for manage job submission, you can use this setting for limiting the number of featured jobs per employer.','noo') ?></small></p>
						</td>
					</tr>
					<?php do_action( 'noo_setting_job_submission_fields' ); ?>
				</tbody>
			</table>
			<?php 
		}

		public static function get_default_fields() {
			$default_fields = array();
				// array(
				// 	'_salary' => array(
				// 		'name' => '_salary',
				// 		'label' => __('Salary', 'noo'),
				// 		'type' => 'select',
				// 		'value' => "0 ~ $1000\n$1000 ~ $3000\n$3000 ~ $10.000",
				// 		'std' => '',
				// 		'is_default' => 'yes',
				// 		'is_disabled' => 'yes',
				// 		'required' => 'true'
				// 	),
				// 	'_experience_required' => array(
				// 		'name' => '_experience_required',
				// 		'label' => __('Experience required', 'noo'),
				// 		'type' => 'text',
				// 		'value' => '',
				// 		'std' => '',
				// 		'is_default' => 'yes',
				// 		'is_disabled' => 'yes',
				// 		'required' => 'true'
				// 	),
				// );

			return apply_filters( 'noo_resume_default_fields', $default_fields );
		}
		
		public static function get_custom_fields_option($key = '', $default = null){
			$custom_fields = self::get_setting('noo_job_custom_field', array());
			
			if( !$custom_fields || !is_array($custom_fields) ) {
				return $custom_fields = array();
			}

			if( isset($custom_fields['__options__']) && isset($custom_fields['__options__'][$key]) ) {

				return $custom_fields['__options__'][$key];
			}
		
			return $default;
		}
		
		public function setting_custom_field(){
			?>
			<div class="wrap">
				<form action="options.php" method="post">
					<?php 
					setting_custom_field( 
						'noo_job_custom_field',
						self::get_default_fields(),
						noo_get_custom_fields( self::get_setting('noo_job_custom_field', array()), 'noo_jobs_custom_fields_' ),
						$this->get_custom_fields_option('display_position', 'after'),
						'noo_job_custom_field'
					);
					submit_button(__('Save Changes','noo'));
					?>
				</form>
			</div>
			<?php
		}
		
		public function setting_application(){
			$allow_register = Noo_Member::get_setting('allow_register', 'both');
			$custom_apply_link = self::get_setting('noo_job_linkedin', 'custom_apply_link' );
			$member_apply = self::get_setting('noo_job_linkedin', 'member_apply',self::get_setting('noo_job_general', 'member_apply','')); // moved from setting general
			$disable_member_upload = self::get_setting('noo_job_linkedin', 'disable_member_upload',self::get_setting('noo_job_general', 'member_upload','')); // moved from setting general

			$use_apply_with_linkedin = self::get_setting('noo_job_linkedin','use_apply_with_linkedin');
			$api_key = self::get_setting('noo_job_linkedin','api_key');
			$secret_key = self::get_setting('noo_job_linkedin','secret_key');
			$cover_letter_field = self::get_setting('noo_job_linkedin','cover_letter_field');
			$apply_job_using_captcha = self::get_setting('noo_job_linkedin','apply_job_using_captcha');
			?>
			<?php settings_fields('noo_job_linkedin'); ?>
			<h3><?php _e('General Options', 'noo'); ?></h3>
			<table class="form-table" cellpadding="0">
				<tbody>
					<tr>
						<th>
							<?php esc_html_e('Enable custom application link','noo')?>
						</th>
						<td>
							<fieldset>
								<label title="No"><input type="radio" <?php checked( $custom_apply_link, '' ); ?> name="noo_job_linkedin[custom_apply_link]" value=""><?php _e('No', 'noo'); ?></label><br/>
								<label title="For Admin"><input type="radio" <?php checked( $custom_apply_link, 'admin' ); ?> name="noo_job_linkedin[custom_apply_link]" value="admin"><?php _e('Yes, on the dashboard', 'noo'); ?></label><br/>
								<label title="For Employer"><input type="radio" <?php checked( $custom_apply_link, 'employer' ); ?> name="noo_job_linkedin[custom_apply_link]" value="employer"><?php _e('Yes, on both dashboard and frontend', 'noo'); ?></label><br/>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e('Limit application to member','noo')?>
						</th>
						<td>
							<input type="hidden" name="noo_job_linkedin[member_apply]" value="">
							<input class="member_apply" type="checkbox" <?php checked( $member_apply, 'yes' ); ?> name="noo_job_linkedin[member_apply]" value="yes">
							<p><small><?php echo __('Allow only logged in Candidates to apply for jobs.','noo') ?></small></p>
							<?php if( $member_apply == 'yes' && $allow_register != 'both' && $allow_register != 'candidate' ) : ?>
								<p><strong><?php echo sprintf( __('NOTE: <a href="%s" >You have not allowed candidate registration.</a>','noo'), admin_url( 'edit.php?post_type=noo_job&page=manage_noo_job&tab=member' ) ); ?></strong></p>
							<?php endif; ?>
						</td>
					</tr>
					<tr class="member_apply-child">
						<th>
							<?php esc_html_e('Disable file upload','noo')?>
						</th>
						<td>
							<input type="hidden" name="noo_job_linkedin[disable_member_upload]" value="">
							<input type="checkbox" <?php checked( $disable_member_upload, 'yes' ); ?> name="noo_job_linkedin[disable_member_upload]" value="yes">
							<p><small><?php echo __('Only allow candidates using submitted resumes to apply for jobs.','noo') ?></small></p>
						</td>
					</tr>
					<script>
						jQuery(document).ready(function($) {
							if( $("input.member_apply").is(":checked") ) {
								$('.member_apply-child').show();
							} else {
								$('.member_apply-child').hide();
							}

							$("input.member_apply").change( function() {
								if( $(this).is(":checked") ) {
									$('.member_apply-child').show();
								} else {
									$('.member_apply-child').hide();
								}
							} );
						});
					</script>
					<!-- Using captcha register -->
						<tr>
							<th>
								<?php _e('Enable Captcha Job Application','noo')?>
							</th>
							<td>
								<input type="checkbox" name="noo_job_linkedin[apply_job_using_captcha]" value="yes" <?php checked($apply_job_using_captcha,'yes')?>/>
								<small><?php _e('Simple Captcha function for preventing spam.', 'noo'); ?></small>
							</td>
						</tr>
					<!-- / Using captcha register -->

					<?php do_action( 'noo_setting_application_general_fields' ); ?>
				</tbody>
			</table>
			<br/><hr/><br/>
			<h3><?php echo __('Apply with LinkedIn','noo')?></h3>
			<table class="form-table" cellspacing="0">
				<tbody>
					<tr>
						<th>
							<?php esc_html_e('Allow apply with LinkedIn','noo')?>
						</th>
						<td>
							<input type="checkbox" name="noo_job_linkedin[use_apply_with_linkedin]" value="yes" <?php checked($use_apply_with_linkedin,'yes')?>>
						</td>
					</tr>
					<tr id="linkedin-app-api">
						<th>
							<?php esc_html_e('LinkedIn App API','noo')?>
						</th>
						<td>
							<input type="text" name="noo_job_linkedin[api_key]" value="<?php echo ($api_key ? $api_key :'') ?>" placeholder="<?php _e( 'Client ID', 'noo' ); ?>" size="50" >
							<p>
								<?php echo sprintf( __('<b>%s</b> requires that you create an application inside its framework to allow access from your website to their API.<br/> To know how to create this application, ', 'noo' ), 'LinkedIn' ); ?>
								<a href="javascript:void(0)" onClick="jQuery('#linkedin-help').toggle();return false;"><?php _e('click here and follow the steps.', 'noo'); ?></a>
							</p>
							<div id="linkedin-help" class="noo-setting-help" style="display: none; max-width: 1200px;" >
								<hr/>
								<br/>
								<?php _e('<em>Application ID</em> and <em>Secret</em> (also sometimes referred as <em>Consumer Key</em> and <em>Secret</em> or <em>Client ID</em> and <em>Secret</em>) are what we call an application credentials', 'noo') ?>. 
								<?php echo sprintf( __( 'This application will link your website <code>%s</code> to <code>%s API</code> and these credentials are needed in order for <b>%s</b> users to access your website', 'noo'), $_SERVER["SERVER_NAME"], 'LinkedIn', 'LinkedIn' ) ?>. 
								<br/>
								<br/>
								<?php echo sprintf( __('To register a new <b>%s API Application</b> and enable authentication, follow the steps', 'noo'), 'LinkedIn' ) ?>
								<br/>
								<?php $setupsteps = 0; ?>
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e( 'Go to', 'noo'); ?>&nbsp;<a href="https://www.linkedin.com/secure/developer" target ="_blank">https://www.linkedin.com/secure/developer</a></p>
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e('Select <b>Create Application</b> button', 'noo') ?>.</p> 
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e('Fill in required information then click <b>Submit</b> button', 'noo') ?>.</p> 
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e('Check on <em>r_emailaddress</em> as your <b>App permission</b> to get the email from your users', 'noo') ?> 
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e('Add your site URL to the <b>OAuth 2.0 - Authorized Redirect URLs</b>. It should match with the current site', 'noo') ?> <em><?php echo get_option('siteurl'); ?></em></p> 
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e('In the sidebar, choose Javascript then Add your domain to the <b>Valid SDK Domains</b>. It should match with the current domain', 'noo') ?> <em><?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER["SERVER_NAME"]; ?></em></p> 
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e('Go back to the <b>Authentication</b> tab, then copy the <em>Client ID</em>', 'noo') ?>.</p> 
								<p><b><?php echo ++$setupsteps; ?></b>. <?php _e('And paste the Client ID into the setting above', 'noo') ?>.</p> 
								<p>
									<b><?php _e("And that's it!", 'noo') ?></b> 
									<br />
									<?php echo __( 'For more reference, you can see: ', 'noo' ); ?><a href="https://developer.linkedin.com/docs/oauth2", target="_blank"><?php _e('LinkedIn Document', 'noo'); ?></a>, <a href="https://www.google.com/search?q=LinkedIn API create application" target="_blank"><?php _e('Google', 'noo'); ?></a>, <a href="http://www.youtube.com/results?search_query=LinkedIn API create application " target="_blank"><?php _e('Youtube', 'noo'); ?></a>
								</p> 
								<div style="margin-bottom:12px;" class="noo-thumb-wrapper">
									<a href="http://update.nootheme.com/wp-content/uploads/2015/09/linkedin_api_1.png" target="_blank"><img src="http://update.nootheme.com/wp-content/uploads/2015/09/linkedin_api_1.png"></a>
									<a href="http://update.nootheme.com/wp-content/uploads/2015/09/linkedin_api_2.png" target="_blank"><img src="http://update.nootheme.com/wp-content/uploads/2015/09/linkedin_api_2.png"></a>
									<a href="http://update.nootheme.com/wp-content/uploads/2015/09/linkedin_api_3.png" target="_blank"><img src="http://update.nootheme.com/wp-content/uploads/2015/09/linkedin_api_3.png"></a>
								</div> 
								<br/>
								<hr/>
							</div>
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e('Cover letter field','noo')?>
						</th>
						<td>
							<select class="regular-text" name="noo_job_linkedin[cover_letter_field]">
								<option <?php selected($cover_letter_field,'optional')?> value="optional"><?php esc_html_e('Optional','noo')?></option>
								<option <?php selected($cover_letter_field,'required')?>  value="required"><?php esc_html_e('Required','noo')?></option>
								<option <?php selected($cover_letter_field,'hidden')?>  value="hidden"><?php esc_html_e('Hidden','noo')?></option>
							</select>
						</td>
					</tr>

					<?php do_action( 'noo_setting_application_linkedin_fields' ); ?>
				</tbody>
			</table>
			<?php 
		}
		
		public function setting_email(){
			$noo_notify_job_submitted_admin = self::get_setting('noo_email','noo_notify_job_submitted_admin');

			$noo_notify_register_employer = self::get_setting('noo_email','noo_notify_register_employer');
			$noo_notify_job_submitted_employer = self::get_setting('noo_email','noo_notify_job_submitted_employer');
			$noo_notify_job_review_approve_employer = self::get_setting('noo_email','noo_notify_job_review_approve_employer');
			$noo_notify_job_review_reject_employer = self::get_setting('noo_email','noo_notify_job_review_reject_employer');
			$noo_notify_job_apply_employer = self::get_setting('noo_email','noo_notify_job_apply_employer');

			$noo_notify_job_apply_attachment = self::get_setting('noo_email','noo_notify_job_apply_attachment');

			$noo_notify_register_candidate = self::get_setting('noo_email','noo_notify_register_candidate');
			$noo_notify_job_apply_candidate = self::get_setting('noo_email','noo_notify_job_apply_candidate');
			$noo_notify_job_apply_approve_candidate = self::get_setting('noo_email','noo_notify_job_apply_approve_candidate');
			$noo_notify_job_apply_reject_candidate = self::get_setting('noo_email','noo_notify_job_apply_reject_candidate');
			$noo_notify_resume_submitted_candidate = self::get_setting('noo_email','noo_notify_resume_submitted_candidate');

			$blogname = get_option('blogname');
			$from_name = Noo_Job::get_setting( 'noo_email', 'from_name', $blogname );
			// $from_name = empty( $from_name ) ? $blogname : $from_name;
			$from_email = Noo_Job::get_setting( 'noo_email', 'from_email', '' );

			?>
			<?php settings_fields('noo_email'); ?>
			<h3><?php echo __('Email Options','noo')?></h3>
			<table class="form-table" cellspacing="0">
				<tbody>
					<tr>
						<th>
							<?php _e('Admin Emails','noo')?>
						</th>
						<td>
							<p><input type="checkbox" name="noo_email[noo_notify_job_submitted_admin]" value="disable" <?php checked($noo_notify_job_submitted_admin,'disable')?>>
							<small><?php _e( 'Disable email job submitted email.', 'noo' ); ?></small></p>
						</td>
					</tr>
					<tr>
						<th>
							<?php _e('Employer Emails','noo')?>
						</th>
						<td>
							<p><input type="checkbox" name="noo_email[noo_notify_register_employer]" value="disable" <?php checked($noo_notify_register_employer,'disable')?>>
							<small><?php _e( 'Disable registration email.', 'noo' ); ?></small></p>
							<p><input type="checkbox" name="noo_email[noo_notify_job_submitted_employer]" value="disable" <?php checked($noo_notify_job_submitted_employer,'disable')?>>
							<small><?php _e( 'Disable job submitted email.', 'noo' ); ?></small></p>
							<?php if( self::get_setting('noo_job_general', 'job_approve') == 'yes' ) : ?>
								<p><input type="checkbox" name="noo_email[noo_notify_job_review_approve_employer]" value="disable" <?php checked($noo_notify_job_review_approve_employer,'disable')?>>
								<small><?php _e( 'Disable job review approved email.', 'noo' ); ?></small></p>
								<p><input type="checkbox" name="noo_email[noo_notify_job_review_reject_employer]" value="disable" <?php checked($noo_notify_job_review_reject_employer,'disable')?>>
								<small><?php _e( 'Disable job review rejected email.', 'noo' ); ?></small></p>
							<?php endif; ?>
							<p><input type="checkbox" class="employer_apply_notify" name="noo_email[noo_notify_job_apply_employer]" value="disable" <?php checked($noo_notify_job_apply_employer,'disable')?>>
							<small><?php _e( 'Disable job application notification email.', 'noo' ); ?></small></p>
						</td>
					</tr>
					<tr>
						<th>
							<?php _e('Candidate Emails','noo')?>
						</th>
						<td>
							<p><input type="checkbox" name="noo_email[noo_notify_register_candidate]" value="disable" <?php checked($noo_notify_register_candidate,'disable')?>>
							<small><?php _e( 'Disable registration email.', 'noo' ); ?></small></p>
							<p><input type="checkbox" name="noo_email[noo_notify_job_apply_candidate]" value="disable" <?php checked($noo_notify_job_apply_candidate,'disable')?>>
							<small><?php _e( 'Disable job application notification email.', 'noo' ); ?></small></p>
							<p><input type="checkbox" name="noo_email[noo_notify_job_apply_approve_candidate]" value="disable" <?php checked($noo_notify_job_apply_approve_candidate,'disable')?>>
							<small><?php _e( 'Disable job application approved email.', 'noo' ); ?></small></p>
							<p><input type="checkbox" name="noo_email[noo_notify_job_apply_reject_candidate]" value="disable" <?php checked($noo_notify_job_apply_reject_candidate,'disable')?>>
							<small><?php _e( 'Disable job application rejected email.', 'noo' ); ?></small></p>
							<p><input type="checkbox" name="noo_email[noo_notify_resume_submitted_candidate]" value="disable" <?php checked($noo_notify_resume_submitted_candidate,'disable')?>>
							<small><?php _e( 'Disable resume subitted email.', 'noo' ); ?></small></p>
						</td>
					</tr>
					<tr class="employer_apply_notify-child">
						<th>
							<?php _e('Email Attachment','noo')?>
						</th>
						<td>
							<p><input type="checkbox" name="noo_email[noo_notify_job_apply_attachment]" value="enable" <?php checked($noo_notify_job_apply_attachment,'enable')?>>
							<small><?php _e( 'Include application attachment in Employer email.', 'noo' ); ?></small></p>
						</td>
					</tr>
					<script>
						jQuery(document).ready(function($) {
							if( $("input.employer_apply_notify").is(":checked") ) {
								$('.employer_apply_notify-child').hide();
							} else {
								$('.employer_apply_notify-child').show();
							}

							$("input.employer_apply_notify").change( function() {
								if( $(this).is(":checked") ) {
									$('.employer_apply_notify-child').hide();
								} else {
									$('.employer_apply_notify-child').show();
								}
							} );
						});
					</script>
				</tbody>
			</table>
			<hr/>
			<table class="form-table" cellspacing="0">
				<tbody>
					<tr>
						<th>
							<?php _e('From Email','noo')?>
						</th>
						<td>
							<input type="text" name="noo_email[from_email]" placeholder="<?php echo noo_mail_do_not_reply(); ?>" size="40" value="<?php echo esc_attr($from_email); ?>">
							<p><small><?php _e( 'The email address that emails on your site should be sent from. You should leave it blank if you used a 3rd plugin for sending email.', 'noo' ); ?></small></p>
						</td>
					</tr>
					<tr>
						<th>
							<?php _e('From Name','noo')?>
						</th>
						<td>
							<input type="text" name="noo_email[from_name]" placeholder="<?php echo get_option('blogname'); ?>" size="40" value="<?php echo esc_attr($from_name); ?>">
							<p><small><?php _e( 'The name that emails on your site should be sent from. You should leave it blank if you used a 3rd plugin for sending email.', 'noo' ); ?></small></p>
						</td>
					</tr>
					<?php do_action( 'noo_setting_email_fields' ); ?>
				</tbody>
			</table>
			<?php 
		}
		
		protected function _approve_job(){
			if(isset($_GET['action']) && $_GET['action'] == 'noo_job_approve'){
				if ( ! current_user_can( 'edit_posts' ) ) {
					wp_die( __( 'You do not have sufficient permissions to access this page.', 'noo' ), '', array( 'response' => 403 ) );
				}
		
				if ( ! check_admin_referer( 'noo-job-approve' ) ) {
					wp_die( __( 'You have taken too long. Please go back and retry.', 'noo' ), '', array( 'response' => 403 ) );
				}
		
				$post_id = ! empty( $_GET['job_id'] ) ? (int) $_GET['job_id'] : '';
		
				if ( ! $post_id || get_post_type( $post_id ) !== 'noo_job' ) {
					die;
				}
		
				$job_data = array(
					'ID'          => $post_id,
					'post_status' => 'publish'
				);
				wp_update_post( $job_data );
				wp_safe_redirect( esc_url_raw( remove_query_arg( array( 'trashed', 'untrashed', 'deleted', 'ids' ), wp_get_referer() ) ) );
				die();
			}
		}

		protected function _feature_job(){
			if(isset($_GET['action']) && $_GET['action'] == 'noo_job_feature'){
				if ( ! current_user_can( 'edit_posts' ) ) {
					wp_die( __( 'You do not have sufficient permissions to access this page.', 'noo' ), '', array( 'response' => 403 ) );
				}
		
				if ( ! check_admin_referer( 'noo-job-feature' ) ) {
					wp_die( __( 'You have taken too long. Please go back and retry.', 'noo' ), '', array( 'response' => 403 ) );
				}
		
				$post_id = ! empty( $_GET['job_id'] ) ? (int) $_GET['job_id'] : '';
		
				if ( ! $post_id || get_post_type( $post_id ) !== 'noo_job' ) {
					die;
				}
		
				$featured = noo_get_post_meta( $post_id, '_featured' );
		
				if ( 'yes' === $featured ) {
					update_post_meta( $post_id, '_featured', 'no' );
				} else {
					update_post_meta( $post_id, '_featured', 'yes' );
				}
		
		
				wp_safe_redirect( esc_url_raw( remove_query_arg( array( 'trashed', 'untrashed', 'deleted', 'ids' ), wp_get_referer() ) ) );
				die();
			}
		}
		
		protected function _insert_default_data(){
			if ( get_option( 'noo_job_insert_default_data' ) == '1' ) {
				return;
			}	
			$taxonomies = array(
				'job_type' => array(
					'Full Time',
					'Part Time',
					'Freelance',
					'Contract'
				)
			);
			$default_colors = array('#f14e3b', '#458cce', '#e6b707', '#578523');
			$job_type_colors = array();
			foreach ( $taxonomies as $taxonomy => $terms ) {
				foreach ( $terms as $index => $term ) {
					if ( ! get_term_by( 'slug', sanitize_title( $term ), $taxonomy ) ) {
						$result = wp_insert_term( $term, $taxonomy );
						if( !is_wp_error( $result ) ) {
							if( $taxonomy == 'job_type' ) {
								$job_type_colors[$result['term_id']] = $default_colors[$index];
							}
						}
					}
				}
				if( $taxonomy == 'job_type' ) {
					update_option( 'noo_job_type_colors', $job_type_colors );
				}
			}

			delete_option('noo_job_insert_default_data');
			update_option( 'noo_job_insert_default_data', '1' );
		}
		
		private function _create_cron_jobs(){
			if ( get_option( 'noo_job_cron_jobs' ) == '1' && ( wp_get_schedule( 'noo_job_check_expired_jobs' ) !== false ) ) {
				return;
			}
			wp_clear_scheduled_hook( 'noo_job_check_expired_jobs' );
			wp_schedule_event( time(), 'hourly', 'noo_job_check_expired_jobs' );
			
			delete_option('noo_job_cron_jobs');
			update_option( 'noo_job_cron_jobs', '1' );
		}
		
		public function check_expired_jobs(){
			global $wpdb;
			
			// Change status to expired
			$job_ids = $wpdb->get_col( $wpdb->prepare( "
				SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta
				LEFT JOIN {$wpdb->posts} as posts ON postmeta.post_id = posts.ID
				WHERE postmeta.meta_key = '_expires'
				AND postmeta.meta_value > 0
				AND postmeta.meta_value < %s
				AND posts.post_status = 'publish'
				AND posts.post_type = 'noo_job'
				", current_time( 'timestamp' ) ) );
			
			if ( $job_ids ) {
				foreach ( $job_ids as $job_id ) {
					$job_data       = array();
					$job_data['ID'] = $job_id;
					$job_data['post_status'] = 'expired';
					wp_update_post( $job_data );
				}
			}
		}
		
		public function noo_step_icon_shortcode($atts, $content = null){
			extract(shortcode_atts(array(
				'values'			=>"fa fa-key|1.Register an account to start|#,fa fa-search-plus|2. Specify & search your desired job|#,fa fa-file-text-o|3. Send your resume to employers|#",
				'visibility'        =>'',
				'class'             =>'',
				'id'                =>'',
				'custom_style'      =>''
			), $atts));
			
			$visibility       = ( $visibility      != ''     ) && ( $visibility != 'all' ) ? esc_attr( $visibility ) : '';
			$class            = ( $class           != ''     ) ? 'noo-step-icon clearfix' . esc_attr( $class ) : 'noo-step-icon clearfix';
			$class           .= noo_visibility_class( $visibility );
			
			$id    = ( $id    != '' ) ? ' id="' . esc_attr( $id ) . '"' : '';
			$class = ( $class != '' ) ? ' class="' . $class . '"' : '';
			
			$custom_style = ( $custom_style != '' ) ? ' style="' . $custom_style . '"' : '';
			
			$value_arr = explode( ",", $values );
			$value_data = array();
			foreach ($value_arr as $value){
				$new_value = array();
				$data = explode( "|", $value );
				$new_value['icon_class'] = isset($data[0]) ? $data[0] : 'fa fa-home';
				$new_value['title'] = isset($data[1]) ? $data[1] : __('Step Icon Title','noo');
				$new_value['link'] = isset($data[2]) ? $data[2]:'#';
				$value_data[] = $new_value;
			}
			ob_start();
			?>
			<div<?php echo ( $id. $class.$custom_style );?>>
				<ul class="noo-step-icon-<?php echo count($value_data)?>">
					<?php foreach ($value_data as $vd):?>
					<li>
						<span class="noo-step-icon-item">
							<a href="<?php echo esc_url($vd['link'])?>"><span class="<?php echo esc_attr($vd['icon_class'])?> noo-step-icon-class"></span><span class="noo-step-icon-title"><?php echo esc_html($vd['title'])?></span></a>
						</span>
					</li>
					<?php endforeach;?>
				</ul>
			</div>
			<?php
			return ob_get_clean();
		}
		
		public function noo_job_map_shortcode($atts, $content = null){
			$this->enqueue_map_script();
			
			extract(shortcode_atts(array(
				'map_height'		=>'700',
				'map_style'		=>'dark',
				'zoom'		=>'12',
				'center_latitude'	=>'40.714398',
				'center_longitude'	=>'-74.005279',
				'fit_bounds'	=>'yes',
				'search_form'		=>'yes',
				'show_keyword'		=>'yes',
				'pos2'                 =>'job_location',
				'pos3'                 =>'job_category',
				'pos4'				=>'no',
				'pos5'                 =>'job_location',
				'pos6'                 =>'job_category',
				'pos7'				=>'no',
				'pos8'                 =>'job_location',
				'pos9'                 =>'job_category',
				'pos10'				=>'no',
				'visibility'        =>'',
				'class'             =>'',
				'id'                =>'',
				'custom_style'      =>''
			), $atts));
			if( isset( $show_location ) ) {
				if( $show_location == 'yes' )
					$pos2 = 'job_location';
				else
					$pos2 = 'no';
			}
			if( isset( $job_category ) ) {
				if( $job_category == 'yes' )
					$pos3 = 'job_category';
				else
					$pos3 = 'no';
			}
			if( isset( $job_type ) ) {
				if( $job_type == 'yes' )
					$pos4 = 'job_type';
				else
					$pos4 = 'no';
			}

			$fields_count = 1;
			if( $show_keyword == 'yes' ) $fields_count++;
			if( $pos2 != 'no' ) $fields_count++;
			if( $pos3 != 'no' ) $fields_count++;
			if( $pos4 != 'no' ) $fields_count++;

			$visibility       = ( $visibility      != ''     ) && ( $visibility != 'all' ) ? esc_attr( $visibility ) : '';
			$class            = ( $class           != ''     ) ? 'noo-job-map ' . esc_attr( $class ) : 'noo-job-map';
			$class           .= noo_visibility_class( $visibility );

			$id    = ( $id    != '' ) ? 'id="' . esc_attr( $id ) . '"' : '';
			$class = ( $class != '' ) ? 'class="' . $class . '"' : '';
			
			$custom_style = ( $custom_style != '' ) ? 'style="' . $custom_style . '"' : '';

			ob_start();

			?>
			<div <?php echo ( $id . ' ' . $class . ' ' . $custom_style ); ?>>
				<div class="job-map">
					<div class="gmap-loading"><?php _e('Loading Maps','noo');?>
						<div class="gmap-loader">
							<div class="rect1"></div>
							<div class="rect2"></div>
							<div class="rect3"></div>
							<div class="rect4"></div>
							<div class="rect5"></div>
						</div>
					</div>
					<div id="gmap" data-map_style="<?php echo $map_style; ?>" data-latitude="<?php echo esc_html($center_latitude); ?>" data-longitude="<?php echo esc_html($center_longitude); ?>" data-zoom="<?php echo $zoom; ?>" data-fit_bounds="<?php echo $fit_bounds; ?>" style="height: <?php echo esc_attr($map_height); ?>px;" ></div>
					<div class="container-map-location-search">
						<i class="fa fa-search"></i>
						<input type="text" class="form-control" id="map-location-search" placeholder="<?php echo __('Search for a location...', 'noo'); ?>" autocomplete="off">
					</div>
				</div>
				<?php

				if( $search_form == 'yes' && $fields_count > 1 ) : ?>
				<div class="job-advanced-search column-<?php echo esc_attr($fields_count); ?>">
					<div class="job-advanced-search-wrap">
						<form method="get" class="form-inline" action="<?php echo esc_url( home_url( '/' ) );?>">
							<div class="job-advanced-search-form">
								<input type="hidden" value="noo_job" name="post_type">
								<?php if( $show_keyword == 'yes' ) : ?>
									<div class="form-group">
										<label class="sr-only" for="search-keyword"><?php _e('Keyword','noo')?></label>
										<input type="text" class="form-control" id="search-keyword" name="s" placeholder="<?php _e('Keyword','noo')?>">
									</div>
								<?php else : ?>
									<input type="hidden" value="" name="s">
								<?php endif; ?>
								<?php 
									self::advanced_search_field($pos2);
									self::advanced_search_field($pos3);
									self::advanced_search_field($pos4);
									self::advanced_search_field($pos5);
									self::advanced_search_field($pos6);
									self::advanced_search_field($pos7);
									self::advanced_search_field($pos8);
									self::advanced_search_field($pos9);
									self::advanced_search_field($pos10);
								?>
								<div class="form-group">
									<button type="submit" class="btn btn-primary btn-search-submit"><?php _e('Search','noo')?></button>
								</div>
							</div>
						</form>
					</div>
				</div>
				<?php endif;?>
			</div>
			<?php
			return ob_get_clean();
		}
		
		public static function advanced_search_field($field_val='', $is_resume = false, $tag_id = 'search-custom-field', $show_label = false ){
			global $wpdb;
			if(empty($field_val) || $field_val == 'no' )
				return '';
			$label_class = $show_label ? 'h5' : 'sr-only';
			switch ($field_val){
				case '_job_location':
				case 'job_location':
					$current_value = isset($_GET['location']) ? $_GET['location'] : '';
				?>
				<div class="form-group">
				    <label class="<?php echo $label_class; ?>" for="search-location"><?php _e('Location','noo')?></label>
				    <div class="advance-search-form-control">
						<select name="location" class="form-control-chosen form-control<?php if ( noo_get_option( 'noo_enable_rtl_support', 0 ) ) echo ' chosen-rtl'; ?>" id="search-location" data-placeholder="<?php echo __("Select Location",'noo'); ?>">
							<option class="text-placeholder" value=""><?php _e('All Location','noo')?></option>
							<?php 
							$locations = (array) get_terms('job_location', array('hide_empty' => !$is_resume));
							?>
							<?php foreach ($locations as $location):
								$value = $is_resume ? esc_attr($location->term_id) : esc_attr($location->slug);
							?>
								<option value="<?php echo $value?>" <?php selected( $current_value, $value ); ?> ><?php echo esc_html($location->name)?></option>
							<?php endforeach;?>
						</select>
					</div>
				 </div>
				<?php
				return;
				case '_job_category':
				case 'job_category':
					$current_value = isset($_GET['category']) ? $_GET['category'] : '';
				?>
				<div class="form-group">
				    <label class="<?php echo $label_class; ?>" for="search-category"><?php _e('Category','noo')?></label>
				    <div class="advance-search-form-control">
						<select name="category" class="form-control-chosen form-control<?php if ( noo_get_option( 'noo_enable_rtl_support', 0 ) ) echo ' chosen-rtl'; ?>" id="search-category" data-placeholder="<?php echo __("Select Category",'noo'); ?>">
							<option class="text-placeholder" value=""><?php _e('All Category','noo')?></option>
							<?php 
							$categories = (array) get_terms('job_category', array('hide_empty' => !$is_resume));
							?>
							<?php foreach ($categories as $category):
								$value = $is_resume ? esc_attr($category->term_id) : esc_attr($category->slug);
							?>
								<option value="<?php echo $value; ?>" <?php selected( $current_value, $value ); ?> ><?php echo esc_html($category->name)?></option>
							<?php endforeach;?>
						</select>
					</div>
				 </div>	
				<?php
				return;
				case 'job_type':
					$current_value = isset($_GET['type']) ? $_GET['type'] : '';
				?>
				<div class="form-group">
				    <label class="<?php echo $label_class; ?>" for="search-type"><?php _e('Type','noo')?></label>
				    <div class="advance-search-form-control">
						<select name="type"  class="form-control-chosen form-control<?php if ( noo_get_option( 'noo_enable_rtl_support', 0 ) ) echo ' chosen-rtl'; ?>" id="search-type" data-placeholder="<?php echo __("Select Type",'noo'); ?>">
							<option class="text-placeholder" value=""><?php _e('All Type','noo')?></option>
							<?php 
							$types = (array) get_terms('job_type');
							?>
							<?php foreach ($types as $type):?>
							<option value="<?php echo esc_attr($type->slug)?>" <?php selected( $current_value, $type->slug ); ?> ><?php echo esc_html($type->name)?></option>
							<?php endforeach;?>
						</select>
					</div>
				 </div>
				<?php
				return;
			}

			$default_fields = array();
			$custom_fields = array();
			$fields = array();
			$field_id = '';
			$field_label = '';
			$field_prefix = '';
			$post_type = '';
			// $tag_id = '';

			if( !$is_resume ) {
				$default_fields = Noo_Job::get_default_fields();
				$custom_fields = noo_get_custom_fields( self::get_setting('noo_job_custom_field', array()), 'noo_jobs_custom_fields_' );
				$fields = array_merge( array_diff_key($default_fields, $custom_fields), $custom_fields );
				$field_prefix = '_noo_job_field_';
				$post_type = 'noo_job';
			} else {
				$default_fields = Noo_Resume::get_default_fields();
				$custom_fields = noo_get_custom_fields( get_option( 'noo_resume' ), 'noo_resume_custom_fields_' );
				$fields = array_merge( array_diff_key($default_fields, $custom_fields), $custom_fields );
				$field_prefix = '_noo_resume_field_';
				$post_type = 'noo_resume';
			}

			if(array_key_exists($field_val, $default_fields)){
				$d = $default_fields[$field_val];
				$field_id = $d['name'];
				$field_label =$d['label'];
				// $tag_id = $post_type.'_search_'.$field_id;
			}else{
				$field_arr = explode('|', $field_val);
				$field_id = isset( $field_arr[0] ) ? $field_arr[0] : '';
				$field_label = isset( $field_arr[1] ) ? $field_arr[1] : '';
				// $tag_id = $post_type.'_search_'.$field_id;
			}

			if( empty( $field_id ) ) return '';

			$current_value = isset($_GET[$field_id]) ? $_GET[$field_id] : '';
			foreach ($fields as $name => $value) :
				if ( sanitize_title( $name ) == str_replace($field_prefix, '', $field_id) ) :
					$value['type'] = isset( $value['type'] ) ? $value['type'] : 'text';
					if ( $value['type'] == 'textarea' ) :

						?>
						<div class="form-group">
						    <label class="<?php echo $label_class; ?>" for="search-keyword"><?php echo esc_html($value['label']) ?></label>
						    <input type="text" class="form-control" id="<?php echo $field_id ?>" name="<?php echo $field_id ?>" placeholder="<?php echo esc_attr($value['value']); ?>" value="<?php echo esc_attr($current_value); ?>">
						 </div>
						<?php

					elseif ( $value['type'] == 'radio' || $value['type'] == 'checkbox' ) :
						$values = explode( "\n", $value['value'] ); ?>
						<label class="<?php echo $label_class; ?>"><?php echo esc_html($value['label']) ?></label>
						<div class="form-group">
							<?php foreach ($values as $option) :
									$option_key = explode( '|', $option );
									$option_key[0] = trim( $option_key[0] );
									$option_key[1] = isset( $option_key[1] ) ? $option_key[1] : $option_key[0];
								?>
									<div class="form-control-flat <?php echo esc_attr($option_key[0]); ?>">
										<label style="padding-left: 40px;" class="<?php echo $value['type']; ?>">
											<input style="position: fixed;" id="<?php echo $field_id ?>" name="<?php echo $field_id ?>" type="<?php echo $value['type']; ?>" value="<?php echo $option_key[0]; ?>" <?php checked($current_value, $option_key[0]); ?>/><i></i> 
											<?php echo $option_key[1]; ?>
										</label>
									</div>
							<?php endforeach; ?>
						</div>
						<?php
					elseif ( $value['type'] == 'select' || $value['type'] == 'multiple_select' ) :

						$values = explode( "\n", $value['value'] ); 

					?>
						<div class="form-group">
						    <label class="<?php echo $label_class; ?>" for="<?php echo $tag_id; ?>"><?php echo esc_html($field_label)?></label>
						    <div class="advance-search-form-control<?php if ( noo_get_option( 'noo_enable_rtl_support', 0 ) ) echo ' chosen-rtl'; ?>">
								<select name="<?php echo esc_attr($field_id)?>" id="<?php echo $tag_id; ?>" class="form-control-chosen form-control" data-placeholder="<?php echo sprintf(__("Select %s",'noo'), $field_label ); ?>">
									<option class="text-placeholder" value="">
										<?php _e('All','noo')?>
										<?php echo esc_html($field_label)?>
									</option>
									<?php foreach ($values as $option):
										$option_key = explode( '|', $option );
										$option_key[0] = trim( $option_key[0] );
										$option_key[1] = isset( $option_key[1] ) ? $option_key[1] : $option_key[0];
									?>
										<option value="<?php echo esc_attr($option_key[0])?>" <?php selected( $current_value, $option_key[0] ); ?>>
											<?php echo esc_html($option_key[1])?>
										</option>
									<?php endforeach;?>
								</select>
							</div>
						 </div>
						<?php
					else :

						$field_values = $wpdb->get_col(
							$wpdb->prepare('
								SELECT DISTINCT meta_value
								FROM %1$s
								LEFT JOIN %2$s ON %1$s.post_id = %2$s.ID
								WHERE meta_key = \'%3$s\' AND post_type = \'%4$s\' AND post_status = \'%5$s\'
								', $wpdb->postmeta, $wpdb->posts, $field_id, $post_type, 'publish'));

						// if( $field_values ) :
							?>
							<div class="form-group">
							    <label class="<?php echo $label_class; ?>" for="<?php echo $tag_id; ?>"><?php echo esc_html($field_label)?></label>
							    <div class="advance-search-form-control<?php if ( noo_get_option( 'noo_enable_rtl_support', 0 ) ) echo ' chosen-rtl'; ?>">
									<select name="<?php echo esc_attr($field_id)?>" id="<?php echo $tag_id; ?>" class="form-control-chosen form-control" data-placeholder="<?php echo sprintf(__("Select %s",'noo'), $field_label ); ?>">
										<option class="text-placeholder" value="">
											<?php _e('All','noo')?>
											<?php echo esc_html($field_label)?>
										</option>
										<?php foreach ($field_values as $field_value):
										if( empty( $field_value ) ) continue;
										?>
											<option value="<?php echo esc_attr($field_value)?>" <?php selected( $current_value, $field_value ); ?>>
												<?php echo esc_html($field_value)?>
											</option>
										<?php endforeach;?>
									</select>
								</div>
							 </div>
							<?php
						// endif;

					endif;

				endif;

			endforeach;
				
			return '';
		}
		
		public function noo_job_search_shortcode($atts, $content = null){
			wp_enqueue_style('vendor-chosen');
			wp_enqueue_script('vendor-chosen');
			extract(shortcode_atts(array(
				'top_title'            => __( 'JobMonster WordPress Theme', 'noo' ),
				'title'                => __( 'Join us & Explore thousands of Jobs', 'noo' ),
				'background_type'      =>'no_background',
				'slider_images'        =>'',
				'slider_animation'     =>'fade',
				'slider_time'          =>'3000',
				'slider_speed'         =>'600',
				'slider_height'        =>'600',
				'background_image'     =>'',
				'image_height_type'   => '',
				'image_height_custom' => '500',
				'search_type'          =>'noo_job',
				'search_mode'          =>'noo_horizontal',
				'show_keyword'         =>'yes',
				'r_pos2'               =>'job_location',
				'r_pos3'               =>'job_category',
				'r_pos4'               =>'job_location',
				'r_pos5'               =>'job_category',
				'r_pos6'               =>'job_location',
				'r_pos7'               =>'job_category',
				'r_pos8'               =>'job_category',
				'r_pos9'               =>'job_category',
				'r_pos10'               =>'no',
				'pos2'                 =>'job_location',
				'pos3'                 =>'job_category',
				'pos4'                 =>'job_location',
				'pos5'                 =>'job_category',
				'pos6'				   =>'job_category',
				'pos7'                 =>'job_location',
				'pos8'                 =>'job_category',
				'pos9'                 =>'job_category',
				'pos10'				=>'no',
				'search_position'	=>'120',
				'visibility'        =>'',
				'class'             =>'',
				'id'                =>'',
				'custom_style'      =>''
			), $atts));
			$fields_count = 1;
			if( $search_mode == 'noo_horizontal' ) {
				if( $show_keyword == 'yes' ) $fields_count++;
				if( $search_type == 'noo_resume' ) {
					if( $r_pos2 != 'no' ) $fields_count++;
					if( $r_pos3 != 'no' ) $fields_count++;
					if( $r_pos4 != 'no' ) $fields_count++;
					if( $r_pos5 != 'no' ) $fields_count++;
					if( $r_pos6 != 'no' ) $fields_count++;
					if( $r_pos7 != 'no' ) $fields_count++;
					if( $r_pos8 != 'no' ) $fields_count++;
					if( $r_pos9 != 'no' ) $fields_count++;
					if( $r_pos10 != 'no' ) $fields_count++;
				} else {
					if( $pos2 != 'no' ) $fields_count++;
					if( $pos3 != 'no' ) $fields_count++;
					if( $pos4 != 'no' ) $fields_count++;
					if( $pos5 != 'no' ) $fields_count++;
					if( $pos6 != 'no' ) $fields_count++;
					if( $pos7 != 'no' ) $fields_count++;
					if( $pos8 != 'no' ) $fields_count++;
					if( $pos8 != 'no' ) $fields_count++;
					if( $pos10 != 'no' ) $fields_count++;
				}
			}

			$visibility       = ( $visibility      != ''     ) && ( $visibility != 'all' ) ? esc_attr( $visibility ) : '';
			$class            = ( $class           != ''     ) ? esc_attr( $class ) : '';
			$class           .= noo_visibility_class( $visibility );

			$id    = ( $id    != '' ) ? esc_attr( $id ) : 'job-search-slider-' . noo_vc_elements_id_increment();
			$id_out = ( $id    != '' ) ? 'id="' . esc_attr( $id ) . '"' : '';
			if( $background_type == 'slider' && !empty( $slider_images ) ) {
				$custom_style .=';height:'.$slider_height.'px;';
			}
			$custom_style = ( $custom_style != '' ) ? 'style="' . $custom_style . '"' : '';
			if( $background_type == 'slider' ) {
				wp_enqueue_script( 'vendor-carouFredSel' );
			}
			if( $background_type == '' || $background_type == 'no_background' ) {
				$search_position = '';
				$class .= ' no-background';
			}
			ob_start();
			?>
			<div class="noo-job-search-wrapper <?php echo esc_attr( $class ); ?>" <?php echo ( $id_out . ' ' . $class . ' ' . $custom_style ); ?>>
				<?php if( $background_type == 'image' ) :
					$thumbnail = '';
					if ( !empty( $background_image ) ) {
						$thumbnail = wp_get_attachment_url($background_image);
					}
					$style_bg = 'style="';
					$style_bg .= 'background-image: url(' . esc_url($thumbnail) . ');';
					$style_bg .= 'height: ' . $image_height_custom . 'px';
					$style_bg .= '"';
				?>
				<div class="job-search-bg-image" <?php echo $style_bg; ?>></div>
					<?php if ( $image_height_type == 'noo_fullscreen' ) : ?>
					<script>
						jQuery('document').ready(function ($) {
							var navbar_height = $('.navbar').outerHeight();
							if( $('body').hasClass( 'admin-bar' ) ) {
								navbar_height += $( '#wpadminbar' ).outerHeight();
							}
							$('.job-search-bg-image').css( 'height', ( $(window).height() - navbar_height ) + 'px' );
						})
					</script>
					<?php endif;?>
				<?php endif; ?>
				<?php if( $background_type == 'slider' && !empty( $slider_images ) ) : ?>
				<div class="job-search-bg-slider" >
					<?php 
					$html  = array();

					$html[] = '  <ul class="sliders">';
					$images = explode(',', $slider_images);
					foreach ($images as $image) {
						$thumbnail = wp_get_attachment_url($image);
						$html[] ='<li><img alt="*" class="slide-image" src="' . $thumbnail . '"></li>';
					}
					$html[] = '  </ul>';
					$html[] = '  <div class="clearfix"></div>';

					// slider script
					$html[] = '<script>';
					$html[] = "jQuery('document').ready(function ($) {";
					$html[] = " $('#{$id} .sliders').each(function(){";
					$html[] = '  var _this = $(this);';
					$html[] = '  imagesLoaded(_this,function(){';
					$html[] = "   _this.carouFredSel({";
					$html[] = "    infinite: true,";
					$html[] = "    circular: true,";
					$html[] = "    responsive: true,";
					$html[] = "    debug : false,";
					$html[] = '    scroll: {';
					$html[] = '      items: 1,';
					$html[] = ( $slider_speed   != ''         ) ? '      duration: ' . $slider_speed . ',' : '';
					$html[] = '      pauseOnHover: "resume",';
					$html[] = '      fx: "' . $slider_animation . '"';
					$html[] = '    },';
					$html[] = '    items: {';
					$html[] = '      visible: 1';
					$html[] = '    },';
					$html[] = '    auto: {';
					$html[] = ( $slider_time    != ''     ) ? '      timeoutDuration: ' . $slider_time . ',' : '';
					$html[] = '      play: true';
					$html[] = '    }';
					$html[] = '   });';
					$html[] = '  });';
					$html[] = ' });';
					$html[] = '});';
					$html[] = '</script>';

					echo implode("\n", $html);
					?>
				</div>
				<?php
					if( !empty( $slider_height ) ) {
						$html[] = '<style type="text/css" media="screen">';
						$html[] = "  #{$id}.noo-slider .caroufredsel_wrapper .sliders .slide-item.noo-property-slide { max-height: {$slider_height}px; }";
						$html[] = '</style>';
					}
				?>
				<?php endif; ?>
				<div class="job-advanced-search <?php echo ( $search_mode == 'noo_vertical' ? ' vertical' : '' ); ?> column-<?php echo esc_attr($fields_count); ?>" <?php if( !empty( $search_position ) ) echo 'style="top: ' . absint($search_position) . 'px;"';?>>
					<div class="job-search-info text-center">
						<?php if( !empty( $top_title ) ) : ?>
							<p class="search-top-title"><?php echo ($top_title); ?></p>
						<?php endif; ?>
						<?php if( !empty( $title ) ) : ?>
							<h3 class="search-main-title"><?php echo ($title); ?></h3>
						<?php endif; ?>
					</div>
					<div class="job-advanced-search-wrap">
						<form method="get" class="form-inline" action="<?php echo esc_url( home_url( '/' ) );?>">
							<div class="job-advanced-search-form<?php if ( noo_get_option( 'noo_enable_rtl_support', 0 ) ) echo ' chosen-rtl'; ?>">
								<input type="hidden" value="<?php echo esc_attr($search_type)?>" name="post_type">
								<?php if( $show_keyword == 'yes' ) : ?>
								<div class="form-group">
								    <label class="sr-only" for="search-keyword"><?php _e('Keyword','noo')?></label>
								    <input type="text" class="form-control" id="search-keyword" name="s" placeholder="<?php _e('Keyword','noo')?>" value="<?php echo ( isset( $_GET['s'] ) ? esc_attr( $_GET['s'] ) : '' ); ?>">
								 </div>
								<?php else : ?>
								<input type="hidden" value="" name="s">
								<?php endif; ?>
								<?php 
								if($search_type == 'noo_job'):
									self::advanced_search_field($pos2);
									self::advanced_search_field($pos3);
									self::advanced_search_field($pos4);
									self::advanced_search_field($pos5);
									self::advanced_search_field($pos6);
									self::advanced_search_field($pos7);
									self::advanced_search_field($pos8);
									self::advanced_search_field($pos9);
									self::advanced_search_field($pos10);
								else:
									self::advanced_search_field($r_pos2, true);
									self::advanced_search_field($r_pos3, true);
									self::advanced_search_field($r_pos4, true);
									self::advanced_search_field($r_pos5, true);
									self::advanced_search_field($r_pos6, true);
									self::advanced_search_field($r_pos7, true);
									self::advanced_search_field($r_pos8, true);
									self::advanced_search_field($r_pos9, true);
									self::advanced_search_field($r_pos10, true);
								endif;
								?>
								  <div class="form-group form-action">
								  	<button type="submit" class="btn btn-primary btn-search-submit"><?php _e('Search','noo')?></button>
								  </div>
							</div>
						</form>
					</div>
				</div>
			</div>
			<?php
			return ob_get_clean();
		}
		
		public function noo_jobs_shortcode($atts, $content = null){
			extract(shortcode_atts(array(
				'title_type'             => 'text',
				'title'                  => '',
				'show'                   => 'featured',
				'show_pagination'        => 'yes',
				'show_autoplay'          => 'on',
				'posts_per_page'         => 3,
				'no_content'             => 'text',
				'job_category'           => 'all',
				'job_type'               => 'all',
				'job_location'           => 'all',
				'orderby'                => 'date',
				'order'                  => 'desc',
				'display_style'          => 'list',
				'show_view_more'         => 'yes'
			), $atts));
			$paged = 1;
			$atts['ajax_item'] = defined( 'DOING_AJAX' ) && DOING_AJAX && isset($_POST['action']) && $_POST['action'] === 'noo_nextajax';
			if($atts['ajax_item']){
				$paged = isset($_POST['page']) ? absint($_POST['page']) : 1;
				$posts_per_page = isset($_POST['posts_per_page']) ? absint($_POST['posts_per_page']) : $post_per_page;
				$show 	= isset($_POST['show']) ? $_POST['show'] : $show;
				$display_style = isset($_POST['display_style']) ? ($_POST['display_style']) : $display_style;
				$job_category = isset($_POST['job_category']) ? $_POST['job_category'] : $job_category;
				$job_type = isset($_POST['job_type']) ? $_POST['job_type'] : $job_type;
				$job_location = isset($_POST['job_location']) ? $_POST['job_location'] : $job_location;
				$orderby = isset($_POST['orderby']) ? $_POST['orderby'] : $orderby;
				$order = isset($_POST['order']) ? $_POST['order'] : $order;
				$show_view_more = isset($_POST['show_view_more']) ? $_POST['show_view_more'] : $show_view_more;
			}

			//  -- args query

				$args = array(
					'post_type'			  => 'noo_job',
					'post_status'         => 'publish',
					'paged'			  	  => $paged,
					'posts_per_page'	  => $posts_per_page,
					'ignore_sticky_posts' => true,
				);

			//  -- tax_query
			
				$args['tax_query'] = array( 'relation' => 'AND' );
				if ( $job_category != 'all' ) {
					$args['tax_query'][] = array(
						'taxonomy' => 'job_category',
						'field' => 'slug',
						'terms' => $job_category
					);
				}

				if ( $job_type != 'all' ) {
					$args['tax_query'][] = array(
						'taxonomy' => 'job_type',
						'field' => 'slug',
						'terms' => $job_type
					);
				}

				if ( $job_location != 'all' ) {
					$args['tax_query'][] = array(
						'taxonomy' => 'job_location',
						'field' => 'slug',
						'terms' => $job_location
					);
				}

			//  -- Check order by......
			
				if ( $orderby == 'view' ) {
					$args['orderby'] = 'meta_value_num';
					$args['meta_key'] = '_noo_views_count';
		 		} elseif ( $orderby == 'date' ) {
		 			$args['orderby'] = 'date';
		 		} else {
		 			$args['orderby'] = 'rand';
		 		}

		 	//  -- Check order
		 		if ( $orderby != 'rand' ) :
			 		if ( $order == 'asc' ) {
			 			$args['order'] = 'ASC';
			 		} else {
			 			$args['order'] = 'DESC';
			 		}
			 	endif;


			if($show == 'featured'){
				$args['meta_query'][] = array(
					'key'   => '_featured',
					'value' => 'yes'
				);
			}
			
			$r = new WP_Query( $args );
			ob_start();

			$atts['query'] = $r;
			$atts['item_class'] = 'nextajax-item';
			$atts['pagination'] = $show_pagination == 'yes' ? 1 : 0;
			$atts['paginate'] = 'nextajax';
			$atts['paginate_data'] = array(
					'posts_per_page'    => $posts_per_page,
					'job_category'      => $job_category,
					'job_type'          => $job_type,
					'job_location'      => $job_location,
					'orderby'      => $orderby,
					'order'        => $order,
				);
			$atts['show_view_more']    = $show_view_more;
			$atts['show_autoplay']     = $show_autoplay;
			$atts['display_style']     = $display_style;
			$atts['featured']    = $show == 'featured' ? 'featured' : 'recent';
			$atts['is_shortcode']    = true;
			self::loop_display($atts);
			$output = ob_get_clean();
			if($atts['ajax_item']){
				echo $output;
				die;
			}
			return $output;
		}
		
		/**
		 * Check use job package
		 * @return boolean
		 */
		
		public static function use_woocommerce_package(){
			return defined('WOOCOMMERCE_VERSION');
		}
		
		public static function get_application_method($post = null){
			$post = get_post( $post );
			if ( $post->post_type !== 'noo_job' )
				return;
			
			$method = new stdClass();
			$apply  = get_post_meta($post->ID,'_application_email',true);
			
			if ( empty( $apply ) )
				return false;
			
			if ( strstr( $apply, '@' ) && is_email( $apply ) ) {
				$method->type      = 'email';
				$method->raw_email = $apply;
				$method->email     = antispambot( $apply );
				$method->subject   = apply_filters( 'noo_job_application_email_subject', sprintf( __( 'Application via "%s" on %s', 'noo' ), $post->post_title, home_url() ), $post );
			} else {
				if ( strpos( $apply, 'http' ) !== 0 )
					$apply = 'http://' . $apply;
				$method->type = 'url';
				$method->url  = $apply;
			}
			
			return apply_filters( 'noo_job_get_application_method', $method, $post );
		}
		
		public static function get_job_locations($search_name = ''){
			$data   =   array();
			$args = array(
				'hide_empty'=>false,
			);
			if(!empty($search_name)){
				$args['name__like'] = $search_name;	
			}
			$locations = (array) get_terms('job_location',$args);
			foreach($locations as $location){
				$key = esc_attr($location->slug);
				$data[$key] =  $location->name;
			};
			return $data;
		}
		
		public function ajax_search_job_location(){
			$search_name = isset($_POST['term']) ? $_POST['term'] : '';
			$data = self::get_job_locations($search_name);
			wp_send_json($data);
		}
		
		public static function jobpackage_handler() {
			if( Noo_Job::use_woocommerce_package() ) {
				if( isset( $_GET['package_id'] ) || self::get_job_remain() > 0 ) {
					wp_safe_redirect(esc_url_raw(add_query_arg( 'action', 'post_job')));
				}
			}
			return;
		}
		
		public static function login_handler(){
			if(!self::need_login()){
				if(Noo_Job::use_woocommerce_package()) {
					wp_safe_redirect(esc_url_raw(add_query_arg( 'action', 'job_package')));
				}
				else {
					wp_safe_redirect(esc_url_raw(add_query_arg( 'action', 'post_job')));
				}
			}
			return;
		}
		
		public static function get_employer_package($employer_id=''){
			if(!self::use_woocommerce_package()){
				$package = array(
					'job_duration' => absint(self::get_setting('noo_job_general', 'job_display_duration',30)),
					'job_limit'    => absint(self::get_setting('noo_job_general', 'job_posting_limit',5)),
					'job_featured' => absint( self::get_setting('noo_job_general', 'job_feature_limit',1))
				);
				return $package;
			}
			if(empty($employer_id)){
				$employer_id = get_current_user_id();
			}
			$job_package = get_user_meta($employer_id, '_job_package', true);
			return $job_package;
		}

		public static function get_job_remain( $employer_id = '' ) {
			if(empty($employer_id)){
				$employer_id = get_current_user_id();
			}

			$package = self::get_employer_package( $employer_id );
			$job_limit = empty( $package ) || !is_array( $package ) || !isset( $package['job_limit'] ) ? 0 : $package['job_limit'];
			$job_added = self::get_job_added( $employer_id );

			return absint($job_limit) - absint($job_added);
		}

		public static function get_job_added( $employer_id = '' ) {
			if(empty($employer_id)){
				$employer_id = get_current_user_id();
			}

			$job_added = get_user_meta($employer_id,'_job_added',true);

			return empty( $job_added ) ? 0 : absint( $job_added );
		}

		public static function set_job_expires($job_id='') {
			if( empty( $job_id ) ) return false;

			$_ex = noo_get_post_meta($job_id,'_expires');
			$employer_id = get_post_field( 'post_author', $job_id );
			if(empty($_ex) && $package = Noo_Job::get_employer_package($employer_id)){
				$_expires = strtotime('+'.absint(@$package['job_duration']).' day');
				update_post_meta($job_id, '_expires', $_expires);
			}
		}

		public static function increase_job_count($employer_id='') {
			$employer_id = empty( $employer_id ) ? get_current_user_id() : $employer_id;
			if( empty( $employer_id ) ) return false;

			$_count = self::get_job_added( $employer_id );
			update_user_meta($employer_id, '_job_added', $_count + 1 );
		}

		public static function decrease_job_count($employer_id='') {
			$employer_id = empty( $employer_id ) ? get_current_user_id() : $employer_id;
			if( empty( $employer_id ) ) return false;

			$_count = self::get_job_added( $employer_id );
			update_user_meta($employer_id, '_job_added', max( 0, $_count - 1 ) );
		}
		
		/**
		 * 
		 * @param int $employer_id
		 * @param bool $is_paged
		 * @param bool $only_publish
		 * @return WP_Query
		 */
		public static function get_job_by_employer($employer_id='',$is_paged = true,$only_publish = false){
			if(empty($employer_id)){
				$employer_id = get_current_user_id();
			}
			
			if($is_paged){
				if( is_front_page() || is_home()) {
					$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : ( ( get_query_var( 'page' ) ) ? get_query_var( 'page' ) : 1 );
				} else {
					$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
				}
			}
			
			$args = array(
				'post_type'=>'noo_job',
				'author'=>$employer_id,
				'post_status'=>array('publish','pending','expired'),
			);
			if($is_paged){
				$args['paged'] = $paged;
			}
			if($only_publish){
				$args['post_status'] = array('publish');
			}
			$r = new WP_Query($args);
			return $r;
		}
		
		public static function get_employer_company($employer_id=''){
			if(empty($employer_id)){
				$employer_id = get_current_user_id();
			}
			return get_user_meta($employer_id,'employer_company',true);
		}

		public static function can_add_job($employer_id = ''){
			if(empty($employer_id)){
				$employer_id = get_current_user_id();
			}
			if( !Noo_Member::is_employer() ) return false;

			// Job posting with a package selected
			if( is_page() && ( 'page-post-job.php' === get_page_template_slug() ) && isset( $_GET['package_id'] ) ) {
				return true;
			}

			// Check the number of job added.
			return self::get_job_remain( $employer_id ) > 0;
		}

		/**
		 * Retrieve get feature job by employer
		 * 
		 * @param string $employer_id
		 * @return WP_Query
		 */
		public static function get_count_feature_job_by_employer($employer_id=''){
			if(empty($employer_id)){
				$employer_id = get_current_user_id();
			}
			return absint(get_user_meta( $employer_id, '_job_featured', true ));
		}
		
		public static function can_set_job_feature($employer_id = ''){
			return self::get_remain_job_feature($employer_id) > 0;
		}

		public static function get_remain_job_feature($employer_id = '') {
			if(empty($employer_id)){
				$employer_id = get_current_user_id();
			}
			$current_feature_count =  self::get_count_feature_job_by_employer($employer_id);
			// if(!self::use_woocommerce_package()){
			// 	$package = self::get_employer_package();
			// 	return max( absint(@$package['job_featured']) - absint($current_feature_count), 0 );
			// }
			$package = self::get_employer_package($employer_id);
			if( empty( $package ) || !isset( $package['job_featured'] ) ) {
				return 0;
			}
			
			return max( absint( $package['job_featured'] ) - absint($current_feature_count), 0 ) ;
		}

		public static function send_notification($job_id = null, $user_id = 0) {
			if( empty( $job_id ) ) {
				return false;
			}
			if( empty( $user_id ) ) $user_id = get_current_user_id();
			$job = get_post($job_id);
			if( empty( $job ) ) return;

			$current_user = get_userdata( $user_id );
			if( $current_user->ID != $job->post_author ) {
				return false;
			}

			$emailed = noo_get_post_meta( $job_id, '_new_job_emailed', 0 );
			if( $emailed ) {
				return false;
			}

			if ( is_multisite() )
				$blogname = $GLOBALS['current_site']->site_name;
			else
				$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
			
			$company = get_post(absint(Noo_Job::get_employer_company($current_user->ID)));
			
			$job_need_approve = Noo_Job::get_setting('noo_job_general', 'job_approve','yes' ) == 'yes';
			if( $job_need_approve ) {
				$job_link = esc_url( add_query_arg( 'job_id', get_the_ID(), Noo_Member::get_endpoint_url('preview-job') ) );
				
				//admin email
				$subject = sprintf(__('[%1$s] New job submitted: %2$s','noo'),$blogname,$job->post_title);
				$to = get_option('admin_email');
				$message = __('%1$s has just submitted a job for review.<br/><br/>
				<a href="%2$s">View Job Detail</a>.<br/><br/>

				Best regards,<br/>
				%3$s','noo');
				
				noo_mail($to, $subject, sprintf($message,$company->post_title,get_permalink($job_id),$blogname),array(),'noo_notify_job_submitted_admin');

				// employer email
				$subject = sprintf(__('[%1$s] You\'ve submitted job %2$s','noo'),$blogname,$job->post_title);
				
				$to = $current_user->user_email;
				$message = __('Hi %1$s,<br/><br/>
				You\'ve submitted a new job:<br/>
				<a href="%2$s">View Job Detail</a>.
				<br/><br/>
				We will review your job and replied back to you soon.<br/>
				You can manage and follow sattus of your jobs in <a href="%3$s">Manage Jobs</a><br/><br/>
				Best regards,<br/>
				%4$s','noo');
							
				noo_mail($to, $subject, sprintf($message,$current_user->display_name,$job_link,Noo_Member::get_endpoint_url('manage-job'),$blogname),array(),'noo_notify_job_submitted_employer');
			} else {
				$job_link = get_permalink($job_id);

				//admin email
				$subject = sprintf(__('[%1$s] New job posted: %2$s','noo'),$blogname,$job->post_title);
				$to = get_option('admin_email');
				$message = __('%1$s has just posted a job:<br/><br/>
				<a href="%2$s">View Job Detail</a>.
				<br/><br/>
				Best regards,<br/>
				%3$s','noo');
				
				noo_mail($to, $subject, sprintf($message,$company->post_title,$job_link,$blogname),array(),'noo_notify_job_submitted_admin');

				// employer email
				$subject = sprintf(__('[%1$s] You\'ve successfully post job %2$s','noo'),$blogname,$job->post_title);
				$to = $current_user->user_email;
				$message = __('Hi %1$s,<br/><br/>
				You\'ve successfully post a new job:<br/>
				<a href="%2$s">View Job Detail</a>.
				<br/><br/>
				You can manage your jobs in <a href="%3$s">Manage Jobs</a><br/><br/>
				Best regards,<br/>
				%4$s','noo');
				
				noo_mail($to, $subject, sprintf($message,$current_user->display_name,get_permalink($job_id),Noo_Member::get_endpoint_url('manage-job'),$blogname),array(),'noo_notify_job_submitted_employer');
			}

			update_post_meta( $job_id, '_new_job_emailed', 1 );
		}

		public static function get_job_type($post = null ){
			$post = get_post( $post );
			if ( $post->post_type !== 'noo_job' ) {
				return;
			}
			
			$types = wp_get_post_terms( $post->ID, 'job_type' );
			
			if ( !is_wp_error( $types ) && !empty( $types ) ) {
				$type = current( $types );

				$noo_job_type_colors = get_option('noo_job_type_colors');
				$type->color = isset($noo_job_type_colors[$type->term_id]) ? $noo_job_type_colors[$type->term_id] : '';
			} else {
				$type = false;
			}
			
			return apply_filters( 'get_job_type', $type, $post );
		}
		
		public static function get_job_status(){
			return apply_filters('noo_job_status', array(
				'draft'           => _x( 'Draft', 'Job status', 'noo' ),
				'expired'         => _x( 'Expired', 'Job status', 'noo' ),
				'preview'         => _x( 'Preview', 'Job status', 'noo' ),
				'pending'         => _x( 'Pending Approval', 'Job status', 'noo' ),
				'publish'         => _x( 'Active', 'Job status', 'noo' ),
			));
		}
		
		public static function is_page_post_job(){
			$page_temp = get_page_template_slug();
			return 'page-post-job.php' === $page_temp;
		}
		
		public static function need_login(){
			return !is_user_logged_in();
		}
		
		public function admin_enqueue_scripts(){
			if(get_post_type() === 'noo_job' || get_post_type() === 'noo_application' || (isset($_GET['page']) && ( $_GET['page']=='manage_noo_job' || $_GET['page']=='job_custom_field'))){
				wp_enqueue_style( 'noo-job', NOO_FRAMEWORK_ADMIN_URI . '/assets/css/noo_job.css');
				
				$custom_field_tmpl = '';
				$custom_field_tmpl.= '<tr>';
				$custom_field_tmpl.= '<td>';
				$custom_field_tmpl.= '<input type="text" value="" placeholder="'.esc_attr__('Field Name','noo').'" name="noo_job_custom_field[__i__][name]">';
				$custom_field_tmpl.= '</td>';
				$custom_field_tmpl.= '<td>';
				$custom_field_tmpl.= '<input type="text" value="" placeholder="'.esc_attr__('Field Label','noo').'" name="noo_job_custom_field[__i__][label]">';
				$custom_field_tmpl.= '</td>';
				$custom_field_tmpl.= '<td>';
				$custom_field_tmpl.= '<select name="noo_job_custom_field[__i__][type]">';
				$custom_field_tmpl.= '<option value="text">'.esc_attr__('Text','noo').'</option>';
				$custom_field_tmpl.= '<option value="number">'.esc_attr__('Number','noo').'</option>';
				$custom_field_tmpl.= '<option value="textarea">'.esc_attr__('Textarea','noo').'</option>';
				$custom_field_tmpl.= '<option value="select">'.esc_attr__('Select','noo').'</option>';
				$custom_field_tmpl.= '<option value="multiple_select">'.esc_attr__('Multiple Select','noo').'</option>';
				$custom_field_tmpl.= '<option value="radio">'.esc_attr__('Radio','noo').'</option>';
				$custom_field_tmpl.= '<option value="checkbox">'.esc_attr__('Checkbox','noo').'</option>';
				$custom_field_tmpl.= '</select>';
				$custom_field_tmpl.= '</td>';
				$custom_field_tmpl.= '<td>';
				$custom_field_tmpl.= '<textarea placeholder="'.esc_attr__('Field Value','noo').'" name="noo_job_custom_field[__i__][value]"></textarea>';
				$custom_field_tmpl.= '</td>';
				$custom_field_tmpl.= '<td>';
				$custom_field_tmpl.= '<input type="checkbox" name="noo_job_custom_field[__i__][required]" /> '.esc_attr__('Active','noo');
				$custom_field_tmpl.= '</td>';
				$custom_field_tmpl.= '<td>';
				$custom_field_tmpl.= '<input class="button button-primary" onclick="return delete_noo_job_custom_field(this);" type="button" value="'.esc_attr__('Delete','noo').'">';
				$custom_field_tmpl.= '</td>';
				$custom_field_tmpl.= '</tr>';

				$noojobL10n = array(
					'custom_field_tmpl'=>$custom_field_tmpl,
					'disable_text'=>__('Disable', 'noo'),
					'enable_text'=>__('Enable', 'noo'),
				);
				
				wp_register_script( 'noo-job', NOO_FRAMEWORK_ADMIN_URI . '/assets/js/noo_job.js', array( 'jquery'), null, true );
				wp_localize_script('noo-job', 'noojobL10n', $noojobL10n);
				wp_enqueue_script('noo-job');
			}
		}
		
		public function extend_job_status(){
			global $post, $post_type;
			if($post_type === 'noo_job'){
				$html = $selected_label = '';
				foreach ((array) self::get_job_status() as $status=>$label){
					$seleced = selected($post->post_status,esc_attr($status),false);
					if($seleced)
						$selected_label = $label;
					$html .= "<option ".$seleced." value='".esc_attr($status)."'>".$label."</option>";
				}  
				?>
				<script type="text/javascript">
					jQuery( document ).ready( function($) {
						<?php if ( ! empty( $selected_label ) ) : ?>
							jQuery( '#post-status-display' ).html( '<?php echo esc_js( $selected_label ); ?>' );
						<?php endif; ?>
						var select = jQuery( '#post-status-select' ).find( 'select' );
						jQuery( select ).html( "<?php echo ($html); ?>" );
					} );
				</script>
				<?php
			}
		}

		public function modified_views_status( $views ) {
			if( isset( $views['publish'] ) )
				$views['publish'] = str_replace( 'Published ', __('Active', 'noo') . ' ', $views['publish'] );

			return $views;
		}

		public function register_post_type() {
			if ( post_type_exists( 'noo_job' ) )
				return;
			
			$job_slug = self::get_setting('noo_job_general','archive_slug','jobs');
			
			register_post_type( 
				'noo_job', 
				array( 
					'labels' => array( 
						'name' => __( 'Jobs', 'noo' ), 
						'singular_name' => __( 'Job', 'noo' ), 
						'add_new' => __( 'Add New Job', 'noo' ), 
						'add_new_item' => __( 'Add Job', 'noo' ), 
						'edit' => __( 'Edit', 'noo' ), 
						'edit_item' => __( 'Edit Job', 'noo' ), 
						'new_item' => __( 'New Job', 'noo' ), 
						'view' => __( 'View', 'noo' ), 
						'view_item' => __( 'View Job', 'noo' ), 
						'search_items' => __( 'Search Job', 'noo' ), 
						'not_found' => __( 'No Jobs found', 'noo' ), 
						'not_found_in_trash' => __( 'No Jobs found in Trash', 'noo' ), 
						'parent' => __( 'Parent Job', 'noo' ) ),
					 
					'description'         => __( 'This is where you can add new job.', 'noo' ),
					'public'              => true,
					'menu_icon' 		  => 'dashicons-portfolio',
					'show_ui'             => true,
					'capability_type'     => 'post',
					'map_meta_cap'        => true,
					'publicly_queryable'  => true,
					'exclude_from_search' => false,
					'hierarchical'        => false, // Hierarchical jobs memory issues - WP loads all records!
					'rewrite'             => $job_slug ? array( 'slug' => untrailingslashit( $job_slug ), 'with_front' => false, 'feeds' => true ) : false,
					'query_var'           => true,
					'supports' 			  => noo_get_option( 'noo_job_comment', false ) ? array( 'title', 'editor', 'comments' ) : array( 'title', 'editor' ), 
					'has_archive'         => true,
					'show_in_nav_menus'   => true,
					'delete_with_user'	  => true,
					'can_export' 		  => true) );
			register_taxonomy( 
				'job_category', 
				'noo_job', 
				array( 
					'labels' => array( 
						'name' => __( 'Job Category', 'noo' ), 
						'add_new_item' => __( 'Add New Job Category', 'noo' ), 
						'new_item_name' => __( 'New Job Category', 'noo' ) ), 
					'hierarchical' => true, 
					'query_var' => true, 
					// 'capabilities'          => array(
					// 	'manage_terms' => 'manage_noo_job_terms',
					// 	'edit_terms'   => 'edit_noo_job_terms',
					// 	'delete_terms' => 'delete_noo_job_terms',
					// 	'assign_terms' => 'assign_noo_job_terms',
					// ),
					'rewrite' => array( 'slug' => 'job-category' ) ) );
			register_taxonomy( 
				'job_type', 
				'noo_job', 
				array( 
					'labels' => array( 
						'name' => __( 'Job Type', 'noo' ), 
						'add_new_item' => __( 'Add New Job Type', 'noo' ), 
						'new_item_name' => __( 'New Job Type', 'noo' ) ), 
					'hierarchical' => true, 
					'query_var' => true,
					// 'capabilities'          => array(
					// 	'manage_terms' => 'manage_noo_job_terms',
					// 	'edit_terms'   => 'edit_noo_job_terms',
					// 	'delete_terms' => 'delete_noo_job_terms',
					// 	'assign_terms' => 'assign_noo_job_terms',
					// ),
					'rewrite' => array( 'slug' => 'job-type' ) ) );
			register_taxonomy( 
				'job_location', 
				'noo_job', 
				array( 
					'labels' => array( 
						'name' => __( 'Job Location', 'noo' ), 
						'add_new_item' => __( 'Add New Job Location', 'noo' ), 
						'new_item_name' => __( 'New Job Location', 'noo' ) ), 
					'hierarchical' => true, 
					'query_var' => true, 
					// 'capabilities'          => array(
					// 	'manage_terms' => 'manage_noo_job_terms',
					// 	'edit_terms'   => 'edit_noo_job_terms',
					// 	'delete_terms' => 'delete_noo_job_terms',
					// 	'assign_terms' => 'assign_noo_job_terms',
					// ),
					'rewrite' => array( 'slug' => 'job-location' ) ) );
			
			register_post_status( 'expired',array( 
					'label' => _x( 'Expired', 'Job status', 'noo' ), 
					'public' => false, 
					'exclude_from_search' => false, 
					'show_in_admin_all_list' => true, 
					'show_in_admin_status_list' => true, 
					'label_count' => _n_noop( 'Expired <span class="count">(%s)</span>', 'Expired <span class="count">(%s)</span>', 'noo' ) 
			));
			register_post_status('preview',array(
					'label' => _x( 'Preview', 'Job status', 'noo' ),
					'public' => false,
					'exclude_from_search' => false,
					'show_in_admin_all_list' => true,
					'show_in_admin_status_list' => true,
					'label_count' => _n_noop('Preview <span class="count">(%s)</span>','Preview <span class="count">(%s)</span>','noo' ) 
			));
		}

		/**
		 * 
		 * @param WP_Query $query
		 */
		public function pre_get_posts($query) {
			if( is_admin() ) {
				return;
			}

			if( $query->is_main_query() && $query->is_singular ) {
				if( empty( $query->query_vars['post_status'] ) ) {
					// add expired to viewable link
					$post_status = array( 'publish', 'expired' );
					if( current_user_can( 'edit_posts' ) ) {
						$post_status[] = 'pending';
					}
					$query->set( 'post_status', $post_status );
				}

				return;
			}

			if ( isset($query->query_vars['post_type']) && ($query->query_vars['post_type'] === 'noo_job'  || $query->query_vars['post_type'] === 'noo_resume')) {
				// if($query->query_vars['post_type'] === 'noo_job'){
				// 	$query->set( 'orderby', 'meta_value date' );
				// 	$query->set( 'order', 'DESC' );
				// 	$query->set( 'meta_key', '_featured' );
				// }
				if( $query->is_search ) {
					
					$meta_query = array('relation' => 'AND');
					
					$tax_query = array('relation' => 'AND');
					if( isset( $_GET['location'] ) && $_GET['location'] !=''){
						$tax_query[] = array(
							'taxonomy'     => 'job_location',
							'field'        => 'slug',
							'terms'        => $_GET['location']
						);
						if($query->query_vars['post_type'] === 'noo_resume'){
							if($l = get_term_by('slug', $_GET['location'], 'job_location')){
								$_GET['_job_location']	= $l->term_id;
							}
						}
					}
					if( isset( $_GET['category'] ) && $_GET['category'] !=''){
						$tax_query[] = array(
							'taxonomy'     => 'job_category',
							'field'        => 'slug',
							'terms'        => $_GET['category']
						);
						if($query->query_vars['post_type'] === 'noo_resume'){
							if($c = get_term_by('slug', $_GET['category'], 'job_category')){
								$_GET['_job_category']	= $c->term_id;
							}
						}
					}
					if( isset( $_GET['type'] ) && $_GET['type'] !=''){
						$tax_query[] = array(
							'taxonomy'     => 'job_type',
							'field'        => 'slug',
							'terms'        => $_GET['type']
						);
						
					}

					if($query->query_vars['post_type'] === 'noo_job'){
						$query->tax_query->queries[] = $tax_query;
						$query->query_vars['tax_query'][] = $tax_query;
					}
					$get_keys = array_keys($_GET);
					
					$resume_default_fields = noo_get_custom_fields( self::get_setting('noo_job_custom_field', array()), 'noo_jobs_custom_fields_' );
					

					foreach ($get_keys as $get_key){
						if(strstr( $get_key, '_noo_job_field_' )){
							$value = $_GET[$get_key];
							if(!empty($value)){
								$meta_query[]	= array(
									'key'=>$get_key,
									'value'=>$value,
									'compare'=>'LIKE'
								);
							}
						}else{
							if(array_key_exists($get_key, $resume_default_fields) || strstr( $get_key, '_noo_resume_field_' )){
								$value = $_GET[$get_key];
								if(!empty($value)){
									$meta_query[]	= array(
										'key'=>$get_key,
										'value'=>$value,
										'compare'=>'LIKE'
									);
								}
							}
						}
							
					}
					$query->query_vars['meta_query'][] = $meta_query;
				}
			}
		}

		public function post_class($output) {

			$post_id = get_the_ID();
			if( 'noo_job' == get_post_type($post_id) ) {
				if( 'yes' == noo_get_post_meta( $post_id, '_featured', '' ) ) {
					$output[] = 'featured-job';
				}
			}
			
			return $output;
		}

		public function default_job_data($post_ID = 0, $post = null, $update = false) {

			if( !$update && !empty( $post_ID ) && $post->post_type == 'noo_job' ) {
				update_post_meta( $post_ID, '_featured', 'no' );
			}
		}


		private static $linkedin_script_loaded;

		public static function load_linkedin_script() {
			static $linkedin_script_loaded = false;

			if( $linkedin_script_loaded ) return;
			$linkedin_script_loaded = true;
			$protocol = is_ssl() ? 'https' : 'http';
			?>
			<script type="text/javascript" src="<?php echo $protocol; ?>://platform.linkedin.com/in.js">
				api_key: <?php echo Noo_Job::get_setting('noo_job_linkedin','api_key') ?>
			</script>
			<?php
		}

		public function job_site_schema( $schema = array() ) {
			if( is_singular( 'noo_job' ) ) {
				$schema['itemscope'] = '';
				$schema['itemtype'] = 'http://schema.org/JobPosting';
			}

			return $schema;
		}

		public function job_title_schema( $schema = array() ) {
			if( is_singular( 'noo_job' ) ) {
				$schema['itemprop'] = 'title';
			}

			return $schema;
		}

		public function fb_share_media() {
			if( !is_singular( 'noo_job' ) ) return;
			if( !noo_get_option('noo_job_social_facebook', true ) ) return;

			$job_id			= get_the_ID();
			$employer_id	= get_post_field( 'post_author', $job_id );
			$company_id		= self::get_employer_company( $employer_id );
			$thumbnail_id 	= noo_get_post_meta($company_id, '_logo', '');
			$social_share_img = wp_get_attachment_image_src( $thumbnail_id, 'full');
    		if( !empty( $social_share_img ) && isset($social_share_img[0]) ) : 
				?>
				<meta property="og:image" content="<?php echo $social_share_img[0]; ?>"/>
	    		<meta property="og:image:secure_url" content="<?php echo $social_share_img[0]; ?>" />
				<?php
			endif;
		}

		public static function social_share( $post_id = null, $title = '' ) {
			$post_id = (null === $post_id) ? get_the_id() : $post_id;
			if( get_post_type($post_id) != 'noo_job' ) return;

			$facebook     = noo_get_option('noo_job_social_facebook', true );
			$twitter      = noo_get_option('noo_job_social_twitter', true );
			$google		  = noo_get_option('noo_job_social_google', true );
			$pinterest    = noo_get_option('noo_job_social_pinterest', true );
			$linkedin     = noo_get_option('noo_job_social_linkedin', true );

			$share_url     = urlencode( get_permalink() );
			$share_title   = urlencode( get_the_title() );
			$share_source  = urlencode( get_bloginfo( 'name' ) );
			$share_content = urlencode( get_the_content() );
			$share_media   = wp_get_attachment_thumb_url( get_post_thumbnail_id() );
			$popup_attr    = 'resizable=0, toolbar=0, menubar=0, status=0, location=0, scrollbars=0';

			$html = array();

			if ( $facebook || $twitter || $google || $pinterest || $linkedin ) {
				$html[] = '<div class="job-social clearfix">';
				$html[] = '<span class="noo-social-title">';
				$html[] = empty( $title ) ? __("Share this job",'noo') : $title;
				$html[] = '</span>';
				if($facebook) {
					$html[] = '<a href="#share" class="noo-icon fa fa-facebook"'
							. ' title="' . __( 'Share on Facebook', 'noo' ) . '"'
									. ' onclick="window.open('
											. "'http://www.facebook.com/sharer.php?u={$share_url}&amp;t={$share_title}','popupFacebook','width=650,height=270,{$popup_attr}');"
											. ' return false;">';
					$html[] = '</a>';
				}

				if($twitter) {
					$html[] = '<a href="#share" class="noo-icon fa fa-twitter"'
							. ' title="' . __( 'Share on Twitter', 'noo' ) . '"'
									. ' onclick="window.open('
											. "'https://twitter.com/intent/tweet?text={$share_title}&amp;url={$share_url}','popupTwitter','width=500,height=370,{$popup_attr}');"
											. ' return false;">';
					$html[] = '</a>';
				}

				if($google) {
					$html[] = '<a href="#share" class="noo-icon fa fa-google-plus"'
							. ' title="' . __( 'Share on Google+', 'noo' ) . '"'
									. ' onclick="window.open('
									. "'https://plus.google.com/share?url={$share_url}','popupGooglePlus','width=650,height=226,{$popup_attr}');"
									. ' return false;">';
					$html[] = '</a>';
				}

				if($pinterest) {
					$html[] = '<a href="#share" class="noo-icon fa fa-pinterest"'
							. ' title="' . __( 'Share on Pinterest', 'noo' ) . '"'
									. ' onclick="window.open('
											. "'http://pinterest.com/pin/create/button/?url={$share_url}&amp;media={$share_media}&amp;description={$share_title}','popupPinterest','width=750,height=265,{$popup_attr}');"
											. ' return false;">';
					$html[] = '</a>';
				}

				if($linkedin) {
					$html[] = '<a href="#share" class="noo-icon fa fa-linkedin"'
							. ' title="' . __( 'Share on LinkedIn', 'noo' ) . '"'
									. ' onclick="window.open('
											. "'http://www.linkedin.com/shareArticle?mini=true&amp;url={$share_url}&amp;title={$share_title}&amp;summary={$share_content}&amp;source={$share_source}','popupLinkedIn','width=610,height=480,{$popup_attr}');"
											. ' return false;">';
					$html[] = '</a>';
				}

				$html[] = '</div>'; // .noo-social.social-share
			}

			echo implode("\n", $html);
		}
		
		public static function noo_contentjob_meta($args='') {
			$defaults=array(
				'show_company'=>true,
				'show_type'=>true,
				'show_location'=>true,
				'show_date'=>true,
				'show_closing_date'=>false,
				'show_category'=>false,
				'job_id'=>'',
				'schema'=>false
			);
			$args = wp_parse_args($args,$defaults);
			$args['job_id'] = empty($args['job_id']) ? get_the_ID() : $args['job_id'];

			$job = get_post($args['job_id']);
			$post_type = get_post_type();
			$company_name		= '';
			$company_id			= self::get_employer_company($job->post_author);
			$type 				= self::get_job_type( $args['job_id'] );
			$categories			= get_the_terms( $args['job_id'], 'job_category' );
			$locations			= get_the_terms( $args['job_id'], 'job_location' );
			$html = array();
			$html[] = '<p class="content-meta">';
			// Company Name
			if( $args['show_company'] && !empty( $company_id ) ) {
				$html[] = '<span class="job-company"> <a href="'.esc_url(get_permalink($company_id)).'">' . get_the_title( $company_id ) . '</a></span>';
			}
			// Job Type
			if( $args['show_type'] && !empty( $type ) ) {
				$schema = $args['schema'] ? ' employmentType="' . $type->name . '"' : '';
				$html[] = '<span class="job-type"' . $schema . '><a href="'.get_term_link($type,'job_type').'" style="color: '.$type->color.'"><i class="fa fa-bookmark"></i>' .$type->name. '</a></span>';
			}
			// Job Location
			$locations_html = '';
			$separator = ', ';
			if( $args['show_location'] && !empty( $locations ) ) {
				foreach ($locations as $location) {
					$schema = $args['schema'] ? ' name="' . $location->name . '"' : '';
					$locations_html .= '<a href="' . get_term_link($location->term_id,'job_location') . '"' . $schema . '><em>' . $location->name . '</em></a>' . $separator;
				}
				$schema = $args['schema'] ? ' itemscope itemtype="http://schema.org/LocalBusiness"' : '';
				$html[] = '<span class="job-location"' . $schema . '>';
				$html[] = '<i class="fa fa-map-marker"></i>';
				$html[] = trim($locations_html, $separator);
				$html[] = '</span>';
			}
			// Date
			if( $args['show_date'] || $args['show_closing_date'] ) {
				$html[] = '<span class="job-date">';
				$html[] = '<time class="entry-date" datetime="' . esc_attr(get_the_date('c', $args['job_id'])) . '">';
				$html[] = '<i class="fa fa-calendar"></i>';
				if( $args['show_date'] ) {
					$schema = $args['schema'] ? ' itemprop="datePosted"' : '';
					$html[] = '<span' . $schema . '>';
					$html[] = esc_html(get_the_date(get_option('date_format'), $args['job_id']));
					$html[] = '</span>';
				}
				$separator = ' - ';
				$closing_date = noo_get_post_meta($args['job_id'], '_closing', '');
				if( $args['show_closing_date'] && !empty( $closing_date )) {
					$html[] = '<span>';
					$html[] = ( $args['show_date'] ? $separator : '' ) . esc_html(date_i18n(get_option('date_format'), strtotime($closing_date)));
					$html[] = '</span>';
				}
				$html[] = '</time>';
				$html[] = '</span>';
			}
			// Category
			$categories_html = '';
			$separator = ' - ';
			if( $args['show_category'] && !empty( $categories ) ) {
				foreach ($categories as $category) {
					// echo($category->term_id); die;
					$categories_html .= '<a href="' . get_term_link($category->term_id, 'job_category') . '" title="' . esc_attr(sprintf(__("View all posts in: &ldquo;%s&rdquo;", 'noo') , $category->name)) . '">' . ' ' . $category->name . '</a>' . $separator;
				}
				$schema = $args['schema'] ? ' itemprop="occupationalCategory"' : '';
				$html[] = '<span class="job-category"' . $schema . '>';
				$html[] = '<i class="fa fa-folder"></i>';
				$html[] = trim($categories_html, $separator);
				$html[] = '</span>';
			}

			if ( is_singular( 'noo_job' ) ) :
			// -- Add button print
				$html[] = '<span>';
				$html[] = '<a href="javascript:void(0)" onclick="return window.print();"><i class="fa fa-print"></i> ' . __('Print', 'noo'). '</a>';
				$html[] = '</span>';
			endif;

			echo implode($html, "\n");
		}

		public static function display_detail($query=null,$in_preview=false){

			if(empty($query)){
				global $wp_query;
				$query = $wp_query;
			}

			while ($query->have_posts()): $query->the_post(); global $post;
			// Job's social info
			
			$job_id			= get_the_ID();
			$facebook		= noo_get_post_meta( $job_id, "_company_facebook", '' );
			$twitter		= noo_get_post_meta( $job_id, "_company_twitter", '' );
			$google_plus	= noo_get_post_meta( $job_id, "_company_google_plus", '' );
			$linkedin		= noo_get_post_meta( $job_id, "_company_linkedin", '' );
			$pinterest		= noo_get_post_meta( $job_id, "_company_pinterest", '' );
			$company_id		= self::get_employer_company($post->post_author);

			ob_start();
	        include(locate_template("layouts/noo_job-detail.php"));
	        echo ob_get_clean();
			
			endwhile;
			wp_reset_query();
		}

		public static function loop_display( $args = '' ) {
			$defaults = array( 
				'paginate'               =>'normal',
				'class'                  => '',
				'item_class'             =>'loadmore-item',
				'query'                  => '', 
				'title_type'             =>'text',
				'title'                  => '', 
				'pagination'             => 1, 
				'excerpt_length'         =>30,
				'posts_per_page'         =>'',
				'ajax_item'              =>false,
				'featured'               =>'recent',
				'is_shortcode'           =>false,
				'no_content'             =>'text',
				'display_style'          => 'list',
				'list_contentjob_meta'   => array(),
				'paginate_data'		 	 => array(),
				'show_view_more'         => 'yes',
				'show_autoplay'          => 'on',
				'in_related'             => false,
			);
			$loop_args = wp_parse_args($args,$defaults);
			extract($loop_args);

			global $wp_query;
			if(!empty($loop_args['query'])) {
				$wp_query = $loop_args['query'];
			}

			$content_meta = array();
			
			$content_meta['show_company'] = noo_get_option('noo_jobs_show_company_name', true);
			$content_meta['show_type'] = noo_get_option('noo_jobs_show_job_type', true);
			$content_meta['show_location'] = noo_get_option('noo_jobs_show_job_location', true);
			$content_meta['show_date'] = noo_get_option('noo_jobs_show_job_date', true);
			$content_meta['show_closing_date'] = noo_get_option('noo_jobs_show_job_closing', true);
			$content_meta['show_category'] = noo_get_option('noo_jobs_show_job_category', false);

			$list_contentjob_meta = array_merge($content_meta, $list_contentjob_meta);

			$paginate_data = apply_filters( 'noo-job-loop-paginate-data', $paginate_data, $loop_args );

			if( $is_shortcode ) $class .= ' jobs-shortcode';
			if( $display_style == 'slider' ) {
				$class .= ' slider';
				$paginate = '';
			}

			$item_class = array($item_class);
			$item_class[] ='noo_job';
			ob_start();

			if( $display_style !== 'slider' ) {
				include(locate_template("layouts/noo_job-loop.php"));
			} else {
				include(locate_template("layouts/noo_job-slider.php"));
			}
	       
	        echo ob_get_clean();
			wp_reset_postdata();
			wp_reset_query();

		}

		public static function get_bookmarked_job_ids( $user_id = 0 ) {
			if( empty($user_id) ) {
				$user_id = get_current_user_id();
			}

			if( empty($user_id) ) {
				return array();
			}

			$bookmarks = get_option("noo_bookmark_job_{$user_id}", array());
			if( empty( $bookmarks ) || !is_array( $bookmarks ) ) {
				return array();
			}

			return $bookmarks;
		}

		public static function set_bookmark_job( $user_id = 0, $job_id = 0 ) {
			if( empty($user_id) ) {
				$user_id = get_current_user_id();
			}

			if( empty($user_id) ) {
				return false;
			}

			if( empty($job_id) ) {
				$job_id = get_the_ID();
			}

			$job_id = absint( $job_id );

			// if( empty($job_id) || 'noo_job' != get_post_type( $job_id ) ) {
			// 	return false;
			// }

			$bookmarks = get_option("noo_bookmark_job_{$user_id}");
			if( empty( $bookmarks ) || !is_array( $bookmarks ) ) {
				$bookmarks = array();
			}

			if( isset( $bookmarks[$job_id] ) && $bookmarks[$job_id] == 1 ) {
				return true;
			} else {
				$bookmarks[$job_id] = 1;
			}
			return update_option("noo_bookmark_job_{$user_id}", $bookmarks);
		}

		public static function clear_bookmark_job( $user_id = 0, $job_id = 0 ) {
			if( empty($user_id) ) {
				$user_id = get_current_user_id();
			}

			if( empty($user_id) ) {
				return false;
			}

			if( empty($job_id) ) {
				$job_id = get_the_ID();
			}

			$job_id = absint( $job_id );

			// if( empty($job_id) || 'noo_job' != get_post_type( $job_id ) ) {
			// 	return false;
			// }

			$bookmarks = get_option("noo_bookmark_job_{$user_id}", array());
			if( empty( $bookmarks ) || !is_array( $bookmarks ) ) {
				$bookmarks = array();
			}

			if( !isset($bookmarks[$job_id]) ) {
				return true;
			}

			unset($bookmarks[$job_id] );
			return update_option("noo_bookmark_job_{$user_id}", $bookmarks);
		}
		
		public static function geolocation_enabled(){
			return apply_filters( 'noo_job_geolocation_enabled', true );	
		}
		

		public static function get_job_geolocation($raw_address){
			$invalid_chars = array( " " => "+", "," => "", "?" => "", "&" => "", "=" => "" , "#" => "" );
			$raw_address   = trim( strtolower( str_replace( array_keys( $invalid_chars ), array_values( $invalid_chars ), $raw_address ) ) );
		
			if ( empty( $raw_address ) ) {
				return false;
			}
		
			$transient_name              = 'geocode_' . md5( $raw_address );
			$geocoded_address            = get_transient( $transient_name );
			$jm_geocode_over_query_limit = get_transient( 'jm_geocode_over_query_limit' );
		
			// Query limit reached - don't geocode for a while
			if ( $jm_geocode_over_query_limit && false === $geocoded_address ) {
				return false;
			}
		
			try {
				if ( false === $geocoded_address || empty( $geocoded_address->results[0] ) ) {
					$result = wp_remote_get(
						apply_filters( 'noo_job_geolocation_endpoint', "http://maps.googleapis.com/maps/api/geocode/json?address=" . $raw_address . "&sensor=false&region=" . apply_filters( 'noo_job_geolocation_region_cctld', '', $raw_address ), $raw_address ),
						array(
							'timeout'     => 60,
							'redirection' => 1,
							'httpversion' => '1.1',
							'user-agent'  => 'NooJob; ' . home_url( '/' ),
							'sslverify'   => false
						)
					);
					if ( ! is_wp_error( $result ) && $result['body'] ) {
						$result           = wp_remote_retrieve_body( $result );
						$geocoded_address = json_decode( $result );
			
						if ( $geocoded_address->status ) {
							switch ( $geocoded_address->status ) {
								case 'ZERO_RESULTS' :
									throw new Exception( __( "No results found", 'noo' ) );
									break;
								case 'OVER_QUERY_LIMIT' :
									set_transient( 'jm_geocode_over_query_limit', 1, HOUR_IN_SECONDS );
									throw new Exception( __( "Query limit reached", 'noo' ) );
									break;
								case 'OK' :
									if ( ! empty( $geocoded_address->results[0] ) ) {
										set_transient( $transient_name, $geocoded_address, 24 * HOUR_IN_SECONDS * 365 );
									} else {
										throw new Exception( __( "Geocoding error", 'noo' ) );
									}
									break;
								default :
									throw new Exception( __( "Geocoding error", 'noo' ) );
									break;
							}
						} else {
							throw new Exception( __( "Geocoding error", 'noo' ) );
						}
					}else {
						throw new Exception( __( "Geocoding error", 'noo' ) );
					}
				}
			} catch ( Exception $e ) {
				return new WP_Error( 'error', $e->getMessage() );
			}
		
			$address                      = array();
			$address['lat']               = sanitize_text_field( $geocoded_address->results[0]->geometry->location->lat );
			$address['long']              = sanitize_text_field( $geocoded_address->results[0]->geometry->location->lng );
			$address['formatted_address'] = sanitize_text_field( $geocoded_address->results[0]->formatted_address );
		
			if ( ! empty( $geocoded_address->results[0]->address_components ) ) {
				$address_data             = $geocoded_address->results[0]->address_components;
				$street_number            = false;
				$address['street']        = false;
				$address['city']          = false;
				$address['state_short']   = false;
				$address['state_long']    = false;
				$address['zipcode']       = false;
				$address['country_short'] = false;
				$address['country_long']  = false;
		
				foreach ( $address_data as $data ) {
					switch ( $data->types[0] ) {
						case 'street_number' :
							$address['street']        = sanitize_text_field( $data->long_name );
							break;
						case 'route' :
							$route = sanitize_text_field( $data->long_name );
		
							if ( ! empty( $address['street'] ) )
								$address['street'] = $address['street'] . ' ' . $route;
							else
								$address['street'] = $route;
							break;
						case 'sublocality_level_1' :
						case 'locality' :
							$address['city']          = sanitize_text_field( $data->long_name );
							break;
						case 'administrative_area_level_1' :
							$address['state_short']   = sanitize_text_field( $data->short_name );
							$address['state_long']    = sanitize_text_field( $data->long_name );
							break;
						case 'postal_code' :
							$address['postcode']      = sanitize_text_field( $data->long_name );
							break;
						case 'country' :
							$address['country_short'] = sanitize_text_field( $data->short_name );
							$address['country_long']  = sanitize_text_field( $data->long_name );
							break;
					}
				}
			}
		
			return $address;
		}

		public static function is_bookmarked( $user_id = 0, $job_id = 0 ) {
			if( empty($user_id) ) {
				$user_id = get_current_user_id();
			}

			if( empty($user_id) ) {
				return false;
			}

			if( empty($job_id) ) {
				$job_id = get_the_ID();
			}

			if( empty($job_id) || 'noo_job' != get_post_type( $job_id ) ) {
				return false;
			}

			$job_id = absint( $job_id );

			$bookmarks = get_option("noo_bookmark_job_{$user_id}", array());

			if( empty( $bookmarks ) || !is_array( $bookmarks ) ) {
				return false;
			}

			return ( isset($bookmarks[$job_id]) && !empty($bookmarks[$job_id]) );
		}

		/* -------------------------------------------------------
		 * Create functions ralated_jobs
		 * ------------------------------------------------------- */
			
		public static function related_jobs( $job_id, $title ) {
			global $wp_query;
			//  -- args query
				// $terms = get_the_terms( $post->ID , 'noo_job');

				$args = array(
					'post_type'      => 'noo_job',
					'post_status'    => 'publish',
					// 'tag__in'        => array($first_tag),
					'posts_per_page' => (int) noo_get_option( 'noo_job_related_num', 3 ),
					'post__not_in'   => array($job_id)
				);

			//  -- tax_query
			
				$job_categorys = wp_get_post_terms( $job_id, 'job_category' );
				$job_types = wp_get_post_terms( $job_id, 'job_type' );
				$job_locations = wp_get_post_terms( $job_id, 'job_location' );


				$args['tax_query'] = array( 'relation' => 'AND' );
				if ( $job_categorys ) {
					$term_job_category = array(); 
					foreach ($job_categorys as $job_category) {
						$term_job_category = array_merge( $term_job_category, (array) $job_category->slug );
					}
					$args['tax_query'][] = array(
						'taxonomy' => 'job_category',
						'field' => 'slug',
						'terms' => $term_job_category
					);
				}

				if ( $job_types ) {
					$term_job_type = array();
					foreach ($job_types as $job_type) {
						$term_job_type = array_merge( $term_job_type, (array) $job_type->slug );
					}
					$args['tax_query'][] = array(
						'taxonomy' => 'job_type',
						'field' => 'slug',
						'terms' => $term_job_type
					);
				}

				if( $job_locations ) {
					$term_job_location = array(); 
					foreach ($job_locations as $job_location) {
						$term_job_location = array_merge( $term_job_location, (array) $job_location->slug );
					}
					$args['tax_query'][] = array(
						'taxonomy' => 'job_location',
						'field' => 'slug',
						'terms' => $term_job_location
					);
				}

			// --- new query
				$wp_query = new WP_Query( $args );

				$loop_args = array( 
					'title'				   => $title,
					'paginate'             =>null,
					'class'          	   =>'related-jobs hidden-print',
					'item_class'           =>'',
					'query'                => $wp_query, 
					'pagination'           => false, 
					'ajax_item'            => null,
					'featured'             =>'recent',
					'is_shortcode'         => false,
					'no_content'           =>'none',
					'display_style'        => '',
				);

				self::loop_display( $loop_args );

		}
		/** ====== END ralated_jobs ====== **/

}
new Noo_Job();
endif;