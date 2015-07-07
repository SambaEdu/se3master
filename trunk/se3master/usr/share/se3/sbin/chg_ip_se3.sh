#!/bin/bash
# Script largement ébauché par Franck Molle...
# ... poursuivi par Stéphane Boireau (03/10/2005)
#
## $Id$ ##
#
##### Permet de changer l'adresse IP du serveur se3 #####
#

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Script intéractif permettant de changer l'adresse IP de ce serveur"
	echo "Usage : pas d'option"
	exit
fi

#Couleurs
COLTITRE="\033[1;35m"   # Rose
COLPARTIE="\033[1;34m"  # Bleu

COLTXT="\033[0;37m"     # Gris
COLCHOIX="\033[1;33m"   # Jaune
COLDEFAUT="\033[0;33m"  # Brun-jaune
COLSAISIE="\033[1;32m"  # Vert

COLCMD="\033[1;37m"     # Blanc

COLERREUR="\033[1;31m"  # Rouge
COLINFO="\033[0;36m"    # Cyan

ERREUR()
{
        echo -e "$COLERREUR"
        echo "ERREUR!"
        echo -e "$1"
        echo -e "$COLTXT"
        exit 1
}

POURSUIVRE()
{
        REPONSE=""
        while [ "$REPONSE" != "o" -a "$REPONSE" != "O" -a "$REPONSE" != "n" ]
        do
                echo -e "$COLTXT"
                echo -e "Peut-on poursuivre? (${COLCHOIX}O/n${COLTXT}) $COLSAISIE\c"
                read REPONSE
                if [ -z "$REPONSE" ]; then
                        REPONSE="o"
                fi
        done

        if [ "$REPONSE" != "o" -a "$REPONSE" != "O" ]; then
                ERREUR "Abandon!"
        fi
}

clear
echo -e "$COLTITRE"
echo "********************************"
echo "* SCRIPT PERMETTANT DE CHANGER *"
echo "*     L'IP DU SERVEUR SE3      *"
echo "********************************"

echo -e "$COLINFO"
echo "Dans ce script, on suppose (pour le moment) que le LDAP est sur le SE3."

echo -e "$COLTXT"
echo "Appuyez sur Entree pour continuer..."
read

echo -e $COLPARTIE
echo "--------"
echo "Partie 1 : Recupération des données"
echo "--------"

### on suppose que l'on est sous debian ;) ####
WWWPATH="/var/www"

echo -e "$COLTXT"
echo "Recherche des informations dans $WWWPATH/se3/includes/config.inc.php"
echo -e "$COLCMD\c"

## recuperation des variables necessaires pour interoger mysql ###

. /etc/se3/config_d.cache.sh 
. /etc/se3/config_o.cache.sh 
. /etc/se3/config_m.cache.sh

#
# Arret des services
#
echo -e "$COLTXT"
echo "Arrêt des services..."
echo -e "$COLCMD\c"
/etc/init.d/samba stop
/etc/init.d/slapd stop
/etc/init.d/apache2 stop
/etc/init.d/apache2se stop

echo -e "$COLTXT"
echo "Récupération des valeurs actuelles de IP, MASQUE et GATEWAY..."
echo -e "$COLCMD\c"

FICHIER_TEMP="/tmp/interfaces"
cat /etc/network/interfaces | grep -v "#" > $FICHIER_TEMP

OLD_IP=$(cat $FICHIER_TEMP | grep address | sed -e "s/address//g" | tr "\t" " " | sed -e "s/ //g")
OLD_NETMASK=$(cat $FICHIER_TEMP | grep netmask | sed -e "s/netmask//g" | tr "\t" " " | sed -e "s/ //g")
OLD_NETWORK=$(cat $FICHIER_TEMP | grep network | sed -e "s/network//g" | tr "\t" " " | sed -e "s/ //g")
OLD_BROADCAST=$(cat $FICHIER_TEMP | grep broadcast | sed -e "s/broadcast//g" | tr "\t" " " | sed -e "s/ //g")
OLD_GATEWAY=$(cat $FICHIER_TEMP | grep gateway | sed -e "s/gateway//g" | tr "\t" " " | sed -e "s/ //g")
OLD_DNS=$(grep "nameserver" /etc/resolv.conf |  awk '{print $2}' | head -n1)

