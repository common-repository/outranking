jQuery(function () {
  /**
   * A function to decode HTML entities
   * @since 1.0.1
   *
   * @param {String} encodedString encoded String containing HTML
   * @returns decoded HTML
   */
  function decodeEntities(encodedString) {
    var textArea = document.createElement("textarea");
    textArea.innerHTML = encodedString;
    var decodedString = textArea.value;
    return decodedString;
  }
  /**
   * A function to encode HTML entities from jQuery Array of Objects
   * @since 1.0.1
   *
   * @param {Array} jQueryObjectsArray
   * @returns encoded String containing HTML
   */
  function encodeEntities(jQueryObjectsArray) {
    var decodedString = "";
    jQueryObjectsArray.each((index, element) => {
      if (![undefined, null].includes(element.outerHTML)) {
        decodedString += element.outerHTML;
      }
    });
    return decodedString;
  }
  /**
   * Saving API Key
   * @since 1.0.0
   */
  jQuery("body").on("click", "#outranking_api_key_submit", function () {
    var api_key = jQuery("#outranking_api_key_input").val();
    jQuery.ajax({
      type: "post",
      dataType: "json",
      url: ajaxurl,
      data: { action: "outranking_save_token", key: api_key },
      beforeSend: function () {
        preLoader(true);
      },
      success: function (response) {
        /**
         * Changed custom validation to standard WP
         * @since  1.0.1
         */
        if (response.success === true) {
          refreshMetaBox({
            is_ajax_update: true,
          });
        }
      },
    });
  });
  /**
   * Getting API Updation Form on Click of Update API Key button
   * @since 1.0.0
   * @deprecated 1.0.0
   */
  jQuery("body").on("click", "#outranking_update_token_btn", function () {
    refreshMetaBox(
      {
        is_update_token_form: true,
        is_ajax_update: true,
      },
      () => {}
    );
  });
  /**
   * Import an Article
   * @since 1.0.0
   */
  jQuery("body").on("click", ".outranking-import-button", function (e) {
    e.preventDefault();
    let contentEditor = null;
    if (![undefined, null].includes(tinymce)) {
      contentEditor = tinymce.editors.filter(
        (editor) => editor.id === "content"
      );
      if (contentEditor.length > 0) {
        contentEditor = contentEditor[0];
      } else {
        contentEditor = null;
      }
    }
    if (![undefined, null].includes(contentEditor)) {
      contentEditor.setMode("readonly");
    }
    var outrankingID = jQuery(this)
      .closest(".outranking-article")
      .data("outranking-id");
    jQuery.ajax({
      type: "post",
      dataType: "json",
      url: ajaxurl,
      data: { action: "outranking_import_article", id: outrankingID },
      beforeSend: function () {
        preLoader(true);
      },
      success: function (response) {
        /**
         * Changed custom validation to standard WP
         * @since  1.0.1
         */
        if (response.success === true) {
          var article = response.data.html;
          var modalData = response.data.modalData;
          if (article.content !== null) {
            /**
             * Finding DOC_URL, META_TITLE and META_DESCRIPTION and removing them from content
             * @since 1.0.1
             */
            if (![undefined, null].includes(contentEditor)) {
              /**
               * Update Title and Excerpt
               * @since 1.0.1
               */
              if (![undefined, null].includes(article.metaTitle)) {
                jQuery("#title").val(article.metaTitle);
                jQuery("#title")
                  .closest("#titlewrap")
                  .find("label")
                  .addClass(".screen-reader-text");
              }
              if (![undefined, null].includes(article.metaDescription)) {
                jQuery("#excerpt").val(article.metaDescription);
              }
              contentEditor.setContent(article.content);
            } else {
              /**
               * Update Title and Excerpt
               * @since 1.0.1
               */
              if (![undefined, null].includes(article.metaTitle)) {
                wp.data
                  .dispatch("core/editor")
                  .editPost({ title: article.metaTitle });
              }
              if (![undefined, null].includes(article.content)) {
                wp.data
                  .dispatch("core/editor")
                  .editPost({ content: article.content });
              }
              wp.data
                .dispatch("core/block-editor")
                .resetBlocks(wp.blocks.parse(article.content));
              var block = wp.data.select("core/block-editor").getBlocks()[0];
              if (![null, undefined].includes(block)) {
                wp.data.dispatch("core/block-editor").replaceBlocks(
                  block.clientId,
                  wp.blocks.rawHandler({
                    HTML: wp.blocks.getBlockContent(block),
                  })
                );
              }
            }
            showAlert("Article Imported Successfully !");
            if (modalData) {
              jQuery("#outranking-metaData-section").append(modalData);
              jQuery(".outranking-content-section").css("display", "none");
              jQuery(".close-modal").on("click", function () {
                jQuery("#outranking-metaData-section").empty();
                jQuery(".outranking-content-section").css("display", "block");
              });
              jQuery("#copyDescription").on("click", function () {
                copyToClipboard(
                  document.getElementById("metaDescription"),
                  "Meta Description!",
                  false
                );
              });
              jQuery("#copyTitle").on("click", function () {
                copyToClipboard(
                  document.getElementById("metaTitle"),
                  "Meta Title!",
                  false
                );
              });
            }
          }
        }
      },
      complete: function () {
        if (![undefined, null].includes(contentEditor)) {
          contentEditor.setMode("design");
        }
        preLoader(false);
      },
    });
  });
  /**
   * Copy To Clipboard Function
   * @since 1.0.2
   */
  function copyToClipboard(element, msg, selectCopiedText = false) {
    /* Get the text field */
    var copyText = element;
    /* Select the text field */
    if (selectCopiedText == true) {
      copyText.select();
      copyText.setSelectionRange(0, 99999); /* For mobile devices */
    }
    navigator.clipboard.writeText(copyText.value);
    /* Alert the copied text */
    showAlert("Copied To Clipbord " + msg);
  }
  /**
   * Exporting an Article
   * @since 1.0.0
   */
  jQuery("body").on("click", ".outranking-export-button", function (e) {
    e.preventDefault();
    if (![undefined, null].includes(tinymce.activeEditor)) {
      /**
       * Getting Title, Excerpt and Permalink
       * @since 1.0.1
       */
      var metaTitle = jQuery("#title").val();
      var docURL = jQuery("#sample-permalink>a").text();
      var metaDescription = jQuery("#excerpt").val();
      var docContent = tinymce.activeEditor.getContent();
      tinymce.activeEditor.setMode("readonly");
    } else {
      /**
       * Getting Title, Excerpt and Permalink
       * @since 1.0.1
       */
      var metaTitle = wp.data
        .select("core/editor")
        .getEditedPostAttribute("title");
      var docURL = wp.data.select("core/editor").getPermalink();
      var metaDescription = wp.data
        .select("core/editor")
        .getEditedPostAttribute("excerpt");
      var docContent = wp.data.select("core/editor").getEditedPostContent();
      // alert(wp.data.select("core/editor").getCurrentPostId());
    }
    /**
     * Prepending DOC_URL, META_TITLE and META_DESCRIPTION
     * @since 1.0.1
     */
    // content = "<DOC_URL>" + docURL + "</DOC_URL>\r\n";
    // content += "<META_TITLE>" + metaTitle + "</META_TITLE>\r\n";
    // content +=
    // 	"<META_DESCRIPTION>" + metaDescription + "</META_DESCRIPTION>\r\n";
    // content += docContent;
    // console.log(content);
    var outrankingID = jQuery(this)
      .closest(".outranking-article")
      .data("outranking-id");
    jQuery.ajax({
      type: "post",
      dataType: "json",
      url: ajaxurl,
      data: {
        action: "outranking_export_article",
        documentId: outrankingID,
        title: metaTitle,
        description: metaDescription,
        url: docURL,
        content: docContent,
      },
      beforeSend: function () {
        preLoader(true);
      },
      success: function (response) {
        /**
         * Changed custom validation to standard WP
         * @since  1.0.1
         */
        if (response.success === true) {
          showAlert("Article Exported Successfully !");
        }
        refreshMetaBox({
          is_ajax_update: true,
        });
      },
      complete: function () {
        if (![undefined, null].includes(tinymce.activeEditor)) {
          tinymce.activeEditor.setMode("design");
        }
        preLoader(false);
      },
    });
  });
  /**
   * Refreshing Metabox
   * @since 1.0.0
   *
   * @param {JSON} args arguments to be sent for filtering output
   * @param {Function} callback a function to use as callback
   */
  const refreshMetaBox = (args, callback = () => null) => {
    jQuery.ajax({
      type: "post",
      dataType: "json",
      url: ajaxurl,
      data: { action: "outranking_refresh_metabox", args: args },
      beforeSend: function () {
        preLoader(true);
      },
      success: function (response) {
        /**
         * Changed custom validation to standard WP
         * @since  1.0.1
         */
        if (response.success === true) {
          /**
           * Added decodeEntities and encodeEntites
           * @since 1.0.1
           */
          var htmlObj = jQuery(decodeEntities(response.data.html));
          jQuery("#outranking_metabox_container").html(encodeEntities(htmlObj));
          callback();
        }
      },
      complete: function () {
        preLoader(false);
      },
    });
  };
  /**
   * Toggle Preloader
   * @since 1.0.0
   *
   * @param {Boolean} status whether to turn on or off the preloader
   */
  const preLoader = (status) => {
    if (status === true) {
      jQuery("#outranking_metabox_container>div").css("display", "none");
      jQuery("#outranking-loader-container").css("display", "block");
    } else {
      jQuery("#outranking_metabox_container>div").css("display", "block");
      jQuery("#outranking-loader-container").css("display", "none");
    }
  };
  /**
   * Show Alert
   * @since 1.0.0
   *
   * @param {String/HTML} content content to be displayed inside of the alert
   */
  const showAlert = (content = null) => {
    jQuery(".outranking-alert").remove();
    jQuery("body").append(
      '<div class="outranking-alert">' + content + "</div>"
    );
    jQuery(".outranking-alert").fadeIn();
    setTimeout(() => {
      jQuery(".outranking-alert").fadeOut();
    }, 2000);
  };
  /**
   * Toggling API Key update form
   * @since 1.0.0
   */
  jQuery("body").on("click", "#outranking-api-setting", function () {
    jQuery(".outranking-api-key-block").toggleClass("d-flex");
  });
  /**
   * Searching Articles
   * @since 1.0.0
   */
  var searchTypingTimer;
  var pageIndex = 1;
  jQuery("body").on("keyup", "#outranking-article-search-input", function () {
    clearTimeout(searchTypingTimer);
    let searchString = null;
    if (jQuery("#outranking-article-search-input").val()) {
      searchString = jQuery("#outranking-article-search-input").val();
    }
    searchTypingTimer = setTimeout(() => {
      refreshArticleList(searchString, pageIndex);
    }, 1500);
  });
  /**
   * Articles Pagination
   * @since 1.0.0
   */
  jQuery("body").on("click", ".outranking-pagination .page-link", function (e) {
    e.preventDefault();
    var toGetPageIndex = jQuery(e.target).data("outranking-page-index");
    let searchString = null;
    if (jQuery("#outranking-article-search-input").val()) {
      searchString = jQuery("#outranking-article-search-input").val();
    }
    refreshArticleList(searchString, toGetPageIndex);
  });
  /**
   * Refresh Only Article List
   * @param {String} searchString sting to search for in the articles' list
   * @param {Number} pageIndex Page Index to fetch
   */
  const refreshArticleList = (searchString, pageIndex) => {
    jQuery.ajax({
      type: "post",
      dataType: "json",
      url: ajaxurl,
      data: {
        action: "outranking_refresh_metabox",
        args: {
          is_only_articles_update: true,
          filter_params: {
            search: searchString,
            page_index: pageIndex,
          },
        },
      },
      beforeSend: function () {
        jQuery("#outranking-article-search-input").prop("disabled", true);
        jQuery(".outranking-articles-wrapper>div").css("display", "none");
        jQuery("#outranking-article-loader-container").css("display", "block");
      },
      success: function (response) {
        /**
         * Changed custom validation to standard WP
         * @since  1.0.1
         */
        if (response.success === true) {
          /**
           * Added decodeEntities and encodeEntities
           * @since 1.0.1
           */
          var htmlObj = jQuery(decodeEntities(response.data.html));
          jQuery(".outranking-articles-wrapper").html(encodeEntities(htmlObj));
        }
      },
      complete: function () {
        jQuery(".outranking-articles-wrapper>div").css("display", "block");
        jQuery("#outranking-article-loader-container").css("display", "none");
        jQuery("#outranking-article-search-input").prop("disabled", false);
      },
    });
  };
});
