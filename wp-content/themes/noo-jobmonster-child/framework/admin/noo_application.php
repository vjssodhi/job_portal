<?php
if( !class_exists('Noo_Application') ) :
class Noo_Application {

	public function __construct() {
		add_action( 'init', array( &$this, 'register_post_type' ), 0 );
		if ( is_admin() ) {
			add_action( 'admin_menu', array(&$this,'admin_menu') );
			add_filter( 'manage_edit-noo_application_columns', array( $this, 'columns' ) );
			add_action( 'manage_noo_application_posts_custom_column', array( $this, 'custom_columns' ), 2 );
			add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 1, 2 );
			add_action( 'restrict_manage_posts', array(&$this, 'restrict_manage_posts') );
			add_filter( 'parse_query', array(&$this, 'posts_filter') );
			add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		
			add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes' ), 30 );
			
			add_filter( 'views_edit-noo_application', array( &$this,'modified_views_status' ) );
			foreach ( array( 'post', 'post-new' ) as $hook ) {
				add_action( "admin_footer-{$hook}.php", array( &$this,'extend_application_status' ) );
			}
		}
	}

	public function admin_menu() {
		global $submenu;
		$permalink = admin_url( 'edit.php' ).'?post_type=noo_job&page=manage_noo_job&tab=application';
		
		$submenu['edit.php?post_type=noo_application'][] = array( 'Settings', 'edit_theme_options', $permalink );
	}
	
	public function add_meta_boxes() {
		$meta_box = array( 
			'id' => "job_application_settings", 
			'title' => __( 'Job Application Settings', 'noo' ), 
			'page' => 'noo_application', 
			'context' => 'normal', 
			'priority' => 'high', 
			'fields' => array( 
				array( 'id' => '_candidate_email', 'label' => __( 'Contact Email', 'noo' ), 'type' => 'text' ), 
				array( 'id' => '_attachment', 'label' => __( 'Attachment', 'noo' ), 'type' => 'text' ),
				//array( 'id' => '_job_application_author', 'label' => __( 'Posted by:', 'noo' ), 'type' => 'author','callback' => array( &$this, 'meta_box_author' ) )
			) 
		);
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
	}
	
	public function meta_box_author( $post, $id, $type, $meta, $std, $field){
		
		wp_dropdown_users( array(
			'who'              => '',
			'id'				   => $id,
			'show_option_none' => __( 'Guest user', 'noo' ),
			'name'             => "noo_meta_boxes[" . $id . "]",
			'selected'         => $post->post_author,
			'include_selected' => true
		) );
	}
	
	public function enter_title_here( $text, $post ) {
		if ( $post->post_type == 'noo_application' ) {
			return __( 'Candidate name', 'noo' );
		}
		return $text;
	}
	
	public function post_updated_messages( $messages ) {
		$messages['noo_application'] = array(
			0  => '',
			1  => __( 'Job application updated.', 'noo' ),
			2  => __( 'Custom field updated.', 'noo' ),
			3  => __( 'Custom field deleted.', 'noo' ),
			4  => __( 'Job application updated.', 'noo' ),
			5  => '',
			6  => __( 'Job application published.', 'noo' ),
			7  => __( 'Job application saved.', 'noo' ),
			8  => __( 'Job application submitted.', 'noo' ),
			9  => '',
			10 => __( 'Job application draft updated.', 'noo' )
		);
	
		return $messages;
	}

	public function modified_views_status( $views ) {
		if( isset( $views['publish'] ) )
			$views['publish'] = str_replace( 'Published ', __('Approved', 'noo') . ' ', $views['publish'] );

		return $views;
	}
	
	public function restrict_manage_posts() {
		global $typenow, $wp_query, $wpdb;
	
		if ( 'noo_application' != $typenow ) {
			return;
		}
	
		?>
		<select id="dropdown_noo_job" name="job">
		<option value=""><?php _e( 'All jobs', 'noo' ) ?></option>
			<?php
				$jobs_with_applications = $wpdb->get_col( "SELECT DISTINCT post_parent FROM {$wpdb->posts} WHERE post_type = 'noo_application'" );
				$current                = isset( $_GET['job'] ) ? $_GET['job'] : 0;
				foreach ( $jobs_with_applications as $job_id ) {
					if ( ( $title = get_the_title( $job_id ) ) && $job_id ) {
						echo '<option value="' . $job_id . '" ' . selected( $current, $job_id, false ) . '">' . $title . '</option>';
					}
				}
			?>
		</select>
		<?php
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
	}

	public function posts_filter( $query ){
		global $pagenow;
		$type = 'post';
		if (isset($_GET['post_type'])) {
			$type = $_GET['post_type'];
		}
		if ( 'noo_application' == $type && is_admin() && $pagenow=='edit.php' ) {
			if( !isset($query->query_vars['post_type']) || $query->query_vars['post_type'] == 'noo_application' ) {
				if( isset($_GET['job']) && $_GET['job'] != '') {
					$job_id = $_GET['job'];

					$query->query_vars['post_parent'] = $job_id;
				}
				if( isset($_GET['candidate']) && $_GET['candidate'] != '') {
					$candidate_id = $_GET['candidate'];
					$candidate_info = get_userdata($candidate_id);

					$query->query_vars['meta_query'][] = array(
						'key' => '_candidate_email',
						'value' => $candidate_info->user_email,
					);
				}
			}
		}
	}
	
	public function columns( $columns ) {
		if ( ! is_array( $columns ) ) {
			$columns = array();
		}
	
		unset( $columns['title'], $columns['date'] );
	
		$columns["application_status"]      = __( "Status", 'noo' );
		$columns["candidate"]               = __( "Candidate", 'noo' );
		$columns["job"]                     = __( "Job applied for", 'noo' );
		$columns["attachment"]              = __( "CV | Attachment", 'noo' );
		$columns["job_application_posted"]  = __( "Posted", 'noo' );
		$columns['job_application_actions'] = __( "Actions", 'noo' );
		return $columns;
	}
	
	public function custom_columns( $column ) {
		global $post;
	
		switch ( $column ) {
			case "application_status" :
				$status   = $post->post_status;
				$statuses = self::get_application_status();
				if ( isset( $statuses[ $status ] ) ) {
					$status = $statuses[ $status ];
				} else {
					$status = __( 'Inactive', 'noo' );
				}
				echo '<span class="job-application-status job-application-status-'.sanitize_html_class($status).'">';
				echo esc_html($status);
				echo '</span>';
				break;
			case "candidate" :
				echo '<a href="' . admin_url('post.php?post=' . $post->ID . '&action=edit') . '" class="tips candidate_name" data-tip="' . sprintf( __( 'ID: %d', 'noo' ), $post->ID ) . '"><strong>' . $post->post_title . '</strong></a>';
				if ( $email = get_post_meta( $post->ID, '_candidate_email', true ) ) {
					echo '<br/><a href="mailto:' . esc_attr( $email ) . '">' . esc_attr( $email ) . '</a>';
				}
				break;
			case 'job' :
				$job = get_post( $post->post_parent );
	
				if ( $job && $job->post_type === 'noo_job' ) {
					echo '<a href="' . get_permalink( $job->ID ) . '">' . $job->post_title . '</a>';
				} elseif ( $job = get_post_meta( $post->ID, '_job_applied_for', true ) ) {
					echo esc_html( $job );
				} else {
					echo '<span class="na">&ndash;</span>';
				}
				break;
			case 'attachment' :
				$attachment = noo_get_post_meta( $post->ID, '_attachment', '' );
				if( is_numeric($attachment) ) $attachment = get_permalink( $attachment );
				if ( $attachment ) {
					echo '<a href="' . $attachment . '">' . basename( $attachment ) . '</a>';
				} else {
					echo '<span class="na">&ndash;</span>';
				}
				break;
			case "job_application_posted" :
				echo '<span><strong>' . date_i18n( get_option('date_format'), strtotime( $post->post_date ) ) . '</strong><span><br>';
				$email = noo_get_post_meta( $post->ID, '_candidate_email', true );
				$user = get_user_by( 'email', $email );
				echo !$user ? __( 'by a Guest', 'noo' ) : sprintf( __( 'by %s', 'noo' ), '<a href="' . get_edit_user_link( $user->ID ) . '">' . $user->display_name . '</a>' );
				echo '</span>';
				break;
			case "job_application_actions" :
				echo '<div class="actions">';
				$admin_actions           = array();
				if ( $post->post_status !== 'trash' ) {
					$admin_actions['view']   = array(
						'action'  => 'view',
						'name'    => __( 'View', 'noo' ),
						'url'     => get_edit_post_link( $post->ID ),
						'icon'	  => 'visibility',
					);
					$admin_actions['delete'] = array(
						'action'  => 'delete',
						'name'    => __( 'Delete', 'noo' ),
						'url'     => get_delete_post_link( $post->ID ),
						'icon'	  => 'trash',
					);
				}
	
				$admin_actions = apply_filters( 'noo_application_manager_admin_actions', $admin_actions, $post );
	
				foreach ( $admin_actions as $action ) {
					printf( '<a class="button tips icon-%1$s" href="%2$s" data-tip="%3$s">%4$s</a>', $action['action'], esc_url( $action['url'] ), esc_attr( $action['name'] ), '<i class="dashicons dashicons-'.$action['icon'].'"></i>' );
				}
	
				echo '</div>';
	
				break;
		}
	}

	public static function new_job_application( $job_id, $candidate_name, $candidate_email, $application_message, $meta = array() , $send_notification = true){
		$job = get_post( $job_id );
		if ( ! $job || $job->post_type !== 'noo_job' ) {
			return false;
		}
		
		$post_data = array(
			'post_title'     => wp_kses_post( $candidate_name ),
			'post_content'   => wp_kses_post( $application_message ),
			'post_status'    => 'pending',
			'post_type'      => 'noo_application',
			'comment_status' => 'closed',
			'post_author'    => get_current_user_id(),
			'post_parent'    => $job_id
		);
		$application_id = wp_insert_post( $post_data );
		if ( $application_id ) {
			update_post_meta( $application_id, '_job_applied_for', $job->post_title );
			update_post_meta( $application_id, '_candidate_email', $candidate_email );
			$_candidate_user_id = get_current_user_id();
			update_post_meta( $application_id, '_candidate_user_id', $_candidate_user_id );
		
			if ( $meta ) {
				foreach ( $meta as $key => $value ) {
					update_post_meta( $application_id, $key, $value );
				}
			}
			if ( $send_notification ) {
				//Send email
				self::send_notification(array(
					'job_id'=>$job_id,
					'application_id'=>$application_id,
					'candidate_email'=>$candidate_email,
					'candidate_name'=>$candidate_name,
					'application_message'=>$application_message,
				));
			}
			return $application_id;
		}
		return false;
	}
	
	public static function send_notification($args = ''){
		$defaults = array(
			'job_id'=>'',
			'application_id'=>'',
			'candidate_email'=>'',
			'candidate_name'=>'',
			'application_message'=>'',
		);
		
		$p = wp_parse_args($args,$defaults);
		extract($p);
		
		$job = get_post($job_id);
		
		if ( $job->post_type !== 'noo_job' )
			return;
		
		
		$blogname = get_bloginfo( 'name' );

		//employer email

		$subject = sprintf(__('[%1$s] %2$s applied for %3$s','noo'),$blogname, $candidate_name, $job->post_title);
		$notify_email  = get_post_meta($job_id,'_application_email',true);
		$employer = get_userdata( $job->post_author );

		if ( !empty( $notify_email ) && strstr( $notify_email, '@' ) && is_email( $notify_email ) ) {
			$to = $notify_email;
		} elseif ( $job->post_author ) {
			$to = $employer->user_email;
		} else {
			$to = '';
		}

		$attach_file = '';
		$email_attachment = Noo_Job::get_setting('noo_email','noo_notify_job_apply_attachment');
		if( $email_attachment == 'enable' ) {
			$attachment = noo_get_post_meta( $application_id, '_attachment', '' );
			$upload_dir = wp_upload_dir();
			if( strpos( $attachment, $upload_dir['baseurl'] ) === 0 ) {
				$attach_file = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $attachment);
			}
		}

		if ( $to && !empty( $employer ) ) {
			$message = __( 'Hi %1$s,<br/>
				<br/>
				%2$s\'ve just applied for %3$s.<br/>
				<a href="%4$s">View Resume</a><br/>
				You can manage applications for your jobs in <a href="%5$s">Manage Application</a>.
				<br/><br/>
				Best regards,<br/>
				%6$s','noo');
			noo_mail($to, $subject, sprintf($message,$employer->display_name,$candidate_name,$job->post_title,$attachment,Noo_Member::get_endpoint_url('manage-application'),$blogname), array(), 'noo_notify_job_apply_employer', $attach_file);
		}

		//candidate email
		$subject = sprintf(__('You have successfully applied for %1$s','noo'),$job->post_title);
		$to = $candidate_email;
		$message = __( 'Congratulation %1$s,<br/><br/>
You\'ve successfully applied for %2$s.<br/>
<a href="%3$s">View Job Detail</a><br/>
You can manage and follow status of your applied jobs and applications in <a href="%4$s">My Applications</a>.
<br/><br/>
Note: Due to high application volume, employers may not be able to respond to all the application.
<br/><br/>
Good luck on your future career path!
<br/><br/>
Best regards,<br/>
%5$s','noo');
		noo_mail($to, $subject, sprintf($message,$candidate_name,$job->post_title,get_permalink($job),Noo_Member::get_endpoint_url('manage-job-applied'),$blogname), array(), 'noo_notify_job_apply_candidate');
		
		return;
	}
	
	public function register_post_type() {
		if ( post_type_exists( 'noo_application' ) )
			return;
		
		register_post_type( 
			'noo_application', 
			array( 
				'labels' => array( 
					'name' => __( 'Job Applications', 'noo' ), 
					'singular_name' => __( 'Job Application', 'noo' ), 
					'add_new' => __( 'Add New Application', 'noo' ), 
					'add_new_item' => __( 'Add Job Application', 'noo' ), 
					'edit' => __( 'Edit Job Application', 'noo' ), 
					'edit_item' => __( 'Edit Job Application', 'noo' ), 
					'new_item' => __( 'New Job Application', 'noo' ), 
					'view' => __( 'View Job Application', 'noo' ), 
					'view_item' => __( 'View Job Application', 'noo' ), 
					'search_items' => __( 'Search Job Application', 'noo' ), 
					'not_found' => __( 'No Job Applications found', 'noo' ), 
					'not_found_in_trash' => __( 'No Job Applications found in Trash', 'noo' ), 
					'parent' => __( 'Parent Job Application', 'noo' ) 
				), 
				// 'capabilities' => array(
				// 	'publish_posts'       => 'manage_noo_job',
				// 	'edit_posts'          => 'manage_noo_job',
				// 	'edit_others_posts'   => 'manage_noo_job',
				// 	'delete_posts'        => 'manage_noo_job',
				// 	'delete_others_posts' => 'manage_noo_job',
				// 	'read_private_posts'  => 'manage_noo_job',
				// 	'edit_post'           => 'manage_noo_job',
				// 	'delete_post'         => 'manage_noo_job',
				// 	'read_post'           => 'manage_noo_job',
				// 	'create_posts'        => false // No one should have this cap by default
				// ),
				'description' 		  => __( 'This is where you can edit and view job applications.', 'noo' ), 
				'menu_icon' 		  => 'dashicons-groups',
				'public'              => false,
				'show_ui'             => true,
				// 'capability_type'     => 'noo_job',
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'hierarchical'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title', 'editor' ),
				'has_archive'         => false,
				'show_in_nav_menus'   => false,
				'delete_with_user'    => true,
			) 
		);
		
		register_post_status( 'rejected', array(
			'label'                     => __( 'Rejected', 'noo' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Rejected <span class="count">(%s)</span>', 'Rejected <span class="count">(%s)</span>', 'noo' ),
		) );
		
		register_post_status( 'inactive', array(
			'label'                     => __( 'Inactive', 'noo' ),
			'public'                    => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', 'noo' ),
		) );
	}
	
	public function extend_application_status(){
		global $post, $post_type;
		if($post_type === 'noo_application'){
			$html = $selected_label = '';
			foreach ((array) self::get_application_status() as $status=>$label){
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
	
	public static function get_application_status(){
		return apply_filters('noo_application_status', array(
			'rejected'        => __( 'Rejected', 'noo' ),
			'pending'         => __( 'Pending', 'noo' ),
			'publish'         => __( 'Approved', 'noo' ),
			'inactive'        => __( 'Inactive', 'noo' ),
		));
	}

	public static function can_edit_application( $user_id = 0, $application_id = 0 ) {
		if( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		if( empty( $user_id ) || empty( $application_id ) ) {
			return false;
		}

		if ( get_post_type( $application_id ) !== 'noo_application' ) {
			return false;
		}

		if ( get_post_status( $application_id ) !== 'pending' ) {
			return false;
		}

		if( !Noo_Member::is_employer( $user_id ) ) {
			return false;
		}

		$job_id = get_post_field( 'post_parent', $application_id );

		if( is_wp_error( $job_id ) ) {
			return false;
		}
		$employer_id = get_post_field( 'post_author', $job_id );

		return ( absint($employer_id) === absint($user_id) );
	}

	public static function can_trash_application( $user_id = 0, $application_id = 0 ) {
		if( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		if( empty( $user_id ) || empty( $application_id ) ) {
			return false;
		}

		if ( get_post_type( $application_id ) !== 'noo_application' ) {
			return false;
		}

		if( Noo_Member::is_employer( $user_id ) ) {
			$job_id = get_post_field( 'post_parent', $application_id );

			if( is_wp_error( $job_id ) ) {
				return false;
			}
			$employer_id = get_post_field( 'post_author', $job_id );

			return ( absint($employer_id) === absint($user_id) );
		} elseif( Noo_Member::is_candidate( $user_id ) ) {
			$status = get_post_field( 'post_status', $application_id );
			$user = get_userdata( $user_id );
			$email = noo_get_post_meta( $application_id, '_candidate_email' );

			return ( $status === 'pending' && $user && $email == $user->user_email );
		}

		return false;
	}

	public static function can_delete_application( $user_id = 0, $application_id = 0 ) {
		if( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		if( empty( $user_id ) || empty( $application_id ) ) {
			return false;
		}

		if ( get_post_type( $application_id ) !== 'noo_application' ) {
			return false;
		}

		if( !Noo_Member::is_candidate( $user_id ) ) {
			return false;
		}

		$status = get_post_field( 'post_status', $application_id );
		$user = get_userdata( $user_id );
		$email = noo_get_post_meta( $application_id, '_candidate_email' );

		return ( $status === 'inactive' && $user && $email == $user->user_email );
	}

	public static function has_applied( $candidate_id = 0, $job_id = 0 ) {
		if( empty( $candidate_id ) ) {
			$candidate_id = get_current_user_id();
		}
		if( empty( $candidate_id ) || empty( $job_id ) ) {
			return false;
		}

		$candidate = get_userdata( $candidate_id );
		$application_args = array(
			'post_type'=>'noo_application',
			'posts_per_page' => -1,
			'post_status'=>array('publish','pending','rejected'),
			'post_parent'=> absint( $job_id ),
			'meta_query'=>array(
				array(
					'key' => '_candidate_email',
					'value' => $candidate->user_email,
				),
			)
		);
		$application = new WP_Query($application_args);
		if ( $application->post_count ) {
			return true;
		}

		return false;
	}
}
new Noo_Application();
endif;