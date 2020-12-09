<?php
error_reporting(0);
if (!$_ENV) {
    echo "It is need set variables_order = \"EGPCS\"  in php.ini";
    exit;
}

$base_root = str_replace(DIRECTORY_SEPARATOR."default", "", __DIR__);
$ini = parse_ini_file($base_root . DIRECTORY_SEPARATOR . "config.ini", true);


if( isset($ini["server"]["this_ssl_must"]) && ( $ini["server"]["this_ssl_must"]) && isset($ini["server"]["this_ssl_port"]) ){
    if (!((stripos($ini['server']["this_ssl_certs_dir"], "/") === 0) || (stripos($ini['server']["this_ssl_certs_dir"], ":") === 1))) {
        $ini['server']["this_ssl_certs_dir"] = $base_root . DIRECTORY_SEPARATOR . $ini['server']["this_ssl_certs_dir"];
    }
    $certs_dir = $ini["server"]["this_ssl_certs_dir"];
    $file_arr = scandir($certs_dir);
    if(in_array( explode(":", $_SERVER["HTTP_HOST"])[0].".key",$file_arr) && !$_SERVER['HTTPS']){
        echo "IT is need https";
        exit;
    }
     
    
}

if (isset($ini['php_env']) && isset($ini['php_env'][$_ENV['APP_JT']])) {
    foreach ($ini['php_env'][$_ENV['APP_JT']] as $key => $value) {
        putenv($key . "=" . $value);
        $_ENV[$key] = $_SERVER[$key] = $value;

    }
}




chdir(dirname($_ENV['SCRIPT_FILENAME_origin']));
$_ENV['SCRIPT_FILENAME'] = $_SERVER['SCRIPT_FILENAME'] = $_ENV['SCRIPT_FILENAME_origin'];
unset($_ENV['SCRIPT_FILENAME_origin']);
unset($_SERVER['SCRIPT_FILENAME_origin']);
unset($_ENV['APP_JT']);
unset($_SERVER['APP_JT']);
 
if(file_exists($root_file = "./" . basename($_ENV['SCRIPT_NAME']))){
    require_once $root_file;
}else{
    echo "who you are?";
    exit;
}
?>