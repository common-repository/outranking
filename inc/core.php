<?php

if (!function_exists('download_url')) {
	include_once(ABSPATH . '/wp-admin/includes/file.php');
}
if (!function_exists('media_handle_sideload')) {
	include_once(ABSPATH . '/wp-admin/includes/image.php');
	include_once(ABSPATH . '/wp-admin/includes/media.php');
}
if (!function_exists('wp_get_attachment_url')) {
	include_once(ABSPATH . '/wp-includes/post.php');
}
/**
 * Function to Upload Image To Media Library From External URL
 * @since 1.0.2
 */
function outranking_upload_image($url)
{
	$image = "";
	if ($url != "" && stripos($url, site_url()) === FALSE) {
		$file = array();
		$file['name'] = $url;
		$file['tmp_name'] = download_url($url);
		if (is_wp_error($file['tmp_name'])) {
			@unlink($file['tmp_name']);
			// var_dump($file['tmp_name']->get_error_messages());
		} else {
			$attachmentId = media_handle_sideload($file);
			if (is_wp_error($attachmentId)) {
				@unlink($file['tmp_name']);
				// var_dump($attachmentId->get_error_messages());
			} else {
				$image = wp_get_attachment_url($attachmentId);
			}
		}
	} else {
		$image = $url;
	}
	return $image;
}
/**
 * Added a common function to filter the content coming from Outranking Platform which will replace the images with locally hosted images,
 * change the youtube video to embed
 * @since 1.1.2
 */
function outranking_content_filter($content)
{

	$allowed_html = wp_kses_allowed_html('post');
	$allowed_html['iframe'] = array(
		'class' => 1,
		'frameborder' => 1,
		'allowfullscreen' => 1,
		'src' => 1,
	);

	$allowed_html['a']['nofollow'] = 1;
	$allowed_html['a']['rel'] = 1;

	$content = wp_kses($content, $allowed_html);

	$document = new DOMDocument();
	$document->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
	$images = $document->getElementsByTagName('img');
	$a_tags = $document->getElementsByTagName('a');

	foreach ($images as $key => $image) {

		$src = $image->getAttribute('src');
		$new_src = __NAMESPACE__ . outranking_upload_image($src);

		/**
		 * Replaced the function from createElement and setAttribute to cloneNode to get all the attributes along with it
		 * $new_img = $document->createElement('img');
		 * $new_img->setAttribute('alt', $image->getAttribute('alt'));
		 * @since 1.0.6
		 */
		$new_img = $image->cloneNode(true);
		$new_img->setAttribute('src', $new_src);
		$image->parentNode->replaceChild($new_img, $image);
	}
	$iframes = $document->getElementsByTagName('iframe');
	/**
	 * Processing iframes into the post and replacing regular youtube URLs to embed ones
	 * @since 1.0.2
	 */
	foreach ($iframes as $iframe) {
		$src = $iframe->getAttribute('src');
		echo $src;
		$src = str_replace("https://www.youtube.com/watch?v=", "https://www.youtube.com/embed/", $src);
		$src = str_replace("https://youtu.be/", "https://www.youtube.com/embed/", $src);
		echo $src;
		$new_iframe = $iframe->cloneNode(true);
		$new_iframe->setAttribute('src', $src);
		$iframe->parentNode->replaceChild($new_iframe, $iframe);
	}

	/**
	 * Processing a into the post and add rel="nofollow" to the external links.
	 * @since 1.0.2
	 */
	foreach ($a_tags as $a_tag) {
		$key = site_url();
		if (!is_numeric(stripos($a_tag->getAttribute('href'), $key))) {
			if ($a_tag->hasAttribute('rel') and ($rel_att = $a_tag->getAttribute('rel')) !== '') {
				$rel = preg_split('/\s+/', trim($rel_att));
			}
			if (!in_array('nofollow', $rel)) {
				$rel[] = 'nofollow';
				$a_tag->setAttribute('rel', implode(' ', $rel));
			}
		}
	}

	return $document->saveHTML();
}
