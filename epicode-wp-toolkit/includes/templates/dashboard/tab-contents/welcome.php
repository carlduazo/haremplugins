<table width="100%">
  <tr>
    <td valign="top" width="66%">
      <table class="wp-list-table widefat fixed">
        <thead>
          <tr>
            <th><strong>Welcome</strong></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td align="center">
              <table class="form-table">
                <tr valign="top">
                  <td scope="row">
                    <p>This plugin contains a Toolkit for the Epicode Developers.</p>
                    <p>Do not change any values yourself this might cause unexpected errors or bugs, ask Epicode to change these settings for you if needed.</p>
                    <p>&nbsp;</p>
                    <p>Contact us by mail: <a href="mailto:developer@epicode.nl" target="_blank">developer@epicode.nl</a> or visit our website <a href="https://epicode.nl" target="_blank">https://epicode.nl</a></p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </tbody>
      </table>
    </td>
    <td width="15">&nbsp;</td>
    <td valign="top" width="33%">
      <table class="wp-list-table widefat fixed">
        <thead>
          <tr>
            <th><strong>ACF Sync</strong></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <table class="form-table">
                <tr valign="top">
                  <td scope="row">
                    <span>Sync is available go <a href="<?php echo admin_url( 'edit.php?post_type=acf-field-group&post_status=sync' ); ?>">here</a> to sync you latest pulled field changes</span>
                  </td>
                </tr>
                <tr valign="top">
                  <td scope="row">
                    <input class="space-right" id="disable_acf_sync" <?= $epic_disable_acf_sync ? 'checked="checked"':''; ?> name="disable_acf_sync" type="checkbox" value="1">
                    <label for="disable_acf_sync">Disable ACF Sync</label>
                  </td>
                </tr>
                <tr valign="top">
                  <td scope="row">
                    <span>This allows multiple devs to work on a project, use git to push / pull files, and keep all databases synchronized with the latest field group settings!</span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </tbody>
      </table><br />
      <table class="wp-list-table widefat fixed">
        <thead>
          <tr>
            <th><strong>CPTUI Sync</strong></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <table class="form-table">
                <tr valign="top">
                  <td scope="row">
                    <span>AutoSync is on by default, syncs the Post Types to this plugin.</span>
                  </td>
                </tr>
                <tr valign="top">
                  <td scope="row">
                    <input class="space-right" id="disable_cpt_sync" <?= $epic_disable_cpt_sync ? 'checked="checked"':''; ?> name="disable_cpt_sync" type="checkbox" value="1">
                    <label for="disable_cpt_sync">Disable CPTUI Sync</label>
                  </td>
                </tr>
                <tr valign="top">
                  <td scope="row">
                    <span>This allows devs to create the Custom Post Types locally using CPT UI and then push them to staging/production.</span>
                  </td>
                </tr>
                <tr>
                  <td>
                    <input class="button-primary" name="submit-epic" type="submit" value="<?php _e('Save Changes') ?>">
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </tbody>
      </table>
    </td>
  </tr>
</table>
