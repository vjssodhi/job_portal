<?php

class Noo_Wiget extends WP_Widget {

	public $widget_cssclass;

	public $widget_description;

	public $widget_id;

	public $widget_name;

	public $settings;

	public $cached = true;

	/**
	 * Constructor
	 */
	public function __construct() {
		$widget_ops = array( 'classname' => $this->widget_cssclass, 'description' => $this->widget_description );
		
		parent::__construct( $this->widget_id, $this->widget_name, $widget_ops );

		if ( $this->cached ) {
			add_action( 'save_post', array( $this, 'flush_widget_cache' ) );
			add_action( 'deleted_post', array( $this, 'flush_widget_cache' ) );
			add_action( 'switch_theme', array( $this, 'flush_widget_cache' ) );
		}
	}

	/**
	 * get_cached_widget function.
	 */
	function get_cached_widget( $args ) {
		$cache = wp_cache_get( apply_filters( 'dh_cached_widget_id', $this->widget_id ), 'widget' );
		
		if ( ! is_array( $cache ) ) {
			$cache = array();
		}
		
		if ( isset( $cache[$args['widget_id']] ) ) {
			echo $cache[$args['widget_id']];
			return true;
		}
		
		return false;
	}

	/**
	 * Cache the widget
	 * @param string $content
	 */
	public function cache_widget( $args, $content ) {
		$cache[$args['widget_id']] = $content;
		
		wp_cache_set( apply_filters( 'dh_cached_widget_id', $this->widget_id ), $cache, 'widget' );
	}

	/**
	 * Flush the cache
	 *
	 * @return void
	 */
	public function flush_widget_cache() {
		wp_cache_delete( apply_filters( 'dh_cached_widget_id', $this->widget_id ), 'widget' );
	}

	/**
	 * update function.
	 *
	 * @see WP_Widget->update
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		if ( ! $this->settings ) {
			return $instance;
		}
		
		foreach ( $this->settings as $key => $setting ) {
			
			if ( isset( $setting['multiple'] ) ) :
				$instance[$key] = implode( ',', $new_instance[$key] );
			 else :
				if ( isset( $new_instance[$key] ) ) {
					$instance[$key] = sanitize_text_field( $new_instance[$key] );
				} elseif ( 'checkbox' === $setting['type'] ) {
					$instance[$key] = 0;
				}
			endif;
		}
		if ( $this->cached ) {
			$this->flush_widget_cache();
		}
		
		return $instance;
	}

	/**
	 * form function.
	 *
	 * @see WP_Widget->form
	 * @param array $instance
	 */
	public function form( $instance ) {
		if ( ! $this->settings ) {
			return;
		}

		foreach ( $this->settings as $key => $setting ) {
			$value = isset( $instance[$key] ) ? $instance[$key] : $setting['std'];
			switch ( $setting['type'] ) {
				case "text" :
					?>
					<p>
						<label for="<?php echo $this->get_field_id( $key ); ?>"><?php echo esc_html($setting['label']); ?></label>
						<input class="widefat"
							id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"
							name="<?php echo $this->get_field_name( $key ); ?>" type="text"
							value="<?php echo esc_attr( $value ); ?>" />
					</p>
					<?php
					break;
				
				case "number" :
					?>
					<p>
						<label for="<?php echo $this->get_field_id( $key ); ?>"><?php echo esc_html($setting['label']); ?></label>
						<input class="widefat"
							id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"
							name="<?php echo $this->get_field_name( $key ); ?>" type="number"
							step="<?php echo esc_attr( $setting['step'] ); ?>"
							min="<?php echo esc_attr( $setting['min'] ); ?>"
							max="<?php echo esc_attr( $setting['max'] ); ?>"
							value="<?php echo esc_attr( $value ); ?>" />
					</p>
					<?php
					break;
				case "select" :
					if ( isset( $setting['multiple'] ) ) :
						$value = explode( ',', $value );
					endif;
					?>
					<p>
						<label for="<?php echo $this->get_field_id( $key ); ?>"><?php echo esc_html($setting['label']); ?></label>
						<select class="widefat"
							id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"
							<?php if(isset($setting['multiple'])):?> multiple="multiple" <?php endif;?>
							name="<?php echo $this->get_field_name( $key ); ?><?php if(isset($setting['multiple'])):?>[]<?php endif;?>">
							<?php foreach ( $setting['options'] as $option_key => $option_value ) : ?>
								<option value="<?php echo esc_attr( $option_key ); ?>"
									<?php if(isset($setting['multiple'])): selected( in_array ( $option_key, $value ) , true ); else: selected( $option_key, $value ); endif; ?>><?php echo esc_html( $option_value ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</p>
					<?php
					if ( @$setting['multi_fields'] == true ) : ?>
						<button id="add_multi_fields"><span class="dashicons dashicons-plus"></span></button>
						<script type="text/javascript">
						jQuery(document).ready(function($) {
							var i = 0;
							$('button#add_multi_fields').on('click', 'span', function(event) {
								event.preventDefault();
							});
						});
						</script>
					<?php endif;
					break;
				
				case "checkbox" :
					?>
					<p>
						<input id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"
							name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>"
							type="checkbox" value="1" <?php checked( $value, 1 ); ?> /> <label
							for="<?php echo $this->get_field_id( $key ); ?>"><?php echo esc_html($setting['label']); ?></label>
					</p>
					<?php
					break;
			}
		}
	}

}

class Noo_MailChimp extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'noo_mailchimp_widget',  // Base ID
			'Noo MailChimps',  // Name
			array( 'classname' => 'mailchimp-widget', 'description' => __( 'Display simple MailChimp subscribe form.', 'noo' ) ) );
	}

	public function widget( $args, $instance ) {
		extract( $args );
		if ( ! empty( $instance['title'] ) ) {
			$title = apply_filters( 'widget_title', $instance['title'] );
		}
		echo $before_widget;
		if ( ! empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}

		?>
<form class="mc-subscribe-form<?php echo (isset($_COOKIE['noo_subscribed']) ? ' submited':'')?>">
			<?php if( isset($_COOKIE['noo_subscribed']) ) : ?>
			<label class="noo-message alert" role="alert"><?php _e( 'You\'ve already subscribed.', 'noo' ); ?></label>
			<?php else: ?> 
			<label for="email"><?php echo esc_attr( $instance['subscribe_text'] ); ?></label>

	<div class="mc-email-wrap">
		<input type="email" id="email" name="mc_email"
			class="form-control mc-email" value=""
			placeholder="<?php _e( 'Enter your email here...', 'noo' ); ?>" />
	</div>
	<input type="hidden" name="mc_list_id"
		value="<?php echo esc_attr( @$instance['mail_list'] ); ?>" /> <input
		type="hidden" name="action" value="noo_mc_subscribe" />
			<?php wp_nonce_field('noo-subscribe','nonce'); ?>
			<?php endif; ?>
		</form>
<?php
		echo $after_widget;
	}

	public function form( $instance ) {
		$defaults = array( 
			'title' => '', 
			'subscribe_text' => __( 'Subscribe to stay update', 'noo' ),
			'mail_list' => '');
		$instance = wp_parse_args( (array) $instance, $defaults );

		global $noo_mailchimp;
		$api_key = noo_get_option('noo_mailchimp_api_key', '');
		$mail_list = !empty( $api_key ) ? $noo_mailchimp->get_mail_lists( $api_key ) : '';
		
		echo '
		<p>
			<label>' . __( 'Title', 'noo' ) . ':</label>
			<input type="text" name="' .
			 $this->get_field_name( 'title' ) . '" id="' . $this->get_field_id( 'title' ) . '" value="' .
			 esc_attr( $instance['title'] ) . '" class="widefat" />
		</p>
		<p>
			<label>' . __( 'Subscribe Text', 'noo' ) . ':</label>
			<input type="text" name="' .
			 $this->get_field_name( 'subscribe_text' ) . '" id="' . $this->get_field_id( 'subscribe_text' ) . '" value="' .
			 esc_attr( $instance['subscribe_text'] ) . '" class="widefat" />
		</p>';
		if(!empty($mail_list)) {
		echo '
		<p>
			<label>' . __( 'Subscribe Mail List', 'noo' ) . ':</label>
			<select name="' .
			 $this->get_field_name( 'mail_list' ) . '" id="' . $this->get_field_id( 'mail_list' ) . '" class="widefat" >';
			foreach($mail_list as $id => $list_name) {
				echo '<option value="' . $id . '" ' . selected( $instance['mail_list'], $id, false ) . '>' . $list_name . '</option>';
			}
			echo '	</select>
		</p>';
		} else {
			$customizer_general_link = esc_url( add_query_arg( array('autofocus%5Bsection%5D' => 'noo_customizer_section_site_enhancement'), admin_url( '/customize.php' ) ) );
			echo '<p><strong>' . sprintf( __( 'There\'s a problem getting your mail list, please check your API key at MailChimp Settings in <a href="%s" target="_blank">Customizer</a>', 'noo' ), $customizer_general_link ) . '</strong></p>';
		}
	}
}


class Noo_Tweets extends WP_Widget {

	public function __construct() {
		parent::__construct( 
			'dh_tweets',  // Base ID
			'Recent Tweets',  // Name
			array( 'classname' => 'tweets-widget', 'description' => __( 'Display recent tweets', 'noo' ) ) );
	}

