<?php
/** @var array $app */
/** @var ?string $pageTitle */
$docTitle = $app['site']['name'];
if (isset($pageTitle) && is_string($pageTitle) && $pageTitle !== '') {
    $docTitle = $pageTitle . ' — ' . $app['site']['name'];
}
?>
<title><?= htmlspecialchars($docTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($app['site']['description']) ?>" />
<meta name="theme-color" content="<?= htmlspecialchars($app['site']['themeColor']) ?>" />

