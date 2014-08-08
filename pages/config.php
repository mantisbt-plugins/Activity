<?php
auth_reauthenticate( );
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

html_page_top( plugin_lang_get( 'title' ) );

print_manage_menu( );

?>

<br />
<form action="<?php echo plugin_page( 'config_edit' )?>" method="post">
    <?php echo form_security_field( 'plugin_Activity_config_edit' ) ?>

    <table align="center" class="width50" cellspacing="1">

        <tr>
            <td class="form-title" colspan="3">
                <?php echo plugin_lang_get( 'title' ) . ': ' . plugin_lang_get( 'config' )?>
            </td>
        </tr>

        <tr <?php echo helper_alternate_class( )?>>
            <td class="category">
                <?php echo plugin_lang_get( 'lbl_show_status_legend' )?>
            </td>
            <td class="center">
                <label><input type="radio" name="show_status_legend" value="1" <?php echo( ON == plugin_config_get( 'show_status_legend' ) ) ? 'checked="checked" ' : ''?>/>
                    <?php echo plugin_lang_get( 'enabled' )?></label>
            </td>
            <td class="center">
                <label><input type="radio" name="show_status_legend" value="0" <?php echo( OFF == plugin_config_get( 'show_status_legend' ) ) ? 'checked="checked" ' : ''?>/>
                    <?php echo plugin_lang_get( 'disabled' )?></label>
            </td>
        </tr>
        <tr <?php echo helper_alternate_class( )?>>
            <td class="category">
                <?php echo plugin_lang_get( 'lbl_show_avatar' )?>
            </td>
            <td class="center">
                <label><input type="radio" name="show_avatar" value="1" <?php echo( ON == plugin_config_get( 'show_avatar' ) ) ? 'checked="checked" ' : ''?>/>
                    <?php echo plugin_lang_get( 'enabled' )?></label>
            </td>
            <td class="center">
                <label><input type="radio" name="show_avatar" value="0" <?php echo( OFF == plugin_config_get( 'show_avatar' ) ) ? 'checked="checked" ' : ''?>/>
                    <?php echo plugin_lang_get( 'disabled' )?></label>
            </td>
        </tr>
        <tr <?php echo helper_alternate_class( )?>>
            <td class="category">
                <?php echo plugin_lang_get( 'lbl_limit_bug_notes' )?>
            </td>
            <td class="center" colspan="2">
                <label><input type="text" name="limit_bug_notes" pattern="[0-9]+" value="<?php echo( plugin_config_get( 'limit_bug_notes' ) )?>"/></label>
            </td>
        </tr>
        <tr <?php echo helper_alternate_class( )?>>
            <td class="category">
                <?php echo plugin_lang_get( 'lbl_day_count' )?>
            </td>
            <td class="center" colspan="2">
                <label><input type="text" name="day_count" pattern="[0-9]+" value="<?php echo( plugin_config_get( 'day_count' ) )?>"/></label>
            </td>
        </tr>
        <tr>
            <td class="center" colspan="3">
                <input type="submit" class="button" value="<?php echo lang_get( 'change_configuration' )?>" />
            </td>
        </tr>

    </table>
</form>

<?php
html_page_bottom();
