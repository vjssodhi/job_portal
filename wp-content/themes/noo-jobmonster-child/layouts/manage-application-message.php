<?php
$employer_message_title = noo_get_post_meta($application_id, '_employer_message_title', '');
$employer_message_body = noo_get_post_meta($application_id, '_employer_message_body', '');
$mesage_excerpt = empty($employer_message_title) ? wp_trim_words( $employer_message_body, 10 ) : $employer_message_title;
$mesage_excerpt = !empty($mesage_excerpt) ? $mesage_excerpt . __('...', 'noo') : '';
						
?>
<div id="employerMsgModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="employerMsgModalLabel" aria-hidden="false" style="display: block;">
	<div class="modal-dialog">
    	<div class="modal-content">
    		<div class="modal-header">
    			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    			<h4 class="modal-title text-center" id="employerMsgModalLabel">
    				<?php 
    				if($mode):
    					_e('Candidate\'s Message','noo');
    				else:
    					_e('Employer\'s Message', 'noo');
    				endif; ?>
    			</h4>
    		</div>
    		<div class="modal-body">
    			<?php if($mode):?>
    			<div class="candidate-message clearfix">
    				<p class="employer_title"><?php echo esc_html( $application->post_content );?></p>
    			</div>
    			<?php else:?>
    			<div class="row">
	    			<div class="loop-item-wrap col-xs-9">
					    <div class="item-featured">
							<a href="<?php echo get_permalink( $job->ID ); ?>">
								<?php echo $logo_company;?>
							</a>
						</div>
						
						<div class="loop-item-content">
							<h2 class="loop-item-title">
								<a href="<?php echo get_permalink( $job->ID ); ?>" title="<?php echo esc_attr( sprintf( __( 'Permanent link to: "%s"','noo' ), the_title_attribute( 'echo=0' ) ) ); ?>"><?php echo get_the_title( $job->ID ); ?></a>
							</h2>
							<?php Noo_Job::noo_contentjob_meta(array('job_id'=>$job->ID,'show_date'=>false,'show_category'=>true));?>
						</div>
					</div>
	    			<div class="application-status col-xs-3">
	    				<?php 
						$status   = $application->post_status;
						$statuses = Noo_Application::get_application_status();
						$status_class = $status;
						if ( isset( $statuses[ $status ] ) ) {
							$status = $statuses[ $status ];
						} else {
							$status = __( 'Inactive', 'noo' );
							$status_class = 'inactive';
						}
						?>
						<span class="job-application-status job-application-status-<?php echo sanitize_html_class($status_class) ?>">
						<?php echo esc_html($status)?>
						</span>
	    			</div>
				</div>
    			<hr/>
    			<div class="employer-message clearfix">
    				<strong class="employer_title"><?php echo esc_html( $employer_message_title );?></strong>
    				<p class="employer_title" style="overflow: auto;"><?php echo esc_html( $employer_message_body );?></p>
    			</div>
    			<?php endif;?>
    		</div>
		</div>
	</div>
</div>
