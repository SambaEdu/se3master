#!/bin/bash

## $Id$ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE=$LOG_DIR/log_maj103

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
	#On envoie un mail � l'admin
	echo "$REPORT"  | mail -s "[SE3] R�sultat de la Mise a jour 100" $MAIL_ADMIN
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


# Mise a jour du journal des mises a jour

echo "Mise a jour 103:
- Correction pb des eleves dans plusieurs classes
- Ajout suppression dhcp + inventaire lors de la suppresion d'une machine de l'annuaire
- Ajout acls equipe sur les fichiers d�pos�s via la distribution
- Correction bug interface sauvegarde, ajout dep sur smbfs" >> $HISTORIQUE_MAJ
MAIL_REPORT
echo "Un mail recapitulatif de la mise � jour sera envoy�"

exit 0
