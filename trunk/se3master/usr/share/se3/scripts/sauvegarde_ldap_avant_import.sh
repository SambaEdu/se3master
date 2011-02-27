#!/bin/bash

# $Id$
# Script destin� � effectuer une sauvegarde de l'annuaire LDAP avant de proc�der � un nouvel import
# Auteur: Stephane Boireau (27)
# Derni�re modification: 08/03/2007

dossier_svg="/var/se3/save/sauvegarde_ldap_avant_import"
#dossier_svg="/var/remote_adm/sauvegarde_ldap_avant_import"

if [ "$1" = "--help" -o "$1" = "-h" ]; then
        echo "Script destin� � effectuer une sauvegarde de l'annuaire LDAP vers"
		echo "   $dossier_svg"
		echo "avant de proc�der � un nouvel import."
        echo ""
        echo "Usage : pas d'option"
        exit
fi

mkdir -p $dossier_svg
date=$(date +%Y%m%d-%H%M%S)

BASEDN=$(cat /etc/ldap/ldap.conf | grep "^BASE" | tr "\t" " " | sed -e "s/ \{2,\}/ /g" | cut -d" " -f2)
ROOTDN=$(cat /etc/ldap/slapd.conf | grep "^rootdn" | tr "\t" " " | cut -d'"' -f2)
PASSDN=$(cat /etc/ldap.secret)

#source /etc/ssmtp/ssmtp.conf

echo "Erreur lors de la sauvegarde de pr�caution effectu�e avant import.
Le $date" > /tmp/erreur_svg_prealable_ldap_${date}.txt
# Le fichier d erreur est g�n�r� quoi qu il arrive, mais il n est exp�di� qu en cas de probl�me de sauvegarde
/usr/bin/ldapsearch -xLLL -D $ROOTDN -w $PASSDN > $dossier_svg/ldap_${date}.ldif || mail root -s "Erreur sauvegarde LDAP" < /tmp/erreur_svg_prealable_ldap_${date}.txt
rm -f /tmp/erreur_svg_prealable_ldap_${date}.txt
