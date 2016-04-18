<?php
/**
 * HTML Functions for NOO Framework.
 * This file contains various functions used for rendering site's small layouts.
 *
 * @package    NOO Framework
 * @version    1.0.0
 * @author     Kan Nguyen <khanhnq@nootheme.com>
 * @copyright  Copyright (c) 2014, NooTheme
 * @license    http://opensource.org/licenses/gpl-2.0.php GPL v2 or later
 * @link       http://nootheme.com
 */

// Shortcodes
require_once NOO_FRAMEWORK_FUNCTION . '/noo-html-shortcodes.php';

// Featured Content
require_once NOO_FRAMEWORK_FUNCTION . '/noo-html-featured.php';

// Pagination
require_once NOO_FRAMEWORK_FUNCTION . '/noo-html-pagination.php';

// upload
require_once NOO_FRAMEWORK_FUNCTION . '/noo-function-upload.php';

if (!function_exists('noo_content_meta')):
	function noo_content_meta($is_shortcode=false,$hide_author = false,$hide_date = false,$hide_category = false,$hide_comment = false) {
		global $post;
		$post_type = get_post_type();
		if ( $post_type == 'post' ) {
			if ((!is_single() && noo_get_option( 'noo_blog_show_post_meta' ) === false)
					|| (is_single() && noo_get_option( 'noo_blog_post_show_post_meta' ) === false)) {
						return;
					}
		} elseif ($post_type == 'portfolio_project') {
			if (noo_get_option( 'noo_portfolio_show_post_meta' ) === false) {
				return;
			}
		}

		$html = array();
		$html[] = '<p class="content-meta">';
		// Author
		if(!$hide_author):
			$authordata = get_userdata($post->post_author);
			$html[] = '<span>';
			$html[] = '<i class="fa fa-pencil"></i> ';
			$author = sprintf(
				'<a href="%1$s" title="%2$s" rel="author">%3$s</a>',
				esc_url( get_author_posts_url( $authordata->ID, get_the_author_meta( 'nicename',$authordata->ID) ) ),
				esc_attr( sprintf( __( 'Posts by %s', 'noo'),  $authordata->display_name ) ),
				$authordata->display_name
			);
			$html[] = $author;
			$html[] = '</span>';
		endif;
		// Date
		if(!$hide_date):
			$html[] = '<span>';
			$html[] = '<time class="entry-date" datetime="' . esc_attr(get_the_date('c')) . '">';
			$html[] = '<i class="fa fa-calendar"></i> ';			
			$html[] = esc_html(get_the_date());
			$html[] = '</time>';
			$html[] = '</span>';
		endif;
		// Categories
		$categories_html = '';
		$separator = ', ';

		if (get_post_type() == 'portfolio_project') {
			if (has_term('', 'portfolio_category', NULL)) {
				$categories = get_the_terms(get_the_id() , 'portfolio_category');
				foreach ($categories as $category) {
					$categories_html .= '<a' . ' href="' . get_term_link($category->slug, 'portfolio_category') . '"' . ' title="' . esc_attr(sprintf(__("View all Portfolio Items in: &ldquo;%s&rdquo;", 'noo') , $category->name)) . '">' . ' ' . $category->name . '</a>' . $separator;
				}
			}
		} else {
			$categories = get_the_category();
			foreach ($categories as $category) {
				$categories_html.= '<a' . ' href="' . get_category_link($category->term_id) . '"' . ' title="' . esc_attr(sprintf(__("View all posts in: &ldquo;%s&rdquo;", 'noo') , $category->name)) . '">' . ' ' . $category->name . '</a>' . $separator;
			}
		}
		if(!$hide_category):
			$html[] = '<span>';
			$html[] = '<i class="fa fa-list-ul"></i> ';
			$html[] = trim($categories_html, $separator) . '</span>';
		endif;
		// Comments
		$comments_html = '';

		if (comments_open()) {
			$comment_title = '';
			$comment_number = '';
			if (get_comments_number() == 0) {
				$comment_title = sprintf(__('Leave a comment on: &ldquo;%s&rdquo;', 'noo') , get_the_title());
				$comment_number = __(' Leave a Comment', 'noo');
			} else if (get_comments_number() == 1) {
				$comment_title = sprintf(__('View a comment on: &ldquo;%s&rdquo;', 'noo') , get_the_title());
				$comment_number = ' 1 ' . __('Comment', 'noo');
			} else {
				$comment_title = sprintf(__('View all comments on: &ldquo;%s&rdquo;', 'noo') , get_the_title());
				$comment_number =  ' ' . get_comments_number() . ' ' . __('Comments', 'noo');
			}
				
			$comments_html.= '<span><a' . ' href="' . esc_url(get_comments_link()) . '"' . ' title="' . esc_attr($comment_title) . '"' . ' class="meta-comments">';
			$comments_html.= '<i class="fa fa-comments"></i> ';
			$comments_html.=  $comment_number . '</a></span>';
		}
		if(!$hide_comment)
			$html[] = $comments_html;
		$html[] = '</p>';
		echo implode($html, "\n");
	}
