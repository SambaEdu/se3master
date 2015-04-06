#!/bin/bash
let a=`cat /proc/meminfo |grep Active: | gawk -F" " '{print $2}'`*100/`cat /proc/meminfo |grep MemTotal | gawk -F" " '{print $2}'`
swaptotal=`cat /proc/meminfo |grep SwapTotal | gawk -F" " '{print $2}'`
swapfree=`cat /proc/meminfo |grep SwapFree | gawk -F" " '{print $2}'`
let s=($swaptotal-$swapfree)*100/$swaptotal
echo $a
echo $s
