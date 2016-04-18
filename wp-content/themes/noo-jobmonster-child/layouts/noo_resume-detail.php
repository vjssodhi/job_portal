<?php
wp_enqueue_script('noo-timeline-vendor');
wp_enqueue_script('noo-timeline');

$enable_education = Noo_Resume::get_setting('enable_education', '1');
$enable_experience = Noo_Resume::get_setting('enable_experience', '1');
$enable_skill = Noo_Resume::get_setting('enable_skill', '1');
$enable_video = (bool) Noo_Resume::get_setting('enable_video', '');
$display_video_resume = Noo_Resume::get_setting('display_video_resume', 'before_content');
while ($query->have_posts()): $query->the_post(); global $post;

	if( Noo_Resume::can_view_resume( $post->ID ) ) :
		$resume_id = $post->ID;
		$default_fields = Noo_Resume::get_default_fields();
		$custom_fields = noo_get_custom_fields( get_option( 'noo_resume' ), 'noo_resume_custom_fields_' );
		$fields = array_merge( array_diff_key($default_fields, $custom_fields), $custom_fields );

		$education					= array();
		if( $enable_education ) {
			$education['school']		= noo_json_decode( noo_get_post_meta( $resume_id, '_education_school' ) );
			$education['qualification']	= noo_json_decode( noo_get_post_meta( $resume_id, '_education_qualification' ) );
			$education['date']			= noo_json_decode( noo_get_post_meta( $resume_id, '_education_date' ) );
			$education['note']			= noo_json_decode( noo_get_post_meta( $resume_id, '_education_note' ) );
		}

		$experience					= array();
		if( $enable_experience ) {
			$experience['employer']		= noo_json_decode( noo_get_post_meta( $resume_id, '_experience_employer' ) );
			$experience['job']			= noo_json_decode( noo_get_post_meta( $resume_id, '_experience_job' ) );
			$experience['date']			= noo_json_decode( noo_get_post_meta( $resume_id, '_experience_date' ) );
			$experience['note']			= noo_json_decode( noo_get_post_meta( $resume_id, '_experience_note' ) );
		}

		$skill						= array();
		if( $enable_skill ) {
			$skill['name']				= noo_json_decode( noo_get_post_meta( $resume_id, '_skill_name' ) );
			$skill['percent']			= noo_json_decode( noo_get_post_meta( $resume_id, '_skill_percent' ) );
		}
