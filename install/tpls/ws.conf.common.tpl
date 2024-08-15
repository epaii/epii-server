    server_name *.{{domain_this}} {{this_ip}} {{domain_app}};

# ---app_proxy_pass---
{{app_proxy_pass}}
#---app_proxy_pass---

    set $app default;
 
    set $app_sub -1;
    set $app_sub2 -1;
    set $app_sub3 -1;
    set $base_root  {{base_root}};


    set $root {{www_dir}};

    if ( $host ~*  ^([^\.]+)\.{{domain_this_1}} ) {

        set $app $1;
    }
    if ( $host ~*  ^([^\.]+)\.([^\.]+)\.{{domain_this_1}} ) {

        set $app $2;
        set $app_sub $1;
    }
    if ( $host ~*  ^([^\.]+)\.([^\.]+)\.([^\.]+)\.{{domain_this_1}} ) {

        set $app $3;
        set $app_sub $2;
        set $app_sub2 $1;
    }
    if ( $host ~*  ^([^\.]+)\.([^\.]+)\.([^\.]+)\.([^\.]+)\.{{domain_this_1}} ) {

        set $app $4;
        set $app_sub $3;
        set $app_sub2 $2;
        set $app_sub3 $1;
    }

# ---domain_app---
{{domain_app_list}}
#---domain_app---




set $app_default 0;
set $app_get 0;
if ( $app ~*  ^default$ ) {
   set $app_default 1;
}

   if ( $uri	~*  ^/app/([^\/]+)/$ ) {
      set $app_get $1;
       set $app_default 1$app_default;
   }
  if ( $app_default = 11 ){
      set $app $app_get;
       rewrite ^/app/([^\/]+)/$ /index.php;
   }
  set $app_default 0;
if ( $app ~*  ^default$ ) {
   set $app_default 1;
}
set $app_default_index 0;
if ( $uri	~*  ^/app/([^\/]+)/(.*)$ ) {
set $app_get $1;
 set $app_default 1$app_default;
 set $app_default_index $2;
 
}
  if ( $app_default = 11 ){
       set $app $app_get;
rewrite ^/app/([^\/]+)/(.*)$ /$app_default_index;
  }

# ---domain_when_ip---
{{domain_when_ip}}
#---domain_when_ipend---

    if ( $app !~* ^default$ ) {
        set $root $root/$app;
    }

# ---app_dir---
{{app_dir}}
#---app_dirend---


    if ( $app_sub !~* ^-1$ ) {
        set $root $root/$app_sub;
    }
    if ( $app_sub2 !~* ^-1$ ) {
        set $root $root/$app_sub2;
    }
    if ( $app_sub3 !~* ^-1$ ) {
        set $root $root/$app_sub3;
    }

    # ---dir--root---
{{root_dir}}
#---dir--rootedn---

    set $phpport {{php_port_0}};

    # ---php--port---
{{php_port}}
#---php--portedn---

    if ( !-d $root ) {
        # set $root  $base_root/default;
    }


    location / {
        if (!-e $request_filename) {
            rewrite ^(.*)$ /index.php?s=$1 last;
            break;
        }
    }

    # error_page  404              /404.html;

    # redirect server error pages to the static page /50x.html
    error_page 500 502 503 504  /50x.html;
    location = /50x.html {
        set $root  $base_root/default;
    }

    index  index.html index.htm index.php;
    access_log {{base_root}}/logs/nginx.access.log;
    error_log {{base_root}}/logs/nginx.error.log;
    root $root;
    location ~ \.php(.*)$ {
        fastcgi_pass $phpport;
        fastcgi_index index.php;

        # fastcgi_param SCRIPT_FILENAME  $root/$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        include fastcgi_params;
        set $real_script_name $fastcgi_script_name;
        if ($fastcgi_script_name ~ ^(.+?\.php)(/.+)$) {
            set $real_script_name $1;
            set $path_info $2;
        }
        fastcgi_param QUERY_STRING  $query_string;
       # fastcgi_param SCRIPT_FILENAME $document_root$real_script_name;
        fastcgi_param SCRIPT_FILENAME $base_root/default/route.php;
        fastcgi_param SCRIPT_FILENAME_origin $document_root$real_script_name;
        fastcgi_param APP_JT $app;
        fastcgi_param SCRIPT_NAME $real_script_name;
        fastcgi_param PATH_INFO $path_info;

    }
    location ~ .*\.(gif|jpg|jpeg|png|bmp|swf|flv|ico)$ {
        expires 30d;
        access_log off;
    }
    location ~ .*\.(js|css)?$ {
        expires 7d;
        access_log off;
        add_header Access-Control-Allow-Origin *;
    }
	location ~* \.(eot|ttf|ttc|otf|eot|woff|woff2|svg)$ {

       add_header Access-Control-Allow-Origin *;
	}

    location ~ .*.(svn|git|cvs) {
        deny all;
    }
 