echo -e "$COLINFO"
echo "Configuration IP actuelle:"
echo -e "$COLTXT\c"
echo "IP :         $OLD_IP"
echo "Masque :     $OLD_NETMASK"
echo "Réseau :     $OLD_NETWORK"
echo "Broadcast :  $OLD_BROADCAST"
echo "Passerelle : $OLD_GATEWAY"
echo "DNS :        $OLD_DNS"

echo -e "$COLTXT"
echo -e "Nouvelle IP: $COLSAISIE\c"
read NEW_IP

echo -e "$COLTXT"
echo -e "Nouveau masque: [${COLDEFAUT}$OLD_NETMASK${COLTXT}] $COLSAISIE\c"
read NEW_NETMASK

if [ -z "$NEW_NETMASK" ]; then
	NEW_NETMASK=$OLD_NETMASK
fi

if [ -e /usr/share/se3/sbin/bibliotheque_ip_masque.sh ]; then
	source /usr/share/se3/sbin/bibliotheque_ip_masque.sh

	DEFAULT_NETWORK=$(calcule_reseau $NEW_IP $NEW_NETMASK)
	DEFAULT_BROADCAST=$(calcule_broadcast $NEW_IP $NEW_NETMASK)

	IP1A3=$(echo ${DEFAULT_NETWORK} | cut -d"." -f1-3)
	IP4=$(($(echo ${DEFAULT_NETWORK} | cut -d"." -f4)+1))
	DEFAULT_GATEWAY=${IP1A3}.${IP4}
	DEFAULT_DNS=${DEFAULT_GATEWAY}
else
	DEFAULT_NETWORK=$OLD_NETWORK
	DEFAULT_BROADCAST=$OLD_BROADCAST
	DEFAULT_GATEWAY=$OLD_GATEWAY
	DEFAULT_DNS=$OLD_DNS
fi


echo -e "$COLTXT"
echo -e "Nouvelle adresse réseau: [${COLDEFAUT}${DEFAULT_NETWORK}${COLTXT}] $COLSAISIE\c"
read NEW_NETWORK

if [ -z "$NEW_NETWORK" ]; then
	NEW_NETWORK=${DEFAULT_NETWORK}
fi

echo -e "$COLTXT"
echo -e "Nouvelle adresse de broadcast: [${COLDEFAUT}${DEFAULT_BROADCAST}${COLTXT}] $COLSAISIE\c"
read NEW_BROADCAST

if [ -z "$NEW_BROADCAST" ]; then
	NEW_BROADCAST=${DEFAULT_BROADCAST}
fi

echo -e "$COLTXT"
echo -e "Nouvelle passerelle: [${COLDEFAUT}${DEFAULT_GATEWAY}${COLTXT}] $COLSAISIE\c"
read NEW_GATEWAY

if [ -z "$NEW_GATEWAY" ]; then
	NEW_GATEWAY=${DEFAULT_GATEWAY}
fi

echo -e "$COLTXT"
echo -e "Nouveau DNS : [${COLDEFAUT}${DEFAULT_DNS}${COLTXT}] $COLSAISIE\c"
read NEW_DNS

if [ -z "$NEW_DNS" ]; then
	NEW_DNS=${DEFAULT_DNS}
fi


echo -e "$COLINFO"
echo "Vous vous apprêtez à modifier les paramètres suivants:"
echo -e "               AVANT                   APRES"
echo -e "IP:		$OLD_IP		$NEW_IP"
echo -e "Masque:		$OLD_NETMASK		$NEW_NETMASK"
echo -e "Réseau:		$OLD_NETWORK		$NEW_NETWORK"
echo -e "Broadcast:	$OLD_BROADCAST		$NEW_BROADCAST"
echo -e "Passerelle:	$OLD_GATEWAY		$NEW_GATEWAY"
echo -e "DNS:		$OLD_DNS		$NEW_DNS"

