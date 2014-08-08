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
 * @package   MantisBT
 * @link      http://www.mantisbt.org
 */
/**
 * MantisBT Core API's
 */
require_once('core.php');

require_once('bug_api.php');
require_once('bugnote_api.php');
require_once('icon_api.php');
require_once('activity_api.php');


$t_today     = date ( 'd:m:Y' );
$t_day_count = plugin_config_get ( 'day_count' );
$t_from_day  = date ( 'd:m:Y', strtotime ( date ( 'Y-m-d' ) ) - SECONDS_PER_DAY * ($t_day_count - 1) );

function format_date_submitted( $p_date_submitted ) {
    global $t_today;
    $c_date   = date ( 'd:m:Y', $p_date_submitted );
    $c_format = $t_today == $c_date ? 'H:i:s' : 'd.m.y';
    return date ( $c_format, $p_date_submitted );
}

function string_get_bugnote_view_link2( $p_bug_id, $p_bugnote_id, $p_user_id = null, $p_detail_info = true, $p_fqdn = false ) {
    $t_bug_id = (int)$p_bug_id;

    if (bug_exists ( $t_bug_id ) && bugnote_exists ( $p_bugnote_id )) {
        $t_link = '<a href="';
        if ($p_fqdn) {
            $t_link .= config_get_global ( 'path' );
        } else {
            $t_link .= config_get_global ( 'short_path' );
        }

        $t_link .= string_get_bugnote_view_url ( $p_bug_id, $p_bugnote_id, $p_user_id ) . '"';
        if ($p_detail_info) {
            $t_reporter    = string_attribute ( user_get_name ( bugnote_get_field ( $p_bugnote_id, 'reporter_id' ) ) );
            $t_update_date = string_attribute ( date ( config_get ( 'normal_date_format' ), (bugnote_get_field ( $p_bugnote_id, 'last_modified' )) ) );
            $t_link .= ' title="' . bug_format_id ( $t_bug_id ) . ': [' . $t_update_date . '] ' . $t_reporter . '"';
        }

        $t_link .= '>' . bugnote_format_id ( $p_bugnote_id ) . '</a>';
    } else {
        $t_link = bugnote_format_id ( $p_bugnote_id );
    }

    return $t_link;
}

/**
 * @param $p_group BugnoteData[]
 * @return bool
 */
function is_empty_group( $p_group ) {
    foreach ( $p_group as $t_bugnote ) {
        $t_note = trim ( $t_bugnote->note );
        if (!empty($t_note)) return false;
    }
    return true;
}


function activity_print_styles() {
    return '<style type="text/css" >
            .activity-item {
                margin-left: 1px;
            }

            .activity-date, .activity-avatar {
                color: gray;
                font-size: 8pt;
                padding: 5px;
            }
            .activity-note {
                border: 1px solid #C8C8E8;
                background-color: #E8E8E8;
                -webkit-border-radius: 4px;
                -moz-border-radius: 4px;
                border-radius: 4px;
                max-width: 658px;
                overflow-x: hidden;
            }
            td.activity-center {
                text-align: center;
            }
            </style>';
}


$t_user_id = auth_get_current_user_id ();

$f_project = gpc_get_string ( 'project', '' );
$f_page    = gpc_get_string ( 'page', '' );

if (is_blank ( $f_project )) {
    $f_project_id = gpc_get_int ( 'project_id', -1 );
} else {
    $f_project_id = project_get_id_by_name ( $f_project );
    if ($f_project_id === 0) {
        trigger_error ( ERROR_PROJECT_NOT_FOUND, ERROR );
    }
}


if ($f_project_id == -1) {
    $t_project_id = helper_get_current_project ();
} else {
    $t_project_id = $f_project_id;
}

if (ALL_PROJECTS == $t_project_id) {
    $t_topprojects = $t_project_ids = user_get_accessible_projects ( $t_user_id );
    foreach ( $t_topprojects as $t_project ) {
        $t_project_ids = array_merge ( $t_project_ids, user_get_all_accessible_subprojects ( $t_user_id, $t_project ) );
    }

    $t_project_ids_to_check = array_unique ( $t_project_ids );
    $t_project_ids          = array();

    foreach ( $t_project_ids_to_check as $t_project_id ) {
        $t_changelog_view_access_level = config_get ( 'view_changelog_threshold', null, null, $t_project_id );
        if (access_has_project_level ( $t_changelog_view_access_level, $t_project_id )) {
            $t_project_ids[] = $t_project_id;
        }
    }
} else {
    access_ensure_project_level ( config_get ( 'view_changelog_threshold' ), $t_project_id );
    $t_project_ids = user_get_all_accessible_subprojects ( $t_user_id, $t_project_id );
    array_unshift ( $t_project_ids, $t_project_id );
}

