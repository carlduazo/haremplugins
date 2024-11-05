<table width="100%">
  <tr>
    <td valign="top">
      <table class="wp-list-table widefat fixed">
        <thead>
          <tr>
            <th>
              <strong>Select menu's you want to hide</strong>
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
                if (!empty($menu_pages)){
                  foreach ($menu_pages as $menu){
                    if(strpos($menu['slug'], 'separator') === false){
                      $menu_id = str_replace('.', '_', $menu['slug']); ?>
                      <tr valign="top">
                        <td scope="row"><input class="space-right" id="<?= $menu_id; ?>" name="hide_menu_items[<?= $menu['slug']; ?>]" type="checkbox" <?= in_array($menu['slug'], $epic_hide_menu_array)?'checked="checked"':''; ?> value="<?= $menu['slug']; ?>"><label for="<?= $menu_id; ?>"><?= $menu['name']; ?></label></td>
                      </tr><?php
                      if(!empty($menu['sub_menu'])){
                        foreach ($menu['sub_menu'] as $sub_menu) {
                          $sub_menu_id = str_replace('.', '_', $sub_menu['slug']); ?>
                          <tr valign="top">
                            <td scope="row"><input class="space-right sub-menu" style="margin-left: 32px;" id="<?= $sub_menu_id.'_subitem'; ?>" name="hide_submenu_items[<?= $menu['slug']; ?>][]" type="checkbox" <?= !empty($epic_hide_sub_menu_array[$menu['slug']]) && in_array($sub_menu['slug'], $epic_hide_sub_menu_array[$menu['slug']])?'checked="checked"':''; ?> value="<?= $sub_menu['slug']; ?>"><label for="<?= $sub_menu_id.'_subitem'; ?>"><?= $sub_menu['name']; ?></label></td>
                          </tr><?php
                        }
                      }
                    }
                  }
                } else { ?>
                  <tr>
                    <td>No Menu Items Found</td>
                  </tr><?php
                } ?>
              </table>
            </td>
          </tr>
        </tbody>
      </table>
    </td>
    <td width="15">&nbsp;</td>
    <td valign="top" width="30%">
      <table class="wp-list-table widefat fixed">
        <thead>
          <tr>
            <th><strong>Save changes</strong></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <table class="form-table">
                <tr>
                  <td>
                    <input class="button-primary" name="submit-epic" type="submit" value="<?php _e('Save Changes') ?>">
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
            <th><strong>WP REST API</strong></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <table class="form-table">
                <tr>
                  <td>
                    <input class="space-right" name="epic_wp_rest_api_disabled" type="hidden" value="no">
                    <input class="space-right" id="epic_wp_rest_api_disabled" name="epic_wp_rest_api_disabled" type="checkbox" <?= checked( $epic_wp_rest_api_disabled == 'yes' ) ?> value="yes">
                    <label for="block_seach_engines">Disable WP REST API</label>
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
            <th><strong>Search Engine Visibility</strong></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <table class="form-table">
                <tr>
                  <td>
                    <input class="space-right" name="block_seach_engines" type="hidden" value="no">
                    <input class="space-right" id="block_seach_engines" name="block_seach_engines" type="checkbox" <?= $epic_block_seach_engines == 'yes' ? 'checked="checked"':''; ?> value="yes"><label for="block_seach_engines">Discourage search engines from indexing this site</label>
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
            <th><strong>Block WP_Mail</strong></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <table class="form-table">
                <tr>
                  <td>
                    <input class="space-right" id="block_wp_mail" name="block_wp_mail" type="checkbox" <?= $epic_block_wp_mail ? 'checked="checked"':''; ?> value="yes"><label for="block_wp_mail">Disable the WP_Mail function</label>
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
            <th><strong>Images | External source</strong></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <table class="form-table">
                <tr>
                  <td>
                    <input type="hidden" name="images_enable_external_source" value="no">
                    <input type="checkbox" id="images_enable_external_source" name="images_enable_external_source" class="space-right" id="images_current_site_url"  value="yes" <?= $images_enable_external_source == 'yes' ? 'checked="checked"' : ''; ?>>
                    <label for="images_enable_external_source">Enable images external source</label>
                  </td>
                </tr>
                <tr>
                  <td>
                    <label for="images_external_site_url">External site's URL</label>
                    <input type="url" name="images_external_site_url" class="large-text" id="images_external_site_url" value="<?= $images_external_site_url; ?>" placeholder="https://someexternalsource.com">
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
            <th><strong>Epicode | Feedback</strong></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <table class="form-table">
                <tr>
                  <td>
                    <label for="project_huddle_api">Feedback API URL</label>
                    <input id="project_huddle_api" name="project_huddle_api" type="text" value="<?= $epic_project_huddle_api; ?>" placeholder="//feedback.epicode.nl/?p=1337&amp;ph_apikey=r4nd0mnumb3rh3r3" class="large-text" />
                  </td>
                </tr>
                <tr>
                  <td>
                    <input class="space-right" id="project_huddle_api_enable" name="project_huddle_api_enable" type="checkbox" <?= $epic_project_huddle_api_enable ? 'checked="checked"':''; ?> value="yes"><label for="project_huddle_api_enable">Enable ProjectHuddle Api</label>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </tbody>
      </table>
    </td>
    <td width="15">&nbsp;</td>
  </tr>
</table>
