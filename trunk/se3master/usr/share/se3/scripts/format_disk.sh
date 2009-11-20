#!/bin/bash
[ ! -e /dev/disk/by-id/$1-part1 ] &&  echo "Disque introuvable!"
mkfs.ext3 /dev/disk/by-id/$1-part1 >/dev/null
