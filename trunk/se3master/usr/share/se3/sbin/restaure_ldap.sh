#!/bin/bash

# Script destin� � restaurer une sauvegarde de l'annuaire LDAP.
# Auteur: Stephane Boireau
# Derni�re modification: 22/01/2008

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

ERREUR()
{
        echo -e "$COLERREUR"
        echo "ERREUR!"
        echo -e "$1"
        echo -e "$COLTXT"
        exit 0
}

POURSUIVRE()
{
        REPONSE=""
        while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
        do
                echo -e "$COLTXT"
                echo -e "Peut-on poursuivre? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c"
                read REPONSE
        done

        if [ "$REPONSE" != "o" ]; then
                ERREUR "Abandon!"
        fi
}

POURSUIVRE_OU_CORRIGER()
{
	REPONSE=""
	while [ "$REPONSE" != "1" -a "$REPONSE" != "2" ]
	do
		if [ ! -z "$1" ]; then
			echo -e "$COLTXT"
			echo -e "Peut-on poursuivre (${COLCHOIX}1${COLTXT}) ou voulez-vous corriger (${COLCHOIX}2${COLTXT}) ? [${COLDEFAUT}${1}${COLTXT}] $COLSAISIE\c"
			read REPONSE

			if [ -z "$REPONSE" ]; then
				REPONSE="$1"
			fi
		else
			echo -e "$COLTXT"
			echo -e "Peut-on poursuivre (${COLCHOIX}1${COLTXT}) ou voulez-vous corriger (${COLCHOIX}2${COLTXT}) ? $COLSAISIE\c"
			read REPONSE
		fi
	done
}

. /usr/share/se3/includes/config.inc.sh -lm

BASEDN="$ldap_base_dn"
ADMINRDN="$adminRdn"
ADMINPW="$adminPw"
PEOPLERDN="$peopleRdn"
GROUPSRDN="$groupsRdn"
RIGHTSRDN="$rightsRdn"

PEOPLER=`echo $PEOPLERDN |cut -d = -f 2`
RIGHTSR=`echo $RIGHTSRDN |cut -d = -f 2`
GROUPSR=`echo $GROUPSRDN |cut -d = -f 2`



dossier=/var/se3/save/ldap
mkdir -p ${dossier}

ladate=$(date "+%Y%m%d.%H%M%S")

# Sauvegarde pr�alable
echo -e "$COLTXT"
echo "Sauvegarde pr�alable de l'annuaire dans son �tat actuel..."
echo -e "$COLCMD\c"
ldapsearch -xLLL -D "$ADMINRDN,$BASEDN" -w $ADMINPW > $dossier/svg_${ladate}.ldif

if [ "$?" = "0" ]; then
	echo -e "$COLTXT"
	echo "Sauvegarde r�ussie."
else
	echo -e "$COLERREUR"
	echo "Echec de la sauvegarde pr�alable."
	echo "Il n'est pas tr�s raisonnable de poursuivre sans disposer"
	echo "d'une sauvegarde r�cente."

	REPONSE=""
	while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
	do
		echo -e "$COLTXT"
		echo -e "Voulez-vous continuer n�anmoins? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c"
		read REPONSE
	done

	if [ "$REPONSE" = "n" ]; then
		echo -e "$COLERREUR"
		echo "Abandon."
		echo -e "$COLTXT"
		exit
	else
		REPONSE=""
		while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
		do
			echo -e "$COLTXT"
			echo -e "Etes vous s�r de vouloir continuer? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c"
			read REPONSE
		done
	fi

	if [ "$REPONSE" = "n" ]; then
		echo -e "$COLERREUR"
		echo "Abandon."
		echo -e "$COLTXT"
		exit
	fi
fi

echo -e "$COLTXT"
echo "Choix de la sauvegarde � restaurer"
cd $dossier
echo -e "Les sauvegardes pr�sentes dans le dossier ${COLINFO}${dossier}${COLTXT} sont:"
echo -e "$COLCMD\c"
ls -lht *.ldif

echo -e "$COLTXT"
echo "Vous pouvez aussi choisir un fichier LDIF situ�"
echo "ailleurs dans l'aborescence."

echo -e "$COLTXT"
echo "Quelle sauvegarde souhaitez-vous restaurer?"

REPONSE=""
while [ "$REPONSE" != "1" ]
do
	echo -e "${COLTXT}Votre choix: $COLSAISIE\c"
	read -e SVG

	if [ ! -e "$SVG" ]; then
		echo -e "$COLERREUR"
		echo -e "Le fichier ${COLINFO}${SVG}${COLERREUR} n'existe pas."

		REPONSE=2
	else
		echo -e "$COLTXT"
		echo "Vous avez choisi:"
		echo -e "$COLCMD\c"
		ls -lh $SVG

		POURSUIVRE_OU_CORRIGER
	fi
done

#v=$(df -h | grep /var | grep -v "/var/se3" | sed -e "s/ \{2,\}/ /g" | cut -d" " -f5 | sed -e "s/%//")
#if [ $v -gt 50 ]; then
#
#else
#
#fi

