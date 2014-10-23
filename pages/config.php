<?php
auth_reauthenticate();
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

html_page_top( plugin_lang_get( 'title' ) );

print_manage_menu();

function activity_print_user_option_list( $p_name ) {
	$t_users     = project_get_all_user_rows();
	$t_selection = plugin_config_get( $p_name );
	$t_size      = count( $t_users );
	if ($t_size > 10) $t_size = 10;

	echo '<select name="' . $p_name . '[]" multiple="multiple" size="' . $t_size . '">';
	foreach ( $t_users as $t_user ) {
		echo '<option value="' . $t_user['id'] . '"';
		check_selected( $t_selection, $t_user['id'] );
		echo '>' . $t_user['username'] . '</option>';
	}
	echo '</select>';
}

?>

	<br/>
	<form action="<?php echo plugin_page( 'config_edit' ) ?>" method="post">
		<?php echo form_security_field( 'plugin_Activity_config_edit' ) ?>

		<table align="center" class="width50" cellspacing="1">

			<tr>
				<td class="form-title" colspan="3">
					<?php echo plugin_lang_get( 'title' ) . ': ' . plugin_lang_get( 'config' ) ?>
				</td>
			</tr>

			<tr <?php echo helper_alternate_class() ?>>
				<td class="category">
					<?php echo plugin_lang_get( 'lbl_show_status_legend' ) ?>
				</td>
				<td class="center">
					<label><input type="radio" name="show_status_legend"
								  value="1" <?php echo (ON == plugin_config_get( 'show_status_legend' )) ? 'checked="checked" ' : '' ?>/>
						<?php echo plugin_lang_get( 'enabled' ) ?></label>
				</td>
				<td class="center">
					<label><input type="radio" name="show_status_legend"
								  value="0" <?php echo (OFF == plugin_config_get( 'show_status_legend' )) ? 'checked="checked" ' : '' ?>/>
						<?php echo plugin_lang_get( 'disabled' ) ?></label>
				</td>
			</tr>
			<tr <?php echo helper_alternate_class() ?>>
				<td class="category">
					<?php echo plugin_lang_get( 'lbl_show_avatar' ) ?>
				</td>
				<td class="center">
					<label><input type="radio" name="show_avatar"
								  value="1" <?php echo (ON == plugin_config_get( 'show_avatar' )) ? 'checked="checked" ' : '' ?>/>
						<?php echo plugin_lang_get( 'enabled' ) ?></label>
				</td>
				<td class="center">
					<label><input type="radio" name="show_avatar"
								  value="0" <?php echo (OFF == plugin_config_get( 'show_avatar' )) ? 'checked="checked" ' : '' ?>/>
						<?php echo plugin_lang_get( 'disabled' ) ?></label>
				</td>
			</tr>
			<tr <?php echo helper_alternate_class() ?>>
				<td class="category">
					<?php echo plugin_lang_get( 'lbl_limit_bug_notes' ) ?>
				</td>
				<td class="center" colspan="2">
					<label><input type="text" name="limit_bug_notes" pattern="[0-9]+"
								  value="<?php echo(plugin_config_get( 'limit_bug_notes' )) ?>"/></label>
				</td>
			</tr>
			<tr <?php echo helper_alternate_class() ?>>
				<td class="category">
					<?php echo plugin_lang_get( 'lbl_day_count' ) ?>
				</td>
				<td class="center" colspan="2">
					<label><input type="text" name="day_count" pattern="[0-9]+"
								  value="<?php echo(plugin_config_get( 'day_count' )) ?>"/></label>
				</td>
			</tr>
			<tr>
				<td colspan="3" class="form-title">
					<?php echo plugin_lang_get( 'lbl_notify' ) ?>
				</td>
			</tr>
			<tr <?php echo helper_alternate_class() ?>>
				<td class="category">
					<?php echo plugin_lang_get( 'lbl_notify_login' ) ?>
				</td>
				<td class="center" colspan="2">
					<label><input type="text" name="notify_login"
								  value="<?php echo(plugin_config_get( 'notify_login' )) ?>"/></label>
				</td>
			</tr>
			<tr <?php echo helper_alternate_class() ?>>
				<td class="category">
					<?php echo plugin_lang_get( 'lbl_notify_subject' ) ?>
				</td>
				<td class="center" colspan="2">
					<label><input type="text" name="notify_subject" maxlength="50" size="50"
								  value="<?php echo(plugin_config_get( 'notify_subject' )) ?>"/></label>
				</td>
			</tr>
			<tr <?php echo helper_alternate_class() ?>>
				<td class="category">
					<?php echo plugin_lang_get( 'lbl_notify_project' ) ?>
				</td>
				<td class="center" colspan="2">
					<label>
						<select name="notify_project">
							<option value="0"><?php echo lang_get( 'all_projects' ); ?></option>
							<?php
							print_project_option_list( plugin_config_get( 'notify_project' ), false, null, false );
							?>
						</select>
					</label>
				</td>
			</tr>
			<tr <?php echo helper_alternate_class() ?>>
				<td class="category">
					<?php echo plugin_lang_get( 'lbl_notify_users' ) ?>
				</td>
				<td class="center" colspan="2">
					<label><?php activity_print_user_option_list( 'notify_users' ); ?></label>
				</td>
			</tr>
			<tr <?php echo helper_alternate_class() ?>>
				<td class="category">
					<?php echo plugin_lang_get( 'lbl_notify_note_users' ) ?>
				</td>
				<td class="center" colspan="2">
					<label><?php activity_print_user_option_list( 'notify_note_users' ); ?></label>
				</td>
			</tr>			
			<tr <?php echo helper_alternate_class() ?>>
				<td class="category">
					<?php echo plugin_lang_get( 'lbl_notify_use_html' ) ?>
				</td>
				<td class="center">
					<label><input type="radio" name="notify_use_html"
								  value="1" <?php echo (ON == plugin_config_get( 'notify_use_html' )) ? 'checked="checked" ' : '' ?>/>
						<?php echo plugin_lang_get( 'enabled' ) ?></label>
				</td>
				<td class="center">
					<label><input type="radio" name="notify_use_html"
								  value="0" <?php echo (OFF == plugin_config_get( 'notify_use_html' )) ? 'checked="checked" ' : '' ?>/>
						<?php echo plugin_lang_get( 'disabled' ) ?></label>
				</td>
			</tr>
			<tr <?php echo helper_alternate_class() ?>>
				<td class="category">
					<?php echo plugin_lang_get( 'lbl_notify_path' ) ?>
				</td>
				<td class="center" colspan="2">
					<label><input type="text" name="notify_path" maxlength="50" size="50"
								  value="<?php echo(plugin_config_get( 'notify_path' )) ?>"/></label>
				</td>
			</tr>			
			<tr>
				<td class="center" colspan="3">
					<input type="submit" class="button" value="<?php echo lang_get( 'change_configuration' ) ?>"/>
				</td>
			</tr>
		</table>
	</form>

<?php
html_page_bottom();
