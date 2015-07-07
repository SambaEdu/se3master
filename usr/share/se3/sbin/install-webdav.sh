#!/bin/bash

###
#	Mise en place webdav pour interconnexion avec ENT
###
### Auteur Franck.molle@ac-rouen.fr


## $Id$ ##


. /usr/share/se3/includes/config.inc.sh -cml

davdir="/etc/apache2/webdav"
davdir_sav="/etc/apache2/webdav-sav"
davname="$(echo $domain | cut -d . -f1)"


function helpscript ()
{
echo "script devant être lancé avec un argument :
- install : installation initiale
- update : mise à jour configuration pour nouveaux comptes
- classes : mise à jour acls sur classes
- create : creation pour un utilisateur
"
exit 1
}





function install_conf ()
{
# mv /etc/apache2/sites-available/default /etc/apache2/sites-available/default-sav
echo "<VirtualHost *:80>
        ServerAdmin webmaster@localhost

        <Location /$davname>
                Dav On
                DirectorySlash Off
        </Location>


        DocumentRoot /var/www
        <Directory />
                Options FollowSymLinks
                AllowOverride None
        </Directory>
        <Directory /var/www/>
                Options Indexes FollowSymLinks MultiViews
                AllowOverride None
                Order allow,deny
                allow from all
        </Directory>

        ScriptAlias /cgi-bin/ /usr/lib/cgi-bin/
        <Directory \"/usr/lib/cgi-bin\">
                AllowOverride None
                Options +ExecCGI -MultiViews +SymLinksIfOwnerMatch
                Order allow,deny
                Allow from all
        </Directory>

        ErrorLog \${APACHE_LOG_DIR}/error.log

        # Possible values include: debug, info, notice, warn, error, crit,
        # alert, emerg.
        LogLevel warn

        CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
"  >/etc/apache2/sites-available/default



echo "Alias /$davname/classes "/var/se3/Classes"

	DAVMinTimeout 600
	DAVDepthInfinity On
	

	<Directory /var/se3/Classes>
		DAV on
		AuthName \"Webdav Classes\"
		Options Indexes FollowSymLinks
		AuthType Basic
		AuthBasicProvider ldap
		AuthzLDAPAuthoritative off
		# Paramètres de l'annuaire LDAP 
		AuthLDAPURL ldap://127.0.0.1:389/ou=People,$ldap_base_dn?uid? 
		AuthLDAPGroupAttribute memberUid 
		AuthLDAPGroupAttributeIsDN off 
		# Pour limiter l'accès au admins 
		Require ldap-group cn=admins,ou=Groups,$ldap_base_dn
		# Pour limiter l'accès au groupe Profs 
		Require ldap-group cn=Profs,ou=Groups,$ldap_base_dn
		</Directory>

include /etc/apache2/webdav/*.conf
" >/etc/apache2/sites-available/webdav 

a2enmod dav_fs
a2enmod authnz_ldap
a2ensite webdav

rm -f /etc/apache2/mods-enabled/deflate.* 


rm -f "/etc/apache2/webdav/*"

mkdir -p $davdir


[ -e /etc/cron.d/se3-webdav ] && rm -f /etc/cron.d/se3-webdav

}

function create_user ()
{
uid="$1"
echo "creation $uid.conf pour $uid"
echo "# $uid
Alias /$davname/$uid /home/$uid/Docs
<Directory /home/$uid/Docs>
DAV On
AuthType Basic
AuthName \"My WebDav Directory\"
Options Indexes FollowSymLinks
AuthType Basic
AuthBasicProvider ldap
AuthzLDAPAuthoritative off
# Paramètres de l'annuaire LDAP
AuthLDAPURL ldap://127.0.0.1:389/ou=People,$ldap_base_dn?uid? 
AuthLDAPGroupAttribute memberUid 
AuthLDAPGroupAttributeIsDN off 
Require ldap-filter &(uid=$uid)
</Directory>
" > $davdir/$uid.conf

echo "mise en place des droits www-data sur le home de $uid"
setfacl -m u:www-data:x /home/$uid
setfacl -R -m u:www-data:rx /home/$uid/Docs
setfacl -R -m d:u:www-data:rx /home/$uid/Docs
}

function update_conf ()
{


rm -f /etc/apache2/webdav-sav/*
if [ -e $davdir ];then
	mv $davdir/* $davdir_sav/ 
	mkdir -p $davdir_sav
fi



# On cherche la liste des utilisateurs
ldapsearch -xLLL -D $adminRdn,$ldap_base_dn -w $adminPw objectClass=person uid| grep uid:| cut -d ' ' -f2| while read uid
do
	echo "traitement de $uid"
		
	if [ "$uid" != "admin" ] && [ "$uid" != "adminse3" ] && [ "$uid" != "webmaster" ] && [ "$uid" != "unattend" ] 
	then
		
	
		if [ -e /home/$uid ]
		then
			  if [ -e $davdir_sav/$uid.conf ]; then
				# le fichier webdav del'utilisateur existe il faut le sauvegarder
				echo "recup conf pour $uid"
				
				echo "mise en place des droits www-data sur le home de $uid"
				setfacl -m u:www-data:x /home/$uid
				setfacl -R -m u:www-data:rx /home/$uid/Docs
				setfacl -R -m d:u:www-data:rx /home/$uid/Docs
				
				mv  $davdir_sav/$uid.conf $davdir/$uid.conf
			  else
				# on cree la conf
				create_user $uid
			  fi
  # 				/usr/share/se3/shares/shares.avail/mkhome.sh $uid
			  
		fi
	fi
done



}

function classes_update ()
{

echo "mise en place des droits sur les classes"
setfacl -R -m u:www-data:rx /var/se3/Classes/
setfacl -R -m d:u:www-data:rx /var/se3/Classes/

}


PHASE="$1" 


case "$PHASE" in

    "install")
    
    install_conf
	update_conf
	classes_update
        ;;

    "update")
        update_conf
        ;;

     "classes")
        classes_update
        ;;   
        
      "create")
		
		if [ -e /home/$2 ]; then
			create_user $2
        else
			echo "/home/$2 inexistant"
			exit 1
        fi
        ;;   
          
        
    *)
        # L'argument PHASE est incorrect, on arrete tout.
        helpscript
        ;;

esac





/etc/init.d/apache2 restart
exit 0










