#!/bin/bash

/bin/rm -r /var/cache/se3_install
tar -xzf /tmp/se3_install2.tgz -C /var/cache
cd /var/cache/se3_install
./maj_se.sh
