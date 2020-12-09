
if ( $host ~*  ^{{domain}}$ ) {
         ssl_certificate      "{{app}}.pem";
        ssl_certificate_key  "{{app}}.key";
}