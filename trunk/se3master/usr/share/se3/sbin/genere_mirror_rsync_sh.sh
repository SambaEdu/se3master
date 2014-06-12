#!/bin/bash

#/usr/share/se3/sbin/genere_mirror_rsync_sh.sh
#
## $Id$ ##
#
##### Generation du /mirror/mirror_rsync.sh d'apres les parametres mis en place par /usr/share/se3/sbin/mirroring_mise_en_place.sh #####
# Auteur: Stephane Boireau (Animateur TICE du secteur de Bernay/Pont-Audemer (27))
# Date:   07/07/2008

if [ "$1" = "-h" -o "$1" = "--help" -o ! -e /mirror/param_mirror_rsync.sh ]; then
	echo "USAGE: Ce script permet de lire le fichier de parametres"
	echo "          /mirror/param_mirror_rsync.sh"
	echo "       pour generer le script"
	echo "          /mirror/mirror_rsync.sh"
	echo "       qui est ensuite lance via la crontab."
	echo "       Ce script est normalement appele par le script"
	echo "          /usr/share/se3/sbin/mirroring_mise_en_place.sh"
	echo "       On peut neanmoins le lancer apres modification manuelle"
	echo "       du fichier de parametres"
	echo "          /mirror/param_mirror_rsync.sh"
	exit
fi

source /mirror/param_mirror_rsync.sh

ladate=$(date "+%Y%m%d-%H%M%S")
if [ -e /mirror/mirror_rsync.sh ]; then
	mv /mirror/mirror_rsync.sh /mirror/mirror_rsync_${ladate}.sh
fi

touch /mirror/mirror_rsync.sh
chmod 700 /mirror/mirror_rsync.sh

echo "#!/bin/bash

source /mirror/param_mirror_rsync.sh

FORMATE_DUREE() {
	h=\$((\$1/3600))
	m=\$(((\$1-(\$h*3600))/60))
	s=\$((\$1-(\$h*3600)-(\$m*60)))

	if [ \$h -le 9 ]; then
		h=0\$h
	fi
	if [ \$m -le 9 ]; then
		m=0\$m
	fi
	if [ \$s -le 9 ]; then
		s=0\$s
	fi
	echo \"\$h:\$m:\$s\"
}



/mirror/umount_DISK2.sh 2>/dev/null

date_rsync=\$(date +%a%Hh%M)
FICHIERLOG=\"/mirror/log_rsync_\${date_rsync}\"
touch \$FICHIERLOG

echo \"fichier de log du \$(date)\" > \$FICHIERLOG

t0=\$(date +%s)
t1=\$t0
#==================================
# partition /
#echo 'Controle de la partition Racine' | tee \$FICHIERLOG
#e2fsck \$PARTROOT_CIBLE
#echo '' | tee -a \$FICHIERLOG

echo 'Montage de la partition Racine' | tee -a \$FICHIERLOG
mount -t ext3 \$PARTROOT_CIBLE  /mirror/part_root
if [ \$? != 0 ]; then
	echo \"** ERREUR ** lors du montage de la partition / de \${DISK2}\"  | tee -a \$FICHIERLOG
	echo 'Le script ne peut se poursuivre' | tee -a \$FICHIERLOG
	echo \"VeRIFIEZ LE BON FONCTIONNEMENT DU \${DISK2}\" | tee -a \$FICHIERLOG
	touch /mirror/mail_alerte.txt
	echo \"Rapport de fonctionnement du script de mirroring lors de son lancement\" > /mirror/mail_alerte.txt
	echo \"Voici les problemes constates\" >> /mirror/mail_alerte.txt
	echo \"\" >> /mirror/mail_alerte.txt
	cat \$FICHIERLOG >> /mirror/mail_alerte.txt
	mail \$MAIL_ADMIN -s \"[Alerte /mirror/mirror_rsync.sh] Pb avec le script\" < /mirror/mail_alerte.txt
	exit 1
fi
echo '' | tee -a \$FICHIERLOG