REP=""
while [ -z "$REP" ]
do
	echo -e "$COLTXT"
	echo "Arr�t du serveur d'annuaire..."
	/etc/init.d/slapd stop
	sleep 2
	test=$(ps aux | grep slapd | grep -v grep)
	if [ ! -z "$test" ]; then
		echo -e "$COLERREUR"
		echo "L'arr�t du serveur a �chou�."
		echo "Il reste au moins un processus slapd:"
		echo -e "$COLCMD\c"
		ps aux | grep slapd | grep -v grep

		REPONSE=""
		while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
		do
			echo -e "$COLTXT"
			echo -e "Voulez-vous r�essayer d'arr�ter slapd? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c"
			read REPONSE
		done

		if [ "$REPONSE" = "n" ]; then
			echo -e "$COLERREUR"
			echo "Abandon."
			exit 1
		fi
	else
		REP="OK"
	fi
done

echo -e "$COLTXT"
echo "Pr�paration de la nouvelle arborescence /var/lib/ldap"
echo -e "$COLCMD\c"
mv /var/lib/ldap /var/lib/ldap.${ladate}
mkdir /var/lib/ldap
cp /var/lib/ldap.${ladate}/DB_CONFIG /var/lib/ldap/

echo -e "$COLTXT"
echo -e "Restauration de la sauvegarde ${COLINFO}$SVG"
echo -e "$COLCMD\c"
slapadd -c -l $SVG


if [ "$?" = "0" ]; then
	echo -e "$COLTXT"
	echo "La commande a semble-t-il r�ussi."
	# Droits sur /var/lib/ldap
# 	[ -z $(grep "3.1" /etc/debian_version) ] && chown -R openldap.openldap /var/lib/ldap
	echo -e "$COLTXT"
	echo -e "Red�marrage du serveur d'annuaire LDAP"
	echo -e "$COLCMD\c"
	/etc/init.d/slapd start
	test=$(ps aux | grep slapd | grep -v grep)
	if [ -z "$test" ]; then
		echo -e "$COLERREUR"
		echo "Le red�marrage du service slapd a �chou�."

		echo -e "$COLTXT"
		echo "Vous devrez red�marrer manuellement le service par:"
		echo -e "$COLCMD\c"
		echo "   /etc/init.d/slapd start"
	else
		echo -e "$COLTXT"
		echo "R�d�marrage du service slapd r�ussi."
	fi
	  
	echo -e "$COLTXT"
	echo "Apr�s red�marrage du LDAP, red�marrez si n�cessaire les services samba"
	echo "et apache2se."
	
else
	echo -e "$COLERREUR"
	echo "La commande a renvoy� un code d'erreur."
# 	[ -z $(grep "3.1" /etc/debian_version) ] && chown -R openldap.openldap /var/lib/ldap
	REPONSE=""
	while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
	do
		echo -e "$COLTXT"
		echo -e "Voulez-vous remettre en place l'arborescence pr�c�dente? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c"
		read REPONSE
	done

	if [ "$REPONSE" = "o" ]; then
		echo -e "$COLTXT"
		echo -e "R�tablissement de la version ant�rieure..."
		echo -e "$COLCMD\c"
		rm -fr /var/lib/ldap
		mv /var/lib/ldap.${ladate} /var/lib/ldap

		echo -e "$COLTXT"
		echo -e "Red�marrage du serveur d'annuaire LDAP"
		echo -e "$COLCMD\c"
		/etc/init.d/slapd start
		test=$(ps aux | grep slapd | grep -v grep)
		if [ -z "$test" ]; then
			echo -e "$COLERREUR"
			echo "Le red�marrage du service slapd a �chou�."

			echo -e "$COLTXT"
			echo "Vous devrez red�marrer manuellement le service par:"
			echo -e "$COLCMD\c"
			echo "   /etc/init.d/slapd start"
		else
			echo -e "$COLTXT"
			echo "R�d�marrage du service slapd r�ussi."
		fi
		echo -e "$COLTXT"
		echo "Apr�s red�marrage du LDAP, red�marrez si n�cessaire les services samba"
		echo "et apache2se."
		exit 1
	else
		echo -e "$COLERREUR"
		echo "L'op�ration a �chou�."
		echo "Le serveur d'annuaire LDAP n'a pas �t� red�marr�."
		echo -e "$COLTXT"
		exit 1
	fi

fi

DOMAINSID=`net getlocalsid | cut -d: -f2 | sed -e "s/ //g"`
#Change le SambaPrimaryGroupe
echo "Modification du SambaPrimaryGroupe en arriere plan dans 2mn"
AT_SCRIPT=/root/modif_SambaPrimaryGroupe.sh
echo "#!/bin/bash
ldapsearch -x -b $PEOPLERDN,$BASEDN '(objectclass=*)' uid | grep -v People | grep -v \# | grep uid: | cut -d\" \" -f2 | while read ID
do
ldapmodify -x -v -D "$ADMINRDN,$BASEDN" -w "$ADMINPW" <<EOF
dn: uid=\$ID,$PEOPLERDN,$BASEDN
changetype: modify
replace: sambaPrimaryGroupSID
sambaPrimaryGroupSID: $DOMAINSID-513
EOF
done
" >$AT_SCRIPT
chmod 700 $AT_SCRIPT
at now +2 minutes -f $AT_SCRIPT >/dev/null

#rm -rf /var/lib/ldap
