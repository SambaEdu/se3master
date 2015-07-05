#/bin/bash


## $Id$ ##


/usr/sbin/lpadmin -h 127.0.0.1 $1 $2 $3 $4 $5 $6 $7 $8 $9 ${10} ${11} -o printer-error-policy=abort-job
