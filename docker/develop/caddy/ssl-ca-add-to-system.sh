#!/bin/bash
sudo cp ../../tmp/caddy/ssl-ca.crt /usr/local/share/ca-certificates/
sudo update-ca-certificates
sudo openssl verify ../../tmp/caddy/ssl-ca.crt
