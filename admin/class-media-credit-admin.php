<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2013-2016 Peter Putzer.
 * Copyright 2010-2011 Scott Bressler.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License,
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 * @link       https://mundschenk.at
 * @since      3.0.0
 *
 * @package    Media_Credit
 * @subpackage Media_Credit/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Media_Credit
 * @subpackage Media_Credit/admin
 * @author     Peter Putzer <github@mundschenk.at>
 */
class Media_Credit_Admin implements Media_Credit_Base {

	/**
	 * The ID of this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The base URL for loading ressources.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      string    $ressource_url    The base URL for admin ressources.
	 */
	private $ressource_url;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    3.0.0
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->ressource_url = plugin_dir_url( __FILE__ );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    3.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Media_Credit_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Media_Credit_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		/* wp_enqueue_style( $this->plugin_name, $this->ressource_url . 'css/media-credit-admin.css', array(), $this->version, 'all' ); */
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    3.0.0
	 */
	public function enqueue_scripts() {
		// Preview script for the settings page.
		if ( $this->is_media_settings_page() ) {
			wp_enqueue_script( 'media-credit-preview', $this->ressource_url . 'js/media-credit-preview.js', array( 'jquery' ), $this->version, true );
		}

		// Autocomplete when editing media.
		if ( $this->is_media_edit_page() ) {
			wp_enqueue_script( 'media-credit-autocomplete',   $this->ressource_url . 'js/media-credit-autocomplete.js',   array( 'jquery', 'jquery-ui-autocomplete' ), $this->version, true );
			wp_enqueue_script( 'media-credit-media-handling', $this->ressource_url . 'js/media-credit-media-handling.js', array( 'jquery' ),                           $this->version, true );
		}
	}

	/**
	 * Template for setting Media Credit in image properties.
	 */
	public function image_properties_template() {
		include( dirname( __FILE__ ) . '/partials/media-credit-image-properties-tmpl.php' );
	}

	/**
	 * Removes the default wpeditimage plugin.
	 *
	 * @param array $plugins An array of plugins to load.
	 * @return array The array of plugins to load.
	 */
	public function tinymce_internal_plugins( $plugins ) {
		if ( false !== ( $key = array_search( 'wpeditimage', $plugins ) ) ) {
			unset( $plugins[ $key ] );
		}

		return $plugins;
	}

	/**
	 * Add our own version of the wpeditimage plugin.
	 * The plugins depend on the global variable echoed in admin_head().
	 *
	 * @param array $plugins An array of plugins to load.
	 *
	 * @return array The array of plugins to load.
	 */
	public function tinymce_external_plugins( $plugins ) {
		$plugins['mediacredit'] = $this->ressource_url . 'js/tinymce4/media-credit-tinymce.js';
		$plugins['noneditable'] = $this->ressource_url . 'js/tinymce4/tinymce-noneditable.js';

		return $plugins;
	}

	/**
	 * Add our global variable for the TinyMCE plugin.
	 */
	public function admin_head() {
		$options = get_option( self::OPTION );

		$authors = array();
		foreach ( get_users( array( 'who' => 'authors' ) ) as $author ) {
			$authors[ $author->ID ] = $author->display_name;
		}

		$media_credit = array(
			'separator'    => $options['separator'],
			'organization' => $options['organization'],
			'id'           => $authors,
		);

		?>
		<script type='text/javascript'>
			var $mediaCredit = <?php echo wp_json_encode( $media_credit ) ?>;
		</script>
		<?php
	}

	/**
	 * Add styling for media credits in the rich editor.
	 *
	 * @param string $css A comma separated list of CSS files.
	 *
	 * @return string A comma separated list of CSS files.
	 */
	public function tinymce_css( $css ) {
		return $css . ( ! empty( $css ) ? ',' : '' ) . $this->ressource_url . 'css/media-credit-tinymce.css';
	}

