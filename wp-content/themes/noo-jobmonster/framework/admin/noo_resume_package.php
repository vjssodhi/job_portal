<?php

if( !class_exists('WC_Product_Resume_Package') ) :
	class WC_Product_Resume_Package extends WC_Product {
		
		public function __construct( $product ) {
			$this->product_type = 'resume_package';
			parent::__construct( $product );
		}
		
		public function is_purchasable() {
			return true;
		}
		
		public function is_sold_individually() {
			return true;
		}
		
		public function is_virtual() {
			return true;
		}

		public function is_downloadable() {
			return true;
		}

		public function has_file( $download_id = '' ) {
			return false;
		}
		
		public function get_post_resume_limit(){

			if($this->resume_posting_unlimit){

				return 99999999;

			}
			
			if($this->resume_posting_limit){
				if ( $this->resume_posting_limit == 1 )
					return 1;
				return $this->resume_posting_limit;
			}
			return 1;
		}
		
		public function get_resume_feature_limit(){
			if($this->resume_feature_limit){
				return $this->resume_feature_limit;
			}
			return 0;
		}
		
		public function get_can_view_resume(){
			return $this->can_view_resume;
		}
		
		public function add_to_cart_url() {
			$url = $this->is_in_stock() ? esc_url( remove_query_arg( 'added-to-cart', add_query_arg( 'add-to-cart', $this->id, home_url() ) ) ) : get_permalink( $this->id );
			return apply_filters( 'woocommerce_product_add_to_cart_url', $url, $this );
		}
		
		public function add_to_cart_text() {
			$text = $this->is_purchasable() && $this->is_in_stock() ? __( 'Select', 'noo' ) : __( 'Read More', 'noo' );
			return apply_filters( 'woocommerce_product_add_to_cart_text', $text, $this );
		}
	}
endif;