	public function widget( $args, $instance ) {
		extract( $args );
		if ( ! empty( $instance['title'] ) ) {
			$title = apply_filters( 'widget_title', $instance['title'] );
		}
		echo $before_widget;
		if ( ! empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}
		
		// check settings and die if not set
		if ( empty( $instance['consumerkey'] ) || empty( $instance['consumersecret'] ) || empty( 
			$instance['accesstoken'] ) || empty( $instance['accesstokensecret'] ) || empty( $instance['cachetime'] ) ||
			 empty( $instance['username'] ) ) {
			echo '<strong>' . __( 'Please fill all widget settings!', 'noo' ) . '</strong>' . $after_widget;
			return;
		}
		
		$noo_widget_recent_tweets_cache_time = get_option( 'noo_widget_recent_tweets_cache_time' );
		$diff = time() - $noo_widget_recent_tweets_cache_time;
		
		$crt = (int) $instance['cachetime'] * 3600;
		
		if($diff >= $crt || empty($noo_widget_recent_tweets_cache_time)){
			
			if ( ! require_once ( dirname(__FILE__) . '/twitteroauth.php' ) ) {
				echo '<strong>' . __( 'Couldn\'t find twitteroauth.php!', 'noo' ) . '</strong>' . $after_widget;
				return;
			}

			function getConnectionWithAccessToken( $cons_key, $cons_secret, $oauth_token, $oauth_token_secret ) {
				$connection = new TwitterOAuth( $cons_key, $cons_secret, $oauth_token, $oauth_token_secret );
				return $connection;
			}
			
			$connection = getConnectionWithAccessToken( 
				$instance['consumerkey'], 
				$instance['consumersecret'], 
				$instance['accesstoken'], 
				$instance['accesstokensecret'] );
			$tweets = $connection->get( 
				"https://api.twitter.com/1.1/statuses/user_timeline.json?screen_name=" . $instance['username'] .
					 "&count=10&exclude_replies=" . $instance['excludereplies'] );
			
			if ( ! empty( $tweets->errors ) ) {
				if ( $tweets->errors[0]->message == 'Invalid or expired token' ) {
					echo '<strong>' . $tweets->errors[0]->message . '!</strong><br/>' . sprintf(__( 
						'You\'ll need to regenerate it <a href="%s" target="_blank">here</a>!', 
						'noo' ), 'https://dev.twitter.com/apps') . $after_widget;
				} else {
					echo '<strong>' . $tweets->errors[0]->message . '</strong>' . $after_widget;
				}
				return;
			}
			
			$tweets_array = array();
			for ( $i = 0; $i <= count( $tweets ); $i++ ) {
				if ( ! empty( $tweets[$i] ) ) {
					$tweets_array[$i]['created_at'] = $tweets[$i]->created_at;
					$tweets_array[$i]['name']	=	$tweets[$i]->user->name;
					$tweets_array[$i]['screen_name'] = $tweets[$i]->user->screen_name;
					$tweets_array[$i]['profile_image_url'] = $tweets[$i]->user->profile_image_url;
					// clean tweet text
					$tweets_array[$i]['text'] = preg_replace( '/[\x{10000}-\x{10FFFF}]/u', '', $tweets[$i]->text );
					
					if ( ! empty( $tweets[$i]->id_str ) ) {
						$tweets_array[$i]['status_id'] = $tweets[$i]->id_str;
					}
				}
			}
			update_option( 'noo_widget_recent_tweets', serialize( $tweets_array ) );
			update_option( 'noo_widget_recent_tweets_cache_time', time() );
		}
		
		$noo_widget_recent_tweets = maybe_unserialize( get_option( 'noo_widget_recent_tweets' ) );
		if ( ! empty( $noo_widget_recent_tweets ) ) {
			echo '<div class="recent-tweets"><ul>';
			$i = '1';
			foreach ( $noo_widget_recent_tweets as $tweet ) {
				
				if ( ! empty( $tweet['text'] ) ) {
					if ( empty( $tweet['status_id'] ) ) {
						$tweet['status_id'] = '';
					}
					if ( empty( $tweet['created_at'] ) ) {
						$tweet['created_at'] = '';
					}
					
					echo '<li><div class="twitter_user"><a class="twitter_profile" target="_blank" href="http://twitter.com/' .
						 $instance['username'] . '/statuses/' . $tweet['status_id'] . '"><img src="'.$tweet['profile_image_url'].'">'. $tweet['name'] .'</a><span class="twitter_username">@'.$tweet['screen_name'].'</span></div><span>' . $this->_convert_links( $tweet['text'] ) .
						 '</span></li>';
					if ( $i == $instance['tweetstoshow'] ) {
						break;
					}
					$i++;
				}
			}
			
			echo '</ul></div>';
		}
		
		echo $after_widget;
	}

	protected function _convert_links( $status, $targetBlank = true, $linkMaxLen = 50 ) {
		// the target
		$target = $targetBlank ? " target=\"_blank\" " : "";
		
		// convert link to url
		$status = preg_replace( 
			"/((http:\/\/|https:\/\/)[^ )]+)/i", 
			"<a href=\"$1\" title=\"$1\" $target >$1</a>", 
			$status );
		
		// convert @ to follow
		$status = preg_replace( 
			"/(@([_a-z0-9\-]+))/i", 
			"<a href=\"http://twitter.com/$2\" title=\"Follow $2\" $target >$1</a>", 
			$status );
		
		// convert # to search
		$status = preg_replace( 
			"/(#([_a-z0-9\-]+))/i", 
			"<a href=\"https://twitter.com/search?q=$2\" title=\"Search $1\" $target >$1</a>", 
			$status );
		
		// return the status
		return $status;
	}

	protected function _relative_time( $a = '' ) {
		// get current timestampt
		$b = strtotime( "now" );
		// get timestamp when tweet created
		$c = strtotime( $a );
		// get difference
		$d = $b - $c;
		// calculate different time values
		$minute = 60;
		$hour = $minute * 60;
		$day = $hour * 24;
		$week = $day * 7;
		
		if ( is_numeric( $d ) && $d > 0 ) {
			// if less then 3 seconds
			if ( $d < 3 )
				return "right now";
				// if less then minute
			if ( $d < $minute )
				return sprintf( __( "%s seconds ago", 'noo' ), floor( $d ) );
				// if less then 2 minutes
			if ( $d < $minute * 2 )
				return __( "about 1 minute ago", 'noo' );
				// if less then hour
			if ( $d < $hour )
				return sprintf( __( '%s minutes ago', 'noo' ), floor( $d / $minute ) );
				// if less then 2 hours
			if ( $d < $hour * 2 )
				return __( "about 1 hour ago", 'noo' );
				// if less then day
			if ( $d < $day )
				return sprintf( __( "%s hours ago", 'noo' ), floor( $d / $hour ) );
				// if more then day, but less then 2 days
			if ( $d > $day && $d < $day * 2 )
				return __( "yesterday", 'noo' );
				// if less then year
			if ( $d < $day * 365 )
				return sprintf( __( '%s days ago', 'noo' ), floor( $d / $day ) );
				// else return more than a year
			return __( "over a year ago", 'noo' );
		}
	}

