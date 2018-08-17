<?php
/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2018/7/27
 * Time: 上午10:19
 */
error_reporting(0);
$base_root = str_replace(DIRECTORY_SEPARATOR . "install", "", __DIR__);
$this_dir = __DIR__;
$is_win = strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
$shall_ext = $is_win ? "bat" : "sh";
$_SERVER['_'] = getPhp();
$ini = parse_ini_file($base_root . DIRECTORY_SEPARATOR . "config.ini", true);
$ini['server']['nginx_root'] = dirname($ini['nginx']['cmd']);
if (!isset($ini['server']['php_cmd']))
{
    $ini['server']['php_cmd'] = defined("PHP_BINARY")?PHP_BINARY:"php";
}



if (!isset($ini['server']['domain_other'])) {
    $ini['server']['domain_other'] = "pc.jt";
}
if (!isset($ini['server']['www_dir'])) $ini['server']['www_dir'] = $base_root . DIRECTORY_SEPARATOR . "web";


$find = ["domain_app", "base_root", "this_ip", "this_port", "php_root", "nginx_root", "domain_this", "domain_other", "domain_this_1", "domain_other_1", "www_dir", "nginx_cmd", "php_cmd"];
$replace = [implode(" ", array_keys($ini['domain_app'])), $base_root, $ini['server']['this_ip'], $ini['server']['this_port'], $ini['php']['root'][0], $ini['server']['nginx_root'], $ini['server']['domain_this'], $ini['server']['domain_other'], str_replace(".", "\\.", $ini['server']['domain_this']), str_replace(".", "\\.", $ini['server']['domain_other']), isset($ini['server']['www_dir']) ? $ini['server']['www_dir'] : $base_root . DIRECTORY_SEPARATOR . "web", $ini['nginx']['cmd'], $ini['server']['php_cmd']];

$root_dir = "";
foreach ($ini['root_dir'] as $key => $value) {
    $root_dir .= parse_tpl($this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . "nginx_root_dir.tpl", ["app", "dir"], [$key, $value]);
}

$find[] = "root_dir";
$replace[] = $root_dir;


$php_port = "";
foreach ($ini['app_php_select'] as $key => $value) {
    $php_port .= parse_tpl($this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . "nginx_php_port.tpl", ["app", "port"], [$key, $ini['php']['port'][$value]]);
}

$find[] = "php_port";
$replace[] = $php_port;
$find[] = "php_port_0";
$replace[] = $ini['php']['port'][0];


$app_dir = "";
foreach ($ini['app_dir'] as $key => $value) {
    $app_dir .= parse_tpl($this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . "nginx_app_dir.tpl", ["app", "dir"], [$key, $value]);
}


$find[] = "app_dir";
$replace[] = $app_dir;


$domian_when_ip = $ini['server']['default_app'];
$find[] = "domain_when_ip";
if ($domian_when_ip) {

    $replace[] = parse_tpl($this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . "nginx_domain_when_ip.tpl", ["this_ip", "default_app"], [$ini['server']['this_ip'], $ini['server']['default_app']]);;
} else {
    $replace[] = "";
}


$domain_app_list = "";
foreach ($ini['domain_app'] as $key => $value) {
    $appinfo = explode("/", $value);
    for ($i = 0; $i < 4; $i++) {
        if (!$appinfo[$i]) $appinfo[$i] = -1;
    }
    $domain_app_list .= parse_tpl($this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . "nginx_domain_app.tpl", ["domain", "app", "app_sub", "app_sub2", "app_sub3"], [$key, $appinfo[0], $appinfo[1], $appinfo[2], $appinfo[3]]);
}

$find[] = "domain_app_list";
$replace[] = $domain_app_list;


$nignx_config_tpl = $this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . 'ws.conf.tpl';
$nignx_config = $base_root . DIRECTORY_SEPARATOR . "configs" . DIRECTORY_SEPARATOR . "nginx" . DIRECTORY_SEPARATOR . 'ws.conf';


parse_tpl($nignx_config_tpl, $find, $replace, $nignx_config);


