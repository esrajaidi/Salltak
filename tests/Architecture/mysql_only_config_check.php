<?php

$root = dirname(__DIR__, 2);
$checks = [];

$assertContains = function (string $file, string $needle, string $label) use (&$checks, $root): void {
    $content = file_get_contents($root.'/'.$file);
    $checks[] = [$label, str_contains($content, $needle), "$file should contain: $needle"];
};

$assertNotContains = function (string $file, string $needle, string $label) use (&$checks, $root): void {
    $content = file_get_contents($root.'/'.$file);
    $checks[] = [$label, !str_contains(strtolower($content), strtolower($needle)), "$file should not contain: $needle"];
};

$assertContains('.env.example', 'DB_CONNECTION=mysql', 'env defaults to MySQL');
$checks[] = ['SQLite database file removed', !file_exists($root.'/database/database.sqlite'), 'database/database.sqlite should not exist'];
$assertContains('.env', 'DB_CONNECTION=mysql', 'runtime env uses MySQL');
$assertContains('.env', 'DB_DATABASE=salltak', 'runtime env uses salltak database');
$assertContains('.env.example', 'DB_DATABASE=salltak', 'env uses salltak database');
$assertContains('config/database.php', "env('DB_CONNECTION', 'mysql')", 'database config defaults to MySQL');
$assertNotContains('config/database.php', "'sqlite' =>", 'SQLite connection removed');
$assertContains('config/queue.php', "env('DB_CONNECTION', 'mysql')", 'queue fallback uses MySQL');
$assertContains('phpunit.xml', '<env name="DB_CONNECTION" value="mysql"/>', 'PHPUnit uses MySQL');
$assertContains('phpunit.xml', '<env name="DB_DATABASE" value="salltak_test"/>', 'PHPUnit uses isolated test database');
$assertNotContains('setup.bat', 'database.sqlite', 'Windows setup does not create SQLite');
$assertNotContains('setup.sh', 'database.sqlite', 'Unix setup does not create SQLite');
$assertNotContains('README.md', 'database.sqlite', 'README no longer instructs SQLite');
$assertContains('database/mysql_setup.sql', 'CREATE DATABASE IF NOT EXISTS `salltak`', 'MySQL bootstrap creates app database');
$assertContains('database/mysql_setup.sql', 'CREATE DATABASE IF NOT EXISTS `salltak_test`', 'MySQL bootstrap creates test database');

$failed = 0;
foreach ($checks as [$label, $ok, $message]) {
    echo ($ok ? '[PASS] ' : '[FAIL] ').$label.PHP_EOL;
    if (!$ok) {
        echo '       '.$message.PHP_EOL;
        $failed++;
    }
}

echo PHP_EOL.count($checks).' checks, '.$failed.' failures'.PHP_EOL;
exit($failed === 0 ? 0 : 1);
