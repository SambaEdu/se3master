#!/bin/bash
#
##### Script de creation des liens pour disposer des memes profils Firefox et Thunderbird sous Windows et Linux #####
#
# Auteur: Stephane Boireau, futur ex-animateur de secteur
#
## $Id$ ##
#
# Derniere modification: 14/02/2009

echo_debug ()
{
	# Passer la variable à 1 pour afficher des messages de debug en cours de traitement
	debug=0
	if [ "$debug" = "1" ]; then
		echo $*
	fi
}

ladate=$(date +%Y%m%d%H%M%S)

ls /home/|while read A
# ls /home/|grep boireaus|while read A
do
	t=$(ldapsearch -xLLL uid=$A)
	if [ -n "$t" ]; then
		echo_debug "Compte: $A"

		# Traitement de Firefox
		echo_debug "Traitement de Firefox"
		cd /home/$A
		if [ ! -e .mozilla/firefox/profiles.ini ]; then
			# Il n'existe pas encore de profil Firefox pour Linux
			echo_debug "Il n'existe pas encore de profil Firefox pour Linux"
			mkdir -p .mozilla/firefox
			cd .mozilla/firefox
			ln -s ../../profil/appdata/Mozilla/Firefox/Profiles/default ./
			echo "[General]
StartWithLastProfile=1

[Profile0]
Name=default
IsRelative=1
Path=default" > profiles.ini
			chown -R $A:lcs-users /home/$A/.mozilla
		else
			# Il existe un ou des profils Firefox pour Linux
			echo_debug "Il existe un ou des profils Firefox pour Linux"
			#cd .mozilla/firefox
			#if egrep -q "(Path=default$|Name=default$)" .mozilla/firefox/profiles.ini; then
			if grep -q "Path=default$" .mozilla/firefox/profiles.ini; then
				# Il existe deja un profil pointant vers un dossier "default"
				echo_debug "On ne corrige pas le .mozilla/firefox/profiles.ini pour $A"
			else
				if grep -q "Name=default$" .mozilla/firefox/profiles.ini; then
					# Il existe deja un profil nomme default, mais son Path n'est pas default
					# On va juste donner un autre Nom pour le profil

					# On cherche le nombre de profiles et surtout un indice libre
					echo_debug "On cherche le nombre de profiles et surtout un indice libre"
					num=$(grep "^\[Profile" .mozilla/firefox/profiles.ini | sed -e "s|[^0-9]||g" | sort -n |tail -n 1)
					num=$(($num+1))

					echo "[Profile$num]
Name=default_${ladate}
IsRelative=1
Path=default" >> .mozilla/firefox/profiles.ini

					# On bascule en mode Choix du profil au lancement:
					echo_debug "On bascule en mode Choix du profil au lancement"
					sed -i "s|StartWithLastProfile=1|StartWithLastProfile=0|" .mozilla/firefox/profiles.ini
				else
					# On cherche le nombre de profiles et surtout un indice libre
					echo_debug "On cherche le nombre de profiles et surtout un indice libre"
					num=$(grep "^\[Profile" .mozilla/firefox/profiles.ini | sed -e "s|[^0-9]||g" | sort -n |tail -n 1)
					num=$(($num+1))

					echo "[Profile$num]
Name=default
IsRelative=1
Path=default" >> .mozilla/firefox/profiles.ini

					# On bascule en mode Choix du profil au lancement:
					echo_debug "On bascule en mode Choix du profil au lancement"
					sed -i "s|StartWithLastProfile=1|StartWithLastProfile=0|" .mozilla/firefox/profiles.ini
				fi

				cd .mozilla/firefox
				ln -s ../../profil/appdata/Mozilla/Firefox/Profiles/default ./
			fi
		fi

		# Traitement de Thunderbird
		echo_debug "Traitement de Thunderbird"
		cd /home/$A
		if [ ! -e .thunderbird/profiles.ini ]; then
			# Il n'existe pas encore de profil Firefox pour Linux
			echo_debug "Il n'existe pas encore de profil Thunderbird pour Linux"
			mkdir -p .thunderbird
			ln -s .thunderbird .mozilla-thunderbird
			cd .thunderbird
			ln -s ../profil/appdata/Thunderbird/Profiles/default ./
			echo "[General]
