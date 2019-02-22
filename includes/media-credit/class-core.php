<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2013-2019 Peter Putzer.
 * Copyright 2010-2011 Scott Bressler.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  ***
 *
 * @package mundschenk-at/media-credit
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Media_Credit;

use Media_Credit\Data_Storage\Options;

/**
 * The main API for the Media Credit plugin. To allow for static template functions,
 * it is instantiated as a singleton.
 *
 * The class provides access to the plugin settings and utility methods for manipulating
 * the postmeta data making up the credit information for individual attachments.
 *
 * @since 3.3.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Core {

	/**
	 * The string stored in the database when the credit meta is empty.
	 *
	 * @var string
	 */
	const EMPTY_META_STRING = ' ';

	/**
	 * The key used for storing the media credit in postmeta.
	 *
	 * @var string
	 */
	const POSTMETA_KEY = '_media_credit';

	/**
	 * The key used for storing the optional media credit URL in postmeta.
	 *
	 * @var string
	 */
	const URL_POSTMETA_KEY = '_media_credit_url';

	/**
	 * The key used for storing optional media credit data in postmeta.
	 *
	 * @var string
	 */
	const DATA_POSTMETA_KEY = '_media_credit_data';

	/**
	 * The singleton instance.
	 *
	 * @var Core
	 */
	private static $instance;

	/**
	 * The plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * The options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * The default settings.
	 *
	 * @var Settings
	 */
	private $settings_template;

	/**
	 * The cached plugin settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Creates a new instance.
	 *
	 * @param string   $version           The plugin version string (e.g. "3.0.0-beta.2").
	 * @param Options  $options           The options handler.
	 * @param Settings $settings_template The default settings template.
	 */
	public function __construct( $version, Options $options, Settings $settings_template ) {
		$this->version           = $version;
		$this->options           = $options;
		$this->settings_template = $settings_template;
	}

	/**
	 * Sets this API instance as the plugin singleton. Should not be called outside of plugin set-up.
	 *
	 * @internal
	 *
	 * @throws \BadMethodCallException Thrown when Media_Credit\Core::make_singleton is called after plugin initialization.
	 */
	public function make_singleton() {
		if ( null !== self::$instance ) {
			throw new \BadMethodCallException( __METHOD__ . ' called more than once.' );
		}

		self::$instance = $this;
	}

	/**
	 * Retrieves the plugin API instance.
	 *
	 * @throws \BadMethodCallException Thrown when Media_Credit\Core::get_instance is called before plugin initialization.
	 *
	 * @return Core
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			throw new \BadMethodCallException( __METHOD__ . ' called without prior plugin intialization.' );
		}

		return self::$instance;
	}

	/**
	 * Retrieves the plugin version.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Retrieves the plugin settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		if ( empty( $this->settings ) ) {
			$this->settings = $this->options->get( Options::OPTION, [] );
		}

		return $this->settings;
	}

	/**
	 * If the given media is attached to a post, edit the media-credit info in the attached (parent) post.
	 *
	 * @param int|\WP_Post $attachment An attachemnt ID or the corresponding \WP_Post object.
	 * @param string       $freeform   Credit for attachment with freeform string. Empty if attachment should be credited to the attachment author.
	 * @param string       $url        Credit URL for linking. Empty means default link for user of this blog, no link for freeform credit.
	 */
	public function update_media_credit_in_post( $attachment, $freeform = '', $url = '' ) {

		// Make sure we are dealing with a post object.
		if ( ! $attachment instanceof \WP_Post ) {
			$attachment = \get_post( $attachment );
		}

		if ( ! empty( $attachment->post_parent ) ) {
			// Get the parent post of the attachment.
			$post = \get_post( $attachment->post_parent );

			// Filter the post's content.
			$post->post_content = $this->filter_changed_media_credits( $post->post_content, $attachment->ID, (int) $attachment->post_author, $freeform, $url );

			// Save the filtered content in the database.
			\wp_update_post( $post );
		}
	}

	/**
	 * Filters post content for changed media credits.
	 *
	 * @param string $content   The current post content.
	 * @param int    $image_id  The attachment ID.
	 * @param int    $author_id The author ID.
	 * @param string $freeform  The freeform credit.
	 * @param string $url       The credit URL. Optional. Default ''.
	 *
	 * @return string           The filtered post content.
	 */
	public function filter_changed_media_credits( $content, $image_id, $author_id, $freeform, $url = '' ) {

		// Get the image source URL.
		$src = \wp_get_attachment_image_src( $image_id );
		if ( empty( $src[0] ) ) {
			// Invalid image ID.
			return $content;
		}

		// Extract the image basename without the size for use in a regular expression.
		$filename = \preg_quote( $this->get_image_filename_from_full_url( $src[0] ), '/' );

		// Look at every matching shortcode.
		\preg_match_all( '/' . \get_shortcode_regex( [ 'media-credit' ] ) . '/Ss', $content, $matches, PREG_SET_ORDER );

		foreach ( $matches as $shortcode ) {

			// Grab the shortcode attributes ...
			$attr = \shortcode_parse_atts( $shortcode[3] );
			$attr = $attr ?: [];

			// ... and the contained <img> tag.
			$img = $shortcode[5];

			if ( ! \preg_match( "/src=([\"'])(?:(?!\1).)*{$filename}/S", $img ) || ! \preg_match( "/wp-image-{$image_id}/S", $img ) ) {
				// This shortcode is for another image.
				continue;
			}

			// Check for credit type.
			if ( $author_id > 0 ) {
				// The new credit should use the ID.
				$id_or_name = "id={$author_id}";
			} else {
				// No valid ID, so use the freeform credit.
				$id_or_name = "name=\"{$freeform}\"";
			}

			// Drop the old id/name attributes (if any).
			unset( $attr['id'] );
			unset( $attr['name'] );

			// Update link attribute.
			if ( ! empty( $url ) ) {
				$attr['link'] = $url;
			} else {
				unset( $attr['link'] );
			}

			// Start reconstructing the shortcode.
			$new_shortcode = "[media-credit {$id_or_name}";

			// Add the rest of the attributes.
			foreach ( $attr as $name => $value ) {
				$new_shortcode .= " {$name}=\"{$value}\"";
			}

			// Finish up with the closing bracket and the <img> content.
			$new_shortcode .= ']' . $img . '[/media-credit]';

			// Replace the old shortcode with then new one.
			$content = \str_replace( $shortcode[0], $new_shortcode, $content );
		}

		return $content;
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
	protected function get_image_filename_from_full_url( $image ) {
		// Drop "-{$width}x{$height}".
		return \preg_replace( '/(.*?)(\-\d+x\d+)?\.\w+/S', '$1', \wp_basename( $image ) );
	}
}
