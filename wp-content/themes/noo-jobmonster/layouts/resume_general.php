<?php 
$resume_id = isset($_GET['resume_id']) ?absint($_GET['resume_id']) : 0;
$resume = $resume_id ? get_post($resume_id) : '';
$default_fields = Noo_Resume::get_default_fields();
$custom_fields = noo_get_custom_fields( get_option( 'noo_resume' ), 'noo_resume_custom_fields_' );
$fields = array_merge( array_diff_key($default_fields, $custom_fields), $custom_fields );
$blank_field = array( 'name' => '', 'label' => '', 'type' => 'text', 'value' => '', 'required' => '', 'is_disabled' => '' );
?>
<?php do_action('noo_post_resume_general_before'); ?>
<div class="resume-form">
	<div class="resume-form-general row">
		<div class="col-sm-7">
			<div class="form-group">
				<label for="title" class="col-sm-5 control-label"><?php _e('Resume Title','noo')?></label>
				<div class="col-sm-7">
			    	<input type="text" value="<?php echo ($resume ? $resume->post_title : '')?>" class="form-control jform-validate" id="title"  name="title" autofocus required>
			    </div>
			</div>
			<?php 
			if($fields) : foreach ($fields as $field) :
				$field = is_array( $field ) ? array_merge( $blank_field, $field ) : $blank_field; 
				$field['type'] = isset($field['type']) ? $field['type'] : 'text';
				$field['required'] = isset( $field['required'] ) && $field['required'] ? 'required' : '';
				if( !isset( $field['name'] ) || empty( $field['name'] ))
					continue;
				if( array_key_exists($field['name'], $default_fields) ) :
					if( isset( $field['is_disabled'] ) && ($field['is_disabled'] == 'yes') )
						continue;
					$id = $field['name'];
					$label = isset( $field['label_translated'] ) ? $field['label_translated'] : $field['label'];

					if( $field['name'] == '_job_category' ) :
						?>
						<div class="form-group">
							<label for="_job_category" class="col-sm-5 control-label"><?php echo esc_html( $label );?></label>
							<div class="col-sm-7<?php if ( noo_get_option( 'noo_enable_rtl_support', 0 ) ) echo ' chosen-rtl'; ?>">
								<select class="form-control form-control-chosen ignore-valid <?php echo ( $field['required'] == 'required' ? 'jform-chosen-validate' : '' ); ?>" data-placeholder="<?php _e('Select Categories', 'noo'); ?>" name="_job_category[]" id="_job_category" multiple >
									<option value=""></option>
								<?php 
								$categories = get_terms('job_category',array('hide_empty'=>0));
								
								if($categories):
									$value = $resume_id ? noo_get_post_meta($resume_id,'_job_category', '') : '';
								
									$value = noo_json_decode($value);
								?>
									<?php foreach ($categories as $category): ?>
										<option value="<?php echo esc_attr($category->term_id)?>" <?php if(!empty($value) && in_array($category->term_id, $value)):?> selected="selected"<?php endif;?>><?php echo esc_html($category->name)?></option>
									<?php endforeach;?>
								<?php endif;?>
								</select>
						    </div>
						</div>
						<?php
					elseif ( $field['name'] == '_job_location' ) :
						?>
						<div class="form-group">
							<label for="_job_location" class="col-sm-5 control-label"><?php echo esc_html( $label );?></label>
							<div class="col-sm-7<?php if ( noo_get_option( 'noo_enable_rtl_support', 0 ) ) echo ' chosen-rtl'; ?>">
								<select id="_job_location" name="_job_location[]" multiple class="form-control form-control-chosen ignore-valid <?php echo ( $field['required'] == 'required' ? 'jform-chosen-validate' : '' ); ?>" data-placeholder="<?php _e('Select Locations', 'noo'); ?>">
									<option value=""></option>
								<?php 
								$locations = get_terms('job_location',array('hide_empty'=>0));
								if($locations) :
									$value = $resume_id ? noo_get_post_meta($resume_id,'_job_location') : '';
									$value = noo_json_decode($value);
								?>
									<?php 
									foreach ($locations as $location) : ?>
									<option value="<?php echo esc_attr($location->term_id)?>" <?php if(!empty($value) && in_array($location->term_id, $value)):?> selected="selected"<?php endif;?>><?php echo esc_html($location->name)?></option>
									<?php endforeach;?>
								<?php endif;?>
								</select>
						    </div>
						</div>
						<?php
					else :
						$placeholder = isset( $default_fields[$field['name']]['std'] ) ? $default_fields[$field['name']]['std'] : '';
						$value = $resume_id ? noo_get_post_meta($resume_id,$id) : '';
						?>
						<div class="form-group">
							<label for="<?php echo esc_attr( $id );?>" class="col-sm-5 control-label"><?php echo esc_html($label);?></label>
							<div class="col-sm-7">
						    	<input type="text" value="<?php echo esc_attr( $value );?>" class="form-control" <?php echo  $field['required']; ?> id="<?php echo esc_attr( $id );?>"  name="<?php echo esc_attr( $id );?>" placeholder="<?php echo esc_attr($placeholder);?>" >
						    </div>
						</div>
						<?php
					endif;
					?>
					<?php
				else :
					$id = '_noo_resume_field_'.sanitize_title($field['name']);
					$label = isset( $field['label_translated'] ) ? $field['label_translated'] : @$field['label'];
					$value = $resume_id ? noo_get_post_meta($resume_id,$id) : '';
					?><!-- 
					<div class="form-group">
						<label for="<?php echo esc_attr( $id );?>" class="col-sm-5 control-label"><?php echo esc_html($label);?></label>
						<div class="col-sm-7">
					    	<input type="text" value="<?php echo esc_attr( $value );?>" class="form-control" id="<?php echo esc_attr( $id );?>"  name="<?php echo esc_attr( $id );?>" >
					    </div>
					</div> -->
					<div class="form-group">
						<label for="<?php echo esc_attr($id)?>" class="col-sm-5 control-label"><?php echo esc_html($label);?></label>
						<div class="col-sm-7<?php if ( noo_get_option( 'noo_enable_rtl_support', 0 ) ) echo ' chosen-rtl'; ?>">
							<?php noo_show_custom_fields( $field, $resume_id, $id ); ?>
					    </div>
					</div>
				<?php endif; ?>
			<?php endforeach; endif; ?>
			<?php if( Noo_Resume::get_setting('enable_video', '') ) : ?>
				<div class="form-group">
					<label for="url_video" class="col-sm-5 control-label"><?php _e( 'Video URL', 'noo' ); ?></label>
					<div class="col-sm-7">
				    	<input type="text" value="<?php echo noo_get_post_meta( $resume_id, '_noo_url_video' ) ?>" class="form-control" id="url_video"  name="url_video" placeholder="<?php _e( 'Youtube or Vimeo link', 'noo' ); ?>" >
				    </div>
				</div>
			<?php endif; ?>
		</div>
		<?php if( Noo_Resume::get_setting('enable_upload_resume', '1') ) : ?>
			<div class="col-sm-5">
				<label for="file_cv" class="control-label"><?php _e('Upload your Attachment','noo')?></label>
				<div class="form-control-flat">
					<div class="upload-to-cv clearfix">
				    	<?php noo_plupload_form( 'file_cv', Noo_Resume::get_setting('extensions_upload_resume', 'doc,pdf' ), noo_get_post_meta( $resume_id, '_noo_file_cv' ) ) ?>
						<p class="help-block"><?php echo sprintf( __('Allowed file: %s', 'noo'), Noo_Resume::get_setting('extensions_upload_resume', 'doc,pdf') ); ?></p>
					</div>
				</div>
			</div>
		<?php endif; ?>
		<div class="col-sm-5">
			<div class="form-group">
			    <label for="desc" class="control-label"><?php _e('Introduce Yourself','noo')?></label>
			    <textarea class="form-control form-control-editor ignore-valid" id="desc" name="desc" rows="14" placeholder="<?php echo esc_attr__('Describe your resume in a few paragraphs','noo')?>"><?php echo ($resume ? $resume->post_content : '')?></textarea>
			</div>
		</div>
	</div>
</div>
<?php do_action('noo_post_resume_general_after'); ?>