	public function form( $instance ) {
		$defaults = array( 
			'title' => '', 
			'consumerkey' => '', 
			'consumersecret' => '', 
			'accesstoken' => '', 
			'accesstokensecret' => '', 
			'cachetime' => '', 
			'username' => '', 
			'tweetstoshow' => '' );
		$instance = wp_parse_args( (array) $instance, $defaults );
		
		echo '
		<p>
			<label>' . __( 'Title', 'noo' ) . ':</label>
			<input type="text" name="' .
			 $this->get_field_name( 'title' ) . '" id="' . $this->get_field_id( 'title' ) . '" value="' .
			 esc_attr( $instance['title'] ) . '" class="widefat" />
		</p>
		<p>
			<label>' . __( 'Consumer Key', 'noo' ) . ':</label>
			<input type="text" name="' .
			 $this->get_field_name( 'consumerkey' ) . '" id="' . $this->get_field_id( 'consumerkey' ) . '" value="' .
			 esc_attr( $instance['consumerkey'] ) . '" class="widefat" />
		</p>
		<p>
			<label>' . __( 'Consumer Secret', 'noo' ) . ':</label>
			<input type="text" name="' .
			 $this->get_field_name( 'consumersecret' ) . '" id="' . $this->get_field_id( 'consumersecret' ) . '" value="' .
			 esc_attr( $instance['consumersecret'] ) . '" class="widefat" />
		</p>
		<p>
			<label>' . __( 'Access Token', 'noo' ) . ':</label>
			<input type="text" name="' .
			 $this->get_field_name( 'accesstoken' ) . '" id="' . $this->get_field_id( 'accesstoken' ) . '" value="' .
			 esc_attr( $instance['accesstoken'] ) . '" class="widefat" />
		</p>
		<p>
			<label>' . __( 'Access Token Secret', 'noo' ) . ':</label>
			<input type="text" name="' .
			 $this->get_field_name( 'accesstokensecret' ) . '" id="' . $this->get_field_id( 'accesstokensecret' ) .
			 '" value="' . esc_attr( $instance['accesstokensecret'] ) . '" class="widefat" />
		</p>
		<p>
			<label>' .
			 __( 'Cache Tweets in every', 'noo' ) . ':</label>
			<input type="text" name="' .
			 $this->get_field_name( 'cachetime' ) . '" id="' . $this->get_field_id( 'cachetime' ) . '" value="' .
			 esc_attr( $instance['cachetime'] ) . '" class="small-text" />' . __( 'hours', 'noo' ) . '
		</p>
		<p>
			<label>' . __( 'Twitter Username', 'noo' ) . ':</label>
			<input type="text" name="' .
			 $this->get_field_name( 'username' ) . '" id="' . $this->get_field_id( 'username' ) . '" value="' .
			 esc_attr( $instance['username'] ) . '" class="widefat" />
		</p>
		<p>
			<label>' . __( 'Tweets to display', 'noo' ) . ':</label>
			<select type="text" name="' .
			 $this->get_field_name( 'tweetstoshow' ) . '" id="' . $this->get_field_id( 'tweetstoshow' ) . '">';
		for ( $i = 1; $i <= 10; $i++ ) {
			echo '<option value="' . $i . '"';
			if ( $instance['tweetstoshow'] == $i ) {
				echo ' selected="selected"';
			}
			echo '>' . $i . '</option>';
		}
		echo '
			</select>
		</p>
		<p>
			<label>' . __( 'Exclude replies', 'noo' ) . ':</label>
			<input type="checkbox" name="' .
			 $this->get_field_name( 'excludereplies' ) . '" id="' . $this->get_field_id( 'excludereplies' ) .
			 '" value="true"';
		if ( ! empty( $instance['excludereplies'] ) && esc_attr( $instance['excludereplies'] ) == 'true' ) {
			echo ' checked="checked"';
		}
		echo '/></p>';
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['consumerkey'] = strip_tags( $new_instance['consumerkey'] );
		$instance['consumersecret'] = strip_tags( $new_instance['consumersecret'] );
		$instance['accesstoken'] = strip_tags( $new_instance['accesstoken'] );
		$instance['accesstokensecret'] = strip_tags( $new_instance['accesstokensecret'] );
		$instance['cachetime'] = strip_tags( $new_instance['cachetime'] );
		$instance['username'] = strip_tags( $new_instance['username'] );
		$instance['tweetstoshow'] = strip_tags( $new_instance['tweetstoshow'] );
		$instance['excludereplies'] = strip_tags( $new_instance['excludereplies'] );
		
		if ( $old_instance['username'] != $new_instance['username'] ) {
			delete_option( 'noo_widget_recent_tweets_cache_time' );
		}
		
		return $instance;
	}
}

class Noo_Job_Type_Widget extends Noo_Wiget {
	public function __construct() {
		$this->widget_cssclass = 'noo-job-type-widget';
		$this->widget_description = __( "Display Noo Job Type.", 'noo' );
		$this->widget_id = 'noo_job_type_widget';
		$this->widget_name = __( 'Noo Job Type', 'noo' );
		$this->cached = true;
		$this->settings = array(
			'title' => array( 'type' => 'text', 'std' => '', 'label' => __( 'Title', 'noo' ) ),
		);
		parent::__construct();
	}

	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		$types = (array) get_terms( 'job_type', array('hide_empty'=>false) );
		$noo_job_type_colors = get_option('noo_job_type_colors');
		echo '<ul>';
		foreach ($types as $type){
			$type->color = isset($noo_job_type_colors[$type->term_id]) ? $noo_job_type_colors[$type->term_id] : '';
			echo '<li>';
			echo '<a class="job-type-'.esc_attr($type->slug).'" title="'.esc_attr($type->name).'" href="'.get_term_link($type,'job_type').'" style="color: '.$type->color.'">'.esc_html($type->name).'<i style="color: '.$type->color.'" class="fa fa-bookmark"></i></a>';
			echo '</li>';
		}
		echo '</ul>';
		echo $args['after_widget'];
	}
}

class Noo_Job_Category_Widget extends Noo_Wiget {
	public function __construct() {
		$this->widget_cssclass = 'noo-job-category-widget';
		$this->widget_description = __( "Display Noo Job Categories.", 'noo' );
		$this->widget_id = 'noo_job_category_widget';
		$this->widget_name = __( 'Noo Job Categories', 'noo' );
		$this->cached = true;
		$this->settings = array(
			'title' => array( 'type' => 'text', 'std' => '', 'label' => __( 'Title', 'noo' ) ),
			'count' => array(
				'type'  => 'checkbox',
				'std'   => 0,
				'label' => __( 'Show Job Counts', 'noo' )
			),
			'include_empty' => array(
				'type'  => 'checkbox',
				'std'   => 0,
				'label' => __( 'Include Empty Categories', 'noo' )
			),
			'hierarchical' => array(
				'type'  => 'checkbox',
				'std'   => 0,
				'label' => __( 'Show Hierarchy', 'noo' )
			),
		);
		parent::__construct();
	}

	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		$c = ! empty( $instance['count'] ) ? '1' : '0';
		$h = ! empty( $instance['hierarchical'] ) ? '1' : '0';
		$e = ! empty( $instance['include_empty'] ) ? '0' : '1';
		$cat_args = array(
			'taxonomy'     =>'job_category',
			'orderby'      => 'name', 
			'show_count'   => $c, 
			'hide_empty'   => $e,
			'hierarchical' => $h
		);
		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		$categories = get_categories( $cat_args );
		echo '<ul>';
		echo  walk_category_tree( $categories, 0, array(
			'style'              =>'list',
			'show_count'         =>$c,
			'hide_empty'         => $e,
			'hierarchical'       =>$h,
			'use_desc_for_title' =>1,
		));
		echo '</ul>';
		echo $args['after_widget'];
	}
}

class Noo_Job_Location_Widget extends Noo_Wiget {
	public function __construct() {
		$this->widget_cssclass = 'noo-job-location-widget';
		$this->widget_description = __( "Display Noo Job Location.", 'noo' );
		$this->widget_id = 'noo_job_location_widget';
		$this->widget_name = __( 'Noo Job Location', 'noo' );
		$this->cached = true;
		$this->settings = array(
			'title' => array( 'type' => 'text', 'std' => '', 'label' => __( 'Title', 'noo' ) ),
			'display' => array( 'type' => 'text', 'std' =>5, 'label' => __( 'Display', 'noo' ) ),
			'count' => array(
				'type'  => 'checkbox',
				'std'   => 0,
				'label' => __( 'Show post counts', 'noo' )
			),
			'include_empty' => array(
				'type'  => 'checkbox',
				'std'   => 0,
				'label' => __( 'Include Empty Categories', 'noo' )
			),
			'hierarchical' => array(
				'type'  => 'checkbox',
				'std'   => 0,
				'label' => __( 'Show hierarchy', 'noo' )
			),
		);
		parent::__construct();
	}

	public function widget( $args, $instance ) {
		extract( $args );
		$title 		= apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		$display 	= isset( $instance['display'] ) ? absint($instance['display']) : 5;
		$display    = max($display,1);
		$c = ! empty( $instance['count'] ) ? '1' : '0';
		$h = ! empty( $instance['hierarchical'] ) ? '1' : '0';
		$e = ! empty( $instance['include_empty'] ) ? '0' : '1';
		$cat_args = array( 
			'taxonomy'     => 'job_location', 
			'orderby'      => 'count', 
			'order'        => 'DESC', 
			'number'       => $display,
			'show_count'   => $c, 
			'hide_empty'   => $e, 
			'hierarchical' => $h 
		);
		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		$categories = get_categories( $cat_args );
		echo '<ul>';
		echo  walk_category_tree( $categories, 0, array(
			'style'              => 'list',
			'show_count'         => $c,
			'hide_empty'         => $e, 
			'hierarchical'       => $h,
			'use_desc_for_title' =>1,
		));
		echo '</ul>';

		echo $args['after_widget'];
	}
}

class Noo_Job_Search_Widget extends Noo_Wiget {
	public function __construct() {
		$this->widget_cssclass = 'noo-job-search-widget';
		$this->widget_description = __( "Simple keyword search for Jobs.", 'noo' );
		$this->widget_id = 'noo_job_search_widget';
		$this->widget_name = __( 'Simple Job Search', 'noo' );
		$this->cached = true;
		$this->settings = array(
			'title' => array( 'type' => 'text', 'std' => '', 'label' => __( 'Title', 'noo' ) ),
		);
		parent::__construct();
	}

	public function widget( $args, $instance ) {
		extract( $args );
		$title 		= apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		ob_start();
		do_action( 'pre_get_job_search_form'  );
		?>
		<form method="get" class="form-horizontal noo-job-search" action="<?php echo esc_url( home_url( '/'  ) ); ?>">
			<label class="sr-only" for="s"><?php _e( 'Search for:', 'noo' ); ?></label>
			<input type="search" id="s" class="form-control" placeholder="<?php echo esc_attr__( 'Search Job&hellip;', 'noo' ); ?>" value="<?php echo get_search_query(); ?>" name="s" title="<?php echo esc_attr__( 'Search for:', 'noo' ); ?>" />
			<input type="submit" id="searchsubmit" class="hidden" name="submit" value="Search">
			<input type="hidden" name="post_type" value="noo_job" />
		</form>	
		<?php
		$form = apply_filters( 'get_job_search_form', ob_get_clean() );
		echo $form;
		echo $args['after_widget'];
	}
}