StartWithLastProfile=1

[Profile0]
Name=default
IsRelative=1
Path=default" > profiles.ini
			chown -R $A:lcs-users /home/$A/.thunderbird
		else
			#cd .thunderbird
			#if egrep -q "(Path=default$|Name=default$)" .thunderbird/profiles.ini; then
			if grep -q "Path=default$" .thunderbird/profiles.ini; then
				# Il existe deja un profil pointant vers un dossier "default"
				echo_debug "On ne corrige pas le .thunderbird/profiles.ini pour $A"
			else
				if grep -q "Name=default$" .thunderbird/profiles.ini; then
					# On cherche le nombre de profiles et surtout un indice libre
					echo_debug "On cherche le nombre de profiles et surtout un indice libre"
					num=$(grep "^\[Profile" .thunderbird/profiles.ini | sed -e "s|[^0-9]||g" | sort -n |tail -n 1)
					num=$(($num+1))

					echo "[Profile$num]
Name=default_${ladate}
IsRelative=1
Path=default" >> .thunderbird/profiles.ini
					# On bascule en mode Choix du profil au lancement:
					sed -i "s|StartWithLastProfile=1|StartWithLastProfile=0|" .thunderbird/profiles.ini
				else
					# On cherche le nombre de profiles et surtout un indice libre
					echo_debug "On cherche le nombre de profiles et surtout un indice libre"
					num=$(grep "^\[Profile" .thunderbird/profiles.ini | sed -e "s|[^0-9]||g" | sort -n |tail -n 1)
					num=$(($num+1))

					echo "[Profile$num]
Name=default
IsRelative=1
Path=default" >> .thunderbird/profiles.ini
					# On bascule en mode Choix du profil au lancement:
					sed -i "s|StartWithLastProfile=1|StartWithLastProfile=0|" .thunderbird/profiles.ini
				fi
				cd .thunderbird
				ln -s ../profil/appdata/Thunderbird/Profiles/default ./
			fi
		fi
		echo_debug "============================"

		# Decommenter pour avancer PAS � PAS
		#read PAUSE < /dev/tty
	fi
done

for user in user user.linux
do
	if [ ! -e "/etc/skel/$user/.mozilla/firefox/default" -a -e "/etc/skel/$user/profil/appdata/Mozilla/Firefox/Profiles/default"  ]; then
		echo_debug "/etc/skel/$user/.mozilla/firefox/default n'existe pas encore"
		mkdir -p /etc/skel/$user/.mozilla/firefox
		cd /etc/skel/$user/.mozilla/firefox
		echo "[General]
StartWithLastProfile=1

[Profile0]
Name=default
IsRelative=1
Path=default" > profiles.ini
		ln -s ../../profil/appdata/Mozilla/Firefox/Profiles/default ./
	fi

	if [ ! -e "/etc/skel/$user/.thunderbird/default" -a -e "/etc/skel/$user/profil/appdata/Thunderbird/Profiles/default" ]; then
		echo_debug "/etc/skel/$user/.thunderbird/default n'existe pas encore"
		mkdir -p /etc/skel/$user/.thunderbird
		ln -s /etc/skel/$user/.thunderbird /etc/skel/$user/.mozilla-thunderbird
		cd /etc/skel/$user/.thunderbird
		echo "[General]
StartWithLastProfile=1

[Profile0]
Name=default
IsRelative=1
Path=default" > profiles.ini
		ln -s ../profil/appdata/Thunderbird/Profiles/default ./
	fi
done
