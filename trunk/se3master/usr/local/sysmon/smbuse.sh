#!/bin/bash
#/usr/bin/smbstatus -S | grep -v root | grep -v nobody | awk 'NF>6 {print $1}' | sort -u |wc -l |awk -F ' ' '{print $1}'
/usr/bin/smbstatus -S | grep -v root | grep -v nobody | awk 'NF>6 {print $2}' | sort -u |wc -l|awk -F ' ' '{print $1}'
ps auxw |grep smbd |grep -v grep | wc -l |awk -F ' ' '{print $1}'
