<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2019 Peter Putzer.
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

use Media_Credit\Components\Admin;
use Media_Credit\Components\Frontend;
use Media_Credit\Components\Setup;
use Media_Credit\Components\Settings_Page;
use Media_Credit\Components\Shortcodes;

/**
 * Initializes the Media Credit plugin.
 *
 * @since 3.3.0
 */
class Plugin {

	/**
	 * The settings page handler.
	 *
	 * @var Media_Credit\Component[]
	 */
	private $components = [];

	/**
	 * Creates an instance of the plugin controller.
	 *
	 * @param Setup         $setup          The (de-)activation handling.
	 * @param Frontend      $frontend       The frontend.
	 * @param Shortcodes    $shortcodes     The shortcodes handler.
	 * @param Admin         $admin          The backend.
	 * @param Settings_Page $media_settings The Media settings page.
	 */
	public function __construct( Setup $setup, Frontend $frontend, Shortcodes $shortcodes, Admin $admin, Settings_Page $media_settings ) {
		$this->components[] = $setup;
		$this->components[] = $frontend;
		$this->components[] = $shortcodes;
		$this->components[] = $admin;
		$this->components[] = $media_settings;
	}

	/**
	 * Starts the plugin for real.
	 */
	public function run() {
		foreach ( $this->components as $component ) {
			$component->run();
		}
	}
}