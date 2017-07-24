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
 * Activity plugin
 * @package    MantisPlugin
 * @subpackage MantisPlugin
 * @link       http://www.mantisbt.org
 */

/**
 * requires MantisPlugin.class.php
 */
require_once(config_get( 'class_path' ) . 'MantisPlugin.class.php');

/**
 * Activity Class
 */
class ActivityPlugin extends MantisPlugin {

	/**
	 *  A method that populates the plugin information and minimum requirements.
	 */
	function register() {
		$this->name        = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );
		$this->page        = 'config';

		$this->version  = '1.0';
		$this->requires = array('MantisCore' => '2.0.0',);

		$this->author  = 'Sergey Marchenko';
		$this->contact = 'sergey@mzsl.ru';
		$this->url     = 'http://zetabyte.ru';
	}

	/**
	 * Default plugin configuration.
	 */
	function hooks() {
		$hooks = array('EVENT_MENU_MAIN' => 'menu',
					   'EVENT_LAYOUT_RESOURCES' => 'resources');

		return $hooks;
	}

	function menu() {
		$links = array();
		$links[] = array(
			'title' => plugin_lang_get( 'activity' ),
			'url'   => plugin_page( 'activity_page' ),
			'icon'  => 'fa-list'
		);
		return $links;
	}

	function init() {
		$t_path = config_get_global( 'plugin_path' ) . plugin_get_current() . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR;
		set_include_path( get_include_path() . PATH_SEPARATOR . $t_path );
	}

	function config() {
		return array('show_status_legend' => ON,
					 'limit_bug_notes' => 500,
					 'day_count' => 1,
					 'show_avatar' => OFF,
					 'notify_login' => 'admin',
					 'notify_subject' => 'Mantis report: {user}, {date}',
					 'notify_project' => 0,
					 'notify_users' => array(),
					 'notify_note_users' => array(),
					 'notify_use_html' => OFF,
					 'notify_path' => ''
		);
	}

	/**
	 * Create the resource link to load the jQuery library.
	 */
	function resources( $p_event ) {
		return '<link rel="stylesheet" type="text/css" href="' . plugin_file( 'activity.css' ) . '"/>' .
                '<script src="' . plugin_file( 'activity.js' ) . '"></script>';
	}

}
