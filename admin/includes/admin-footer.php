<?php
/**
 * Al-Riaz Associates — Admin Footer
 * Closes main content wrapper. Include at the bottom of every admin page.
 */
?>
</main><!-- /#mainContent -->

<!-- Bootstrap 5.3.2 Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery 3.7.1 -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
/* ── Sidebar Toggle ──────────────────────────────────── */
(function () {
  const sidebar  = document.getElementById('adminSidebar');
  const overlay  = document.getElementById('sidebarOverlay');
  const hamburger = document.getElementById('hamburgerBtn');

  function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('visible');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('visible');
    document.body.style.overflow = '';
  }

  if (hamburger) {
    hamburger.addEventListener('click', function () {
      sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });
  }

  if (overlay) {
    overlay.addEventListener('click', closeSidebar);
  }

  // Close sidebar on nav link click (mobile)
  if (sidebar) {
    sidebar.querySelectorAll('.sidebar-link').forEach(function (link) {
      link.addEventListener('click', function () {
        if (window.innerWidth < 992) closeSidebar();
      });
    });
  }
})();

/* ── Auto-dismiss Flash Messages ────────────────────── */
(function () {
  document.querySelectorAll('[data-auto-dismiss]').forEach(function(el) {
    const delay = parseInt(el.dataset.autoDismiss) || 4000;
    setTimeout(function() {
      var bsAlert = bootstrap.Alert.getOrCreateInstance(el);
      if (bsAlert) bsAlert.close();
    }, delay);
  });
})();

