<table width="100%">
  <tr>
    <td valign="top">
      <table class="wp-list-table widefat fixed">
        <thead>
          <tr>
            <th><strong>Sentry.IO</strong></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <label class="label" for="wp_sentry_dsn">DSN</label>
              <i style="vertical-align: middle;">( Click <a href="https://kb.epicode.nl/how-to-get-dsn-in-sentry-io/" target="_blank">here</a> to see how to get DSN. )</i>
              <input id="wp_sentry_dsn" name="dsn" type="text" value="<?= $data_settings['dsn'] ?>" class="large-text" />
            </td>
          </tr>
        </tbody>
      </table>
    </td>
    <td width="15">&nbsp;</td>
    <td valign="top" width="450">
      <table class="wp-list-table widefat fixed">
        <thead>
          <tr>
            <th><strong>Sentry.IO</strong></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td align="center">
              <table class="form-table">
                <tr valign="top">
                  <td scope="row">
                    <input type="hidden" name="allow" value="no">
                    <input
                      class="space-right error-reporting-allow js"
                      id="allow"
                      <?php checked( $data_settings['allow'] == 'yes' ) ?>
                      name="allow"
                      type="checkbox"
                      value="yes">
                    <label for="">Allow Sentry.IO</label>
                  </td>
                </tr>
                <tr valign="top">
                  <td scope="row">
                    <input type="hidden" name="php_error_tracking_enabled" value="no">
                    <input
                      class="space-right error-reporting-php_tracker js"
                      id="php_error_tracking_enabled"
                      <?php checked( $data_settings['php_error_tracking_enabled'] == 'yes' ) ?>
                      name="php_error_tracking_enabled"
                      type="checkbox"
                      value="yes">
                    <label for="">Enable PHP error tracking</label>
                  </td>
                </tr>
                <tr valign="top">
                  <td scope="row">
                    <input type="hidden" name="js_error_tracking_enabled" value="no">
                    <input
                      class="space-right error-reporting-js_tracker js"
                      id="js_error_tracking_enabled"
                      <?php checked( $data_settings['js_error_tracking_enabled'] == 'yes' ) ?>
                      name="js_error_tracking_enabled"
                      type="checkbox"
                      value="yes">
                    <label for="">Enable JS error tracking</label>
                  </td>
                </tr>
                <tr valign="top">
                  <td scope="row">
                    <label for="">Environment</label>
                    <select class="" name="environment">
                      <?php foreach ( $environment_types as $environment_type ) { ?>
                        <option value="<?= $environment_type; ?>" <?php selected( $data_settings['environment'] == $environment_type ); ?>><?= $environment_type; ?></option>
                      <?php } ?>
                    </select>
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
      </table><br>
    </td>
  </tr>
</table>