endif;

if (!function_exists('noo_get_readmore_link')):
	function noo_get_readmore_link() {
		return '<a href="' . get_permalink() . '" class="read-more">'
		. __('Continue reading', 'noo' ) 
		. '</a>';
	}
endif;

if (!function_exists('noo_readmore_link')):
	function noo_readmore_link() {
		if( noo_get_option('noo_blog_show_readmore', 1 ) ) {
			echo noo_get_readmore_link();
		} else {
			echo '';
		}
	}
endif;

if (!function_exists('noo_list_comments')):
	function noo_list_comments($comment, $args, $depth) {
		$GLOBALS['comment'] = $comment;
		GLOBAL $post;
		$avatar_size = isset($args['avatar_size']) ? $args['avatar_size'] : 60;
		?>
		<li id="li-comment-<?php comment_ID(); ?>" <?php comment_class(); ?>>
			<div class="comment-wrap">
				<div class="comment-img">
					<?php echo get_avatar($comment, $avatar_size); ?>
				</div>
				<article id="comment-<?php comment_ID(); ?>" class="comment-block">
					<header class="comment-header">
						<cite class="comment-author"><?php echo get_comment_author_link(); ?> 
							<?php if ($comment->user_id === $post->post_author): ?>
							<span class="ispostauthor">
								<?php _e('Author', 'noo'); ?>
							</span>
							<?php endif; ?>
						</cite>
						
						<div class="comment-meta">
							<time datetime="<?php echo esc_html(noo_relative_time(get_the_date('j M y'))); ?>">
								<?php echo noo_relative_time(); ?>
							</time>
							<span class="comment-edit">
								<?php edit_comment_link('' . __('Edit', 'noo')); ?>
							</span>
						</div>
						<?php if ('0' == $comment->comment_approved): ?>
							<p class="comment-pending"><?php _e('Your comment is awaiting moderation.', 'noo'); ?></p>
						<?php endif; ?>
					</header>
					<section class="comment-content">
						<?php comment_text(); ?>
					</section>
					<span class="comment-reply">
						<i class="fa fa-reply"></i>
						<?php comment_reply_link(array_merge($args, array(
							'reply_text' => (__('Reply', 'noo') . '') ,
							'depth' => $depth,
							'max_depth' => $args['max_depth']
						))); ?>
					</span>
				</article>
			</div>
		<?php
	}
endif;

