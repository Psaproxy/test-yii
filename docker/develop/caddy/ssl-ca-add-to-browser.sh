#!/bin/bash
certfile="../../tmp/caddy/ssl-ca.crt"
certname="Caddy Root Test Yii Sagri"
for certDB in $(find ~/ -name "cert8.db")
do
certdir=$(dirname ${certDB});
certutil -A -n "${certname}" -t "TCu,Cu,Tu" -i ${certfile} -d dbm:${certdir}
done
for certDB in $(find ~/ -name "cert9.db")
do
certdir=$(dirname ${certDB});
certutil -A -n "${certname}" -t "TCu,Cu,Tu" -i ${certfile} -d sql:${certdir}
done
