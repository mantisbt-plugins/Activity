<?php
# Make sure this script doesn't run via the webserver
if( php_sapi_name() != 'cli' ) {
	echo "It is not allowed to run this script through the webserver.\n";
	exit(1);
}

# This page sends an E-mail if a due date is getting near
# includes all due_dates not met
require_once(dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . DIRECTORY_SEPARATOR . 'core.php');
require_once(dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'activity_api.php');

function activity_prepare_subject( $p_subject, $p_user_name, $p_date_from ) {
	return str_replace( array('{user}', '{date}'), array($p_user_name, $p_date_from), $p_subject );
}

function activity_prepare_note ( $p_note ) {
	$t_note_length = 50;
	$c_note = str_replace(array("\r\n", "\n"), array(' ', ' '), $p_note);
	return mb_strlen( $c_note ) > $t_note_length ?
			trim( mb_substr( $c_note, 0, $t_note_length ) ) . ' ...'
			: trim( $c_note );
}


$t_limit         = config_get( 'plugin_Activity_limit_bug_notes' );
$t_login         = config_get( 'plugin_Activity_notify_login' );
$t_subject       = config_get( 'plugin_Activity_notify_subject' );
$t_note_user_ids = config_get( 'plugin_Activity_notify_note_users' );
$t_user_ids      = config_get( 'plugin_Activity_notify_users' );
$t_project_id    = config_get( 'plugin_Activity_notify_project' );
$t_user_id       = null;

$t_project_ids = array();

if (!auth_attempt_script_login( $t_login )) {
	die('Authentication failed. Check your plugin settings.');
}

$t_user_id = user_get_id_by_name( $t_login );
$t_core_path = config_get( 'core_path' );

require_once($t_core_path . 'email_api.php');

if( ALL_PROJECTS == $t_project_id ) {
	$t_topprojects = $t_project_ids = user_get_accessible_projects( $t_user_id );
	foreach ( $t_topprojects as $t_project ) {
		$t_project_ids = array_merge( $t_project_ids, user_get_all_accessible_subprojects( $t_user_id, $t_project ) );
	}

	$t_project_ids_to_check = array_unique( $t_project_ids );
	$t_project_ids          = array();

	foreach ( $t_project_ids_to_check as $t_project_id ) {
		$t_changelog_view_access_level = config_get( 'view_changelog_threshold', null, null, $t_project_id );
		if( access_has_project_level( $t_changelog_view_access_level, $t_project_id ) ) {
			$t_project_ids[] = $t_project_id;
		}
	}
} else {
	$t_project_ids   = array();
	$t_project_ids[] = $t_project_id;
}

$t_emails = array();
foreach ( $t_user_ids as $t_user_id ) {
	$t_email = user_get_email( $t_user_id );
	if( !is_blank( $t_email ) ) $t_emails[] = $t_email;
}


$t_date_from   = date( 'Y-m-d' ); // , strtotime('2014-07-14'));
$t_date_to     = date( 'Y-m-d' ); // , strtotime('2014-07-14'));

if( !empty($t_note_user_ids) && !empty($t_project_ids) ) foreach ( $t_note_user_ids as $t_user_id ) {
	$t_message   = '';
	$t_user_name = $t_user_id != null ? user_get_name( $t_user_id ) : lang_get( 'private' );
	$t_subject2  = activity_prepare_subject( $t_subject, $t_user_name, $t_date_from );

	foreach ( $t_project_ids as $t_project_id ) {
		$t_all_bugnotes = activity_get_latest_bugnotes( $t_project_id, $t_date_from, $t_date_to, $t_user_id, $t_limit );
		$t_group_by_bug = activity_group_by_bug( $t_all_bugnotes );
		$t_issue_size   = count( $t_group_by_bug );
		$t_bugnote_size = count( $t_all_bugnotes );
		if ($t_issue_size == 0 || $t_bugnote_size == 0) continue;

		$t_project_name = project_get_name($t_project_id);
		$t_message .= "\r\nUser: $t_user_name\r\nDate: $t_date_from\r\n";
		$t_message .= "Project: $t_project_name\r\n";
		$t_message .= "Comments: $t_bugnote_size\r\nIssues: $t_issue_size\r\n";
		foreach ( $t_group_by_bug as $t_bug_id => $t_bugnotes ) {
			$t_summary       = bug_get_field( $t_bug_id, 'summary' );
			$t_bug_format_id = bug_format_id( $t_bug_id );
			$t_message .= "\r\n";
			$t_message .= $t_bug_format_id . ' - ' . $t_summary . "\r\n";
			foreach ( $t_bugnotes as $t_bugnote ) {
				$t_bugnote_format_id = $t_bug_format_id . ':' . bugnote_format_id( $t_bugnote->id );
				$t_date_submitted    = date( config_get( 'complete_date_format' ), bug_get_field( $t_bug_id, 'date_submitted' ) );
				$t_date_submitted    = date( 'Y-m-d H:i:s', $t_bugnote->date_submitted );
				$t_note              = activity_prepare_note($t_bugnote->note);
				$t_message .= '  ' . $t_bugnote_format_id . ' - ' . $t_date_submitted . ' - ' . $t_note . "\r\n";
			}
		}
	}
	if( !empty($t_message) ) {
//		echo 'Send to: ' . join( ',', $t_emails ) . PHP_EOL;
//		echo 'Subject: ' . $t_subject2 . PHP_EOL;
//		echo $t_message . PHP_EOL;
		foreach ($t_emails as $t_email) {
			email_store( $t_email, $t_subject2, $t_message );
			if( OFF == config_get( 'email_send_using_cronjob' ) ) {
				email_send_all();
			}
		}
	}
}
