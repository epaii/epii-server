server{
      listen {{this_port}};
      {{https}}
      server_name {{domain_app}};
      location / 
        {
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_pass {{proxy_pass}};
        }
}
