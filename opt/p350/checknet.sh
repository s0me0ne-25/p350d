#!/bin/bash
# Internet connectivity probe used by pagerd.
#
# Opens a TLS connection to port 443 of the host (Google Public DNS by
# default) and succeeds only if the server presents a certificate issued
# by a trusted CA for that very IP address. Unlike ping, this is not fooled
# by captive portals or by networks that answer ICMP but do not route
# real traffic.
#
# pagerd still calls it with ping-style arguments ("-w3 -n 8.8.8.8"):
# only the last argument, the host, is used.
#
# The certificate validity period is not checked (-no_check_time): the Pi
# has no RTC, and a wrong clock right after boot must not look like a
# network outage.

HOST=${@: -1}
HOST=${HOST:-8.8.8.8}
TIMEOUT=5

timeout "$TIMEOUT" openssl s_client -connect "$HOST:443" \
    -verify_ip "$HOST" -verify_return_error -no_check_time \
    -CApath /etc/ssl/certs \
    </dev/null >/dev/null 2>&1
