<?php
# MantisBT - a php based bugtracking system
# Copyright (C) 2002 - 2014  MantisBT Team - mantisbt-dev@lists.sourceforge.net
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

form_security_validate( 'plugin_Activity_config_edit' );

auth_reauthenticate( );
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

$f_show_status_legend = gpc_get_int( 'show_status_legend', plugin_config_get('show_status_legend') );
$f_limit_bug_notes    = gpc_get_int( 'limit_bug_notes', plugin_config_get('limit_bug_notes'));
$f_day_count          = gpc_get_int( 'day_count', plugin_config_get('day_count'));

if ($f_limit_bug_notes < 1) $f_limit_bug_notes = 1;
if ($f_day_count < 1) $f_day_count = 1;

if( plugin_config_get( 'show_status_legend' ) != $f_show_status_legend ) {
    plugin_config_set( 'show_status_legend', $f_show_status_legend );
}

if( plugin_config_get( 'limit_bug_notes' ) != $f_limit_bug_notes ) {
    plugin_config_set( 'limit_bug_notes', $f_limit_bug_notes );
}

if( plugin_config_get( 'day_count' ) != $f_day_count ) {
    plugin_config_set( 'day_count', $f_day_count );
}



form_security_purge( 'plugin_Activity_config_edit' );

print_successful_redirect( plugin_page( 'config', true ) );
