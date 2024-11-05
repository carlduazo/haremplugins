<?=
epictk_view(
  'maintenance/partials/header',
  ['logo' => $logo, 'status_code' => 503]
) ?>
<div class="panel panel--shadow">
  <div class="maintenance__text"><?= $text ?></div>
  <div class="links-list">
    <ul class="list">
      <li class="list__item">
        <a href="<?= home_url() ?>">
          <?php epictk_the_icon( 'refresh', ['icon--prefix'] ) ?>
          <?= __( 'Refresh and check again', 'epictk' )  ?>
        </a>
      </li>
      <li class="list__item">
        <a href="<?= $contact_us_link ?>">
          <?php epictk_the_icon( 'comments', ['icon--prefix'] ) ?>
          <?= __( 'Contact us', 'epictk' )  ?>
        </a>
      </li>
    </ul>
  </div>
</div>