/* ── Reusable Confirm Modal ───────────────────────────
   Any element with [data-confirm="message"] triggers a styled modal
   instead of the native confirm() dialog. Optional attributes:
     data-confirm-title   — heading text (default: "Please confirm")
     data-confirm-ok      — OK button label  (default: "Confirm")
     data-confirm-cancel  — cancel label     (default: "Cancel")
     data-confirm-variant — "danger"|"warning"|"primary" (default "danger")
     data-confirm-icon    — Font Awesome class suffix (default varies by variant)

   Works for <button type="submit">, <a href>, or plain buttons.
   For <select data-confirm-change="..."> the confirmation fires on change;
   the select reverts if the user cancels.
------------------------------------------------------- */
(function(){
  var tpl = document.createElement('div');
  tpl.innerHTML =
    '<div class="modal fade" id="adminConfirmModal" tabindex="-1" aria-hidden="true">'
  + '  <div class="modal-dialog modal-dialog-centered modal-sm">'
  + '    <div class="modal-content">'
  + '      <div class="modal-header border-0 pb-0">'
  + '        <h5 class="modal-title d-flex align-items-center gap-2">'
  + '          <i class="fa-solid fa-circle-exclamation" data-confirm-icon style="color:#dc3545;"></i>'
  + '          <span data-confirm-title-el>Please confirm</span>'
  + '        </h5>'
  + '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>'
  + '      </div>'
  + '      <div class="modal-body pt-2" data-confirm-body-el></div>'
  + '      <div class="modal-footer border-0 pt-0">'
  + '        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" data-confirm-cancel-el>Cancel</button>'
  + '        <button type="button" class="btn btn-sm btn-danger" data-confirm-ok-el>Confirm</button>'
  + '      </div>'
  + '    </div>'
  + '  </div>'
  + '</div>';
  document.body.appendChild(tpl.firstElementChild);

  var modalEl  = document.getElementById('adminConfirmModal');
  var titleEl  = modalEl.querySelector('[data-confirm-title-el]');
  var bodyEl   = modalEl.querySelector('[data-confirm-body-el]');
  var iconEl   = modalEl.querySelector('[data-confirm-icon]');
  var okBtn    = modalEl.querySelector('[data-confirm-ok-el]');
  var cancelBtn = modalEl.querySelector('[data-confirm-cancel-el]');
  var modal    = new bootstrap.Modal(modalEl);

  var variantMap = {
    danger:  { btn: 'btn-danger',  icon: 'fa-circle-exclamation', color: '#dc3545' },
    warning: { btn: 'btn-warning', icon: 'fa-triangle-exclamation', color: '#ffc107' },
    primary: { btn: 'btn-primary', icon: 'fa-circle-info', color: '#0d6efd' },
    success: { btn: 'btn-success', icon: 'fa-circle-check', color: '#198754' }
  };

  function showConfirm(opts, onConfirm, onCancel) {
    var v = variantMap[opts.variant] || variantMap.danger;
    titleEl.textContent = opts.title || 'Please confirm';
    bodyEl.textContent  = opts.message || 'Are you sure?';
    okBtn.textContent   = opts.ok || 'Confirm';
    cancelBtn.textContent = opts.cancel || 'Cancel';
    okBtn.className = 'btn btn-sm ' + v.btn;
    iconEl.className = 'fa-solid ' + (opts.icon || v.icon);
    iconEl.style.color = v.color;

    var handled = false;
    function finish(confirmed) {
      if (handled) return;
      handled = true;
      modal.hide();
      setTimeout(function(){
        if (confirmed) { if (onConfirm) onConfirm(); }
        else           { if (onCancel)  onCancel(); }
      }, 150);
    }
    okBtn.onclick = function(){ finish(true); };
    modalEl.addEventListener('hidden.bs.modal', function once(){
      modalEl.removeEventListener('hidden.bs.modal', once);
      if (!handled) finish(false);
    });
    modal.show();
  }
  // Expose for ad-hoc callers
  window.adminConfirm = showConfirm;

  // ── Click handler for [data-confirm] on buttons / links / submits ──
  document.addEventListener('click', function(e){
    var el = e.target.closest('[data-confirm]');
    if (!el) return;
    if (el.dataset.confirmed === '1') { el.dataset.confirmed = ''; return; }

    e.preventDefault();
    e.stopPropagation();

    showConfirm({
      title:   el.getAttribute('data-confirm-title') || undefined,
      message: el.getAttribute('data-confirm') || 'Are you sure? This action cannot be undone.',
      ok:      el.getAttribute('data-confirm-ok') || undefined,
      cancel:  el.getAttribute('data-confirm-cancel') || undefined,
      variant: el.getAttribute('data-confirm-variant') || undefined,
      icon:    el.getAttribute('data-confirm-icon') || undefined
    }, function(){
      el.dataset.confirmed = '1';
      if (el.form && el.type === 'submit') {
        // Preserve which submit button was clicked (so name/value is sent).
        if (el.name) {
          var hidden = el.form.querySelector('input[type=hidden][data-confirm-proxy]');
          if (!hidden) {
            hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.setAttribute('data-confirm-proxy', '1');
            el.form.appendChild(hidden);
          }
          hidden.name  = el.name;
          hidden.value = el.value;
        }
        el.form.submit();
      } else {
        el.click();
      }
    });
  });

  // ── Change handler for <select data-confirm-change="..."> ──
  // The select's previous value is remembered so we can revert on cancel.
  document.addEventListener('focus', function(e){
    var sel = e.target;
    if (sel.tagName === 'SELECT' && sel.hasAttribute('data-confirm-change')) {
      sel.dataset.previousValue = sel.value;
    }
  }, true);

  document.addEventListener('change', function(e){
    var sel = e.target;
    if (sel.tagName !== 'SELECT' || !sel.hasAttribute('data-confirm-change')) return;
    if (sel.dataset.confirmed === '1') { sel.dataset.confirmed = ''; return; }

    var msgTpl = sel.getAttribute('data-confirm-change');
    var prev   = sel.dataset.previousValue !== undefined ? sel.dataset.previousValue : '';
    var newVal = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].text : sel.value;
    var msg    = msgTpl.replace('{value}', newVal);

    showConfirm({
      title:   sel.getAttribute('data-confirm-title') || undefined,
      message: msg,
      ok:      sel.getAttribute('data-confirm-ok') || undefined,
      variant: sel.getAttribute('data-confirm-variant') || 'primary'
    }, function(){
      sel.dataset.previousValue = sel.value;
      sel.dataset.confirmed = '1';
      if (sel.form) sel.form.submit();
    }, function(){
      sel.value = prev;
    });
  });
})();

/* ── Select All Checkboxes ───────────────────────────── */
var selectAll = document.getElementById('selectAll');
if (selectAll) {
  selectAll.addEventListener('change', function() {
    var checked = this.checked;
    document.querySelectorAll('.row-check').forEach(function(cb) {
      cb.checked = checked;
    });
  });
}

/* ── AJAX Status Update Helper ───────────────────────── */
window.BASE_PATH = <?= json_encode(defined('BASE_PATH') ? BASE_PATH : '') ?>;
window.ajaxPost = function(url, data, callback) {
  // Auto-prefix BASE_PATH for root-relative URLs in subdirectory installs.
  if (typeof url === 'string' && url.charAt(0) === '/' && url.charAt(1) !== '/'
      && window.BASE_PATH && url.indexOf(window.BASE_PATH + '/') !== 0) {
    url = window.BASE_PATH + url;
  }
  fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
    body: new URLSearchParams(data)
  })
  .then(function(r){ return r.json(); })
  .then(function(json){ if (callback) callback(json); })
  .catch(function(err){ console.error('[AJAX]', err); });
};
</script>

</body>
</html>
