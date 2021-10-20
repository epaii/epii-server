<?php

/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2018/7/27
 * Time: 上午10:19
 */
error_reporting(0);
$this_dir = str_replace("\\", "/", __DIR__);
$base_root = str_replace(DIRECTORY_SEPARATOR . "install", "", __DIR__);
$base_root = str_replace("\\", "/", $base_root);
$is_win = strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
$shall_ext = $is_win ? "bat" : "sh";
$_SERVER['_'] = getPhp();
$ini = parse_ini_file($base_root . DIRECTORY_SEPARATOR . "config.ini", true);




foreach ($ini as $key => $value) {
    foreach ($value as $k => $v) {
        $ini[$key][$k] = str_replace("\\", "/", $v);
    }
}
if (!isset($ini["app_proxy_pass"])) {
    $ini["app_proxy_pass"] = [];
}
if (!isset($ini["domain_proxy_pass"])) {
    $ini["domain_proxy_pass"] = [];
}

if (!isset($ini["app_spring_boot"])) {
    $ini["app_spring_boot"] = [];
}



if ($is_win) {
    $ini['server']['nginx_root'] = " -p " . dirname($ini['nginx']['cmd']);
} else {
    $ini['server']['nginx_root'] = "";
}

if (!isset($ini['server']['php_cmd'])) {
    $ini['server']['php_cmd'] = getPhp();
}

if (!isset($ini['server']['domain_other'])) {
    $ini['server']['domain_other'] = "pc.jt";
}
$ini["app_dir_in_config"] = $ini["app_dir"];

function parse_dir($name, $dvalue)
{
    global $base_root, $ini;
    if (!isset($ini['server'][$name])) {
        $ini['server'][$name] = $base_root . DIRECTORY_SEPARATOR . $dvalue;
    }

    if (!((stripos($ini['server'][$name], "/") === 0) || (stripos($ini['server'][$name], ":") === 1))) {
        $ini['server'][$name] = $base_root . DIRECTORY_SEPARATOR . $ini['server'][$name];
    }
    if (!is_dir($ini['server'][$name])) {
        mkdir($ini['server'][$name], 0777, true);
    }
}
parse_dir("www_dir", "web");

$web_ini_file = $ini['server']['www_dir'].DIRECTORY_SEPARATOR."epii-server.ini";
if(file_exists($web_ini_file)){
    $web_ini = parse_ini_file($web_ini_file, true);
    if($web_ini)
    {
        foreach($web_ini as $key=>$values){
            if($values){
                foreach ($values as $key1 => $value1) {
                    $ini[$key][$key1] = $value1;
                }
            }
        }
    }

}
 
if (isset($ini['server']['www_dir'])) {
    if (!is_dir($ini['server']['www_dir'])) {
        mkdir($ini['server']['www_dir'], 0777, true);
    }
    $file_arr = scandir($ini['server']['www_dir']);

    foreach ($file_arr as $item) {

        if ($item != ".." && $item != ".") {

            if (is_dir($tmp_dir = $ini['server']['www_dir'] . "/" . $item)) {
                if (file_exists($tmp_config_file = $tmp_dir . "/epii-server.ini")) {

                    $this_ini = parse_ini_file($tmp_config_file, true);
                    if ($this_ini) {

                        $this_ini = array_merge(["app" => $item, "root" => "", "app_type" => "php_web"], $this_ini);


                        if ($this_ini["app_type"] == "php_web") {
                            $ini['app_dir'][$this_ini["app"]] = $tmp_dir . "/" . $this_ini["root"];
                            if (isset($this_ini["php_select"])) {
                                $ini['app_php_select'][$this_ini["app"]] = $this_ini["php_select"];
                            }
                            if (isset($this_ini["server_name"])) {
                                $_domains = explode(" ", $this_ini["server_name"]);
                                foreach ($_domains as $value) {
                                    if ($value = trim($value)) {
                                        $ini['domain_app'][$value] = $this_ini["app"];
                                    }
                                }
                            }
                        } else if ($this_ini["app_type"] == "proxy") {
                            if (isset($this_ini["proxy_pass"])) {
                                if (isset($this_ini["server_name"])) {
                                    $_domains = explode(" ", $this_ini["server_name"]);
                                    foreach ($_domains as $value) {
                                        if ($value = trim($value)) {
                                            $ini['domain_proxy_pass'][$value] = $this_ini["proxy_pass"];
                                        }
                                    }
                                }else{
                                    $ini["app_proxy_pass"][$this_ini["app"]] = $this_ini["proxy_pass"];
                                }
                                
                            } else {
                                echo $this_ini["app"] . " : app_type need set proxy_pass" . PHP_EOL;
                                exit;
                            }
                            
                        } else if ($this_ini["app_type"] == "spring_boot_web") {
                            if (isset($this_ini["jar"])) {
                                $ini["app_spring_boot"][$this_ini["app"]] = file_exists($this_ini["jar"])?$this_ini["jar"]: ($tmp_dir . "/" . $this_ini["jar"]);
                            } else {
                                echo $this_ini["app"] . " : app_type need set jar" . PHP_EOL;
                                exit;
                            }
                            if (isset($this_ini["server_name"])) {
                                $_domains = explode(" ", $this_ini["server_name"]);
                                foreach ($_domains as $value) {
                                    if ($value = trim($value)) {
                                        $ini['domain_proxy_pass'][$value] = "app:".$this_ini["app"];
                                    }
                                }
                            }
                        }


 
                    }
                }
            }
        }
    }
}


