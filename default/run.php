<?php

$lockfile = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "install" . DIRECTORY_SEPARATOR . ".time";

if (file_exists($lockfile)) {
    $time = (int) file_get_contents($lockfile);
} else {
    $time = 0;
}

$config_file = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "config.ini";
if (!file_exists($config_file)) {
    echo "It is not find config.ini,You can copy config.ini.example to config.ini  to set you config";
    exit;
}
if (true || (filemtime($config_file) > $time)) {
    echo "re install \n";
    $include_file = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "install" . DIRECTORY_SEPARATOR . "install.php";
    require $include_file;
}

$is_win = strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
function runcmd($cmd)
{

    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
        pclose(popen('start /B ' . $cmd, 'r'));
    } else {
        pclose(popen($cmd.' > /dev/null 2>&1 &', 'r'));
        //var_dump($pid = shell_exec($cmd . " && echo $!"));
    }
}
function runcmd_log($cmd)
{

    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
        pclose(popen('start /B ' . $cmd, 'r'));
    } else {
        pclose(popen($cmd." 2>&1 &", 'r'));
       
    }
}
require_once __DIR__."/start.php";

exit;
