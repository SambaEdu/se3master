#!/bin/bash
#***************************************************************************************************
# Auteur Jean yves Morvan - académie de Rouen
# 09/2017 modif F. Molle : Utilisation des infos en cache la base MySql 

COLTXT="\033[1;37m"
COLTITRE="\033[1;35m"
COLENTREE="\033[1;33m"

mkdir -p /root/save
if [ -e /etc/se3/config_c.cache.sh ] && [ -e /etc/se3/config_l.cache.sh ] && [ -e /etc/se3/config_m.cache.sh ] 
then
	echo "Initialisation des variables avec le cache de la BDD"
else
	echo "Fichier cache inexistant - création du cache"
	/usr/share/se3/includes/config.inc.sh -clmf
	
fi
source /etc/se3/config_c.cache.sh 
source /etc/se3/config_l.cache.sh
source /etc/se3/config_m.cache.sh

if [ -e /etc/dhcp/dhcpd.conf ];then
	cp /etc/dhcp/dhcpd.conf /root/save/
else
	cp  /etc/dhcp3/dhcpd.conf /root/save/
fi

if [ -e /var/lib/samba/private/secrets.tdb ]; then 
	cp  /var/lib/samba/private/secrets.tdb /root/save/
else
	cp /var/lib/samba/secrets.tdb /root/save/
fi

cp /etc/samba/smb.conf /root/save
cp /etc/ldap.secret /root/save
if [ -f /etc/se3/setup_se3.data ]; then 
	cp /etc/se3/setup_se3.data  /root/save/setup_se3.data.old
fi
#***************************************************************************************************
#***************************************************************************************************

Base_DN="$ldap_base_dn"
echo -e "$COLTITRE"
echo "##########################################################################"
echo "# Recuperation de la base DN :                                           #"
echo "#Base DN =" $Base_DN                                                     
echo "# Si la base DN est correcte, appuyez sur ENTREE sinon appuyer sur CTL-C #"
echo "##########################################################################"
read PAUSE




#***************************************************************************************************

echo -e "$COLTITRE"
echo "##############################"
echo "# Sauvegarde de la base LDAP #"
echo "##############################"

echo -e "$COLTXT"
slapcat > /root/save/ldap_se3_sav.ldif
#***************************************************************************************************
#***************************************************************************************************

echo -e "$COLTITRE"
echo "#################################################################"
echo "# Sauvegarde de des branches computers et parcs de la base LDAP #"
echo "#################################################################"

echo -e "$COLTXT"
slapcat -s ou=Computers,$Base_DN -l /root/save/computers.ldif
slapcat -s ou=Parcs,$Base_DN -l /root/save/parcs.ldif
slapcat -s ou=Printers,$Base_DN -l /root/save/printers.ldif
slapcat -s ou=Rights,$Base_DN -l /root/save/rights.ldif

#***************************************************************************************************

#***************************************************************************************************

echo -e "$COLTITRE"
echo "#########################################################"
echo "# Sauvegarde des différents mots de passe et parametres #"
echo "#########################################################"

 echo -e "*******************Paramètres LDAP*******************" > /root/save/parametres.txt
echo -e "$COLTXT"
echo -e "Domaine de messagerie : $domain" >> /root/save/parametres.txt
echo -n "Base DN : " >> /root/save/parametres.txt
echo "$Base_DN" >> /root/save/parametres.txt

echo -n "Root DN : " >> /root/save/parametres.txt
echo "$adminRdn" >> /root/save/parametres.txt

echo -n "Mot de passe LDAP : " >> /root/save/parametres.txt
cat /etc/ldap.secret >>  /root/save/parametres.txt

if [ -e  /etc/ldap/syncrepl.conf ]; then
	echo "Réplication d'annuaire détectée"
	echo "Attention : réplication d'annuaire en place" >> /root/save/parametres.txt
	grep HOST /etc/ldap/ldap.conf >> /root/save/parametres.txt
fi

if [ -e /etc/apache2/sites-enabled/webdav ]; then
	echo "Existence d'un service webdav pour interconnexion ENT détectée"
	echo "****************** Service Webdav ******************" >> /root/save/parametres.txt
	echo "Attention : Service webdav pour interconnexion ENT détecté" >> /root/save/parametres.txt
fi


 echo -e "*******************Autres mots de passe*******************" >> /root/save/parametres.txt
 echo -n "Mot de passe MySql : " >> /root/save/parametres.txt
 grep password /root/.my.cnf | cut -f2 -s -d'=' >> /root/save/parametres.txt

 echo -n "Mot de passe de AdminSE3 : " >> /root/save/parametres.txt
 echo "$xppass" >> /root/save/parametres.txt 


 echo -e "*******************Paramètres samba*******************" >> /root/save/parametres.txt
 echo -n "Domaine samba : " >> /root/save/parametres.txt
 echo "$se3_domain" >> /root/save/parametres.txt
 echo -e "\n"
 echo -n "Nom NetBios : " >> /root/save/parametres.txt
 echo "$netbios_name" >> /root/save/parametres.txt
 echo -e "\n"
 echo -n "@ip  et masque : " >> /root/save/parametres.txt
 echo "$se3ip / $se3mask"  >> /root/save/parametres.txt
 echo -e "\n"

