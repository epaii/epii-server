<?php
$this_dir = str_replace("\\", "/", __DIR__);
$base_root = str_replace(DIRECTORY_SEPARATOR . "bin", "", __DIR__);
$base_root = str_replace("\\", "/", $base_root);
$is_win = strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
$shall_ext = $is_win ? "bat" : "sh";
$config_file = $base_root . DIRECTORY_SEPARATOR . "config.ini";


function config()
{
    global $config_file;
    static $config;
    if (!$config) {
        $config = parse_ini_file($config_file, true);
        foreach ($config as $key => $value) {
            foreach ($value as $k => $v) {
                $config[$key][$k] = str_replace("\\", "/", $v);
            }
        }
    }
    return $config;

}
function is_win()
{
    return strtoupper(substr(PHP_OS,0,3)) == 'WIN';
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
    $config = config();
    $apps = $config["app_dir"];
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

function app_info($dir)
{

    $config = config();
    $apps = $config["app_dir"];

    $dir = str_replace("\\", "/", $dir);
    $names = array_keys(array_filter($apps, function ($item) use ($dir) {
        return $item == $dir;
    }));
    if ($names) {
        $string = PHP_EOL."当前目录所属APP:" . implode(",", $names);
        foreach ($names as $name){
            $string .= PHP_EOL."http://".$config["server"]["this_ip"]."/app/".$name;
            $string .= PHP_EOL."http://".$name.".".$config["server"]["domain_this"];
        }
        show_success($string );
    } else {
        show_error("没有找到App");
    }


}

function app_remove($name, $dir = null)
{
    global $config_file;
    $config = config();
    $apps = $config["app_dir"];
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
    print_r($config["app_dir"]);
}

function app_ls()
{
    app_list();
}

function do_start()
{
    if(is_win())
    {
        runcmd(__DIR__."/../start.cmd");
    }else{
        runcmd(__DIR__."/../start.sh");
    }

}

function do_stop()
{
    if(is_win())
    {
        runcmd(__DIR__."/../stop.cmd");
    }else{
        runcmd(__DIR__."/../stop.sh");
    }
}

function do_reload()
{
    do_stop();
    do_start();
}
function runcmd($cmd)
{

    if(strtoupper(substr(PHP_OS,0,3)) == 'WIN')
    {
        pclose(popen('start /B '. $cmd, 'r'));
    }else
    {
        pclose(popen($cmd.' > /dev/null &', 'r'));
    }
}
if ($argc == 1) {
    do_start();
    exit;
}

if ($argc == 3) {
    call_user_func("do_".$argv[1]);
    exit;
}
if ($argc > 3) {
    $mod = $argv[1];
    if ($mod == "app") {
        $ac = $argv[2];
        call_user_func_array("app_" . $ac, array_slice($argv, 3));
        exit;
    }
}