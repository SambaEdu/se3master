#!/bin/bash


## $Id$ ##


FOUND=0
BUG=0
while read line
do
    if [ "$FOUND" == "1" ]; then
        echo $line|grep AuthInfoRequired >/dev/null
        if [ "$?" == "0" ];then
            BUG=1
        fi
        if [ "$line" == "</Printer>" ]; then
            break
        fi
    fi
    if [ "$line" == "<Printer $1>" ]; then
       FOUND=1 
    fi
done < /etc/cups/printers.conf
exit $BUG