if ( ! function_exists('noo_comment_form') ) :
	function noo_comment_form( $args = array(), $post_id = null ) {
		global $id;
		$user = wp_get_current_user();
		$user_identity = $user->exists() ? $user->display_name : '';

		if ( null === $post_id ) {
			$post_id = $id;
		}
		else {
			$id = $post_id;
		}

		if ( comments_open( $post_id ) ) :
		?>
		<div id="respond-wrap">
			<?php 
				$commenter = wp_get_current_commenter();
				$req = get_option( 'require_name_email' );
				$aria_req = ( $req ? " aria-required='true'" : '' );
				$fields =  array(
					'author' => '<div class="row"><div class="col-sm-12"><p class="comment-form-author"><input id="author" name="author" type="text" placeholder="' . __( 'Name*', 'noo' ) . '" class="form-control" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30"' . $aria_req . ' /></p>',
					'email' => '<p class="comment-form-email"><input id="email" name="email" type="text" placeholder="' . __( 'Email*', 'noo' ) . '" class="form-control" value="' . esc_attr(  $commenter['comment_author_email'] ) . '" size="30"' . $aria_req . ' /></p>',
					'url' => '<p class="comment-form-url"><input id="url" name="url" type="text" placeholder="' . __( 'Website', 'noo' ) . '" class="form-control" value="' . esc_attr( $commenter['comment_author_url'] ) . '" size="30" /></p></div>',
					'comment_field'		   => '<div class="col-sm-12"><p class="comment-form-comment"><textarea class="form-control" id="comment" name="comment" cols="40" rows="6" aria-required="true"></textarea></p></div></div>'
				);
				$comments_args = array(
						'fields'               => apply_filters( 'comment_form_default_fields', $fields ),
						'logged_in_as'		   => '<p class="logged-in-as">' . sprintf( __( 'Logged in as <a href="%1$s">%2$s</a>. <a href="%3$s" title="Log out of this account">Log out?</a>', 'noo' ), admin_url( 'profile.php' ), $user_identity, wp_logout_url( apply_filters( 'the_permalink', get_permalink( ) ) ) ) . '</p>',
						'title_reply'          => sprintf('<span>%s</span>',__( 'Leave your thought', 'noo' )),
						'title_reply_to'       => sprintf('<span>%s</span>',__( 'Leave a reply to %s', 'noo' )),
						'cancel_reply_link'    => __( 'Click here to cancel the reply', 'noo' ),
						'comment_notes_before' => '',
						'comment_notes_after'  => '',
						'label_submit'         => __( 'Submit', 'noo' ),
						'comment_field'		   =>'',
						'must_log_in'		   => ''
				);
				if(is_user_logged_in()){
					$comments_args['comment_field'] = '<p class="comment-form-comment"><textarea class="form-control" id="comment" name="comment" cols="40" rows="6" aria-required="true"></textarea></p>';
				}
			comment_form($comments_args); 
			?>
		</div>

		<?php
		endif;
	}
endif;

if ( ! function_exists('noo_post_nav') ) :
	function noo_post_nav() {
		global $post;

		// Don't print empty markup if there's nowhere to navigate.
		$previous = ( is_attachment() ) ? get_post( $post->post_parent ) : get_adjacent_post( false, '', true );
		$next     = get_adjacent_post( false, '', false );

		if ( ! $next && ! $previous )
			return;
		?>
		<?php $prev_link = get_previous_post_link( '%link', __( 'Previous post', 'noo' ) ); ?>
		<?php $next_link = get_next_post_link( '%link', __( 'Next post', 'noo' ) ); ?>
		<nav class="post-navigation<?php echo( (!empty($prev_link) || !empty($next_link) ) ? ' post-navigation-line':'' )?>" role="navigation">
			<div class="row">
				<div class="col-sm-6">			
				<?php if($prev_link):?>
					<div class="prev-post">
						<i class="fa fa-long-arrow-left">&nbsp;</i>
						<?php echo $prev_link?>
					</div>
				<?php endif;?>
				</div>
				<div class="col-sm-6">			
				<?php if(!empty($next_link)):?>
					<div class="next-post">
						<?php echo $next_link;?>
						<i class="fa fa-long-arrow-right">&nbsp;</i>
					</div>
				<?php endif;?>
				</div>
			</div>
		</nav>
		<?php
	}
endif;

if ( ! function_exists( 'noo_portfolio_attributes' ) ) :
	function noo_portfolio_attributes( $post_id = null ) {
		if ( noo_get_option( 'noo_portfolio_enable_attribute', true ) === false) {
			return '';
		}

		$post_id = (null === $post_id) ? get_the_id() : $post_id;
		$attributes = get_the_terms( $post_id, 'portfolio_tag' );

		$html = array();
		$html[] = '<ul class="list-unstyled attribute-list">';
		$i=0;
		foreach( $attributes as $attribute ) {
			$html[] = '<li class="'.($i % 2 == 0 ? 'odd':'even').'">';
			$html[] = '<a href="' . get_term_link( $attribute->slug, 'portfolio_tag' ) . '">';
			$html[] = '<i class="fa fa-check"></i>';
			$html[] = $attribute->name;
			$html[] = '</a>';
			$html[] = '</li>';
			$i++;
		};
		$html[] = '</ul>';

		echo implode($html, "\n");
	}
