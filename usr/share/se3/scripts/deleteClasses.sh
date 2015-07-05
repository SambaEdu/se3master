#!/bin/bash

##$Id$##

##### Supprime les répertoires Classes#####

if [ "$1" != "" ]
then
        REPERTOIRE="/var/se3/Classes/Classe_"$1
        # echo $REPERTOIRE

        if [ -d "$REPERTOIRE" ]
        then
                # echo "Ok rep exist"
                rm -Rf $REPERTOIRE
        else
                # echo "$REPERTOIRE existe pas"
                exit 1
        fi
fi

