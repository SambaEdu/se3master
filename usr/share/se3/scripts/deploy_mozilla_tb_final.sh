#!/bin/bash


## $Id$ ##


#### script permettant de ventiler le profil de thunderbird ####

# Si un profil thunderbird a été crée par un utilisateur,
# celui ci sera ignoré par le script.
# franck molle 03/2005
# le script permet desormais de redeployer les profils sans pour autant ecraser s'ils ont ete modifies par l'utilisateur.
# sauf à passer en parametre "force_delete"

# Modifs Stephane Boireau: 11/03/2006
chemin_html="/var/www/se3/tmp"

#Couleurs
# COLTITRE="\033[1;35m"	# Rose
# COLPARTIE="\033[1;34m"	# Bleu
#
# COLTXT="\033[0;37m"	# Gris
# COLCHOIX="\033[1;33m"	# Jaune
# COLDEFAUT="\033[0;33m"	# Brun-jaune
# COLSAISIE="\033[1;32m"	# Vert
#
# COLCMD="\033[1;37m"	# Blanc
#
# COLERREUR="\033[1;31m"	# Rouge
# COLINFO="\033[0;36m"	# Cyan

ERREUR()
{
	echo "$COLERREUR"
	echo "ERREUR!"
	echo "$1"
	echo ""
	exit 1
}
#echo "Géneration des profils de Mozilla Thunderbird<br>"
#echo "<br>"
# echo -e "Les profils deja existants seront ignorés"
# echo -e ""


AIDE()
{
	echo "Permet de déployer les profils Mozilla Thunderbird sans les répertoires personnels"
	echo "Usage : deploy_mozilla_tb_final.sh option1 option2 option3 option4"
	echo "option1 et option2 sont obligatoires alors que option3 et  option4 sont facultatives"
	echo "option1 :  le nom du groupe des utilisateurs à générer, all pour tous les utilisateurs de l'annuaire"
	echo "option2 : force_move afin de régénérer les profils existants en sauvegardant, no_move pour ne rien toucher"
	echo "option3 : nom du serveur pop "
	echo "option4 : nom du serveur smtp"
	echo "--help ou -h cette aide"

}

if [ "$1"  "--help" -o "$1"  "-h" -o "$1"  "" ]
then
AIDE
fi


if [ -z "$1" ]; then
	ERREUR "$0 prend au moins en arguments la liste des utilisateurs � g�n�rer, all pour tous les homes"
fi

OPTION="$2"

if [ "$OPTION" != "force_move" -o "$OPTION"  "no_move" ]
then
AIDE
fi

POP_SERVEUR="$3"
SMTP_SERVEUR="$4"


WWWPATH="/var/www"
LADATE=$(date +%D_%Hh%M | sed -e "s!/!_!g")


if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
        dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
        dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
        dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
        dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`

else
	ERREUR "Impossible de trouver $WWWPATH/se3/includes/config.inc.php"
fi

path2UserSkel=`echo "SELECT value FROM params WHERE name='path2UserSkel'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$path2UserSkel" ]; then
	path2UserSkel="/etc/skel/user"
fi
lcsIp=`echo "SELECT value FROM params WHERE name='lcsIp'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`

#Seuls les homes deja existants seront complétés
# Les compte crees par les utilisateurs seront ignorés
CHEMIN_TB_SOURCE="${path2UserSkel}/profil/appdata/Thunderbird"

if [ "$1"  "all" ]; then
	list=$(ls /home | grep -v netlogon | grep -v templates)
else


### recuperation des parametres actuels de l'annuaire dans la base ####
	BASEDN=`echo "SELECT value FROM params WHERE name=\"ldap_base_dn\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`

	TST_GRP=$(ldapsearch -xLLL cn=$1 -b $BASEDN | grep member)

	if [ -z "$TST_GRP" ]; then
	TST_UID=$(ldapsearch -xLLL uid="$1")
		if [ -z "$TST_UID" ]; then
			ERREUR "Impossible de trouver le groupe ou l'utilisateur passé en paramètre dans l'annuaire Ldap"
		else
			list=$1
		fi
	else
		TST_GRP_POSIX=$(ldapsearch -xLLL "cn=$1" | grep memberUid)
		if [ -z "$TST_GRP_POSIX" ]; then
			list=$(ldapsearch -x -LLL cn=$1 -b $BASEDN | grep uid | cut -d " " -f2 |  cut -d "=" -f2 | cut -d "," -f1)
		else
			list=$(ldapsearch -x -LLL "cn=$1" | grep memberUid | cut -d " " -f2)
		fi
	fi

