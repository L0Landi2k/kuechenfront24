#!/bin/sh
# Baut die beiden ZIP-Dateien für WordPress (Plugin und Theme) in dist/
set -e
cd "$(dirname "$0")"
rm -f dist/kf24-konfigurator.zip dist/kuechenfront24.zip
(cd plugin && zip -qr -X ../dist/kf24-konfigurator.zip kf24-konfigurator -x '*.DS_Store')
(cd theme && zip -qr -X ../dist/kuechenfront24.zip kuechenfront24 -x '*.DS_Store')
ls -la dist
