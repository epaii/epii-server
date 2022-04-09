
echo "start {{jar}}:{{port}}\n";
runcmd_log('{{java}} -jar {{jar}} --server.port={{port}} --spring.profiles.active=pro --from-epii-server --app-of-{{key}} {{log}} ');


