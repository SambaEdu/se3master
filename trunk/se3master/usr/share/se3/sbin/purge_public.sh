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



PURGE=`echo "SELECT value FROM params WHERE name='purge_public'" | mysql -h localhost se3db -N`

if [ "$PURGE" == "1" ]
then
        rm -Rf /var/se3/Docs/public/* > /dev/null
fi
