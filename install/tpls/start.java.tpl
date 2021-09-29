
echo "start {{jar}}:{{port}}\n";
runcmd_log('{{java}} -jar {{jar}} --server.port={{port}} --from-epii-server {{log}} ');