endif;

if ( ! function_exists( 'noo_social_share' ) ) :
	function noo_social_share( $post_id = null ) {
		$post_id = (null === $post_id) ? get_the_id() : $post_id;
		$post_type =  get_post_type($post_id);
		$prefix = 'noo_blog';

		if($post_type == 'portfolio_project' ) {
			$prefix = 'noo_portfolio';
		}

		if(noo_get_option("{$prefix}_social", true ) === false) {
			return '';
		}

		$share_url     = urlencode( get_permalink() );
		$share_title   = urlencode( get_the_title() );
		$share_source  = urlencode( get_bloginfo( 'name' ) );
		$share_content = urlencode( get_the_content() );
		$share_media   = wp_get_attachment_thumb_url( get_post_thumbnail_id() );
		$popup_attr    = 'resizable=0, toolbar=0, menubar=0, status=0, location=0, scrollbars=0';

		$facebook     = noo_get_option( "{$prefix}_social_facebook", true );
		$twitter      = noo_get_option( "{$prefix}_social_twitter", true );
		$google		  = noo_get_option( "{$prefix}_social_google", true );
		$pinterest    = noo_get_option( "{$prefix}_social_pinterest", true );
		$linkedin     = noo_get_option( "{$prefix}_social_linkedin", true );

		$html = array();

		if ( $facebook || $twitter || $google || $pinterest || $linkedin ) {
			$html[] = '<div class="content-share">';
			// $html[] = '<p class="share-title">';
			// $html[] = '</p>';
			$html[] = '<div class="noo-social social-share">';
			$html[] = '<span class="noo-social-title">';
			$html[] = __("Share",'noo');
			$html[] = '</span>';
			if($facebook) {
				$html[] = '<a href="#share" class="noo-share"'
						. ' title="' . __( 'Share on Facebook', 'noo' ) . '"'
								. ' onclick="window.open('
										. "'http://www.facebook.com/sharer.php?u={$share_url}&amp;t={$share_title}','popupFacebook','width=650,height=270,{$popup_attr}');"
										. ' return false;">';
				$html[] = '<i class="fa fa-facebook"></i>';
				$html[] = '</a>';
			}

			if($twitter) {
				$html[] = '<a href="#share" class="noo-share"'
						. ' title="' . __( 'Share on Twitter', 'noo' ) . '"'
								. ' onclick="window.open('
										. "'https://twitter.com/intent/tweet?text={$share_title}&amp;url={$share_url}','popupTwitter','width=500,height=370,{$popup_attr}');"
										. ' return false;">';
				$html[] = '<i class="fa fa-twitter"></i></a>';
			}

			if($google) {
				$html[] = '<a href="#share" class="noo-share"'
						. ' title="' . __( 'Share on Google+', 'noo' ) . '"'
								. ' onclick="window.open('
								. "'https://plus.google.com/share?url={$share_url}','popupGooglePlus','width=650,height=226,{$popup_attr}');"
								. ' return false;">';
								$html[] = '<i class="fa fa-google-plus"></i></a>';
			}

			if($pinterest) {
				$html[] = '<a href="#share" class="noo-share"'
						. ' title="' . __( 'Share on Pinterest', 'noo' ) . '"'
								. ' onclick="window.open('
										. "'http://pinterest.com/pin/create/button/?url={$share_url}&amp;media={$share_media}&amp;description={$share_title}','popupPinterest','width=750,height=265,{$popup_attr}');"
										. ' return false;">';
				$html[] = '<i class="fa fa-pinterest"></i></a>';
			}

			if($linkedin) {
				$html[] = '<a href="#share" class="noo-share"'
						. ' title="' . __( 'Share on LinkedIn', 'noo' ) . '"'
								. ' onclick="window.open('
										. "'http://www.linkedin.com/shareArticle?mini=true&amp;url={$share_url}&amp;title={$share_title}&amp;summary={$share_content}&amp;source={$share_source}','popupLinkedIn','width=610,height=480,{$popup_attr}');"
										. ' return false;">';
				$html[] = '<i class="fa fa-linkedin"></i></a>';
			}

			$html[] = '</div>'; // .noo-social.social-share
			$html[] = '</div>'; // .share-wrap
		}

		echo implode("\n", $html);
	}
