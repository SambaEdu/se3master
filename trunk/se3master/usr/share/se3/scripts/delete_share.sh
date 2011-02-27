#!/bin/bash
######################################
######################################

SMB_CONF=/etc/samba/smb.conf
MAIL=$(ldapsearch -xLLL "uid=admin" | grep mail | cut -d " " -f2)
SE3_ROOT=/var/se3

LineStart=0
LineEnd=0
TotalLine=$(wc -l $SMB_CONF | cut -d ' ' -f1)

while read line 
do
	if [ $LineEnd == "0" ]
	then	
		(( LineStart+=1 ))
		echo "$line" | grep "#<$1>" > /dev/null
		if [ "$?" == "0" ]
		then
			(( LineEnd=LineStart ))
		fi
	else
		(( LineEnd+=1 ))
		echo "$line" | grep "#</$1>" > /dev/null
		if [ "$?" == "0" ]
		then
			break
		fi
		#echo "$line" | grep ".*path.*=.*" > /dev/null
		#if [ "$?" == "0" ]
		#then
		#	dir=$(expr "$line" : '.*path.*=[^/]*\(.*\)')
		#	rm -rf $dir
		#fi
	fi
done < $SMB_CONF

(( LineStart-=1 ))
head -n $LineStart $SMB_CONF >/tmp/smb.conf
(( StartEnd=TotalLine-LineEnd ))
tail -n $StartEnd $SMB_CONF >>/tmp/smb.conf
mv -f /tmp/smb.conf /etc/samba/smb.conf

#On envoie un mail � l'admin
echo "La suppression du partage $1 sur le serveur $(hostname) a r�ussie!" | \
mail -s "[SE3 T�che d'administration] Cr�ation partage Samba" $MAIL 

#On affiche le m�me message � l'�cran
echo "La suppression du partage $1 sur le serveur $(hostname) a r�ussie!"