class Noo_Job_Count_Widget extends Noo_Wiget {

	public function __construct() {
		$this->widget_cssclass = 'noo-job-count-widget';
		$this->widget_description = __( "Display the total number of jobs available.", 'noo' );
		$this->widget_id = 'noo_job_count_widget';
		$this->widget_name = __( 'Noo Job Count', 'noo' );
		$this->cached = true;
		$this->settings = array(
			'title' => array( 'type' => 'text', 'std' => '', 'label' => __( 'Title', 'noo' ) ),
		);
		parent::__construct();
	}

	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		$company_count = wp_count_posts('noo_company');
		$job_count = wp_count_posts('noo_job');
		echo '<ul>';
		echo '<li><a href="' . get_post_type_archive_link( 'noo_company' ) . '" >' . __('Companies', 'noo') . '</a>';
		echo '<p class="jobs-count">' . $company_count->publish . '</p>';
		echo '</li>';
		echo '<li><a href="' . get_post_type_archive_link( 'noo_job' ) . '" >' . __('Available Jobs', 'noo') . '</a>';
		echo '<p class="jobs-count">' . $job_count->publish . '</p>';
		echo '</li>';
		echo '</ul>';
		echo $args['after_widget'];
	}
}

class Noo_Resume_Categories_Widget extends Noo_Wiget {

	public function __construct() {
		$this->widget_cssclass = 'noo-resume-category-widget';
		$this->widget_description = __( "Display the Categories for resume.", 'noo' );
		$this->widget_id = 'noo_resume_categories_widget';
		$this->widget_name = __( 'Resume Categories', 'noo' );
		$this->cached = true;
		$this->settings = array(
			'title' => array( 'type' => 'text', 'std' => '', 'label' => __( 'Title', 'noo' ) ),
		);
		parent::__construct();
	}

	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		$categoriess = (array) get_terms( 'job_category', array('hide_empty'=>false) );
		echo '<ul>';
		$archive_link = get_post_type_archive_link( 'noo_resume' );
		foreach ($categoriess as $category){
			$category_link = esc_url( add_query_arg( array('resume_category' => $category->term_id), $archive_link ) );

			echo '<li>';
			echo '<a class="resume-category-'.esc_attr($category->slug).'" title="'.esc_attr($category->name).'" href="'.$category_link.'" >'.esc_html($category->name).'</a>';
			echo '</li>';
		}
		echo '</ul>';
		echo $args['after_widget'];
	}
}

class Noo_Resume_Search_Widget extends Noo_Wiget {
	public function __construct() {
		if( !class_exists('Noo_Resume') ) return;

		$this->widget_cssclass = 'noo-resume-search-widget';
		$this->widget_description = __( "Simple keyword search for Resumes.", 'noo' );
		$this->widget_id = 'noo_resume_search_widget';
		$this->widget_name = __( 'Simple Resume Search', 'noo' );
		$this->cached = true;
		$this->settings = array(
			'title' => array( 'type' => 'text', 'std' => '', 'label' => __( 'Title', 'noo' ) ),
			'candidate_name' => array( 'type' => 'checkbox', 'std' => '1', 'label' => __( 'Search by Candidate Name', 'noo' ) ),
			'search_content' => array( 'type' => 'checkbox', 'std' => '', 'label' => __( 'Search by Resume Title &amp; Content', 'noo' ) ),
		);

		$education = Noo_Resume::get_setting('enable_education', '1');
		$experience = Noo_Resume::get_setting('enable_experience', '1');
		$skill = Noo_Resume::get_setting('enable_skill', '1');

		if( $education ) {
			$this->settings['education'] = array( 'type' => 'checkbox', 'std' => '', 'label' => __( 'Search by Education', 'noo' ) );
		}
		if( $experience ) {
			$this->settings['experience'] = array( 'type' => 'checkbox', 'std' => '', 'label' => __( 'Search by Experience', 'noo' ) );
		}
		if( $skill ) {
			$this->settings['skill'] = array( 'type' => 'checkbox', 'std' => '', 'label' => __( 'Search by Skill', 'noo' ) );
		}
		parent::__construct();
	}

	public function widget( $args, $instance ) {
		extract( $args );
		$title 		= apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		$candidate_name = isset( $instance['candidate_name'] ) ? $instance['candidate_name'] : '';
		$search_content = isset( $instance['search_content'] ) ? $instance['search_content'] : '';
		$education = isset( $instance['education'] ) ? $instance['education'] : '';
		$experience = isset( $instance['experience'] ) ? $instance['experience'] : '';
		$skill = isset( $instance['skill'] ) ? $instance['skill'] : '';

		ob_start();
		do_action( 'pre_get_resume_search_form'  );
		?>
		<form method="get" class="form-horizontal noo-resume-search" action="<?php echo esc_url( home_url( '/'  ) ); ?>">
			<label class="sr-only" for="s"><?php _e( 'Search for:', 'noo' ); ?></label>
			<input type="search" id="s" class="form-control" placeholder="<?php echo esc_attr__( 'Search Resume&hellip;', 'noo' ); ?>" value="<?php echo (isset($_GET['s']) ? esc_html($_GET['s']) : ''); ?>" name="s" title="<?php echo esc_attr__( 'Search for:', 'noo' ); ?>" />
			<input type="submit" id="searchsubmit" class="hidden" name="submit" value="Search">
			<input type="hidden" name="post_type" value="noo_resume" />
			<?php if( empty( $search_content ) ) : ?>
				<input type="hidden" name="no_content" value="1" />
			<?php endif; ?>
			<?php if( !empty( $candidate_name ) ) : ?>
				<input type="hidden" name="candidate_name" value="1" />
			<?php endif; ?>
			<?php if( !empty( $education ) ) : ?>
				<input type="hidden" name="education" value="1" />
			<?php endif; ?>
			<?php if( !empty( $experience ) ) : ?>
				<input type="hidden" name="experience" value="1" />
			<?php endif; ?>
			<?php if( !empty( $skill ) ) : ?>
				<input type="hidden" name="skill" value="1" />
			<?php endif; ?>
		</form>
		<?php
		$form = apply_filters( 'get_resume_search_form', ob_get_clean() );
		echo $form;
		echo $args['after_widget'];
	}
}

class Noo_Resume_Count_Widget extends Noo_Wiget {

	public function __construct() {
		$this->widget_cssclass = 'noo-resume-count-widget';
		$this->widget_description = __( "Display the total number of resumes available.", 'noo' );
		$this->widget_id = 'noo_resume_count_widget';
		$this->widget_name = __( 'Resumes Count', 'noo' );
		$this->cached = true;
		$this->settings = array(
			'title' => array( 'type' => 'text', 'std' => '', 'label' => __( 'Title', 'noo' ) ),
		);
		parent::__construct();
	}

	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base );
		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		$resume_count = Noo_Resume::count_viewable_resumes('', true);
		echo '<ul><li><a href="' . get_post_type_archive_link( 'noo_resume' ) . '" >' . __('Resumes', 'noo') . '</a>';
		echo '<p class="jobs-count">' . $resume_count . '</p>';
		echo  '</li></ul>';
		echo $args['after_widget'];
	}
}

class Noo_Jobs_Widget extends Noo_Wiget {

	public function __construct() {
		$this->widget_cssclass = 'noo-jobs-widget';
		$this->widget_description = __( "Display style (jobs slider, jobs list) .", 'noo' );
		$this->widget_id = 'noo_jobs_widget';
		$this->widget_name = __( 'Noo Jobs', 'noo' );
		$this->cached = true;
		// --- Job category

			$job_category = array( 'all' => __( 'All Categories', 'noo' ) );
			$job_category_list = get_terms( 'job_category', array( 'hide_empty' => false ) );

		    if ( is_array( $job_category_list ) && ! empty( $job_category_list ) ) {
		        foreach ( $job_category_list as $category_details ) {
		            $job_category[ $category_details->slug ] = $category_details->name;  
		        }
		    }
	    	
	    // --- Job type

			$job_type = array( 'all' => __( 'All type', 'noo' ) );
			$job_type_list = get_terms( 'job_type', array( 'hide_empty' => false ) );

		    if ( is_array( $job_type_list ) && ! empty( $job_type_list ) ) {
		        foreach ( $job_type_list as $type_details ) {
		            $job_type[ $type_details->slug ] = $type_details->name;  
		        }
		    }

		// --- Job location

			$job_location = array( 'all' => __( 'All location', 'noo' ) );
			$job_location_list = get_terms( 'job_location', array( 'hide_empty' => false ) );

		    if ( is_array( $job_location_list ) && ! empty( $job_location_list ) ) {
		        foreach ( $job_location_list as $location_details ) {
		            $job_location[ $location_details->slug ] = $location_details->name;  
		        }
		    }


		$this->settings = array(
			'title' => array( 
				'type'  => 'text', 
				'std'   => '', 
				'label' => __( 'Title', 'noo' ) 
			),
			'show' => array( 
				'type'    => 'select',
				'std'     => '',
				'label'   => __( 'Show', 'noo' ),
				'options' => array(
					'featured' => 'Featured',
					'recent'   => 'Recent'
				)
			),
			'job_category' => array( 
				'type'    => 'select',
				'std'     => '',
				'label'   => __( 'Job Categorie', 'noo' ),
				'options' => $job_category
			),
			'job_type' => array( 
				'type'    => 'select',
				'std'     => '',
				'label'   => __( 'Job Type', 'noo' ),
				'options' => $job_type
			),
			'job_location' => array( 
				'type'    => 'select',
				'std'     => '',
				'label'   => __( 'Job Location', 'noo' ),
				'options' => $job_location
			),
			'posts_per_page' => array( 
				'type'  => 'text', 
				'std'   => '1', 
				'label' => __( 'Posts per page', 'noo' ) 
			),
			'orderby' => array( 
				'type'    => 'select',
				'std'     => '',
				'label'   => __( 'Order by', 'noo' ),
				'options' => array(
					'featured' => 'Date',
					'view'  => 'Popular'
				)
			),
			'order' => array( 
				'type'    => 'select',
				'std'     => '',
				'label'   => __( 'Sort by', 'noo' ),
				'options' => array(
					'featured' => 'Recent',
					'popular'  => 'Older'
				)
			),
		);

		parent::__construct();
	}