?>
		<article id="post-<?php the_ID(); ?>" class="resume">
			<?php if( !isset( $hide_profile ) || empty( $hide_profile ) ) noo_get_layout('resume_candidate_profile'); ?>
			<div class="resume-content">
				<div class="row">
					<div class="col-md-12">
						<div class="resume-desc">
							<div class="resume-general row">
								<div class="col-sm-3">
								<h3 class="title-general">
								<span><?php _e('General Infomation','noo');?></span>
								</h3>										
								</div>
								<div class="col-sm-9">
									<ul>
										<?php
										if($fields) : foreach ($fields as $field) : 
											if( !isset( $field['name'] ) || empty( $field['name'] )) continue;
											$field['type'] = isset( $field['type'] ) ? $field['type'] : 'text';
											$id = '_noo_resume_field_'.sanitize_title($field['name']);
											if( array_key_exists($field['name'], $default_fields) ) {
												if( isset( $field['is_disabled'] ) && ($field['is_disabled'] == 'yes') )
													continue;
												$id = $field['name'];
											}
											$value = $resume_id ? noo_get_post_meta($resume_id, $id, '') : '';
											$label = isset( $field['label_translated'] ) ? $field['label_translated'] : $field['label'];
											if( $id == '_job_category' ) {
												if( !empty( $value ) ) {
													$value = noo_json_decode($value);
													$category_terms = empty( $value ) ? array() : get_terms( 'job_category', array('include' => array_merge($value, array(0)), 'hide_empty' => 0, 'fields' => 'names') );
													$value = implode(', ', $category_terms);
												}
											} elseif( $id == '_job_location' ) {
												if( !empty( $value ) ) {
													$value = noo_json_decode($value);
													$location_terms = empty( $value ) ? array() : get_terms( 'job_location', array('include' => array_merge($value, array(0)), 'hide_empty' => 0, 'fields' => 'names') );
													$value = implode(', ', $location_terms);
												}
											} else {
												$value = !is_array($value) ? trim($value) : $value;
												if( empty( $value ) ) continue;

												if ( $field['type'] === 'checkbox' || $field['type'] === 'radio' || $field['type'] === 'multiple_select' ) : 
													$value = !is_array( $value ) ? array( $value ) : $value;
													$value = implode(', ', $value);
												endif;
											}

											if( empty($value) ) continue;
											?>
											<li>
												<span class="label-<?php echo $id; ?>"><?php esc_html_e($label)?></span>
													<?php echo esc_html( $value ); ?>
											</li>

										<?php endforeach; endif; ?>
									</ul>
								</div>
								<div class="resume-description col-sm-12">
									<?php 
										$url_video = $enable_video ? noo_get_post_meta($post->ID, '_noo_url_video') : '';
										if ( $display_video_resume == 'after_content' ) the_content();
										if ( !empty( $url_video ) ) :
											global $wp_embed;
											?>
											<div class="resume-video">
												<?php echo $wp_embed->run_shortcode( "[embed width='800']{$url_video}[/embed]" );  ?>
											</div>
											<?php
										endif;
										if ( $display_video_resume == 'before_content' ) the_content();
									?>
								</div>
							</div>
							<?php if( $enable_education ) : ?>
								<?php if( isset( $education['school'] ) && !empty( $education['school'] ) ) : ?>
							<div class="resume-timeline row">
								<div class="col-md-3 col-sm-12">
									<h3 class="title-general">
										<span><?php _e('Education','noo');?></span>
									</h3>
								</div>
								<div class="col-md-9 col-sm-12">
									<div id="education-timeline" class="timeline-container education">
											<?php $education_count = count( $education['school'] );
											for( $index = 0; $index < $education_count; $index++ ) :
												if( empty( $education['school'][$index] ) ) continue;
												?>
												<div class="timeline-wrapper <?php echo ( $index == ( $education_count - 1 ) ) ? 'last' : ''; ?>">
													<div class="timeline-time"><span><?php esc_attr_e( $education['date'][$index] ); ?></span></div>
													<dl class="timeline-series">
														<span class="tick tick-before"></span>
														<dt id="<?php echo 'education'.$index ?>" class="timeline-event"><a><?php esc_attr_e( $education['school'][$index] ); ?><span><?php esc_attr_e( $education['qualification'][$index] ); ?></span></a></dt>
														<span class="tick tick-after"></span>
														<dd class="timeline-event-content" id="<?php echo 'education'.$index.'EX' ?>">
															<div><?php echo wpautop( html_entity_decode( $education['note'][$index] ) ); ?></div>
														<br class="clear">
														</dd><!-- /.timeline-event-content -->
													</dl><!-- /.timeline-series -->
												</div><!-- /.timeline-wrapper -->
											<?php endfor; ?>
									</div>
								</div>
							</div>
								<?php endif; ?>
							<?php endif; ?>
							<?php if( $enable_experience ) : ?>
								<?php if( isset( $experience['employer'] ) && !empty( $experience['employer'] )) : ?>
							<div class="resume-timeline row">
								<div class="col-md-3 col-sm-12">
									<h3 class="title-general">
										<span><?php _e('Work Experience','noo');?></span>
									</h3>
								</div>
								<div class="col-md-9 col-sm-12">
									<div id="experience-timeline" class="timeline-container experience">
										<?php $experience_count = count( $experience['employer'] );
											for( $index = 0; $index < $experience_count; $index++ ) : 
												if( empty( $experience['employer'][$index] ) ) continue;
												?>
												<div class="timeline-wrapper <?php echo ( $index == ( $experience_count - 1 ) ) ? 'last' : ''; ?>">
													<div class="timeline-time"><span><?php esc_attr_e( $experience['date'][$index] ); ?></span></div>
													<dl class="timeline-series">
														<span class="tick tick-before"></span>
														<dt id="<?php echo 'experience'.$index ?>" class="timeline-event"><a><?php esc_attr_e( $experience['employer'][$index] ); ?><span class="tick tick-after"><?php esc_attr_e( $experience['job'][$index] ); ?></span></a></dt>
														
														<dd class="timeline-event-content" id="<?php echo 'experience'.$index.'EX' ?>">
															<div><?php echo wpautop( html_entity_decode( $experience['note'][$index] ) ); ?></div>
														<br class="clear">
														</dd><!-- /.timeline-event-content -->
													</dl><!-- /.timeline-series -->
												</div><!-- /.timeline-wrapper -->
										<?php endfor; ?>
									</div>
								</div>
							</div>
								<?php endif; ?>
							<?php endif; ?>
							<?php if( $enable_skill ) : ?>
								<?php if( isset( $skill['name'] ) && !empty( $skill['name'] ) ) : ?>
							<div class="resume-timeline row">
								<div class="col-md-3 col-sm-12">
									<h3 class="title-general">
										<span><?php _e('Summary of Skills','noo');?></span>
									</h3>
								</div>
								<div class="col-md-9 col-sm-12">
									<div id="skill" class="skill">
										<?php $skill_count = count( $skill['name'] );
											for( $index = 0; $index < $skill_count; $index++ ) : 
												if( empty( $skill['name'][$index] ) ) continue;
												$skill_value = min( intval( $skill['percent'][$index] ), 100 );
												$skill_value = max( $skill_value, 0 );
												?>
											<div class="pregress-bar clearfix">
												<div class="progress_title"><span><?php esc_attr_e( $skill['name'][$index] ); ?></span></div>
												<div class="progress">
													<div aria-valuemax="100" aria-valuemin="0" aria-valuenow="70" class="progress-bar progress-bar-bg" data-valuenow="<?php esc_attr_e( $skill_value ); ?>" role="progressbar" style="width: <?php esc_attr_e( $skill_value ); ?>%;">
														<div class="progress_label" style="opacity: 1;"><span><?php esc_attr_e( $skill_value ); ?></span><?php _e('%', 'noo'); ?></div>
													</div>
												</div>
											</div>
										<?php endfor; ?>
									</div>
								</div>
							</div>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</article> <!-- /#post- -->
	<?php else: ?>
		<?php
			list($title, $link) = Noo_Resume::get_resume_permission_message();
		?>
		<article id="post-<?php the_ID(); ?>" class="resume">
			<h3><?php echo $title; ?></h3>
			<?php if( !empty( $link ) ) echo $link; ?>
		</article>
	<?php endif; ?>
<?php
endwhile;
?>
<script>
	jQuery(document).ready(function() {
		jQuery.timeliner({
			timelineContainer:'.resume-timeline .timeline-container',
		});
		jQuery('.venobox').venobox();
	});
	// $(document).ready(function() {
	// 	$.timeliner({
	// 		timelineContainer:'#timeline',
	// 	});
	// 	$('.venobox').venobox();
	// });

</script>