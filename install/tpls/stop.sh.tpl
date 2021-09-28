echo stop nginx
{{nginx_cmd}} -s stop
 if [ "$(uname)" == "Darwin" ]; then
    ps -ef | grep "from-epii-server" | grep -v grep | awk '{print $2}' | xargs kill 
    ps -ef | grep "php-fpm" | grep -v grep | awk '{print $2}' | xargs kill 
 else
    ps -ef | grep "from-epii-server" | grep -v grep | awk '{print $2}' | xargs kill -9
    ps -ef | grep "php-fpm" | grep -v grep | awk '{print $2}' | xargs kill -9
fi