	/**
	 * Initialize settings.
	 */
	public function admin_init() {
		register_setting( 'media', $this->plugin_name, array( $this, 'sanitize_option_values' ) );

		// Don't bother doing this stuff if the current user lacks permissions as they'll never see the pages.
		if ( ( current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' ) ) && user_can_richedit() ) {
			add_action( 'admin_head',           array( $this, 'admin_head' ) );
			add_filter( 'mce_external_plugins', array( $this, 'tinymce_external_plugins' ) );
			add_filter( 'tiny_mce_plugins',     array( $this, 'tinymce_internal_plugins' ) );
			add_filter( 'mce_css',              array( $this, 'tinymce_css' ) );
		}

		// Filter the_author using this method so that freeform media credit is correctly displayed in Media Library.
		add_filter( 'the_author', 'Media_Credit_Template_Tags::get_media_credit' );
	}

	/**
	 * Is the current page one where media attachments can be edited?
	 *
	 * @access private
	 */
	private function is_media_edit_page() {
		global $pagenow;

		$media_edit_pages = array(
			'post-new.php',
			'post.php',
			'page.php',
			'page-new.php',
			'media-upload.php',
			'media.php',
			'media-new.php',
			'ajax-actions.php',
			'upload.php',
			'customize.php',
		);

		return in_array( $pagenow, $media_edit_pages, true );
	}

	/**
	 * Is the current page the media settings page?
	 *
	 * @access private
	 */
	private function is_media_settings_page() {
		global $pagenow;

		return ( 'options-media.php' === $pagenow );
	}

	/**
	 * AJAX hook for autocompleting author names.
	 *
	 * Use `action=media_credit_author_names` and `term=<your search>` in the AJAX call.
	 */
	public function ajax_author_names() {
		check_ajax_referer( 'media_credit_author_names', 'nonce', true );

		if ( ! empty( $_POST['term'] ) ) {                        // Input var okay.
			$term = sanitize_key( wp_unslash( $_POST['term'] ) ); // Input var okay.
		} else {
			wp_send_json_error( '0' ); // Standard response for failure.
		}

		if ( ! empty( $_POST['limit'] ) ) {                   // Input var okay.
			$limit = absint( wp_unslash( $_POST['limit'] ) ); // Input var okay.
		} else {
			$limit = 10;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( '-1' ); // Standard response for permissions.
		}

		$authors = $this->get_editable_authors_by_name( $term,  $limit );
		if ( empty( $authors ) ) {
			wp_send_json_error( '0' ); // Standard response for failure.
		}

		$results = array();
		foreach ( $authors as $author ) {
			$results[] = (object) array(
				'id'    => $author->id,
				'label' => $author->display_name,
				'value' => $author->display_name,
			);
		}

		wp_send_json_success( $results );
	}

	/**
	 * Returns the users from the current blog that are valid post authors and that contain $name within their
	 * display name.
	 *
	 * @param string $name    The name we are looking for.
	 * @param number $limit   Limit of results to fetch.
	 * @return An array of users.
	 */
	private function get_editable_authors_by_name( $name, $limit ) {
		global $wpdb;

		$cache_key = "authors_by_name_{$name}_for_limit_{$limit}";

		if ( ! $authors = wp_cache_get( $cache_key, 'media_credit' ) ) {
			// Allow display_name search in WP_User_Query.
			add_filter( 'user_search_columns', array( $this, 'add_display_name_to_search_columns' ), 10, 3 );

			// Get possible author names.
			$authors = get_users( array(
				'who'                => 'authors',
				'fields'             => array( 'id', 'display_name' ),
				'search'		     => '*' . $name . '*',
				'search_columns'     => array( 'display_name' ),
				'number'             => $limit,
				'media_credit_query' => 'authors_by_name', // Marker for our user_search_columns filter.
			) );

			// Reset filters to status quo ante.
			remove_filter( 'user_search_columns', array( $this, 'add_display_name_to_search_columns' ), 10 );

			// Cache result.
			wp_cache_set( $cache_key, $authors, 'media_credit', MINUTE_IN_SECONDS );
		}

		// TODO: filter doc.
		return apply_filters( 'get_editable_authors_by_name', $authors, $name );
	}

	/**
	 * Change search_columns to 'display_name' for 'authors_by_name' query.
	 *
	 * @param array         $search_columns An array of column names.
	 * @param int|string    $search         The search term.
	 * @param WP_User_Query $query          The query object.
	 * @return array                        Array of column names.
	 */
	public function add_display_name_to_search_columns( $search_columns, $search, $query ) {
		if ( isset( $query->query_vars['media_credit_query'] ) && 'authors_by_name' === $query->query_vars['media_credit_query'] ) {
			return array( 'display_name' );
		} else {
			return $search_columns;
		}
	}

	/**
	 * AJAX hook for filtering post content after editing media files
	 */
	public function ajax_filter_content() {
		if ( ! isset( $_REQUEST['image_id'] ) || ! $image_id = absint( $_REQUEST['image_id'] ) ) { // Input var okay.
			wp_send_json_error();
		}

		check_ajax_referer( 'update-post_' . $image_id, 'nonce', true );

		if ( ! isset( $_REQUEST['post_content'] ) || ! ( $old_content = filter_var( wp_unslash( $_REQUEST['post_content'] ) ) )      || // Input var okay. Only uses for comparison.
			 ! isset( $_REQUEST['author_id'] )    || ! ( $author_id   = absint( $_REQUEST['author_id'] ) )                           || // Input var okay.
			 ! isset( $_REQUEST['freeform'] )     || ! ( $freeform    = sanitize_text_field( wp_unslash( $_REQUEST['freeform'] ) ) ) || // Input var okay.
			 ! isset( $_REQUEST['url'] )          || ! ( $url         = esc_url_raw( wp_unslash( $_REQUEST['url'] ) ) ) ) {             // Input var okay.
			wp_send_json_error();
		}

		wp_send_json_success( $this->filter_post_content( $old_content, $image_id, $author_id, $freeform, $url ) );
	}

	/**
	 * Display settings for plugin on the built-in Media options page.
	 */
	public function display_settings() {
		$options = get_option( self::OPTION );

		add_settings_section( $this->plugin_name, __( 'Media Credit', 'media-credit' ),
							  array( $this, 'print_settings_section' ), 'media',
							  array( 'options' => $options ) );

		add_settings_field( 'preview', sprintf( '<em>%s</em>', __( 'Preview', 'media-credit' ) ),
							array( $this, 'print_preview_field' ), 'media', $this->plugin_name,
							array( 'options' => $options ) );
		add_settings_field( 'separator', __( 'Separator', 'media-credit' ),
							array( $this, 'print_separator_field' ), 'media', $this->plugin_name,
							array( 'options' => $options ) );
		add_settings_field( 'organization', __( 'Organization', 'media-credit' ),
							array( $this, 'print_organization_field' ), 'media', $this->plugin_name,
							array( 'options' => $options ) );
		add_settings_field( 'credit_at_end', __( 'Display credit after posts', 'media-credit' ),
							array( $this, 'print_end_of_post_field' ), 'media', $this->plugin_name,
							array( 'options' => $options ) );
		add_settings_field( 'no_default_credit', __( 'Do not display default credit', 'media-credit' ),
							array( $this, 'print_no_default_credit_field' ), 'media', $this->plugin_name,
							array( 'options' => $options ) );
		add_settings_field( 'post_thumbnail_credit', __( 'Display credit for featured images', 'media-credit' ),
							array( $this, 'print_post_thumbnail_credit_field' ), 'media', $this->plugin_name,
							array( 'options' => $options ) );
	}

	/**
	 * Enqueue scripts & styles for displaying media credits in the rich-text editor.
	 *
	 * @param array $options An array of options. Used ot check if TinyMCE is enabled.
	 */
	public function enqueue_editor( $options ) {
		if ( $options['tinymce'] ) {
			// Note: An additional dependency "media-views" is not listed below
			// because in some cases such as /wp-admin/press-this.php the media
			// library isn't enqueued and shouldn't be. The script includes
			// safeguards to avoid errors in this situation.
			wp_enqueue_script( 'media-credit-image-properties', $this->ressource_url . 'js/tinymce4/media-credit-image-properties.js', array( 'jquery' ), $this->version, true );
			wp_enqueue_script( 'media-credit-tinymce-switch',   $this->ressource_url . 'js/tinymce4/media-credit-tinymce-switch.js',   array( 'jquery' ), $this->version, true );

			// Edit in style.
			wp_enqueue_style( 'media-credit-image-properties-style', $this->ressource_url . 'css/tinymce4/media-credit-image-properties.css', array(), $this->version, 'screen' );
		}
	}

	/**
	 * Add custom media credit fields to Edit Media screens.
	 *
	 * @param array       $fields The custom fields.
	 * @param int|WP_Post $post   Post object or ID.
	 * @return array              The list of fields.
	 */
	public function add_media_credit_fields( $fields, $post ) {
		$credit = Media_Credit_Template_Tags::get_media_credit( $post );
		$html = "<input id='attachments[$post->ID][media-credit]' class='media-credit-input' size='30' value='$credit' name='attachments[$post->ID][media-credit]'  />";
		$fields['media-credit'] = array(
			'label'         => __( 'Credit:', 'media-credit' ),
			'input'         => 'html',
			'html'          => $html,
			'show_in_edit'  => true,
			'show_in_modal' => true,
		);

		$url = Media_Credit_Template_Tags::get_media_credit_url( $post );
		$html = "<input id='attachments[$post->ID][media-credit-url]' class='media-credit-input' type='url' size='30' value='$url' name='attachments[$post->ID][media-credit-url]' />";
		$fields['media-credit-url'] = array(
			'label'         => __( 'Credit URL:', 'media-credit' ),
			'input'         => 'html',
			'html'          => $html, // @todo: Find another way?
			'show_in_edit'  => true,
			'show_in_modal' => true,
		);

		$author = '' === Media_Credit_Template_Tags::get_freeform_media_credit( $post ) ? $post->post_author : '';
		$author_display = Media_Credit_Template_Tags::get_media_credit( $post );
		$author_for_script = '' === $author ? -1 : $author;
		$nonce = wp_create_nonce( 'media_credit_author_names' );
		$html_hidden = "<input name='attachments[$post->ID][media-credit-hidden]' id='attachments[$post->ID][media-credit-hidden]' type='hidden' value='$author' class='media-credit-hidden' data-author='$author_for_script' data-post-id='$post->ID' data-author-display='$author_display' data-nonce='$nonce' />";
		$fields['media-credit-hidden'] = array(
			'label'         => '', // necessary for HTML type fields.
			'input'         => 'html',
			'html'          => $html_hidden,
			'show_in_edit'  => true,
			'show_in_modal' => true,
		);

		return $fields;
	}

	/**
	 * Change the post_author to the entered media credit from add_media_credit() above.
	 *
	 * @param object $post Object of attachment containing all fields from get_post().
	 * @param object $attachment Object of attachment containing few fields, unused in this method.
	 */
	function save_media_credit_fields( $post, $attachment ) {
		$wp_user_id    = $attachment['media-credit-hidden'];
		$freeform_name = $attachment['media-credit'];
		$url           = $attachment['media-credit-url'];

		// We need to update the credit URL in any case.
		update_post_meta( $post['ID'], self::URL_POSTMETA_KEY, $url ); // unsert '_media_credit_url' metadata field.

		if ( ! empty( $wp_user_id ) && get_the_author_meta( 'display_name', $wp_user_id ) === $freeform_name ) {
			// A valid WP user was selected, and the display name matches the free-form
			// the final conditional is necessary for the case when a valid user is selected, filling in the hidden
			// field, then free-form text is entered after that. if so, the free-form text is what should be used.
			$post['post_author'] = $wp_user_id; // update post_author with the chosen user.
			delete_post_meta( $post['ID'], self::POSTMETA_KEY ); // delete any residual metadata from a free-form field (as inserted below).
			$this->update_media_credit_in_post( $post, true, '', $url );
		} else {
			// Free-form text was entered, insert postmeta with credit.
			// if free-form text is blank, insert a single space in postmeta.
			$freeform = empty( $freeform_name ) ? self::EMPTY_META_STRING : $freeform_name;
			update_post_meta( $post['ID'], self::POSTMETA_KEY, $freeform ); // insert '_media_credit' metadata field for image with free-form text.
			$this->update_media_credit_in_post( $post, false, $freeform, $url );
		}

		return $post;
	}

	/**
	 * If the given media is attached to a post, edit the media-credit info in the attached (parent) post.
	 *
	 * @param object $post     Object of attachment containing all fields from get_post().
	 * @param bool   $wp_user  True if attachment should be credited to a user of this blog, false otherwise.
	 * @param string $freeform Credit for attachment with freeform string. Empty if attachment should be credited to a user of this blog, as indicated by $wp_user above.
	 * @param string $url      Credit URL for linking. Empty means default link for user of this blog, no link for freeform credit.
	 */
	private function update_media_credit_in_post( $post, $wp_user, $freeform = '', $url = '' ) {
		if ( ! empty( $post['post_parent'] ) ) {
			$parent = get_post( $post['post_parent'], ARRAY_A );
			$parent['post_content'] = $this->filter_post_content( $parent['post_content'], $post['ID'], $post['post_author'], $freeform, $url );

			wp_update_post( $parent );
		}
	}

	/**
	 * Add media credit information to media using shortcode notation before sending to editor.
	 *
	 * @param string       $html          The image HTML markup to send.
	 * @param int          $attachment_id The attachment id.
	 * @param string       $caption       The image caption.
	 * @param string       $title         The image title.
	 * @param string       $align         The image alignment.
	 * @param string       $url           The image source URL.
	 * @param string|array $size          Size of image. Image size or array of width and height values (in that order). Default 'medium'.
	 * @param string       $alt           The image alternative, or alt, text.
	 *
	 * @return string
	 */
	public function image_send_to_editor( $html, $attachment_id, $caption, $title, $align, $url, $size, $alt = '' ) {
		$attachment  = get_post( $attachment_id );
		$credit_meta = Media_Credit_Template_Tags::get_freeform_media_credit( $attachment );
		$credit_url  = Media_Credit_Template_Tags::get_media_credit_url( $attachment );
		$options     = get_option( self::OPTION );

		if ( self::EMPTY_META_STRING === $credit_meta ) {
			return $html;
		} elseif ( ! empty( $credit_meta ) ) {
			$credit = 'name="' . $credit_meta . '"';
		} elseif ( empty( $options['no_default_credit'] ) ) {
			$credit = 'id=' . $attachment->post_author;
		} else {
			return $html;
		}

		if ( ! empty( $credit_url ) ) {
			$credit .= ' link="' . $credit_url . '"';
		}

		if ( ! preg_match( '/width="([0-9]+)/', $html, $matches ) ) {
			return $html;
		}

		$width = $matches[1];

		$html = preg_replace( '/(class=["\'][^\'"]*)align(none|left|right|center)\s?/', '$1', $html );
		if ( empty( $align ) ) {
			$align = 'none';
		}

		$shcode = '[media-credit ' . $credit . ' align="align' . $align . '" width="' . $width . '"]' . $html . '[/media-credit]';
		return apply_filters( 'media_add_credit_shortcode', $shcode, $html );
	}

	/**
	 * Filter post content for changed media credits.
	 *
	 * @param string $content   The current post content.
	 * @param int    $image_id  The attachment ID.
	 * @param int    $author_id The author ID.
	 * @param string $freeform  The freeform credit.
	 * @param string $url       The credit URL. Optional. Default ''.
	 *
	 * @return string           The filtered post content.
	 */
	private function filter_post_content( $content, $image_id, $author_id, $freeform, $url = '' ) {
		preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER );

		if ( ! empty( $matches ) ) {
			foreach ( $matches as $shortcode ) {
				if ( 'media-credit' === $shortcode[2] ) {
					$attr             = shortcode_parse_atts( $shortcode[3] );
					$img              = $shortcode[5];
					$image_attributes = wp_get_attachment_image_src( $image_id );
					$image_filename   = $this->get_image_filename_from_full_url( $image_attributes[0] );

					if ( preg_match( '/src=".*' . $image_filename . '/', $img ) &&
						 preg_match( '/wp-image-' . $image_id . '/', $img ) ) {

						if ( $author_id > 0 ) {
							$attr['id'] = $author_id;
							unset( $attr['name'] );
						} else {
							$attr['name'] = $freeform;
							unset( $attr['id'] );
						}

						if ( ! empty( $url ) ) {
							$attr['link'] = $url;
						} else {
							unset( $attr['link'] );
						}

						$new_shortcode = '[media-credit';
						if ( isset( $attr['id'] ) ) {
							$new_shortcode .= ' id=' . $attr['id'];
							unset( $attr['id'] );
						} elseif ( isset( $attr['name'] ) ) {
							$new_shortcode .= ' name="' . $attr['name'] . '"';
							unset( $attr['name'] );
						}
						foreach ( $attr as $name => $value ) {
							$new_shortcode .= ' ' . $name . '="' . $value . '"';
						}
						$new_shortcode .= ']' . $img . '[/media-credit]';

						$content = str_replace( $shortcode[0], $new_shortcode, $content );
					}
				} elseif ( ! empty( $shortcode[5] ) && has_shortcode( $shortcode[5], 'media-credit' ) ) {
					$content = str_replace( $shortcode[5], $this->filter_post_content( $shortcode[5], $image_id, $author_id, $freeform, $url ), $content );
				}
			}
		}

		return $content;
	}