$nignx_config_root = $ini['nginx']['nginx_config_file'];#$ini['server']['nginx_root'] . DIRECTORY_SEPARATOR . "conf" . DIRECTORY_SEPARATOR . "nginx.conf";
if (!is_file($nignx_config_root)) {
    echo "\n  error:not find nginx\n";
    // exit;
}

$myconfig = file_get_contents($nignx_config_root);
$myconfig = substr($myconfig, 0, strrpos($myconfig, "}"));
$pd = "include " . $base_root . DIRECTORY_SEPARATOR . "configs" . DIRECTORY_SEPARATOR . "nginx" . DIRECTORY_SEPARATOR . "*.conf;";
$pd = str_replace(DIRECTORY_SEPARATOR, "/", $pd);
$myconfig = str_replace($pd, "", $myconfig);
file_put_contents($nignx_config_root, $myconfig . "\r\n" . $pd . "\r\n}");


$php_bat = "";
foreach ($ini['php']['php_cgi'] as $key => $value) {
    $php_bat .= parse_tpl($this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . "start.php.tpl", ["base_root", "i", "port", "root", "cmd"], [$base_root, $key, $ini['php']['port'][$key], dirname($value), $value]);
}

$find[] = "php_bat";
$replace[] = $php_bat;


$start_tpl = $this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . 'start.cmd.php.tpl';
$start_bat = $base_root . DIRECTORY_SEPARATOR . "default" . DIRECTORY_SEPARATOR . "start.php";
parse_tpl($start_tpl, $find, $replace, $start_bat);


$start_tpl = $this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . 'start.bat.tpl';
$start_bat = $base_root . DIRECTORY_SEPARATOR . "start." . $shall_ext;
parse_tpl($start_tpl, ['php_cmd'], [$ini['server']['php_cmd']], $start_bat);


$stop_tpl = $this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . 'stop.' . $shall_ext . '.tpl';
$staop_bat = $base_root . DIRECTORY_SEPARATOR . "stop." . $shall_ext;
parse_tpl($stop_tpl, $find, $replace, $staop_bat);

//$re_install_tpl = $this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . 're_install.'.$shall_ext.'.tpl';
//$re_install_bat = $base_root . DIRECTORY_SEPARATOR . "re_install.".$shall_ext;
//parse_tpl($re_install_tpl, $find, $replace, $re_install_bat);

if (!$is_win) {
    // chmod($re_install_bat,777);
    chmod($staop_bat, 0777);
    chmod($start_bat, 0777);
    chmod($nignx_config, 0777);

}

$lock_file = __DIR__ . DIRECTORY_SEPARATOR . ".time";
file_put_contents($lock_file, filectime($lock_file));

foreach (["web", "logs"] as $d) {
    $web_dir = $base_root . DIRECTORY_SEPARATOR . $d;
    if (!is_dir($web_dir)) {
        mkdir($web_dir, 0777, true);
    }
}


function parse_tpl($tpl_file, $find, $replace, $to_file = null)
{
    if (is_string($find)) {
        $find = ["/\{\{" . $find . "\}\}/is"];
        $replace = [$replace];
    } else {
        foreach ($find as $key => $value) {
            $find[$key] = "/\{\{" . $value . "\}\}/is";
        }
    }
    $txt = preg_replace($find, $replace, file_get_contents($tpl_file));
    if ($to_file) {
        if (!is_dir($todir = dirname($to_file))) {
            mkdir($todir, 0777, true);
        }
        file_put_contents($to_file, $txt);
    } else {
        return $txt;
    }

}


function getPhp()
{
    if (substr(strtolower(PHP_OS), 0, 3) == 'win') {

        $ini = ini_get_all();

        $path = $ini['extension_dir']['local_value'];

        $php_path = str_replace('\\', '/', $path);

        $php_path = str_replace(array('/ext/', '/ext'), array('/', '/'), $php_path);

        $real_path = $php_path . 'php.exe';

    } else {

        $real_path = PHP_BINDIR . '/php';

    }

    if (strpos($real_path, 'ephp.exe') !== FALSE) {

        $real_path = str_replace('ephp.exe', 'php.exe', $real_path);

    }
    return  $real_path = PHP_BINDIR . '/php';
}