#!/bin/bash
echo "ACTION==\"add\", KERNEL==\"sd*\", RUN+=\"/usr/share/se3/sbin/mountdisk.sh /dev/disk/by-id/$1-part1\"" >/etc/udev/rules.d/10-se3.rules
echo "ACTION==\"remove\", KERNEL==\"sd*\", RUN+=\"/usr/share/se3/sbin/umountdisk.sh /dev/disk/by-id/$1-part1\"" >>/etc/udev/rules.d/10-se3.rules
/etc/init.d/udev reload >/dev/null
