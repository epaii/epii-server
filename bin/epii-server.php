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
        $string = PHP_EOL."APP:" . implode(",", $names);
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

function domain_list()
{
    $config = config();
    $list=[];
    foreach ($config["domain_app"] as $key=>$value){
        $list[$key]=["app"=>$value,"dir"=>isset($config["app_dir"][$value])?$config["app_dir"][$value]:""];
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
        if (!isset($config["app_dir"][$app]))
        {
            show_error("App:".$app." is not exist");
            exit;
        }
        $apps[$name] = $app;
        $string = [];
        foreach ($apps as $key => $value) {
            $string[] = $key . "=" . $value;
        }
        $file_content = file_get_contents($config_file);
        $file_content = preg_replace("/\[domain_app\](.*?)$/is", "[domain_app]" . PHP_EOL . implode(PHP_EOL, $string) , $file_content);
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

        unset( $apps[$name]) ;
        $string = [];
        foreach ($apps as $key => $value) {
            $string[] = $key . "=" . $value;
        }
        $file_content = file_get_contents($config_file);
        $file_content = preg_replace("/\[domain_app\](.*?)$/is", "[domain_app]" . PHP_EOL . implode(PHP_EOL, $string) , $file_content);
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

    echo "|epii-server config|配置详情|".PHP_EOL.
"|epii-server start|启动服务|".PHP_EOL.
"|epii-server stop|暂停服务|".PHP_EOL.
"|epii-server restart|重启启动服务|".PHP_EOL.
"|epii-server app list/ls|显示所有应用|".PHP_EOL.
"|epii-server app add {appname}|为当前目录为新应用|".PHP_EOL.
"|epii-server app remove|删除当前目录对应的应用|".PHP_EOL.
"|epii-server app remove {appname}|删除应用|".PHP_EOL.
"|epii-server app info|显示当前目录对应的应用信息|".PHP_EOL.
"|epii-server domain list\ls|域名列表|".PHP_EOL.
"|epii-server domain add {domain} {appname}|新增域名绑定|".PHP_EOL.
"|epii-server domain remove {domain}|解除域名绑定|".PHP_EOL;

}
function do_start()
{
		include __DIR__."/../default/start.php";


}

function do_stop()
{

    if(is_win())
    {
		 
        runcmd_this(__DIR__."/../stop.bat");
    }else{
        runcmd_this(__DIR__."/../stop.sh");
    }
}

function do_reload()
{
    do_stop();
    do_start();
}
function runcmd_this($cmd)
{

	$cmd = str_replace("\\","/",$cmd);
 
    if(strtoupper(substr(PHP_OS,0,3)) == 'WIN')
    {
		system($cmd);
       // pclose(popen('start /B '. $cmd, 'r'));
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
	 if (function_exists("do_".$argv[1]))
         call_user_func("do_".$argv[1]);
    exit;
}
if ($argc > 3) {
    $mod = $argv[1];
    if ($mod == "app") {
        $ac = $argv[2];
        if (function_exists("app_" . $ac))
        call_user_func_array("app_" . $ac, array_slice($argv, 3));
        exit;
    }else if ($mod == "domain") {
        $ac = $argv[2];
        if (function_exists("domain_" . $ac))
            call_user_func_array("domain_" . $ac, array_slice($argv, 3));
        exit;
    }
}