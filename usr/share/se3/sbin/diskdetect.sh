#!/bin/bash

#
## $Id$ ##
#
cd /dev/disk/by-id/
ls usb-*| grep -v part
cd - >/dev/null
