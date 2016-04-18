<?php

function noo_upload_form($images = '', $featured_img = '', $is_gallery = false) {
	wp_localize_script('noo-img-uploader', 'noo_img_upload', array(
		'ajaxurl'        => admin_url('admin-ajax.php'),
		'nonce'          => wp_create_nonce('aaiu_upload'),
		'remove'         => wp_create_nonce('aaiu_remove'),
		'max_files'      => 0,
		'upload_enabled' => true,
		'confirmMsg'     => __('Are you sure you want to delete this?', 'noo'),
		'plupload'       => array(
			'runtimes'         => 'html5,flash,html4',
			'browse_button'    => array('aaiu-uploader', 'cover-upload', 'upload_cover_image'),
			'container'        => 'aaiu-upload-container',
			'file_data_name'   => 'aaiu_upload_file',
			'max_file_size'    => (100 * 1000 * 1000) . 'b',
			'url'              => admin_url('admin-ajax.php') . '?action=noo_upload&nonce=' . wp_create_nonce('aaiu_allow'),
			'flash_swf_url'    => includes_url('js/plupload/plupload.flash.swf'),
			'filters'          => array(array('title' => __('Allowed Files', 'noo'), 'extensions' => 'jpg,gif,png')),
			'multipart'        => true,
			'urlstream_upload' => true,
			'accept'           => 'image/*'
		)
	));
	wp_enqueue_script('noo-img-uploader');
	?>
	<div id="aaiu-upload-imagelist">
		<ul id="aaiu-ul-list" class="aaiu-upload-list"></ul>
	</div>
	<div id="uploaded-images">
		<?php
		if( !empty($featured_img) ) :
			$featured_img_src = wp_get_attachment_image_src($featured_img, 'property-thumbnail');
			if( $featured_img_src ) :
			?>
		<div class="uploaded-img" data-imageid="<?php echo esc_attr($featured_img); ?>">
			<img class="" src="<?php echo esc_url($featured_img_src[0]); ?>"/>
			<a href="javascript:void(0)" class="remove-img">
				<i class="action-remove fa fa-trash-o"></i>
			</a>
			<i class="featured-img fa fa-star"></i>
		</div>
		<?php
			endif;
		endif;

		if(!empty($images)) :
			$images_arr = explode(',', $images);
		foreach ($images_arr as $img_id) :
			$img_id = trim($img_id);
			if( empty($img_id) ) continue;
			$img_src = is_numeric( $img_id ) ? wp_get_attachment_image_src($img_id, 'property-thumbnail') : $img_id;
			if( !$img_src ) continue;
		?>
			<div class="uploaded-img" data-imageid="<?php echo esc_attr($img_id); ?>">
				<img class="" src="<?php echo esc_url($img_src[0]); ?>">
				<?php if( $is_gallery ) : ?>
				<a href="javascript:void(0)" class="remove-img">
					<i class="remove-img fa fa-trash-o"></i>
				</a>
				<?php endif; ?>
			</div>
			<?php
			endforeach;
		endif;
		?>
	</div>
	<?php
}

function noo_upload()
{
	check_ajax_referer('aaiu_allow', 'nonce');

	$file = array(
		'name' => $_FILES['aaiu_upload_file']['name'],
		'type' => $_FILES['aaiu_upload_file']['type'],
		'tmp_name' => $_FILES['aaiu_upload_file']['tmp_name'],
		'error' => $_FILES['aaiu_upload_file']['error'],
		'size' => $_FILES['aaiu_upload_file']['size'],
		);
	$file = noo_fileupload_process($file);
}