echo -e "*******************DHCP*******************" >> /root/save/parametres.txt 
echo -n "Pool DHCP : " >> /root/save/parametres.txt

[ -e /etc/dhcp/dhcpd.conf ] && grep range /etc/dhcp/dhcpd.conf | cut -f2-3 -s -d' ' | cut -f1 -s -d';' >> /root/save/parametres.txt
[ -e /etc/dhcp3/dhcpd.conf ] && grep range /etc/dhcp3/dhcpd.conf | cut -f2-3 -s -d' ' | cut -f1 -s -d';' >> /root/save/parametres.txt
echo -e "*******************partitions*******************" >> /root/save/parametres.txt 
echo -n "partitions  : " >> /root/save/parametres.txt
df >> /root/save/parametres.txt

echo -e "*******************DHCP*******************" >> /root/save/parametres.txt 
clamav=`(dpkg -s se3-clamav | grep "Status: install ok") 2> /dev/null`
backup=`(dpkg -s se3-backup | grep "Status: install ok") 2> /dev/null`
ocs=`(dpkg -s se3-ocs | grep "Status: install ok") 2> /dev/null`
dhcp=`(dpkg -s se3-dhcp | grep "Status: install ok") 2> /dev/null `
clonage=`(dpkg -s se3-clonage | grep "Status: install ok") 2> /dev/null `
unattended=`(dpkg -s se3-unattended | grep "Status: install ok") 2> /dev/null`
wpkg=`(dpkg -s se3-wpkg | grep "Status: install ok") 2> /dev/null `
internet=`(dpkg -s se3-internet | grep "Status: install ok") 2> /dev/null`
synchro=`(dpkg -s se3-synchro | grep "Status: install ok") 2> /dev/null`
if [[ $clamav == "" ]]
then    
 echo -e Clamav : \*\*\*\* >> /root/save/parametres.txt 
else 
 echo -e Clamav : Installe >> /root/save/parametres.txt 
fi
if [[ $backup == "" ]]
then
 echo -e Backup : \*\*\*\* >> /root/save/parametres.txt 
else
 echo -e backup : Installe >> /root/save/parametres.txt 
fi
if [[ $ocs == "" ]]
then
 echo -e ocs : \*\*\*\* >> /root/save/parametres.txt 
else
 echo -e ocs : Installe >> /root/save/parametres.txt 
 
fi
if [[ $dhcp == "" ]]
then
 echo -e Dhcp : \*\*\*\* >> /root/save/parametres.txt 
else
 echo -e Dhcp : Installe >> /root/save/parametres.txt 
fi
if [[ $clonage == "" ]]
then
 echo -e Clonage : \*\*\*\* >> /root/save/parametres.txt 
else
 echo -e Clonage : Installe >> /root/save/parametres.txt 
fi
if [[ $unattended == "" ]]
then
 echo -e Unattended : \*\*\*\* >> /root/save/parametres.txt 
else
 echo -e Unattended : Installe >> /root/save/parametres.txt 
fi
if [[ $wpkg == "" ]]
then
 echo -e Wpkg : \*\*\*\* >> /root/save/parametres.txt 
else
 echo -e Wpkg : Installe >> /root/save/parametres.txt 
fi
if [[ $internet == "" ]]
then
 echo -e Internet : \*\*\*\* >> /root/save/parametres.txt 
else
 echo -e Internet : Installe >> /root/save/parametres.txt 
fi
if [[ $synchro == "" ]]
then
 echo -e Synchro : \*\*\*\* >> /root/save/parametres.txt 
else
 echo -e Synchro : Installe >> /root/save/parametres.txt 
fi
echo "*******************reservation dhcp*******************" 

mysql se3db -e "select ip,name,mac from se3_dhcp fields " -BN | sed "s/\t/;/g" >  /root/save/dhcp.csv

#***************************************************************************************************

if [ -e "/var/se3/unattended/install/wpkg/tmp/timeStamps.xml" ];then
	cp /var/se3/unattended/install/wpkg/tmp/timeStamps.xml /root/save/
fi


echo -e "$COLENTREE"
echo "####################################"
echo "# Appuyer sur ENTREE pour terminer #"
echo "####################################"
cd /
echo -e "$COLTITRE"
read PAUSE


echo "Voulez-vous copier les paramètres vers un autre se3 pour une migration ? O/n"
read rep

if [ "$rep" != "n" ]; then
	echo "saisir ip du nouveau se3"
	read newip
	echo "copie du dossier save dans ${newip}:/root/" 
	scp -r /root/save root@$newip:/root/ 
	ssh root@$newip 'mkdir /etc/se3; mkdir /var/lib/samba'
	echo "copie de secrets.tdb dans ${newip}:/var/lib/samba/" 
	scp /root/save/secrets.tdb root@$newip:/var/lib/samba/ 
	echo "copie de setup_se3.data dans $newip:/etc/se3/" 
	scp /root/save/setup_se3.data root@$newip:/etc/se3/ 
fi