<?php
/** @var array $app */
/** @var string $content */
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <?php require view_path('partials/seo.php'); ?>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            fontFamily: { sans: ["Inter", "ui-sans-serif", "system-ui", "-apple-system", "Segoe UI", "Roboto", "Helvetica Neue", "Arial"] },
            colors: {
              brand: {
                50: "#eff6ff",
                100: "#dbeafe",
                200: "#bfdbfe",
                300: "#93c5fd",
                400: "#60a5fa",
                500: "#3b82f6",
                600: "#2563eb",
                700: "#1d4ed8",
                800: "#1e40af",
                900: "#1e3a8a",
                950: "#172554",
              },
            },
          },
        },
      };
    </script>
    <link rel="stylesheet" href="<?= htmlspecialchars(url('/assets/css/app.css')) ?>" />
  </head>
  <body class="bg-slate-950 text-slate-100 antialiased selection:bg-sky-500/30 selection:text-slate-50">
    <a class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-slate-100 ring-1 ring-white/10" href="#main">
      Skip to content
    </a>

    <?php require view_path('partials/navbar.php'); ?>

    <main id="main">
      <?= $content ?>
    </main>

    <?php require view_path('partials/footer.php'); ?>

    <script src="<?= htmlspecialchars(url('/assets/js/app.js')) ?>"></script>
  </body>
</html>

