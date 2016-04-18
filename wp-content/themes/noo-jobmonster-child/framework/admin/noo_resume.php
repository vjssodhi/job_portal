<?php
if(!class_exists('Noo_Resume')):
	if( !class_exists('Noo_CPT') ) {
		require_once dirname(__FILE__) . '/noo_cpt.php';
	}
	class Noo_Resume extends Noo_CPT {

		static $instance = false;
		
		public static function get_setting( $id, $default = null ) {
			$options = get_option('noo_resume_general');
			if (isset($options[$id])) {
				return $options[$id];
			}
			return $default;
		}

		public static function enable_resume_detail() {
			$education = self::get_setting('enable_education', '1');
			$experience = self::get_setting('enable_experience', '1');
			$skill = self::get_setting('enable_skill', '1');

			return $education || $experience || $skill;
		}

		public static function notify_candidate( $resume_id = null ) {
			if( !Noo_Member::is_logged_in() || empty( $resume_id ) ) {
				return false;
			}

			global $current_user;
			get_currentuserinfo();
			if( !Noo_Member::is_resume_owner( $current_user->ID, $resume_id ) ) {
				return false;
			}

			$emailed = noo_get_post_meta( $resume_id, '_new_resume_emailed', 0 );
			if( $emailed ) {
				return false;
			}

			$candidate_email = $current_user->user_email;
			$resume = get_post($resume_id);
			$job_location = noo_get_post_meta($resume_id, '_job_location');
			if( !empty( $job_location ) ) {
				$job_location = noo_json_decode($job_location);
				$job_location_terms = empty( $job_location ) ? array() : get_terms( 'job_location', array('include' => $job_location, 'hide_empty' => 0, 'fields' => 'names') );
				$job_location = implode(', ', $job_location_terms);
			}
			$job_category = noo_get_post_meta($resume_id, '_job_category');
			if( !empty( $job_category ) ) {
				$job_category = noo_json_decode($job_category);
				$job_category_terms = empty( $job_category ) ? array() : get_terms( 'job_category', array('include' => $job_category, 'hide_empty' => 0, 'fields' => 'names') );
				$job_category = implode(', ', $job_category_terms);
			}

			//candidate email
			$blogname = get_bloginfo( 'name' );
			$to = $current_user->user_email;

			$subject = sprintf(__('You\'ve posted a resume: %1$s','noo'),get_the_title($resume_id));
			$message = __( 'Hi %1$s,
<br/><br/>
You\'ve posted a new resume:<br/>
Title: %2$s<br/>
Location: %3$s<br/>
Category: %4$s<br/>
<br/><br/>
You can manage your resumes in <a href="%5$s">Manage Resume</a>.
<br/><br/>
Best regards,<br/>
%6$s','noo');
			noo_mail($to, $subject, sprintf($message,$current_user->display_name,get_the_title($resume_id),$job_location,$job_category,Noo_Member::get_endpoint_url('manage-resume'),$blogname),array(),'noo_notify_resume_submitted_candidate');
				
			update_post_meta( $resume_id, '_new_resume_emailed', 1 );
		}

		public function __construct(){

			$this->post_type = 'noo_resume';
			$this->slug = 'resumes';
			$this->prefix = 'resume';
			$this->option_key = 'noo_resume';

			$this->setting_title = __('Resume Settings', 'noo');

			// parent::__construct();

			add_action( 'init', array( &$this, 'register_post_type' ),0 );
			add_filter( 'template_include', array( $this, 'template_loader' ) );
			add_action('pre_get_posts', array(&$this,'pre_get_posts'));
			add_filter( 'query_vars', array( &$this, 'add_query_vars') );

			if( is_admin() ) {
				add_action('admin_init', array(&$this,'admin_init'));

				add_action('admin_menu', array(&$this,'admin_menu'));
				add_filter('noo_job_settings_tabs_array', array(&$this,'add_setting_resume_tab'), 99);
				add_action('noo_job_setting_resume', array(&$this,'setting_general'));

				add_action( 'add_meta_boxes', array(&$this, 'remove_meta_boxes' ), 20 );
				add_action( 'add_meta_boxes', array(&$this, 'add_meta_boxes' ), 30 );

				add_filter( 'enter_title_here', array (&$this,'custom_enter_title') );
				add_action( 'admin_enqueue_scripts', array (&$this,'enqueue_style_script') );

				add_filter('manage_edit-' . $this->post_type . '_columns', array(&$this, 'manage_edit_columns'));
				add_action('manage_posts_custom_column', array(&$this, 'manage_posts_custom_column'));

				// Ajax for viewable resume
				add_action( 'wp_ajax_noo_viewable_resume', array(&$this, 'ajax_viewable') );
			
				// Admin Filter
				add_filter('months_dropdown_results', '__return_empty_array');
				add_action( 'restrict_manage_posts', array(&$this, 'restrict_manage_posts') );
				add_filter( 'parse_query', array(&$this, 'posts_filter') );

				add_filter( 'noo_sanitize_meta__education_note', array(&$this, 'sanitize_html_list_value') );
				add_filter( 'noo_sanitize_meta__experience_note', array(&$this, 'sanitize_html_list_value') );
			}

			add_shortcode('noo_resume', array(&$this,'noo_resume_shortcode'));

			add_action('wp_ajax_nopriv_noo_resume_nextajax', array(&$this,'noo_resume_shortcode'));
			add_action('wp_ajax_noo_resume_nextajax', array(&$this,'noo_resume_shortcode'));
			add_action( 'save_post', array( $this, 'save_data_attachment_file' ) );
		}

		public function template_loader($template){
			global $wp_query;
			$post_type = get_query_var('post_type');   
			if( is_post_type_archive( $this->post_type ) ){
				$template       = locate_template( "archive-{$this->post_type}.php" );
			}
			return $template;
		}

		public function register_post_type() {
			// Sample register post type
			$archive_slug = $this->get_setting('archive_slug', $this->slug);
			register_post_type( 
				$this->post_type, 
				array( 
					'labels' => array( 
						'name' => __( 'Resumes', 'noo' ), 
						'singular_name' => __( 'Resume', 'noo' ), 
						'add_new' => __( 'Add New Resume', 'noo' ), 
						'add_new_item' => __( 'Add Resume', 'noo' ), 
						'edit' => __( 'Edit', 'noo' ), 
						'edit_item' => __( 'Edit Resume', 'noo' ), 
						'new_item' => __( 'New Resume', 'noo' ), 
						'view' => __( 'View', 'noo' ), 
						'view_item' => __( 'View Resume', 'noo' ), 
						'search_items' => __( 'Search Resume', 'noo' ), 
						'not_found' => __( 'No Resumes found', 'noo' ), 
						'not_found_in_trash' => __( 'No Resumes found in Trash', 'noo' )
					), 
					'public' => true, 
					'has_archive' => true, 
					'menu_icon' => 'dashicons-clipboard', 
					'rewrite' => array( 'slug' => $archive_slug, 'with_front' => false ), 
					'supports' => array( 'title' ), 
					'can_export' => true,
					'delete_with_user' => true,
				)
			);
		}

		public function admin_init() {
			register_setting('noo_resume', 'noo_resume');
			register_setting('noo_resume_general', 'noo_resume_general');
		}

		public function admin_menu() {
			global $submenu;
			$permalink = admin_url( 'edit.php' ).'?post_type=noo_job&page=manage_noo_job&tab=resume';
			
			add_submenu_page( 'edit.php?post_type=noo_resume', __( 'Custom Fields', 'noo' ), __( 'Custom Fields', 'noo' ), 'edit_theme_options', 'resume_custom_field', array( &$this, 'setting_custom_field' ) );
			$submenu['edit.php?post_type=noo_resume'][] = array( 'Settings', 'edit_theme_options', $permalink );
		}

		public function add_query_vars( $vars ) {
			$vars[] = 'job_category';
		
			return $vars;
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
				'title'        => __( 'General Infomation', 'noo' ),
				'context'      => 'normal',
				'priority'     => 'core',
				'description'  => '',
				'fields'       => array(
				),
			);

			$default_fields = self::get_default_fields();
			$custom_fields = noo_get_custom_fields( get_option( 'noo_resume' ), 'noo_resume_custom_fields_' );
			$fields = array_merge( array_diff_key($default_fields, $custom_fields), $custom_fields );
			if($fields){
				foreach ($fields as $field){
					if( !isset( $field['name'] ) || empty( $field['name'] ) ) continue;
					$field['type'] = !isset( $field['type'] ) || empty( $field['type'] ) ? 'text' : $field['type'];
					if( array_key_exists($field['name'], $default_fields) ) {
						if( isset( $field['is_disabled'] ) && ($field['is_disabled'] == 'yes') )
							continue;
						$id = $field['name'];
					} else {
						$id = '_noo_resume_field_'.sanitize_title($field['name']);
					}

					$type = $field['type'];
					if( $field['name'] == '_job_location' ) {
						$type = 'select';
						$field['type'] = '_job_location';
						$job_locations = array();
						$job_locations[] = array('value'=>'','label'=>__('- Select a location -','noo'));
						$job_locations_terms = (array) get_terms('job_location', array('hide_empty'=>0));

						if( !empty( $job_locations_terms ) ) {
							foreach ($job_locations_terms as $location){
								$job_locations[] = array('value'=>$location->term_id,'label'=>$location->name);
							}
						}

						$field['options'] = $job_locations;
						$field['multiple'] = true;
					}

					if( $field['name'] == '_job_category' ) {
						$type = 'select';
						$field['type'] = '_job_category';
						$job_categories = array();
						$job_categories[] = array('value'=>'','label'=>__('- Select a category -','noo'));
						$job_categories_terms = (array) get_terms('job_category', array('hide_empty'=>0));

						if( !empty( $job_categories_terms ) ) {
							foreach ($job_categories_terms as $category){
								$job_categories[] = array('value'=>$category->term_id,'label'=>$category->name);
							}
						}

						$field['options'] = $job_categories;
						$field['multiple'] = true;
					}

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

			// Video
			if( self::get_setting('enable_video', '') ) {
				$meta_box['fields'][] = array(
							'id'	=> 'url_video',
							'label'	=> __( 'Video URL', 'noo' ),
							'type'	=> 'text',
							'std'	=> __( 'Youtube or Vimeo link', 'noo' )
						);
			}

			// File
			// if( self::get_setting('enable_upload_resume', '1') ) {
			// 	$meta_box['fields'][] = array(
			// 				'id'	=> '_noo_file_cv',
			// 				'label'	=> __('Upload your Attachment','noo'),
			// 				'type'	=> 'media',
			// 				'std'	=> ''
			// 			);
			// }

			$helper->add_meta_box($meta_box);

			// Education
			if( self::get_setting('enable_education', '1') ) {
				$meta_box = array(
					'id'           => '_education',
					'title'        => __( 'Education', 'noo' ),
					'context'      => 'normal',
					'priority'     => 'core',
					'description'  => '',
					'fields'       => array(
						array(
							'id'	=> '_education',
							'label'	=> '',
							'type'	=> 'education',
							'std'	=> '',
							'callback' => array (&$this,'render_metabox_fields')
							)
					),
				);

				$helper->add_meta_box($meta_box);
			}

			// Experience
			if( self::get_setting('enable_experience', '1') ) {
				$meta_box = array(
					'id'           => '_experience',
					'title'        => __( 'Work Experience', 'noo' ),
					'context'      => 'normal',
					'priority'     => 'core',
					'description'  => '',
					'fields'       => array(
						array(
							'id'	=> '_experience',
							'label'	=> '',
							'type'	=> 'experience',
							'std'	=> '',
							'callback' => array (&$this,'render_metabox_fields')
							)
					),
				);

				$helper->add_meta_box($meta_box);
			}

			// Skill
			if( self::get_setting('enable_skill', '1') ) {
				$meta_box = array(
					'id'           => '_skill',
					'title'        => __( 'Summary of Skills', 'noo' ),
					'context'      => 'normal',
					'priority'     => 'core',
					'description'  => '',
					'fields'       => array(
						array(
							'id'	=> '_skill',
							'label'	=> '',
							'type'	=> 'skill',
							'std'	=> '',
							'callback' => array (&$this,'render_metabox_fields')
							)
					),
				);

				$helper->add_meta_box($meta_box);
			}

			// Candidate 
			$meta_box = array(
				'id'           => 'candidate',
				'title'        => __( 'Candidate', 'noo' ),
				'context'      => 'side',
				'priority'     => 'default',
				'description'  => '',
				'fields'       => array(
					array(
						'id' => 'author',
						'label' => __( 'This Resume belong to Candidate', 'noo' ),
						'desc' => '',
						'type' => 'candidate_author',
						'std' => '',
						'callback' => array (&$this,'render_metabox_fields')
					)
				)
			);

			$helper->add_meta_box($meta_box);

			// Viewable 
			$meta_box = array(
				'id'           => 'viewable',
				'title'        => __( 'Public Viewable/Searchable', 'noo' ),
				'context'      => 'side',
				'priority'     => 'default',
				'description'  => '',
				'fields'       => array(
					array(
						'id' => '_viewable',
						'label' => __( 'Viewable/Searchable', 'noo' ),
						'desc' => __( 'Set it to yes and this resume will be publicly viewable and searchable publicly.', 'noo' ),
						'type' => 'select',
						'std' => 'no',
						'options' => array (
							array('value'=>'no','label'=>__('No','noo')),
							array('value'=>'yes','label'=>__('Yes','noo')),
							)
					)
				)
			);

			$helper->add_meta_box($meta_box);

			// Attachment 
			if( Noo_Resume::get_setting('enable_upload_resume', '1') ) :
				add_meta_box(
					'_noo_file_cv',
					__( 'Upload your Attachment', 'noo' ),
					array( $this, 'main_upload' ),
					'noo_resume'
				);
			endif;
		}

		/**
		 * Create main upload attachment file
		 */

		public function main_upload( $post ) {

			// Add a nonce field so we can check for it later.
			wp_nonce_field( 'save_attachment_file', 'attachment_file_nonce' );

			?>
				<div class="upload-to-cv clearfix">
			    	<?php noo_plupload_form( 'file_cv', Noo_Resume::get_setting('extensions_upload_resume', 'doc,pdf' ), noo_get_post_meta( $post->ID, '_noo_file_cv' ) ) ?>
					<p class="help-block"><?php echo sprintf( __('Allowed file: %s', 'noo'), Noo_Resume::get_setting('extensions_upload_resume', 'doc,pdf') ); ?></p>
				</div>
			<?php
		
		}

		public function save_data_attachment_file( $post_id ) {

			// Check if our nonce is set.
				if ( ! isset( $_POST['attachment_file_nonce'] ) ) {
					return;
				}

			// Verify that the nonce is valid.
				
				if ( ! wp_verify_nonce( $_POST['attachment_file_nonce'], 'save_attachment_file' ) ) {
					return;
				}

			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
				if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
					return;
				}

			// Sanitize user input.
				$my_data = sanitize_text_field( $_POST['file_cv'] );

			// Update the meta field in the database.
				update_post_meta( $post_id, '_noo_file_cv', $my_data );

		}

		public function sanitize_html_list_value( $values ) {
			if( !is_array( $values ) ) return $values;

			$count = count( $values );
			for( $index = 0; $index < $count; $index++ ) {
				$values[$index] = htmlentities( $values[$index], ENT_QUOTES );
			}

			return $values;
		}

		public function custom_enter_title( $input ) {
			global $post_type;

			if ( $this->post_type == $post_type )
				return __( 'Resume Title', 'noo' );

			return $input;
		}

		public function manage_edit_columns($columns) {
			$before = array_slice($columns, 0, 2);
			$after = array_slice($columns, 2);
			
			$new_columns = array(
				'candidate_id' => __('Candidate', 'noo'),
				'viewable' => __('Viewable', 'noo'),
				'job_category' => __('Job Category', 'noo'),
				'job_location' => __('Job Location', 'noo')
			);

			$columns = array_merge($before, $new_columns, $after);
			unset($columns['date']);
			return $columns;
		}

		public function manage_posts_custom_column($column) {
			GLOBAL $post;
			$post_id = get_the_ID();

			if ($column == 'candidate_id') {
				$candidate_id = esc_attr( $post->post_author );

				if( !empty( $candidate_id ) ) {
					$candidate = get_userdata( $candidate_id );
					$name = !empty($candidate->display_name) ? $candidate->display_name : $candidate->login_name ;

					echo '<a href="'. get_edit_user_link( $candidate_id ) . '" target="_blank">' . $candidate->display_name . '</a>';
				}
				
			}

			if ( $column == 'viewable' ) {
				$viewable = esc_attr( noo_get_post_meta($post_id, '_viewable') );

				$url = wp_nonce_url( admin_url( 'admin-ajax.php?action=noo_viewable_resume&resume_id=' . $post_id ), 'resume-viewable' );
				echo '<a href="' . esc_url( $url ) . '" title="'. __( 'Toggle viewable', 'noo' ) . '">';
				if ( $viewable == 'yes' ) {
					echo '<span class="noo-post-viewable" title="'.esc_attr__('Yes','noo').'"><i class="dashicons dashicons-star-filled "></i></span>';
				} else {
					echo '<span class="noo-post-viewable not-featured"  title="'.esc_attr__('No','noo').'"><i class="dashicons dashicons-star-empty"></i></span>';
				}
				echo '</a>';
			}

			if ($column == 'job_category') {
				$job_category = noo_get_post_meta($post_id, '_job_category');
				if( !empty( $job_category ) ) {
					$job_category = noo_json_decode($job_category);
					
					$job_category_terms = empty( $job_category ) ? array() : get_terms( 'job_category', array('include' => $job_category) );
					$category_terms = array();
					foreach ($job_category_terms as $job_category_term ) {
						$category_terms[] = edit_term_link( $job_category_term->name, '', '', $job_category_term, false );
					}
					echo implode(', ', $category_terms);
				}
			}

			if ($column == 'job_location') {
				$job_location = noo_get_post_meta($post_id, '_job_location');
				if( !empty( $job_location ) ) {
					$job_location = noo_json_decode($job_location);
					
					$job_location_terms = empty( $job_location ) ? array() : get_terms( 'job_location', array('include' => $job_location) );
					$location_terms = array();
					foreach ($job_location_terms as $job_location_term ) {
						$location_terms[] = edit_term_link( $job_location_term->name, '', '', $job_location_term, false );
					}
					echo implode(', ', $location_terms);
				}
			}
		}

		public function restrict_manage_posts() {
			$type = 'post';
			if (isset($_GET['post_type'])) {
				$type = $_GET['post_type'];
			}

			//only add filter to post type you want
			if ('noo_resume' == $type){
				global $post;

				// Candidate
				$candidates = get_users( array( 'role' => Noo_Member::CANDIDATE_ROLE, 'orderby' => 'display_name' ) );
				?>
				<select name="candidate">
					<option value=""><?php _e('All Candidates', 'noo'); ?></option>
					<?php
					$current_v = isset($_GET['candidate'])? $_GET['candidate']:'';
					foreach ($candidates as $candidate) {
						printf
						(
							'<option value="%s"%s>%s</option>',
							$candidate->ID,
							$candidate->ID == $current_v ? ' selected="selected"':'',
							empty( $candidate->display_name ) ? $candidate->login_name : $candidate->display_name
						);
					}
					?>
				</select>
				<?php
				// Job Category
				$job_categories = get_terms( 'job_category', array( 'hide_empty' => false ) );
				?>
				<select name="category">
					<option value=""><?php _e('All Categories', 'noo'); ?></option>
					<?php
					$current_v = isset($_GET['category'])? $_GET['category']:'';
					foreach ($job_categories as $job_category) {
						printf
						(
							'<option value="%s"%s>%s</option>',
							$job_category->term_id,
							$job_category->term_id == $current_v ? ' selected="selected"':'',
							$job_category->name
						);
					}
					?>
				</select>
				<?php
				// Job Locations
				$job_locations = get_terms( 'job_location', array( 'hide_empty' => false )  );
				?>
				<select name="location">
					<option value=""><?php _e('All Locations', 'noo'); ?></option>
					<?php
					$current_v = isset($_GET['location'])? $_GET['location']:'';
					foreach ($job_locations as $job_location) {
						printf
						(
							'<option value="%s"%s>%s</option>',
							$job_location->term_id,
							$job_location->term_id == $current_v ? ' selected="selected"':'',
							$job_location->name
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
			if ( 'noo_resume' == $type && is_admin() && $pagenow=='edit.php' ) {
				if( !isset($query->query_vars['post_type']) || $query->query_vars['post_type'] == 'noo_resume' ) {
					if( isset($_GET['candidate']) && $_GET['candidate'] != '') {
						$candidate_id = $_GET['candidate'];

						$query->query_vars['author'] = $candidate_id;
					}
				}
			}
		}

		public function pre_get_posts($query){
			if( $query->is_singular ) {
				return;
			}

			if( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				return;
			}

			if ( isset($query->query_vars['post_type']) && $query->query_vars['post_type'] === 'noo_resume' ) {
				if( $query->is_post_type_archive ) {
					if( isset($_GET['resume_category']) && !empty($_GET['resume_category']) ) {
						$resume_category = $_GET['resume_category'];
						$query->query_vars['meta_query'][] = array(
							'key' => '_job_category',
							'value' => '"' . $resume_category . '"',
							'compare' => 'LIKE'
						);
					}
				}
				if( $query->is_search ) {
					global $wpdb;
					$candidate_ids = array();
					$resume_ids = array();
					if( isset($_GET['candidate_name']) && !empty($_GET['candidate_name']) ) {
						$candidate_ids = (array)$wpdb->get_col($wpdb->prepare('
							SELECT DISTINCT ID FROM %1$s AS u WHERE u.display_name LIKE \'%2$s\'', $wpdb->users, '%' . $query->query_vars['s'] . '%'));

						if( !empty( $candidate_ids ) ) {
							$query->query_vars['author__in'] = $candidate_ids;
						}
					}
					$education = isset($_GET['education']) && !empty($_GET['education']) ? true : false;
					$experience = isset($_GET['experience']) && !empty($_GET['experience']) ? true : false;
					$skill = isset($_GET['skill']) && !empty($_GET['skill']) ? true : false;
					if( $education || $experience || $skill ) {
						$where_string = array();
						if( $education )
							$where_string[] = sprintf("(m.meta_key = '_education_school' AND m.meta_value LIKE '%%%s%%')",$query->query_vars['s']);
						if( $experience )
							$where_string[] = sprintf("(m.meta_key = '_experience_employer' AND m.meta_value LIKE '%%%s%%')",$query->query_vars['s']);
						if( $skill )
							$where_string[] = sprintf("(m.meta_key = '_skill_name' AND m.meta_value LIKE '%%%s%%')",$query->query_vars['s']);

						$query_string = $wpdb->prepare( "SELECT DISTINCT post_id FROM %s AS m WHERE " . implode(' OR ', $where_string), $wpdb->postmeta );
						
						$resume_ids = (array)$wpdb->get_col($query_string);

						if( !empty( $resume_ids ) ) {
							$query->query_vars['post__in'] = $resume_ids;
						}
					}
					if( isset($_GET['no_content']) && !empty($_GET['no_content']) && (!empty( $resume_ids ) || !empty( $candidate_ids )) ) {
						$query->query['s'] = '';
						$query->query_vars['s'] = '';
					}

					$meta_query = array('relation' => 'AND');
					if( isset( $_GET['location'] ) && $_GET['location'] !=''){
						$meta_query = array(
							'key' => '_job_location',
							'value' => '"' . $_GET['location'] . '"',
							'compare' => 'LIKE'
						);
					}
					if( isset( $_GET['category'] ) && $_GET['category'] !=''){
						$meta_query = array(
							'key' => '_job_category',
							'value' => '"' . $_GET['category'] . '"',
							'compare' => 'LIKE'
						);
					}
					$get_keys = array_keys($_GET);
					foreach ($get_keys as $get_key){
						if(strstr( $get_key, '_noo_resume_field_' )){
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
					$query->query_vars['meta_query'][] = $meta_query;
				}
				if( Noo_Member::is_logged_in() ) {
					$user_id = get_current_user_id();

					// Candidates can view their resumes
					if( isset($query->query_vars['author']) && $query->query_vars['author'] == $user_id ) {
						return;
					}

					// Single resume, let the resume detail page decided
					if( count($query->query_vars['post__in']) == 1 && !empty( $query->query_vars['post__in'][0] ) ) {
						return;
					}
				}

				if( !is_admin() ) {
					$query->query_vars['meta_query'][] = array(
						'key' => '_viewable',
						'value' => 'yes',
					);
				}
			}
		}

		public function ajax_viewable(){
			if ( ! check_admin_referer( 'resume-viewable' ) ) {
				wp_die( __( 'You have taken too long. Please go back and retry.', 'noo' ), '', array( 'response' => 403 ) );
			}

			$resume_id = ! empty( $_GET['resume_id'] ) ? (int) $_GET['resume_id'] : '';

			if ( ! $resume_id || get_post_type( $resume_id ) !== 'noo_resume' ) {
				die();
			}

			$viewable = noo_get_post_meta( $resume_id, '_viewable', true );

			if ( $viewable && $viewable !== 'yes' ) {
				update_post_meta( $resume_id, '_viewable', 'yes' );
			} else {
				update_post_meta( $resume_id, '_viewable', 'no' );
			}

			wp_safe_redirect( esc_url_raw( remove_query_arg( array( 'trashed', 'untrashed', 'deleted', 'ids' ), wp_get_referer() ) ) );
			die();
		}

		public function enqueue_style_script( $hook ) {
			// global $post;

			// if ( $hook == 'post-new.php' || $hook == 'post.php' || $hook == 'edit.php' ) {
			// 	if ( $this->post_type === $post->post_type ) {

					wp_register_script( 'noo-resume-admin', NOO_FRAMEWORK_ADMIN_URI . '/assets/js/noo-resume-admin.js', array( 'jquery','jquery-ui-sortable'), null, true );
					
					$custom_field_tmpl = '';
					$custom_field_tmpl.= '<tr>';
					$custom_field_tmpl.= '<td>';
					$custom_field_tmpl.= '<input type="text" value="" placeholder="'.esc_attr__('Field Name','noo').'" name="' . $this->post_type . '[custom_field][__i__][name]">';
					$custom_field_tmpl.= '</td>';
					$custom_field_tmpl.= '<td>';
					$custom_field_tmpl.= '<input type="text" value="" placeholder="'.esc_attr__('Field Label','noo').'" name="' . $this->post_type . '[custom_field][__i__][label]">';
					$custom_field_tmpl.= '</td>';
					$custom_field_tmpl.= '<td>';
					$custom_field_tmpl.= '<select name="' . $this->post_type . '[custom_field][__i__][type]">';
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
					$custom_field_tmpl.= '<textarea placeholder="'.esc_attr__('Field Value','noo').'" name="' . $this->post_type . '[custom_field][__i__][value]"></textarea>';
					$custom_field_tmpl.= '</td>';
					$custom_field_tmpl.= '<td>';
					$custom_field_tmpl.= '<input type="checkbox" name="' . $this->post_type . '[custom_field][__i__][required]" /> '.esc_attr__('Active','noo');
					$custom_field_tmpl.= '</td>';
					$custom_field_tmpl.= '<td>';
					$custom_field_tmpl.= '<input class="button button-primary" onclick="return delete_noo_resume_custom_field(this);" type="button" value="'.esc_attr__('Delete','noo').'">';
					$custom_field_tmpl.= '</td>';
					$custom_field_tmpl.= '</tr>';

					$nooresumeL10n = array(
						'custom_field_tmpl'=>$custom_field_tmpl,
						'disable_text'=>__('Disable', 'noo'),
						'enable_text'=>__('Enable', 'noo'),
					);
					wp_localize_script('noo-resume-admin', 'nooresumeL10n', $nooresumeL10n);
					wp_enqueue_script( 'noo-resume-admin' );

					wp_register_style( 'noo-resume-admin', NOO_FRAMEWORK_ADMIN_URI . '/assets/css/noo-resume-admin.css' );
					wp_enqueue_style( 'noo-resume-admin' );
				// }
			// }
		}

		public function render_metabox_fields( $post, $id, $type, $meta, $std = null, $field = null ) {
			switch( $type ) {
				case 'candidate_author':

					$user_list = get_users( array( 'role' => Noo_Member::CANDIDATE_ROLE ) );

					echo'<select name="post_author_override" id="post_author_override" >';
					echo'	<option value="" '. selected( $post->post_author, '', true ) . '>' . __('- Select a Candidate - ', 'noo') . '</option>';
					foreach ( $user_list as $user ) {
						echo'<option value="' . $user->ID . '"';
						selected( $post->post_author, $user->ID, true );
						echo '>' . $user->display_name . '</option>';
					}
					echo '</select>';

					break;
				case 'education':
					$meta = array();
					$meta['school'] = noo_json_decode( noo_get_post_meta( get_the_ID(), $id . '_school' ) );
					$meta['qualification'] = noo_json_decode( noo_get_post_meta( get_the_ID(), $id . '_qualification' ) );
					$meta['date'] = noo_json_decode( noo_get_post_meta( get_the_ID(), $id . '_date' ) );
					$meta['note'] = noo_json_decode( noo_get_post_meta( get_the_ID(), $id . '_note' ) );

					foreach ($meta as $key => $value) {
						if( empty( $value ) ) $meta[$key] = array();
					}

					?>
					<div class="noo-metabox-addable" data-name="<?php echo esc_attr($id); ?>" >
						<table class="noo-addable-fields">
							<thead>
								<tr>
									<th><label><?php _e('School name', 'noo'); ?></label></th>
									<th><label><?php _e('Qualification(s)', 'noo'); ?></label></th>
									<th><label><?php _e('Start/end date', 'noo'); ?></label></th>
									<th><label><?php _e('Note', 'noo'); ?></label></th>
									<th></th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<td colspan="4">
										<input type='hidden' value="" name='noo_meta_boxes[<?php echo esc_attr($id) . '_school'; ?>]' />
										<input type='hidden' value="" name='noo_meta_boxes[<?php echo esc_attr($id) . '_qualification'; ?>]' />
										<input type='hidden' value="" name='noo_meta_boxes[<?php echo esc_attr($id) . '_date'; ?>]' />
										<input type='hidden' value="" name='noo_meta_boxes[<?php echo esc_attr($id) . '_note'; ?>]' />
										<input type="button" value="<?php _e('Add Education', 'noo'); ?>" class="button button-default noo-clone-fields" data-template="<tr><td><input type='text' name='noo_meta_boxes[<?php echo esc_attr($id) . '_school'; ?>][]' /></td><td><input type='text' name='noo_meta_boxes[<?php echo esc_attr($id) . '_qualification'; ?>][]' /></td><td><input type='text' name='noo_meta_boxes[<?php echo esc_attr($id) . '_date'; ?>][]' /></td><td><textarea name='noo_meta_boxes[<?php echo esc_attr($id) . '_note'; ?>][]' ></textarea> </td><td><a href='javascript:void()' class='noo-remove-fields'><?php _e('x', 'noo'); ?></a></td></tr>"/>
									</td>
								</tr>
							</tfoot>
							<tbody>
							<?php
							foreach( $meta['school'] as $index => $school ) : 
							?>
								<tr>
									<td><input type="text" name="noo_meta_boxes[<?php echo esc_attr($id) . '_school'; ?>][]" value="<?php echo esc_attr($meta['school'][$index]); ?>" /></td>
									<td><input type="text" name="noo_meta_boxes[<?php echo esc_attr($id) . '_qualification'; ?>][]" value="<?php echo esc_attr($meta['qualification'][$index]); ?>" /></td>
									<td><input type="text" name="noo_meta_boxes[<?php echo esc_attr($id) . '_date'; ?>][]" value="<?php echo esc_attr($meta['date'][$index]); ?>" /></td>
									<td><textarea name="noo_meta_boxes[<?php echo esc_attr($id) . '_note'; ?>][]" ><?php echo esc_attr($meta['note'][$index]); ?></textarea> </td>
									<td><a href="javascript:void()" class="noo-remove-fields"><?php _e('x', 'noo'); ?></a></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?php
					break;
				case 'experience':
					$meta = array();
					$meta['employer'] = noo_json_decode( noo_get_post_meta( get_the_ID(), $id . '_employer' ) );
					$meta['job'] = noo_json_decode( noo_get_post_meta( get_the_ID(), $id . '_job' ) );
					$meta['date'] = noo_json_decode( noo_get_post_meta( get_the_ID(), $id . '_date' ) );
					$meta['note'] = noo_json_decode( noo_get_post_meta( get_the_ID(), $id . '_note' ) );

					foreach ($meta as $key => $value) {
						if( empty( $value ) ) $meta[$key] = array();
					}

					?>
					<div class="noo-metabox-addable" data-name="<?php echo esc_attr($id); ?>" >
						<table class="noo-addable-fields">
							<thead>
								<tr>
									<th><label><?php _e('Employer', 'noo'); ?></label></th>
									<th><label><?php _e('Job Title', 'noo'); ?></label></th>
									<th><label><?php _e('Start/end date', 'noo'); ?></label></th>
									<th><label><?php _e('Note', 'noo'); ?></label></th>
									<th></th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<td colspan="4">
										<input type='hidden' value="" name='noo_meta_boxes[<?php echo esc_attr($id) . '_employer'; ?>]' />
										<input type='hidden' value="" name='noo_meta_boxes[<?php echo esc_attr($id) . '_job'; ?>]' />
										<input type='hidden' value="" name='noo_meta_boxes[<?php echo esc_attr($id) . '_date'; ?>]' />
										<input type='hidden' value="" name='noo_meta_boxes[<?php echo esc_attr($id) . '_note'; ?>]' />
										<input type="button" value="<?php _e('Add Experience', 'noo'); ?>" class="button button-default noo-clone-fields" data-template="<tr><td><input type='text' name='noo_meta_boxes[<?php echo esc_attr($id) . '_employer'; ?>][]' /></td><td><input type='text' name='noo_meta_boxes[<?php echo esc_attr($id) . '_job'; ?>][]' /></td><td><input type='text' name='noo_meta_boxes[<?php echo esc_attr($id) . '_date'; ?>][]' /></td><td><textarea name='noo_meta_boxes[<?php echo esc_attr($id) . '_note'; ?>][]' ></textarea> </td><td><a href='javascript:void()' class='noo-remove-fields'><?php _e('x', 'noo'); ?></a></td></tr>"/>
									</td>
								</tr>
							</tfoot>
							<tbody>
							<?php
							foreach( $meta['employer'] as $index => $employer ) : 
								// if( empty( $employer ) ) continue;
							?>
								<tr>
									<td><input type="text" name="noo_meta_boxes[<?php echo esc_attr($id) . '_employer'; ?>][]" value="<?php echo esc_attr($meta['employer'][$index]); ?>" /></td>
									<td><input type="text" name="noo_meta_boxes[<?php echo esc_attr($id) . '_job'; ?>][]" value="<?php echo esc_attr($meta['job'][$index]); ?>" /></td>
									<td><input type="text" name="noo_meta_boxes[<?php echo esc_attr($id) . '_date'; ?>][]" value="<?php echo esc_attr($meta['date'][$index]); ?>" /></td>
									<td><textarea name="noo_meta_boxes[<?php echo esc_attr($id) . '_note'; ?>][]" ><?php echo esc_attr($meta['note'][$index]); ?></textarea> </td>
									<td><a href="javascript:void()" class="noo-remove-fields"><?php _e('x', 'noo'); ?></a></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?php
					break;
				case 'skill':
					$meta = array();
					$meta['name'] = noo_json_decode( noo_get_post_meta( get_the_ID(), $id . '_name' ) );
					$meta['percent'] = noo_json_decode( noo_get_post_meta( get_the_ID(), $id . '_percent' ) );

					foreach ($meta as $key => $value) {
						if( empty( $value ) ) $meta[$key] = array();
					}

					?>
					<div class="noo-metabox-addable" data-name="<?php echo esc_attr($id); ?>" >
						<table class="noo-addable-fields">
							<thead>
								<tr>
									<th><label><?php _e('Skill Name', 'noo'); ?></label></th>
									<th style="width:20%;"><label><?php _e('Percent % ( 1 to 100 )', 'noo'); ?></label></th>
									<th></th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<td colspan="2">
										<input type='hidden' value="" name='noo_meta_boxes[<?php echo esc_attr($id) . '_name'; ?>]' />
										<input type='hidden' value="" name='noo_meta_boxes[<?php echo esc_attr($id) . '_percent'; ?>]' />
										<input type="button" value="<?php _e('Add Skill', 'noo'); ?>" class="button button-default noo-clone-fields" data-template="<tr><td><input type='text' name='noo_meta_boxes[<?php echo esc_attr($id) . '_name'; ?>][]' /></td><td><input type='text' name='noo_meta_boxes[<?php echo esc_attr($id) . '_percent'; ?>][]' /></td><td><a href='javascript:void()' class='noo-remove-fields'><?php _e('x', 'noo'); ?></a></td></tr>"/>
									</td>
								</tr>
							</tfoot>
							<tbody>
							<?php
							foreach( $meta['name'] as $index => $name ) : 
								// if( empty( $name ) ) continue;
							?>
								<tr>
									<td><input type="text" name="noo_meta_boxes[<?php echo esc_attr($id) . '_name'; ?>][]" value="<?php echo esc_attr($meta['name'][$index]); ?>" /></td>
									<td><input type="text" name="noo_meta_boxes[<?php echo esc_attr($id) . '_percent'; ?>][]" value="<?php echo esc_attr($meta['percent'][$index]); ?>" /></td>
									<td><a href="javascript:void()" class="noo-remove-fields"><?php _e('x', 'noo'); ?></a></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?php
					break;
			}
		}

		public function add_setting_resume_tab( $tabs ) {
			$temp1 = array_slice($tabs, 0, 1);
			$temp2 = array_slice($tabs, 1);

			$resume_tab = array( 'resume' => __('Resumes','noo') );
			return array_merge($temp1, $resume_tab, $temp2);
		}

		public function setting_general() {
			if(isset($_GET['settings-updated']) && $_GET['settings-updated']) {
				flush_rewrite_rules();
			}
			$customizer_resume_link = esc_url( add_query_arg( array('autofocus%5Bsection%5D' => 'noo_customizer_section_resume'), admin_url( '/customize.php' ) ) );
			
			// Sample setting fields
			$fields = array(
				array(
					'id' => 'archive_slug',
					'label' => __( 'Resume Archive base (slug)', 'noo' ),
					'type' => 'text',
					'default' => 'resumes'
				),
				array(
					'id' => 'archive_slug',
					'label' => __( 'Resume Display', 'noo' ),
					'type' => 'label',
					'default' => sprintf( __('Go to <a href="%s">Customizer</a> to change settings for Resume(s) layout or displayed sections.','noo'), $customizer_resume_link )
				),
				array(
					'id' => 'max_viewable_resumes',
					'label' => __( 'Max Viewable Resume', 'noo' ),
					'desc' => __( 'The maximum number of resumes per Candidate that can be viewable ( and searchable ) publicly.', 'noo' ),
					'type' => 'text',
					'default' => '1'
				),
				array(
					'id' => 'can_view_resume',
					'label' => __( 'Who Can View Resume', 'noo' ),
					'desc' => __( 'Select group that can view resume.', 'noo' ),
					'type' => 'select',
					'default' => 'employer',
					'options'=>array(
						array('label'=>__('All Logged in Employer','noo'),'value'=>'employer'),
						array('label'=>__('All Premium ( not free ) Job Package','noo'),'value'=>'premium_package'),
						array('label'=>__('Set By Specific Job Package','noo'),'value'=>'package'),
						array('label'=>__('Public','noo'),'value'=>'public'),
					),
				),
				array(
					'id' => 'enable_upload_resume',
					'label' => __( 'Enable Upload CV', 'noo' ),
					'desc' => '',
					'type' => 'checkbox',
					'default' => '1'
				),
				array(
					'id' => 'extensions_upload_resume',
					'label' => __( 'Allowed Upload File Types', 'noo' ),
					'desc' => __( 'File types that are allowed for uploading to CV. Default only allows Word and PDF files', 'noo' ),
					'type' => 'text',
					'default' => 'doc,docx,pdf'
				),
				array(
					'id' => 'enable_education',
					'label' => __( 'Enable Education', 'noo' ),
					'desc' => '',
					'type' => 'checkbox',
					'default' => '1'
				),
				array(
					'id' => 'enable_experience',
					'label' => __( 'Enable Experience', 'noo' ),
					'desc' => '',
					'type' => 'checkbox',
					'default' => '1'
				),
				array(
					'id' => 'enable_skill',
					'label' => __( 'Enable Skill', 'noo' ),
					'desc' => '',
					'type' => 'checkbox',
					'default' => '1'
				),
				array(
					'id' => 'enable_video',
					'label' => __( 'Enable Video', 'noo' ),
					'desc' => '',
					'type' => 'checkbox',
					'default' => '0'
				),
				array(
					'id' => 'display_video_resume',
					'label' => __( 'Show Video At', 'noo' ),
					'type' => 'select',
					'default' => 'before_content',
					'options'=>array(
						array('label'=>__('Before Content','noo'),'value'=>'before_content'),
						array('label'=>__('After Content','noo'),'value'=>'after_content'),
					),
				),
			);

			$this->render_settings_tab( $fields, 'noo_resume_general' );
		}

		public function setting_custom_field() {
			?>
			<div class="wrap">
				<form action="options.php" method="post">
					<?php 
					setting_custom_field( 
						'noo_resume[custom_field]',
						self::get_default_fields(),
						noo_get_custom_fields( get_option( 'noo_resume' ), 'noo_resume_custom_fields_' ),
						false,
						'noo_resume_custom_field'
					);
					submit_button(__('Save Changes','noo'));
					?>
				</form>
			</div>
			<?php
		}

		public static function is_page_post_resume(){
			$page_temp = get_page_template_slug();
			return 'page-post-resume.php' === $page_temp;
		}
		
		public static function need_login(){
			return !Noo_Member::is_candidate();
		}

		public static function login_handler(){
			if(!self::need_login()){
				wp_safe_redirect(esc_url_raw(add_query_arg( 'action', 'postresume')));
			}
			return;
		}

		public static function get_default_fields() {
			$default_fields = array(
				'_language' => array(
						'name' => '_language',
						'label' => __('Language', 'noo'),
						'type' => 'text',
						'value' => '',
						'std' => __( 'Your working language', 'noo' ),
						'is_default' => 'yes',
						'required' => false
					),
				'_highest_degree' => array(
						'name' => '_highest_degree',
						'label' => __('Highest Degree Level', 'noo'),
						'type' => 'text',
						'value' => '',
						'std' => __( 'eg. &quot;Bachelor Degree&quot;', 'noo' ),
						'is_default' => 'yes',
						'required' => false
					),
				'_experience_year' => array(
						'name' => '_experience_year',
						'label' => __('Total Year Experience', 'noo'),
						'type' => 'text',
						'value' => '',
						'std' => __( 'eg. &quot;1&quot;, &quot;2&quot;', 'noo' ),
						'is_default' => 'yes',
						'required' => false
					),
				'_job_category' => array(
						'name' => '_job_category',
						'label' => __('Job Category', 'noo'),
						'type' => 'select',
						'value' => '',
						'std' => '',
						'is_default' => 'yes',
						'required' => true
					),
				'_job_level' => array(
						'name' => '_job_level',
						'label' => __('Expected Job Level', 'noo'),
						'type' => 'text',
						'value' => '',
						'std' => __( 'eg. &quot;Junior&quot;, &quot;Senior&quot;', 'noo' ),
						'is_default' => 'yes',
						'required' => false
					),
				'_job_location' => array(
						'name' => '_job_location',
						'label' => __('Job Location', 'noo'),
						'type' => 'select',
						'value' => '',
						'std' => '',
						'is_default' => 'yes',
						'required' => false
					),
				);

			return apply_filters( 'noo_resume_default_fields', $default_fields );
		}

		public static function count_viewable_resumes( $candidate_id = 0, $count_all = false ) {
			if( empty( $candidate_id ) && !$count_all ) return 0;

			$args = array(
				'post_type'=>'noo_resume',
				'post_per_page'=>-1,
				'post_status'=>array('publish'),
				'author'=>$candidate_id,
				'meta_query'=>array(
					array(
						'key' => '_viewable',
						'value' => 'yes',
					),
				)
			);

			if( !$count_all ) {
				$args['author'] = $candidate_id;
			}

			$query = new WP_Query( $args );

			return $query->found_posts;
		}
		public static function can_view_resume( $resume_id = null,$is_loop = false) {
			if( empty( $resume_id )  && !$is_loop) 
				return false;
			
			$candidate_id = get_post_field( 'post_author', $resume_id );
			if( $candidate_id == get_current_user_id() )
				return true;

			if( 'administrator' == Noo_Member::get_user_role(get_current_user_id()) )
				return true;
			
			$can_view_resume_setting = self::get_setting('can_view_resume','employer');
			
			if( $can_view_resume_setting != 'public' && !Noo_Member::is_logged_in() ) {
				return false;
			}
				
			if( $is_loop || noo_get_post_meta( $resume_id, '_viewable', '' ) == 'yes' ){
				if( $can_view_resume_setting === 'public'){
					return true;	
				}elseif(($can_view_resume_setting === 'employer') && Noo_Member::is_employer()){
					return true;
				}elseif (Noo_Job::use_woocommerce_package() && Noo_Member::is_employer()){
					$package = Noo_Job::get_employer_package();
					if(is_array($package) && ($product_package = get_product(absint(@$package['product_id'])))){
						if($product_package->product_type === 'job_package'){
							if($can_view_resume_setting === 'premium_package'){
								if ($product_package->get_price() > 0 ){
									return true;
								}
							}elseif ($can_view_resume_setting === 'package'){
								if($product_package->get_can_view_resume() === '1'){
									return true;
								}
							}
						}
					}
				}
				if($is_loop)
					return false;
			}

			// Employers can view resumes from its applications
			if( isset($_GET['application_id'] ) && !empty($_GET['application_id']) ) {

				$job_id = get_post_field( 'post_parent', $_GET['application_id'] );
				
				$employer_id = get_post_field( 'post_author', $job_id );
				if( $employer_id == get_current_user_id() ) {
					$attachement_id = noo_get_post_meta( $_GET['application_id'], '_attachment', '' );
					// do_action('noo_check_view_applications');
					return $attachement_id == $resume_id;
				}
			}

			return false;
		}

		public static function get_resume_permission_message() {
			$title = __('You don\'t have permission to view resumes.','noo');
			$link = '';
			$can_view_resume_setting = self::get_setting('can_view_resume','employer');
			if( $can_view_resume_setting == 'employer' ) {
				$title = __('Only logged in employers can view resumes.','noo');
				$link = Noo_Member::get_login_url();
				$link = '<a href="' . esc_url( $link ) . '"><i class="fa fa-long-arrow-right"></i>&nbsp;' . __( 'Login as Employer', 'noo' ) . '</a>';
			} elseif( $can_view_resume_setting == 'premium_package' ) {
				$title = __('Only paid employers can view resumes.','noo');
				
				if( !Noo_Member::is_logged_in() ) {
					$link = Noo_Member::get_login_url();
					$link = '<a href="' . esc_url( $link ) . '"><i class="fa fa-long-arrow-right"></i>&nbsp;' . __( 'Login as Employer', 'noo' ) . '</a>';
				} elseif( !Noo_Member::is_employer() ) {
					$link = Noo_Member::get_logout_url();
					$link = '<a href="' . esc_url( $link ) . '"><i class="fa fa-long-arrow-right"></i>&nbsp;' . __( 'Logout then Login as Employer', 'noo' ) . '</a>';
				} else {
					$link = Noo_Member::get_endpoint_url('manage-plan');
					$link = '<a href="' . esc_url( $link ) . '"><i class="fa fa-long-arrow-right"></i>&nbsp;' . __( 'Upgrade your membership', 'noo' ) . '</a>';
				}
			} elseif( $can_view_resume_setting == 'package' ) {
				$title = __('Only paid employers can view resumes.','noo');
				$link = Noo_Member::get_endpoint_url('manage-plan');

				if( !Noo_Member::is_logged_in() ) {
					$link = Noo_Member::get_login_url();
					$link = '<a href="' . esc_url( $link ) . '"><i class="fa fa-long-arrow-right"></i>&nbsp;' . __( 'Login as Employer', 'noo' ) . '</a>';
				} elseif( !Noo_Member::is_employer() ) {
					$link = Noo_Member::get_logout_url();
					$link = '<a href="' . esc_url( $link ) . '"><i class="fa fa-long-arrow-right"></i>&nbsp;' . __( 'Logout then Login as Employer', 'noo' ) . '</a>';
				} else {
					$title = __('Your membership doesn\'t allow you to view resumes.','noo');
					$link = Noo_Member::get_endpoint_url('manage-plan');
					$link = '<a href="' . esc_url( $link ) . '"><i class="fa fa-long-arrow-right"></i>&nbsp;' . __( 'Upgrade your membership', 'noo' ) . '</a>';
				}
			}

			return array( $title, $link );
		}

		public static function noo_content_resume_meta() {
			$post_type = get_post_type();
			$category_job			= noo_get_post_meta( get_the_ID(), '_job_category', '' );
			$resume = array();
			$resume = get_post(get_the_ID());
			$type 				= self::get_job_type( get_the_ID() );
			$html = array();
			$html[] = '<p class="content-meta">';
			// Company Name
			$html[] = '<span> <a href="'.esc_url($resume->post_parent).'">' . $resume->post_parent . '</a></span>';
			// Company Type
			$html[] = '<span class="job-type" style="color: '.$type->color.'"><i class="fa fa-bookmark"></i>' .$type->name. '</span>';
			// Date
			$html[] = '<span>';
			$html[] = '<time class="entry-date" datetime="' . esc_attr(get_the_date('c')) . '">';
			$html[] = '<i class="fa fa-calendar"></i>';
			$html[] = esc_html(get_the_date());
			$html[] = '</time>';
			$html[] = '</span>';

			echo implode($html, "\n");
		}

		public static function display_detail($query=null, $hide_profile = false) {

			if(empty($query)){
				global $wp_query;
				$query = $wp_query;
			}

			ob_start();
	        include(locate_template("layouts/noo_resume-detail.php"));
	        echo ob_get_clean();
			
			wp_reset_query();
		}

		public static function noo_resume_shortcode( $atts, $content = null ) {
			extract(shortcode_atts(array(
				'title'           => '',
				'show_pagination' => 'no',
				'posts_per_page'  => 3,
				'no_content'      => 'text',
				'job_category'    => 'all',
				'job_location'    => 'all',
				'orderby'         => 'date',
				'order'           => 'desc'
			), $atts));
			$paged = 1;
			if(defined( 'DOING_AJAX' ) && DOING_AJAX && isset($_POST['action']) && $_POST['action'] === 'noo_resume_nextajax'){
				$paged = isset($_POST['page']) ? absint($_POST['page']) : 1;
				$posts_per_page = isset($_POST['posts_per_page']) ? absint($_POST['posts_per_page']) : 3;
				$job_category = isset($_POST['job_category']) ? $_POST['job_category'] : $job_category;
				$job_location = isset($_POST['job_location']) ? $_POST['job_location'] : $job_location;
				$orderby = isset($_POST['orderby']) ? $_POST['orderby'] : $orderby;
				$order = isset($_POST['order']) ? $_POST['order'] : $order;
			} else {
				if( is_front_page() || is_home()) {
					$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : ( ( get_query_var( 'page' ) ) ? get_query_var( 'page' ) : 1 );
				} else {
					$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
				}
			}
			$args = array(
				'post_type'			  => 'noo_resume',
				'post_status'         => 'publish',
				'paged'			  	  => $paged,
				'posts_per_page'	  => $posts_per_page,
				// 'ignore_sticky_posts' => true,
			);
			//  -- tax_query
		
				$args['meta_query'] = array( 'relation' => 'AND' );
				if ( $job_category != 'all' ) {
					$args['meta_query'][] = array(
						'key'     => '_job_category',
						'value'   => '"' . $job_category . '"',
						'compare' => 'LIKE'
					);
				}

				if ( $job_location != 'all' ) {
					$args['meta_query'][] = array(
						'key'     => '_job_location',
						'value'   => '"' . $job_location . '"',
						'compare' => 'LIKE'
					);
				}

			//  -- Check order by......
			
				if ( $orderby == 'view' ) {
					$args['orderby']  = 'meta_value_num';
					$args['meta_key'] = '_noo_views_count';
		 		} else {
		 			$args['orderby'] = 'date';
		 		}

		 	//  -- Check order
		 	
		 		if ( $order == 'asc' ) {
		 			$args['order'] = 'ASC';
		 		} else {
		 			$args['order'] = 'DESC';
		 		}

		 	$r = new WP_Query( $args );
		 	ob_start();
			$pagination = $show_pagination == 'yes' ? 1 : 0;
			self::loop_display(array(
				'query'          =>$r,
				'title'          =>$title,
				'paginate'       =>'resume_nextajax',
				'ajax_item'      => (defined( 'DOING_AJAX' ) && DOING_AJAX && isset($_POST['action']) && $_POST['action'] === 'noo_resume_nextajax'),
				'item_class'     =>'nextajax-item',
				'pagination'     =>$pagination,
				'posts_per_page' =>$posts_per_page,
				'job_category'	 =>$job_category,
				'job_location'   =>$job_location,
				'orderby'        =>$orderby,
				'order'          =>$order,
				'is_shortcode'   =>true
			));
			$output = ob_get_clean();
			wp_reset_query();
			if(defined( 'DOING_AJAX' ) && DOING_AJAX && isset($_POST['action']) && $_POST['action'] === 'noo_resume_nextajax'){
				echo $output;
				die;
			}
			return $output;
		}

		public static function loop_display( $args = '' ) {
			$defaults = array( 
				'query'          => '', 
				'title'          => '', 
				'pagination'     => 1,
				'paginate'       => 'normal',
				'ajax_item'		 => false,
				'excerpt_length' => 30,
				'posts_per_page'  => 3,
				'is_shortcode'   => false,
				'job_category'    => 'all',
				'job_location'    => 'all',
				'orderby'         => 'date',
				'order'           => 'desc',
				'live_search'     => false
			);
			$p = wp_parse_args($args,$defaults);
			extract($p);
			global $wp_query;
			if(!empty($query))
				$wp_query = $query;

			ob_start();
	        include(locate_template("layouts/noo_resume-loop.php"));
	        echo ob_get_clean();

			wp_reset_postdata();
			wp_reset_query();

		}
	}

	new Noo_Resume();
endif;
