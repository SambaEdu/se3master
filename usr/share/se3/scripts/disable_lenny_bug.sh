#!/bin/bash

## $Id$ ##


/etc/init.d/cups stop >/dev/null
sed -i 's/AuthInfoRequired.*//g' /etc/cups/printers.conf
/etc/init.d/cups start >/dev/null
