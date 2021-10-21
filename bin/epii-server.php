<?php

error_reporting(0);
$this_dir = str_replace("\\", "/", __DIR__);
$base_root = str_replace(DIRECTORY_SEPARATOR . "bin", "", __DIR__);
$base_root = str_replace("\\", "/", $base_root);
$is_win = strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
$shall_ext = $is_win ? "bat" : "sh";
$config_file = $base_root . DIRECTORY_SEPARATOR . "config.ini";
$host_file = is_win() ? 'C:/Windows/System32/drivers/etc/hosts' : '/etc/hosts';


function config($app_all = true)
{
    static $config;
    if(!$config){
        $config = json_decode(file_get_contents(__DIR__."/runtime.json"),true);
    }
   
    return $config;
}
function is_win()
{
    return strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
}

function show_error($msg)
{
    echo $msg . PHP_EOL;
}

function show_success($msg)
{
    echo "Success:" . $msg . PHP_EOL;
}

function app_add($name, $dir)
{

    global $config_file;
    $config = config(false);
    $apps = $config["app_dir_in_config"];
    if (isset($apps[$name])) {
        show_error("Error:App is exist;" . $apps[$name]);
        exit;
    } else {
        $apps[$name] = str_replace("\\", "/", $dir);
        $string = [];
        foreach ($apps as $key => $value) {
            $string[] = $key . "=" . $value;
        }
        $file_content = file_get_contents($config_file);
        $file_content = preg_replace("/\[app_dir\](.*?)\[app_php_select\]/is", "[app_dir]" . PHP_EOL . implode(PHP_EOL, $string) . PHP_EOL . "[app_php_select]", $file_content);
        file_put_contents($config_file, $file_content);
        show_success("创建成功");
    }
}

function app_info($name, $dir = null)
{

    $config = config();
    $apps = $config["app_dir"];
    $names = [];

    if (!$dir) {
        $name = str_replace("\\", "/", $name);
        $names = array_keys(array_filter($apps, function ($item) use ($name) {
            return $item == $name;
        }));
    } else {
        if (isset($apps[$name]))
            $names = [$name];
    }
    if ($names) {


        foreach ($names as $name) {
            $string = PHP_EOL . "APP:" . $names;
            $string = PHP_EOL . "Location:" . $apps[$name];
            $string .= PHP_EOL . "http://" . $config["server"]["this_ip"] . "/app/" . $name;
            $string .= PHP_EOL . "http://" . $name . "." . $config["server"]["domain_this"];
        }
        show_success($string);
    } else {
        show_error("没有找到App");
    }
}

function _app_getinfo($name, $dir = null)
{
    $names = null;
    $config = config();
    $apps = $config["app_dir"];
    if (!$dir) {
        $name = str_replace("\\", "/", $name);
        $names = array_keys(array_filter($apps, function ($item) use ($name) {
            return $item == $name;
        }));
    } else {
        if (isset($apps[$name]))
            $names = [$name];
    }
    if ($names) {
        $out = [];
        foreach ($names as $name) {
            $out[] = ["name" => $name, "dir" => $apps[$name], "url" => "http://" . $config["server"]["this_ip"] . "/app/" . $name, "url_domain" => "http://" . $name . "." . $config["server"]["domain_this"]];
        }
        return $out;
    } else {
        return null;
    }
}

function app_open($name, $dir = null)
{
    $webs = _app_getinfo($name, $dir);
    $host = initHosts();

    if ($webs) {
        foreach ($webs as $web) {

            $domain = $web["name"] . "." . config()["server"]["domain_this"];

            $url = isset($host[$domain]) ? $web["url_domain"] : $web["url"];
            if (is_win()) {
                system("start " . $url);
            } else
                system("open " . $url);
        }
    } else {
        show_error("没有找到App");
    }
}

function app_remove($name, $dir = null)
{
    global $config_file;
    $config = config(false);
    $apps = $config["app_dir_in_config"];
    if (!$dir) {
        $name = str_replace("\\", "/", $name);
        $names = array_keys(array_filter($apps, function ($item) use ($name) {
            return $item == $name;
        }));
    } else {
        $names = [$name];
    }
    $rewrite = false;
    foreach ($names as $name) {
        if (!isset($apps[$name])) {
            show_error("Error:App is not exist;" . $apps[$name]);
            exit;
        } else {
            $rewrite = true;
            unset($apps[$name]);
        }
    }
    if ($rewrite) {
        $string = [];
        foreach ($apps as $key => $value) {
            $string[] = $key . "=" . $value;
        }
        $file_content = file_get_contents($config_file);
        $file_content = preg_replace("/\[app_dir\](.*?)\[app_php_select\]/is", "[app_dir]" . PHP_EOL . implode(PHP_EOL, $string) . PHP_EOL . "[app_php_select]", $file_content);
        file_put_contents($config_file, $file_content);
        show_success("删除" . implode(",", $names) . "成功");
    }
}

