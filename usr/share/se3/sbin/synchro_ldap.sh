#!/bin/bash

#
## $Id$ ##
#
##### Script de mise en place de la réplication entre le LDAP du Slis et du Se3 #####

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Permet de mettre en place la réplication" 
    echo "avec l'annuaire LDAP du Slis / lcs ou tout autre annuaire distant"
	echo "Usage : aucune option"
	exit
fi

#SCRIPT DE MISE EN PLACE DE LA REPLICATION
#DU LDAP DU SLIS VERS LE LDAP DU SE3
# Stephane Boireau / Franck Molle
# 01/2005
# modif keyser 13/04/05
# 11/2005 ajout tests  sur root et cn=profs

#Couleurs
COLTITRE="\033[1;35m"	# Rose
COLPARTIE="\033[1;34m"	# Bleu

COLTXT="\033[0;37m"	# Gris
COLCHOIX="\033[1;33m"	# Jaune
COLDEFAUT="\033[0;33m"	# Brun-jaune
COLSAISIE="\033[1;32m"	# Vert

COLCMD="\033[1;37m"	# Blanc

COLERREUR="\033[1;31m"	# Rouge
COLINFO="\033[0;36m"	# Cyan

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
	while [ "$REPONSE" != "o" -a "$REPONSE" != "O" -a "$REPONSE" != "n" ]
	do
		echo -e "$COLTXT"
		echo -e "Peut-on poursuivre? (${COLCHOIX}O/n${COLTXT}) $COLSAISIE\c"
		read REPONSE
		if [ -z "$REPONSE" ]; then
			REPONSE="o"
		fi
	done

	if [ "$REPONSE" != "o" -a "$REPONSE" != "O" ]; then
		ERREUR "Abandon!"
	fi
}

clear
echo -e "$COLTITRE"
echo "*************************"
echo "* SCRIPT DE REPLICATION *"
echo "* DU LDAP DU LCS / SLIS *"
echo "*  VERS LE LDAP DU SE3  *"
echo "*************************"

echo -e "$COLINFO"
echo "ATTENTION: Ce script nécessite que l'installation SE3 utilise"
echo "           pour unique LDAP celui du LCS ou SLIS."
echo "           Cela signifie que le LDAP du SE3 doit être pour le moment"
echo "           inutilisé."
echo
echo -e "$COLTXT"
echo "       Appuyez sur Entree pour continuer........"
read

echo -e $COLPARTIE
echo "--------"
echo "Partie 1 : Récupération des données"
echo "--------"
echo -e "$COLTXT"

### on suppose que l'on est sous debian ;) ####
WWWPATH="/var/www"

DEBVER=`cat /etc/debian_version`
echo -e "$COLINFO\c"
if [ "$DEBVER" = "3.0" ]; then
	echo "Debian woody détectée."
	SLAPDCONFIN=slapd_ldbm.conf.in
	SMBCONFIN=smb.conf.in
else
	echo "Debian sarge/sid détectée."
	SLAPDCONFIN=slapd_bdb.conf.in
	SMBCONFIN=smb_3.conf.in
	SMBVERSION="samba3"
fi

echo -e "$COLTXT"
echo "Recherche des informations dans $WWWPATH/se3/includes/config.inc.php"

## recuperation des variables necessaires pour interoger mysql ###
echo -e "$COLCMD"
if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
	dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f2 | cut -d \" -f2`
	dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut	-d = -f 2 |cut -d \" -f 2`
 	dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 | cut -d \" -f 2`
 	dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 | cut -d \" -f 2`
else
	ERREUR "Fichier de configuration inaccessible, le script ne peut se poursuivre."

fi

### Verification que le serveur ldap est bien sur se3 et non pas déporté"

