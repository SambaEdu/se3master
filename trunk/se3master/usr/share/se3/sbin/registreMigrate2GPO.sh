#!/bin/bash

## $Id$ ##

mysql -e "alter table corresp change os os CHAR(200) DEFAULT 'TOUS' NOT NULL;" se3db;
mysql -e "alter table corresp change antidote antidote CHAR(200) DEFAULT 'TOUS' NOT NULL;" se3db;

mysqldump se3db corresp >/tmp/corresp$$.sql
sed -i 's/TypeXP/2000,XP,Vista,Seven/g' /tmp/corresp$$.sql
sed -i 's/Type9x/Win9x/g' /tmp/corresp$$.sql
mysql -e "delete from corresp;" se3db
mysql se3db < /tmp/corresp$$.sql
rm -f /tmp/corresp$$.sql
