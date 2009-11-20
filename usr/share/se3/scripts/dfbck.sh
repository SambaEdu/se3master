#!/bin/bash

df -Ph |grep backuppc | awk '{print $4}'