endif;

if (!function_exists('noo_social_icons')):
	function noo_social_icons($position = 'topbar', $direction = '') {
		if ($position == 'topbar') {
			// Top Bar social
		} else {
			// Bottom Bar social
		}
		
		$class = isset($direction) ? $direction : '';
		$html = array();
		$html[] = '<div class="noo-social social-icons ' . $class . '">';
		
		$social_list = array(
			'facebook' => __('Facebook', 'noo') ,
			'twitter' => __('Twitter', 'noo') ,
			'google-plus' => __('Google+', 'noo') ,
			'pinterest' => __('Pinterest', 'noo') ,
			'linkedin' => __('LinkedIn', 'noo') ,
			'rss' => __('RSS', 'noo') ,
			'youtube' => __('YouTube', 'noo') ,
			'instagram' => __('Instagram', 'noo') ,
		);
		
		$social_html = array();
		foreach ($social_list as $key => $title) {
			$social = noo_get_option("noo_social_{$key}", '');
			if ($social) {
				$social_html[] = '<a href="' . $social . '" title="' . $title . '" target="_blank">';
				$social_html[] = '<i class="fa fa-' . $key . '"></i>';
				$social_html[] = '</a>';
			}
		}
		
		if(empty($social_html)) {
			$social_html[] = __('No Social Media Link','noo');
		}
		
		$html[] = implode($social_html, "\n");
		$html[] = '</div>';
		
		echo implode($html, "\n");
	}
endif;

if(!function_exists('noo_gototop')):
	function noo_gototop(){
		if( noo_get_option( 'noo_back_to_top', true ) ) {
			echo '<a href="#" class="go-to-top hidden-print"><i class="fa fa-angle-up"></i></a>';
		}
		return ;
	}
	add_action('wp_footer','noo_gototop');
endif;

/* -------------------------------------------------------
 * Create functions setting_custom_field
 * ------------------------------------------------------- */

