/* Batlee Housing Solution - small progressive enhancements. The site also works without JavaScript. */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  // Mobile menus (storefront + admin)
  $$('.nav-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target = document.getElementById(btn.getAttribute('aria-controls'));
      if (!target) return;
      var open = target.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });

  // Sort dropdown submits itself
  $$('.js-autosubmit').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
  });

  // Product gallery thumbnails
  var main = $('#main-img');
  $$('.js-thumb').forEach(function (t) {
    t.addEventListener('click', function () {
      if (main) main.src = t.getAttribute('data-src');
      $$('.js-thumb').forEach(function (x) { x.classList.remove('active'); });
      t.classList.add('active');
    });
  });

  // Add-to-cart without leaving the page (falls back to a normal form post)
  var toastTimer;
  function toast(msg, isErr) {
    var t = $('#toast');
    if (!t) {
      t = document.createElement('div');
      t.id = 'toast';
      t.setAttribute('role', 'status');
      t.style.cssText = 'position:fixed;left:50%;bottom:84px;transform:translateX(-50%);z-index:90;max-width:90vw;padding:11px 18px;border-radius:10px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.3);';
      document.body.appendChild(t);
    }
    t.style.background = isErr ? '#8f2314' : '#111214';
    t.style.color = '#fff';
    t.textContent = msg;
    t.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { t.hidden = true; }, 2600);
  }
  $$('form.js-add-cart').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      var submitter = ev.submitter;
      if (submitter && submitter.name === 'buy_now') return; // normal post, goes to cart
      if (!window.fetch || !window.FormData) return;
      ev.preventDefault();
      fetch(form.getAttribute('action'), {
        method: 'POST', body: new FormData(form), credentials: 'same-origin',
        headers: { 'X-Requested-With': 'fetch', 'X-CSRF-Token': csrf }
      }).then(function (r) { return r.json(); }).then(function (d) {
        toast(d.message, !d.ok);
        var b = document.getElementById('cart-badge');
        if (b && typeof d.count === 'number') { b.textContent = d.count; b.hidden = d.count < 1; }
      }).catch(function () { form.submit(); });
    });
  });

  // Helpline chat box
  var fab = $('#helpline-open'), panel = $('#helpline-panel'), closeBtn = $('#helpline-close');
  function setPanel(open) {
    if (!panel) return;
    panel.hidden = !open;
    fab.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  if (fab && panel) {
    fab.addEventListener('click', function () { setPanel(panel.hidden); });
    closeBtn.addEventListener('click', function () { setPanel(false); fab.focus(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setPanel(false); });
  }
  var hf = $('#helpline-form'), hs = $('#helpline-status');
  if (hf) {
    hf.addEventListener('submit', function (ev) {
      if (!window.fetch || !window.FormData) return;
      ev.preventDefault();
      hs.className = 'helpline-status'; hs.textContent = 'Sending...';
      fetch(hf.getAttribute('action'), { method: 'POST', body: new FormData(hf), credentials: 'same-origin', headers: { 'X-CSRF-Token': csrf } })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          hs.textContent = d.message; hs.className = 'helpline-status ' + (d.ok ? 'ok' : 'err');
          if (d.ok) hf.reset();
        })
        .catch(function () { hs.textContent = 'Could not send. Please call us instead.'; hs.className = 'helpline-status err'; });
    });
  }

  // Optional delivery location (only when the customer taps the button)
  var locBtn = $('#use-location');
  if (locBtn) {
    locBtn.addEventListener('click', function () {
      var st = $('#loc-status');
      if (!navigator.geolocation) { st.textContent = 'Location is not supported on this device.'; return; }
      st.textContent = 'Getting your location...';
      navigator.geolocation.getCurrentPosition(function (pos) {
        $('#lat').value = pos.coords.latitude.toFixed(7);
        $('#lng').value = pos.coords.longitude.toFixed(7);
        st.textContent = 'Location attached. Thank you!';
      }, function () {
        st.textContent = 'Could not get your location - you can skip this step.';
      }, { enableHighAccuracy: true, timeout: 10000 });
    });
  }

  // Payment page: show the UPI reference box only for UPI
  var upi = $('#upi-ref');
  if (upi) {
    var sync = function () {
      var sel = $('input[name="method"]:checked');
      upi.hidden = !(sel && sel.value === 'upi');
    };
    $$('input[name="method"]').forEach(function (r) { r.addEventListener('change', sync); });
    sync();
  }

  // Confirm dangerous admin actions
  $$('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
    });
  });
})();