POURSUIVRE

# patch pour se3-unattended si present :
if [ -e /var/se3/unattended/install/site/unattend.txt ]; then
	sed "s/$OLD_IP/$NEW_IP/g" -i /var/se3/unattended/install/site/unattend.txt
fi

#
# Mise a jour de /etc/network/interfaces
#
echo -e "$COLTXT"
echo "Mise à jour de /etc/network/interfaces"
echo -e "$COLCMD\c"
cp -f /etc/network/interfaces /etc/network/interfaces.ori
echo "cat /etc/network/interfaces.ori | sed -e \"s/address $OLD_IP/address $NEW_IP/g\" | sed -e \"s/netmask $OLD_NETMASK/netmask $NEW_NETMASK/g\" | sed -e \"s/network $OLD_NETWORK/network $NEW_NETWORK/g\" | sed -e \"s/broadcast $OLD_BROADCAST/broadcast $NEW_BROADCAST/g\" | sed -e \"s/gateway $OLD_GATEWAY/gateway $NEW_GATEWAY/g\" > /etc/network/interfaces"
cat /etc/network/interfaces.ori | sed -e "s/address $OLD_IP/address $NEW_IP/g" | sed -e "s/netmask $OLD_NETMASK/netmask $NEW_NETMASK/g" | sed -e "s/network $OLD_NETWORK/network $NEW_NETWORK/g" | sed -e "s/broadcast $OLD_BROADCAST/broadcast $NEW_BROADCAST/g" | sed -e "s/gateway $OLD_GATEWAY/gateway $NEW_GATEWAY/g" > /etc/network/interfaces
chmod 644 /etc/network/interfaces



#
# Mise a jour de  /etc/networks
#
echo -e "$COLTXT"
echo "Mise à jour de /etc/networks"
echo -e "$COLCMD\c"
cp -f /etc/networks /etc/networks.ori
echo "cat /etc/networks.ori | sed -e \"s/network $OLD_NETWORK/network $NEW_NETWORK/g\" > /etc/networks"
cat /etc/networks.ori | sed -e "s/network $OLD_NETWORK/network $NEW_NETWORK/g" > /etc/networks
chmod 644 /etc/networks 





# Mise à jour de /etc/resolv.conf
#
echo -e "$COLTXT"
echo "Mise à jour de /etc/resolv.conf"
echo -e "$COLCMD\c"
cp -f /etc/resolv.conf /etc/resolv.conf.ori
sed "s/$OLD_DNS/$NEW_DNS/g" -i /etc/resolv.conf
echo "sed \"s/$OLD_DNS/$NEW_DNS/g\" -i /etc/resolv.conf"
chmod 644 /etc/resolv.conf

#
echo -e "$COLTXT"
echo "Mise à jour de /etc/ldap/ldap.conf"
echo -e "$COLCMD\c"
cp -f /etc/ldap/ldap.conf /etc/ldap/ldap.conf.ori
echo "sed -e \"s/$OLD_IP/$NEW_IP/g\" -i /etc/ldap/ldap.conf"
sed -e "s/$OLD_IP/$NEW_IP/g" -i /etc/ldap/ldap.conf
chmod 644 /etc/ldap/ldap.conf

#
# Mise à jour de /etc/pam_ldap.conf
#
echo -e "$COLTXT"
echo "Mise à jour de /etc/pam_ldap.conf"
echo -e "$COLCMD\c"
cp -f /etc/pam_ldap.conf /etc/pam_ldap.conf.ori
echo "sed -e \"s/$OLD_IP/$NEW_IP/g\" -i /etc/pam_ldap.conf"
sed -e "s/$OLD_IP/$NEW_IP/g" -i /etc/pam_ldap.conf
chmod 644 /etc/pam_ldap.conf


