#!/bin/bash
cd /opt/observium/

# 4.1.1
#/usr/bin/lessc -m=always --source-map=html/css/observium.map html/css/bootstrap/less/bootstrap.less html/css/observium.css
#/usr/bin/lessc -m=always --source-map=html/css/observium-dark.map html/css/bootstrap/less/bootstrap-dark.less html/css/observium-dark.css
#/usr/bin/lessc -m=always --source-map=html/css/observium-darkblue.map html/css/bootstrap/less/bootstrap-darkblue.less html/css/observium-darkblue.css

# 2.7.1 / 3.13.0
lessc --source-map=html/css/observium.map html/css/bootstrap/less/bootstrap.less html/css/observium.css
lessc --source-map=html/css/observium-dark.map html/css/bootstrap/less/bootstrap-dark.less html/css/observium-dark.css
lessc --source-map=html/css/observium-darkblue.map html/css/bootstrap/less/bootstrap-darkblue.less html/css/observium-darkblue.css

# old 1.6.1
#lessc --source-map=html/css/observium.map --source-map-url=observium.map --source-map-basepath=html/css/bootstrap/less html/css/bootstrap/less/bootstrap.less > html/css/observium.css
#lessc --source-map=html/css/observium-dark.map --source-map-url=observium-dark.map --source-map-basepath=html/css/bootstrap/less html/css/bootstrap/less/bootstrap-dark.less > html/css/observium-dark.css
#lessc --source-map=html/css/observium-darkblue.map --source-map-url=observium-darkblue.map --source-map-basepath=html/css/bootstrap/less html/css/bootstrap/less/bootstrap-darkblue.less > html/css/observium-darkblue.css
