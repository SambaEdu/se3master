#!/bin/bash

# $Id$
# Script de changement d'uid
# Auteur: Stephane Boireau

if [ -z "$2" -o "$1" = "-h" -o "$1" = "--help" ]; then
	echo "USAGE: Script de changement d'uid."
	echo "       Passer en parametres l'ancien uid et le nouveau."
	echo "       Exemple:"
	echo "                $0 ancien_uid nouvel_uid"
	echo ""
	echo "       Pour modifier aussi l'uid dans le profil Thunderbird:"
	echo "                $0 ancien_uid nouvel_uid y"
	echo "       Attention: Si Thunderbird contient des comptes autres que celui du SE3,"
	echo "                  il se peut qu'il ne faille pas modifier le profil Thunderbird."
	exit
fi

# Caracteres autorises
caracteres_autorises="a-z0-9\.-"

ladate=$(date +%Y%m%d%H%M%S)
tmp=/var/se3/save/modification_ldap/$ladate
mkdir -p $tmp

ancien_uid=$1
nouvel_uid=$2
modif_thund_prefjs=$3

WWWPATH="/var/www"

# recup parametres ldap
. /etc/se3/config_l.cache.sh
# recup parametres caches :
. /etc/se3/config_m.cache.sh
. /etc/se3/config_d.cache.sh

# Tester si l'ancien uid existe
t=$(ldapsearch -xLLL -b $peopleRdn,$ldap_base_dn uid=$ancien_uid)
if [ -z "$t" ]; then
	echo "ABANDON: L'uid $ancien_uid n'existe pas dans la branche People."
	exit
fi

# Tester si le nouvel uid existe déja
t=$(ldapsearch -xLLL -b $peopleRdn,$ldap_base_dn uid=$nouvel_uid)
if [ -n "$t" ]; then
	echo "ABANDON: Le nouvel uid $nouvel_uid existe deja dans la branche People."
	exit
fi

t2=$(ldapsearch -xLLL -b $trashRdn,$ldap_base_dn uid=$nouvel_uid)
if [ -n "$t2" ]; then
	echo "ABANDON: Le nouvel uid $nouvel_uid existe deja dans la branche Trash."
	exit
fi

# Tester si le nouvel uid est correctement formate
t=$(echo "$nouvel_uid"|tr "[A-Z]" "[a-z]"|sed -e "s|[$caracteres_autorises]*||g")
if [ -n "$t" ]; then
	echo "ABANDON: Le nouvel uid $nouvel_uid contient des caracteres non autorises: $t"
	exit
fi

# Modif dans l'annuaire
# Extraction du dn: uid=$ancien_uid,...
echo "Sauvegarde de l'entree ${ancien_uid}"
ldapsearch -xLLL -D $adminRdn,$ldap_base_dn -w $adminPw -b $peopleRdn,$ldap_base_dn uid=$ancien_uid > $tmp/svg_${ancien_uid}.ldif
if [ "$?" != "0" ]; then
	echo "ABANDON: Il s'est produit une erreur lors de la sauvegarde."
	echo "         On ne modifie pas l'uid."
	exit
fi

# Modification du LDIF
cat $tmp/svg_${ancien_uid}.ldif|sed -e "s|^dn: uid=$ancien_uid,|dn: uid=$nouvel_uid,|;s|^uid: $ancien_uid$|uid: $nouvel_uid|;s|^mail: $ancien_uid@|mail: $nouvel_uid@|;s|^homeDirectory: /home/$ancien_uid$|homeDirectory: /home/$nouvel_uid|;" > $tmp/modif_uid_${ancien_uid}_vers_${nouvel_uid}.ldif
if [ "$?" != "0" ]; then
	echo "ABANDON: Il s'est produit une erreur lors de l'operation de modification"
	echo "         sur la sauvegarde."
	echo "         On ne modifie pas l'uid."
	exit
fi

