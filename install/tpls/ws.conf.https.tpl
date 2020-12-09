        listen {{this_port}} ssl;
        ssl_certificate      "{{this_ssl_certs_dir}}/$ssl_server_name.pem";
        ssl_certificate_key  "{{this_ssl_certs_dir}}/$ssl_server_name.key";       
        ssl_session_cache shared:SSL:10m;
        ssl_session_timeout 10m;
        ssl_prefer_server_ciphers  on;
        ssl_protocols TLSv1 TLSv1.1 TLSv1.2;
        ssl_ciphers ECDHE-RSA-AES256-SHA384:AES256-SHA256:RC4:HIGH:!MD5:!aNULL:!eNULL:!NULL:!DH:!EDH:!AESGCM;
