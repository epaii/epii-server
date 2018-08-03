
echo "start phpcgi{{i}}\n";
if($is_win)
{
runcmd('{{cmd}} -b 127.0.0.1:{{port}} -c {{root}}/php.ini');
}else{
runcmd('{{cmd}}');
}