	public function widget( $args, $instance ) {
		extract( $args );
		// $title = apply_filters( 'widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base );
		echo $args['before_widget'];
		
		$paged = 1;

		//  -- Array

			$job_args = array(
				'post_type'			  => 'noo_job',
				'post_status'         => 'publish',
				'paged'			  	  => $paged,
				'posts_per_page'	  => $instance['posts_per_page'] ? $instance['posts_per_page'] : 1,
				'ignore_sticky_posts' => true
			);

		//  -- tax_query
		
			$job_args['tax_query'] = array( 'relation' => 'AND' );
			if ( $instance['job_category'] != 'all' ) {
				$job_args['tax_query'][] = array(
					'taxonomy' => 'job_category',
					'field' => 'slug',
					'terms' => $instance['job_category']
				);
			}

			if ( $instance['job_type'] != 'all' ) {
				$job_args['tax_query'][] = array(
					'taxonomy' => 'job_type',
					'field' => 'slug',
					'terms' => $instance['job_type']
				);
			}

			if ( $instance['job_location'] != 'all' ) {
				$job_args['tax_query'][] = array(
					'taxonomy' => 'job_location',
					'field' => 'slug',
					'terms' => $instance['job_location']
				);
			}

		//  -- Check orderby

			if ( $instance['orderby'] == 'view' ) {
				$job_args['orderby'] = 'meta_value_num';
				$job_args['meta_key'] = 'noo_view_count';
	 		} else {
	 			$job_args['orderby'] = 'date';
	 		}

	 	//  -- Check sort by
		
 			if ( $instance['order'] == 'asc' ) {
	 			$job_args['order'] = 'ASC';
	 		} else {
	 			$job_args['order'] = 'DESC';
	 		}

	 	//  -- Check featured
	 		if( $instance['show'] == 'featured' ){
				$job_args['meta_query'][] = array(
					'key'   => '_featured',
					'value' => 'yes'
				);
			}

		//  -- create new query
		
			$jobs_new_query = new WP_Query( $job_args );

			if ( $jobs_new_query->have_posts() ) : ?>
				
					<div class="jobs posts-loop slider">

						<div class="posts-loop-title">
							<h3><?php echo $instance['title'];?></h3>
						</div>

						<div class="pagination list-center" data-show="<?php echo $instance['show'] ?>" data-posts-per-page="<?php echo $instance['posts_per_page'] ?>" data-style="slider">
							<a href="#" class="prev page-numbers disabled">
								<i class="fa fa-long-arrow-left"></i>
							</a>
							
							<a href="#" class="next page-numbers">
								<i class="fa fa-long-arrow-right"></i>
							</a>
						</div>

						<div class="posts-loop-content">
							<?php $i = 0; while ( $jobs_new_query->have_posts() ) : $jobs_new_query->the_post(); global $post; ?>
								<?php
									$company_name		= '';
									$logo_company		= '';
									$type 				= Noo_Job::get_job_type( $post );
					
									$company_id			= Noo_Job::get_employer_company($post->post_author);

									$locations			= get_the_terms( get_the_ID(), 'job_location' );
									if( !empty( $company_id ) ) {
										$company_name           = get_the_title( $company_id );
										$cover_image_id      	= noo_get_post_meta(get_the_ID(), '_cover_image');
										$logo_company           = Noo_Company::get_company_logo( $company_id );
										$company_cover_image 	= wp_get_attachment_image($cover_image_id, 'medium', false, array( 'alt' => $company_name ) );
										$thumb_jobs             = wp_get_attachment_image(get_the_ID(), 'company-logo', false, array( 'alt' => get_the_title() ) );
									}
								?>
								<div class="slider_post list_slider_<?php echo ++$i; ?>">
									<div class="img-thumb">
										<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>">
											<?php
												if ( !empty($company_cover_image) ) {
													echo $company_cover_image;
												} else {
													echo '<img style="width: 100%" src="' . NOO_ASSETS_URI .'/images/img-defaul-02.jpg" alt="" />';
												}
											?>
										</a>
									</div>
									<article <?php post_class(); ?>>
										<div class="loop-item-wrap">
											<div class="item-title-bar">
												<div class="item-featured">
													<a href="<?php the_permalink() ?>" title="<?php the_title(); ?>">
														<?php echo $logo_company;?>
													</a>
												</div>
												<div class="items">
													<h4 class="item-title">
														<a href="<?php the_permalink() ?>" title="<?php the_title(); ?>">
															<?php the_title(); ?>
														</a>
													</h4>
													<h3 class="item-company">
														<a href="<?php echo esc_url(get_permalink($company_id)); ?>" title="<?php echo get_the_title( $company_id ); ?>">
															<?php echo get_the_title( $company_id ); ?>
														</a>
													</h3>
												</div>
											</div>
											<div class="item-info">
												<span class="job-type">
													<a href="<?php echo get_term_link($type,'job_type'); ?>" style="color: <?php echo $type->color; ?>">
														<i class="fa fa-bookmark"></i>
														<?php echo $type->name; ?>
													</a>
												</span>
												<?php
													$locations_html = '';
													$separator = ', ';
													if( !empty( $locations ) ) {
														foreach ($locations as $location) {
															$locations_html .= '<a href="' . get_term_link($location->term_id,'job_location') . '"><em>' . $location->name . '</em></a>' . $separator;
														}
														$html = '<span>';
														$html .= '<i class="fa fa-map-marker"></i> ';
														$html .= trim($locations_html, $separator);
														$html .= '</span>';
														echo $html;
													}
												?>
											</div>
											<div class="item-excerpt">
												<?php echo get_the_excerpt( ); ?>
											</div>
											<div class="item-view-more">
												<a class="btn btn-primary" href="<?php echo get_permalink($post->ID)?>">
													<?php _e('View more', 'noo')?>
												</a>
											</div>
										</div>
									</article>
								</div>
							<?php endwhile; ?>
							<div class="total-slider" style="display: none" data-total-slider="<?php echo $i ?>"></div>
						</div><!-- end .posts-loop-content -->

					</div> <!-- end /slider -->
				
			<?php endif;
			wp_reset_postdata();
			wp_reset_query();

		echo $args['after_widget'];
	}

}

class Noo_Advanced_Search_Widget extends WP_Widget {

	public function option_type( $post_type = 'job' ) {

		if ( $post_type == 'job' ) :

			$search_field = array(
				'no'           => __('None', 'noo'),
				'job_location' => __('Job Location','noo'),
				'job_category' => __('Job Category','noo'),
				'job_type'     => __('Job Type','noo'),
			);
			$custom_fields = noo_get_custom_fields( Noo_Job::get_setting('noo_job_custom_field', array()), 'noo_jobs_custom_fields_' );
			if($custom_fields){
				foreach ($custom_fields as $k=>$custom_field){
					$label = __('Custom Field: ','noo').( isset( $custom_field['label_translated'] ) ? $custom_field['label_translated'] : (isset($custom_field['label']) ? $custom_field['label'] : $k));
					$id = '_noo_job_field_'.sanitize_title(@$custom_field['name']).'|'.(isset($custom_field['label']) ? $custom_field['label'] : $k);
					$search_field[$id] = $label;
				}
			}

			return $search_field;

		elseif ( $post_type == 'resume' ) :

			$resume_default_fields = Noo_Resume::get_default_fields();
			$resume_custom_fields = noo_get_custom_fields( get_option( 'noo_resume' ), 'noo_resume_custom_fields_' );
			$resume_fields = array_merge( array_diff_key($resume_default_fields, $resume_custom_fields), $resume_custom_fields );
			$resume_search_field = array(
				'no' => __('None', 'noo'),
			);
			foreach ($resume_fields as $k=>$field){
				if( !isset($field['name']) || empty($field['name'])) continue;
				
				if( array_key_exists($field['name'], $resume_default_fields) ) {
					if( isset( $field['is_disabled'] ) && ($field['is_disabled'] == 'yes') )
						continue;
					$label = ( isset( $field['label_translated'] ) ? $field['label_translated'] : (isset($field['label']) ? $field['label'] : $k));
					$id = $field['name'];
					$resume_search_field[$id] = $label;
				}else{
					$label = __('Custom Field: ','noo').( isset( $field['label_translated'] ) ? $field['label_translated'] : (isset($field['label']) ? $field['label'] : $k));
					//$id = '_noo_resume_field_'.sanitize_title($field['name']);
					$id = '_noo_resume_field_'.sanitize_title(@$field['name']).'|'.(isset($field['label']) ? $field['label'] : $k);
					$resume_search_field[$id] = $label;
				}
			}

			return $resume_search_field;

		endif;

	} 