fi

# echo "$list"
# read




#======================================================
# Nombre de dossiers à traiter:
nbdossiers=$(echo "$list" | wc -l)
compteur=1

mkdir -p $chemin_html
chown www-se3 $chemin_html

echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
<html>
<head>
<meta http-equiv=\"refresh\" content=\"2\">
<title>Traitement des profils</title>
</head>
<body>
<h1 align=\"center\">Traitement des profils</h1>
<p align=\"center\">Le traitement va démarrer...<br></p>
</body>
</html>" > $chemin_html/recopie_profils_thunderbird.html
chmod 755 $chemin_html/recopie_profils_thunderbird.html
chown www-se3 $chemin_html/recopie_profils_thunderbird.html
#======================================================

for user in $list
# echo "$list" | while read user
# ls /home | grep -v netlogon | grep -v templates | while read user
do
	if [ ! -e /home/$user ]; then
	echo ""
# 	echo "/home/<A HREF="/annu/people.php?uid=$user">$user</A> n'existe pas"
	else

		MAIL=$(ldapsearch -xLLL "uid=$user" | grep mail | cut -d " " -f2)
		PRENOM=$(ldapsearch -xLLL "uid=$user" | grep gecos | cut -d " " -f2)
		NOM=$(ldapsearch -xLLL "uid=$user" | grep gecos | cut -d " " -f3 | cut -d "," -f1)
		DOMNAME=$(ldapsearch -xLLL "uid=$user" | grep mail | cut -d " " -f2 | cut -d "@" -f2)

		PREF_JS="/home/$user/profil/appdata/Thunderbird/Profiles/default/prefs.js"
		if [ -z "$lcsIp" ]; then
			PREF_JS_TMP="/home/$user/profil/appdata/Thunderbird/Profiles/default/prefs.js.slis"
		else
			PREF_JS_TMP="/home/$user/profil/appdata/Thunderbird/Profiles/default/prefs.js.lcs"
		fi

		CHEMIN_CIBLE="/home/$user/profil/appdata/Thunderbird/Profiles/default"
		CHEMIN_TB_CIBLE="/home/$user/profil/appdata/Thunderbird"
		FICHIER_PROFILES="${CHEMIN_TB_CIBLE}/profiles.ini"



		#echo "<br>"
		echo "Traitement de <A HREF="/annu/people.php?uid=$user">$user</A> <br>"

		#===================================================
		echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
<html>
<head>
<meta http-equiv=\"refresh\" content=\"2\">
<title>Traitement des profils</title>
</head>
<body>
<h1 align=\"center\">Traitement des profils</h1>
<p align=\"center\">Traitement de $user...<br>($compteur/$nbdossiers)</p>
</body>
</html>" > $chemin_html/recopie_profils_thunderbird.html
		#===================================================

		if [ ! -e $CHEMIN_TB_CIBLE ]; then
				echo "Le profil TB de <A HREF="/annu/people.php?uid=$user">$user</A> n'existe pas, je le cr�e avec les param�tres :"
				echo "$MAIL"
				cp -a $CHEMIN_TB_SOURCE $CHEMIN_TB_CIBLE

				# Personalisation du profil
			[ -z "$POP_SERVEUR" ] && POP_SERVEUR="$DOMNAME"
			[ -z "$SMTP_SERVEUR" ]&& SMTP_SERVEUR="$DOMNAME"
				cat $PREF_JS_TMP \
					| sed -e "s/nom_compte_replace@domaine/$MAIL/g" \
					| sed -e "s/nom_compte_replace/$user/g" \
					| sed -e "s/domaine/$DOMNAME/g" \
					| sed -e "s/pop.replace.fr/$POP_SERVEUR/g" \
					| sed -e "s/smtp.replace.fr/$SMTP_SERVEUR/g" \
					| sed -e "s/votre_nom_replace/$PRENOM\ $NOM/g" \
					| sed -e "s/nom_serveur_replace/$lcsIp/g" \
					| sed -e "s/login_replace/$user/g" >  $PREF_JS
				## correction des droits##
				chown -R $user:admins $CHEMIN_TB_CIBLE > /dev/null 2>&1
				chmod -R 700 $CHEMIN_TB_CIBLE > /dev/null 2>&1


		else
				echo "Le profil Mozilla Thunderbird existe déjà.<br>"
