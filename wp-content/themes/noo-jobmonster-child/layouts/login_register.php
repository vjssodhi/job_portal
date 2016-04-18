<?php 
$form = '';
$can_register = Noo_Member::can_register();

if($can_register && isset($_GET['action']) && $_GET['action'] === 'register'){
	$form = 'register';
}else{
	$form = 'login';
}

?>
<div class="jpanel jpanel-login">
	<div class="jpanel-title">
		<h3><?php echo $can_register ? __('Login or create an account','noo') : __('Login','noo');?></h3>
	</div>
	<div class="jpanel-body">
		<div class="account-actions">
			<?php if($can_register):?>
			<a href="<?php echo esc_url(add_query_arg('action','register'))?>" class="btn btn-default jbtn-register<?php echo 'register' == $form ? ' active':''?>"><?php _e('Register','noo')?></a>
			<a href="<?php echo esc_url(add_query_arg('action','login'))?>" class="btn btn-default jbtn-login<?php echo 'login' == $form ? ' active':''?>"><span class="hidden-xs"><?php _e('Already have account ? ','noo')?></span><span class="login-label"><?php _e('Login','noo')?></span></a>
			<?php endif;?>
		</div>
		<div class="account-form">
			<?php if('login' == $form):?>
			<div class="account-log-form">
				<?php Noo_Member::ajax_login_form(__('Continue','noo'));?>
			</div>
			<?php endif;?>
			<?php if('register' == $form):?>
			<div class="account-reg-form">
				<?php Noo_Member::ajax_register_form(__('Continue','noo'))?>
			</div>
			<?php endif;?>
		</div>
	</div>
</div>
