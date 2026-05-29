<?php
/**
 * Theme toggle — include once per page, before </body>.
 * Inline script so clicks always work (no dependency on external asset URL).
 */
?>
<script>
(function () {
  if (window.__northbridgeThemeBooted) return;
  window.__northbridgeThemeBooted = true;

  function isDark() {
    return document.documentElement.classList.contains('dark');
  }

  function applyTheme(next) {
    var dark = next === 'dark';
    document.documentElement.classList.toggle('dark', dark);
    document.documentElement.classList.toggle('nb-staff-active', dark && document.body && document.body.classList.contains('nb-staff'));
    try {
      localStorage.setItem('theme', dark ? 'dark' : 'light');
    } catch (e) {}
  }

  function bind() {
    document.querySelectorAll('.theme-toggle').forEach(function (btn) {
      if (btn.dataset.themeBound === '1') return;
      btn.dataset.themeBound = '1';
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        applyTheme(isDark() ? 'light' : 'dark');
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bind);
  } else {
    bind();
  }
})();
</script>