	public function __construct() {

		parent::__construct( false, __( '[OLD] Noo Advanced Search', 'noo' ) );
		
	}

	
    public function form( $instance ) {
 
        $default = array(
            'title' => '',
            'search_type' => 'noo_job',
            'show_keyword' => 'yes',
            'r_pos' => '',
            'r_pos2' => '',
            'r_pos3' => '',
            'r_pos4' => '',
            'r_pos5' => '',
            'r_pos6' => '',
            'r_pos7' => '',
            'r_pos8' => '',
        );
        $instance = wp_parse_args( (array) $instance, $default );
        $title = esc_attr($instance['title']);
        $search_type = esc_attr($instance['search_type']);
        $show_keyword = esc_attr($instance['show_keyword']);
        $r_pos = esc_attr($instance['r_pos']);
        $option_resume = $this->option_type('resume');
        $option_job = $this->option_type('job');
        $id_post_type = 'post_type_' . uniqid();
        
 		?>
 		<p>This widget id deprecated and will be remove in the future. Please switch to <strong><?php echo ( $search_type == 'noo_job' ? 'Jobs Advanced Search' : 'Resumes Advanced Search' ); ?></strong> widget</p>
 		<p>
 			<label for="<?php echo $this->get_field_id( 'title' ); ?>">
 				<?php _e( 'Title', 'noo' ); ?>
 			</label>
			<input class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
				value="<?php echo $title; ?>" />
 		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'search_type' ); ?>">
				<?php _e( 'Search Post Type', 'noo' ); ?>
			</label>
			<select class="widefat"
				id="<?php echo $id_post_type; ?>"
				name="<?php echo $this->get_field_name( 'search_type' ); ?>">
					<option value="noo_job"<?php echo $search_type == 'noo_job' ? ' selected' : ''; ?>><?php _e( 'Job', 'noo' ); ?></option>
					<option value="noo_resume"<?php echo $search_type == 'noo_resume' ? ' selected' : ''; ?>><?php _e( 'Resume', 'noo' ); ?></option>
			</select>
		</p>

 		<p>
			<label for="<?php echo $this->get_field_id( 'show_keyword' ); ?>">
				<?php _e( 'Enable Keyword Search', 'noo' ); ?>
			</label>
			<select class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'show_keyword' ) ); ?>"
				name="<?php echo $this->get_field_name( 'show_keyword' ); ?>">
					<option value="yes"<?php echo $show_keyword == 'yes' ? ' selected' : ''; ?>><?php _e( 'Yes', 'noo' ); ?></option>
					<option value="no"<?php echo $show_keyword == 'no' ? ' selected' : ''; ?>><?php _e( 'No', 'noo' ); ?></option>
			</select>
		</p>

		<!-- Search Position #1 -->

				<p>
					<label for="<?php echo $this->get_field_id( "r_pos" ); ?>">
						<?php _e( 'Search Position #1', 'noo' ); ?>
					</label>
					<select class="widefat search-position"
						id="<?php echo esc_attr( $this->get_field_id( 'r_pos' ) ); ?>"
						name="<?php echo $this->get_field_name( 'r_pos' ); ?>">
							<?php foreach ($option_resume as $key => $value) {
								echo "<option class='resume' value='{$key}'" . ($r_pos == $key ? ' selected' : '') . ">{$value}</option>";
							} ?>
							<?php foreach ($option_job as $key => $value) {
								echo "<option class='job' value='{$key}'" . ($r_pos == $key ? ' selected' : '') . ">{$value}</option>";
							} ?>
					</select>
				</p>

			<!-- /Search Position #1 -->

		<?php for ( $po = 2; $po <= 8; $po++ ) : ?>
			<?php $r_pos = esc_attr($instance["r_pos{$po}"]); ?>
			<!-- Search Position #<?php echo $po; ?> -->

				<p>
					<label for="<?php echo $this->get_field_id( "r_pos{$po}" ); ?>">
						<?php _e( 'Search Position #' . $po, 'noo' ); ?>
					</label>
					<select class="widefat search-position"
						id="<?php echo esc_attr( $this->get_field_id( "r_pos{$po}" ) ); ?>"
						name="<?php echo $this->get_field_name( "r_pos{$po}" ); ?>">
							<?php foreach ($option_resume as $key => $value) {
								echo "<option class='resume' value='{$key}'" . ($r_pos == $key ? ' selected' : '') . ">{$value}</option>";
							} ?>
							<?php foreach ($option_job as $key => $value) {
								echo "<option class='job' value='{$key}'" . ($r_pos == $key ? ' selected' : '') . ">{$value}</option>";
							} ?>
					</select>
				</p>

			<!-- /Search Position #<?php echo $po; ?> -->

		<?php endfor; ?>

		<!-- <button class="<?php echo $this->get_field_id( 'r_pos' ); ?>"><span class="dashicons dashicons-plus"></span></button> -->
		<!-- <div id="<?php echo $this->get_field_id( 'field_new' ); ?>"></div> -->
		<style type="text/css">
			button.<?php echo $this->get_field_id( 'r_pos' ); ?> span:hover{
				cursor: pointer;
			}
		</style>

		<script type="text/javascript">
		jQuery(document).ready(function($) {

			var 
				id_select = 'select#<?php echo esc_attr( $this->get_field_id( 'r_pos' ) ); ?>';
				select_begin = $('select#<?php echo $id_post_type; ?>').val();
				if ( select_begin == 'noo_job' ) {
					$('option.job').show();
					$('option.resume').hide();
				} else if ( select_begin == 'noo_resume' ) {
					$('option.resume').show();
					$('option.job').hide();
				}

			$('select#<?php echo $id_post_type; ?>').change(function(event) {
				$this = $(this);
				select = $this.val();
				if ( select == 'noo_job' ) {
					$this.closest('form').find('select.search-position').prop('selectedIndex',0);
					$this.closest('form').find('option.job').show();
					$this.closest('form').find('option.resume').hide();
				} else if ( select == 'noo_resume' ) {
					$this.closest('form').find('select.search-position').prop('selectedIndex',0);
					$this.closest('form').find('option.resume').show();
					$this.closest('form').find('option.job').hide();
				}
			});

		});
		</script>
 		<?php
    }
 
    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['search_type'] = strip_tags($new_instance['search_type']);
        $instance['show_keyword'] = strip_tags($new_instance['show_keyword']);
        $instance['r_pos'] = strip_tags($new_instance['r_pos']);
        $instance['r_pos2'] = strip_tags($new_instance['r_pos2']);
        $instance['r_pos3'] = strip_tags($new_instance['r_pos3']);
        $instance['r_pos4'] = strip_tags($new_instance['r_pos4']);
        $instance['r_pos5'] = strip_tags($new_instance['r_pos5']);
        $instance['r_pos6'] = strip_tags($new_instance['r_pos6']);
        $instance['r_pos7'] = strip_tags($new_instance['r_pos7']);
        $instance['r_pos8'] = strip_tags($new_instance['r_pos8']);
        return $instance;
    }
 
    public function widget( $args, $instance ) {
        extract($args);
        $title = apply_filters( 'widget_title', $instance['title'] );
        $search_type = $instance['search_type'];
        $show_keyword = $instance['show_keyword'];
        $r_pos = $instance['r_pos'];
        $r_pos2 = $instance['r_pos2'];
        $r_pos3 = $instance['r_pos3'];
        $r_pos4 = $instance['r_pos4'];
        $r_pos5 = $instance['r_pos5'];
        $r_pos6 = $instance['r_pos6'];
        $r_pos7 = $instance['r_pos7'];
        $r_pos8 = $instance['r_pos8'];
        
        $name_pos = explode('|', $r_pos);
        $prefix = uniqid();
 
        echo $before_widget;
        echo $before_title.$title.$after_title;
        ?>
        <form id="<?php echo $prefix . '_form'; ?>" method="get" class="widget-advanced-search" action="<?php echo esc_url( home_url( '/' ) );?>">
	        <style type="text/css">
	        .widget-advanced-search ul li:first-child, .widget-advanced-search ol li:first-child{
	        	padding-top: 6px;
	        }
	        </style>
	        <input type="hidden" class="form-control" name="post_type" value="<?php echo $search_type; ?>" />
	        <input type="hidden" class="form-control" name="action" value="live_search" />
	        <!-- <input type="hidden" class="form-control" name="live-search-nonce" value="<?php //echo wp_create_nonce( 'noo-advanced-live-search' ); ?>" /> -->
	        <?php //wp_nonce_field( 'noo-advanced-live-search', 'live-search-nonce' ); ?>
	        <?php
	        if ( $show_keyword == 'yes' ) :
	        	?>
	        	<div class="form-group">
				    <label class="sr-only" for="<?php echo $prefix . '_search-keyword'; ?>"><?php _e( 'Keyword', 'noo' ); ?></label>
				    <input type="text" class="form-control" id="<?php echo $prefix . '_search-keyword'; ?>" name="s" placeholder="<?php _e( 'Keyword', 'noo' ); ?>" value="<?php echo ( isset( $_GET['s'] ) ? esc_attr( $_GET['s'] ) : '' ); ?>"/>
				</div>
	        	<?php
	        else :
	        	?>
	        	<input type="hidden" name="s" value="" />
	        	<?php
	        endif;
	       	if($search_type == 'noo_job'):
				Noo_Job::advanced_search_field($r_pos, false, $prefix . '_pos', true);
				Noo_Job::advanced_search_field($r_pos2, false, $prefix . '_pos2', true);
				Noo_Job::advanced_search_field($r_pos3, false, $prefix . '_pos3', true);
				Noo_Job::advanced_search_field($r_pos4, false, $prefix . '_pos4', true);
				Noo_Job::advanced_search_field($r_pos5, false, $prefix . '_pos5', true);
				Noo_Job::advanced_search_field($r_pos6, false, $prefix . '_pos6', true);
				Noo_Job::advanced_search_field($r_pos7, false, $prefix . '_pos7', true);
				Noo_Job::advanced_search_field($r_pos8, false, $prefix . '_pos8', true);
			else:
				Noo_Job::advanced_search_field($r_pos, true, $prefix . '_pos', true);
				Noo_Job::advanced_search_field($r_pos2, true, $prefix . '_pos2', true);
				Noo_Job::advanced_search_field($r_pos3, true, $prefix . '_pos3', true);
				Noo_Job::advanced_search_field($r_pos4, true, $prefix . '_pos4', true);
				Noo_Job::advanced_search_field($r_pos5, true, $prefix . '_pos5', true);
				Noo_Job::advanced_search_field($r_pos6, true, $prefix . '_pos6', true);
				Noo_Job::advanced_search_field($r_pos7, true, $prefix . '_pos7', true);
				Noo_Job::advanced_search_field($r_pos8, true, $prefix . '_pos8', true);
			endif;
			?>
			<button type="submit" class="btn btn-primary btn-search-submit"><?php _e('Search', 'noo'); ?></button>
		</form>
		<?php if( $search_type != 'noo_resume' || Noo_Resume::can_view_resume(null,true) ) : ?>
			<script type="text/javascript">
			jQuery(document).ready(function($) {
				var container = $(".noo-main > .<?php echo ($search_type == 'noo_job') ? 'jobs' : 'resumes'; ?>");
				if( container.length ) {
					$('#<?php echo $prefix . '_form'; ?>').on('change', function(event) {
						event.preventDefault();
						var $form = $('#<?php echo $prefix . '_form'; ?> .form-control');
						var data = $form.serialize();
						history.pushState(null, null, '?' + data);
						$.ajax({
							url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
							data: data
						})
						.done(function( data ) {
							$(".noo-main").html(data);
							if($('[data-paginate="loadmore"]').find('.loadmore-action').length){
								$('[data-paginate="loadmore"]').each(function(){
									var $this = $(this);
									$this.nooLoadmore({
										navSelector  : $this.find('div.pagination'),            
										nextSelector : $this.find('div.pagination a.next'),
										itemSelector : 'article.loadmore-item',
										finishedMsg  : "<?php echo ( $search_type == 'noo_resume' ? __('All resumes displayed','noo') : __('All jobs displayed','noo') ); ?>"
									});
								});
							}
						})
						.fail(function() {
							
						})
						
					});
				}
			});
			</script>
		<?php
		endif;
        echo $after_widget;
 
    }
}

