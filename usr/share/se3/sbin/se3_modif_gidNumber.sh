#!/bin/bash

#
## $Id$ ##
#
##### Script de correction pour les doublons de gidnumbers - stéphane Boireau #####

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Corrige le doublon de gidNumber"
	echo "Usage : aucune option"
	exit
fi


# Pour régler la collision clamav/lcs-users
# Après traitement, on doit avoir:
#    clamav:    108
#    lcs-users: 1000
# Seul le gidNumber du groupe lcs-users et des comptes utilisateurs dont le gidNumber était 108 doiven être modifiés pour passer à 1000
gid1=108
gid2=1000

# Pour régler la collision lcs-users/machines
# Après traitement, on doit avoir:
#    lcs-users: 1000
#    machines:  999
# Seul le gidNumber du groupe machines et des comptes machines doit être modifié dans la deuxième étape
gid3=1000
gid4=999


#BASE=$(grep "^BASE" /etc/ldap/ldap.conf | tr "\t" " " | sed -e "s/ \{2,\}/ /g" | cut -d" " -f2)
#ROOTDN=$(grep "^rootdn" /etc/ldap/slapd.conf | tr "\t" " " | sed -e "s/ \{2,\}/ /g" | sed -e 's/"//g' | cut -d" " -f2)
#ROOTPW=$(cat /etc/ldap.secret)
BASEDN=$(cat /etc/ldap/ldap.conf | grep "^BASE" | tr "\t" " " | sed -e "s/ \{2,\}/ /g" | cut -d" " -f2)
ROOTDN=$(cat /etc/ldap/slapd.conf | grep "^rootdn" | tr "\t" " " | cut -d'"' -f2)
PASSDN=$(cat /etc/ldap.secret)

ladate=$(date +"%Y.%m.%d-%H.%M.%S");

#tmp=/root/tmp/tmp.$ladate
tmp=/home/_root_tmp.$ladate
mkdir -p $tmp
chmod 700 $tmp

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

echo -e "$COLTITRE"
echo "*****************************"
echo "* Modification de gidNumber *"
echo "*****************************"

echo -e "$COLINFO"
echo "Traitement de clamav/lcs-users"

echo -e "$COLTXT"
echo "Sauvegarde des ACL de /var/se3"
echo -e "$COLCMD\c"
cd /var/se3
getfacl -R . > $tmp/varse3.acl

echo -e "$COLTXT"
echo "Sauvegarde des ACL de /home"
echo -e "$COLCMD\c"
cd /home
getfacl -R . > $tmp/home.acl

echo -e "$COLTXT"
echo "Sauvegarde de l'annuaire..."
echo -e "$COLCMD\c"
ldapsearch -xLLL -D $ROOTDN -w $PASSDN > $tmp/svg_ldap_${ladate}.ldif

echo -e "$COLTXT"
echo "Modification des groupes propriétaires de $gid1 à $gid2 sur /var/se3" | tee -a $tmp/chg_proprio_var_se3.txt
echo -e "$COLCMD\c"
find /var/se3/ -gid $gid1 | while read A
do
	echo "Traitement de: $A" >> $tmp/chg_proprio_var_se3.txt
	chown :$gid2 "$A" | tee -a $tmp/chg_proprio_var_se3.txt
	#chown -v :$gid2 "$A" | tee -a $tmp/chg_proprio_var_se3.txt
done

echo -e "$COLTXT"
echo "Modification des groupes propriétaires de $gid1 à $gid2 sur /home" | tee -a $tmp/chg_proprio_home.txt
echo -e "$COLCMD\c"
find /home/ -gid $gid1 | while read A
do
	echo "Traitement de: $A" >> $tmp/chg_proprio_home.txt
	chown :$gid2 "$A" | tee -a $tmp/chg_proprio_home.txt
	#chown -v :$gid2 "$A" | tee -a $tmp/chg_proprio_home.txt
