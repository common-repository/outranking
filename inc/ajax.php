<?php

/**
 * Function to handle save token action
 * @since 1.0.0
 */
if (!function_exists('outranking_save_token')) {
	function outranking_save_token()
	{
		if (isset($_REQUEST['key']) && trim($_REQUEST['key']) !== '') {
			/**
			 * Added Sanitization
			 * @since 1.0.1
			 */
			update_option('outranking_api_key', trim(sanitize_text_field($_REQUEST['key'])));
			/**
			 * Removed Output Buffering as it was not required to return
			 * @since 1.0.1
			 */
			// ob_start();
			// OutrankingMetaBox::render();
			// $metabox_html = ob_get_clean();
			/**
			 * Replaced json_encode with wp_send_json_success
			 * @since 1.0.1
			 */
			wp_send_json_success(array('message' => 'API key updated successfully', 'html' => ''));
		} else {
			/**
			 * Replaced json_encode with wp_send_json_error
			 * @since 1.0.1
			 */
			echo wp_send_json_error(array('message' => 'updating API key failed'));
		}
		die();
	}
}
/**
 * Function to handle refresh metabox action
 * @since 1.0.0
 */
if (!function_exists('outranking_refresh_metabox')) {
	function outranking_refresh_metabox()
	{
		$args = array();
		/**
		 * Added Sanitization of Key and Value
		 * @since 1.0.1
		 */
		foreach ($_POST['args'] as $key => $value) {
			if ($key === 'filter_params') {
				foreach ($_POST['args']['filter_params'] as $param => $param_value) {
					$args[sanitize_key($key)][sanitize_key($param)] = sanitize_text_field($param_value);
				}
			} else {
				$args[sanitize_key($key)] = rest_sanitize_boolean($value);
			}
		}
		ob_start();
		OutrankingMetaBox::render($args);
		$metabox_html = ob_get_clean();
		/**
		 * Added esc_html to variable $metabox_html
		 * @since 1.0.1
		 */
		wp_send_json_success(array('message' => 'metabox retrived successfully', 'html' => esc_html($metabox_html)));
	}
}
/**
 * Function to handle import article action
 * @since 1.0.0
 */