function noo_fileupload_process($file)
{
	$attachment = noo_handle_file($file);
	if (is_array($attachment)) {
		$file = explode('/', $attachment['data']['file']);
		$file = array_slice($file, 0, count($file) - 1);
		$path = implode('/', $file);

		$dir = wp_upload_dir();
		$path = $dir['baseurl'] . '/' . $path;
		$thumbnail = '';
		$image = '';

		if( isset( $attachment['data']['sizes']['property-infobox'] ) ) {
			$thumbnail = $path . '/' . $attachment['data']['sizes']['property-infobox']['file'];
		} else {
			$thumbnail = $dir['baseurl'] . '/' . $attachment['data']['file'];
		}

		if( isset( $attachment['data']['sizes']['property-image'] ) ) {
			$image = $path . '/' . $attachment['data']['sizes']['property-image']['file'];
		} else {
			$image = $dir['baseurl'] . '/' . $attachment['data']['file'];
		}

		$response = array(
			'success' => true,
			'image' => $image,
			'thumbnail' => $thumbnail,
			'image_id' => $attachment['id']
			);

		echo json_encode($response);
		exit;
	}

	$response = array('success' => false);
	echo json_encode($response);
	exit;
}

function noo_handle_file($upload_data)
{

	$return = false;
	$uploaded_file = wp_handle_upload($upload_data, array('test_form' => false));

	if (isset($uploaded_file['file'])) {
		$file_loc = $uploaded_file['file'];
		$file_name = basename($upload_data['name']);
		$file_type = wp_check_filetype($file_name);

		$attachment = array(
			'post_mime_type' => $file_type['type'],
			'post_title' => preg_replace('/\.[^.]+$/', '', basename($file_name)),
			'post_content' => '',
			'post_status' => 'inherit'
			);

		$attach_id = wp_insert_attachment($attachment, $file_loc);
		$attach_data = wp_generate_attachment_metadata($attach_id, $file_loc);
		wp_update_attachment_metadata($attach_id, $attach_data);

		$return = array('data' => $attach_data, 'id' => $attach_id);

		return $return;
	}

	return $return;
}

function noo_getHTML($attachment)
{
	$file = explode('/', $attachment['data']['file']);
	$file = array_slice($file, 0, count($file) - 1);
	$path = implode('/', $file);

	$dir = wp_upload_dir();
	$path = $dir['baseurl'] . '/' . $path;

	if( is_page_template('agent_dashboard_submit.php') ) {
		$image = $attachment['data']['sizes']['property-thumbnail']['file'] . 3;
	} else {
		$image = $attachment['data']['sizes']['property-image']['file'] . get_page_template();
	}

	$html = $path . '/' . $image;

	return $html;
}

function noo_delete_file()
{
	check_ajax_referer('aaiu_remove', 'nonce');

	$attach_id = $_POST['attach_id'];

	wp_delete_attachment($attach_id, true);
	exit;
}

add_action('wp_ajax_noo_upload', 'noo_upload');
add_action('wp_ajax_noo_delete_file', 'noo_delete_file');
add_action('wp_ajax_nopriv_noo_upload', 'noo_upload');
add_action('wp_ajax_nopriv_noo_delete_file', 'noo_delete_file');