# Suppression de l'ancienne entree
echo "Suppression de l'ancienne entree $ancien_uid"
ldapdelete -x -D $adminRdn,$ldap_base_dn -w $adminPw uid=$ancien_uid,$peopleRdn,$ldap_base_dn
if [ "$?" != "0" ]; then
	echo "ABANDON: Il s'est produit une erreur lors de la suppression de l'ancienne entree."
	echo "         On ne poursuit pas."
	exit
fi

# Import de la nouvelle entree
echo "Import de la nouvelle entree $nouvel_uid"
ldapadd -x -D $adminRdn,$ldap_base_dn -w $adminPw -f $tmp/modif_uid_${ancien_uid}_vers_${nouvel_uid}.ldif
if [ "$?" != "0" ]; then
	echo "ABANDON: Il s'est produit une erreur lors de l'ajout de la nouvelle entree."
	echo "         On ne poursuit pas."
	exit
fi

if [ -e "/home/$ancien_uid" ]; then
	mv /home/$ancien_uid /home/$nouvel_uid
fi

# Faut-il corriger les
# /home/$ancien_uid/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js
# /home/$ancien_uid/profil/appdata/Thunderbird/Profiles/default/prefs.js
# /home/$ancien_uid/Docs/desktop.ini
# ?


if [ "$modif_thund_prefjs" = "y" -a -e /home/$nouvel_uid/profil/appdata/Thunderbird/Profiles/default/prefs.js ]; then
	cp /home/$nouvel_uid/profil/appdata/Thunderbird/Profiles/default/prefs.js /home/$nouvel_uid/profil/appdata/Thunderbird/Profiles/default/prefs.js.$ladate
	echo "Correction du /home/$nouvel_uid/profil/appdata/Thunderbird/Profiles/default/prefs.js"
	sed -i "s|/$ancien_uid@|/$nouvel_uid@|;s|\"$ancien_uid@|\"$nouvel_uid@|;s|\"$ancien_uid\");|\"$nouvel_uid\");|" /home/$nouvel_uid/profil/appdata/Thunderbird/Profiles/default/prefs.js
fi

# Modification des member
echo "Correction des appartenances member"
cpt=0
ldapsearch -xLLL member=uid=$ancien_uid,$peopleRdn,$ldap_base_dn | grep "^dn: " | cut -d"," -f1,2 | while read A
do
	echo "$A,$ldap_base_dn
changetype: modify
add: member
member: uid=$nouvel_uid,$peopleRdn,$ldap_base_dn
-
delete: member
member: uid=$ancien_uid,$peopleRdn,$ldap_base_dn
" >> $tmp/modif_member_$cpt.ldif

	#cat $tmp/modif_memberUid_$cpt.ldif
	ldapmodify -x -D $adminRdn,$ldap_base_dn -w $adminPw -f $tmp/modif_member_$cpt.ldif
	#sleep 1

	cpt=$(($cpt+1))
done

# Modification des memberUid
echo "Correction des appartenances memberUid"
cpt=0
ldapsearch -xLLL memberUid=$ancien_uid | grep "^dn: " | cut -d"," -f1,2 | while read A
do
	echo "$A,$ldap_base_dn
changetype: modify
add: memberUid
memberUid: $nouvel_uid
-
delete: memberUid
memberUid: $ancien_uid
" >> $tmp/modif_memberUid_$cpt.ldif

	#cat $tmp/modif_memberUid_$cpt.ldif
	ldapmodify -x -D $adminRdn,$ldap_base_dn -w $adminPw -f $tmp/modif_memberUid_$cpt.ldif
	#sleep 1

	cpt=$(($cpt+1))
done

# Y a-t-il des enregistrements ailleurs? dans mysql?

# Parcours des ACL?
# Non... Apparemment, en modifiant dans le ldap, c'est OK.
# Les acl doivent donc reposer sur les uidNumber

echo "Termine."

if [ "$modif_thund_prefjs" != "y" ]; then
	echo "Il se peut qu'il faille corriger le fichier:
/home/$nouvel_uid/profil/appdata/Thunderbird/Profiles/default/prefs.js
s'il existe."
fi