echo 'rsync de la partition Racine' | tee -a \$FICHIERLOG
echo  | tee -a \$FICHIERLOG
/usr/bin/rsync -av --delete --exclude=/etc/fstab --exclude=/home/* --exclude=/mirror/ --exclude=/tmp/* --exclude=/var/lock/* --exclude=/proc/* --exclude=/sys/* --exclude=/cdrom/* --exclude=/var/*  / /mirror/part_root | tee -a \$FICHIERLOG
echo '' | tee -a \$FICHIERLOG


sed \"s#\$PARTROOTUUID#\$PARTROOTUUID_CIBLE#\" -i /mirror/part_root/boot/grub/grub.cfg  

umount \$PARTROOT_CIBLE

t2=\$(date +%s)
duree=\$((t2-t1))
echo \"Duree du mirroring de la racine: \${duree} secondes soit \$(FORMATE_DUREE \${duree})\" | tee -a \$FICHIERLOG
echo '' | tee -a \$FICHIERLOG

t1=\$t2
#==================================

#partition /var 
echo 'Montage de la partition /VAR' | tee -a \$FICHIERLOG
mount -t ext3 \$PARTVAR_CIBLE /mirror/part_var

if [ \$? != 0 ]; then
	echo \"** ERREUR ** lors du montage de la partition /var de \${DISK2}\"  | tee -a \$FICHIERLOG
	echo 'Le script ne peut se poursuivre' | tee -a \$FICHIERLOG
	echo \"VeRIFIEZ LE BON FONCTIONNEMENT DU \${DISK2}\" | tee -a \$FICHIERLOG
	touch /mirror/mail_alerte.txt
	echo \"Rapport de fonctionnement du script de mirroring lors de son lancement\" > /mirror/mail_alerte.txt
	echo \"Voici les problemes constates\" >> /mirror/mail_alerte.txt
	echo \"\" >> /mirror/mail_alerte.txt
	cat \$FICHIERLOG >> /mirror/mail_alerte.txt
	mail \$MAIL_ADMIN -s \"[Alerte /mirror/mirror_rsync.sh] Pb avec le script\" < /mirror/mail_alerte.txt
	exit 1
fi
echo '' | tee -a \$FICHIERLOG

echo 'Arret de ldap, mysql et cron' | tee -a \$FICHIERLOG
/etc/init.d/slapd stop
/etc/init.d/mysql stop
/etc/init.d/cron stop
echo '' | tee -a \$FICHIERLOG

echo 'rsync de la partition /var' | tee -a \$FICHIERLOG
echo '' | tee -a \$FICHIERLOG
/usr/bin/rsync -av --delete --exclude=/lock/*  --exclude=/lib/backuppc/* --exclude=/se3/* /var/ /mirror/part_var/ | tee -a \$FICHIERLOG
umount \$PARTVAR_CIBLE

echo '' | tee -a \$FICHIERLOG
echo 'Redemarrage  de ldap, mysql et cron' | tee -a \$FICHIERLOG
/etc/init.d/slapd start
/etc/init.d/mysql start
/etc/init.d/cron start
echo '' | tee -a \$FICHIERLOG

t2=\$(date +%s)
duree=\$((t2-t1))
echo \"Duree du mirroring de la partition /var: \${duree} secondes soit \$(FORMATE_DUREE \${duree})\" | tee -a \$FICHIERLOG
echo '' | tee -a \$FICHIERLOG

t1=\$t2

#==================================

#partition home
echo 'Montage de la partition HOME' | tee -a \$FICHIERLOG
mount -t xfs \$PARTHOME_CIBLE /mirror/part_home
if [ \$? != 0 ]; then
	echo \"** ERREUR ** lors du montage de la partition /home de \${DISK2}\"  | tee -a \$FICHIERLOG
	echo 'Le script ne peut se poursuivre' | tee -a \$FICHIERLOG
	echo \"VeRIFIEZ LE BON FONCTIONNEMENT DU \${DISK2}\" | tee -a \$FICHIERLOG
	touch /mirror/mail_alerte.txt
	echo \"Rapport de fonctionnement du script de mirroring lors de son lancement\" > /mirror/mail_alerte.txt
	echo \"Voici les problemes constates\" >> /mirror/mail_alerte.txt
	echo \"\" >> /mirror/mail_alerte.txt
	cat \$FICHIERLOG >> /mirror/mail_alerte.txt
	mail \$MAIL_ADMIN -s \"[Alerte /mirror/mirror_rsync.sh] Pb avec le script\" < /mirror/mail_alerte.txt
	exit 1
fi
echo '' | tee -a \$FICHIERLOG

echo 'rsync de la partition /home' | tee -a \$FICHIERLOG
echo '' | tee -a \$FICHIERLOG
/usr/bin/rsync -av --delete /home/  /mirror/part_home/ | tee -a \$FICHIERLOG
cd /
umount \$PARTHOME_CIBLE

t2=\$(date +%s)
duree=\$((t2-t1))
echo \"Duree du mirroring de la partition /home: \${duree} secondes soit \$(FORMATE_DUREE \${duree})\" | tee -a \$FICHIERLOG
echo '' | tee -a \$FICHIERLOG

t1=\$t2
#==========================================
#partition /var/se3
echo 'Montage de la partition VAR/SE3' | tee -a \$FICHIERLOG
mount -t xfs \$PARTVARSE3_CIBLE /mirror/part_varse3

if [ \$? != 0 ]; then
	echo \"** ERREUR ** lors du montage de la partition /var/se3 de \${DISK2}\"  | tee -a \$FICHIERLOG
	echo 'Le script ne peut se poursuivre' | tee -a \$FICHIERLOG
	echo \"VeRIFIEZ LE BON FONCTIONNEMENT DU \${DISK2}\" | tee -a \$FICHIERLOG
	touch /mirror/mail_alerte.txt
	echo \"Rapport de fonctionnement du script de mirroring lors de son lancement\" > /mirror/mail_alerte.txt
	echo \"Voici les problemes constates\" >> /mirror/mail_alerte.txt
	echo \"\" >> /mirror/mail_alerte.txt
	cat \$FICHIERLOG >> /mirror/mail_alerte.txt
	mail \$MAIL_ADMIN -s \"[Alerte /mirror/mirror_rsync.sh] Pb avec le script\" < /mirror/mail_alerte.txt
	exit 1
fi

echo '' | tee -a \$FICHIERLOG

echo 'rsync de la partition /var/se3' | tee -a \$FICHIERLOG
echo '' | tee -a \$FICHIERLOG
/usr/bin/rsync -av --delete /var/se3/* /mirror/part_varse3 | tee -a \$FICHIERLOG


t2=\$(date +%s)
duree=\$((t2-t1))
echo \"Duree du mirroring de la partition /var/se3: \${duree} secondes soit \$(FORMATE_DUREE \${duree})\" | tee -a \$FICHIERLOG
echo '' | tee -a \$FICHIERLOG

t1=\$t2

cd /var/se3
echo 'Sauvegarde / Restauration des ACLS en cours pour /var/se3....'
getfacl -R . > /mirror/part_varse3/list_acls.txt

t2=\$(date +%s)
duree=\$((t2-t1))
echo \"Duree de la sauvegarde des ACL de la partition /var/se3: \${duree} secondes soit \$(FORMATE_DUREE \${duree})\" | tee -a \$FICHIERLOG
echo '' | tee -a \$FICHIERLOG

t1=\$t2

cd /mirror/part_varse3/
setfacl --restore=list_acls.txt
cd /
umount \$PARTVARSE3_CIBLE


t2=\$(date +%s)
duree=\$((t2-t1))
echo \"Duree de la restauration des ACL de la partition /var/se3: \${duree} secondes soit \$(FORMATE_DUREE \${duree})\" | tee -a \$FICHIERLOG
echo '' | tee -a \$FICHIERLOG

echo 'Recapitulatif:' | tee -a \$FICHIERLOG
grep \"^Duree \" \$FICHIERLOG

duree=\$((t2-t0))
echo \"Duree totale du mirroring: \${duree} secondes soit \$(FORMATE_DUREE \${duree})\" | tee -a \$FICHIERLOG
" >> /mirror/mirror_rsync.sh


# creation du script mount_disk2
if [ -e /mirror/mount_DISK2.sh ]; then
	mv /mirror/mount_DISK2.sh /mirror/mount_DISK2_${ladate}.sh
fi

touch /mirror/mount_DISK2.sh
chmod 700 /mirror/mount_DISK2.sh
echo "source /mirror/param_mirror_rsync.sh
mount -t ext3 \$PARTROOT_CIBLE  /mirror/part_root
mount -t ext3 \$PARTVAR_CIBLE  /mirror/part_var
mount -t xfs \$PARTHOME_CIBLE /mirror/part_home
mount -t xfs \$PARTVARSE3_CIBLE /mirror/part_varse3
" > /mirror/mount_DISK2.sh

# creation du script umount_disk2
if [ -e /mirror/umount_DISK2.sh ]; then
	mv /mirror/umount_DISK2.sh /mirror/umount_DISK2_${ladate}.sh
fi
touch /mirror/umount_DISK2.sh
chmod 700 /mirror/umount_DISK2.sh
echo "source /mirror/param_mirror_rsync.sh
umount \$PARTROOT_CIBLE
umount \$PARTHOME_CIBLE
umount \$PARTVARSE3_CIBLE
umount \$PARTVAR_CIBLE
" > /mirror/umount_DISK2.sh
