#!/bin/bash
cd `dirname $0`
ping $@ | grep "bytes from" > /dev/null
