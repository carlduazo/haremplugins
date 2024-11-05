<table width="100%">
  <tr>
    <td valign="top">
      <table class="wp-list-table widefat fixed">
        <thead>
          <tr>
            <th><strong>Enable maintenance mode</strong></th>
          </tr>
        </thead>
        <tbody>
          <?php
            $maintenance_types = [
              'is-staging' => [
                'has_fields' => true,
                'enable_label' => 'Enable staging mode',
              ],
              'is-maintenance' => [
                'has_fields' => true,
                'enable_label' => 'Enable maintenance mode',
              ],
              'is-reactive-maintenance' => [
                'has_fields' => true,
                'enable_label' => 'Reactive maintenance mode',
              ],
              'is-reactive-staging' => [
                'has_fields' => true,
                'enable_label' => 'Reactive staging mode',
              ],
              'none' => [
                'has_fields' => false,
                'enable_label' => 'None',
              ]
            ];
            foreach ( $maintenance_types as $maintenance_type_key => $maintenance_type ) { ?>
              <tr
                <?php
                $class_attr_arr = ['js maintenance-type-row', $maintenance_type_key];
                if ( $epic_maintenance_type != $maintenance_type_key ) {
                  $class_attr_arr[] = 'hidden';
                }
                printf( 'class="%s"', join(' ', $class_attr_arr) ) ?>
              >
              <?php
              if ( $maintenance_type['has_fields'] ) {
                if ( in_array( $maintenance_type_key, ['is-staging', 'is-maintenance'] ) ) { ?>
                <td>
                  <table class="form-table">
                    <tr valign="top">
                      <td scope="row">
                        <label class="label" for="maintenance_logo[<?= $maintenance_type_key ?>]">Logo</label>
                        <input id="maintenance_logo[<?= $maintenance_type_key ?>]" name="maintenance_logo[<?= $maintenance_type_key ?>]" type="text" value="<?= $epic_maintenance_logo_arr[$maintenance_type_key] ?? $epic_maintenance_logo_arr ?>" class="large-text" />
                      </td>
                    </tr>
                    <tr valign="top">
                      <td scope="row">
                        <label class="label" for="maintenance_text[<?= $maintenance_type_key ?>]">Text</label>
                        <textarea id="maintenance_text[<?= $maintenance_type_key ?>]" name="maintenance_text[<?= $maintenance_type_key ?>]" cols="80" rows="10" class="large-text"><?= $epic_maintenance_text_arr[$maintenance_type_key] ?? $epic_maintenance_text_arr ?></textarea>
                      </td>
                    </tr>

                    <?php if ( $maintenance_type_key == 'is-staging' ) { ?>
                    <tr valign="top">
                      <td scope="row">
                        <label class="label" for="maintenance_actual_website_link[<?= $maintenance_type_key ?>]">Actual Website Link</label>
                        <input id="maintenance_actual_website_link[<?= $maintenance_type_key ?>]" name="maintenance_actual_website_link[<?= $maintenance_type_key ?>]" type="text" value="<?= $epic_maintenance_actual_website_link_arr[$maintenance_type_key] ?>" class="large-text" />
                      </td>
                    </tr>
                    <?php } ?>

                    <tr valign="top">
                      <td scope="row">
                        <label class="label" for="maintenance_contact_us_link[<?= $maintenance_type_key ?>]">Contact Us Link</label>
                        <input id="maintenance_contact_us_link[<?= $maintenance_type_key ?>]" name="maintenance_contact_us_link[<?= $maintenance_type_key ?>]" type="text" value="<?= $epic_maintenance_contact_us_link_arr[$maintenance_type_key] ?>" class="large-text" />
                      </td>
                    </tr>
                  </table>
                </td>
              <?php
                } else {
              ?>
                <td>
                  <p style="padding-left: 10px;">Disables maintenance/staging modes and enables after some amount of hours.</p>
                  <table class="form-table">
                    <tr valign="top">
                      <td scope="row">
                        <label class="label" for="reactive_maintenance_duration[<?= $maintenance_type_key ?>]">Duration (in hours)</label>
                        <input id="reactive_maintenance_duration[<?= $maintenance_type_key ?>]" name="reactive_maintenance_duration[<?= $maintenance_type_key ?>]" type="text" value="<?= $epic_reactive_maintenance_duration_arr[$maintenance_type_key] ?? $epic_reactive_maintenance_duration_arr ?>" class="large-text" />
                      </td>
                    </tr>
                  </table>
                </td>
              <?php
                }
              }	else { ?>
                <td>
                  <table class="form-table">
                    <tr valign="top">
                      <td scope="row">
                        <?= __( 'No dynamic field settings available.', 'epictk' ) ?>
                      </td>
                    </tr>
                  </table>
                </td>
              <?php } ?>
              </tr>
          <?php } ?>
        </tbody>
      </table>
    </td>
    <td width="15">&nbsp;</td>
    <td valign="top" width="450">
      <table class="wp-list-table widefat fixed">
        <thead>
          <tr>
            <th><strong>Enable maintenance mode</strong></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td align="center">
              <table class="form-table">
                <?php
                foreach ( $maintenance_types as $maintenance_type_key => $maintenance_type ) {
                  $input_id = 'maintenance_type' . $maintenance_type_key; ?>
                <tr valign="top">
                  <td scope="row">
                    <input
                      class="space-right js maintenance-type"
                      id="<?= $input_id ?>"
                      <?php checked( $epic_maintenance_type === $maintenance_type_key ) ?>
                      name="maintenance_type"
                      type="radio"
                      value="<?= $maintenance_type_key ?>">
                    <label for="<?= $input_id ?>"><?= $maintenance_type['enable_label'] ?></label>
                  </td>
                </tr>
                <?php } ?>
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
