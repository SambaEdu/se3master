#!/bin/bash


# $Id$



# Script destine a deplacer dans un dossier /home_Trash_$date les homes de comptes orphelins
# plutot que de les supprimer directement
# Auteur: Stephane Boireau
# Derniere modification: 12/10/2008

### recup pass root mysql
. /root/.my.cnf 2>/dev/null

BASEDN=$(cat /etc/ldap/ldap.conf | grep "^BASE" | tr "\t" " " | sed -e "s/ \{2,\}/ /g" | cut -d" " -f2)
ROOTDN=$(cat /etc/ldap/slapd.conf | grep "^rootdn" | tr "\t" " " | cut -d'"' -f2)
PASSDN=$(cat /etc/ldap.secret)

dest=/home/_Trash_$(date +%Y%m%d_%H%M%S)
fich=/var/www/se3/Admin/mv_Trash_$(date +%Y%m%d%H%M%S)

cpt=0
echo "Parcours de la Corbeille...<br />"
ldapsearch -xLLL -b ou=Trash,$BASEDN uid | grep "^uid: " | sed -e "s/^uid: //" | while read uid
do
    #echo "Controle de $uid" | tee -a $fich
    if [ -d "/home/$uid" ]; then
        if [ "$cpt" = "0" ]; then

            mkdir -p ${dest}
            if [ "$?" != "0" ]; then
            echo "ERREUR: La creation du dossier ${dest} a echoue." | tee -a $fich
                exit
            fi

            echo "Deplacement vers ${dest}: " | tee -a $fich
        else
            echo ", "|tee -a $fich
        fi
        echo "$uid"|tee -a $fich
        mv /home/$uid ${dest}/
	chown -R admin:admins ${dest}/$uid
        cpt=$(($cpt+1))
    else
        echo "/home/$uid n'existe pas"| tee -a $fich
    fi

    # A VOIR pour LCS:
    # Faut-il un dump de sauvegarde?...
    #db_name=$(echo "$uid" | sed -e "s/-//g" | sed -e "s/_//g" | sed -e "s/\.//g")
    #echo "DROP DATABASE ${db_name}" | mysql -h $dbhost -u $dbuser -p$dbpass > /dev/null 2>&1
    #echo "delete from personne where login = '$uid'" | mysql -h $dbhost lcs_db -u $dbuser -p$dbpass
done

