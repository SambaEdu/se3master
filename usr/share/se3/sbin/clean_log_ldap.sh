#!/bin/bash

#
##### Vide /var/lib/ldap lance via se3crontab tous les jours  relance slapd #####
#
### $Id$ ###
#
 
if [ "$1" = "--help" -o "$1" = "-h" ]
then
echo "permet de vider les logs ldap,"
echo "teste et corrige le group mapping sur Profs et Eleves"
echo "Ce script est lance tous les jours par cron à 01h45"
echo ""
echo "Usage : aucune option"
fi

## On relance ldap pour créer un checkpoint
# /etc/init.d/slapd stop
# sleep 3
# /etc/init.d/slapd start
# sleep 2
# /usr/bin/db4.8_archive -d -h /var/lib/ldap
#  
# remise en place du GM au cas ou
net groupmap list | grep "\bProfs\b" || net groupmap add unixgroup=Profs ntgroup=Profs
net groupmap list | grep "\bEleves\b" || net groupmap add unixgroup=Eleves ntgroup=Eleves
 
exit 0

