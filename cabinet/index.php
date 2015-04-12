<?php
session_start();

// автозагрузчик классов
spl_autoload_register(function ($class) {
	$prefix = 'ld\\cnamrf\\';
	$basedir = __DIR__ . '/src/';
	$len = strlen($prefix);

	if (strncmp($prefix, $class, $len) !== 0) {
		return;
	}

	$relative_class = substr($class, $len);
	$file = $basedir . str_replace('\\', '/', $relative_class) . '.php';

	if (file_exists($file)) {
		require $file;
	}
});

$cabinet = new ld\cnamrf\cabinet;

if ($_SESSION['auth'] == 'true') 
{
	$cabinet->dashboard();
}
else
{
	$cabinet->auth();
}
?>