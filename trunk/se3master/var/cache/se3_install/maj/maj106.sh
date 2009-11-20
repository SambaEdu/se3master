#!/bin/bash

## $Id: maj106.sh 3475 2009-01-17 22:19:46Z plouf $ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE=$LOG_DIR/log_maj106

#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"

#date
LADATE=$(date +%d-%m-%Y)

### recup pass root mysql
. /root/.my.cnf 2>/dev/null

MAIL_REPORT()
{
[ -e /etc/ssmtp/ssmtp.conf ] && MAIL_ADMIN=$(cat /etc/ssmtp/ssmtp.conf | grep root | cut -d= -f2)
if [ ! -z "$MAIL_ADMIN" ]; then
	REPORT=$(cat $REPORT_FILE)
	#On envoie un mail à l'admin
	echo "$REPORT"  | mail -s "[SE3] Résultat de la Mise a jour 106" $MAIL_ADMIN
fi
}


POURSUIVRE()
{
	[ -n "$1" ] && echo "$1"
	REPONSE=""
	while [ "$REPONSE" != "o" -a "$REPONSE" != "O" -a "$REPONSE" != "n" ]
	do
		#echo -e "$COLTXT"
		echo -e "${COLTXT}Peut-on poursuivre ? (${COLCHOIX}O/n${COLTXT}) $COLSAISIE\c"
		read REPONSE
		if [ -z "$REPONSE" ]; then
			REPONSE="o"
		fi
	done
	echo -e "$COLTXT"
	if [ "$REPONSE" != "o" -a "$REPONSE" != "O" ]; then
		echo "Abandon!"
		exit 1
	fi
}

#Modif smb.conf afin de ne pas écrire avec groupe root
[ -z "$(grep "force group" /etc/samba/smb.conf)" ] && sed "s/admin users = @admins/admin users = @admins\n\tforce group = admins/" -i /etc/samba/smb.conf
echo ""
echo "Relance le script pour les clients Linux"
/usr/share/se3/sbin/create_client_linux.sh >/dev/null
echo ""
echo "Mise à jour du schéma samba et des index ldap"
sed "/samba3.schema/d" -i /etc/ldap/slapd.conf
echo ""
cp conf/samba_se3etch.schema /etc/ldap/schema/samba.schema
/usr/share/se3/scripts/mkSlapdConf.sh index

# Connexion automatique aux imprimantes partagées par le se3 : se3printers.bat
mkdir -p /var/se3/Progs/ro/printers
mkdir -p /var/se3/unattended/install/packages/windows/printers
# déploiement de se3printers.bat
cp conf/se3printers.bat /var/se3/unattended/install/packages/windows/printers
# le lien symbolique permettra une mise à jour rapide (à l'aide du deploiementimprimantes.xml de wpkg), indépendante du paquet se3
# si quelqu'un a testé l'install en manuel, le fichier existe : on le remplace par le lien symbolique.
[ -e /var/se3/Progs/ro/printers/se3printers.bat ] && rm /var/se3/Progs/ro/printers/se3printers.bat
ln -s /var/se3/unattended/install/packages/windows/printers/se3printers.bat /var/se3/Progs/ro/printers/se3printers.bat
# on rajoute la ligne commentée au base/logon.bat (à documenter sur le wiki)
[ -z "`cat /home/templates/base/logon.bat | grep "se3printers.bat"`" ] && echo "::@if \"%OS%\"==\"Windows_NT\" call %LOGONSERVER%\Progs\ro\printers\se3printers.bat" >> /home/templates/base/logon.bat
unix2dos /home/templates/base/logon.bat


# Correction de droits
echo "Résolution de problèmes de droits, nettoyage de /home/netlogon - exécution de permse3"
/usr/share/se3/sbin/permse3
echo ""
# Mise en place des profils FF/TB pour les clients windows
echo "Mise en place des profils FF/TB par défaut pour les clients Linux"
echo "Vous pouvez faire concorder les profils mozilla sous windows à ceux des clients linux en lancant"
echo "/usr/share/script/se3_mozilla_win2linux.sh"
echo "**** Attention le script parcourt tous les homes donc il peut être long !****"
echo ""
for user in user user.linux
do
	if [ ! -e "/etc/skel/$user/.mozilla/firefox/default" -a -e "/etc/skel/$user/profil/appdata/Mozilla/Firefox/Profiles/default"  ]; then
# 		echo_debug "/etc/skel/$user/.mozilla/firefox/default n'existe pas encore"
		mkdir -p /etc/skel/$user/.mozilla/firefox
		cd /etc/skel/$user/.mozilla/firefox
		echo "[General]
StartWithLastProfile=1

[Profile0]
Name=default
IsRelative=1
Path=default" > profiles.ini
		ln -s ../../profil/appdata/Mozilla/Firefox/Profiles/default ./
	fi

	if [ ! -e "/etc/skel/$user/.thunderbird/default" -a -e "/etc/skel/$user/profil/appdata/Thunderbird/Profiles/default" ]; then
# 		echo_debug "/etc/skel/$user/.thunderbird/default n'existe pas encore"
		mkdir -p /etc/skel/$user/.thunderbird
		cd /etc/skel/$user/.thunderbird
		echo "[General]
StartWithLastProfile=1

[Profile0]
Name=default
IsRelative=1
Path=default" > profiles.ini
		ln -s ../profil/appdata/Thunderbird/Profiles/default ./
	fi
done


# Mise a jour du journal des mises a jour
echo "Mise a jour 106:
- Imprimantes : Ajout mode client déployable par défaut à la place du client classique
- Imprimantes : Correction bug non prise en charge de certaines modifications sur une imprimante
- Imprimantes : Déploiement de se3printers.bat (connexion automatique aux imprimantes partagées par le se3)
- Connexion.sh : correctif bug et pour les vlan 
- machineAdd.pl : correctif sur entree cn=machine lorsque la mac est vide
- Maj de logonpl / permse3
- Correctifs sur les pages de tests
- Correctif pour italc
- Mise en place des profils FF/TB par défaut pour les clients windows
- Modification de permse3 pour pb de droits sur /home/netlogon">> $HISTORIQUE_MAJ
MAIL_REPORT
echo "Un mail recapitulatif de la mise à jour sera envoyé"

exit 0
