<table width="100%">
  <tr>
    <td valign="top">
      <table class="wp-list-table widefat fixed">
        <thead>
          <tr>
            <th>
              <strong>Select plugins you want to disable from updates</strong>
              <span class="checkbox-right">
                <input class="space-right" id="checkall" name="checkall" type="checkbox" value="1">
                <label for="checkall">Check all</label>
              </span>
              </tr>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <table class="form-table checkboxes">
                <?php
                if (!empty($plugins)){ ?>
                  <?php foreach ($plugins as $plugin_key_name=>$plugin){ ?>
                    <?php $pluginid = str_replace(" ", "_", $plugin['Name']); ?>
                    <tr valign="top">
                      <td scope="row"><input class="space-right" id="<?= $pluginid; ?>" name="block_plugin_updates[<?= $plugin_key_name; ?>]" type="checkbox" <?= in_array($plugin_key_name,$epic_update_blocked_plugins_array)?'checked="checked"':''; ?> value="<?= $plugin_key_name; ?>"><label for="<?= $pluginid; ?>"><?= $plugin['Name']; ?></label></td>
                    </tr><?php
                  }
                } else { ?>
                  <tr>
                    <td>No Plugins Found</td>
                  </tr><?php
                } ?>
              </table>
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
            <th><strong>Disable plugin installation</strong></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td align="center">
              <table class="form-table">
                <tr>
                  <td>
                    <input class="space-right" id="disable_plugin_installation" name="disable_plugin_installation" type="checkbox" <?= $epic_disable_plugin_installation ? 'checked="checked"':''; ?> value="yes"><label for="disable_plugin_installation">Disable plugin installation</label>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </tbody>
      </table><br>
      <table class="wp-list-table widefat fixed">
        <thead>
          <tr>
            <th><strong>Disable Updates</strong></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td align="center">
              <table class="form-table">
                <tr>
                  <td>
                    <input class="space-right" id="block_core" name="block_core" type="checkbox" <?= $epic_core_blocked ? 'checked="checked"':''; ?> value="yes"><label for="block_core">Disable WP Core Updates</label>
                  </td>
                </tr>
                <tr>
                  <td>
                    <input class="space-right" id="block_theme" name="block_theme" type="checkbox" <?= $epic_theme_blocked ? 'checked="checked"':''; ?> value="yes"><label for="block_theme">Disable Theme Updates</label>
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
    <td width="15">&nbsp;</td>
  </tr>
</table>