#
# Mise à jour de /etc/ldap/config.se3
#
echo -e "$COLTXT"
echo "Mise à jour de /etc/ldap/config.se3"
echo -e "$COLCMD\c"
cp -f /etc/ldap/config.se3 /etc/ldap/config.se3.ori
echo "sed -e \"s/$OLD_IP/$NEW_IP/g\" -i /etc/ldap/config.se3"
sed -e "s/$OLD_IP/$NEW_IP/g" -i /etc/ldap/config.se3
chmod 644 /etc/ldap/config.se3

#
# Mise à jour de /etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/hostperm.1
#
HOTPERM_FICH="/etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/hostperm.1"
echo -e "$COLTXT"
echo "Mise à jour de $HOTPERM_FICH"
echo -e "$COLCMD\c"
cp -f $HOTPERM_FICH $HOTPERM_FICH.ori
echo "sed -e \"s/$OLD_IP/$NEW_IP/g\" -i $HOTPERM_FICH"
sed -e "s/$OLD_IP/$NEW_IP/g" -i $HOTPERM_FICH
chmod 644 $HOTPERM_FICH

#
# Mise à jour de /etc/libnss-ldap.conf
#
echo -e "$COLTXT"
echo "Mise à jour de /etc/libnss-ldap.conf"
echo -e "$COLCMD\c"
cp -f /etc/libnss-ldap.conf /etc/libnss-ldap.conf.ori
echo "sed -e \"s/$OLD_IP/$NEW_IP/g\" -i /etc/libnss-ldap.conf"
sed -e "s/$OLD_IP/$NEW_IP/g" -i /etc/libnss-ldap.conf
chmod 644 /etc/libnss-ldap.conf

#
# Mise à jour de /etc/samba/smb.conf
#
echo -e "$COLTXT"
echo "Mise à jour de /etc/samba/smb.conf"
echo -e "$COLCMD\c"
cp -f /etc/samba/smb.conf /etc/samba/smb.conf.ori
#cat /etc/samba/smb.conf.ori | sed -e "s/ldap server = $OLD_IP/ldap server = $NEW_IP/g" | sed -e "s!interfaces = $OLD_IP\/$OLD_NETMASK!interfaces = $NEW_IP\/$NEW_NETMASK!"> /etc/samba/smb.conf
echo "cat /etc/samba/smb.conf.ori | sed -e \"s/ldap server = $OLD_IP/ldap server = $NEW_IP/g\" | sed -e \"s!ldap://${OLD_IP}!ldap://${NEW_IP}!g\"| sed -e \"s!interfaces = $OLD_IP/$OLD_NETMASK!interfaces = $NEW_IP/$NEW_NETMASK!\"> /etc/samba/smb.conf"
cat /etc/samba/smb.conf.ori | sed -e "s/ldap server = $OLD_IP/ldap server = $NEW_IP/g" | sed -e "s!ldap://${OLD_IP}!ldap://${NEW_IP}!g"| sed -e "s!interfaces = $OLD_IP/$OLD_NETMASK!interfaces = $NEW_IP/$NEW_NETMASK!"> /etc/samba/smb.conf
chmod 644 /etc/samba/smb.conf


#
# Mise à jour de /etc/hosts
#
echo -e "$COLTXT"
echo "Mise à jour de /etc/hosts"
echo -e "$COLCMD\c"
cp -f /etc/hosts /etc/hosts.ori
echo "cat /etc/hosts.ori | tr \"\t\" \" \" | sed -e \"s/ \{2,\}/ /g\" > /tmp/hosts.tmp"
cat /etc/hosts.ori | tr "\t" " " | sed -e "s/ \{2,\}/ /g" > /tmp/hosts.tmp
echo "cat /tmp/hosts.tmp | sed -e \"s/$OLD_IP /$NEW_IP /g\" > /etc/hosts"
cat /tmp/hosts.tmp | sed -e "s/$OLD_IP /$NEW_IP /g" > /etc/hosts
chmod 644 /etc/hosts


