<?php 
$job_id = isset($_GET['job_id']) ?absint($_GET['job_id']) : 0;
$job = get_post($job_id);
$default_job_content = Noo_Job::get_setting('noo_job_general', 'default_job_content', '' );
?>
<div class="job-form">
	<div class="job-form-detail">
		<div class="form-group row">
			<label for="position" class="col-sm-3 control-label"><?php _e('Job Title','noo')?></label>
			<div class="col-sm-9">
		    	<input type="text" value="<?php echo ($job_id ? $job->post_title : '')?>" class="form-control jform-validate" id="position"  name="position" autofocus required placeholder="<?php echo esc_attr__('Enter a short title for your job','noo')?>">
		    </div>
		</div>
		<div class="form-group row">
		    <label for="desc" class="col-sm-3 control-label"><?php _e('Job Description','noo')?></label>
		    <div class="col-sm-9">
		    	<textarea class="form-control form-control-editor ignore-valid jform-validate" id="desc"  name="desc" rows="8" placeholder="<?php echo esc_attr__('Describe your job in a few paragraphs','noo')?>"><?php echo ($job_id ? $job->post_content : $default_job_content)?></textarea>
		    </div>
		</div>
		<?php
			if ( Noo_Job::get_setting('noo_job_general', 'cover_image','yes') == 'yes' ) :
		?>
			<div class="form-group  row">
				<label class="col-sm-3 control-label"><?php _e('Cover Image','noo')?></label>
				<div class="col-sm-9">
					<div class="row">
						<div id="noo_upload-thumb-cover" class="col-md-2 noo_upload">
							<?php
								$job_cover_image = ($job_id ? noo_get_post_meta($job_id,'_cover_image') : '');
								show_list_image_upload($job_cover_image, 'cover_image');
							?>
						</div>
						<div id="noo_upload-wrap" class="col-md-10">
							
							<div id="noo_upload-cover" class="btn btn-default">
								<i class="fa fa-folder-open-o"></i> <?php _e('Browse','noo');?>
							</div>
							
						</div>
						<script>
						jQuery(document).ready(function($) {
							//$('#noo_upload-cover').click(function(event) {
								$('#noo_upload-cover').noo_upload({
									input_name : 'cover_image',
									container : 'noo_upload-wrap',
									browse_button : 'noo_upload-cover',
									tag_thumb : 'noo_upload-thumb-cover',
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
		<div class="form-group row">
			<label for="location" class="col-sm-3 control-label"><?php _e('Job Location','noo')?></label>
			<div class="col-sm-9<?php if ( noo_get_option( 'noo_enable_rtl_support', 0 ) ) echo ' chosen-rtl'; ?>">
				<select id="location" name="location[]" multiple data-placeholder="<?php echo esc_attr__('Enter a city and country or leave it blank','noo')?>" class="form-control form-control-chosen jform-chosen-validate">
					<option value=""></option>
					<?php 
					$job_cations = (array)wp_get_object_terms(absint($job_id),'job_location',array('fields' => 'slugs'));
					foreach ((array)Noo_Job::get_job_locations() as $key=>$location){ ?>
					<option value="<?php echo esc_attr($key)?>" <?php if(!empty($job_cations) && in_array($key, $job_cations)):?> selected="selected"<?php endif;?>><?php echo esc_html($location)?></option>
					<?php }?>
				</select>
		    	<p class="help-block add-new-location"><a class="add-new-location-btn" href="#add-location"><?php esc_html_e('+ Add New Location','noo')?></a></p>
		    	<div class="add-new-location-content" style="display:none">	
		    		<div class="row">
		    			<div class="col-sm-6">
		    				<input type="text" value="" class="form-control input-sm" placeholder="<?php echo esc_attr__('Enter new location','noo')?>" style="height: 36px;">
		    			</div>
		    			<div class="col-sm-6">
		    				<button class="btn btn-small btn-default add-new-location-submit" type="button" style="height: 36px;"><?php _e('Add','noo')?></button>
		    			</div>
		    		</div>
		    	</div>
		    </div>
		</div>
		<div class="form-group row">
			<label for="type" class="col-sm-3 control-label"><?php _e('Job Type','noo')?></label>
			<div class="col-sm-9<?php if ( noo_get_option( 'noo_enable_rtl_support', 0 ) ) echo ' chosen-rtl'; ?>">
				<?php 
				$types = get_terms('job_type',array('hide_empty'=>0));
				if($types):
				?>
				<select class="form-control form-control-chosen ignore-valid jform-chosen-validate" name="type" id="type" data-placeholder="<?php echo esc_attr__('Select job type for your job ','noo')?>">
					<option value=""></option>
					<?php 
					$selected = '';
					if($job_id){
						$terms = get_the_terms($job_id,'job_type');
						if($terms && is_array($terms)){
							$selected = current($terms);
						}
					}
					?>
					<?php foreach ($types as $type): ?>
						<option value="<?php echo esc_attr($type->term_id)?>" <?php if(!empty($selected) && $selected->term_id == $type->term_id):?> selected="selected"<?php endif;?>><?php echo esc_html($type->name)?></option>
					<?php endforeach;?>
				</select>
				<?php endif;?>
		    </div>
		</div>
		<div class="form-group row">
			<label for="category" class="col-sm-3 control-label"><?php _e('Job Category','noo')?></label>
			<div class="col-sm-9<?php if ( noo_get_option( 'noo_enable_rtl_support', 0 ) ) echo ' chosen-rtl'; ?>">
				<?php 
				$categories = get_terms('job_category',array('hide_empty'=>0));
				if($categories):
				?>
				<select class="form-control form-control-chosen ignore-valid jform-chosen-validate" name="category[]" id="category" multiple data-placeholder="<?php echo esc_attr__('Select a category for your job ','noo')?>">
					<option value=""></option>
					<?php 
					$selected = array();
					if($job_id){
						$terms = get_the_terms($job_id,'job_category');
						if($terms && is_array($terms)){
							foreach ($terms as $t){
								$selected[] = $t->term_id;
							}
						}
					}
					?>
					<?php foreach ($categories as $category): ?>
						<option value="<?php echo esc_attr($category->term_id)?>" <?php if(!empty($selected) && in_array($category->term_id, $selected)):?> selected="selected"<?php endif;?>><?php echo esc_html($category->name)?></option>
					<?php endforeach;?>
				</select>
				<?php endif;?>
		    </div>
		</div>
		<div class="form-group row">
			<label for="closing" class="col-sm-3 control-label"><?php _e('Closing Date','noo')?></label>
			<div class="col-sm-9">
		    	<input type="text" value="<?php echo ($job ? noo_get_post_meta($job_id,'_closing') : '')?>" class="form-control jform-validate jform-datepicker" id="closing"  name="closing" placeholder="<?php echo esc_attr__('YYYY-MM-DD','noo')?>">
		    </div>
		</div>
		<div class="form-group row">
			<label for="application_email" class="col-sm-3 control-label"><?php _e('Application Notify Email','noo')?></label>
			<div class="col-sm-9">
		    	<input type="text" value="<?php echo ($job ? noo_get_post_meta($job_id,'_application_email') : '')?>" class="form-control" id="application_email"  name="application_email" >
		    	<em><?php _e('Email to receive application notification. Leave it blank to use your account email.','noo'); ?></em>
		    </div>
		</div>
		<?php $custom_apply_link = Noo_Job::get_setting('noo_job_linkedin', 'custom_apply_link' );
			if( $custom_apply_link == 'employer' ) :
			?>
				<div class="form-group row">
					<label for="custom_application_url" class="col-sm-3 control-label"><?php _e('Custom Application URL','noo')?></label>
					<div class="col-sm-9">
				    	<input type="text" value="<?php echo ($job ? noo_get_post_meta($job_id,'_custom_application_url') : '')?>" class="form-control" id="custom_application_url"  name="custom_application_url" >
				    	<em><?php _e('Custom link to redirect job seekers to when applying for this job.','noo'); ?></em>
				    </div>
				</div>
			<?php endif; ?>
		<?php 
		$default_fields = Noo_Job::get_default_fields();
		$custom_fields = noo_get_custom_fields( Noo_Job::get_setting('noo_job_custom_field', array()), 'noo_jobs_custom_fields_' );
		$fields = array_merge( array_diff_key($default_fields, $custom_fields), $custom_fields );
		$blank_field = array( 'name' => '', 'label' => '', 'type' => 'text', 'value' => '', 'required' => '', 'is_disabled' => '' );
		$job_detail_fields = array();
		if($fields){
			foreach ($fields as $field){
				$field = is_array( $field ) ? array_merge( $blank_field, $field ) : $blank_field; 
				if( !isset( $field['name'] ) || empty( $field['name'] )) continue;
					$id = '_noo_job_field_'.sanitize_title($field['name']);

				if( array_key_exists($field['name'], $default_fields) ) {
					if( isset( $field['is_disabled'] ) && ($field['is_disabled'] == 'yes') )
						continue;

					$id = $field['name'];

				}
				$field['type'] = isset($field['type']) ? $field['type'] : 'text';

				?>
				<div class="form-group row">
					<label for="<?php echo esc_attr($id)?>" class="col-sm-3 control-label"><?php echo(isset( $field['label_translated'] ) ? $field['label_translated'] : @$field['label'])  ?></label>
					<div class="col-sm-9<?php if ( noo_get_option( 'noo_enable_rtl_support', 0 ) ) echo ' chosen-rtl'; ?>">
						<?php noo_show_custom_fields( $field, $job_id, $id ); ?>
				    </div>
				</div>
				<?php
			}
		}
		?>
	</div>
	<?php if(!Noo_Job::get_employer_company()):?>
	<div class="job-form-company">
	<h4><?php _e('Company Profile', 'noo')?></h4>
	<?php echo Noo_Member::get_company_profile_form(false)?>
	</div>
	<?php 
	endif;
	?>
</div>
