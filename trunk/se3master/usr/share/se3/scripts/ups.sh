#!/bin/bash
#
# $Id$
#


/etc/init.d/nut stop
sleep 5

# Pour tout stopper
if [ -f /etc/nut/upsd.stop ]
then
  # Fabrique /etc/default/nut
  echo "START_UPSD=NO" > /etc/default/nut
  echo "START_UPSMON=NO" >> /etc/default/nut
  rm -f /etc/nut/*
  exit
fi


# Verifie la presence de /etc/nut 
if [ ! -d /etc/nut ]
then 
    mkdir /etc/nut
fi

chown -R www-se3.nut /etc/nut  
chmod -R 770  /etc/nut

## Version Debian
if [ -e /etc/debian_version ]
then
        DEBIAN_VERSION=`cat /etc/debian_version`
fi
if [ "$DEBIAN_VERSION" != "3.1" ]
then
        chown nut /dev/ttyS0
        chown nut /dev/ttyS1
	adduser nut dialout >/dev/null
fi


if [ -f /etc/nut/ups.conf -a -f /etc/nut/upsmon.conf ]
then
  # Fabrique /etc/default/nut
  echo "START_UPSD=YES" > /etc/default/nut
  echo "START_UPSMON=YES" >> /etc/default/nut

  /etc/init.d/nut start
fi

if [ ! -f /etc/nut/ups.conf -a -f /etc/nut/upsmon.conf ]
then
  # Fabrique /etc/default/nut
  echo "START_UPSD=NO" > /etc/default/nut
  echo "START_UPSMON=YES" >> /etc/default/nut

  /etc/init.d/nut start
fi