function app_list()
{
    $config = config();
 
    print_r( $config["app_dir"]);
    if($config["app_proxy_pass"]){
        print_r($config["app_proxy_pass"]);
    }
    if($config["domain_proxy_pass"]){
        print_r($config["domain_proxy_pass"]);
    }
    if($config["app_spring_boot_info"]){
        print_r($config["app_spring_boot_info"]);
    }
    
}

function app_ls()
{
    app_list();
}

function app_start($name){
    $config = config();
    if(isset($config["app_spring_boot_info"][$name])){
        echo "start ".$config["app_spring_boot_info"][$name]["jar"].PHP_EOL;
        runcmd_log($config["server"]["java_cmd"].' -jar '.$config["app_spring_boot_info"][$name]["jar"].' --server.port='.$config["app_spring_boot_info"][$name]["port"].' --spring.profiles.active=pro --from-epii-server --app-of-'.$name.' >'.$config["server"]["log_dir"].DIRECTORY_SEPARATOR.$name.".java.log");
    }
}

function domain_list()
{
    $config = config();
    $list = [];
    foreach ($config["domain_app"] as $key => $value) {
        $list[$key] = ["app" => $value, "dir" => isset($config["app_dir"][$value]) ? $config["app_dir"][$value] : ""];
    }
    print_r($list);
}
function domain_add($name, $app)
{

    global $config_file;
    $config = config();
    $apps = $config["domain_app"];
    if (isset($apps[$name])) {
        show_error("Error:Domain is exist;" . $apps[$name]);
        exit;
    } else {
        if (!isset($config["app_dir"][$app])) {
            show_error("App:" . $app . " is not exist");
            exit;
        }
        $apps[$name] = $app;
        $string = [];
        foreach ($apps as $key => $value) {
            $string[] = $key . "=" . $value;
        }
        $file_content = file_get_contents($config_file);
        if(strpos($file_content,"[end]")>0){
            $file_content = preg_replace("/\[domain_app\](.*?)\[/is", "[domain_app]" . PHP_EOL . implode(PHP_EOL, $string).PHP_EOL."[", $file_content);
        }else{
            $file_content = preg_replace("/\[domain_app\](.*?)$/is", "[domain_app]" . PHP_EOL . implode(PHP_EOL, $string), $file_content);
        }
        
        file_put_contents($config_file, $file_content);
        show_success("创建成功");
    }
}
function domain_remove($name)
{

    global $config_file;
    $config = config();
    $apps = $config["domain_app"];
    if (!isset($apps[$name])) {
        show_error("Error:Domain is not exist;" . $apps[$name]);
        exit;
    } else {

        unset($apps[$name]);
        $string = [];
        foreach ($apps as $key => $value) {
            $string[] = $key . "=" . $value;
        }
        $file_content = file_get_contents($config_file);
        $file_content = preg_replace("/\[domain_app\](.*?)$/is", "[domain_app]" . PHP_EOL . implode(PHP_EOL, $string), $file_content);
        file_put_contents($config_file, $file_content);
        show_success("删除域名成功");
    }
}
function do_config()
{
    print_r(config());
}
function do_help()
{

    echo "|epii-server config|配置详情|" . PHP_EOL .
        "|epii-server start|启动服务|" . PHP_EOL .
        "|epii-server stop|暂停服务|" . PHP_EOL .
        "|epii-server restart|重启启动服务|" . PHP_EOL .
        "|epii-server app list/ls|显示所有应用|" . PHP_EOL .
        "|epii-server app add {appname}|为当前目录为新应用|" . PHP_EOL .
        "|epii-server app remove|删除当前目录对应的应用|" . PHP_EOL .
        "|epii-server app remove {appname}|删除应用|" . PHP_EOL .
        "|epii-server app info {appname}|显示当前目录对应的应用信息|" . PHP_EOL .
        "|epii-server app open |打开当前目录对应的应用网址|" . PHP_EOL .
        "|epii-server app open {appname}|打开指定应用网址|" . PHP_EOL .
        "|epii-server app opendir |打开当前目录对应的目录|" . PHP_EOL .
        "|epii-server app opendir {appname}|打开指定应用目录|" . PHP_EOL .
        "|epii-server app dir {appname}|仅仅显示应用目录|" . PHP_EOL .
        "|epii-server hosts list\ls|本地域名列表|" . PHP_EOL .
        "|epii-server hosts addall|本地域名全部添加,需要管理员权限|" . PHP_EOL .
        "|epii-server hosts add {appname}|本地域名添加,需要管理员权限|" . PHP_EOL .
        "|epii-server hosts clear|清除相关本地域名添加,需要管理员权限|" . PHP_EOL .
        "|epii-server domain list\ls|外网域名列表|" . PHP_EOL .
        "|epii-server domain add {domain} {appname}|新增外网域名绑定|" . PHP_EOL .
        "|epii-server domain remove {domain}|解除域名绑定|" . PHP_EOL.
        "|epii-server app stop {java_app_name}|java 项目单个项目关闭|" . PHP_EOL .
        "|epii-server app start {java_app_name}|java 项目单个项目启动|" . PHP_EOL .
        "|epii-server app restart {java_app_name}|java 项目单个项目重启|" . PHP_EOL .
        "|epii-server reinstall |重新生成配置文件|" . PHP_EOL .
        "|epii-server reload |重新生成配置文件 并让nginx重新加载|" . PHP_EOL ;
}
function do_start()
{
    include __DIR__ . "/../default/start.php";
}

