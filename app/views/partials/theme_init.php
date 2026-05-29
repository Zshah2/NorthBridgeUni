<script>
(function () {
  try {
    var saved = localStorage.getItem('theme');
    var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    var isDark = saved ? saved === 'dark' : prefersDark;
    document.documentElement.classList.toggle('dark', !!isDark);
    if (document.body && document.body.classList.contains('nb-staff')) {
      document.documentElement.classList.toggle('nb-staff-active', !!isDark);
    }
  } catch (e) {}
})();
</script>
