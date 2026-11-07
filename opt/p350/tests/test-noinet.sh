#!/bin/bash
cd `dirname $0`
iptables -t filter -A OUTPUT -d 8.8.8.8 -j DROP
echo Inet disabled, ENTER to enable
read -s
iptables -t filter -D OUTPUT -d 8.8.8.8 -j DROP
echo Inet enabled