class Noo_Advanced_Job_Search_Widget extends WP_Widget {

	public function __construct() {

		parent::__construct( false, __( 'Job Advanced Search', 'noo' ) );
		
	}

	
    public function form( $instance ) {
 
        $default = array(
            'title' => '',
            'show_keyword' => 'yes',
            'r_pos1' => '',
            'r_pos2' => '',
            'r_pos3' => '',
            'r_pos4' => '',
            'r_pos5' => '',
            'r_pos6' => '',
            'r_pos7' => '',
            'r_pos8' => '',
        );
        $instance = wp_parse_args( (array) $instance, $default );
        $title = esc_attr($instance['title']);
        $show_keyword = esc_attr($instance['show_keyword']);

        $search_fields = array(
			'no'           => __('None', 'noo'),
			'job_location' => __('Job Location','noo'),
			'job_category' => __('Job Category','noo'),
			'job_type'     => __('Job Type','noo'),
		);
		$custom_fields = noo_get_custom_fields( Noo_Job::get_setting('noo_job_custom_field', array()), 'noo_jobs_custom_fields_' );
		if($custom_fields){
			foreach ($custom_fields as $k=>$custom_field){
				$label = __('Custom Field: ','noo').( isset( $custom_field['label_translated'] ) ? $custom_field['label_translated'] : (isset($custom_field['label']) ? $custom_field['label'] : $k));
				$id = '_noo_job_field_'.sanitize_title(@$custom_field['name']).'|'.(isset($custom_field['label']) ? $custom_field['label'] : $k);
				$search_fields[$id] = $label;
			}
		}
 		?>
 		<p>
 			<label for="<?php echo $this->get_field_id( 'title' ); ?>">
 				<?php _e( 'Title', 'noo' ); ?>
 			</label>
			<input class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
				value="<?php echo $title; ?>" />
 		</p>

 		<p>
			<label for="<?php echo $this->get_field_id( 'show_keyword' ); ?>">
				<?php _e( 'Enable Keyword Search', 'noo' ); ?>
			</label>
			<select class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'show_keyword' ) ); ?>"
				name="<?php echo $this->get_field_name( 'show_keyword' ); ?>">
					<option value="yes"<?php echo $show_keyword == 'yes' ? ' selected' : ''; ?>><?php _e( 'Yes', 'noo' ); ?></option>
					<option value="no"<?php echo $show_keyword == 'no' ? ' selected' : ''; ?>><?php _e( 'No', 'noo' ); ?></option>
			</select>
		</p>

		<?php for ( $po = 1; $po <= 8; $po++ ) : ?>
			<?php $r_pos = esc_attr($instance["r_pos{$po}"]); ?>
			<!-- Search Position #<?php echo $po; ?> -->

				<p>
					<label for="<?php echo $this->get_field_id( "r_pos{$po}" ); ?>">
						<?php _e( 'Search Position #' . $po, 'noo' ); ?>
					</label>
					<select class="widefat search-position"
						id="<?php echo esc_attr( $this->get_field_id( "r_pos{$po}" ) ); ?>"
						name="<?php echo $this->get_field_name( "r_pos{$po}" ); ?>">
							<?php foreach ($search_fields as $key => $value) {
								echo "<option value='{$key}'" . ($r_pos == $key ? ' selected' : '') . ">{$value}</option>";
							} ?>
					</select>
				</p>

			<!-- /Search Position #<?php echo $po; ?> -->

		<?php endfor; ?>
 		<?php
    }
 
    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['show_keyword'] = strip_tags($new_instance['show_keyword']);
		for ( $po = 1; $po <= 8; $po++ ) {
			$instance["r_pos{$po}"] = strip_tags($new_instance["r_pos{$po}"]);
		}

        return $instance;
    }
 
    public function widget( $args, $instance ) {
        extract($args);
        $title = apply_filters( 'widget_title', $instance['title'] );
        $show_keyword = $instance['show_keyword'];
        
        $prefix = uniqid();
 
        echo $before_widget;
        echo $before_title.$title.$after_title;
        ?>
        <form id="<?php echo $prefix . '_form'; ?>" method="get" class="widget-advanced-search" action="<?php echo esc_url( home_url( '/' ) );?>">
	        <style type="text/css">
	        .widget-advanced-search ul li:first-child, .widget-advanced-search ol li:first-child{
	        	padding-top: 6px;
	        }
	        </style>
	        <input type="hidden" class="form-control" name="post_type" value="noo_job" />
	        <input type="hidden" class="form-control" name="action" value="live_search" />
	        <!-- <input type="hidden" class="form-control" name="live-search-nonce" value="<?php //echo wp_create_nonce( 'noo-advanced-live-search' ); ?>" /> -->
	        <?php wp_nonce_field( 'noo-advanced-live-search', 'live-search-nonce' ); ?>
	        <?php
	        if ( $show_keyword == 'yes' ) :
	        	?>
	        	<div class="form-group">
				    <label class="sr-only" for="<?php echo $prefix . '_search-keyword'; ?>"><?php _e( 'Keyword', 'noo' ); ?></label>
				    <input type="text" class="form-control" id="<?php echo $prefix . '_search-keyword'; ?>" name="s" placeholder="<?php _e( 'Keyword', 'noo' ); ?>" value="<?php echo ( isset( $_GET['s'] ) ? esc_attr( $_GET['s'] ) : '' ); ?>"/>
				</div>
	        	<?php
	        else :
	        	?>
	        	<input type="hidden" name="s" value="" />
	        	<?php
	        endif;
	       	for ( $po = 1; $po <= 8; $po++ ) {
				Noo_Job::advanced_search_field($instance["r_pos{$po}"], false, $prefix . "_pos{$po}", true);
			}
			?>
			<button type="submit" class="btn btn-primary btn-search-submit"><?php _e('Search', 'noo'); ?></button>
		</form>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			var container = $(".noo-main > .jobs");
			if( container.length ) {
				$("#<?php echo $prefix . '_form'; ?>").on("change", function(event) {
					event.preventDefault();
					var $form = $("#<?php echo $prefix . '_form'; ?> .form-control");
					var data = $(this).serialize();
					history.pushState(null, null, "?" + $form.serialize());
					$.ajax({
						url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
						data: data
					})
					.done(function( data ) {
						if( data !== "-1") {
							$(".noo-main").html(data);
							if($('[data-paginate="loadmore"]').find(".loadmore-action").length){
								$('[data-paginate="loadmore"]').each(function(){
									var $this = $(this);
									$this.nooLoadmore({
										navSelector  : $this.find("div.pagination"),            
										nextSelector : $this.find("div.pagination a.next"),
										itemSelector : "article.loadmore-item",
										finishedMsg  : "<?php echo __('All jobs displayed','noo'); ?>"
									});
								});
							}
						} else {
							location.reload();
						}
					})
					.fail(function() {
						
					})
				});
			}
		});
		</script>
		<?php
        echo $after_widget;
    }
}

