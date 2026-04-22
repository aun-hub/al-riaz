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

/* ── Confirm Delete ──────────────────────────────────── */
document.addEventListener('click', function(e) {
  var btn = e.target.closest('[data-confirm]');
  if (btn) {
    var msg = btn.getAttribute('data-confirm') || 'Are you sure? This action cannot be undone.';
    if (!confirm(msg)) {
      e.preventDefault();
      e.stopPropagation();
    }
  }
});

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
window.ajaxPost = function(url, data, callback) {
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
