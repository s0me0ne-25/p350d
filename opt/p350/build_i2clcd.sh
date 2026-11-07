#!/bin/bash
cd `dirname $0`

rm -f i2clcd.so
gcc -shared -Wl,-soname,i2clcd -o i2clcd.so -fPIC i2clcd.c -lwiringPi
