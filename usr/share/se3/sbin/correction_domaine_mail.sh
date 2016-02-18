#!/bin/bash
#
##### Script permettant de changer le domaine mail des utilisateurs dans l'annuaire ldap #####
#
# Auteur: Stephane Boireau (Bernay/Pont-Audemer (27))
#
## $Id$ ##
#
# Dernière modif: 12/09/2006

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Script permettant de changer le domaine mail des utilisateurs"
	echo "dans l'annuaire LDAP."
	echo "Usage : Aucune option"
	echo "        Il suffit de répondre aux questions."
	exit
fi

echo "************************"
echo "* Script de correction *"
echo "*   du domaine mail    *"
echo "************************"
echo ""
bon_domaine=""
while [ -z "$bon_domaine" ]
do
	echo ""
	echo "Quel est le nom du domaine mail à mettre en place?"
	echo -e "Domaine: \c"
	read bon_domaine

	echo "L'adresse mail de toto sera transformée en toto@$bon_domaine"
	echo -e "Est-ce correct? (o/n) \c"
	read REPONSE

	if [ "$REPONSE" != "o" ]; then
		bon_domaine=""
	fi
done

echo ""
echo "Sauvegarde initiale du LDAP."
echo "Le serveur LDAP va être arrêté puis redémarré."
echo "Appuyez sur ENTREE pour poursuivre..."
read PAUSE

ladate=$(date +"%Y.%m.%d-%H.%M.%S");
#tmp=/root/tmp/$ladate
tmp=/home/_root_tmp_correction_domaine_mail_${ladate}
mkdir -p $tmp

ROOTDN=$(cat /etc/ldap/slapd.conf | grep '^rootdn' | tr '\n' ' ' | cut -d'"' -f2)
BASE=$(cat /etc/ldap/ldap.conf | grep '^BASE' | tr "\n" " " | sed -e "s/ \{2,\}/ /g" | cut -d" " -f2)

ldapsearch -xLLL -D "$ROOTDN" -w "$(cat /etc/ldap.secret)" > $tmp/ldapsearch_${ladate}.ldif

/etc/init.d/slapd stop
sleep 5
if ps aux | grep slapd | grep -v grep > /dev/null ;then
	echo "ERREUR: Le serveur LDAP n'est semble-t-il pas arrêté."
	echo "Par précaution, le script s'arrête là."
	exit
else
	echo "LDAP arrêté."
fi
# Le /var/lib/ldap a tendance à être trop gros pour faire une sauvegarde tar
#tar -czf $tmp/var_lib_ldap_${ladate}.tar.gz /var/lib/ldap
slapcat > $tmp/slapcat_${ladate}.ldif
/etc/init.d/slapd start
sleep 5

if ps aux | grep slapd | grep -v grep > /dev/null ;then
	echo "LDAP redémarré."
else
	echo "ERREUR: Le serveur LDAP n'est semble-t-il pas redémarré."
	echo "Par précaution, le script s'arrête là."
	exit
fi

echo "#!/bin/bash" > $tmp/restaure_ldap.sh
echo "/etc/init.d/slapd stop
sleep 5
if ps aux | grep slapd | grep -v grep > /dev/null ;then
	echo \"ERREUR: Le serveur LDAP n'est semble-t-il pas arrêté.\"
	echo \"Par précaution, le script s'arrête là.\"
	exit
else
	echo \"LDAP arrêté.\"
fi
cd /
tar -xzf $tmp/var_lib_ldap_${ladate}.tar.gz
/etc/init.d/slapd start
if ps aux | grep slapd | grep -v grep > /dev/null ;then
	echo \"LDAP redémarré.\"
else
	echo \"ERREUR: Le serveur LDAP n'est semble-t-il pas redémarré.\"
	echo \"Il faut le redémarrer à la main.\"
	exit
fi" >> $tmp/restaure_ldap.sh

chmod +x $tmp/restaure_ldap.sh

echo ""
echo "Début des corrections..."
ldapsearch -xLLL -b "ou=People,$BASE" uid | grep "^uid: " | sed -e "s/^uid: //" | while read login
do
	if [ "$login" != "ldapadm" -a "$login" != "smbadm" -a "$login" != "samba" -a "$login" != "root" -a "$login" != "admin" ]; then
		if ! ldapsearch -xLLL uid=$login mail | grep "^mail: $login@$bon_domaine$" > /dev/null; then
			echo "dn: uid=$login,ou=People,$BASE" > $tmp/modif_email_${login}.ldif
			echo "changetype: modify" >> $tmp/modif_email_${login}.ldif
			echo "replace: mail" >> $tmp/modif_email_${login}.ldif
			echo "mail: $login@$bon_domaine" >> $tmp/modif_email_${login}.ldif
			echo "" >> $tmp/modif_email_${login}.ldif
			ldapmodify -x -D "$ROOTDN" -w "$(cat /etc/ldap.secret)" -f $tmp/modif_email_${login}.ldif
		fi
	fi
done

echo "Terminé."

echo ""
echo "Pour restaurer le LDAP en cas de problème, lancer:"
echo "$tmp/restaure_ldap.sh"
echo ""
