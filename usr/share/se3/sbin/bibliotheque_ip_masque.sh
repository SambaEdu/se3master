#!/bin/bash

# $Id$
# 
# Bibliothèque de fonctions de calculs IP/MASQUE/...
# Usage: source <chemin>/bibliotheque_ip_masque.sh
#        calcule_reseau $IP $MASQUE
#        calcule_broadcast $IP $MASQUE

function puissance(){
	a=$1
	n=$2
	resultat=1
	cpt=1
	while [ $cpt -le $n ]
	do
		resultat=$(($resultat*$a))
		cpt=$((cpt+1))
	done
	echo $resultat
}

function binaire(){
	reste=$1
	diviseur=128
	chaine=""
	while [ $diviseur -ge 1 ]
	do
		if [ $reste -ge $diviseur ]; then
			reste=$(($reste-$diviseur))
			chaine=$chaine"1"
		else
			chaine=$chaine"0"
		fi
		diviseur=$(($diviseur/2))
	done
	echo $chaine
}

function octet(){
	chaine=$1
	octet=0
	cpt=0
	while [ $cpt -le 7 ]
	do
		b=${chaine:$cpt:1}
		octet=$(($octet+$(($(puissance 2 $((7-$cpt)))*$b))))
		cpt=$((cpt+1))
	done
	echo $octet
}

function et(){
	A=$1
	B=$2
	chaine=""
	cpt=0
	while [ $cpt -le 7 ]
	do
		a=${A:$cpt:1}
		b=${B:$cpt:1}
		if [ "$a" = "1" -a "$b" = "1" ]; then
			ajout="1"
		else
			ajout="0"
		fi
		chaine=${chaine}${ajout}
		cpt=$((cpt+1))
	done
	echo $chaine
}

function complement(){
	A=$1
	chaine=""
	cpt=0
	while [ $cpt -le 7 ]
	do
		a=${A:$cpt:1}
		if [ "$a" = "1" ]; then
			ajout="0"
		else
			ajout="1"
		fi
		chaine=${chaine}${ajout}
		cpt=$((cpt+1))
	done
	echo $chaine
}

function ou(){
	A=$1
	B=$2
	chaine=""
	cpt=0
	while [ $cpt -le 7 ]
	do
		a=${A:$cpt:1}
		b=${B:$cpt:1}
		if [ "$a" = "0" -a "$b" = "0" ]; then
			ajout="0"
		else
			ajout="1"
		fi
		chaine=${chaine}${ajout}
		cpt=$((cpt+1))
	done
	echo $chaine
}

#octet $1
#puissance $1 $2
#binaire $1
#et 11001011 10010110
#et 11001011
#   10010110
# = 10000010
#et $1 $2

#ou 11001011
#   10010110
# = 11011111
#ou $1 $2

function calcule_reseau(){
	IP=$1
	NETMASK=$2
	binreseau=""
	cpt=1
	while [ $cpt -le 4 ]
	do
		octip[$cpt]=$(echo $IP | cut -d"." -f$cpt)
		binip[$cpt]=$(binaire ${octip[$cpt]})

		octmask[$cpt]=$(echo $NETMASK | cut -d"." -f$cpt)
		binmask[$cpt]=$(binaire ${octmask[$cpt]})

		et_ajout=$(et ${binip[$cpt]} ${binmask[$cpt]})
		binreseau=${binreseau}${et_ajout}
		octreseau[$cpt]=$(octet ${et_ajout})

		cpt=$((cpt+1))
	done
	echo "${octreseau[1]}.${octreseau[2]}.${octreseau[3]}.${octreseau[4]}"
}

function calcule_broadcast(){
	IP=$1
	NETMASK=$2
	binbroadcast=""
	cpt=1
	while [ $cpt -le 4 ]
	do
		octip[$cpt]=$(echo $IP | cut -d"." -f$cpt)
		binip[$cpt]=$(binaire ${octip[$cpt]})

		octmask[$cpt]=$(echo $NETMASK | cut -d"." -f$cpt)
		binmask[$cpt]=$(binaire ${octmask[$cpt]})
		compbinmask[$cpt]=$(complement ${binmask[$cpt]})

		ou_ajout=$(ou ${binip[$cpt]} ${compbinmask[$cpt]})
		binbroadcast=${binbroadcast}${ou_ajout}
		octbroadcast[$cpt]=$(octet ${ou_ajout})

		cpt=$((cpt+1))
	done
	echo "${octbroadcast[1]}.${octbroadcast[2]}.${octbroadcast[3]}.${octbroadcast[4]}"
}