function noo_plupload_form($field_name='',$extensions='jpg,gif,png',$value=''){
	$js_folder_uri = SCRIPT_DEBUG ? NOO_ASSETS_URI . '/js' : NOO_ASSETS_URI . '/js/min';
	$js_suffix     = SCRIPT_DEBUG ? '' : '.min';
	wp_register_script( 'noo_plupload', $js_folder_uri . '/noo_plupload' . $js_suffix . '.js', array( 'jquery', 'plupload-all' ), null, true );

	$noo_plupload = array(
		'ajaxurl' => admin_url('admin-ajax.php'),
		'remove' => wp_create_nonce('noo-plupload-remove'),
		'confirmMsg' => __('Are you sure you want to delete this?', 'noo'),
	);
	wp_localize_script('noo_plupload', 'nooPluploadL10n', $noo_plupload);
	wp_enqueue_script('noo_plupload');
	
	$id = uniqid('plupload_');
	$plupload_init = array(
		'runtimes' => 'html5,flash,html4',
		'browse_button' => $id.'_uploader-btn',
		'container' => $id.'_upload-container',
		'file_data_name' => 'file',
		'max_file_size' => wp_max_upload_size(),
		'url' => admin_url('admin-ajax.php') . '?action=noo_plupload&nonce=' . wp_create_nonce('noo-plupload'),
		'flash_swf_url' => includes_url('js/plupload/plupload.flash.swf'),
		'filters' => array(array('title' => __('Allowed Files', 'noo'), 'extensions' => $extensions)),
		'multipart' => true,
		'urlstream_upload' => true,
		'multi_selection' => false
	);
	$plupload_init_json = htmlspecialchars(json_encode($plupload_init), ENT_QUOTES, 'UTF-8');
	?>
	<div id="<?php echo esc_attr($id.'_upload-container'); ?>" class="noo-plupload">
		<div class="noo-plupload-btn" data-settings="<?php echo esc_attr($plupload_init_json)?>">
			<a href="#" class="btn btn-default" id="<?php echo esc_attr($id.'_uploader-btn'); ?>"><i class="fa fa-folder-open-o"></i> <?php esc_html_e('Browse','noo')?></a>
			<?php 
	    	$max_upload_size = wp_max_upload_size();
	    	if ( ! $max_upload_size ) {
	    		$max_upload_size = 0;
	    	}
	    	?>
	    	<p class="help-block"><?php printf( __( 'Maximum upload file size: %s', 'noo' ), esc_html( size_format( $max_upload_size ) ) ); ?></p>
		</div>
		<div class="noo-plupload-preview">
			<?php if( !empty($value) ) : 
				$file_name = noo_json_decode( $value );
				$file_name = $file_name[0];
			?>
			<div>
				<a class="delete-pluploaded" data-filename="<?php echo esc_attr($file_name); ?>" href="#" title="<?php _e('Delete File', 'noo'); ?>"><i class="fa fa-times-circle" style="color:#f00"></i></a>
				<strong><?php echo esc_html($file_name); ?></strong>
			</div>
			<?php endif; ?>
		</div>
		<input type="hidden" class="noo-plupload-value" name="<?php echo esc_attr($field_name)?>" value="<?php echo esc_attr($value); ?>" >
	</div>
	<?php
}

/* -------------------------------------------------------
 * Create functions noo_get_file_upload
 $dir * ------------------------------------------------------- */

if ( ! function_exists( 'noo_get_file_upload' ) ) :
	
	function noo_get_file_upload( $filename = null, $folder = 'jobmonster' ) {
		if ( $filename == null ) return;
		$dir = wp_upload_dir();
		$target_dir = $dir['baseurl'] . '/' . $folder . '/';

		wp_mkdir_p($target_dir);
		$file_path = $target_dir . $filename;

		return $file_path;
	}

endif;

/** ====== END noo_get_file_upload  $dir ===== **/