class Noo_Advanced_Resume_Search_Widget extends WP_Widget {

	public function __construct() {

		parent::__construct( false, __( 'Resume Advanced Search', 'noo' ) );
	}
	
    public function form( $instance ) {
 
        $default = array(
            'title' => '',
            'show_keyword' => 'yes',
            'r_pos1' => '',
            'r_pos2' => '',
            'r_pos3' => '',
            'r_pos4' => '',
            'r_pos5' => '',
            'r_pos6' => '',
            'r_pos7' => '',
            'r_pos8' => '',
        );
        $instance = wp_parse_args( (array) $instance, $default );
        $title = esc_attr($instance['title']);
        $show_keyword = esc_attr($instance['show_keyword']);

		$resume_default_fields = Noo_Resume::get_default_fields();
		$resume_custom_fields = noo_get_custom_fields( get_option( 'noo_resume' ), 'noo_resume_custom_fields_' );
		$resume_fields = array_merge( array_diff_key($resume_default_fields, $resume_custom_fields), $resume_custom_fields );
		$search_fields = array(
			'no' => __('None', 'noo'),
		);
		foreach ($resume_fields as $k=>$field){
			if( !isset($field['name']) || empty($field['name'])) continue;
			
			if( array_key_exists($field['name'], $resume_default_fields) ) {
				if( isset( $field['is_disabled'] ) && ($field['is_disabled'] == 'yes') )
					continue;
				$label = ( isset( $field['label_translated'] ) ? $field['label_translated'] : (isset($field['label']) ? $field['label'] : $k));
				$id = $field['name'];
				$search_fields[$id] = $label;
			}else{
				$label = __('Custom Field: ','noo').( isset( $field['label_translated'] ) ? $field['label_translated'] : (isset($field['label']) ? $field['label'] : $k));
				//$id = '_noo_resume_field_'.sanitize_title($field['name']);
				$id = '_noo_resume_field_'.sanitize_title(@$field['name']).'|'.(isset($field['label']) ? $field['label'] : $k);
				$search_fields[$id] = $label;
			}
		}
        
 		?>
 		<p>
 			<label for="<?php echo $this->get_field_id( 'title' ); ?>">
 				<?php _e( 'Title', 'noo' ); ?>
 			</label>
			<input class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
				value="<?php echo $title; ?>" />
 		</p>

 		<p>
			<label for="<?php echo $this->get_field_id( 'show_keyword' ); ?>">
				<?php _e( 'Enable Keyword Search', 'noo' ); ?>
			</label>
			<select class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'show_keyword' ) ); ?>"
				name="<?php echo $this->get_field_name( 'show_keyword' ); ?>">
					<option value="yes"<?php echo $show_keyword == 'yes' ? ' selected' : ''; ?>><?php _e( 'Yes', 'noo' ); ?></option>
					<option value="no"<?php echo $show_keyword == 'no' ? ' selected' : ''; ?>><?php _e( 'No', 'noo' ); ?></option>
			</select>
		</p>

		<?php for ( $po = 1; $po <= 8; $po++ ) : ?>
			<?php $r_pos = esc_attr($instance["r_pos{$po}"]); ?>
			<!-- Search Position #<?php echo $po; ?> -->

				<p>
					<label for="<?php echo $this->get_field_id( "r_pos{$po}" ); ?>">
						<?php _e( 'Search Position #' . $po, 'noo' ); ?>
					</label>
					<select class="widefat search-position"
						id="<?php echo esc_attr( $this->get_field_id( "r_pos{$po}" ) ); ?>"
						name="<?php echo $this->get_field_name( "r_pos{$po}" ); ?>">
							<?php foreach ($search_fields as $key => $value) {
								echo "<option value='{$key}'" . ($r_pos == $key ? ' selected' : '') . ">{$value}</option>";
							} ?>
					</select>
				</p>

			<!-- /Search Position #<?php echo $po; ?> -->

		<?php endfor; ?>
 		<?php
    }
 
    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['show_keyword'] = strip_tags($new_instance['show_keyword']);
		for ( $po = 1; $po <= 8; $po++ ) {
			$instance["r_pos{$po}"] = strip_tags($new_instance["r_pos{$po}"]);
		}

        return $instance;
    }
 
    public function widget( $args, $instance ) {
        extract($args);
        $title = apply_filters( 'widget_title', $instance['title'] );
        $show_keyword = $instance['show_keyword'];
        
        $prefix = uniqid();
 
        echo $before_widget;
        echo $before_title.$title.$after_title;
        ?>
        <form id="<?php echo $prefix . '_form'; ?>" method="get" class="widget-advanced-search" action="<?php echo esc_url( home_url( '/' ) );?>">
	        <style type="text/css">
	        .widget-advanced-search ul li:first-child, .widget-advanced-search ol li:first-child{
	        	padding-top: 6px;
	        }
	        </style>
	        <input type="hidden" class="form-control" name="post_type" value="noo_resume" />
	        <input type="hidden" class="form-control" name="action" value="live_search" />
	        <!-- <input type="hidden" class="form-control" name="live-search-nonce" value="<?php //echo wp_create_nonce( 'noo-advanced-live-search' ); ?>" /> -->
	        <?php wp_nonce_field( 'noo-advanced-live-search', 'live-search-nonce' ); ?>
	        <?php
	        if ( $show_keyword == 'yes' ) :
	        	?>
	        	<div class="form-group">
				    <label class="sr-only" for="<?php echo $prefix . '_search-keyword'; ?>"><?php _e( 'Keyword', 'noo' ); ?></label>
				    <input type="text" class="form-control" id="<?php echo $prefix . '_search-keyword'; ?>" name="s" placeholder="<?php _e( 'Keyword', 'noo' ); ?>" value="<?php echo ( isset( $_GET['s'] ) ? esc_attr( $_GET['s'] ) : '' ); ?>"/>
				</div>
	        	<?php
	        else :
	        	?>
	        	<input type="hidden" name="s" value="" />
	        	<?php
	        endif;
	       	for ( $po = 1; $po <= 8; $po++ ) {
				Noo_Job::advanced_search_field($instance["r_pos{$po}"], true, $prefix . "_pos{$po}", true);
			}
			?>
			<button type="submit" class="btn btn-primary btn-search-submit"><?php _e('Search', 'noo'); ?></button>
		</form>
		<?php if( Noo_Resume::can_view_resume(null,true) ) : ?>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					var container = $(".noo-main > .resumes");
					if( container.length ) {
						$("#<?php echo $prefix . '_form'; ?>").on("change", function(event) {
							event.preventDefault();
							var $form = $("#<?php echo $prefix . '_form'; ?> .form-control");
							var data = $(this).serialize();
							history.pushState(null, null, "?" + $form.serialize());
							$.ajax({
								url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
								data: data
							})
							.done(function( data ) {
								$(".noo-main").html(data);
								if($('[data-paginate="loadmore"]').find(".loadmore-action").length){
									$('[data-paginate="loadmore"]').each(function(){
										var $this = $(this);
										$this.nooLoadmore({
											navSelector  : $this.find("div.pagination"),            
											nextSelector : $this.find("div.pagination a.next"),
											itemSelector : "article.loadmore-item",
											finishedMsg  : "<?php echo __('All resumes displayed','noo'); ?>"
										});
									});
								}
							})
							.fail(function() {
								
							})
						});
					}
				});
			</script>
		<?php
		endif;
        echo $after_widget;
    }
}


function noo_register_widget() {
	register_widget( 'Noo_Tweets' );
	register_widget( 'Noo_MailChimp' );
	if( class_exists('Noo_Job') ) {
		register_widget('Noo_Job_Type_Widget');
		register_widget('Noo_Job_Category_Widget');
		register_widget('Noo_Job_Location_Widget');
		register_widget('Noo_Job_Search_Widget');
		register_widget('Noo_Job_Count_Widget');
		register_widget('Noo_Jobs_Widget');
		register_widget('Noo_Advanced_Search_Widget');
		register_widget('Noo_Advanced_Job_Search_Widget');
		register_widget('Noo_Advanced_Resume_Search_Widget');
	}

	if( class_exists( 'Noo_Resume') ) {
		register_widget('Noo_Resume_Categories_Widget');
		register_widget('Noo_Resume_Search_Widget');
		register_widget('Noo_Resume_Count_Widget');
	}
}
add_action( 'widgets_init', 'noo_register_widget' );