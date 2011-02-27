#!/bin/bash

echo "Complilation admind"
gcc  -o admind admind2.c
cp -v admind /usr/sbin