#
# Mise à jour de /var/se3/Progs/install/ocs-config.bat
#
if [ -e "/var/se3/Progs/install/ocs-config.bat" ]; then
	echo -e "$COLTXT"
	echo "Mise à jour de /var/se3/Progs/install/ocs-config.bat"
	echo -e "$COLCMD\c"
	cp -f /var/se3/Progs/install/ocs-config.bat /var/se3/Progs/install/ocs-config.bat.ori
	echo "sed -e \"s/ip_se3=$OLD_IP/ip_se3=$NEW_IP/g\" -i /var/se3/Progs/install/ocs-config.bat"
	sed -e "s/ip_se3=$OLD_IP/ip_se3=$NEW_IP/g" -i /var/se3/Progs/install/ocs-config.bat
	
fi


#
# Mise à jour des variables 'urlse3', 'ldap_server' et 'se3ip'dans MySQL
# Ou faut-il le faire dans l'interface web parce que d'autres actions sont effectuées que la màj MySQL?
#
echo -e "$COLTXT"
echo "Mise à jour des variables 'urlse3' et 'ldap_server' dans MySQL..."
echo -e "$COLCMD\c"
echo "UPDATE params SET value='http://"$NEW_IP":909' WHERE name='urlse3';" > /tmp/maj_chgt_ip_se3.sql
# Et dans le cas de M.Curie Bernay, cela risque même d'être l'IP du SLIS...
echo "UPDATE params SET value='$NEW_IP' WHERE name='ldap_server';" >> /tmp/maj_chgt_ip_se3.sql
echo "UPDATE params SET value='$NEW_IP' WHERE name='se3ip';" >> /tmp/maj_chgt_ip_se3.sql

# Sauf que... est-ce que le LDAP n'est pas déporté?



if [ -n "$dhcp_wins" ]; then
	echo "UPDATE params SET value='$NEW_IP' WHERE name='dhcp_wins';" >> /tmp/maj_chgt_ip_se3.sql
	echo "UPDATE params SET value='$NEW_IP' WHERE name='dhcp_tftp_server';" >> /tmp/maj_chgt_ip_se3.sql
	
fi

echo "UPDATE params SET value='$NEW_NETMASK' WHERE name='se3mask';" >> /tmp/maj_chgt_ip_se3.sql
mysql -u$dbuser -p$dbpass $dbname < /tmp/maj_chgt_ip_se3.sql

#refresh cache params sql 
/usr/share/se3/includes/config.inc.sh -clpbmsdf


#
# Redémarrage de l'interface réseau
#

[ -z "$ecard" ] && ecard="eth0"
echo -e "$COLTXT"
echo "Redémarrage de l'interface réseau..."
echo -e "$COLCMD\c"
/etc/init.d/networking stop
/etc/init.d/networking start
ifup $ecard

#
# Redémarrage des services
#
echo -e "$COLTXT"
echo "Redémarrage des services..."
echo -e "$COLCMD\c"
/etc/init.d/slapd start
/etc/init.d/samba start
/etc/init.d/apache2 start
/etc/init.d/apache2se start


#
# Mise à jour de l'entrée se3 dans la branche 'Computers'
# Ou bien la modif est-elle effectuée lors de la correction d'urlse3' dans l'interface web?
# Ca ne devrait pas.
#
echo -e "$COLTXT"
echo "Mise à jour de l'entrée se3 dans la branche 'Computers'"
echo -e "$COLCMD\c"
NOM_NETBIOS_SE3=$(cat /etc/samba/smb.conf | grep -v "#" | grep -v ";" | grep "netbios name" | cut -d"=" -f2 | sed -e "s/ //g")
BASE_DN=$(cat /etc/ldap/ldap.conf | grep -v "#" | grep BASE | sed -e "s/BASE//g" | sed -e "s/ //g")
#Au cas où quelqu'un aurait nommé son admin rootdn (ou aurait rootdn dans son BASE_DN):
ADMIN_DN=$(cat /etc/ldap/slapd.conf | grep -v "#" | grep rootdn | tr "\t" " " | sed -e "s/ \{2,\}/ /g" | sed -e 's/"//g'| cut -d" " -f2)
echo "dn: cn=$NOM_NETBIOS_SE3,ou=Computers,$BASE_DN" > /tmp/maj_chgt_ip_se3.ldif
echo "changetype: modify" >> /tmp/maj_chgt_ip_se3.ldif
echo "replace: ipHostNumber" >> /tmp/maj_chgt_ip_se3.ldif
echo "ipHostNumber: $NEW_IP" >> /tmp/maj_chgt_ip_se3.ldif
echo "" >> /tmp/maj_chgt_ip_se3.ldif
ldapmodify -x -D "$ADMIN_DN" -w $(cat /etc/ldap.secret) -f /tmp/maj_chgt_ip_se3.ldif

