<?php
	// Page header shared by the web UI pages: everything from the doctype
	// to the subtitle under the logo. Set before including:
	//   $pageClass    - body class selecting the page styles in p350.css
	//   $pageScripts  - URLs of the page scripts, loaded in this order
	//   $pageSubtitle - text under the logo
	// Each page script defines init(), called once the page has loaded.
	require_once __DIR__ . '/inc_common.php';
?>
<!doctype html>
<html lang=en>
<head>
<meta charset=utf-8>
<title><?php echo htmlspecialchars(getInstallationName()); ?></title>
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="192x192" href="/android-chrome-192x192.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="manifest" href="/site.webmanifest">
<link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
<meta name="msapplication-TileColor" content="#90c73d">
<meta name="msapplication-TileImage" content="/mstile-144x144.png">
<meta name="theme-color" content="#ffffff">
<link rel="stylesheet" href="/p350.css">
<?php foreach ($pageScripts as $script) { ?>
<script type="text/javascript" src="<?php echo htmlspecialchars($script); ?>"></script>
<?php } ?>
</head>
<body class="<?php echo htmlspecialchars($pageClass); ?>" onload="init()">
<center>
<img src="/logo.png" width="400px"/>
<h3><i><?php echo htmlspecialchars($pageSubtitle); ?></i></h3>
