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

if ( ! \function_exists( 'get_media_credit' ) ) {
	/**
	 * Template tag to return the media credit as plain text for some media attachment.
	 *
	 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
	 */
	function get_media_credit( $post = null ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- legacy API.
		return Media_Credit::get_plaintext( $post );
	}
}

if ( ! \function_exists( 'the_media_credit' ) ) {
	/**
	 * Template tag to print the media credit as plain text for some media attachment.
	 *
	 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
	 */
	function the_media_credit( $post = null ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- legacy API.
		echo \esc_html( \get_media_credit( $post ) );
	}
}

if ( ! \function_exists( 'get_media_credit_url' ) ) {
	/**
	 * Template tag to return the media credit URL as plain text for some media attachment.
	 *
	 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
	 */
	function get_media_credit_url( $post = null ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- legacy API.
		return Media_Credit::get_url( $post );
	}
}

if ( ! \function_exists( 'the_media_credit_url' ) ) {
	/**
	 * Template tag to print the media credit URL as plain text for some media attachment.
	 *
	 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
	 */
	function the_media_credit_url( $post = null ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- legacy API.
		echo \esc_url_raw( \get_media_credit_url( $post ) );
	}
}

if ( ! function_exists( 'get_media_credit_html' ) ) {
	/**
	 * Template tag to return the media credit as HTML with a link to the author page if one exists for some media attachment.
	 *
	 * @param int|object $post       Optional post ID or object of attachment. Default is global $post object.
	 * @param bool       $deprecated Optional. Deprecated argument. Default true.
	 */
	function get_media_credit_html( $post = null, /* @scrutinizer ignore-unused */ $deprecated = true ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- legacy API.
		return Media_Credit::get_html( $post );
	}
}

if ( ! \function_exists( 'the_media_credit_html' ) ) {
	/**
	 * Template tag to print the media credit as HTML with a link to the author page if one exists for some media attachment.
	 *
	 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
	 */
	function the_media_credit_html( $post = null ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- legacy API.
		echo \get_media_credit_html( $post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped.
	}
}

if ( ! \function_exists( 'get_media_credit_html_by_user_id' ) ) {
	/**
	 * Template tag to return the media credit as HTML with a link to the author page if one exists for a WordPress user.
	 *
	 * @param int $id User ID of a WordPress user.
	 */
	function get_media_credit_html_by_user_id( $id ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- legacy API.
		return Media_Credit::get_html_by_user_id( $id );
	}
}

if ( ! \function_exists( 'the_media_credit_html_by_user_id' ) ) {
	/**
	 * Template tag to print the media credit as HTML with a link to the author page if one exists for a WordPress user.
	 *
	 * @param int $id User ID of a WordPress user.
	 */
	function the_media_credit_html_by_user_id( $id ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- legacy API.
		echo \get_media_credit_html_by_user_id( $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped.
	}
}


if ( ! \function_exists( 'display_author_media' ) ) {
	/**
	 * Template tag to display the recently added media attachments for given author.
	 *
	 * @param int     $author_id           The user ID of the author.
	 * @param boolean $sidebar             Display as sidebar or inline. Optional. Default true.
	 * @param int     $limit               Optional. Default 10.
	 * @param boolean $link_without_parent Optional. Default false.
	 * @param string  $header              HTML-formatted heading. Optional. Default <h3>Recent Media</h3> (translated).
	 * @param boolean $exclude_unattached  Optional. Default true.
	 */
	function display_author_media( $author_id, $sidebar = true, $limit = 10, $link_without_parent = false, $header = null, $exclude_unattached = true ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- legacy API.
		Media_Credit::display_author_media( $author_id, $sidebar, $limit, $link_without_parent, $header, $exclude_unattached );
	}
}

if ( ! \function_exists( 'author_media_and_posts' ) ) {
	/**
	 * Template tag to return the recently added media attachments and posts for a given author.
	 *
	 * @param int     $author_id          The user ID of the author.
	 * @param boolean $include_posts      Optional. Default true.
	 * @param int     $limit              Optional. Default 0.
	 * @param boolean $exclude_unattached Optional. Default true.
	 */
	function author_media_and_posts( $author_id, $include_posts = true, $limit = 0, $exclude_unattached = true ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- legacy API.
		return Media_Credit::author_media_and_posts( $author_id, $include_posts, $limit, $exclude_unattached );
	}
}

if ( ! \function_exists( 'author_media' ) ) {
	/**
	 * Returns the recently added media attachments for a given author.
	 *
	 * @param  int  $author_id          The user ID of the author.
	 * @param  int  $limit              Optional. The upper limit to the number of returned posts. Default 0 (no limit).
	 * @param  bool $exclude_unattached Optional. Flag indicating if media not attached to a post should be included. Default true.
	 *
	 * @return array
	 */
	function author_media( $author_id, $limit = 0, $exclude_unattached = true ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- legacy API.
		return Media_Credit::author_media_and_posts( $author_id, false, $limit, $exclude_unattached );
	}
}