if ( ! function_exists( 'setting_custom_field' ) ) :
	
	function setting_custom_field( $name_settings, $default_fields, $custom_fields, $field_display, $class ) {
		if(isset($_GET['settings-updated']) && $_GET['settings-updated']) {
			if( function_exists('icl_object_id') ) {
				$field_prefix = $name_settings == 'noo_resume[custom_field]' ? 'noo_resume_field_' : 'noo_job_field_';
				foreach ($custom_fields as $custom_field) {
					do_action( 'wpml_register_single_string', 'NOO Custom Fields', $field_prefix.sanitize_title(@$custom_field['name']), @$custom_field['label'] );
				}
			}
		}
		
		settings_fields( $name_settings == 'noo_resume[custom_field]' ? 'noo_resume' : $name_settings ); 

		$blank_field = array( 'name' => '', 'label' => '', 'type' => 'text', 'value' => '', 'required' => '', 'is_disabled' => '' );
		
		// -- Check value
			$custom_fields = $custom_fields ? $custom_fields : array();
			$default_fields = $default_fields ? $default_fields : array();
			$field_display = !empty($field_display) ? $field_display : '';

		$fields = array_merge( array_diff_key($default_fields, $custom_fields), $custom_fields );

		?>
		<h3><?php echo __('Custom Fields','noo')?></h3>
		<table class="form-table" cellspacing="0">
			<tbody>
				<tr>
					<!-- <th>
						<?php //esc_html_e('Fields','noo')?>
					</th> -->
					<td>
						<?php 
							$num_arr = count($fields) ? array_map( 'absint', array_keys($fields) ) : array();
							$num = !empty($num_arr) ? end($num_arr) : 1;
						?>
						<table class="widefat <?php echo $class ?>_table" data-num="<?php echo esc_attr( $num ); ?>" cellspacing="0" >
							<thead>
								<tr>
									<th style="padding: 9px 7px">
										<?php esc_html_e('Field Key','noo')?>
									</th>
									<th style="padding: 9px 7px">
										<?php esc_html_e('Field Label','noo')?>
									</th>
									<th style="padding: 9px 7px">
										<?php esc_html_e('Field Type','noo')?>
									</th>
									<th style="padding: 9px 7px">
										<?php esc_html_e('Field Value','noo')?>
									</th>
									<th style="padding: 9px 7px">
										<?php esc_html_e('Is Mandatory?','noo')?>
									</th>
									<th style="padding: 9px 7px">
										<?php esc_html_e('Action','noo')?>
									</th>
								</tr>
							</thead>
							<tbody>
								<?php  if(!empty($fields)): ?>
									<?php foreach ($fields as $field):
										$field = is_array( $field ) ? array_merge( $blank_field, $field ) : $blank_field; 
										if( !isset($field['name']) || empty($field['name'])) continue;

										$key = $field['name'];
										$is_default = array_key_exists($key, $default_fields);
										$is_disabled = $is_default && isset( $field['is_disabled'] ) && ($field['is_disabled'] == 'yes');
										$field['type'] = isset($field['type']) ? $field['type'] : 'text';
										$required = !empty($field['required']) ? 'checked' : '';
									?>
									<tr data-stt="<?php echo esc_attr($key)?>" <?php echo ($is_disabled ? 'class="noo-disable-field"' : ''); ?>>
										<td>
											<input type="text" value="<?php echo esc_attr($field['name'])?>" <?php echo ( $is_default ? 'readonly="readonly"' : '' ); ?> placeholder="<?php _e('Field Key','noo')?>" name="<?php echo $name_settings; ?>[<?php echo esc_attr($key)?>][name]">
										</td>
										<td>
											<input type="text" value="<?php echo esc_attr($field['label'])?>" placeholder="<?php _e('Field Label','noo')?>" name="<?php echo $name_settings; ?>[<?php echo esc_attr($key)?>][label]">
										</td>
										<td>
											<select<?php echo ( $is_default ? ' disabled' : '' ); ?> name="<?php echo $name_settings; ?>[<?php echo esc_attr($key)?>][type]">
												<option value="text"<?php echo (esc_attr($field['type']) === "text" ) ? " selected" : ''; ?>><?php echo _e( 'Text', 'noo' ); ?></option>
												<option value="number"<?php echo (esc_attr($field['type']) === "number" ) ? " selected" : ''; ?>><?php echo _e( 'Number', 'noo' ); ?></option>
												<option value="textarea"<?php echo (esc_attr($field['type']) === "textarea" ) ? " selected" : ''; ?>><?php echo _e( 'Textarea', 'noo' ); ?></option>
												<option value="select"<?php echo (esc_attr($field['type']) === "select" ) ? " selected" : ''; ?>><?php echo _e( 'Select', 'noo' ); ?></option>
												<option value="multiple_select"<?php echo (esc_attr($field['type']) === "multiple_select" ) ? " selected" : ''; ?>><?php echo _e( 'Multiple Select', 'noo' ); ?></option>
												<option value="radio"<?php echo (esc_attr($field['type']) === "radio" ) ? " selected" : ''; ?>><?php echo _e( 'Radio', 'noo' ); ?></option>
												<option value="checkbox"<?php echo (esc_attr($field['type']) === "checkbox" ) ? " selected" : ''; ?>><?php echo _e( 'Checkbox', 'noo' ); ?></option>
											</select>
										</td>
										<td>
											<textarea placeholder="<?php _e('Field Value','noo')?>" name="<?php echo $name_settings; ?>[<?php echo esc_attr($key)?>][value]"><?php echo $field['value'];?></textarea>
										</td>
										<td>
											<input type="checkbox" value="true" name="<?php echo $name_settings; ?>[<?php echo esc_attr($key)?>][required]" <?php echo $required ?>/>
											<?php _e('Yes','noo')?>
										</td>
										<td>
											<?php if( $is_default ) : ?>
												<input type="hidden" value="<?php echo ($is_disabled ? 'yes' : 'no'); ?>" name="<?php echo $name_settings; ?>[<?php echo esc_attr($key)?>][is_disabled]">
												<input class="button button-primary" onclick="return toggle_disable_noo_resume_custom_field(this);" type="button" value="<?php echo ( $is_disabled ? __('Enable','noo') : __('Disable','noo') );?>">
											<?php else : ?>
												<input class="button button-primary" onclick="return delete_noo_resume_custom_field(this);" type="button" value="<?php _e('Delete','noo')?>">
											<?php endif; ?>
										</td>
									</tr>
									<?php endforeach; ?>
								<?php endif;?>
							</tbody>
							<tfoot>
								<tr>
									<td colspan="6">
										<input class="button button-primary" id="add_<?php echo $class; ?>" type="button" value="<?php esc_attr_e('Add','noo')?>">
									</td>
								</tr>
							</tfoot>
						</table>
					</td>
				</tr>
			</tbody>
		</table>
		<?php if ( $field_display || $name_settings == 'noo_job_custom_field' ) : ?>
			<table class="form-table" cellspacing="0">
				<tbody>
						<tr>
							<th>
								<?php _e('Show Custom Fields:','noo') ?>
							</th>
							<td>
								<select class="regular-text" name="<?php echo $name_settings; ?>[__options__][display_position]">
									<option <?php selected( $field_display,'before')?> value="before"><?php _e('Before Description','noo')?></option>
									<option <?php selected( $field_display,'after')?>  value="after"><?php _e('After Description','noo')?></option>
								</select>
							</td>
						</tr>
				</tbody>
			</table>
		<?php endif; ?>
		<?php
	}

