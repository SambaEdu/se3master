#!/bin/bash

#
##### Script destine a recreer les liens symboliques de /home et /var/se3/Classes apres une copie scp #####
#
# Auteur: Stephane Boireau (ex-Animateur TICE vire comme un malpropre par un regime qui ne voit que par les faveurs au prive et le degraissage du public)
#
## $Id$ ##
#
# /usr/share/se3/scripts/se3_recreer_liens_apres_scp.sh

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

# Recuperation des parametres LDAP
PEOPLERDN="ou=People"
BASEDN=$(cat /etc/ldap/ldap.conf | grep "^BASE" | tr "\t" " " | sed -e "s/ \{2,\}/ /g" | cut -d" " -f2)

. /usr/share/se3/sbin/variables_admin_ldap.sh lib > /dev/null

echo -e "$COLTITRE"
echo "********************************"
echo "*    Script de re-creation     *"
echo "*    des liens symboliques     *"
echo "* de /home et /var/se3/Classes *"
echo "*     apres une copie scp      *"
echo "********************************"

echo -e "$COLTXT"
echo "Parcours de /home"
echo -e "$COLCMD\c"
cd /home
echo "Controle de "
ls | while read A
do
	echo -e "$A \c"
	if [ "$A" = "_templates" -o "$A" = "_netlogon" ]; then
		if [ -d "$A" ]; then
			rm -fr "$A"
			ln -s "${A:1}" "$A"
		fi
	else
		t=$(ldapsearch -xLLL -b $PEOPLERDN,$BASEDN uid=$A)
		if [ -n "$t" ]; then
			cd "$A"

			if [ -d "Bureau" ]; then
				rm -fr Bureau
				ln -s Bureau profil/Bureau
			fi

			if [ -d "Demarrer" ]; then
				rm -fr Demarrer
				ln -s Demarrer profil/Demarrer
			fi
		fi
	fi
done

echo -e "$COLTXT"
echo "Parcours de /var/se3/Classes"
echo -e "$COLCMD\c"
cd /var/se3/Classes
ls | grep Classe_grp | while read A
do
	echo "Traitement de $A"
	cd "$A"
	ls | grep -v "^_" | while read B
	do
		rm -f "$B"
	done
	cd ..
done

cd /root

echo -e "$COLTXT"
echo "Recréation des dossiers de classes"
echo -e "$COLCMD\c"
updateClasses.pl -c ALL

echo -e "$COLTITRE"
echo "Termine."
echo -e "$COLTXT"
