#!/bin/sh
#
## $Id$ ##
#
##### Permet de faire la mise à jour de debian et se3 #####

# Franck Molle - 05/2006...
if [ "$1" = "--help" -o "$1" = "-h" ]
then
        echo "Script permettant la mise a jour du système debian et se3"
        echo "Usage : sans option pour un mode intéractif ou avec --auto pour le mode muet"
        exit
fi	


REPORT_FILE="/root/mailtoadmin"
echo "" > $REPORT_FILE
LADATE=$(date +%x)

debug="0" #desactivation debug si =0
MAIL_REPORT()
{
[ -e /etc/ssmtp/ssmtp.conf ] && MAIL_ADMIN=$(cat /etc/ssmtp/ssmtp.conf | grep root | cut -d= -f2)
if [ ! -z "$MAIL_ADMIN" ]; then
        REPORT=$(cat $REPORT_FILE)
        #On envoie un mail à l'admin
	echo "$REPORT"  | mail -s "[SE3] Résultat de $0" $MAIL_ADMIN
fi
}

LINE_TEST()
{
ping -c1  www.google.fr >/dev/null
if [ "$?" != "0" ]; then
        echo "Votre connexion internet ne semble pas fonctionnelle !!" | tee -a $REPORT_FILE
        MAIL_REPORT
        exit 1
fi
}

echo "<pre>"
clear
echo "************************************"
echo "* SCRIPT DE MISE A JOUR SYSTEME    *"
echo "************************************"
echo 


if [ "$1" == "--auto" ] 
then 
        ### mode auto : on installe les deps, on repond oui aux questions, on rend debconf silencieux et on lance un permse3 en mode rapide ###

        option="-y"
        PERMSE3_OPTION="--light"
        DEBIAN_PRIORITY="critical"
        DEBIAN_FRONTEND="noninteractive"
        export  DEBIAN_FRONTEND
else
        ### mode interactif debconf est moins bavard mais pas muet permse3 sera plus precis mais plus long aussi ###

        DEBIAN_PRIORITY="critical"
        PERMSE3_OPTION="--full"
fi

export  DEBIAN_PRIORITY
[ "$debug" != "1" ] && apt-get clean
USE_SPACE=$(df -h | grep "/var$" | awk '{print $5}' | sed -e s/%//)
if [ "$USE_SPACE" -le 90 ]; then
        echo "Résultat de la demande de mise à jour système du $LADATE :" > $REPORT_FILE
        echo "" >> $REPORT_FILE
        echo "Mise à jour de la liste des paquets disponibles ....." | tee -a $REPORT_FILE
        LINE_TEST
        apt-get update | tee -a $REPORT_FILE
        echo "" | tee -a $REPORT_FILE
        echo "Mise a jour des paquets optionnels à se3 si necessaire" | tee -a $REPORT_FILE
        dpkg -s se3-clamav | grep "Status: install" >/dev/null && apt-get install se3-clamav $option | tee -a $REPORT_FILE
        dpkg -s se3-dhcp | grep "Status: install" >/dev/null && apt-get install se3-dhcp $option | tee -a $REPORT_FILE
        dpkg -s se3-clonage | grep "Status: install" >/dev/null && apt-get install se3-clonage $option | tee -a $REPORT_FILE
        
# 	if [ -e /etc/clamav/freshclam.conf ]; then
# 		mv /etc/clamav/freshclam.conf /etc/clamav/freshclam.conf_sav_se3_$LADATE
# 		apt-get install clamav-freshclam $option
# 		mv /etc/clamav/freshclam.conf_sav_se3_$LADATE /etc/clamav/freshclam.conf 
# 	fi	
# 
	#upgrade se3
	apt-get install se3 $option | tee -a $REPORT_FILE

        #upgrade samba et relancement si maj
# 	TST_SMBMAJ=$(apt-get -s install samba $option | grep "la plus récente version disponible")
	apt-get install samba $option | tee -a $REPORT_FILE
# 	[ -z "$TST_SMBMAJ" ] && /etc/init.d/samba restart | tee -a $REPORT_FILE
	
#upgrade reste du système
	apt-get dist-upgrade $option | tee -a $REPORT_FILE
        echo "" | tee -a $REPORT_FILE
        echo "Correction de droits si besoin...." | tee -a $REPORT_FILE
        /usr/share/se3/sbin/permse3 $PERMSE3_OPTION | tee -a $REPORT_FILE
        
        echo "Correction de backuppc si besoin...." | tee -a $REPORT_FILE
        [ -e /var/cache/se3_install/maj/wawabpcfix.tgz ] && tar -zxf /var/cache/se3_install/maj/wawabpcfix.tgz -C / | tee -a $REPORT_FILE
        echo "" | tee -a $REPORT_FILE

        # teste si apache a besoin d'etre relancé
	if [ -z "$(ps aux | grep "apache2se" | grep -v grep)" ]; then
                echo "Redémarrage d'Apachese" | tee -a $REPORT_FILE
                /etc/init.d/apache2se start | tee -a $REPORT_FILE
                
        fi

### Rajout d'Eric Elter après constatation de l'arrêt des deux services en question après le lancement de ce script

	# teste si samba a besoin d'etre relancé
       if [ -z "$(ps aux | grep "smbd" | grep -v grep)" ]; then
               echo "Redémarrage de Samba" | tee -a $REPORT_FILE
               /etc/init.d/samba start | tee -a $REPORT_FILE

       fi

   	# teste si mysql a besoin d'etre relancé
       if [ -z "$(ps aux | grep "mysqld" | grep -v grep)" ]; then
               echo "Redémarrage de MySQL" | tee -a $REPORT_FILE
               /etc/init.d/mysql start | tee -a $REPORT_FILE

       fi 

### Fin de rajout

        echo "Mise à jour terminée" | tee -a $REPORT_FILE
else
        echo -e "Attention : Mise à jour système impossible :(\nEspace insuffisant sur la partition /var, il reste moins de 10% d'espace libre." | tee -a $REPORT_FILE
fi
echo "</pre>"
MAIL_REPORT

DEBIAN_PRIORITY="high"
DEBIAN_FRONTEND="dialog" 
export  DEBIAN_PRIORITY
export  DEBIAN_FRONTEND
mv $REPORT_FILE /var/log/update_system$LADATE.log
exit 0