endif;

/** ====== END setting_custom_field ====== **/

/* -------------------------------------------------------
 * Create functions noo_show_custom_fields
 * ------------------------------------------------------- */

if ( ! function_exists( 'noo_show_custom_fields' ) ) :
	
	function noo_show_custom_fields( $field, $id_type, $id_fields ) {

		$field['required']= isset($field['required']) && $field['required'] ? ' class="jform-validate" required aria-required="true"' : '';
		$value = noo_get_post_meta( $id_type, $id_fields, '' );
		$value = !is_array($value) ? trim($value) : $value;
		if ( $field['type'] === "text" || $field['type'] === "number"  ) : ?>
			<input id="<?php echo esc_attr($id_fields)?>" class="form-control" type="<?php echo $field['type']; ?>" name="<?php echo esc_attr($id_fields)?>" value="<?php echo esc_attr($value); ?>" placeholder="<?php echo $field['value']; ?>" <?php echo $field['required']; ?>/>
		<?php elseif ( $field['type'] === "textarea" ) : ?>
			<textarea class="form-control" id="<?php echo esc_attr($id_fields)?>"  name="<?php echo esc_attr($id_fields)?>" placeholder="<?php echo $field['value']; ?>" rows="8" <?php echo $field['required']; ?>><?php echo esc_html($value); ?></textarea>
		<?php else : ?>
			<?php
				$list_value = $field['value'];
				$list_option = explode( "\n", $list_value );
			?>
			<?php if ( $field['type'] === "select" || $field['type'] === "multiple_select" ) : 
				if( $field['type'] === 'multiple_select' && !is_array( $value ) ) $value = noo_json_decode( $value );
			?>
				<select id="<?php echo esc_attr($id_fields)?>" class="form-control form-control-chosen ignore-valid <?php echo ( !empty( $field['required'] ) ? 'jform-chosen-validate' : '' ); ?>" name="<?php echo esc_attr($id_fields) . ( $field['type'] == 'multiple_select' ? '[]' : '' ) ?>"<?php echo ( $field['type'] == 'multiple_select' ? ' multiple' : '' ); ?> data-placeholder="<?php echo sprintf(__("Select %s",'noo'), $field['label'] ); ?>" <?php echo $field['required']; ?>>
					<?php
						foreach ($list_option as $index => $option) {
							$option_key = explode( '|', $option );
							$option_key[0] = trim( $option_key[0] );
							// if( empty( $option_key[0] ) ) continue;
							$option_key[1] = isset( $option_key[1] ) ? $option_key[1] : $option_key[0];
							if( is_array( $value ) ) {
								$selected = in_array($option_key[0], $value) ? 'selected="selected"' : '';
							} else {
								$selected = ( $option_key[0] == $value ) ? 'selected="selected"' : '';
							}
							echo "<option value='{$option_key[0]}' {$selected}>{$option_key[1]}</option>";
						}
					?>
				</select>
			<?php elseif ( $field['type'] === "radio" ) : ?>
				<?php
					foreach ($list_option as $index => $option) {
						$option_key = explode( '|', $option );
						$option_key[0] = trim( $option_key[0] );
						if( empty( $option_key[0] ) ) continue;
						$option_key[1] = isset( $option_key[1] ) ? $option_key[1] : $option_key[0];
						$checked = ( ( empty( $value ) && $index == 0 ) || ( $option_key[0] == $value ) ) ? 'checked="checked"' : '';
						echo "<div id='{$id_fields}' class='form-control-flat'><label class='radio'><input type='radio' name='{$id_fields}' value='{$option_key[0]}' {$field['required']} {$checked}><i></i>{$option_key[1]}</label></div>";
					}
				?>
			<?php elseif ( $field['type'] === "checkbox" ) : 
				if( !is_array( $value ) ) $value = noo_json_decode( $value );
			?>
				<?php
					foreach ($list_option as $option) {
						$option_key = explode( '|', $option );
						$option_key[0] = trim( $option_key[0] );
						if( empty( $option_key[0] ) ) continue;
						$option_key[1] = isset( $option_key[1] ) ? $option_key[1] : $option_key[0];
						$checked = in_array($option_key[0], $value) ? 'checked="checked"' : '';
						?>
						<div class="checkbox">
							<div class="form-control-flat">
								<label class="checkbox">
									<input id="<?php echo esc_attr($id_fields)?>" name="<?php echo $id_fields; ?>[]" type="checkbox" <?php echo $field['required']; ?> <?php echo $checked; ?> value="<?php echo $option_key[0]; ?>" /><i></i> 
									<?php echo $option_key[1]; ?>
								</label>
							</div>
						</div>
						<?php
					}
				endif;
		endif;

	}

