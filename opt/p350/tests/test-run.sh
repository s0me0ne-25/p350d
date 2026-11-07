#!/bin/bash
cd `dirname $0`/../

rm -rf ./__pycache__
./build_i2clcd.sh
sudo -u pagerd ./pagerd