function noo_plupload(){
	check_ajax_referer('noo-plupload', 'nonce');
	header('Content-Type: text/html; charset=' . get_option('blog_charset'));
	send_nosniff_header();
	nocache_headers();
	status_header(200);
	
	$dir = wp_upload_dir();
	
	$target_dir = $dir['basedir'].'/jobmonster/';
	
	wp_mkdir_p($target_dir);
	
	$cleanup_target_dir = true; // Remove old files
	$maxFileAge         = 5 * 3600; // Temp file age in seconds
	
	$chunk  = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
	$chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
	
	$file_name = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';
	$file_name = preg_replace('/[^\w\._]+/', '_', $file_name);
	
	$tmp_file_name = $file_name;
	$file_path = $target_dir . $tmp_file_name;
	
	if (file_exists($file_path)) {
		$count = 1;
		$new_path = $file_path;
		while (file_exists($new_path)) {
			$new_filename = $count++ . '_' . $file_name;
			$new_path = $target_dir . $new_filename;
		}
	
		$tmp_file_name = $new_filename;
	}
	
	$file_path = $target_dir . $tmp_file_name;
	
	// Remove old temp files
	if ($cleanup_target_dir) {
		if (is_dir($target_dir) && ($dir = opendir($target_dir))) {
			while (($file = readdir($dir)) !== false) {
				$tmp_file_path = $target_dir . $file;
	
				// Remove temp file if it is older than the max age and is not the current file
				if (preg_match('/\.part$/', $file) && (filemtime($tmp_file_path) < time() - $maxFileAge) && ($tmp_file_path != "{$file_path}.part")) {
					@unlink($tmp_file_path);
				}
			}
			closedir($dir);
		} else {
			die('{"status" : "error", "error" : {"code": 100, "message": "' . __("Failed to open temp directory.", 'noo') . '"}}');
		}
	}
	
	// Look for the content type header
	if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
		$contentType = $_SERVER["HTTP_CONTENT_TYPE"];
	
	if (isset($_SERVER["CONTENT_TYPE"]))
		$contentType = $_SERVER["CONTENT_TYPE"];
	
	// Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
	if (strpos($contentType, "multipart") !== false) {
		if (isset($_FILES["file"]['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
			// Open temp file
			$out = @fopen("{$file_path}.part", $chunk == 0 ? "wb" : "ab");
			if ($out) {
				// Read binary input stream and append it to temp file
				$in = @fopen($_FILES["file"]['tmp_name'], "rb");
	
				if ($in) {
					while ($buff = fread($in, 4096))
						fwrite($out, $buff);
				} else {
					die('{"status" : "error", "error" : {"code": 101, "message": "' . __("Failed to open input stream.",'noo') . '"}}');
				}
	
				@fclose($in);
				@fclose($out);
				@unlink($_FILES["file"]['tmp_name']);
			} else {
				die('{"status" : "error", "error" : {"code": 102, "message": "' . __("Failed to open output stream.", 'noo') . '"}}');
			}
	
		} else {
			die('{"status" : "error", "error" : {"code": 103, "message": "' . __("Failed to move uploaded file.", 'noo') . '"}}');
		}
	
	} else {
		// Open temp file
		$out = @fopen("{$file_path}.part", $chunk == 0 ? "wb" : "ab");
		if ($out) {
			// Read binary input stream and append it to temp file
			$in = @fopen("php://input", "rb");
	
			if ($in) {
				while ($buff = fread($in, 4096))
					fwrite($out, $buff);
			} else {
				die('{"status" : "error", "error" : {"code": 101, "message": "' . __("Failed to open input stream.", 'noo') . '"}}');
			}
	
			@fclose($in);
			@fclose($out);
		} else {
			die('{"status" : "error", "error" : {"code": 102, "message": "' . __("Failed to open output stream.", 'noo') . '"}}');
		}
	
	}
	
	// Check if file has been uploaded
	if (!$chunks || $chunk == $chunks - 1) {
		// Strip the temp .part suffix off
		rename("{$file_path}.part", $file_path);
	}
	$uploaded_filename = $_FILES["file"]["name"];
	$output = array("status"    => "ok",
			"data" => array(
				"filename"     => $tmp_file_name ,
				"upload_filename" => str_replace("\\'", "'", urldecode($uploaded_filename)) //Decoding filename to prevent file name mismatch.
			)
	);
	wp_send_json($output);
}

function noo_plupload_delete_file(){
	check_ajax_referer('noo-plupload-remove', 'nonce');
	
	$file_name = isset($_POST['filename']) ? $_POST['filename'] : '';
	$dir = wp_upload_dir();
	$target_dir = $dir['basedir'].'/jobmonster/';
	if(!empty($file_name) && file_exists($target_dir.$file_name)){
		@unlink($target_dir.$file_name);
	}
	die(1);
}
add_action('wp_ajax_noo_plupload', 'noo_plupload');
add_action('wp_ajax_noo_plupload_delete_file', 'noo_plupload_delete_file');
add_action('wp_ajax_nopriv_noo_plupload', 'noo_plupload');
add_action('wp_ajax_nopriv_noo_plupload_delete_file', 'noo_plupload_delete_file');