IPLDAPMASTER=`echo "SELECT value FROM params WHERE name=\"ldap_server\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
IPSE3=`cat /etc/network/interfaces | grep address | sed -e s/address\ // | cut -f2`
if [ "$IPSE3" != "$IPLDAPMASTER" ] ; then
	### recuperation des parametres actuels de l'annuaire dans la base ####
	#echo "BASEDN=`echo "SELECT value FROM params WHERE name=\"ldap_base_dn\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`"

	BASEDN=`echo "SELECT value FROM params WHERE name=\"ldap_base_dn\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
	ADMINRDN=`echo "SELECT value FROM params WHERE name=\"adminRdn\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
	ADMINPW=`echo "SELECT value FROM params WHERE name=\"adminPw\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
 else
 	ERREUR "Il semble que le LDAP utilisé par votre SE3 soit celui du SE3 lui-même ! "

 fi

## verificiation de la bonne configuration du ldap local au cas ou la section 3 du script
## d'install aurait ete zappee
RDN_LOCAL=`grep rootdn /etc/ldap/slapd.conf | cut -f3 | sed -e "s/\"//g"`
ADMINPW_LOCAL=`grep rootpw /etc/ldap/slapd.conf | cut -f3`

# echo "valeurs trouvees"
# echo $RDN_LOCAL
# echo $ADMINPW_LOCAL
# read

if [ "$RDN_LOCAL" == "$ADMINRDN,$BASEDN" -a "$ADMINPW_LOCAL" == "$ADMINPW" ]; then
	echo -e $COLTXT
	echo "Ldap local configuré correctement par rapport au ldap distant,"
	echo "le script peut se poursuivre."
else
	echo -e $COLTXT
	echo "Le ldap local n'est pas configuré correctement par rapport au ldap distant."
	echo "Sans doute la section 3 a t-elle été passée lors de l'installation de se3."
	echo "Il est indispensable que slapd soit configuré correctement."

	echo "Le script va maintenant configurer ldap en local."
	POURSUIVRE
	SCHEMADIR="\/etc\/ldap\/schema"
	SLAPDCONF='/etc/ldap/slapd.conf'

	# Configuration du slapd
	echo -e "$COLTXT"
	echo -e "Arrêt du serveur LDAP..."
	echo -e "$COLCMD"
	/etc/init.d/slapd stop
	STOPLDAPOK=`ps aux | grep slapd | sed -e '/grep slapd/d'`
	if [ ! -z "$STOPLDAPOK" ]; then
		ERREUR "Le serveur Ldap n'a pas été arrêté correctement, arrêtez-le et relancez le script."
	else
		echo -e "${COLTXT}Le serveur ldap de se3 a été arrêté avec succès."
		LDAPSTOP="yes"
	fi
	echo -e "$COLCMD\c"

	if [ ! -d /var/lib/ldap ]; then
		mkdir /var/lib/ldap
	fi
	#echo "cp -a /var/lib/ldap /var/lib/ldap.se3sav"
	cp -a /var/lib/ldap /var/lib/ldap.se3sav
	SCHEMAD=`echo $SCHEMADIR | sed -e 's/\\\//g'`
	cd /var/cache/se3_install/
	cp -af conf/*.schema $SCHEMAD/
	mv $SLAPDCONF $SLAPDCONF.se3sav
	cat conf/$SLAPDCONFIN | sed -e "s/#SCHEMADIR#/$SCHEMADIR/g" | sed -e "s/#BASEDN#/$BASEDN/g" | sed -e "s/#ADMINRDN#/$ADMINRDN/g" | sed -e "s/#ADMINPW#/$ADMINPW/g" > $SLAPDCONF


	# Prise en compte des lignes spécifiques à la version 2.1.x backportée
	dpkg -s slapd |grep "Version" | grep "2.1." && slapd21="OK"
	if [ ! -z "$slapd21" ]; then
		mv $SLAPDCONF $SLAPDCONF.bak
		echo "allow bind_v2" > $SLAPDCONF
		echo "modulepath /usr/lib/ldap">>$SLAPDCONF
		echo "moduleload back_ldbm" >>$SLAPDCONF
		cat $SLAPDCONF.bak >> $SLAPDCONF
		rm  $SLAPDCONF.bak
	fi
	chmod 640 $SLAPDCONF
	chown root.$LDAPGRP $SLAPDCONF
	/usr/sbin/slapindex 2>/dev/null
	echo "UPDATE params SET value=\"/etc/$SLAPDIR/slapd.conf\" WHERE name=\"path2slapdconf\""|mysql -h $dbhost se3db -u se3db_admin -p$dbpass
fi
#read

ladate=$(date +"%Y.%m.%d-%H.%M.%S")
DOSSIERTMP="/root/export_ldap/$ladate"
mkdir -p $DOSSIERTMP

echo -e "$COLTXT"
echo "Certaines informations sensibles vont maintenant être affichées."
echo "Veillez à ce qu'aucun oeil malicieux ne traine derrière votre dos."
POURSUIVRE

echo -e "$COLTXT"
echo "Voici les informations récupérées de vos fichiers de config:"
echo -e "Base du LDAP:             ${COLINFO}$BASEDN${COLTXT}"
echo -e "Administrateur du LDAP:   ${COLINFO}$ADMINRDN${COLTXT}"
echo -e "Mot de passe de"
echo -e "l'administrateur du LDAP: ${COLINFO}$ADMINPW${COLTXT}"
echo -e "IP du LDAP maitre:        ${COLINFO}$IPLDAPMASTER${COLTXT}"
echo ""
echo "Ces informations vont permettre l'extraction des données du LDAP."


echo -e $COLPARTIE
echo "--------"
echo "Partie 2 : Extraction de l'annuaire distant"
echo "--------"
echo -e "$COLTXT"
POURSUIVRE

#TST_ROOT_PEOPLE=$(ldapsearch -x -LLL -D $ADMINRDN,$BASEDN -w $ADMINPW  uid=root | grep People | cut -d " " -f2)
TST_ROOT_PEOPLE=$(ldapsearch -x -LLL -D $ADMINRDN,$BASEDN -w $ADMINPW  uid=root | grep -i People | cut -d, -f2)
if [ ! -z "$TST_ROOT_PEOPLE" ]; then

	#echo -e "$COLCMD"
	#echo "$TST_ROOT_PEOPLE"
	
	echo -e  "$COLINFO"
	echo -e  "Attention, vous avez un compte root dans la branche People de l'annuaire, \nIl est nécessaire de la supprimer afin d'éviter des dysfonctionnements."
	echo -e "Par sécurité, une sauvegarde préalable de l'annuaire sera effectuée."
	echo -e "$COLCMD"
	ldapsearch -x -LLL -D $ADMINRDN,$BASEDN -w $ADMINPW > $DOSSIERTMP/export_original.ldif || ERREUR "L'exportation LDIF a échoué!"
	echo "ldapdelete -x -D $ADMINRDN,$BASEDN "$TST_ROOT_PEOPLE" -w "$ADMINPW""
	ldapdelete -x -D $ADMINRDN,$BASEDN "uid=root,$TST_ROOT_PEOPLE,$BASEDN" -w "$ADMINPW"
fi

TST_PROFCN=$(ldapsearch -x -LLL -D $ADMINRDN,$BASEDN -w $ADMINPW  cn=profs | grep -v memberUid | grep profs)
if [ ! -z "$TST_PROFCN" ]; then
echo -e "$COLINFO"
echo -e "Attention, vous avez une entrée cn=profs dans votre annuaire à la place de cn=Profs"
echo -e "Il est nécessaire de modifier cette entrée afin d'éviter des dysfonctionnements."
echo -e "$COLCMD"
ldapsearch -x -LLL -D $ADMINRDN,$BASEDN -w $ADMINPW "cn=Profs" | grep -v "memberUid" | sed -e "s/cn: profs/cn: Profs/"> $DOSSIERTMP/profscn.ldif
ldapmodify -x -D $ADMINRDN,$BASEDN -w "$ADMINPW"  -f $DOSSIERTMP/profscn.ldif
fi


#Extraction LDIF
echo -e "$COLCMD"
echo "ldapsearch -x -LLL -D $ADMINRDN,$BASEDN -w $ADMINPW > $DOSSIERTMP/export.ldif"
ldapsearch -x -LLL -D $ADMINRDN,$BASEDN -w $ADMINPW > $DOSSIERTMP/export.ldif || ERREUR "L'exportation LDIF a échoué!"

echo -e "$COLTXT\c"
echo "L'export LDIF a été effectué avec succès."


echo -e $COLPARTIE
echo "--------"
echo "Partie 3 : Importation de l'annuaire extrait sur se3 "
echo "--------"
echo -e "$COLTXT"


POURSUIVRE
#Arrêt du serveur LDAP local si necessaire
if [ "$LDAPSTOP" != "yes" ]; then
	echo -e "$COLTXT"
	echo "Arrêt du serveur LDAP du SE3:"
	echo -e "${COLCMD}"
	/etc/init.d/slapd stop

	STOPLDAPOK=`ps aux | grep slapd | sed -e '/grep slapd/d'`
	if [ ! -z "$STOPLDAPOK" ]; then
		ERREUR "Le serveur Ldap n'a pas été arrêté correctement, arrêtez-le et relancez le script."
	else
		echo -e "${COLTXT}Le serveur ldap de se3 a été arrêté avec succès."
	fi
fi

# sauvegarde et vidage du ldap du se3
mv /var/lib/ldap /var/lib/ldap.sauve_$ladate
mkdir /var/lib/ldap
if [ "$DEBVER" != "3.0" ]; then
cp -a /var/lib/ldap.sauve_${ladate}/DB_CONFIG  /var/lib/ldap
fi

#Import LDIF
echo -e "$COLTXT"
echo "Importation de l'extraction LDIF dans la base LDAP du SE3:"
echo "Cela peut être long si votre annuaire contient beaucoup de données..."
echo -e "$COLCMD"
echo "slapadd -b $BASEDN -l $DOSSIERTMP/export.ldif"
slapadd -b $BASEDN -l $DOSSIERTMP/export.ldif || ERREUR "L'importation du fichier LDIF a échoué!"
slapindex

echo -e "${COLTXT}L'importation LDIF est effectuée."

echo -e "$COLTXT"

echo -e $COLPARTIE
echo "--------"
echo "Partie 4 : Basculement du ldap en mode autonome sur se3 "
echo "--------"
echo -e "$COLTXT"

echo -e "Les deux annuaires sont désormais identiques, on repasse le ldap se3 en mode autonome."
echo -e "$COLTXT"

echo "Arrêt du serveur Samba de SE3:"
echo -e "${COLCMD}\c"
/etc/init.d/samba stop

echo -e "$COLTXT"
echo -e "Mise à jour des fichiers de configuration Ldap et Samba..."
echo -e "${COLCMD}\c"

# mise a jour du parametre mysql
echo "UPDATE params SET value=\"$IPSE3\" WHERE name=\"ldap_server\""|mysql -h $dbhost se3db -u se3db_admin -p$dbpass
# Mise à jour de /etc/ldap/slapd.conf

#
cp -f /etc/ldap/slapd.conf /etc/ldap/slapd.conf.ori
cat /etc/ldap/slapd.conf.ori | sed -e "s/$IPLDAPMASTER/$IPSE3/g" > /etc/ldap/slapd.conf
chmod 600 /etc/ldap/slapd.conf
#
# Mise à jour de /etc/ldap/ldap.conf
#
cp -f /etc/ldap/ldap.conf /etc/ldap/ldap.conf.ori
cat /etc/ldap/ldap.conf.ori | sed -e "s/$IPLDAPMASTER/$IPSE3/g" >/etc/ldap/ldap.conf
chmod 644 /etc/ldap/ldap.conf
#
# Mise à jour de /etc/pam_ldap.conf
#
cp -f /etc/pam_ldap.conf /etc/pam_ldap.conf.ori
cat /etc/pam_ldap.conf.ori | sed -e "s/$IPLDAPMASTER/$IPSE3/g" > /etc/pam_ldap.conf
chmod 644 /etc/pam_ldap.conf
#
# Mise à jour de /etc/libnss-ldap.conf
#
cp -f /etc/libnss-ldap.conf /etc/libnss-ldap.conf.ori
cat /etc/libnss-ldap.conf.ori | sed -e "s/$IPLDAPMASTER/$IPSE3/g"> /etc/libnss-ldap.conf
chmod 644 /etc/libnss-ldap.conf
#
# Mise à jour de /etc/samba/smb.conf
#
cp -f /etc/samba/smb.conf /etc/samba/smb.conf.ori
if [ "$SMBVERSION" = "samba3" ]; then
	cat /etc/samba/smb.conf.ori | sed -e "s/ldap:\/\/$IPLDAPMASTER/ldap:\/\/$IPSE3/g" > /etc/samba/smb.conf
else
	cat /etc/samba/smb.conf.ori | sed -e "s/ldap server = $IPLDAPMASTER/ldapserver = $IPSE3/g" > /etc/samba/smb.conf
fi
chmod 644 /etc/samba/smb.conf

#Redémarrage des service LDAP et Samba du SE3:
echo -e "$COLTXT"
echo "Démarrage des serveurs LDAP et Samba de SE3:"
echo -e "${COLCMD}"
/etc/init.d/slapd start
STARTLDAPOK=`ps aux | grep slapd | sed -e '/grep slapd/d'`
if [ -z "$STARTLDAPOK" ]; then
	ERREUR "Le serveur Ldap n'a pas été relancé correctement."
else
	echo -e "${COLTXT}Le serveur Ldap a été relancé correctement."
fi

echo -e "${COLCMD}"
/etc/init.d/samba start
STARTSMBOK=`ps aux | grep slapd | sed -e '/grep smbd/d'`
if [ -z "$STARTSMBOK" ]; then
	ERREUR "Le serveur Samba n'a pas été relancé correctement."
else
	echo -e "${COLTXT}Le serveur Samba a été relancé correctement."
fi
echo ""
echo -e "$COLTITRE"
echo "/!\ ------- ATTENTION A LIRE ATTENTIVEMENT ------- /!\ "
echo ""
echo "Les annuaires du SLIS / LCS et du SE3 sont maintenant identiques."
echo "Votre annuaire est désormais hébergé sur se3, mais n'est pas encore répliqué."
echo ""
echo "                AVANT TOUTE CRÉATION DE COMPTE,"
echo "ACTIVEZ LA RÉPLICATION DANS LES INTERFACES WEB SE3 ET SLIS / LCS."


echo -e "$COLTITRE"
echo -e "Terminé $COLTXT"

exit 0

