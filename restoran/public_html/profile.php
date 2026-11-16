<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
mini_head('Profil', 'profile');
?>
<main class="page" id="app">
  <header class="page-head"><h1>Profil</h1></header>

  <section class="card-box profile-card" id="profileCard">
    <div class="avatar"><i data-lucide="user"></i></div>
    <div>
      <strong id="profileName">…</strong>
      <small class="muted" id="profileUsername"></small>
    </div>
  </section>

  <section class="card-box">
    <div class="row-between">
      <h2 class="box-title">Telefon raqami</h2>
    </div>
    <form id="phoneForm" class="inline-form" autocomplete="off">
      <input type="tel" id="phoneInput" placeholder="+998 90 123 45 67" maxlength="20" aria-label="Telefon raqami">
      <button class="btn primary sm" type="submit">Saqlash</button>
    </form>
  </section>

  <section class="card-box" id="addressBox">
    <div class="row-between">
      <h2 class="box-title">Saqlangan manzillar</h2>
    </div>
    <div id="addressList" class="address-list"></div>
    <form id="addressForm" class="form compact" autocomplete="off">
      <input name="title" placeholder="Nomi (masalan: Uy)" maxlength="60">
      <textarea name="address" rows="2" placeholder="Manzil" maxlength="255" required></textarea>
      <input name="landmark" placeholder="Mo‘ljal (ixtiyoriy)" maxlength="255">
      <button class="btn primary" type="submit">Manzil qo‘shish</button>
    </form>
  </section>

  <a class="card-box link-card" href="/orders.php">
    <i data-lucide="receipt"></i>
    <div><strong>Buyurtmalar tarixi</strong><small class="muted" id="ordersStat">Buyurtmalaringizni ko‘ring</small></div>
    <i data-lucide="chevron-right"></i>
  </a>

  <section class="card-box" id="restaurantBox">
    <h2 class="box-title">Restoran haqida</h2>
    <p id="restAbout" class="muted"></p>
    <ul class="info-list">
      <li><i data-lucide="clock"></i><span id="restHours"></span></li>
      <li><i data-lucide="map-pin"></i><span id="restAddress"></span></li>
      <li><i data-lucide="phone"></i><a id="restPhone" href="#"></a></li>
      <li><i data-lucide="send"></i><span id="restTg"></span></li>
    </ul>
  </section>
</main>
<?php mini_foot('profile');