$spring_port_begin = 0;
$ini["app_spring_boot_info"] =[];
foreach ($ini["app_spring_boot"] as $key => $value) {
    $port = 7888+$spring_port_begin;
    $ini["app_spring_boot_info"][$key] =["jar"=>$value,"port"=> $port];
    $spring_port_begin++;

    $is_domain = false;
    foreach ($ini['domain_proxy_pass'] as $d_key => $d_value) {
         if($d_value == "app:".$key){
            $is_domain = true;
            $ini['domain_proxy_pass'][$d_key] = "http://127.0.0.1:". $port."/";
         }
    }
    //if(!$is_domain)
    $ini['app_proxy_pass'][$key]="http://127.0.0.1:". $port."/";
  
   
}


parse_dir("this_ssl_certs_dir", "certs");

$log_dir = isset($ini["server"]["log_dir"]) ? $ini["server"]["log_dir"] : ($base_root . "/logs");
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0777, true);
}
$ini["server"]["log_dir"] = $log_dir;
//"this_port", $ini['server']['this_port'],

$find = ["domain_app", "base_root", "this_ip", "nginx_root", "domain_this", "domain_other", "domain_this_1", "domain_other_1", "www_dir", "nginx_cmd", "php_cmd"];
$replace = [implode(" ", array_keys($ini['domain_app'])), $base_root, $ini['server']['this_ip'], $ini['server']['nginx_root'], $ini['server']['domain_this'], $ini['server']['domain_other'], str_replace(".", "\\.", $ini['server']['domain_this']), str_replace(".", "\\.", $ini['server']['domain_other']), isset($ini['server']['www_dir']) ? $ini['server']['www_dir'] : $base_root . DIRECTORY_SEPARATOR . "web", $ini['nginx']['cmd'], $ini['server']['php_cmd']];

$root_dir = "";
foreach ($ini['root_dir'] as $key => $value) {
    $root_dir .= parse_tpl($this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . "nginx_root_dir.tpl", ["app", "dir"], [$key, $value]);
}

$find = ["domain_app", "base_root", "this_ip", "nginx_root", "domain_this", "domain_other", "domain_this_1", "domain_other_1", "www_dir", "nginx_cmd", "php_cmd"];
$replace = [implode(" ", array_keys($ini['domain_app'])), $base_root, $ini['server']['this_ip'], $ini['server']['nginx_root'], $ini['server']['domain_this'], $ini['server']['domain_other'], str_replace(".", "\\.", $ini['server']['domain_this']), str_replace(".", "\\.", $ini['server']['domain_other']), isset($ini['server']['www_dir']) ? $ini['server']['www_dir'] : $base_root . DIRECTORY_SEPARATOR . "web", $ini['nginx']['cmd'], $ini['server']['php_cmd']];

$root_dir = "";
foreach ($ini['root_dir'] as $key => $value) {
    $root_dir .= parse_tpl($this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . "nginx_root_dir.tpl", ["app", "dir"], [$key, $value]);
}

$find[] = "root_dir";
$replace[] = $root_dir;

$app_dir = "";
foreach ($ini['app_dir'] as $key => $value) {
    $app_dir .= parse_tpl($this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . "nginx_app_dir.tpl", ["app", "dir"], [$key, $value]);
}

$find[] = "app_dir";
$replace[] = $app_dir;

$domian_when_ip = $ini['server']['default_app'];
$find[] = "domain_when_ip";
if ($domian_when_ip) {

    $replace[] = parse_tpl($this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . "nginx_domain_when_ip.tpl", ["this_ip", "default_app"], [$ini['server']['this_ip'], $ini['server']['default_app']]);
} else {
    $replace[] = "";
}

