#!/bin/bash
WWWPATH="/var/www"
if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
	dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
	dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
	dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
	dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
else
	echo "Fichier de conf inaccessible."
	exit 1
fi

GENSUPERVISION()
{
echo "#!/bin/bash
rm -rf /tmp/maj 
mkdir -p /tmp/maj
cd /tmp/maj
typeset -i n=\`echo \"SELECT value FROM params WHERE name='supervision_nbr';\"|mysql se3db -N\`
url_maj=\`echo \"SELECT value FROM params WHERE name='supervision_url';\"|mysql se3db -N\`
url_md5=\`echo \"SELECT value FROM params WHERE name='supervision_md5';\"|mysql se3db -N\`
while true
do
fich=\"supervision-maj\$n\"
echo \"téléchargement de \$fich\"
wget -qc \$url_maj/\$fich.tgz
wget -qc \$url_md5/\$fich.md5

if [ -e \$fich.tgz ]; then
      echo \"téléchargement de \$fich terminé\"
      MD5_CONTROL=\`cat \$fich.md5\`
      MD5_FILE=\`md5sum \$fich.tgz\`
      if [ \"\$MD5_CONTROL\" == \"\$MD5_FILE\" ]; then
	  tar -zxf \$fich.tgz
	  ./maj.sh
	  echo \"supervision - mise a jour #\$n terminée\"
      else
	  echo \"Erreur de CRC sur \$fich\"
	  exit 1
      fi
      let n+=1
else
    mysql -D se3db -e \"UPDATE params SET value=\$n WHERE name='supervision_nbr';\"
    echo \"Opération de mise a jour de la supervision terminée\"
    exit 0
fi
done ">/usr/sbin/supervision_rouen.sh



cat >/etc/cron.d/se3_supervision_rouen <<END
30 7 * * 1-5 root /usr/sbin/supervision_rouen.sh >/dev/null 2>&1
END

chmod 700 /usr/sbin/supervision_rouen.sh
}
mysql_cnx="mysql -u $dbuser -p$dbpass -D se3db"
if [ ! -z "$(hostname -d | grep -i "ac-rouen.fr")" ]; then
	echo "Installation des mises a jour / supervision made in rouen"
	paraminst=`echo "SELECT count(*) FROM params WHERE name='supervision_nbr';" | $mysql_cnx -N`
	if [ "$paraminst" = "0" ]; then
		$mysql_cnx -e "INSERT INTO params VALUES ('','supervision_nbr',0,0,'indice de maj supervision',4);"
	fi
	
	paraminst=`echo "SELECT count(*) FROM params WHERE name='supervision_url';" | $mysql_cnx -N`
	if [ "$paraminst" = "0" ]; then
		$mysql_cnx -e "INSERT INTO params VALUES ('','supervision_url','http://lcs.ac-rouen.fr/se3',0,'url de maj supervision',4);"
	fi

	paraminst=`echo "SELECT count(*) FROM params WHERE name='supervision_md5';" | $mysql_cnx -N`
	if [ "$paraminst" = "0" ]; then
		$mysql_cnx -e "INSERT INTO params VALUES ('','supervision_md5','http://wawadeb.crdp.ac-caen.fr/se3/md5',0,'url somme md5 supervision',4);"
	fi
	GENSUPERVISION

fi






