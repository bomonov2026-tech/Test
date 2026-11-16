<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
mini_head('Buyurtma', 'cart');
?>
<main class="page" id="app">
  <a class="back-link" href="/cart.php"><i data-lucide="chevron-left"></i> Savatga qaytish</a>
  <header class="page-head"><h1>Buyurtmani rasmiylashtirish</h1></header>

  <div id="storeNotice" class="alert error" hidden></div>

  <div id="checkoutBody" hidden>
    <div class="seg" role="radiogroup" aria-label="Buyurtma turi">
      <label><input type="radio" name="orderType" value="delivery" checked><span><i data-lucide="truck"></i> Yetkazib berish</span></label>
      <label><input type="radio" name="orderType" value="pickup"><span><i data-lucide="store"></i> Olib ketish</span></label>
    </div>

    <form id="orderForm" class="form" novalidate>
      <fieldset class="card-box">
        <legend>Mijoz ma’lumotlari</legend>
        <label class="field">
          <span>Ism</span>
          <input name="customer_name" required maxlength="120" autocomplete="name">
        </label>
        <label class="field">
          <span>Telefon raqami</span>
          <input name="customer_phone" type="tel" required maxlength="20" placeholder="+998 90 123 45 67" autocomplete="tel">
        </label>
      </fieldset>

      <fieldset class="card-box" id="deliveryBox">
        <legend>Yetkazib berish manzili</legend>
        <div id="addressList" class="address-list"></div>
        <label class="field">
          <span>Manzil</span>
          <textarea name="address" rows="2" maxlength="255" placeholder="Shahar, tuman, ko‘cha, uy"></textarea>
        </label>
        <label class="field">
          <span>Mo‘ljal</span>
          <input name="landmark" maxlength="255" placeholder="Masalan: maktab yonida">
        </label>
        <label class="check">
          <input type="checkbox" name="save_address"> <span>Bu manzilni saqlash</span>
        </label>
      </fieldset>

      <fieldset class="card-box">
        <legend>Izoh</legend>
        <label class="field">
          <textarea name="comment" rows="2" maxlength="500" placeholder="Qo‘shimcha izoh (ixtiyoriy)"></textarea>
        </label>
      </fieldset>

      <div class="card-box pay">
        <i data-lucide="banknote"></i>
        <div><strong>To‘lov: naqd pul</strong><small class="muted">Buyurtma topshirilganda to‘lanadi</small></div>
      </div>

      <div class="card-box" id="reviewBox">
        <h2 class="box-title">Buyurtma tarkibi</h2>
        <ul class="review-lines" id="reviewLines"></ul>
        <div class="summary" id="reviewSummary"></div>
      </div>

      <button class="btn primary block lg" type="button" id="submitBtn">Buyurtmani tasdiqlash</button>
    </form>
  </div>

  <div id="successBox" class="success-card" hidden>
    <div class="success-icon"><i data-lucide="check"></i></div>
    <h2>Buyurtma qabul qilindi</h2>
    <p>Buyurtma raqami: <strong id="orderNoText"></strong></p>
    <p class="muted">Holat o‘zgarganda Telegram orqali xabar olasiz.</p>
    <a class="btn primary block" href="/orders.php">Buyurtmalarim</a>
    <a class="btn ghost block" href="/menu.php">Yana buyurtma berish</a>
  </div>

  <div class="sheet-backdrop" id="confirmSheet" hidden>
    <div class="sheet" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
      <h2 id="confirmTitle">Buyurtmani tasdiqlaysizmi?</h2>
      <ul class="review-lines" id="confirmLines"></ul>
      <div class="summary" id="confirmSummary"></div>
      <div class="sheet-actions">
        <button class="btn ghost" type="button" data-close-sheet>Bekor qilish</button>
        <button class="btn primary" type="button" id="confirmBtn">Tasdiqlash</button>
      </div>
    </div>
  </div>
</main>
<?php mini_foot('checkout');
