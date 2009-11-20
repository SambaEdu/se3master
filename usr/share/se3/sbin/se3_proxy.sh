#!/bin/sh

#
## $Id$ ##
#
##### script de modif de /etc/profile afin que la machine passe par un proxy #####
# modestement ecrit par franck molle 07/2004

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Modifie /etc/profile pour ajouter la conf d'un proxy"
	echo "Usage : pas d'option"
	exit
fi	


if [ `cat /etc/profile | grep se3_proxy` ]; then 
echo "un proxy est deja declaré dans /etc/profile"
exit 0
fi

echo "Passez vous par un slis ou un autre proxy"
echo "pour vous connecter à  internet ? (o/n)"
read REPONSE
while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
do
	echo ""
	echo "Passez vous par un slis ou un autre proxy"
	echo "pour vous connecter à  internet ? (o/n)"
	read REPONSE
done

### on touche a rien si ya pas de proxy ####
if [ "$REPONSE" == "o" ]; then

while [ "$IPOK" != "o" ]
do
	echo "Quelle est l'adresse ip de votre proxy ?"
	read IPPROXY
	echo "Quel est le port de votre proxy (3128)?"
	read PROXYPORT
	if [ -z "$PROXYPORT" ]; then
		PROXYPORT=3128
	fi
	echo "Votre proxy est accessible à $IPPROXY:$PROXYPORT"
	echo "Est-ce correct ? (o/n)"
	read IPOK
	while [ "$IPOK" != "o" -a "$IPOK" != "n" ]
		do
		echo "Est-ce correct ? (o/n)"
		read IPOK
		done
done

### on part du principe ou le port est PROXYPORT
 
echo "je met a jour le fichier /etc/profile ....."
echo "#se3_proxy" >> /etc/profile
echo "http_proxy=\"http://$IPPROXY:$PROXYPORT\"" >> /etc/profile
echo "ftp_proxy=\"http://$IPPROXY:$PROXYPORT\"" >> /etc/profile
echo "export http_proxy ftp_proxy" >> /etc/profile
echo
fi
echo "terminé !"
exit 0
