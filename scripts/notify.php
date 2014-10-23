<?php
# Make sure this script doesn't run via the webserver
if( php_sapi_name() != 'cli' ) {
	echo "It is not allowed to run this script through the webserver.\n";
	exit(1);
}

# This page sends an E-mail if a due date is getting near
# includes all due_dates not met
$ROOT = dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . DIRECTORY_SEPARATOR;
require_once($ROOT . 'core.php');
require_once($ROOT . 'library' . DIRECTORY_SEPARATOR . 'phpmailer' . DIRECTORY_SEPARATOR . 'class.phpmailer.php');

require_once(dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'activity_api.php');

/**
 * EmailData Structure Definition
 * @package    MantisBT
 * @subpackage classes
 */
class ActivityEmailData {
	// properties set during creation
	var $email = '';
	var $subject = '';
	var $body = '';
	var $alt_body = '';
	var $metadata = array(
		'headers' => array(),
	);

	// auto-populated properties
	var $email_id = 0;
	var $submitted = '';
}

function activity_prepare_subject( $p_subject, $p_user_name, $p_date_from ) {
	return str_replace( array('{user}', '{date}'), array($p_user_name, $p_date_from), $p_subject );
}

function activity_prepare_note( $p_note ) {
	$t_note_length = 50;
	$c_note        = str_replace( array("\r\n", "\n"), array(' ', ' '), $p_note );
	return mb_strlen( $c_note ) > $t_note_length ?
		trim( mb_substr( $c_note, 0, $t_note_length ) ) . ' ...'
		: trim( $c_note );
}

function string_get_bug_view_url_with_fqdn2( $p_bug_id, $p_user_id = null ) {
	$t_path = config_get( 'plugin_Activity_notify_path' );
	if( empty($t_path) ) $t_path = config_get( 'path' );
	return $t_path . string_get_bug_view_url( $p_bug_id, $p_user_id );
}

function string_get_bugnote_view_url_with_fqdn2( $p_bug_id, $p_bugnote_id, $p_user_id = null ) {
	$t_path = config_get( 'plugin_Activity_notify_path' );
	if( empty($t_path) ) $t_path = config_get( 'path' );
	return $t_path . string_get_bug_view_url( $p_bug_id, $p_user_id ) . '#c' . $p_bugnote_id;
}


$t_limit         = config_get( 'plugin_Activity_limit_bug_notes' );
$t_login         = config_get( 'plugin_Activity_notify_login' );
$t_subject       = config_get( 'plugin_Activity_notify_subject' );
$t_note_user_ids = config_get( 'plugin_Activity_notify_note_users' );
$t_user_ids      = config_get( 'plugin_Activity_notify_users' );
$t_project_id    = config_get( 'plugin_Activity_notify_project' );
$t_use_html      = config_get( 'plugin_Activity_notify_use_html' );

$t_user_id = null;

$t_project_ids = array();

if( !auth_attempt_script_login( $t_login ) ) {
	die('Authentication failed. Check your plugin settings.');
}

$t_user_id   = user_get_id_by_name( $t_login );
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


$t_date_from = date( 'Y-m-d' ); //, strtotime( '2014-01-14' ) );
$t_date_to   = date( 'Y-m-d' ); //, strtotime( '2014-07-14' ) );

