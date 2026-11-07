#!/usr/bin/env python
# -*- coding: utf-8 -*-
from ctypes import *
import time
import subprocess

#i2clib = CDLL('./i2clcd.so')
#print(i2clib.init())

#print(i2clib.state_lcd())
#print(i2clib.state_ard())
#while True:
#	print(i2clib.queryDuino(0x01))
#	time.sleep(0.3);
#	print(i2clib.queryDuino(0x02))
#	time.sleep(0.3);


print(subprocess.run(["ping", "-c3", "-W1", "8.8.8.7"], universal_newlines=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE).returncode == 0)