endif;

/** ====== END noo_show_custom_fields ====== **/

/* -------------------------------------------------------
 * Create functions noo_display_custom_fields: display the fields on frontend.
 * ------------------------------------------------------- */

if ( ! function_exists( 'noo_display_custom_fields' ) ) :
	
	function noo_display_custom_fields( $field = array(), $value = '', $id = '', $type = '', $label = '' ) {

		if( empty($type) && isset($field['type'] ) ) $type = $field['type']; 
		if( empty($id) && isset($field['id'] ) ) $id = $field['id']; 
		if( empty($label) && isset($field['label'] ) ) $label = $field['label']; 
		// if( empty($value) && isset($field['value'] ) ) $value = $field['value'];

		$value = !is_array($value) ? trim($value) : $value;
		if( empty( $type ) || empty( $value ) ) return;

		if ( $field['type'] === 'multiple_select' ) : 
			$value = !is_array( $value ) ? noo_json_decode( $value ) : $value;
			$value = implode(', ', $value);
		?>
			<h3 class="label-<?php echo $id; ?>"><?php esc_html_e($label)?></h3>
			<p class="value-<?php echo $id; ?>"><?php echo esc_html( $value ); ?></p>
		<?php elseif ( $field['type'] === 'checkbox' || $field['type'] === 'radio' ) : 
			$value = !is_array( $value ) ? noo_json_decode( $value ) : $value;
		?>
			<h3 class="label-<?php echo $id; ?>"><?php esc_html_e($label); ?></h3>
			<?php foreach ($value as $v) : ?>
				<div class="value-<?php echo $id ?> <?php echo $v ?>">
					<i class="fa fa-check-circle"></i>
					<?php echo esc_html_e($v); ?>
				</div>
			<?php endforeach; ?>
		<?php elseif( $field['type'] === "textarea" ) : ?> 
			<h3 class="label-<?php echo $id; ?>"><?php esc_html_e($label); ?></h3>
			<p class="value-<?php echo $id; ?>"><?php echo do_shortcode( $value ); ?></p>
		<?php else : ?> 
			<h3 class="label-<?php echo $id; ?>"><?php esc_html_e($label)?></h3>
			<p class="value-<?php echo $id; ?>"><?php echo esc_html( $value ); ?></p>
		<?php endif;
	}

endif;

/** ====== END noo_display_custom_fields ====== **/