$domain_app_list = "";
foreach ($ini['domain_app'] as $key => $value) {
    $appinfo = explode("/", $value);
    for ($i = 0; $i < 4; $i++) {
        if (!$appinfo[$i]) {
            $appinfo[$i] = -1;
        }
    }
    $domain_app_list .= parse_tpl($this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . "nginx_domain_app.tpl", ["domain", "app", "app_sub", "app_sub2", "app_sub3"], [$key, $appinfo[0], $appinfo[1], $appinfo[2], $appinfo[3]]);
}

$find[] = "domain_app_list";
$replace[] = $domain_app_list;

$find[] = "php_port_0";
if (isset($ini['php']['port'][0])) {
    $replace[] = "127.0.0.1:" . $ini['php']['port'][0];
} else if (isset($ini['php']['socket'][0])) {
    $replace[] = $ini['php']['socket'][0];
} else {
    echo "default php not exist";
    exit;
}

$php_port = "";
foreach ($ini['app_php_select'] as $key => $value) {
    if (isset($ini['php']['port'][$value])) {
        $php_port .= parse_tpl($this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . "nginx_php_port.tpl", ["app", "port"], [$key, $ini['php']['port'][$value]]);
    } else if (isset($ini['php']['socket'][$value])) {
        $php_port .= parse_tpl($this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . "nginx_php_socket.tpl", ["app", "socket"], [$key, $ini['php']['socket'][$value]]);
    }
}

$find[] = "php_port";
$replace[] = $php_port;

//proxy　

$proxy_pass = "";
foreach ($ini['app_proxy_pass'] as $key => $value) {
    
    $proxy_pass .= parse_tpl($this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . "nginx_app_proxy_pass.tpl", ["app", "proxy_pass"], [$key, $value]);
   
}

$find[] = "app_proxy_pass";
$replace[] = $proxy_pass;




$nignx_config_common = parse_tpl($this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . 'ws.conf.common.tpl', $find, $replace);

$nignx_config_tpl = $this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . 'ws.conf.tpl';

function http_or_https($is_https)
{
    global $ini, $nignx_config_tpl, $this_dir, $nignx_config_common;
    $find[] = "nignx_config_common";
    $replace[] = $nignx_config_common;
    if (!$is_https) {
        $find[] = "http_or_https";
        $replace[] = " listen " . $ini["server"]["this_port"] . " default_server;";
        return parse_tpl($nignx_config_tpl, $find, $replace);
    } else {
        $certs_dir = $ini["server"]["this_ssl_certs_dir"];
        if (isset($ini["server"]["this_ssl_port"]) && file_exists($certs_dir)) {
            $find[] = "http_or_https";
            $replace[] = parse_tpl($this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . "ws.conf.https.tpl", ["this_port", "this_ssl_certs_dir"], [$ini["server"]["this_ssl_port"], $ini["server"]["this_ssl_certs_dir"]]);
            return parse_tpl($nignx_config_tpl, $find, $replace);
        } else {
            return "";
        }
    }
}

//https 处理

$nignx_config = $base_root . DIRECTORY_SEPARATOR . "configs" . DIRECTORY_SEPARATOR . "nginx" . DIRECTORY_SEPARATOR . 'ws.conf';

file_put_contents($nignx_config, file_get_contents($this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . "ws.conf.all.tpl") . "\n" . http_or_https(false) . "\n" . http_or_https(true));


// domain——proxy app支持
$nignx_config_domain_pass = $base_root . DIRECTORY_SEPARATOR . "configs" . DIRECTORY_SEPARATOR . "nginx" . DIRECTORY_SEPARATOR . 'proxypass.conf';
$nignx_config_domain_pass_content = "";
foreach($ini['domain_proxy_pass'] as $domain=>$topass){
    $this_find =["domain_app","this_port","proxy_pass"];
    $this_replace=[$domain,$ini["server"]["this_port"],$topass];
    $certs_dir = $ini["server"]["this_ssl_certs_dir"];

    if (isset($ini["server"]["this_ssl_port"]) && file_exists($certs_dir)) {
        $this_find[] = "https";
        $this_replace[] = parse_tpl($this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . "ws.conf.https.tpl", ["this_port", "this_ssl_certs_dir"], [$ini["server"]["this_ssl_port"], $ini["server"]["this_ssl_certs_dir"]]);
       
    }
    $nignx_config_domain_pass_content .= parse_tpl($this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . "nginx_app_domain_proxy_pass.tpl", $this_find, $this_replace);
}
file_put_contents($nignx_config_domain_pass,$nignx_config_domain_pass_content);