	/**
	 * Add a Settings link for the plugin.
	 *
	 * @param array $links A list of action links.
	 * @return array The modified list of action links.
	 */
	public function add_action_links( $links ) {
		$settings_link = '<a href="options-media.php#media-credit">' . __( 'Settings', 'media-credit' ) . '</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Print HTML for settings section.
	 *
	 * @param array $args The argument array.
	 */
	public function print_settings_section( $args ) {
		?><a name="media-credit"></a><?php
		?><p><?php esc_html_e( 'Choose how to display media credit on your blog:', 'media-credit' ) ?></p><?php
	}

	/**
	 * Print HTML for "separator" input field.
	 *
	 * @param array $args The argument array.
	 */
	public function print_separator_field( $args ) {
		?><input type='text' id='media-credit[separator]' name='media-credit[separator]' value='<?php echo esc_attr( $args['options']['separator'] ) ?>' autocomplete='off' /><?php
		?><label for='media-credit[separator]' style='margin-left:5px'><?php esc_html_e( 'Text used to separate author names from organization when crediting media to users of this blog', 'media-credit' ) ?></label><?php
	}

	/**
	 * Print HTML for "organization" input field.
	 *
	 * @param array $args The argument array.
	 */
	public function print_organization_field( $args ) {
		?><input type='text' name='media-credit[organization]' value='<?php echo esc_attr( $args['options']['organization'] ) ?>' autocomplete='off' /><?php
		?><label for='media-credit[separator]' style='margin-left:5px'><?php esc_html_e( 'Organization used when crediting media to users of this blog', 'media-credit' ) ?></label><?php
	}

	/**
	 * Print HTML for preview area.
	 *
	 * @param array $args The argument array.
	 */
	public function print_preview_field( $args ) {
		$current_user = wp_get_current_user();
		?><span id='preview'><a href='<?php echo esc_url_raw( get_author_posts_url( $current_user->ID ) ) ?>'><?php echo esc_html( $current_user->display_name ) ?></a><?php echo esc_html( $args['options']['separator'] . $args['options']['organization'] ) ?></span><?php
	}

	/**
	 * Print HTML for "display credit at end of post" input field.
	 *
	 * @param array $args The argument array.
	 */
	public function print_end_of_post_field( $args ) {
		?><input type='checkbox' id='media-credit[credit_at_end]' name='media-credit[credit_at_end]' value='1' <?php checked( 1, ! empty( $args['options']['credit_at_end'] ), true ) ?> /><?php
		?><label for='media-credit[credit_at_end]' style='margin-left:5px'><?php esc_html_e( "Display media credit for all the images attached to a post after the post content. Style with CSS class 'media-credit-end'", 'media-credit' ) ?></label><?php

		$curr_user = wp_get_current_user();
		$preview = _nx( 'Image courtesy of %2$s%1$s', 'Images courtesy of %2$s and %1$s', 2,
			'%1$s is always the position of the last credit, %2$s of the concatenated other credits (empty in singular)', 'media-credit' );
		$preview = sprintf( $preview, _x( 'John Smith', 'Example name for preview', 'media-credit' ),
			"<span id='preview'><a href='" . esc_url_raw( get_author_posts_url( $curr_user->ID ) ) . "'>{$curr_user->display_name}</a>{$args['options']['separator']}{$args['options']['organization']}</span>"
			. esc_html_x( ', ', 'String used to join multiple image credits for "Display credit after post"', 'media-credit' )
			. esc_html_x( 'Jane Doe', 'Example name for preview', 'media-credit' ) );

		?><br /><em><?php esc_html_e( 'Preview', 'media-credit' ) ?></em>: <?php echo $preview; // XSS ok.
		?><br /><strong><?php esc_html_e( 'Warning', 'media-credit' ) ?></strong>: <?php esc_html_e( 'This will cause credit for all images in all posts to display at the bottom of every post on this blog', 'media-credit' );
	}

	/**
	 * Print HTML for "no default credit" input field.
	 *
	 * @param array $args The argument array.
	 */
	public function print_no_default_credit_field( $args ) {
		?><input type='checkbox' id='media-credit[no_default_credit]' name='media-credit[no_default_credit]' value='1' <?php checked( 1, ! empty( $args['options']['no_default_credit'] ), true ) ?> /><?php
		?><label for='media-credit[no_default_credit]' style='margin-left:5px'><?php esc_html_e( 'Do not display the attachment author as default credit if it has not been set explicitly (= freeform credits only).', 'media-credit' ) ?></label><?php
	}

	/**
	 * Print HTML for "post thumbnail credit" input field.
	 *
	 * @param array $args The argument array.
	 */
	public function print_post_thumbnail_credit_field( $args ) {
		?><input type='checkbox' id='media-credit[post_thumbnail_credit]' name='media-credit[post_thumbnail_credit]' value='1' <?php checked( 1, ! empty( $args['options']['post_thumbnail_credit'] ), true ) ?> /><?php
		?><label for='media-credit[post_thumbnail_credit]' style='margin-left:5px'><?php esc_html_e( 'Try to add media credit to featured images (depends on theme support).', 'media-credit' ) ?></label><?php
	}

	/**
	 * Sanitize our option values.
	 *
	 * @param  array $input An array of ( $key => $value ).
	 * @return array        The sanitized array.
	 */
	public function sanitize_option_values( $input ) {
		foreach ( $input as $key => $value ) {
			$input[ $key ] = htmlspecialchars( $value, ENT_QUOTES );
		}

		return $input;
	}

	/**
	 * Returns the filename of an image in the wp_content directory (normally, could be any dir really) given the full URL to the image, ignoring WP sizes.
	 * E.g.:
	 * Given http://localhost/wordpress/wp-content/uploads/2010/08/ParksTrip2010_100706_1487-150x150.jpg, returns ParksTrip2010_100706_1487 (ignores size at end of string)
	 * Given http://localhost/wordpress/wp-content/uploads/2010/08/ParksTrip2010_100706_1487-thumb.jpg, return ParksTrip2010_100706_1487-thumb
	 * Given http://localhost/wordpress/wp-content/uploads/2010/08/ParksTrip2010_100706_1487-1.jpg, return ParksTrip2010_100706_1487-1
	 *
	 * @param  string $image Full URL to an image.
	 * @return string        The filename of the image excluding any size or extension, as given in the example above.
	 */
	private function get_image_filename_from_full_url( $image ) {
		$last_slash_pos = strrpos( $image, '/' );
		$image_filename = substr( $image, $last_slash_pos + 1, strrpos( $image, '.' ) - $last_slash_pos - 1 );
		$image_filename = preg_replace( '/(.*)-\d+x\d+/', '$1', $image_filename ); // drop "-{$width}x{$height}".

		return $image_filename;
	}
}
