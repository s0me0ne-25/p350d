#!/usr/bin/env python3

# vw.py
# 2015-10-31
# Public Domain

import time
import pigpio
import rh
import json

RX=27
BPS=1200
DEVICE_ADDRESS=1

pi = pigpio.pi() # Connect to local Pi.
rx = rh.rx(pi, RX, BPS) # Specify Pi, rx GPIO, and baud.
rx.set_this_address(DEVICE_ADDRESS) 

while True:
    while rx.ready():
        remote_msg = rx.get()
        print("Raw: %s\n" % json.dumps(remote_msg))
        sensorId = remote_msg["headerFrom"]
        data = remote_msg["msg"][0]
        isPing = bool(remote_msg["headerFlags"] & 0b00000001)
        isOnBattery = bool(remote_msg["headerFlags"] & 0b00000010)
        print("From sensor %d => %d :" % (sensorId, remote_msg["headerTo"]))
        print("\tData: %d (bin: %s)" % (data, '{:08b}'.format(data)))
        print("\tPacket type: %s" % ("Ping" if isPing else "Alert"))
        print("\tSensor power: %s" % ("Battery" if isOnBattery else "Mains"))
        print("\n")
     
    time.sleep(.5)

rx.cancel() # Cancel Virtual Wire receiver.
pi.stop() # Disconnect from local Pi.

