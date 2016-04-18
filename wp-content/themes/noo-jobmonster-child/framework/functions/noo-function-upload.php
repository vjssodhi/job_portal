<?php
/* -------------------------------------------------------
 * Create functions show_list_image_upload
 * ------------------------------------------------------- */

if ( ! function_exists( 'show_list_image_upload' ) ) :
	
	function show_list_image_upload( $thumb_id, $input_name ) {
		if( !empty($thumb_id) ) :
			// $list_img = explode( ',', $thumb_id );
			if ( is_array( $thumb_id ) ) :
				foreach ( $thumb_id as $img ) :
					$img = trim($img);
					if( !empty($img) ) :
						$img_src = wp_get_attachment_image_src( $img, 'thumbnail' );
						echo "<img src='{$img_src[0]}' alt='*' />";
						echo '<input type="hidden" name="' . $input_name . '[]" value="' . $img . '" />';
					endif;
				endforeach;
			else :
				$img_src = is_numeric( $thumb_id ) ? wp_get_attachment_image_src( $thumb_id, 'thumbnail' ) : '';
				$img_src = !empty( $img_src ) ? $img_src[0] : $thumb_id;
				echo "<img src='{$img_src}' alt='*' />";
				echo '<input type="hidden" name="' . $input_name . '" value="' . $thumb_id . '" />';
			endif;
		endif;

	}

endif;

/** ====== END show_list_image_upload ====== **/