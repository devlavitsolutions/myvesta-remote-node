docRoot %home%/%user%/web/%domain%/public_html
vhDomain %domain%
enableGzip 1
enableScript 1
restrained 0

<errorlog %domain%>
    logLevel ERROR
</errorlog>

<accesslog %domain%>
    logFormat "%h %l %u %t \"%r\" %>s %b"
</accesslog>

index {
    useServer 0
    indexFiles index.php, index.html
}

extProcessor lsphp {
    type lsapi
    address uds://tmp/lshttpd/lsphp.sock
    maxConns 10
    initTimeout 60
    retryTimeout 0
    persistConn 1
    respBuffer 0
    autoStart 1
    path /usr/local/lsws/lsphp83/bin/lsphp
    backlog 100
    env PHP_LSAPI_CHILDREN=10
    env LSAPI_AVOID_FORK=200M
    extUser %user%
    extGroup %user%
    memSoftLimit 2047M
    memHardLimit 2047M
    procSoftLimit 400
    procHardLimit 500
}

scriptHandler {
    add lsapi:lsphp php
}

rewrite {
    enable 1
    autoLoadHtaccess 1
}

context /phpmyadmin/ {
    location %home%/%user%/web/%domain%/public_html/phpmyadmin/
    allowBrowse 1
    indexFiles index.php
    rewrite 0
    addDefaultCharset off
    type NULL
    extOverride 1
    scriptHandler { add lsapi:lsphp php }
}

context /webmail {
    location /usr/share/roundcube/
    allowBrowse 1
    indexFiles index.php
    rewrite 0
    addDefaultCharset off
    type NULL
    extOverride 1
    scriptHandler { add lsapi:lsphp php }
}

phpIniOverride  {
  php_admin_value open_basedir=%home%/%user%/web/%domain%/public_html:%home%/%user%/tmp:/tmp
}