if( !class_exists('Noo_Resume_Package') ) :
	class Noo_Resume_Package {
		
		public function __construct(){
			add_action('init', array(&$this,'init'));
			add_shortcode('noo_resume_package_list', array(&$this,'noo_resume_package_list_shortcode'));
			add_action('woocommerce_add_to_cart_handler_resume_package', array(&$this,'woocommerce_add_to_cart_handler'),100);
			// add_action( 'woocommerce_order_status_processing', array( $this, 'order_paid' ) );
			add_action( 'woocommerce_order_status_completed', array( $this, 'order_paid' ) );
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'checkout_fields_resume_meta' ) );
		
			if(is_admin()){
				add_filter( 'woocommerce_product_data_tabs', array( $this, 'product_data_tabs' ) );

				add_action('admin_init', array(&$this,'admin_init'));
				add_filter('noo_job_settings_tabs_array', array(&$this,'add_seting_resume_package_tab'));
				add_action('noo_job_setting_resume_package', array(&$this,'setting_page'));
			}else{
				add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ),100);
			}
		}
		
		public function init(){
			if(!defined('WOOCOMMERCE_VERSION'))
				return ;
			
			add_action( 'after_switch_theme', array(&$this,'switch_theme_hook'));
			if(is_admin()){
				add_filter( 'product_type_selector' , array(&$this, 'product_type_selector'));
				add_action( 'woocommerce_product_options_general_product_data', array( $this, 'resume_package_product_data' ) );
				add_action( 'woocommerce_process_product_meta', array( $this,'save_product_data' ) );
			}
		}
		
		public function pre_get_posts($q){
			global $noo_view_resume_package;
		
			if(!defined('WOOCOMMERCE_VERSION'))
				return ;
			if(empty($noo_view_resume_package) && $this->is_noo_property_query($q))
			{
				$tax_query = array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => array( 'resume_package' ),
					'operator' => 'NOT IN',
				);
				$q->tax_query->queries[] = $tax_query;
				$q->query_vars['tax_query'] = $q->tax_query->queries;
			}
			$noo_view_resume_package = false;
		
		}
		
		protected function is_noo_property_query($query = null){
			if( empty( $query ) ) return false;
			if( isset($query->query_vars['post_type']) && $query->query_vars['post_type'] === 'product' )
				return true;
			if(is_post_type_archive( 'product' ) || is_product_taxonomy() )
				return true;
			return false;
		
		}

		public function checkout_fields_resume_meta( $order_id ) {
			global $woocommerce;

			/* -------------------------------------------------------
			 * Create order create fields _resume_id for storing resume that need to activate
			 * ------------------------------------------------------- */
				foreach ( $woocommerce->cart->cart_contents as $cart_item_key => $cart_item ) {
					if ( isset( $cart_item['_resume_id'] ) && is_numeric( $cart_item['_resume_id'] ) ) :

					    update_post_meta( $order_id, '_resume_id', sanitize_text_field( $cart_item['_resume_id'] ) );

					endif;
			   }
		}
		
		public function order_paid($order_id){
			$order = new WC_Order( $order_id );
			if ( get_post_meta( $order_id, 'resume_package_processed', true ) ) {
				return;
			}
			foreach ( $order->get_items() as $item ) {
				$product = get_product( $item['product_id'] );

				if ($product->is_type( 'resume_package' ) && $order->customer_user ) {
					$user_id = $order->customer_user;

					$package_data = array(
						'product_id'   => $product->id,
						'created'      => current_time('mysql'),
						'resume_limit'    => absint($product->get_post_resume_limit()),
						'resume_featured' => absint($product->get_resume_feature_limit())
					);

					if( !self::is_purchased_free_package( $user_id ) || $product->get_price() > 0 ) {
						update_user_meta( $user_id,'_resume_package', $package_data );
						update_user_meta( $user_id, '_resume_added', '0' );
						update_user_meta( $user_id, '_resume_featured', '0' );
						
						$resume_id = noo_get_post_meta( $order_id, '_resume_id', '' );
						if ( !empty( $resume_id ) && is_numeric( $resume_id ) ) {
							$resume = get_post( $resume_id );
							if ( $resume->post_type == 'noo_resume' ){
								self::increase_resume_count( $user_id );
								update_post_meta( $resume_id, '_waiting_payment', '' );
								$resume_need_approve = Noo_Resume::get_setting('resume_approve','no' ) == 'yes';
								if( !$resume_need_approve ) {
									wp_update_post(array(
										'ID'=>$resume_id,
										'post_status'=>'publish',
										'post_date'		=> current_time( 'mysql' ),
										'post_date_gmt'	=> current_time( 'mysql' , 1 )
									));
								} else {
									wp_update_post(array(
										'ID'=>$resume_id,
										'post_status'=>'pending'
									));
									update_post_meta($resume_id, '_in_review', 1);
								}

								Noo_Resume::send_notification($resume_id, $user_id);
							}
						}

						if( $product->get_price() <= 0 ) {
							update_user_meta( $user_id, '_free_package_bought', 1 );
						}
					}

					break;
				}
			}
			update_post_meta( $order_id, 'resume_package_processed', true );
		}
		
		public function woocommerce_add_to_cart_handler(){
			global $woocommerce;
			$product_id          = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $_REQUEST['add-to-cart'] ) );
			$product 			= get_product( absint($product_id) );
			$quantity 			= empty( $_REQUEST['quantity'] ) ? 1 : wc_stock_amount( $_REQUEST['quantity'] );
			$passed_validation 	= apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );
			if ( $product->is_type( 'resume_package' ) && $passed_validation ) {
				// Add the product to the cart
				$woocommerce->cart->empty_cart();
				if (  $woocommerce->cart->add_to_cart( $product_id, $quantity ) ) {
					//woocommerce_add_to_cart_message( $product_id );
					wp_safe_redirect($woocommerce->cart->get_checkout_url());
					die;
				}
			}
			
		}
		
		public function admin_init(){
			register_setting('resume_package','resume_package');
		}
		
		public static function get_setting($id = null ,$default = null){
			$resume_package_setting = get_option('resume_package');
			if(isset($resume_package_setting[$id]))
				return $resume_package_setting[$id];
			return $default;
		}
		
		public function switch_theme_hook($newname = '', $newtheme = ''){
			if(defined('WOOCOMMERCE_VERSION')){
				if ( ! get_term_by( 'slug', sanitize_title( 'resume_package' ), 'product_type' ) ) {
					wp_insert_term( 'resume_package', 'product_type' );
				}
			}
		}
		
		public function product_type_selector($types){
			$types[ 'resume_package' ] = __( 'Resume Package', 'noo' );
			return $types;
		}
		
		public function resume_package_product_data(){
			global $post;
			?>
			<div class="options_group show_if_resume_package">
			<?php 
			
			$custom_attributes = get_post_meta( $post->ID, '_resume_posting_unlimit', true ) ? 'disabled' : '';
			woocommerce_wp_text_input( 
				array( 
					'id' => '_resume_posting_limit', 
					'label' => __( 'Resume posting limit', 'noo' ), 
					'description' => __( 'The number of resume a user can post with this package.', 'noo' ), 
					'value' => max( get_post_meta( $post->ID, '_resume_posting_limit', true ), 1 ), 
					'placeholder' => 1, 
					'type' => 'number', 
					'desc_tip' => true, 
					'custom_attributes' => array( 'min' => '', 'step' => '1', $custom_attributes => $custom_attributes ) 
				) 
			);
			woocommerce_wp_checkbox(
				array(
					'id' => '_resume_posting_unlimit', 
					'label' => '',
					'value' => get_post_meta( $post->ID, '_resume_posting_unlimit', true ),
					'description' => __( 'Unlimited posting?', 'noo' ), 
				)
			);
			woocommerce_wp_text_input( 
				array( 
					'id' => '_resume_feature_limit', 
					'label' => __( 'Featured Resume limit', 'noo' ), 
					'description' => __( 'The number of a user can set resume to feature with this package.', 'noo' ), 
					'value' => max( get_post_meta( $post->ID, '_resume_feature_limit', true ), 0 ), 
					'placeholder' => '', 
					'desc_tip' => true, 
					'type' => 'number', 
					'custom_attributes' => array( 'min' => '', 'step' => '1' ) ) );

			$can_view_job_setting = Noo_Job::get_setting('noo_job_general', 'can_view_job','candidate');
			if( $can_view_job_setting == 'package' ) {
				woocommerce_wp_checkbox(
					array(
						'id' => '_can_view_job',
						'label' => __( 'Can view Jobs detail', 'noo' ),
						'description' => __( 'Alowing buyers access to resumes.', 'noo' ),
						'cbvalue' => 1,
						'desc_tip' => true,) );
			}
				?>
			
				<script type="text/javascript">
					jQuery('.pricing').addClass( 'show_if_resume_package' );
					jQuery(document).ready(function($) {
						$("#_resume_posting_unlimit").change(function() {
							if(this.checked) {
								$('#_resume_posting_limit').prop('disabled', true);
							} else {
								$('#_resume_posting_limit').prop('disabled', false);
							}
						});
					});
				</script>
				<?php 
				do_action('noo_resume_package_data')
				?>
			</div>
			<?php
		}
		
		public function save_product_data($post_id){
			// Save meta
			$fields = array(
				'_resume_posting_limit' 	=> 'int',
				'_resume_feature_limit'    => 'int',
				'_can_view_job'		=> '',
			);
			foreach ( $fields as $key => $value ) {
				$value = ! empty( $_POST[ $key ] ) ? $_POST[ $key ] : '';
				switch ( $value ) {
					case 'int' :
						$value = absint( $value );
						break;
					case 'float' :
						$value = floatval( $value );
						break;
					default :
						$value = sanitize_text_field( $value );
				}
				update_post_meta( $post_id, $key, $value );
			}
			
			do_action('noo_resume_package_save_data');
		}

		public function product_data_tabs( $product_data_tabs = array() ) {
			if( empty( $product_data_tabs ) ) return;

			if( isset( $product_data_tabs['shipping'] ) && isset( $product_data_tabs['shipping']['class'] ) ) {
				$product_data_tabs['shipping']['class'][] = 'hide_if_resume_package';
			}
			if( isset( $product_data_tabs['linked_product'] ) && isset( $product_data_tabs['linked_product']['class'] ) ) {
				$product_data_tabs['linked_product']['class'][] = 'hide_if_resume_package';
			}
			if( isset( $product_data_tabs['attribute'] ) && isset( $product_data_tabs['attribute']['class'] ) ) {
				$product_data_tabs['attribute']['class'][] = 'hide_if_resume_package';
			}

			return $product_data_tabs;
		}
		
		public function add_seting_resume_package_tab($tabs){
			$temp1 = array_slice($tabs, 0, 2);
			$temp2 = array_slice($tabs, 2);

			$resume_package_tab = array( 'resume_package' => __('Resume Packages','noo') );
			return array_merge($temp1, $resume_package_tab, $temp2);
		}
		
		public function noo_resume_package_list_shortcode($atts, $content = null){
			extract(shortcode_atts(array(
					'product_cat' =>'',
					'add_to_cart'=>true
				), $atts));

			ob_start();
			include(locate_template("layouts/resume_package.php"));
			return ob_get_clean();
		}
		
		public function setting_page(){
			?>
				<?php settings_fields('resume_package'); ?>
				<h3><?php echo __('Package Options','noo')?></h3>
				<table class="form-table" cellspacing="0">
					<tbody>
						<tr>
							<th>
								<?php esc_html_e('Package Page','noo')?>
							</th>
							<td>
								<?php 
								$args = array(
									'name'             => 'resume_package[package_page_id]',
									'id'               => 'package_page_id',
									'sort_column'      => 'menu_order',
									'sort_order'       => 'ASC',
									'show_option_none' => ' ',
									'class'            => '',
									'echo'             => false,
									'selected'         => self::get_setting('package_page_id')
								);
								?>
								<?php echo str_replace(' id=', " data-placeholder='" . __( 'Select a page&hellip;', 'noo' ) .  "' id=", wp_dropdown_pages( $args ) ); ?>
								<p><small><?php _e('Select a page with shortcode [noo_resume_package_list]', 'noo'); ?></small></p>
							</td>
						</tr>
						<tr>
							<th>
								<?php esc_html_e('Allow re-purchase free package','noo')?>
							</th>
							<td>
								<?php $repurchase_free = self::get_setting('repurchase_free',''); ?>
								<input type="hidden" name="resume_package[repurchase_free]" value="">
								<input type="checkbox" <?php checked( $repurchase_free, '1' ); ?> name="resume_package[repurchase_free]" value="1">
								<p><small><?php echo __('Enable this option if you allow candidate to purchase the free package more than one time.','noo') ?></small></p>
							</td>
						</tr>
						<?php do_action( 'noo_setting_resume_package_fields' ); ?>
					</tbody>
				</table>
			<?php 
		}

		public static function is_purchased_free_package( $user_id = '' ) {
			if( empty( $user_id ) ) return false;

			if( self::get_setting('repurchase_free','') ) return false;

			return (bool) get_user_meta( $user_id, '_free_package_bought', true );
		}
		
		public static function get_candidate_package($candidate_id=''){
			if(!self::use_woocommerce_package()){
				$package = array(
					'resume_duration' => absint(self::get_setting('noo_resume_general', 'resume_display_duration',30)),
					'resume_limit'    => absint(self::get_setting('noo_resume_general', 'resume_posting_limit',5)),
					'resume_featured' => absint( self::get_setting('noo_resume_general', 'resume_feature_limit',1))
				);
				return $package;
			}
			if(empty($candidate_id)){
				$candidate_id = get_current_user_id();
			}
			$resume_package = get_user_meta($candidate_id,'_resume_package',true);
			return $resume_package;
		}

		public static function get_resume_remain( $candidate_id = '' ) {
			if(empty($candidate_id)){
				$candidate_id = get_current_user_id();
			}

			$package = self::get_candidate_package( $candidate_id );
			$resume_limit = empty( $package ) || !is_array( $package ) || !isset( $package['resume_limit'] ) ? 0 : $package['resume_limit'];
			$resume_added = self::get_resume_added( $candidate_id );

			return absint($resume_limit) - absint($resume_added);
		}

		public static function get_resume_added( $candidate_id = '' ) {
			if(empty($candidate_id)){
				$candidate_id = get_current_user_id();
			}

			$resume_added = get_user_meta($candidate_id,'_resume_added',true);

			return empty( $resume_added ) ? 0 : absint( $resume_added );
		}

		public static function set_resume_expires($resume_id='') {
			if( empty( $resume_id ) ) return false;

			$_ex = noo_get_post_meta($resume_id,'_expires');
			$candidate_id = get_post_field( 'post_author', $resume_id );
			if(empty($_ex) && $package = Noo_resume::get_candidate_package($candidate_id)){
				$_expires = strtotime('+'.absint(@$package['resume_duration']).' day');
				update_post_meta($resume_id, '_expires', $_expires);
			}
		}

		public static function increase_resume_count($candidate_id='') {
			$candidate_id = empty( $candidate_id ) ? get_current_user_id() : $candidate_id;
			if( empty( $candidate_id ) ) return false;

			$_count = self::get_resume_added( $candidate_id );
			update_user_meta($candidate_id, '_resume_added', $_count + 1 );
		}

		public static function decrease_resume_count($candidate_id='') {
			$candidate_id = empty( $candidate_id ) ? get_current_user_id() : $candidate_id;
			if( empty( $candidate_id ) ) return false;

			$_count = self::get_resume_added( $candidate_id );
			update_user_meta($candidate_id, '_resume_added', max( 0, $_count - 1 ) );
		}
	}
	new Noo_Resume_Package();

endif;