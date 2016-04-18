<?php
/*
Template Name: Add User
*/
if ( empty( $_POST[ 'action' ] ) ) {
	if ( empty( $_GET[ 'action' ] ) ) {
		$GLOBALS[ 'action' ] = '';
	} else {
		$GLOBALS[ 'action' ] = $_GET[ 'action' ];
	}
} else {
	$GLOBALS[ 'action' ] = $_POST[ 'action' ];
}

$steps = array(
	'login' => 1,
	'resume_general' => 2,
	'resume_detail' => 3,
	'resume_preview' => 4,
	);
$enable_resume_detail = Noo_Resume::enable_resume_detail();
if( !$enable_resume_detail ) {
	unset( $steps['resume_detail'] );
	$steps['resume_preview'] = 3;
}
$step_completed = '<i class="fa fa-check"></i>';
$current_step = 1;
$step_content='';
switch ($action){
	case 'register';
	case 'login':
		Noo_Resume::login_handler();
		$layout='login_register';
	break;
	case 'resume_general':
		Noo_Form_Handler::post_resume_action();
		if(!Noo_Member::is_logged_in()){
			wp_safe_redirect(esc_url_raw(add_query_arg( 'action', 'login',get_permalink())));
			exit;
		} elseif( !Noo_Member::is_candidate() ) {
			wp_safe_redirect(home_url('/'));
			exit;
		}
		foreach ($steps as $key => $step) {
		 	if( $key == 'resume_general' ) break;
		 	$steps[$key] = $step_completed;
		}
		$layout = 'resume_general';
		ob_start();
		?>
		<div id="step_content_form" class="jstep-content">
			<div class="jpanel jpanel-resume-form">
				<div class="jpanel-title">
					<h3><?php _e('General Information','noo')?></h3>
				</div>
				<div class="jpanel-body">
					<?php noo_get_layout('resume_candidate_profile'); ?>
					<?php noo_get_layout('resume_general')?>
				</div>
			</div>
			<div class="form-actions form-group text-center clearfix">
			 	<button type="submit" class="btn btn-primary"><?php echo ( $enable_resume_detail ? __('Continue','noo') : __('Preview','noo') ); ?></button>
		 	</div>
		</div>
		<?php
		$step_content = ob_get_clean();
	break;
	case 'resume_detail':
		Noo_Form_Handler::post_resume_action();
		if(!Noo_Member::is_logged_in()){
			wp_safe_redirect(esc_url_raw(add_query_arg( 'action', 'login',get_permalink())));
			exit;
		} elseif( !Noo_Member::is_candidate() ) {
			wp_safe_redirect(home_url('/'));
			exit;
		}
		foreach ($steps as $key => $step) {
		 	if( $key == 'resume_detail' ) break;
		 	$steps[$key] = $step_completed;
		}
		$layout = 'resume_detail';
		ob_start();
		?>
		<div id="step_content_form" class="jstep-content">
			<div class="jpanel jpanel-resume-form">
				<div class="jpanel-title">
					<h3><?php _e('Resume Detail','noo')?></h3>
				</div>
				<div class="jpanel-body">
					<?php noo_get_layout('resume_detail');?>
				</div>
			</div>
			<div class="form-actions form-group text-center clearfix">
				<a type="button" class="btn btn-primary" href="<?php echo esc_url(add_query_arg('action','resume_general'))?>"><?php _e('Back','noo')?></a>
		 		<button type="submit" class="btn btn-primary"><?php _e('Preview','noo')?></button>
		 	</div>
		</div>
		<?php
		$step_content = ob_get_clean();
	break;
	case 'resume_preview':
		Noo_Form_Handler::preview_resume_action();
		if(!Noo_Member::is_logged_in()){
			wp_safe_redirect(esc_url_raw(add_query_arg( 'action', 'login',get_permalink())));
			exit;
		} elseif( !Noo_Member::is_candidate() ) {
			wp_safe_redirect(home_url('/'));
			exit;
		}
		foreach ($steps as $key => $step) {
		 	if( $key == 'resume_preview' ) break;
		 	$steps[$key] = $step_completed;
		}
		$layout = 'resume_preview';
		ob_start();
		?>
		<div id="step_content_form" class="jstep-content">
			<div class="jpanel jpanel-resume-form">
				<div class="jpanel-title">
					<h3><?php _e('Preview and Finish','noo')?></h3>
				</div>
				<div class="jpanel-body">
					<?php do_action('noo_resume_preview_before', $action); ?>
					<?php noo_get_layout('resume_preview')?>
					<?php do_action('noo_resume_preview_after', $action); ?>
				</div>
			</div>
		</div>
		<?php
		$step_content = ob_get_clean();
	break;
	default:
		if(!Noo_Member::is_logged_in()){
			if($action != 'register')
				wp_safe_redirect(esc_url_raw(add_query_arg( 'action', 'login')));
		}elseif(!Noo_Member::is_candidate()) {
			wp_safe_redirect(home_url('/'));
			exit;
		}else{
			wp_safe_redirect(esc_url_raw(add_query_arg( 'action', 'resume_general' )));
		}
	break;
}
?>
<?php get_header(); ?>
<div class="container-wrap">	
	<div class="main-content container-fullwidth">
		<div class="row">
			<div class="<?php noo_main_class(); ?>" role="main">
				<div class="jform">
					<div class="jform-header">
						<div class="container-boxed max">
							<div class="jform-steps">
								<ul class="jsteps jsteps-<?php echo esc_attr(count($steps)) ?>">
									<li data-layout="login_register"  data-step="1" class="<?php if($action == 'login'){?> active<?php }?><?php if($steps['login'] == $step_completed){?> completed<?php }?>">
										<span class="jstep-num">
											<a href="javascript:void(0);"><?php echo wp_kses_post($steps['login'])?></a>
										</span>
										<div class="jstep-line">
											<span class="jstep-dot"></span>
										</div>
										<div class="jstep-label"><?php echo Noo_Member::can_register() ? __('Login or create an account','noo') : __('Login', 'noo'); ?></div>
									</li>
									<li  data-layout="form" data-form-part="general"  data-step="2" class="<?php if($action == 'resume_general' || $action == 'resume_preview'){?> active<?php }?><?php if($steps['resume_general'] == $step_completed){?> completed<?php }?>">
										<span class="jstep-num">
											<?php 
											if($action == 'resume_detail' || $action == 'resume_preview'){
												$postresume_location_query = array('action'=>'resume_general');
												$resume_id = isset($_GET['resume_id']) ? absint($_GET['resume_id']) : 0;
												if($resume_id)
													$postresume_location_query['resume_id'] = $resume_id;
												$postresume_location = esc_url(add_query_arg($postresume_location_query));
											}else{
												$postresume_location='javascript:void(0);';
											}
											?>
											<a href="<?php echo ($postresume_location)?>"><?php echo wp_kses_post($steps['resume_general'])?></a>
										</span>
										<div class="jstep-line">
											<span class="jstep-dot"></span>
										</div>
										<div class="jstep-label"><?php _e('General Information','noo')?></div>
									</li>
									<?php if( $enable_resume_detail ) : ?>
									<li  data-layout="form" data-form-part="detail"  data-step="3" class="<?php if($action == 'resume_detail'){?> active<?php }?><?php if($steps['resume_detail'] == $step_completed){?> completed<?php }?>">
										<span class="jstep-num">
											<?php 
											if($action == 'resume_preview'){
												$postresume_location_query = array('action'=>'resume_detail');
												$resume_id = isset($_GET['resume_id']) ? absint($_GET['resume_id']) : 0;
												if($resume_id)
													$postresume_location_query['resume_id'] = $resume_id;
												$postresume_location = esc_url(add_query_arg($postresume_location_query));
											}else{
												$postresume_location='javascript:void(0);';
											}
											?>
											<a href="<?php echo ($postresume_location)?>"><?php echo wp_kses_post($steps['resume_detail'])?></a>
										</span>
										<div class="jstep-line">
											<span class="jstep-dot"></span>
										</div>
										<div class="jstep-label"><?php _e('User Detail','noo')?></div>
									</li>
									<?php endif; ?>
									<li data-layout="preview"  data-step="4" class="<?php if($action == 'resume_preview'){?> active<?php }?><?php if($steps['resume_preview'] == $step_completed){?> completed<?php }?>">
										<span class="jstep-num">
											<a href="javascript:void(0);"><?php echo wp_kses_post($steps['resume_preview'])?></a>
										</span>
										<div class="jstep-line">
											<span class="jstep-dot"></span>
										</div>
										<div class="jstep-label"><?php _e('Preview and Finish','noo')?></div>
									</li>
								</ul>
							</div>
						</div>
					</div>
					<div class="jform-body">
						<div class="container-boxed max">
							<?php if($action=='login' || $action == 'register'):?>
								<div id="step_content_login" class="jstep-content" style="display: block;">
									<?php noo_get_layout('login_register')?>
								</div>
							<?php else:?>
								<form id="post_resume_form" class="form-horizontal" autocomplete="on" method="post" novalidate="novalidate">
									<div style="display: none;">
										<input type="hidden" name="action" id="hiddenaction" value="<?php echo esc_attr($action)?>">
										<input type="hidden" name="page_id" value="<?php echo get_the_ID()?>">
										<input type="hidden" name="resume_id" value="<?php echo isset($_GET['resume_id']) ? absint($_GET['resume_id']) : 0?>">
										<input type="hidden" name="candidate_id" value="<?php echo get_current_user_id();?>">
										<?php wp_nonce_field('noo-post-resume')?>
									</div>
									<?php echo ($step_content);?>
								</form>
							<?php endif;?>
						</div>
					</div>
				</div>
			</div> <!-- /.main -->
		</div><!--/.row-->
	</div><!--/.container-full-->
</div><!--/.container-wrap-->
	
<?php get_footer(); ?>