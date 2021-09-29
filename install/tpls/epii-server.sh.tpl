if [ "$(uname)" == "Darwin" ]; then
    curFile=$(readlink -n "$0")
else
    curFile=$(readlink -f "$0")
fi
if [ "$curFile" = "" ];then
    curPath="."
else
    curPath=$(dirname $curFile)
fi

{{init_file}}

function app_stop() {

    if [ $# != 1 ]; then
        echo " it is need 3 args"
        exit
    fi
    if [ "$(uname)" == "Darwin" ]; then
        ps -ef | grep "app-of-"$1 | grep -v grep | awk '{print $2}' | xargs kill
    else
        ps -ef | grep "app-of-"$1 | grep -v grep | awk '{print $2}' | xargs kill -9
    fi

}

if [ "$(type -t $1)" == function ]; then
    $*
else
    if [ "$(type -t $1_$2)" == function ]; then
        $1_$2 ${@:3}
    else
       c_idr=`pwd`
        {{php_cmd}} $curPath/epii-server.php $* $c_idr
    fi

fi


