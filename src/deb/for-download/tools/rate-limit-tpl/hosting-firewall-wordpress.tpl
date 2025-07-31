server {
    listen      %ip%:%proxy_port%;
    server_name %domain_idn% %alias_idn%;
    error_log  /var/log/%web_system%/domains/%domain%.error.log error;

    location / {
        error_page 418 = @wordfence_lh;
        error_page 419 = @wordfence_route;
        error_page 420 = @wordfence_sync;

        if ($request_uri ~ "^/\?wordfence_lh") { return 418; }
        if ($request_uri ~ "^/\?rest_route=%2Fwordfence") { return 419; }
        if ($request_uri ~ "^/\?wordfence_syncAttackData") { return 420; }

        limit_conn addr 5;
        limit_conn zone_site 15;
        limit_req zone=one burst=14 delay=7;
        proxy_pass      http://%ip%:%web_port%;
    }

    location /wp-admin/ {
        limit_conn addr 24;
        limit_conn zone_site 30;
        limit_req zone=one burst=40 delay=7;
        proxy_pass      http://%ip%:%web_port%;
    }

    location /wp-json/ {
        limit_conn addr 8;
        limit_conn zone_site 15;
        limit_req zone=one burst=40 delay=7;
        proxy_pass      http://%ip%:%web_port%;
    }

    location @wordfence_lh {
        limit_conn addr 8;
        limit_conn zone_site 15;
        limit_req zone=wfone burst=120;
        proxy_pass      http://%ip%:%web_port%;
    }

    location @wordfence_route {
        limit_conn addr 8;
        limit_conn zone_site 15;
        limit_req zone=wfone burst=120;
        proxy_pass      http://%ip%:%web_port%;
    }

    location @wordfence_sync {
        limit_conn addr 8;
        limit_conn zone_site 15;
        limit_req zone=wfone burst=120;
        proxy_pass      http://%ip%:%web_port%;
    }

    location /wp-json/wordfence/ {
        limit_conn addr 8;
        limit_conn zone_site 15;
        limit_req zone=wfone burst=120;
        proxy_pass      http://%ip%:%web_port%;
    }

    location ~* ^.+\.(%proxy_extentions%)$ {
        root           %docroot%;
        access_log     /var/log/%web_system%/domains/%domain%.log combined;
        access_log     /var/log/%web_system%/domains/%domain%.bytes bytes;
        expires        max;
        # try_files      $uri @fallback;
    }

    location /error/ {
        alias   %home%/%user%/web/%domain%/document_errors/;
    }

    location @fallback {
        proxy_pass      http://%ip%:%web_port%;
    }

    location ~ /wp-config.php    {return 404;}
    location ~ /xmlrpc.php       {return 404;}
    location ~ /\.ht    {return 404;}
    location ~ /\.env   {return 404;}
    location ~ /\.svn/  {return 404;}
    location ~ /\.git/  {return 404;}
    location ~ /\.hg/   {return 404;}
    location ~ /\.bzr/  {return 404;}

    disable_symlinks if_not_owner from=%docroot%;

    include %home%/%user%/conf/web/nginx.%domain%.conf*;
}