if( !empty($t_note_user_ids) && !empty($t_project_ids) ) foreach ( $t_note_user_ids as $t_user_id ) {
	$t_message            = '';
	$t_html_message       = '';
	$t_user_name          = $t_user_id != null ? user_get_name( $t_user_id ) : lang_get( 'private' );
	$t_subject2           = activity_prepare_subject( $t_subject, $t_user_name, $t_date_from );
	$t_total_issue_size   = 0;
	$t_total_bugnote_size = 0;
	$t_project_size       = 0;

	foreach ( $t_project_ids as $t_project_id ) {
		$t_all_bugnotes = activity_get_latest_bugnotes( $t_project_id, $t_date_from, $t_date_to, $t_user_id, $t_limit );
		$t_group_by_bug = activity_group_by_bug( $t_all_bugnotes );
		$t_issue_size   = count( $t_group_by_bug );
		$t_bugnote_size = count( $t_all_bugnotes );
		if( $t_issue_size == 0 || $t_bugnote_size == 0 ) continue;
		$t_total_issue_size += $t_issue_size;
		$t_total_bugnote_size += $t_bugnote_size;
		$t_project_size++;

		$t_project_name = project_get_name( $t_project_id );
		$t_message .= "\r\nUser: $t_user_name\r\nDate: $t_date_from\r\n";
		$t_message .= "Project: $t_project_name\r\n";
		$t_message .= "Comments: $t_bugnote_size\r\nIssues: $t_issue_size\r\n";

		$t_html_message .= "<br/>\r\n<b>User:</b> $t_user_name<br/>\r\n<b>Date:</b> $t_date_from<br/>\r\n";
		$t_html_message .= "<b>Project:</b> $t_project_name<br/>\r\n";
		$t_html_message .= "<b>Comments:</b> $t_bugnote_size<br/>\r\n<b>Issues:</b> $t_issue_size<br/>\r\n";

		foreach ( $t_group_by_bug as $t_bug_id => $t_bugnotes ) {
			$t_summary         = bug_get_field( $t_bug_id, 'summary' );
			$t_bug_format_id   = bug_format_id( $t_bug_id );
			$t_bug_format_link = '<a href="' . string_get_bug_view_url_with_fqdn2( $t_bug_id ) . '">'
								 . $t_bug_format_id . '</a>';
			$t_message .= "\r\n";
			$t_message .= $t_bug_format_id . ' - ' . $t_summary . "\r\n";
			$t_html_message .= "<br/>";
			$t_html_message .= $t_bug_format_link . ' - ' . $t_summary . "<br/>\r\n";
			foreach ( $t_bugnotes as $t_bugnote ) {
				$t_bugnote_format_id = $t_bug_format_id . ':' . bugnote_format_id( $t_bugnote->id );
				$t_date_submitted    = date( config_get( 'complete_date_format' ), bug_get_field( $t_bug_id, 'date_submitted' ) );
				$t_date_submitted    = date( 'Y-m-d H:i:s', $t_bugnote->date_submitted );
				$t_note              = activity_prepare_note( $t_bugnote->note );
				$t_bugnote_href      = string_get_bugnote_view_url_with_fqdn2( $t_bug_id, $t_bugnote->id );
				$t_bugnote_link      = '<a href="' . $t_bugnote_href . '">' . $t_bugnote_format_id . '</a>';

				$t_message .= '  ' . $t_bugnote_format_id . ' - ' . $t_date_submitted . ' - ' . $t_note . "\r\n";
				$t_html_message .= ' ' . $t_bugnote_link . ' - ' . $t_date_submitted . ' - ' . $t_note . "<br/>\r\n";
			}
		}
	}
	if( !empty($t_message) ) {
		$t_message      = "Total issues: " . $t_total_issue_size . "\r\n" .
						  "Total notes: " . $t_total_bugnote_size . "\r\n" .
						  $t_message;
		$t_html_message = "<html><body><b>Total issues:</b> " . $t_total_issue_size . "<br/>\r\n" .
						  "<b>Total notes:</b> " . $t_total_bugnote_size . "<br/>\r\n" .
						  $t_html_message . '</body></html>';
		//						echo 'Send to: ' . join( ',', $t_emails ) . PHP_EOL;
		//						echo 'Subject: ' . $t_subject2 . PHP_EOL;
		//						echo $t_message . PHP_EOL;
		//		//				echo $t_html_message . PHP_EOL;
		//						echo '__________________________' . PHP_EOL;
		foreach ( $t_emails as $t_email ) {
			if( $t_use_html ) {
				$t_email_data                      = new ActivityEmailData();
				$t_email_data->email               = $t_email;
				$t_email_data->subject             = $t_subject2;
				$t_email_data->body                = $t_html_message;
				$t_email_data->alt_body            = $t_message;
				$t_email_data->metadata['charset'] = 'UTF-8';
				activity_email_send( $t_email_data );
			} else {
				email_store( $t_email, $t_subject2, $t_message );
				if( OFF == config_get( 'email_send_using_cronjob' ) ) {
					email_send_all();
				}
			}
		}
	}
}


/**
 * This function sends an email for html messages.
 * @param ActivityEmailData $p_email_data
 * @return bool
 */
