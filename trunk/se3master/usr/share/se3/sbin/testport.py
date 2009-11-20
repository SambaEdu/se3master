#!/usr/bin/python
import socket
import sys
sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
sock.settimeout(0.3)
sys.exit (sock.connect ((sys.argv[1], int(sys.argv[2]))))