# maj params pour wpkg
[ -e /usr/share/se3/scripts/wpkg_initvars.sh ] &&  /usr/share/se3/scripts/wpkg_initvars.sh

[ -n "$dhcp_wins" ] && /usr/share/se3/scripts/makedhcpdconf 




# domscripts
/usr/share/se3/sbin/update-domscripts.sh 

# Nettoyage /home/netlogon/machine/
echo "Nettoyage /home/netlogon/machine/"
 
rm -rf /home/netlogon/machine/*


# logonpy
/usr/share/se3/sbin/update-logonpy.sh
/usr/share/se3/sbin/update-smbconf.sh



# Reconfiguration de se3-clients-linux
echo "Reconfiguration de se3-clients-linux si besoin est"
CLIENTLINUX="$(aptitude search  se3-clients-linux | grep ^i)"
if [ -n "$CLIENTLINUX"  ]; then
     dpkg-reconfigure se3-clients-linux
fi


echo -e "$COLINFO"
echo "Par sécurité:"
echo -e "$COLTXT\c"
echo "Création d'un script de retour à l'état initial:"
echo "retablissement_config_initiale.sh"
echo -e "$COLCMD\c"
echo "/etc/init.d/samba stop
/etc/init.d/slapd stop
/etc/init.d/apache2 stop
/etc/init.d/apache2se stop
cp -f /etc/network/interfaces.ori /etc/network/interfaces
cp -f /etc/ldap/ldap.conf.ori /etc/ldap/ldap.conf
# cp -f /etc/ldap/slapd.conf.ori /etc/ldap/slapd.conf
cp -f /etc/pam_ldap.conf.ori /etc/pam_ldap.conf
cp -f /etc/libnss-ldap.conf.ori /etc/libnss-ldap.conf
cp -f /etc/samba/smb.conf.ori /etc/samba/smb.conf
cp -f /etc/hosts.ori /etc/hosts
cp -f /etc/resolv.conf.ori /etc/resolv.conf
cp -f /var/se3/Progs/install/ocs-config.bat.ori /var/se3/Progs/install/ocs-config.bat
cp -f $HOTPERM_FICH.ori $HOTPERM_FICH
cp -f /etc/ldap/config.se3.ori /etc/ldap/config.se3

echo \"UPDATE params SET value='http://$OLD_IP:909' WHERE name='urlse3';\" > /tmp/retablissement_ip_se3.sql
echo \"UPDATE params SET value='$OLD_IP' WHERE name='ldap_server';\" >> /tmp/retablissement_ip_se3.sql
echo \"UPDATE params SET value='$OLD_IP' WHERE name='se3ip';\" >> /tmp/retablissement_ip_se3.sql
echo \"UPDATE params SET value='$NEW_NETMASK' WHERE name='se3mask';\" >> /tmp/maj_chgt_ip_se3.sql


"> retablissement_config_initiale.sh
if [ -n "$dhcp_wins" ]; then
echo "	
echo \"UPDATE params SET value='$OLD_IP' WHERE name='dhcp_wins';\" >> /tmp/retablissement_ip_se3.sql
echo \"UPDATE params SET value='$OLD_IP' WHERE name='dhcp_tftp_server';\" >> /tmp/retablissement_ip_se3.sql" >> retablissement_config_initiale.sh
fi


echo "
mysql -u$dbuser -p$dbpass $dbname < /tmp/retablissement_ip_se3.sql
. /usr/share/se3/includes/config.inc.sh -clpbmsdf
echo \"dn: cn=$NOM_NETBIOS_SE3,ou=Computers,$BASE_DN\" > /tmp/retablissement_chgt_ip_se3.ldif
echo \"changetype: modify\" >> /tmp/retablissement_chgt_ip_se3.ldif
echo \"replace: ipHostNumber\" >> /tmp/retablissement_chgt_ip_se3.ldif
echo \"ipHostNumber: $OLD_IP\" >> /tmp/retablissement_chgt_ip_se3.ldif
echo \"\" >> /tmp/retablissement_chgt_ip_se3.ldif

/etc/init.d/networking stop
/etc/init.d/networking start
ifup $ecard

/etc/init.d/slapd start
/etc/init.d/samba start
/etc/init.d/apache2 start
/etc/init.d/apache2se start

ldapmodify -x -D \"$ADMIN_DN\" -w $(cat /etc/ldap.secret) -f /tmp/retablissement_chgt_ip_se3.ldif
[ -e /usr/share/se3/scripts/wpkg_initvars.sh ] &&  /usr/share/se3/scripts/wpkg_initvars.sh

# domscripts
/usr/share/se3/sbin/update-domscripts.sh 

# Nettoyage /home/netlogon/machine/
rm -rf /home/netlogon/machine/*


# logonpy
/usr/share/se3/sbin/update-logonpy.sh
/usr/share/se3/sbin/update-smbconf.sh

echo \"Reconfiguration de se3-clients-linux si besoin est\"
CLIENTLINUX=\"$(aptitude search  se3-clients-linux | grep ^i)\"
if [ -n \"$CLIENTLINUX\"  ]; then
     dpkg-reconfigure se3-clients-linux
fi



" >> retablissement_config_initiale.sh


if [ -n "$dhcp_wins" ]; then	
echo "/usr/share/se3/scripts/makedhcpdconf" >> retablissement_config_initiale.sh

fi


chmod +x retablissement_config_initiale.sh



echo -e "$COLTXT"
echo "Fin des opérations."
echo "Appuyez sur ENTREE pour afficher quelques infos."
read PAUSE

echo -e "$COLINFO"
echo "Si vous utilisez le paquet se3-dhcp, veuillez vérifier les paramètres
de configuration du dhcp pour vos clients"
echo "Il restera également à corriger:"
#echo " - le contenu du fichier /etc/hosts"
#echo " - les variables 'urlse3' et 'ldap_server' dans l'interface web"
#echo "   (en passant en mode sans échec: http://$NEW_IP:909/setup/)"
#echo " - l'entrée 'se3' dans la branche Computers de l'annuaire LDAP."
echo " - si certaines applis web sont installées sur le SE3, il est possible"
echo "   qu'il faille corriger les bookmarks des utilisateurs"
echo "   (ou au moins les informer)."
echo "   Corriger au moins dans /etc/skel/user/profil/appdata/Mozilla/Firefox/...:"
echo "    . le prefs.js pour la page d'accueil si elle pointe sur une appli sur le SE3"
echo "    . bookmarks.html si des applis..."
echo " - si un serveur esclave est défini, son IP doit peut-être être modifiée..."
echo " - côté client, le WINS devra être corrigé."
echo " - Reconfigurez le serveur DHCP si le module se3-dhcp est en place."
echo " - Corriger le proxy si necessaire (dans l'interface web SE3 et dans /etc/profile)"

echo ""
echo "Si le domaine DNS a également changé, pensez à corriger la ligne 'search'"
echo "du fichier /etc/resolv.conf"
echo "Contrôlez aussi la configuration de l'expédition des mails dans l'interface:"
echo "   Informations système/Diagnostic/Configuration mail"

echo ""
echo "Avant de tenter des connexions Window$, il peut être nécessaire de "
echo "redémarrer SE3 pour remettre tous les services en ordre."

echo -e "$COLTITRE"
echo "Terminé!"

echo -e "$COLTXT"
echo "Appuyez sur ENTREE pour terminer."
read PAUSE

