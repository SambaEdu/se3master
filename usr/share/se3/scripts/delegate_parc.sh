#!/bin/bash


## $Id$ ##


# faire un alias du template et positionner les droits dessus
# sandrine dangreville 12 mars 2005


if [ $# = 3 ]; then
echo "Démarrage du script"
else
echo "utilisation delegate_parc.sh template utilisateur mode 
      mode = delegate permet de deleguer le gestion du template à l'utilisateur sélectionné 
      mode = nodelegate permet de retirer cette delegation à l'utilisateur sélectionné 
      exemple : delegate_parc.sh base admin delegate"
	exit
fi 


if [ $3 = "delegate" ]
	then
		echo "Delegation du template $1 a $2"
	#	if [ ! -d  /home/$2/Bureau/Templates ]; then
	#		mkdir /home/$2/Bureau/Templates
	#	fi
	if [ ! -d  /home/$2/Docs/Delegation ]; then 
			echo "Creation du repertoire Delegation"
			mkdir /home/$2/Docs/Delegation
		fi	

		if [ ! -L  /home/$2/Docs/Delegation/$1 ]; then 
				echo "Creation du lien"
	ln -s /home/templates/$1/ /home/$2/Docs/Delegation/$1
	echo	"mise en place du droit d'ecriture"
	#echo  "setfacl -R -m u:$2:rwx /home/templates/$1/Bureau"
	setfacl -R -m u:$2:rwx /home/templates/$1/Bureau
	setfacl -R -m u:$2:rwx /home/templates/$1/Demarrer
	setfacl -m u:$2:rwx /home/templates/$1/*.bat
	#echo  "setfacl -R -m m:$2:rwx /home/templates/$1/Bureau"
	setfacl -R -m m::rwx /home/templates/$1/Bureau
	setfacl -R -m m::rwx /home/templates/$1/Demarrer
	setfacl -m m::rwx /home/templates/$1/*.bat
fi
fi

if [ $3 = "nodelegate" ]
	then
		echo "Suppression de la delegation de $1 a $2"
		
	if [ -L  /home/$2/Docs/Delegation/$1 ] 
	then	
	echo "suppression du lien symbolique"
	rm /home/$2/Docs/Delegation/$1
fi
if [ $2 <> "root" ] 
then
	echo "suppression du droit d'ecriture"
	setfacl -R -x u:$2 /home/templates/$1
fi
fi



