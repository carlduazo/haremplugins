<!DOCTYPE html>
<html class="epic_maintenance_mode">
  <head>
    <meta charset="utf-8">
    <title>Maintenance | <?= $html_title ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link id="options-google-fonts" href="https://fonts.googleapis.com/css?family=Hind:400,600,700" rel="stylesheet">
    <link rel="stylesheet" href="<?= $css ?>">
  </head>
  <body>
    <?php if (function_exists('epictk_icons_sprite')) epictk_icons_sprite()  ?>
    <div class="root">
      <div class="root__inner">
        <div class="maintenance">
          <?php
          $view_data = [
            'logo'                => $logo,
            'text'                => $text,
            'actual_website_link' => $actual_website_link,
            'contact_us_link'     => $contact_us_link,
          ];
          echo epictk_view( "maintenance/$maintenance_type", $view_data ); ?>
        </div>
      </div>
    </div>
  </body>
</html>
