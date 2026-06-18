<script>
(function () {
  try {
    var saved = localStorage.getItem('theme');
    var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    var isDark = saved ? saved === 'dark' : prefersDark;
    document.documentElement.classList.toggle('dark', !!isDark);
    if (document.body) {
      if (document.body.classList.contains('nb-staff')) {
        document.documentElement.classList.toggle('nb-staff-active', !!isDark);
      }
      if (document.body.classList.contains('nb-site')) {
        document.documentElement.classList.toggle('nb-site-active', !!isDark);
      }
    }
  } catch (e) {}
})();
</script>