event_hook ( 'EVENT_LAYOUT_RESOURCES', 'activity_print_styles' );

html_page_top ( plugin_lang_get ( 'activity' ) );


$t_project_index = 0;

category_cache_array_rows_by_project ( $t_project_ids );
$t_project_ids_size = count ( $t_project_ids );
echo '<br/>';

$t_stats_from_def    = $t_from_day;
$t_stats_from_def_ar = explode ( ":", $t_stats_from_def );
$t_stats_from_def_d  = $t_stats_from_def_ar[0];
$t_stats_from_def_m  = $t_stats_from_def_ar[1];
$t_stats_from_def_y  = $t_stats_from_def_ar[2];

$t_stats_from_d = gpc_get_int ( 'start_day', $t_stats_from_def_d );
$t_stats_from_m = gpc_get_int ( 'start_month', $t_stats_from_def_m );
$t_stats_from_y = gpc_get_int ( 'start_year', $t_stats_from_def_y );

$t_stats_to_def    = $t_today;
$t_stats_to_def_ar = explode ( ":", $t_stats_to_def );
$t_stats_to_def_d  = $t_stats_to_def_ar[0];
$t_stats_to_def_m  = $t_stats_to_def_ar[1];
$t_stats_to_def_y  = $t_stats_to_def_ar[2];

$t_stats_to_d = gpc_get_int ( 'end_day', $t_stats_to_def_d );
$t_stats_to_m = gpc_get_int ( 'end_month', $t_stats_to_def_m );
$t_stats_to_y = gpc_get_int ( 'end_year', $t_stats_to_def_y );

$t_from = "$t_stats_from_y-$t_stats_from_m-$t_stats_from_d";
$t_to   = "$t_stats_to_y-$t_stats_to_m-$t_stats_to_d";

?>
<form method="post" action="<?php echo string_attribute ( plugin_page ( 'activity_page' ) ) ?>">
    <table border="0" class="width100" cellspacing="0">
        <tr class="row-2">
            <td class="category" width="25%">
                <?php
                $t_filter                      = array();
                $t_filter['do_filter_by_date'] = 'on';
                $t_filter['start_day']         = $t_stats_from_d;
                $t_filter['start_month']       = $t_stats_from_m;
                $t_filter['start_year']        = $t_stats_from_y;
                $t_filter['end_day']           = $t_stats_to_d;
                $t_filter['end_month']         = $t_stats_to_m;
                $t_filter['end_year']          = $t_stats_to_y;
                print_filter_do_filter_by_date ( true );
                ?>
            </td>
        </tr>
        <tr>
            <td class="center" colspan="2">
                <input type="submit" class="button"
                       name="get_bugnote_stats_button"
                       value="<?php echo plugin_lang_get ( 'get_info_button' ) ?>"
                    />
            </td>
        </tr>
    </table>
</form>



<?php

$t_status_legend_position = config_get ( 'status_legend_position' );
$t_show_status_legend     = plugin_config_get ( 'show_status_legend' );
$t_show_avatar            = plugin_config_get ( 'show_avatar', config_get ( 'show_avatar', OFF ) );
$t_limit_bug_notes        = (int)plugin_config_get ( 'limit_bug_notes', 1000 );
$t_update_bug_threshold   = config_get ( 'update_bug_threshold' );
$t_icon_path              = config_get ( 'icon_path' );
$t_show_priority_text     = config_get ( 'show_priority_text' );

if ($t_show_status_legend && ($t_status_legend_position == STATUS_LEGEND_POSITION_TOP || $t_status_legend_position == STATUS_LEGEND_POSITION_BOTH)) {
    html_status_legend ();
    echo '<br />';
}


