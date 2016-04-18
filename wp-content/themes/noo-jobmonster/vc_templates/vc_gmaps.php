<?php
extract(shortcode_atts(array(
    'visibility'      => 'all',
    'class'           => '',
    'id'              => '',
    'custom_style'    => '',
    'link' => '<iframe src="https://mapsengine.google.com/map/u/0/embed?mid=z4vjH8i214vQ.kj0Xiukzzle4" width="640" height="480"></iframe>',
    'size'            => '',
    'disable_zooming' => ''
), $atts));
if ( $link == '' ) { return null; }

$class         = ( $class      != '' ) ? 'noo-gmaps ' . esc_attr( $class ) : 'noo-gmaps';
$visibility    = ( $visibility != '' ) && ( $visibility != 'all' ) ? esc_attr( $visibility ) : '';
$class        .= noo_visibility_class( $visibility );

$id           = ( $id != '' ) ? 'id="' . esc_attr( $id ) . '"' : '';
$class        = ( $class != '' ) ? 'class="' . $class . '"' : '';
$custom_style = ($custom_style != '' ) ? 'style="' . $custom_style . '"' : '';
$link         = trim(vc_value_from_safe($link));
$link         = preg_replace('/height="[0-9]*"/', 'height="'.$size.'"', $link);
// print_r($link );
?>
<div <?php echo ($id . ' ' . $class . ' ' . $custom_style); ?>>
    <?php
    if( $disable_zooming == 'true' ) {
        echo '<div class="map-overlay"></div>';
    }
	if (preg_match('/^\<iframe/', $link) ) {
        echo $link;
    }else echo '<iframe style="width: 100%; margin: 0; border: 0" size="' . $size . '" scrolling="no" src="'.$link.'&amp;t=m&amp;z=14&amp;output=embed"></iframe>';
	?>
</div>