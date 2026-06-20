<?php
/** Site header + primary navigation. Expects $active (nav key). */
$active = $active ?? '';
$nav = [
    'about'      => ['About', '/about.php'],
    'therapists' => ['Therapists', '/therapists.php'],
    'services'   => ['Services', '/services.php'],
    'gift-cards' => ['Gift Cards', '/gift-cards.php'],
    'forms'      => ['Forms', '/forms.php'],
    'contact'    => ['Contact', '/contact.php'],
];
?>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header" data-header>
  <div class="wrap site-header__inner">
    <a class="brand" href="/">
      <span class="brand__mark" aria-hidden="true">
        <svg viewBox="0 0 32 32" width="34" height="34"><use href="/assets/img/logo.svg#leaf"></use></svg>
      </span>
      <span class="brand__text">
        <span class="brand__name">Huntsville Massage</span>
        <span class="brand__sub">Professionals</span>
      </span>
    </a>

    <button class="nav-toggle" aria-expanded="false" aria-controls="primary-nav" aria-label="Toggle menu" data-nav-toggle>
      <span></span><span></span><span></span>
    </button>

    <nav class="primary-nav" id="primary-nav" aria-label="Primary">
      <ul>
        <?php foreach ($nav as $key => [$label, $href]): ?>
          <li><a href="<?= e($href) ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
        <?php endforeach; ?>
        <li class="primary-nav__cta"><a class="btn btn--small" href="/book.php">Book Now</a></li>
      </ul>
    </nav>
  </div>
</header>
<main id="main">
