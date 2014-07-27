<?php
# MantisBT - a php based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Activity page
 * @package MantisPlugin
 * @subpackage MantisPlugin
 * @link http://www.mantisbt.org
 */

/**
 * requires MantisPlugin.class.php
 */
require_once( config_get( 'class_path' ) . 'MantisPlugin.class.php' );

/**
 * Activity Class
 */
class ActivityPlugin extends MantisPlugin {

	/**
	 *  A method that populates the plugin information and minimum requirements.
	 */
	function register( ) {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );
		$this->page = '';

		$this->version = '1.0';
		$this->requires = array(
			'MantisCore' => '1.2.0',
		);

		$this->author = 'Sergey Marchenko';
		$this->contact = 'sergey@mzsl.ru';
		$this->url = 'http://zetabyte.ru';
	}

	/**
	 * Default plugin configuration.
	 */
	function hooks( ) {
		$hooks = array(
			'EVENT_MENU_MAIN' => 'activity_menu',
		);
		return $hooks;
	}

	function activity_menu( ) {
		return array( '<a href="' . plugin_page( 'activity_page' ) . '">' . plugin_lang_get( 'activity' ) . '</a>', );
	}

	function install() {
//		$result = extension_loaded("xmlreader") && extension_loaded("xmlwriter");
//		if ( ! $result ) {
//			#\todo returning false should trigger some error reporting, needs rethinking error_api
//			error_parameters( plugin_lang_get( 'error_no_xml' ) );
//			trigger_error( ERROR_PLUGIN_INSTALL_FAILED, ERROR );
//		}
//		return $result;
        return true;
	}
}
