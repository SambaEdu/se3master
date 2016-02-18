#!/bin/bash

## $Id$ ##
#

##### Purge le repertoire public toutes les nuits si cela est active dans l'interface #####
#

if [ "$1" = "--help" -o "$1" = "-h" ]
then
        echo "Script permettant de puger le repertoire public toutes les nuits."
        echo "Activation via l'interface."

        echo "Usage : pas d'option"
        exit
fi

. /etc/se3/config_c.cache.sh


if [ "$purge_public" == "1" ]
then
        rm -Rf /var/se3/Docs/public/* > /dev/null
fi
