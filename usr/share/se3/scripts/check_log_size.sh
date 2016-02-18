#!/bin/bash
#

## $Id$ ##

#
##### Permet de controler la taille des fichiers de logs pour prevenir un probleme de rotation #####

# Taille max d'un fichier de log en Mo:
nb_max=100

# Possibilite de passer en parametre la taille en Mo a tester
if [ -n "$1" ]; then
	t=$(echo "$1"|sed -e "s|[0-9]||g")
	if [ -z "$t" ]; then
		nb_max=$1
	fi
fi

# Taille max d'un fichier de log en ko:
taille_max=$((${nb_max}*1024))

tmp=/tmp/root_check_size_log_$(date +%Y%m%d%H%M%S)
mkdir -p -m 700 "$tmp"

MAIL_REPORT()
{
	[ -e /etc/ssmtp/ssmtp.conf ] && MAIL_ADMIN=$(cat /etc/ssmtp/ssmtp.conf | grep root | cut -d= -f2)
	if [ ! -z "$MAIL_ADMIN" ]; then
		REPORT=$(cat $REPORT_FILE)
		#On envoie un mail a l'admin
		echo "$REPORT"  | mail -s "[SE3] Resultat de $0" $MAIL_ADMIN
	fi
}

# Liste des fichiers de log
find /var/log/ -type f|xargs du -sk|sort -n > $tmp/liste_fichiers_log.txt

message="Un ou des fichiers de log depassent la taille maxi definie ($nb_max Mo)
Il se peut que cela signifie que la rotation des fichiers de log ne fonctionne pas correctement.
A terme, cela peut provoquer des dysfonctionnements et lenteurs du serveur.

Il se peut egalement que la taille maxi definie soit insuffisante pour tenir compte de la quantite d'informations a loguer avant la rotation des logs.
Quoi qu'il en soit vous devriez prendre des mesures.
"

# Parcours des fichiers de log
#chaine=""
while read A
do
	t=$(echo "$A"|tr "\t" " "|cut -d" " -f1)
	if [ $t -ge $taille_max ]; then
		#echo $A
		f=$(echo "$A"|tr "\t" " "|sed -e "s| \{2,\}| |g"|cut -d" " -f2)
		s=$(du -sh $f)
		#chaine="$chaine\n$s"
		echo $s >> $tmp/liste_fichiers_log_trop_volumineux.txt
	fi
done < $tmp/liste_fichiers_log.txt

# Envoi si necessaire d'un mail
#if [ -n "$chaine" ]; then
if [ -e "$tmp/liste_fichiers_log_trop_volumineux.txt" ]; then
	echo "$message" > $tmp/message.txt
	cat $tmp/liste_fichiers_log_trop_volumineux.txt >> $tmp/message.txt

	REPORT_FILE=$tmp/message.txt
	MAIL_REPORT
fi

# Menage
rm -fr "$tmp"