if (!function_exists('outranking_import_article')) {
	function outranking_import_article()
	{
		$outranking_document_id = sanitize_text_field($_POST['id']);
		$article = OutrankingMetaBox::import($outranking_document_id);
		/**	
		 * Converted into function
		 * @since 1.1.2
		 */
		// /**
		//  * Added support for custom tags, specially for videos, allowed iframe to be included in the content
		//  * Added support for nofollow and sponsored attributes in the a tag
		//  * @since 1.0.6
		//  */
		// $allowed_html = wp_kses_allowed_html('post');
		// $allowed_html['iframe'] = array(
		// 	'class' => 1,
		// 	'frameborder' => 1,
		// 	'allowfullscreen' => 1,
		// 	'src' => 1,
		// );

		// $allowed_html['a']['nofollow'] = 1;
		// $allowed_html['a']['rel'] = 1;


		// $article->content = wp_kses($article->content, $allowed_html);
		// /**
		//  * Processing images into the post and uploading it to the wordpress site
		//  * and replace the outranking URLs with the local ones.
		//  * @since 1.0.2
		//  */
		// $document = new DOMDocument();
		// $document->loadHTML(mb_convert_encoding($article->content, 'HTML-ENTITIES', 'UTF-8'));
		// $images = $document->getElementsByTagName('img');
		// $a_tags = $document->getElementsByTagName('a');
		// foreach ($images as $key => $image) {
		// 	$src = $image->getAttribute('src');
		// 	$new_src = outranking_upload_image($src);
		// 	/**
		// 	 * Replaced the function from createElement and setAttribute to cloneNode to get all the attributes along with it
		// 	 * $new_img = $document->createElement('img');
		// 	 * $new_img->setAttribute('alt', $image->getAttribute('alt'));
		// 	 * @since 1.0.6
		// 	 */
		// 	$new_img = $image->cloneNode(true);
		// 	$new_img->setAttribute('src', $new_src);
		// 	$image->parentNode->replaceChild($new_img, $image);
		// }
		// $iframes = $document->getElementsByTagName('iframe');
		// /**
		//  * Processing iframes into the post and replacing regular youtube URLs to embed ones
		//  * @since 1.0.2
		//  */
		// foreach ($iframes as $iframe) {
		// 	$src = $iframe->getAttribute('src');
		// 	$src = str_replace("https://www.youtube.com/watch?v=", "https://www.youtube.com/embed/", $src);
		// 	$src = str_replace("https://youtu.be/", "https://www.youtube.com/embed/", $src);
		// 	$new_iframe = $iframe->cloneNode(true);
		// 	$new_iframe->setAttribute('src', $src);
		// 	$iframe->parentNode->replaceChild($new_iframe, $iframe);
		// }
		// /**
		//  * Processing a into the post and add rel="nofollow" to the external links.
		//  * @since 1.0.2
		//  */
		// foreach ($a_tags as $a_tag) {
		// 	$key = site_url();
		// 	if (!is_numeric(stripos($a_tag->getAttribute('href'), $key))) {
		// 		if ($a_tag->hasAttribute('rel') and ($rel_att = $a_tag->getAttribute('rel')) !== '') {
		// 			$rel = preg_split('/\s+/', trim($rel_att));
		// 		}
		// 		if (!in_array('nofollow', $rel)) {
		// 			$rel[] = 'nofollow';
		// 			$a_tag->setAttribute('rel', implode(' ', $rel));
		// 		}
		// 	}
		// }
		// $article->content = $document->saveHTML();

		/**
		 * Filter the content 
		 * @since 1.1.2
		 */
		$article->content = outranking_content_filter($article->content);

		/**
		 * Show Meta Title and Meta Description in side panel for copy.
		 * @since 1.0.2
		 */
		$the_plugs = get_option('active_plugins');
		foreach ($the_plugs as $key => $value) {
			$string = explode('/', $value); // Folder name will be displayed
			if ($string[0] == 'seo-by-rank-math' || $string[0] == 'wordpress-seo') {
				$modalData = '<div class="outranking-post-meta-data">
				<a class="close-modal"  href="javascript:void(0)">
				<svg viewBox="0 0 20 20">
				<path fill="#000000" d="M15.898,4.045c-0.271-0.272-0.713-0.272-0.986,0l-4.71,4.711L5.493,4.045c-0.272-0.272-0.714-0.272-0.986,0s-0.272,0.714,0,0.986l4.709,4.711l-4.71,4.711c-0.272,0.271-0.272,0.713,0,0.986c0.136,0.136,0.314,0.203,0.492,0.203c0.179,0,0.357-0.067,0.493-0.203l4.711-4.711l4.71,4.711c0.137,0.136,0.314,0.203,0.494,0.203c0.178,0,0.355-0.067,0.492-0.203c0.273-0.273,0.273-0.715,0-0.986l-4.711-4.711l4.711-4.711C16.172,4.759,16.172,4.317,15.898,4.045z"></path>
				</svg>
				</a>
				<div class="form-group my-10">
				<label for="metaTitle">Title :</label>
				<div class="d-flex w-100 justify-content-between">
				<input type="text" value="' . $article->metaTitle . '" id="metaTitle" class="form-control" />
				<button class="btn btn-secondary" id="copyTitle">Copy</button>
				</div>
				</div>
				<div class="form-group my-10">
				<label for="metaDescription">Desscription :</label>
				<div class="d-flex w-100 justify-content-between">
				<input type="text" value="' . $article->metaDescription . '" id="metaDescription" class="form-control" />
				<button type="button" class="btn btn-secondary" id="copyDescription">Copy</button>
				</div>
				</div>
				</div>';
			}
		}
		wp_send_json_success(array('message' => 'article retrived successfully', 'modalData' => $modalData, 'html' => $article));
	}
}
/**
 * Function to handle export article action
 * @since 1.0.0
 */
if (!function_exists('outranking_export_article')) {
	function outranking_export_article()
	{
		$title = sanitize_text_field($_POST['title']);
		$description = sanitize_text_field($_POST['description']);
		$url = sanitize_text_field($_POST['url']);
		$outranking_document_id = sanitize_text_field($_POST['documentId']);
		$article_content = wp_kses_post($_POST['content']);
		$response = OutrankingMetaBox::export($outranking_document_id, $article_content, $title, $description, $url);
		/**
		 * Removed response variable from sending back
		 * @since 1.0.1
		 */
		wp_send_json_success(array('message' => 'article updated successfully', 'response' => $response));
	}
}