function activity_email_send( $p_email_data ) {
	global $g_phpMailer;

	$t_email_data = $p_email_data;

	$t_recipient   = trim( $t_email_data->email );
	$t_subject     = string_email( trim( $t_email_data->subject ) );
	$t_message     = trim( $t_email_data->body );
	$t_alt_message = string_email_links( trim( $t_email_data->alt_body ) );

	$t_debug_email   = config_get( 'debug_email' );
	$t_mailer_method = config_get( 'phpMailer_method' );

	$t_log_msg = 'ERROR: Message could not be sent - ';

	if( is_null( $g_phpMailer ) ) {
		if( $t_mailer_method == PHPMAILER_METHOD_SMTP ) {
			register_shutdown_function( 'email_smtp_close' );
		}
		$mail = new PHPMailer(true);
	} else {
		$mail = $g_phpMailer;
	}

	if( isset($t_email_data->metadata['hostname']) ) {
		$mail->Hostname = $t_email_data->metadata['hostname'];
	}

	# @@@ should this be the current language (for the recipient) or the default one (for the user running the command) (thraxisp)
	$t_lang = config_get( 'default_language' );
	if( 'auto' == $t_lang ) {
		$t_lang = config_get( 'fallback_language' );
	}
	$mail->SetLanguage( lang_get( 'phpmailer_language', $t_lang ) );

	# Select the method to send mail
	switch( config_get( 'phpMailer_method' ) ) {
		case PHPMAILER_METHOD_MAIL:
			$mail->IsMail();
			break;

		case PHPMAILER_METHOD_SENDMAIL:
			$mail->IsSendmail();
			break;

		case PHPMAILER_METHOD_SMTP:
			$mail->IsSMTP();

			// SMTP collection is always kept alive
			$mail->SMTPKeepAlive = true;

			if( !is_blank( config_get( 'smtp_username' ) ) ) {
				# Use SMTP Authentication
				$mail->SMTPAuth = true;
				$mail->Username = config_get( 'smtp_username' );
				$mail->Password = config_get( 'smtp_password' );
			}

			if( !is_blank( config_get( 'smtp_connection_mode' ) ) ) {
				$mail->SMTPSecure = config_get( 'smtp_connection_mode' );
			}

			$mail->Port = config_get( 'smtp_port' );

			break;
	}

	$mail->IsHTML( true ); # set email format to plain text
	$mail->WordWrap = 100; # set word wrap to 50 characters
	$mail->Priority = $t_email_data->metadata['priority']; # Urgent = 1, Not Urgent = 5, Disable = 0
	$mail->CharSet  = $t_email_data->metadata['charset'];
	$mail->Host     = config_get( 'smtp_host' );
	$mail->From     = config_get( 'from_email' );
	$mail->Sender   = config_get( 'return_path_email' );
	$mail->FromName = config_get( 'from_name' );
	$mail->AddCustomHeader( 'Auto-Submitted:auto-generated' );
	$mail->AddCustomHeader( 'X-Auto-Response-Suppress: All' );

	if( OFF !== $t_debug_email ) {
		$t_message   = 'To: ' . $t_recipient . "\n\n" . $t_message;
		$t_recipient = $t_debug_email;
	}

	try {
		$mail->AddAddress( $t_recipient, '' );
	} catch (phpmailerException $e) {
		log_event( LOG_EMAIL, $t_log_msg . $mail->ErrorInfo );
		$t_success = false;
		$mail->ClearAllRecipients();
		$mail->ClearAttachments();
		$mail->ClearReplyTos();
		$mail->ClearCustomHeaders();
		return $t_success;
	}

	$mail->Subject = $t_subject;
	$mail->Body    = make_lf_crlf( "\n" . $t_message );
	$mail->AltBody = $t_alt_message;

	if( isset($t_email_data->metadata['headers']) && is_array( $t_email_data->metadata['headers'] ) ) {
		foreach ( $t_email_data->metadata['headers'] as $t_key => $t_value ) {
			switch( $t_key ) {
				case 'Message-ID':
					/* Note: hostname can never be blank here as we set metadata['hostname']
					   in email_store() where mail gets queued. */
					if( !strchr( $t_value, '@' ) && !is_blank( $mail->Hostname ) ) {
						$t_value = $t_value . '@' . $mail->Hostname;
					}
					$mail->set( 'MessageID', "<$t_value>" );
					break;
				case 'In-Reply-To':
					$mail->AddCustomHeader( "$t_key: <{$t_value}@{$mail->Hostname}>" );
					break;
				default:
					$mail->AddCustomHeader( "$t_key: $t_value" );
					break;
			}
		}
	}

	try {
		$t_success = $mail->Send();
		if( $t_success ) {
			$t_success = true;

			if( $t_email_data->email_id > 0 ) {
				email_queue_delete( $t_email_data->email_id );
			}
		} else {
			# We should never get here, as an exception is thrown after failures
			log_event( LOG_EMAIL, $t_log_msg . $mail->ErrorInfo );
			$t_success = false;
		}
	} catch (phpmailerException $e) {
		log_event( LOG_EMAIL, $t_log_msg . $mail->ErrorInfo );
		$t_success = false;
	}

	$mail->ClearAllRecipients();
	$mail->ClearAttachments();
	$mail->ClearReplyTos();
	$mail->ClearCustomHeaders();

	return $t_success;
}