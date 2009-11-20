#!/bin/sh
#
# $Id$ #
#

if [ -f /tmp/move_backuppc.lock ]
then
	echo "Lock trouvé"
	logger -t "BackupPc" "Lock trouvé..."
else 

  if [ "$1" = "" -o "$2" = "" ]
  then
	echo Syntaxe : move_rep_backup source destination
	logger -t "BackupPc" "Erreur : impossible de copier"
	exit;
  fi
  
  # On place un lock
  touch /tmp/move_backuppc.lock

  # On copie le répertoire backuppc vers la nouvelle destination
  # Cas ou on essaye de revenir dans /var/lib
  if [ "$2" = "/var/lib/backuppc" ]
  then
  	if [ -L "/var/lib/backuppc" ]
	then
		rm -f /var/lib/backuppc
	fi
  fi	
  mv $1 $2
  logger -t "BackupPc" "Repertoire déplacé de $1 vers $2"
  
  # On recrée le lien symb de /var/lib/backuppc
  if [ "$?" = "0" ]
  then
	if [ -L "/var/lib/backuppc" -o ! -d "/var/lib/backuppc" ]
	then
		rm -f /var/lib/backuppc
	fi	
	ln -s $2 /var/lib/backuppc 

	# On donne les droits a www-se3 sur $2
	# chown -R www-se3.root $2
	
	# On relance backuppc
	/etc/init.d/backuppc stop
	/etc/init.d/backuppc start
  else
	echo "Echec"
  fi
  # on flingue le lock
  if [ -f /tmp/move_backuppc.lock ]
  then
  	rm -f /tmp/move_backuppc.lock
  fi	
fi  