done

echo -e "$COLTXT"
echo "Modif du gidNumber pour cn=lcs-users" | tee -a $tmp/chg_gidNumber_lcs-users.txt
echo -e "$COLCMD\c"
echo "dn: cn=lcs-users,ou=Groups,$BASEDN
changetype: modify
replace: gidNumber
gidNumber: $gid2
" > $tmp/lcsusers_modif.ldif
ldapmodify -x -D $ROOTDN -w $PASSDN -f $tmp/lcsusers_modif.ldif | tee -a $tmp/chg_gidNumber_lcs-users.txt

echo -e "$COLTXT"
echo "Recherche des comptes à modifier..." | tee -a $tmp/chg_gidNumber_comptes.txt
echo -e "$COLCMD\c"
ldapsearch -xLLL -b ou=People,$BASEDN gidNumber=$gid1 uid | grep "^uid: " | sed -e "s/^uid: //" | while read uid
do
        echo -e "$COLTXT\c"
        echo "Modification de $uid" | tee -a $tmp/chg_gidNumber_comptes.txt
        echo -e "$COLCMD\c"
        echo "dn: uid=$uid,ou=People,$BASEDN
changetype: modify
replace: gidNumber
gidNumber: $gid2
" > $tmp/${uid}_modif.ldif
        ldapmodify -x -D $ROOTDN -w $PASSDN -f $tmp/${uid}_modif.ldif | tee -a $tmp/chg_gidNumber_comptes.txt
done


tst=$(ldapsearch -xLLL -b ou=Groups,$BASEDN cn=machines gidNumber | grep "^gidNumber: " | sed -e "s/^gidNumber: //")
if [ "$tst" = "1000" ]; then

	echo -e "$COLINFO"
	echo "Traitement de lcs-users/machines"

	echo -e "$COLTXT"
	echo "Modif du gidNumber pour cn=machines" | tee -a $tmp/chg_gidNumber_machines.txt
	echo -e "$COLCMD\c"
	echo "dn: cn=machines,ou=Groups,$BASEDN
changetype: modify
replace: gidNumber
gidNumber: $gid4
" > $tmp/machines_modif.ldif
	ldapmodify -x -D $ROOTDN -w $PASSDN -f $tmp/machines_modif.ldif | tee -a $tmp/chg_gidNumber_machines.txt

	echo -e "$COLTXT"
	echo "Recherche des comptes à modifier..." | tee -a $tmp/chg_gidNumber_comptes.txt
	echo -e "$COLCMD\c"
	ldapsearch -xLLL -b ou=Computers,$BASEDN gidNumber=$gid3 uid | grep "^uid: " | sed -e "s/^uid: //" | while read uid
	do
			n_uid=$(echo "$uid" | tr "$" "_")
			echo -e "$COLTXT\c"
			echo "Modification de $uid" | tee -a $tmp/chg_gidNumber_comptes_machines.txt
			echo -e "$COLCMD\c"
			echo "dn: uid=$uid,ou=Computers,$BASEDN
changetype: modify
replace: gidNumber
gidNumber: $gid4
" > $tmp/${n_uid}_modif.ldif
			ldapmodify -x -D $ROOTDN -w $PASSDN -f $tmp/${n_uid}_modif.ldif | tee -a $tmp/chg_gidNumber_comptes_machines.txt
#" > $tmp/${uid}_modif.ldif
			#ldapmodify -x -D $ROOTDN -w $PASSDN -f $tmp/${uid}_modif.ldif | tee -a $tmp/chg_gidNumber_comptes_machines.txt
	done
fi


echo -e "$COLTXT"
echo "Les sauvegardes et les fichiers temporaires sont accessibles dans:"
echo -e "   ${COLINFO}${tmp}"

echo -e "$COLTITRE"
echo "***********"
echo "* Terminé *"
echo "***********"
echo -e "$COLTXT"
