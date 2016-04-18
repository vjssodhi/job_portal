<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />

<!-- Favicon-->
<?php 
	$favicon = noo_get_image_option('noo_custom_favicon', '');
	if ($favicon != ''): ?>
	<link rel="shortcut icon" href="<?php echo esc_url($favicon); ?>" />
<?php
endif; ?>
<?php wp_head(); ?>

<!--[if lt IE 9]>
<script src="<?php echo NOO_FRAMEWORK_URI . '/vendor/respond.min.js'; ?>"></script>
<![endif]-->
</head>

<body <?php body_class(); ?>>
	<div class="<?php noo_site_class( 'site' ); ?>" <?php noo_site_schema(); ?> >
	<?php
	$rev_slider_pos = home_slider_position(); ?>
	<?php
		if($rev_slider_pos == 'above') {
			noo_get_layout( 'slider-revolution');
		}
	?>
	<header class="noo-header <?php noo_header_class(); ?>" id="noo-header" <?php noo_header_schema(); ?>>
		<?php noo_get_layout('topbar'); ?>
		<?php noo_get_layout('navbar'); ?>
	</header>

	<?php
		if($rev_slider_pos == 'below') {
			noo_get_layout( 'slider-revolution');
		}
	?>

	<?php noo_get_layout('heading'); ?>