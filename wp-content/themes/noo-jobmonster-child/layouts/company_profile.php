<?php 
$company_id = isset($_GET['company_id']) ? absint($_GET['company_id']) : 0;
$company = get_post($company_id);
$user_ID = get_current_user_id(); 
?>
<div class="company-profile-form">
	<div class="form-group  row">
		<label for="company_name" class="col-sm-3 control-label"><?php _e('Company Name','noo')?></label>
		<div class="col-sm-9">
	    	<input type="text" class="form-control" required id="company_name" value="<?php echo ($company_id ? $company->post_title  : '')?>"  name="company_name" placeholder="<?php echo esc_attr__('Enter your company name','noo')?>">
	    </div>
	</div>
	<div class="form-group  row">
		<label for="company_website" class="col-sm-3 control-label"><?php _e('Company Website','noo')?></label>
		<div class="col-sm-9">
	    	<input type="text" class="form-control" id="company_website" value="<?php echo ($company_id ? noo_get_post_meta($company_id,'_website') : '') ?>" name="company_website" placeholder="<?php echo esc_attr__('Enter your company website','noo')?>">
	    </div>
	</div>
	<div class="form-group  row">
	    <label for="company_desc" class="col-sm-3 control-label"><?php _e('Company Description','noo')?></label>
	    <div class="col-sm-9">
	    	<textarea class="form-control form-control-editor" id="company_desc"  name="company_desc" rows="8" placeholder="<?php echo esc_attr__('Enter your company description','noo')?>"><?php echo ($company_id ? $company->post_content : '') ?></textarea>
	    </div>
	</div>
	<div class="form-group  row">
		<label class="col-sm-3 control-label"><?php _e('Company Logo','noo')?></label>
		<div class="col-sm-9">
			<div class="row">
				<div id="noo_upload-thumb-company" class="col-md-6 noo_upload" style="width:auto">
					<?php
						$company_logo = ($company_id ? noo_get_post_meta($company_id,'_logo') : '');
						show_list_image_upload($company_logo, 'company_logo');
					?>
				</div>
				<div id="noo_upload-wrap-company" class="col-md-6">
					
					<div id="noo_upload-company" class="btn btn-default">
						<i class="fa fa-folder-open-o"></i> <?php _e('Browse','noo');?>
					</div>
				</div>
				<script>
				jQuery(document).ready(function($) {
					//$('#noo_upload-cover').click(function(event) {
						jQuery('#noo_upload-company').noo_upload({
							input_name : 'company_logo',
							container : 'noo_upload-wrap-company',
							browse_button : 'noo_upload-company',
							tag_thumb : 'noo_upload-thumb-company',
							url : '<?php echo admin_url('admin-ajax.php') . '?action=noo_upload&nonce=' . wp_create_nonce('aaiu_allow'); ?>',
							flash_swf_url : '<?php echo includes_url('js/plupload/plupload.flash.swf'); ?>'
						});
					//});
				});
				</script>
			</div>
		</div>
	</div>
	
	<?php if( Noo_Company::get_setting('cover_image', 'yes') == 'yes' ) : ?>
		<div class="form-group row">
			<label class="col-sm-3 control-label"><?php _e('Cover Image','noo')?></label>
			<div class="col-sm-9">
				<div class="row">
					<div id="noo_upload-company-thumb-cover" class="col-md-6 noo_upload" style="width:auto">
						<?php
							$company_cover_image = ($company_id ? noo_get_post_meta($company_id,'_cover_image') : '');
							show_list_image_upload($company_cover_image, 'cover_image');
						?>
					</div>
					<div id="noo_upload-wrap" class="col-md-6">
						
						<div id="noo_upload-company-cover" class="btn btn-default">
							<i class="fa fa-folder-open-o"></i> <?php _e('Browse','noo');?>
						</div>
						
					</div>
					<script>
					jQuery(document).ready(function($) {
						//$('#noo_upload-cover').click(function(event) {
							$('#noo_upload-company-cover').noo_upload({
								input_name : 'cover_image',
								container : 'noo_upload-wrap',
								browse_button : 'noo_upload-company-cover',
								tag_thumb : 'noo_upload-company-thumb-cover',
								url : '<?php echo admin_url('admin-ajax.php') . '?action=noo_upload&nonce=' . wp_create_nonce('aaiu_allow'); ?>',
								flash_swf_url : '<?php echo includes_url('js/plupload/plupload.flash.swf'); ?>'
							});
						//});
					});
					</script>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<!--<div class="form-group row">
		<label for="company_googleplus" class="col-sm-3 control-label"><?php _e('Google+','noo')?></label>
		<div class="col-sm-9">
	    	<input type="text" class="form-control" id="company_googleplus" value="<?php echo ($company_id ? noo_get_post_meta($company_id,'_googleplus') : '') ?>"  name="company_googleplus" placeholder="<?php echo esc_attr__('http://','noo')?>">
	    </div>
	</div>
	<div class="form-group  row">
		<label for="company_facebook" class="col-sm-3 control-label"><?php _e('Facebook','noo')?></label>
		<div class="col-sm-9">
	    	<input type="text" class="form-control" id="company_facebook" value="<?php echo ($company_id ? noo_get_post_meta($company_id,'_facebook') : '') ?>"  name="company_facebook" placeholder="<?php echo esc_attr__('http://','noo')?>">
	    </div>
	</div>
	<div class="form-group  row">
		<label for="company_linkedin" class="col-sm-3 control-label"><?php _e('LinkedIn','noo')?></label>
		<div class="col-sm-9">
	    	<input type="text" class="form-control" id="company_linkedin" value="<?php echo ($company_id ? noo_get_post_meta($company_id,'_linkedin') : '') ?>"  name="company_linkedin" placeholder="<?php echo esc_attr__('http://','noo')?>">
	    </div>
	</div>
	<div class="form-group  row">
		<label for="company_twitter" class="col-sm-3 control-label"><?php _e('Twitter','noo')?></label>
		<div class="col-sm-9">
	    	<input type="text" class="form-control" id="company_twitter" value="<?php echo ($company_id ? noo_get_post_meta($company_id,'_twitter') : '') ?>"  name="company_twitter" placeholder="<?php echo esc_attr__('http://','noo')?>">
	    </div>
	</div>
	<div class="form-group  row">
		<label for="company_instagram" class="col-sm-3 control-label"><?php _e('Instagram','noo')?></label>
		<div class="col-sm-9">
	    	<input type="text" class="form-control" id="company_instagram" value="<?php echo ($company_id ? noo_get_post_meta($company_id,'_instagram') : '') ?>"  name="company_instagram" placeholder="<?php echo esc_attr__('http://','noo')?>">
	    </div>
	</div>-->
</div>