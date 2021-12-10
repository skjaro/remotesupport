# RemoteSupport
GLPI Plugin for direct VNC connection from browser inside computer from server

This Plugin add a simple button inside computer: 

![immagine](https://user-images.githubusercontent.com/35736369/142444042-0cd5627b-5a5d-4586-8022-083e51d6f06c.png)

If user is correctly connected to one or more computer it will launch a VNC connection using the computer name


- Prerequisites
1) VNC Must be installed in the destination PC
2) Fusion inventory must be installed on server with GLPI
3) Agent must be installed on remote PC
4) Easy noVNC must be installed on server with GLPI


clone it and build binnary with make, edit Makefile in remotesupport plugin and build with make

Create systemd unit file:
```
[Unit]
Description=Easy NoVNC

[Service]
ExecStart=/usr/local/bin/easy-novnc -a :8888 -H -P

[Install]
WantedBy=multi-user.target
```

It will listen on 8888 port on local host.


Secure connection to server if you want, some ports are hardcoded in current status of plugin
```
<IfModule mod_ssl.c>
<VirtualHost *:443>
  RewriteEngine On
  ProxyPreserveHost On
  ProxyRequests Off

  # allow for upgrading to websockets
  RewriteEngine On
  RewriteCond %{HTTP:Upgrade} =websocket [NC]
  RewriteRule /(.*)           ws://localhost:8888/$1 [P,L]
  RewriteCond %{HTTP:Upgrade} !=websocket [NC]
  RewriteRule /glpi/(.*)           http://localhost/glpi/$1 [P,L]
  RewriteCond %{HTTP:Upgrade} !=websocket [NC]
  RewriteRule /(.*)           http://localhost:8888/$1 [P,L]

  ProxyPass "/" "http://localhost:8888/"
  ProxyPassReverse "/" "http://localhost:8888/"

  ProxyPass "/glpi/" "http://localhost/glpi/"
  ProxyPassReverse "/glpi/" "http://localhost/glpi/"

  ProxyPass "/vnc" "ws://localhost:8888/vnc"
  ProxyPassReverse "/vnc" "ws://localhost:8888/vnc


  ServerName server.name.lan

SSLCertificateFile /etc/ssl/certs/ssl-cert-snakeoil.pem
SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key

</VirtualHost>
</IfModule>
```
