server {
    listen      %ip%:%proxy_port%;
    server_name %domain_idn% %alias_idn%;
    error_log  /var/log/%web_system%/domains/%domain%.error.log error;

    location / {
        proxy_pass      http://%ip%:%web_port%;

        # BEGIN SMUSH-WEBP
        location ~* "wp-content\/(uploads\/)(.*.(?:png|jpe?g))" {
          root           %sdocroot%;
          add_header Vary Accept;
          expires        max;
          set $image_path $2;
          if (-f "%sdocroot%/wp-content/smush-webp/disable_smush_webp") {
            break;
          }
          if ($http_accept !~* "webp") {
            break;
          }
          # add_header X_WebP_Try /wp-content/smush-webp/$image_path.webp;
          try_files /wp-content/smush-webp/$image_path.webp $uri =404;
        }
        # END SMUSH-WEBP

        location ~* ^.+\.(%proxy_extentions%)$ {
            root           %docroot%;
            access_log     /var/log/%web_system%/domains/%domain%.log combined;
            access_log     /var/log/%web_system%/domains/%domain%.bytes bytes;
            # try_files      $uri @fallback;
        }
    }

    location /error/ {
        alias   %home%/%user%/web/%domain%/document_errors/;
    }

    location @fallback {
        proxy_pass      http://%ip%:%web_port%;
    }

    location ~ /\.ht    {return 404;}
    location ~ /\.svn/  {return 404;}
    location ~ /\.git/  {return 404;}
    location ~ /\.hg/   {return 404;}
    location ~ /\.bzr/  {return 404;}

    disable_symlinks if_not_owner from=%docroot%;

    include %home%/%user%/conf/web/nginx.%domain%.conf*;
}

