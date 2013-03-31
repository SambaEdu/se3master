#!/bin/bash

# $Id$

# Script d'extinction par l'onduleur
# pour remplacer la ligne
#    SHUTDOWNCMD "/sbin/shutdown -h +1"
# de /etc/nut/upsmon.conf
# en
#    SHUTDOWNCMD "<CHEMIN>/se3_log_extinction.sh"

ladate=$(date +%Y%m%d%H%M%S)
date_formatee=$(date +"%A %d/%m/%Y à %H:%M:%S")

doss_log=/var/log
fich_log=${doss_log}/rapport_extinction_par_onduleur.log

fich_mail_extinction=/root/tmp/mail_extinction_${ladate}.txt

# Log de l'extinction:
echo "${ladate} : Extinction le ${date_formatee}" >> ${fich_log}

# Préparation du mail d'alerte:
echo "Extinction le ${date_formatee}" > ${fich_mail_extinction}
mail_admin=$(ldapsearch -xLLL uid=admin mail | grep "^mail: " | sed -e "s/^mail: //")
mail_ssmtp=$(grep "^root=" /etc/ssmtp/ssmtp.conf | cut -d"=" -f2)
if [ ! -z "$mail_admin" ]; then
        mail $mail_admin -s "[Serveur SE3] Extinction par onduleur" < ${fich_mail_extinction}
fi
if [ ! -z "$mail_ssmtp" ]; then
        mail $mail_ssmtp -s "[Serveur SE3] Extinction par onduleur" < ${fich_mail_extinction}
fi
rm ${fich_mail_extinction}

# Extinction d'une autre machine, si jamais sur la machine distante, une clé PUB sans mot de passe du SE3 est insérée dans le /root/.ssh/authorized_keys de ${bcdibox}
#bcdibox=10.127.164.3
#ssh root@${bcdibox} /sbin/halt
#echo "${ladate} : Extinction de ${bcdibox} le ${date_formatee}" >> ${fich_log}

# Extinction:
/sbin/shutdown -h +1
#echo /sbin/shutdown -h +1