# 				echo "<br>"
				#echo "Traitement de <A HREF="/annu/people.php?uid=$user">$user</A> <br>"

				if [ -e $FICHIER_PROFILES ]; then
					TYPE_PROFILE=$(grep 'Path=Profiles/default' "$FICHIER_PROFILES")
					if [ ! -z $TYPE_PROFILE ]; then
					#echo "C un profil type automatique"
					NB_COMPTES_MAIL=$(ls $CHEMIN_CIBLE/Mail | grep -v "defaultbal" | grep -v "Local Folders")

					if [ -z "$NB_COMPTES_MAIL" ]; then
		# 				echo "test 1 pass� pas de compte suppl�mentaire"

# 						
						if [ ! -s $CHEMIN_CIBLE/Mail/defaultbal/Inbox ]; then
		# 				echo "le compte est a zero, donc on peut le r�g�n�rer"
						PROFIL_DEL="ok"

						else
						echo "le profil Thunderbird contient des données.<br>"
						fi

					else
						echo "Le profil Thunderbird a été modifié par l'utilisateur.<br>"
					fi



					else
					echo "Le profil Thunderbird est un profil personnel.<br>"
					fi
				fi

				if [ "$PROFIL_DEL"  "ok" ]; then
					rm -rf $CHEMIN_TB_CIBLE
				fi

				if [ "$OPTION"  "force_move" -a "$PROFIL_DEL" != "ok" ]; then
					echo "<font color=red>On écrase le profil existant mais on sauvegarde sur ${CHEMIN_TB_CIBLE}_sauve_${LADATE} </font><br>"
 					mv $CHEMIN_TB_CIBLE ${CHEMIN_TB_CIBLE}_sauve_${LADATE}
				fi
notifier
				if [ "$PROFIL_DEL"  "ok" -o "$OPTION"  "force_move" ]; then
						echo "Le profil TB de <A HREF="/annu/people.php?uid=$user">$user</A> est régénéré avec les paramètres $MAIL <br>"
						#echo ""

						cp -a $CHEMIN_TB_SOURCE $CHEMIN_TB_CIBLE

						# Personalisation du profil
						cat $PREF_JS_TMP \
							| sed -e "s/nom_compte_replace@domaine/$MAIL/g" \
							| sed -e "s/nom_compte_replace/$user/g" \
							| sed -e "s/domaine/$DOMNAME/g" \
							| sed -e "s/pop.replace.fr/$DOMNAME/g" \
							| sed -e "s/smtp.replace.fr/$DOMNAME/g" \
							| sed -e "s/votre_nom_replace/$PRENOM\ $NOM/g" \
							| sed -e "s/nom_serveur_replace/$lcsIp/g" \
							| sed -e "s/login_replace/$user/g" >  $PREF_JS
						## correction des droits##
						chown -R $user:admins $CHEMIN_TB_CIBLE > /dev/null 2>&1
						chmod -R 700 $CHEMIN_TB_CIBLE > /dev/null 2>&1
				else
					echo "Le profil a été conservé en l'état.<br>"
				fi
		echo "<br>"
		fi
	fi

	#============================================
	compteur=$(($compteur+1))
	#============================================
done
#============================================
echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
<html>
<head>
<title>Traitement des profils</title>
</head>
<body>
<h1 align=\"center\">Traitement des profils</h1>
<p align=\"center\">Traitement terminé !</p>
</body>
</html>" > $chemin_html/recopie_profils_thunderbird.html
#============================================
exit 0