$nignx_config_root = $ini['nginx']['nginx_config_file']; #$ini['server']['nginx_root'] . DIRECTORY_SEPARATOR . "conf" . DIRECTORY_SEPARATOR . "nginx.conf";
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


//启动命令php

$php_bat = "";
foreach ($ini['php']['php_cgi'] as $key => $value) {
    $php_bat .= parse_tpl($this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . "start.php.tpl", ["base_root", "i", "port", "root", "cmd"], [$base_root, $key, $ini['php']['port'][$key], dirname($value), $value]);
}

$find[] = "php_bat";
$replace[] = $php_bat;


//增加java程序的启动
$java_bat = "";
foreach ($ini['app_spring_boot_info'] as $key => $value) {
    $java_bat .= parse_tpl($this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . "start.java.tpl", ["java", "key", "port", "jar","log"], [$is_win?$ini["server"]["java_cmd"]:("nohup ".$ini["server"]["java_cmd"]), $key, $value["port"], $value["jar"],">".$log_dir.DIRECTORY_SEPARATOR.$key.".java.log"]);
}

$find[] = "java_bat";
$replace[] = $java_bat;


$start_tpl = $this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . 'start.cmd.php.tpl';
$start_bat = $base_root . DIRECTORY_SEPARATOR . "default" . DIRECTORY_SEPARATOR . "start.php";
parse_tpl($start_tpl, $find, $replace, $start_bat);

$init_file = "";

if(file_exists(__DIR__.DIRECTORY_SEPARATOR."../bin".DIRECTORY_SEPARATOR."init.sh")){
    $init_file = "source ".$base_root.DIRECTORY_SEPARATOR."bin".DIRECTORY_SEPARATOR."init.sh";
   // echo $base_root.DIRECTORY_SEPARATOR."bin".DIRECTORY_SEPARATOR."init.sh";
}
$find[] = "init_file";
$replace[] = $init_file;

//启动脚本
$start_tpl = $this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . 'start.' . $shall_ext . '.tpl';
$start_bat = $base_root . DIRECTORY_SEPARATOR . "start." . $shall_ext;
parse_tpl($start_tpl, ['php_cmd','init_file'], [$ini['server']['php_cmd'],$init_file], $start_bat);

$stop_tpl = $this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . 'stop.' . $shall_ext . '.tpl';
$staop_bat = $base_root . DIRECTORY_SEPARATOR . "stop." . $shall_ext;
parse_tpl($stop_tpl, $find, $replace, $staop_bat);

$epii_server_tpl = $this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . 'epii-server.' . $shall_ext . '.tpl';
$epii_server_bat = $base_root . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "epii-server." . $shall_ext;
parse_tpl($epii_server_tpl, $find, $replace, $epii_server_bat);
//$re_install_tpl = $this_dir . DIRECTORY_SEPARATOR . "tpls" . DIRECTORY_SEPARATOR . 're_install.'.$shall_ext.'.tpl';
//$re_install_bat = $base_root . DIRECTORY_SEPARATOR . "re_install.".$shall_ext;
//parse_tpl($re_install_tpl, $find, $replace, $re_install_bat);

if (!$is_win) {
    // chmod($re_install_bat,777);

    chmod($staop_bat, 0777);
    chmod($start_bat, 0777);
    chmod($nignx_config, 0777);
    chmod($epii_server_bat, 0777);
    // if(!file_exists("/usr/local/bin/epii-server"))
    system("ln -snf " . $epii_server_bat . " /usr/local/bin/epii-server");
}

$lock_file = __DIR__ . DIRECTORY_SEPARATOR . ".time";
file_put_contents($lock_file, filectime($lock_file));

foreach (["web", "logs"] as $d) {
    $web_dir = $base_root . DIRECTORY_SEPARATOR . $d;
    if (!is_dir($web_dir)) {
        mkdir($web_dir, 0777, true);
    }
}

file_put_contents(__DIR__."/../bin/runtime.json",json_encode($ini));


function parse_tpl($tpl_file, $find, $replace, $to_file = null)
{
    if (is_string($find)) {
        $find = ["{{" . $find . "}}"];
        $replace = [$replace];
    } else {
        foreach ($find as $key => $value) {
            $find[$key] = "{{" . $value . "}}";
        }
    }

    $txt = str_replace($find, $replace, file_get_contents($tpl_file));
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
    return defined("PHP_BINARY") ? PHP_BINARY : "php";
}