foreach ( $t_project_ids as $t_project_id ) {

    $t_bugnotes     = activity_get_latest_bugnotes ( $t_project_id, $t_from, $t_to, $t_limit_bug_notes );
    $t_bugnote_size = count ( $t_bugnotes );
    if ($t_project_ids_size != 1 && $t_bugnote_size > 0) {
        $t_project_name      = project_get_field ( $t_project_id, 'name' );
        $t_project_name_link = '<a href="' . plugin_page ( 'activity_page' ) . '&project_id=' . $t_project_id . '">' . $t_project_name . '</a>';
        echo '<h3 style="text-align: center">' . $t_project_name_link . '</h3><hr/>';
    }

    $t_group_by_bug = array();
    foreach ( $t_bugnotes as $t_bugnote ) {
        if (empty($t_group_by_bug[$t_bugnote->bug_id])) $t_group_by_bug[$t_bugnote->bug_id] = array();

        $t_group_by_bug[$t_bugnote->bug_id][] = $t_bugnote;
    }

    foreach ( $t_group_by_bug as $t_bug_id => $t_group ) {
        if (!empty($t_group) && !is_empty_group ( $t_group )) {
            $t_summary          = bug_get_field ( $t_bug_id, 'summary' );
            $t_status_color     = get_status_color ( bug_get_field ( $t_bug_id, 'status' ), $t_user_id, $t_project_id );
            $t_date_submitted   = date ( config_get ( 'complete_date_format' ), bug_get_field ( $t_bug_id, 'date_submitted' ) );
            $t_background_color = 'background-color: ' . $t_status_color;

            echo '<div align="center">', '<table cellspacing="0" class="width75"><tbody>', '<tr><td class="news-heading-public activity-center" width="9%" style="' . $t_background_color . '">';
            print_bug_link ( $t_bug_id, true );
            echo '<br/>';

            if (!bug_is_readonly ( $t_bug_id ) && access_has_bug_level ( $t_update_bug_threshold, $t_bug_id )) echo '<a href="' . string_get_bug_update_url ( $t_bug_id ) . '"><img border="0" src="' . $t_icon_path . 'update.png' . '" alt="' . lang_get ( 'update_bug_button' ) . '" /></a>';

            if (ON == $t_show_priority_text) {
                print_formatted_priority_string ( $t_bug_id );
            } else {
                print_status_icon ( bug_get_field ( $t_bug_id, 'priority' ) );
            }

            echo '</td><td class="news-heading-public" style="' . $t_background_color . '"><span class="bold">' . $t_summary . '</span> - <span class="italic-small">' . $t_date_submitted . '</span>', '</td></tr>';


            foreach ( $t_group as $t_bugnote ) {
                $t_date_submitted = format_date_submitted ( $t_bugnote->date_submitted );
                $t_user_id        = VS_PRIVATE == $t_bugnote->view_state ? null : $t_bugnote->reporter_id;
                $t_user_name      = $t_user_id != null ? user_get_name ( $t_user_id ) : lang_get ( 'private' );
                $t_user_link      = $t_user_id != null ? '<a href="view_user_page.php?id=' . $t_user_id . '">' . $t_user_name . '</a>' : $t_user_name;
                $t_note           = string_display_links ( trim ( $t_bugnote->note ) );
                $t_bugnote_link   = string_get_bugnote_view_link2 ( $t_bugnote->bug_id, $t_bugnote->id, $t_user_id );
                if (!empty($t_note)) {
                    echo '<tr><td style="vertical-align: top; text-align: center;"><div class="activity-date">', $t_date_submitted, '</div>', '<div class="activity-avatar">';
                    if ($t_show_avatar && !empty($t_user_id)) print_avatar ( $t_user_id, 60 );
                    echo '</div></td>';
                    echo '<td style="vertical-align: top;"><div class="activity-item">', '<span class="bold">', $t_user_link, '</span> (', $t_bugnote_link, ')</div>', '<div class="activity-note">', $t_note, '</div>', '</div></td></tr>';
                }
            }
            echo   '</table>', '</div>';
        }
    }
}


if ($t_show_status_legend && ($t_status_legend_position == STATUS_LEGEND_POSITION_BOTTOM || $t_status_legend_position == STATUS_LEGEND_POSITION_BOTH)) {
    html_status_legend ();
}

html_page_bottom ();
