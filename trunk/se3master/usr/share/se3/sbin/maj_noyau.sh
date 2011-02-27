#!/bin/bash

## $Id$ ##
#
##### Permet la mise à jour vers un noyau supportant plus que 1go #####
#

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Script permettant la mise à jour vers un noyau supportant plus que 1go"
	echo "Usage : pas d'option"
	exit
fi	




#Couleurs
COLTITRE="\033[1;35m"   # Rose
COLDEFAUT="\033[0;33m"  # Brun-jaune
COLCMD="\033[1;37m"     # Blanc
COLERREUR="\033[1;31m"  # Rouge
COLTXT="\033[0;37m"     # Gris
COLINFO="\033[0;36m"	# Cyan
COLPARTIE="\033[1;34m"	# Bleu
COLCHOIX="\033[1;33m"	# Jaune
COLSAISIE="\033[1;32m"	# Vert

echo -e "$COLTITRE"
echo "*****************************************"
echo "* Passage à grub - mise à jour du noyau *"
echo "*****************************************"
echo -e "$COLTXT"
# sleep 1
[ -e /root/debug ] && DEBUG="yes"
[ -e /root/nodl ] && NODL="yes"

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
	while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
	do
		echo -e "$COLTXT"
		echo -e "Peut-on poursuivre? (${COLCHOIX}O/n${COLTXT}) $COLSAISIE\c"
		read REPONSE
		echo -e "$COLTXT"
		if [ -z "$REPONSE" ]; then
			REPONSE="o"
		fi
	done

	if [ "$REPONSE" != "o" -a "$REPONSE" != "O" ]; then
		ERREUR "Abandon!"
	fi
}


INSTALLGRUB()
{
echo -e "$COLPARTIE"
echo "Partie  2: Installation de grub" 
echo -e "$COLTXT"
echo "Le script va remplacer lilo par Grub et configurer automatiquement
le fichier de configuration de boot"
POURSUIVRE
apt-get install busybox initramfs-tools klibc-utils libklibc grub || "Erreur lors de l'installation des paquets nécessaire à grub"
rm -f /boot/grub/menu.*
rm -f /boot/grub/device.map
grub-install --no-floppy --recheck hd0
/usr/sbin/update-grub -y
verif=$(grep "root=/dev/.*2" /boot/grub/menu.lst)
if [ -z "$verif"  ]
then
  echo -e "$COLERREUR"
  echo "Attention, il est possible que le fichier généré par grub soit incorrecte
  votre machine risque ne ne pas pouvoir booter correctement
  Voici son contenu, vérifiez que les valeurs sont correctes pour la partition root
  Habituellement digloo installe la partition root sur la 2ème partition du disque"
  echo -e "$COLTXT"
  grep -v "^#" /boot/grub/menu.lst | grep -v "^$"
#   exit 1
fi
}


INSTALLTXT()
{
echo -e "$COLPARTIE"
echo "Partie 3 : Installation du nouveau noyau" 
echo -e "$COLTXT"
}


while true
do
	echo -e "$COLPARTIE"
	echo "Partie 1 : Choix du noyau" 
	echo -e "$COLINFO"
echo "Veuillez choisir le noyau à télécharger :
1 - Noyau 2.6.18-686 classique pour serveurs avec moins de 4go de ram 
2 - Noyau 2.6.18-bigmem pour serveurs avec moins de 4go de ram 
3 - Noyau 2.6.26-686 classique pour serveurs avec moins de 4go de ram 
4 - Noyau 2.6.26-686-bigmem pour serveurs au moins moins de 4go de ram
5 - Ne rien faire, sortir du script"
	

REPONSE=""
echo -e "$COLCHOIX"
echo -e "Votre choix ? $COLSAISIE\c"
read REPONSE
echo -e "$COLTXT"
	
case "$REPONSE" in
1)
INSTALLGRUB
INSTALLTXT
POURSUIVRE
mkdir -p /boot/grub
apt-get install linux-image-2.6.18-6-686
sed "s/^default.*0/default\t\tsaved/" -i /boot/grub/menu.lst
break
;;

2)
INSTALLGRUB
INSTALLTXT
POURSUIVRE
mkdir -p /boot/grub
apt-get install linux-image-2.6.18-6-686-bigmem
sed "s/^default.*0/default\t\tsaved/" -i /boot/grub/menu.lst
break
;;

3)
INSTALLGRUB
INSTALLTXT
POURSUIVRE
mkdir -p /boot/grub
noyo_pkg="linux-image-2.6.26-bpo.1-686_2.6.26-13~bpo40+1_i386.deb"
noyo_url="http://wawadeb.crdp.ac-caen.fr/iso/$noyo_pkg"
noyo_vers="linux-image-2.6.26-bpo.1-686"
md5_pkg="9277785503e7f2382173a43f11e1fb36"
cd /root
wget $noyo_url || ERREUR "Problème lors de la récupération du noyau, vérifiez votre connexion à internet"
[ "$md5_pkg" != "$(md5sum $noyo_pkg | awk '{print $1}')" ] && ERREUR "Somme Md5 de l'image téléchargée invalide"
dpkg -i $noyo_pkg
apt-get install firmware-bnx2
sed "s/^default.*0/default\t\tsaved/" -i /boot/grub/menu.lst
break
;;

4)
INSTALLGRUB
INSTALLTXT
POURSUIVRE
mkdir -p /boot/grub
noyo_pkg="linux-image-2.6.26-bpo.1-686-bigmem_2.6.26-13~bpo40+1_i386.deb"
md5_pkg="520b48eb2229cdd0e66c30b2c36a7aba"
noyo_url="http://wawadeb.crdp.ac-caen.fr/iso/$noyo_pkg"
noyo_vers="linux-image-2.6.26-bpo.1-686-bigmem"
cd /root
wget $noyo_url || ERREUR "Problème lors de la récupération du noyau, vérifiez votre connexion à internet"
[ "$md5_pkg" != "$(md5sum $noyo_pkg | awk '{print $1}')" ] && ERREUR "Somme Md5 de l'image téléchargée invalide"
dpkg -i $noyo_pkg
apt-get install firmware-bnx2
sed "s/^default.*0/default\t\tsaved/" -i /boot/grub/menu.lst
break
;;

5)
exit 0
;;

*) 
echo -e "$COLERREUR
Choix incorrect $COLTXT"
sleep 1
continue
;;

esac

done

echo -e "$COLINFO
Mise à jour du noyau terminée !
Si vous souhaitez installer un autre noyau, relancez le script
/usr/share/se3/sbin/maj_noyau.sh $COLTXT"

exit 0



