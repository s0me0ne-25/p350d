The is a package of files for my pocsag transcoder project.

This is my project to transmit stored and realtime alpha, numeric, and morse ID message. 
You'll need to understand the POCSAG protocol and its addressing scheme. There are plenty of online resources.
   
Documentation is lighter than I would like. But I've been asked to post this and I don't have time to do a thorough job 
of writing up the docs. You'll have to do a bit of work to get everything running. Mostly just reading the protocol 
notes and experimenting.

Some notes:
Source code is not currently available.


You can build the circuirt or you could run this code on one of the many Arduino Mini and Nano boards available. 
Just be sure it uses a atmega328 and a 16MHZ crystal. There are some simple utilities to upload the .hex file to the 
Arduino bootloader without having to install the full Arduino SDK. The code was all written in assembler, the arduino SDK was not used.

The digital AVR pocsag out signal will need to be fed into your transmitter at the correct levels and at the correct circuit location
to achieve the proper TX deviation necessary for proper data transmission and subsequent reliable reception. 

POCSAG decoding does work, but you need to feed a clean digital signal into the AVR. An external data slicer / level shifter 
connected to the correct location of your receiver is mandatory.  

Data polarity is important.

Contact me with any questions or for the terms of a commercial license.

-carl

hcarlott@gmail.com
