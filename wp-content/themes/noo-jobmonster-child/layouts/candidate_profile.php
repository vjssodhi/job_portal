<?php 
// $candidate_id = isset($_GET['candidate_id']) ? absint($_GET['candidate_id']) : get_current_user_id();
// $candidate = !empty($candidate_id) ? get_userdata($candidate_id) : null;

global $current_user;
get_currentuserinfo();

$first_name = empty( $current_user->user_firstname  ) ? '' : $current_user->user_firstname ;
$last_name = empty( $current_user->user_lastname  ) ? '' : $current_user->user_lastname ;
$name = empty( $current_user->display_name ) ? $current_user->user_login : $current_user->display_name;
$email = $current_user->user_email;

?>
<div class="candidate-profile-form row">
	<div class="col-sm-6">
		<!-- <div class="form-group">
			<label for="first_name" class="col-sm-4 control-label"><?php _e('First Name','noo')?></label>
			<div class="col-sm-8">
		    	<input type="text" class="form-control" required id="first_name" value="<?php echo esc_attr($first_name)?>" name="first_name" placeholder="<?php echo esc_attr__('Your first name','noo')?>">
		    </div>
		</div>
		<div class="form-group">
			<label for="last_name" class="col-sm-4 control-label"><?php _e('Last Name','noo')?></label>
			<div class="col-sm-8">
		    	<input type="text" class="form-control" required id="last_name" value="<?php echo esc_attr($last_name)?>" name="last_name" placeholder="<?php echo esc_attr__('Your last name','noo')?>">
		    </div>
		</div> -->
		<div class="form-group">
			<label for="name" class="col-sm-4 control-label"><?php _e('Full Name','noo')?></label>
			<div class="col-sm-8">
		    	<input type="text" class="form-control" required id="name" value="<?php echo esc_attr($name)?>" name="name" placeholder="<?php echo esc_attr__('Your name','noo')?>">
		    </div>
		</div>
		<div class="form-group">
			<label for="email" class="col-sm-4 control-label"><?php _e('Email','noo')?></label>
			<div class="col-sm-8">
		    	<input type="email" class="form-control" required id="email" value="<?php esc_attr_e($email)?>" name="email" placeholder="<?php echo esc_attr__('Your email','noo')?>">
		    </div>
		</div>
		<div class="form-group">
			<label for="current_job" class="col-sm-4 control-label"><?php _e('Current Job','noo')?></label>
			<div class="col-sm-8">
		    	<input type="text" class="form-control" id="current_job" value="<?php esc_attr_e(get_user_meta( $current_user->ID, 'current_job', true ));?>" name="current_job">
		    </div>
		</div>
		<div class="form-group">
			<label for="current_company" class="col-sm-4 control-label"><?php _e('Current Company','noo')?></label>
			<div class="col-sm-8">
		    	<input type="text" class="form-control" id="current_company" value="<?php esc_attr_e(get_user_meta( $current_user->ID, 'current_company', true ));?>" name="current_company">
		    </div>
		</div>
		<div class="form-group">
			<label for="birthday" class="col-sm-4 control-label"><?php _e('Birthday','noo')?></label>
			<div class="col-sm-8">
		    	<input type="text" class="form-control" id="birthday" value="<?php esc_attr_e(get_user_meta( $current_user->ID, 'birthday', true ));?>" name="birthday">
		    	<script type="text/javascript">
					jQuery(document).ready(function($) {
						$("#birthday").datetimepicker({
							format: 'Y-m-d',
							timepicker: false,
							startDate:'1990/01/01',
							scrollMonth: false,
							scrollTime: false,
							scrollInput: false
						});
					});
				</script>
		    </div>
		</div>
		<div class="form-group">
			<label for="address" class="col-sm-4 control-label"><?php _e('Address','noo')?></label>
			<div class="col-sm-8">
		    	<input type="text" class="form-control" id="address" value="<?php esc_attr_e(get_user_meta( $current_user->ID, 'address', true ));?>" name="address">
		    </div>
		</div>
		<div class="form-group">
			<label for="phone" class="col-sm-4 control-label"><?php _e('Phone Number','noo')?></label>
			<div class="col-sm-8">
		    	<input type="text" class="form-control" id="phone" value="<?php esc_attr_e(get_user_meta( $current_user->ID, 'phone', true ));?>" name="phone">
		    </div>
		</div>
		<div class="form-group">
			<label for="facebook" class="col-sm-4 control-label"><?php _e('Facebook','noo')?></label>
			<div class="col-sm-8">
		    	<input type="text" class="form-control" id="facebook" value="<?php esc_attr_e(get_user_meta( $current_user->ID, 'facebook', true)); ?>"  name="facebook" placeholder="<?php echo esc_attr__('http://','noo')?>">
		    </div>
		</div>
		<div class="form-group">
			<label for="twitter" class="col-sm-4 control-label"><?php _e('Twitter','noo')?></label>
			<div class="col-sm-8">
		    	<input type="text" class="form-control" id="twitter" value="<?php esc_attr_e(get_user_meta( $current_user->ID, 'twitter', true)); ?>"  name="twitter" placeholder="<?php echo esc_attr__('http://','noo')?>">
		    </div>
		</div>
		<div class="form-group">
			<label for="linkedin" class="col-sm-4 control-label"><?php _e('LinkedIn','noo')?></label>
			<div class="col-sm-8">
		    	<input type="text" class="form-control" id="linkedin" value="<?php esc_attr_e(get_user_meta( $current_user->ID, 'linkedin', true)); ?>"  name="linkedin" placeholder="<?php echo esc_attr__('http://','noo')?>">
		    </div>
		</div>
		<div class="form-group">
			<label for="behance" class="col-sm-4 control-label"><?php _e('Behance','noo')?></label>
			<div class="col-sm-8">
		    	<input type="text" class="form-control" id="behance" value="<?php esc_attr_e(get_user_meta( $current_user->ID, 'behance', true)); ?>"  name="behance" placeholder="<?php echo esc_attr__('http://','noo')?>">
		    </div>
		</div>
		<div class="form-group">
			<label for="instagram" class="col-sm-4 control-label"><?php _e('Instagram','noo')?></label>
			<div class="col-sm-8">
		    	<input type="text" class="form-control" id="instagram" value="<?php esc_attr_e(get_user_meta( $current_user->ID, 'instagram', true)); ?>"  name="instagram" placeholder="<?php echo esc_attr__('http://','noo')?>">
		    </div>
		</div>

	</div>

	<div class="col-sm-6">
		<div class="form-group">
		    <label for="description" class="control-label"><?php _e('Instroduce Yourself','noo')?> <small><?php _e('(Optional)','noo')?></small></label>
		    <textarea class="form-control form-control-editor" id="description" name="description" rows="8"><?php esc_html_e( $current_user->description ); ?></textarea>
		</div>
		<div class="form-group">
			<label for="profile_image" class="col-sm-3 control-label"><?php _e('Profile Image','noo')?></label>
			<div class="col-sm-9">
				<div id="noo_upload-thumb-candidate" class="col-md-6 noo_upload" style="width:auto">
					<?php
						$profile_image = ($current_user->ID ? get_user_meta( $current_user->ID, 'profile_image', true) : '');
						show_list_image_upload($profile_image, 'profile_image');
					?>
				</div>
				<div id="noo_upload-wrap-candidate" class="col-md-6">
					
					<div id="noo_upload-candidate" class="btn btn-default">
						<i class="fa fa-folder-open-o"></i> <?php _e('Browse','noo');?>
					</div>
				</div>
				<script>
				jQuery(document).ready(function($) {
					//$('#noo_upload-cover').click(function(event) {
						$('#noo_upload-candidate').noo_upload({
							input_name : 'profile_image',
							container : 'noo_upload-wrap-candidate',
							browse_button : 'noo_upload-candidate',
							tag_thumb : 'noo_upload-thumb-candidate',
							url : '<?php echo admin_url('admin-ajax.php') . '?action=noo_upload&nonce=' . wp_create_nonce('aaiu_allow'); ?>',
							flash_swf_url : '<?php echo includes_url('js/plupload/plupload.flash.swf'); ?>'
						});
					//});
				});
				</script>
			</div>
		</div>
	</div>
</div>