{{init_file}}
echo stop nginx
{{nginx_cmd}} -s stop
 if [ "$(uname)" == "Darwin" ]; then
    ps -ef | grep "from-epii-server" | grep -v grep | awk '{print $2}' | xargs kill   > /dev/null 2>&1
    ps -ef | grep "php-fpm" | grep -v grep | awk '{print $2}' | xargs kill   > /dev/null 2>&1
 else
    ps -ef | grep "from-epii-server" | grep -v grep | awk '{print $2}' | xargs kill -9  > /dev/null 2>&1
    ps -ef | grep "php-fpm" | grep -v grep | awk '{print $2}' | xargs kill -9  > /dev/null 2>&1
fi