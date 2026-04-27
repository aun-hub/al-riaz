<?php
/**
 * Al-Riaz Associates — Icon Picker
 *
 * Drop-in modal-based FontAwesome icon picker. Include this file once on any
 * admin page that needs icon selection. To turn an existing field into an
 * icon picker, render two markup pieces in place of the plain text input:
 *
 *   <input type="hidden" name="icon" id="myIcon" value="fa-bullseye">
 *   <button type="button" class="btn btn-outline-secondary icon-picker-trigger"
 *           data-icon-target="myIcon">
 *     <i class="fa-solid fa-bullseye"></i>
 *     <span class="icon-picker-label">fa-bullseye</span>
 *     <i class="fa-solid fa-chevron-down ms-2 text-muted small"></i>
 *   </button>
 *
 * The JS at the bottom of this file auto-binds to every `.icon-picker-trigger`
 * on the page and keeps the hidden input + button preview in sync.
 *
 * Idempotent — safe to include multiple times; the static guard renders the
 * shared modal markup only once per request.
 */

if (!isset($GLOBALS['__icon_picker_rendered'])) {
    $GLOBALS['__icon_picker_rendered'] = true;

    /**
     * Curated set of FontAwesome 6 solid icons useful for a real-estate
     * agency dashboard. Adding more is harmless — they show up in the grid
     * and become searchable by name.
     */
    $iconCatalog = [
        // Real estate / property
        'fa-building','fa-house','fa-house-chimney','fa-house-user','fa-key',
        'fa-door-open','fa-city','fa-warehouse','fa-store','fa-industry',
        'fa-tree','fa-mountain-sun','fa-square-parking','fa-bed','fa-bath',
        // Trust / values / quality
        'fa-handshake','fa-shield-halved','fa-shield','fa-check',
        'fa-check-circle','fa-circle-check','fa-award','fa-medal','fa-trophy',
        'fa-thumbs-up','fa-star','fa-gem','fa-certificate','fa-ribbon',
        // People / service
        'fa-user','fa-user-tie','fa-users','fa-headset','fa-comments',
        'fa-phone','fa-envelope','fa-paper-plane','fa-id-card','fa-people-group',
        'fa-user-check','fa-user-shield',
        // Mission / vision / strategy
        'fa-bullseye','fa-eye','fa-compass','fa-map','fa-map-location-dot',
        'fa-rocket','fa-flag','fa-lightbulb','fa-chart-line','fa-chart-pie',
        'fa-chart-bar','fa-arrow-trend-up','fa-bolt-lightning',
        // Money / finance
        'fa-coins','fa-money-bill','fa-sack-dollar','fa-piggy-bank',
        'fa-percent','fa-tag','fa-receipt','fa-file-contract','fa-file-invoice-dollar',
        // Misc / generic
        'fa-heart','fa-bolt','fa-fire','fa-globe','fa-circle',
        'fa-clock','fa-calendar','fa-bell','fa-gear','fa-wrench',
        'fa-screwdriver-wrench','fa-magnifying-glass','fa-filter','fa-list-check',
        'fa-clipboard-check','fa-droplet','fa-fire-flame-curved',
        'fa-bolt','fa-couch','fa-elevator','fa-border-all','fa-arrows-turn-to-dots',
        'fa-box-archive','fa-layer-group','fa-snowflake','fa-temperature-high',
        'fa-shirt','fa-utensils','fa-tv','fa-wifi','fa-video','fa-solar-panel',
        'fa-water-ladder','fa-dumbbell','fa-plug-circle-bolt','fa-mountain-sun',
    ];
    $iconCatalog = array_values(array_unique($iconCatalog));
    sort($iconCatalog);
    ?>

    <!-- Icon Picker Modal (shared) -->
    <div class="modal fade" id="iconPickerModal" tabindex="-1" aria-labelledby="iconPickerModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header" style="background:var(--sidebar-bg);color:#fff;">
            <h5 class="modal-title" id="iconPickerModalLabel">
              <i class="fa-solid fa-icons me-2" style="color:var(--gold);"></i>Choose an icon
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="iconPickerSearch" class="form-control"
                       placeholder="Type to filter (e.g. handshake, building, star)..." autocomplete="off">
              </div>
            </div>
            <div id="iconPickerGrid" class="icon-picker-grid"></div>
            <p id="iconPickerEmpty" class="text-muted text-center py-4 d-none">
              <i class="fa-regular fa-face-frown me-1"></i>No icons match that search.
            </p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </div>
      </div>
    </div>

    <style>
      .icon-picker-trigger {
        display: inline-flex; align-items: center; gap: 0.5rem;
        text-align: left; min-width: 180px;
      }
      .icon-picker-trigger > .icon-picker-label {
        font-size: 0.78rem; color: #6c757d; font-family: monospace;
      }
      .icon-picker-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(96px, 1fr));
        gap: 0.5rem;
      }
      .icon-picker-tile {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        padding: 0.85rem 0.4rem; gap: 0.4rem;
        border: 1px solid #e2e6ea; border-radius: 8px;
        background: #fff; cursor: pointer;
        transition: all 0.15s ease;
      }
      .icon-picker-tile:hover {
        border-color: var(--gold);
        box-shadow: 0 4px 12px rgba(245,179,1,0.18);
        transform: translateY(-2px);
      }
      .icon-picker-tile.is-selected {
        border-color: var(--gold);
        background: #fff8e1;
      }
      .icon-picker-tile i { font-size: 1.4rem; color: var(--navy-700); }
      .icon-picker-tile small { font-size: 0.65rem; color: #6c757d; word-break: break-all; line-height: 1.2; text-align: center; }
    </style>

    <script>
    // Deferred so it runs AFTER admin-footer.php has loaded the Bootstrap
    // bundle — otherwise `bootstrap.Modal` would be undefined at parse time
    // and the click handler would never be attached.
    document.addEventListener('DOMContentLoaded', function () {
      var ICONS = <?= json_encode($iconCatalog, JSON_UNESCAPED_UNICODE) ?>;

      var modalEl   = document.getElementById('iconPickerModal');
      var grid      = document.getElementById('iconPickerGrid');
      var searchInp = document.getElementById('iconPickerSearch');
      var empty     = document.getElementById('iconPickerEmpty');
      if (!modalEl || typeof bootstrap === 'undefined') {
        console.warn('[icon-picker] Bootstrap not loaded or modal markup missing — picker disabled.');
        return;
      }
      var modal     = bootstrap.Modal.getOrCreateInstance(modalEl);
      var activeTriggerId = null;  // input id whose trigger we opened the modal from

      function render(filter) {
        var q = (filter || '').trim().toLowerCase();
        grid.innerHTML = '';
        var current = activeTriggerId ? (document.getElementById(activeTriggerId)?.value || '') : '';
        var shown = 0;
        ICONS.forEach(function (cls) {
          if (q && cls.toLowerCase().indexOf(q) === -1) return;
          var tile = document.createElement('button');
          tile.type = 'button';
          tile.className = 'icon-picker-tile' + (cls === current ? ' is-selected' : '');
          tile.dataset.iconClass = cls;
          tile.innerHTML = '<i class="fa-solid ' + cls + '"></i><small>' + cls.replace('fa-', '') + '</small>';
          tile.addEventListener('click', function () { applySelection(cls); });
          grid.appendChild(tile);
          shown++;
        });
        empty.classList.toggle('d-none', shown !== 0);
      }

      function applySelection(cls) {
        if (!activeTriggerId) return;
        var hidden = document.getElementById(activeTriggerId);
        if (!hidden) return;
        hidden.value = cls;
        // Update the trigger button preview that owns this hidden input.
        document.querySelectorAll('.icon-picker-trigger[data-icon-target="' + activeTriggerId + '"]').forEach(function (btn) {
          var iconEl  = btn.querySelector('i.fa-solid:not(.fa-chevron-down)');
          var labelEl = btn.querySelector('.icon-picker-label');
          if (iconEl) {
            // Strip any existing fa-* class and apply the new one.
            iconEl.className = iconEl.className.replace(/fa-[\w-]+/g, function (m) {
              return m === 'fa-solid' ? m : '';
            }).trim();
            iconEl.classList.add('fa-solid');
            iconEl.classList.add(cls);
          }
          if (labelEl) labelEl.textContent = cls;
        });
        // Fire a custom event so callers can react if they want to.
        hidden.dispatchEvent(new Event('change', { bubbles: true }));
        modal.hide();
      }

      // Auto-bind: any button with .icon-picker-trigger opens the modal.
      document.addEventListener('click', function (e) {
        var trigger = e.target.closest('.icon-picker-trigger');
        if (!trigger) return;
        e.preventDefault();
        activeTriggerId = trigger.dataset.iconTarget;
        searchInp.value = '';
        render('');
        modal.show();
        setTimeout(function () { searchInp.focus(); }, 200);
      });

      searchInp.addEventListener('input', function () { render(this.value); });
    });
    </script>
    <?php
}
?>
