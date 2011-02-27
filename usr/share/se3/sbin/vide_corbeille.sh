#!/bin/bash
# Auteurs: denis bonnenfant
#
## $Id$ ##
#
##### script permettant de vider les corbeilles si + de 3 jours ou dépassement quota #####
# usage : clean  pour effacer les  corbeilles si non configurees
#

FICHIERLOCK=/tmp/vide_corbeille.lock
if [ -f $FICHIERLOCK ]; then
    exit 1
fi
touch $FICHIERLOCK

. /usr/share/se3/includes/config.inc.sh -m -l

if [ "$corbeille" == "1" ]; then
    # contournement bug ldap ?
    overfill=$(getent group | grep "overfill")
    for homedir in $(ls /home); do
        if ldapsearch -xLLL -b ${peopleRdn},${ldap_base_dn} "uid=$homedir" uid | grep -q $homedir ; then
            if [ -d /home/$homedir ]; then
                if [ -d /home/$homedir/Corbeille_Reseau ]; then
                    if    echo "$overfill" | grep -q $homedir
                    then
                        # utilisateur en overfill, on efface tout    
                        rm -fr /home/$homedir/Corbeille_Reseau/*
                    else 
                        # effacement des fichiers de + de 3 jours (à cause de l'arrondi : voir le man find)
                        find /home/$homedir/Corbeille_Reseau -mtime +2 -delete
                    fi
                else
                    mkdir -p /home/$homedir/Corbeille_Reseau
                    chown $homedir /home/$homedir/Corbeille_Reseau
                fi
            fi
        fi
    done 
elif [ "$1" == "clean" ]; then
    echo "on fait le menage"
    for homedir in $(ls /home); do
        if [ -d /home/$homedir ]; then
            rm -fr "/home/$homedir/Corbeille_Reseau"
        fi
    done
fi
# on actualise overfill si besoin
/usr/share/se3/scripts/warn_quota.sh>/dev/null

rm -f $FICHIERLOCK

