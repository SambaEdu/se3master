#!/bin/bash

#
## $Id$ ##
#
##### Script de correction pour de gidnumbers en passant de Slis a LCS - stéphane Boireau #####

# gidNumber du groupe slis
# gid1=600
gid1=$(ldapsearch -xLLL uid=admin gidNumber | grep gidNumber | cut -d" " -f2)

# gidNumber du groupe lcs-users
gid2=5005
# gid2=$(ldapsearch -xLLL cn=lcs-users gidNumber | grep "^gidNumber: " | sed -e "s/^gidNumber: //")

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Correction des gidNumber de $gid1 a $gid2 sur le SE3."
	echo "Le script suppose que l'annuaire utilise est local."
	echo "Usage : aucune option"
	exit
fi

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
echo "Traitement de slis/lcs-users"

#echo -e "$COLTXT"
#echo "Sauvegarde des ACL de /var/se3"
#echo -e "$COLCMD\c"
#cd /var/se3
#getfacl -R . > $tmp/varse3.acl

#echo -e "$COLTXT"
#echo "Sauvegarde des ACL de /home"
#echo -e "$COLCMD\c"
#cd /home
#getfacl -R . > $tmp/home.acl

echo -e "$COLTXT"
echo "Sauvegarde de l'annuaire..."
echo -e "$COLCMD\c"
ldapsearch -xLLL -D $ROOTDN -w $PASSDN > $tmp/svg_ldap_${ladate}.ldif
if [ "$?" != "0" ]; then
	echo -e "$COLERREUR"
	echo "Il s'est produit une erreur lors de la sauvegarde."
	echo "Abandon."
	echo -e "$COLTXT"
	exit
fi

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
echo "Mofification du gidnumber de lcs-users pour passage à 5005..." | tee -a $tmp/chg_gidNumber_comptes.txt
echo -e "$COLCMD\c"
echo "dn: cn=lcs-users,ou=Groups,$BASEDN
changetype: modify
replace: gidNumber
gidNumber: $gid2
" > $tmp/lcs-users_modif.ldif
ldapmodify -x -D $ROOTDN -w $PASSDN -f $tmp/lcs-users_modif.ldif | tee -a $tmp/lcs-users_modif.txt

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
" >> $tmp/${uid}_modif.ldif
        ldapmodify -x -D $ROOTDN -w $PASSDN -f $tmp/${uid}_modif.ldif | tee -a $tmp/chg_gidNumber_comptes.txt
done


echo -e "$COLTXT"
echo "Les sauvegardes et les fichiers temporaires sont accessibles dans:"
echo -e "   ${COLINFO}${tmp}"

echo -e "$COLTITRE"
echo "***********"
echo "* Terminé *"
echo "***********"
echo -e "$COLTXT"
