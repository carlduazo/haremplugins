<?=
epictk_view(
  'maintenance/partials/header',
  ['logo' => $logo, 'status_code' => 403]
) ?>
<div class="panel panel--shadow">
  <div class="maintenance__text"><?= $text ?></div>
  <div class="links-list">
    <ul class="list">
      <?php if ( ! empty( $actual_website_link ) ) { ?>
      <li class="list__item">
        <a href="<?= $actual_website_link ?>">
          <?php epictk_the_icon( 'chain', ['icon--prefix'] ) ?>
          <?= __( 'Visit the actual website', 'epictk' )  ?>
        </a>
      </li>
      <?php } ?>
      <li class="list__item">
        <a href="<?= wp_login_url( home_url() ) ?>">
          <?php epictk_the_icon( 'unlock-alt', ['icon--prefix'] ) ?>
          <?= __( 'Login for testing', 'epictk' )  ?>
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
