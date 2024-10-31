<?php

/**
 * A function to truncate the string to a defined length
 * @since 1.0.0
 *
 * @param Number $length Length till the string needs to be truncated
 * @param String $string String that needs to be truncated
 *
 * @return String $string truncated String
 */
function limited_sting_length($length, $string)
{
	return strlen($string) > $length ? "<span title='" . $string . "'>" . substr($string, 0, $length) . "...</span>" : $string;
}
/**
 * A class that handles actions inside Outranking Metabox
 * @since 1.0.0
 */
class OutrankingMetaBox
{
	/**
	 * A function to render the metabox
	 * @since 1.0.1
	 *
	 * @param Boolean $args['is_ajax_update'] true if this is an AJAX update call
	 * @param Boolean $args['is_update_token_form'] true if update token form needs to be rendered
	 * @param Boolean $args['is_only_articles_update'] true if only articles list needs to be rendered
	 * @param String $args['filter_params']['search'] search string using which article needs to be filtered
	 * @param Number $args['filter_params']['page_index'] page index of the article list
	 *
	 * @return HTML $html renders the ouput of the metabox
	 */
	static function render($args = array('is_ajax_update' => false, 'is_update_token_form' => false, 'is_only_articles_update' => false))
	{
		extract($args);
		$is_ajax_update = isset($is_ajax_update) ? $is_ajax_update : false;
		$is_update_token_form = isset($is_update_token_form) ? $is_update_token_form : false;
		$is_only_articles_update = isset($is_only_articles_update) ? $is_only_articles_update : false;
		if ((bool)$is_only_articles_update === true) {
			OutrankingMetaBox::get_articles((bool)$is_only_articles_update, $filter_params);
			return;
		}
		if ((bool)$is_ajax_update === false) {
			echo '<div id="outranking_metabox_container">';
		}
		echo '<div id="outranking-loader-container">';
		echo '<div class="outranking-loader">';
		echo '</div>';
		echo '</div>';
		$api_key = get_option('outranking_api_key');
		if ($api_key === false || (bool)$is_update_token_form === true) {
			echo '<div id="outranking_api_key_form">';
			echo '<div class="form-group">';
			echo '<label>API Key</label>';
			/**
			 * Added esc_attr
			 * @since 1.0.1
			 */
			echo '<input type="text" class="form-control" id="outranking_api_key_input" ' . ($api_key ?? 'value="' . esc_attr($api_key) . '"') . ' placeholder="Enter API key">';
			echo '</div>';
			echo '<button type="button" class="outranking-secondary-btn" id="outranking_api_key_submit">Save</button>';
			echo '</div>';
		} else {
			echo '<div>';
			echo '<div class="outranking-user">';
			echo '<div class="d-flex align-items-center">';
			// echo '<img src="' . plugins_url('../assets/images/user-profile.jpg', __FILE__) . '" alt="user" class="outranking-login-user-img">';
			// echo '<span>Pratik Malvi</span>';
			echo '<div class="hamburger-btn d-flex align-items-center justify-content-between w-100">';
			echo '<label>Settings</label>';
			echo '<a href="#" id="outranking-api-setting" class="d-flex align-items-center">';
			/**
			 * Added esc_url
			 * @since 1.0.1
			 */
			echo '<img src="' . esc_url(plugins_url('../assets/images/setting.svg', __FILE__)) . '" alt="setting">';
			echo '</a>';
			echo '</div>';
			echo '</div>';
			echo '</div>';
			echo '<div class="outranking-api-key-block align-items-center">';
			echo '<div class="form-group w-100">';
			/**
			 * Added esc_attr
			 * @since 1.0.1
			 */
			echo '<input type="text" class="form-control" id="outranking_api_key_input" value="' . esc_attr($api_key) . '" placeholder="Enter API key">';
			echo '</div>';
			echo '<button type="button" class="outranking-secondary-btn outranking-api-save-btn" id="outranking_api_key_submit">Save</a>';
			echo '</div>';
			/**
			 * Removed Output Buffer
			 * @since 1.0.1
			 */
			OutrankingMetaBox::get_articles();
			echo '</div>';
		}
		if ((bool)$is_ajax_update === false) {
			echo '</div>';
		}
	}
	/**
	 * A function to display list of articles based on search string and pagination
	 * @since 1.0.0
	 *
	 * @param Boolean $is_only_articles_update true if this call only for updating the articles
	 * @param String $filter_params['search'] Search String based on which articles will be filtered
	 * @param String $filter_params['page_index'] Page Index to fetch articles
	 *
	 * @return HTML $html Renders the list of articles based on the inputs provided
	 */
	static function get_articles($is_only_articles_update = false, $filter_params = array())
	{
		$search_key = isset($filter_params['search']) && !in_array(trim($filter_params['search']), array(null, 'undefined', 'null', '', NULL)) ? $filter_params['search'] : '';
		$page_index = isset($filter_params['page_index']) && !in_array($filter_params['page_index'], array(null, 'undefined', 'null', '', NULL)) ? (int)($filter_params['page_index'] - 1) : 0;
		$api_key = get_option('outranking_api_key');
		$response = wp_remote_get(OUTRANKING_SERVER_URL . '/api/documents', array(
			'timeout' => 120,
			'headers' => array("apiKey" => $api_key),
			'body' => array("searchQuery" => $search_key, "page" => $page_index, "size" => 10),
			'sslverify' => FALSE
		));
		if (is_wp_error($response)) {
			/**
			 * Added esc_html
			 * @since  1.0.1
			 */
			echo esc_html($response->get_error_message());
			return false;
		}
		$data = json_decode(wp_remote_retrieve_body($response));
		if (is_object($data)) {
			if ((int)$data->code !== 200) {
				return false;
			}
			$articles = $data->data;
			if ($is_only_articles_update === false) {
				echo '<div id="outranking-metaData-section"></div>';
				echo '<div class="outranking-content-section">';
				echo '<div class="form-group mb-3">';
				echo '<label for="outranking-article-search-input">Search Documents</label>';
				echo '<input type="text" class="form-control" id="outranking-article-search-input" placeholder="Search">';
				echo '</div>';
				echo '<span class="outranking-main-title mb-4">';
				echo 'Documents';
				echo '</span>';
				echo '<div class="outranking-articles-wrapper">';
			}
			echo '<div id="outranking-article-loader-container">';
			echo '<div class="outranking-loader">';
			echo '</div>';
			echo '</div>';
			for ($i = 0; $i < count($articles); $i++) {
				$article = $articles[$i];
				$created_on = date_format(date_create($article->createdOn), 'j-M');
				/**
				 * Added esc_attr, esc_url and esc_html
				 * @since 1.0.1
				 */
				echo '<div class="outranking-article" data-outranking-id="' . esc_attr($article->documentId) . '">' . "\r\n";
				echo '<a href="' . esc_url(OUTRANKING_SERVER_URL . '/document/seo/content/' . esc_attr($article->documentId)) . '" target="_blank" class="outranking-title">' . "\r\n";
				echo '<h2>' . esc_html($article->documentName) . '</h2>' . "\r\n";
				echo '</a>';
				echo '<div class="outranking-article-meta">' . "\r\n";
				echo '<div class="outranking-article-meta-date">' . "\r\n";
				echo '<img src="' . esc_url(plugins_url('../assets/images/time.svg', __FILE__)) . '" alt="time">' . "\r\n";
				echo '<span>' . esc_html($created_on) . '</span>';
				echo '</div>';
				echo '</div>' . "\r\n";
				echo '<div class="outranking-score-and-actions">';
				echo '<div class="outranking-score">' . "\r\n";
				echo '<div class="outranking-article-meta-location">' . "\r\n";
				echo '<img src="' . esc_url(plugins_url('../assets/images/map-pin.svg', __FILE__)) . '" alt="pin">';
				echo '<span>' . esc_html(limited_sting_length(15, $article->location)) . '</span>' . "\r\n";
				echo '</div>';
				echo '</div>';
				echo '<div class="outranking-actions">';
				echo '<a href="#" class="outranking-import-button">';
				echo '<img src="' . esc_url(plugins_url('../assets/images/import.svg', __FILE__)) . '" alt="import" title="Import">';
				echo '</a>';
				echo '<a href="#" class="outranking-export-button">';
				echo '<img src="' . esc_url(plugins_url('../assets/images/export.svg', __FILE__)) . '" alt="export" title="Export">';
				echo '</a>';
				echo '</div>';
				echo '</div>';
				echo '</div>';
			}
			$current_page = $data->page + 1;
			$total_pages = $data->totalPage;
			$pages_to_display = array();
			$display_pagination = true;
			if ($current_page === 1) {
				$pages_to_display[] = 1;
				for ($i = 2; $i <= 3; $i++) {
					if ($i <= $total_pages) {
						$pages_to_display[] = $i;
					}
				}
			} else if ($current_page > 1 && $current_page < $total_pages) {
				$pages_to_display[] = $current_page - 1;
				$pages_to_display[] = $current_page;
				$pages_to_display[] = $current_page + 1;
			} else if ($current_page === $total_pages) {
				for ($i = $total_pages - 2; $i <= $total_pages; $i++) {
					if ($i > 0) {
						$pages_to_display[] = $i;
					}
				}
			} else {
				$display_pagination = false;
			}
			if ($display_pagination === true) {
				/**
				 * Added esc_attr and esc_html
				 * @since 1.0.1
				 */
				echo '<div>';
				echo '<div class="d-flex">';
				echo '<nav class="ml-auto">';
				echo '<ul class="pagination outranking-pagination">';
				echo '<li class="page-item">';
				echo '<a data-outranking-page-index="' . esc_attr($current_page - 1) . '" class="page-link ';
				echo $current_page === 1 ? "disabled" : "";
				echo '" href="#!" aria-label="Previous">&laquo;';
				echo '</a>';
				echo '</li>';
				foreach ($pages_to_display as $page_no) {
					echo '<li class="page-item"><a class="page-link ';
					echo $page_no === $current_page ? "active" : "";
					echo '" href="#!" data-outranking-page-index="' . esc_attr($page_no) . '">' . esc_html($page_no) . '</a></li>';
				}
				echo '<li class="page-item">';
				echo '<a data-outranking-page-index="' . esc_attr($current_page + 1) . '" class="page-link ';
				echo $current_page === $total_pages ? "disabled" : "";
				echo '" href="#!" aria-label="Next">&raquo;';
				echo '</a>';
				echo '</li>';
				echo '</ul>';
				echo '</nav>';
				echo '</div>';
				echo '</div>';
			}
			if ($is_only_articles_update === false) {
				echo '</div>';
				echo '</div>';
			}
		} else {
			echo '<div class="outranking-api-error">';
			/**
			 * Added esc_url
			 * @since 1.0.1
			 */
			echo '<img src="' . esc_url(plugins_url('../assets/images/error.svg', __FILE__)) . '">';
			echo '<div class="outranking-error-title"> Ooops! </div>';
			if ($response['response']['code'] === 403) {
				echo '<h4>';
				echo 'The API key is invalid';
				echo '</h4>';
			} else {
				echo '<h4>';
				echo 'Ooops !!';
				echo '</h4>';
				echo '<div>';
				/**
				 * Added esc_html
				 * @since 1.0.1
				 */
				echo esc_html($response['response']['message']);
				echo '</div>';
			}
			echo '</div>';
		}
	}
	/**
	 * A function that gets an article from the server
	 * @since 1.0.0
	 *
	 * @param Number/String $article_id ID of the article that needs to be fetched
	 *
	 * @return Object $data Outranking article data
	 */
	static function import($article_id)
	{
		$api_key = get_option('outranking_api_key');
		$response = wp_remote_get(OUTRANKING_SERVER_URL . '/api/document/' . $article_id, array(
			'headers' => array("apiKey" => $api_key),
			'sslverify' => FALSE
		));
		if (is_wp_error($response)) {
			return false;
		}
		$data = json_decode(wp_remote_retrieve_body($response));
		if ((int)$data->code !== 200) {
			return false;
		}
		return $data->data;
	}
	/**
	 * A function that sends an article to the server
	 * @since 1.0.0
	 *
	 * @param Number/String $article_id ID of the article
	 * @param HTML/String $post_content Content of the article to be sent
	 *
	 * @return Object Response of the API
	 */
	static function export($article_id, $post_content, $title, $description, $url)
	{
		$api_key = get_option('outranking_api_key');
		$args = array(
			"documentId" => esc_html($article_id),
			"metaTitle" => esc_html($title),
			"metaDescription" => esc_html($description),
			"docUrl" => esc_url($url),
			"content" =>  $post_content,
		);
		$response = wp_remote_post(OUTRANKING_SERVER_URL . '/api/document/content/' . $article_id, array(
			'headers' => array("apiKey" => $api_key, 'Content-Type' => 'application/json'),
			'body' => json_encode($args),
			'sslverify' => FALSE
		));
		if (is_wp_error($response)) {
			echo $response->get_error_message();
			return false;
		}
		$data = json_decode(wp_remote_retrieve_body($response));
		if ((int)$data->code !== 200) {
			return false;
		}
		return $data;
	}
}
