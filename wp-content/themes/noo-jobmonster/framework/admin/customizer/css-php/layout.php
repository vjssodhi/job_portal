<?php

// Variables
$default_bg_color = '#ffffff';

$noo_site_layout = noo_get_option( 'noo_site_layout', 'fullwidth' );
$noo_layout_site_width = noo_get_option( 'noo_layout_site_width', '90' );
$noo_layout_site_max_width = noo_get_option( 'noo_layout_site_max_width', '1200' );
$noo_layout_bg_color = noo_get_option( 'noo_layout_bg_color', $default_bg_color );
$noo_layout_bg_image = noo_get_image_option( 'noo_layout_bg_image', '' );
$noo_layout_bg_repeat = noo_get_option( 'noo_layout_bg_repeat', 'no-repeat' );
$noo_layout_bg_align = noo_get_option( 'noo_layout_bg_align', 'left top' );
$noo_layout_bg_attachment = noo_get_option( 'noo_layout_bg_attachment', 'fixed' );
$noo_layout_bg_cover = noo_get_option( 'noo_layout_bg_cover', true );

?>

<?php if( $noo_site_layout == 'boxed' ) : ?>
	body.boxed-layout {
		background-color: <?php echo esc_html($noo_layout_bg_color); ?>;
		<?php if( $noo_layout_bg_image != '' ) : ?>
			background-image: url("<?php echo esc_html($noo_layout_bg_image); ?>");
		<?php endif; ?>
		background-repeat: <?php echo ( !empty( $noo_layout_bg_repeat ) ? $noo_layout_bg_repeat : 'no-repeat' ); ?>;
		background-position: <?php echo esc_html($noo_layout_bg_align); ?>;
		background-attachment: <?php echo esc_html($noo_layout_bg_attachment); ?>;
		<?php if( $noo_layout_bg_cover ) echo '-webkit-background-size: cover;-moz-background-size: cover;-o-background-size: cover;background-size: cover;'; ?>
	}
<?php endif; ?>

<?php if( $noo_site_layout == 'boxed' ) : ?>
	body.boxed-layout .site,
	body.boxed-layout .navbar-fixed-top,
	body.boxed-layout .navbar-fixed-bottom,
	body.boxed-layout .navbar.floating {
		width: <?php echo esc_html($noo_layout_site_width); ?>%;
		max-width: <?php echo esc_html($noo_layout_site_max_width) . 'px'; ?>;
	}
<?php endif; ?>