function do_stop()
{

    if (is_win()) {

        runcmd_this(__DIR__ . "/../stop.bat");
    } else {
        runcmd_this(__DIR__ . "/../stop.sh");
    }
}

function do_reload()
{
    do_stop();
    do_start();
}
function runcmd_this($cmd)
{

    $cmd = str_replace("\\", "/", $cmd);

    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
        system($cmd);
        // pclose(popen('start /B '. $cmd, 'r'));
    } else {
        pclose(popen($cmd . ' > /dev/null &', 'r'));
    }
}
function initHosts()
{
    global $host_file;
    $hosts = [];
    $lines = file($host_file);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] == '#') {
            continue;
        }
        $item = preg_split('/\s+/', $line);
        $hosts[$item[1]] = $item[0];
    }
    return $hosts;
}
function write_hosts($hosts)
{
    global $host_file;
    $str = '';
    foreach ($hosts as $domain => $ip) {
        $str .= $ip . "\t" . $domain . PHP_EOL;
    }
    return file_put_contents($host_file, $str);
}
function hosts_ls()
{
    print_r(initHosts());
}
function hosts_list()
{
    print_r(initHosts());
}
function hosts_addall()
{
    $hosts = initHosts();
    $config = config();
    $applist = $config["app_dir"];
    foreach ($applist as $app => $dir) {
        $domain = $app . "." . $config["server"]["domain_this"];
        if (!isset($hosts[$domain])) {
            $hosts[$domain] = $config["server"]["this_ip"];
        }
    }
    if (!write_hosts($hosts)) {
        show_error("写入失败");
    } else {
        show_success("操作成功");
    }
}
function hosts_clear()
{
    $hosts = initHosts();
    $config = config();
    $applist = $config["app_dir"];
    foreach ($applist as $app => $dir) {
        $domain = $app . "." . $config["server"]["domain_this"];
        if (isset($hosts[$domain])) {
            unset($hosts[$domain]);
        }
    }
    if (!write_hosts($hosts)) {
        show_error("写入失败");
    } else {
        show_success("操作成功");
    }
}
function hosts_add($name, $dir = null)
{
    $hosts = initHosts();
    $config = config();


    $domain = $name . "." . $config["server"]["domain_this"];
    if (!isset($hosts[$domain])) {
        $hosts[$domain] = $config["server"]["this_ip"];
    }

    if (!write_hosts($hosts)) {
        show_error("写入失败" . $name);
    } else {
        show_success("操作成功" . $name);
    }
}


function app_opendir($name, $dir = null)
{
    $webs = _app_getinfo($name, $dir);
    $host = initHosts();

    if ($webs) {
        foreach ($webs as $web) {


            if (is_win()) {
                system("explorer " . str_replace("/", "\\", $web["dir"]));
            } else
                system("open " . $web["dir"]);
        }
    } else {
        show_error("没有找到App");
    }
}
function app_dir($name, $dir = null)
{
    $webs = _app_getinfo($name, $dir);

    if ($webs) {
        foreach ($webs as $web) {

            echo $web["dir"];
            exit;
        }
    } else {
        show_error("没有找到App");
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

function exe_reload(){
    echo 'reloading...';
    exe_reinstall();
    runcmd_this('nginx -s reload');
    echo 'reload finish';
}
function exe_reinstall(){
    echo 'reinstall';
    runcmd_this('php '.__DIR__.'/../install/install.php');
    //require_once __DIR__.'/../install/install.php';
    echo 'reinstall finish';
}

if (function_exists("exe_" . $argv[1])){
    call_user_func("exe_" . $argv[1]);
}

if ($argc == 1) {
    do_start();
    exit;
}


if ($argc == 3) {
    if (function_exists("do_" . $argv[1]))
        call_user_func("do_" . $argv[1]);
    exit;
}
if ($argc > 3) {
    $mod = $argv[1];
    $ac = $argv[2];
    if (function_exists($mod . "_" . $ac))
        call_user_func_array($mod . "_" . $ac, array_slice($argv, 3));
    exit;
}
