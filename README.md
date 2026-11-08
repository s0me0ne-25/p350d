**English** | [Русский](README.ru.md)

# p350d — POCSAG paging station for Raspberry Pi

p350d is the software half of a self-contained POCSAG paging station built
around a Raspberry Pi, two Arduino boards and an ordinary FM transmitter. It
sends text messages to alphanumeric pagers from a web form or a console. It
also watches over the site it is installed at: if mains power drops, the
Internet link goes down or someone presses the alarm button, the station pages
the alert by itself.

The station has been in continuous service since 2021 on a first-generation
Raspberry Pi. This repository publishes that production setup as is.

## Features

- **Paging.** Alphanumeric POCSAG at 512, 1200 or 2400 baud through an
  external modulator. Text is converted to KOI-7, the charset used by
  Cyrillic pagers such as the Motorola Advisor.
- **Reliable delivery.** Every message is repeated several times under one
  preamble, then retransmitted periodically until the retry limit is reached.
  The number of repeats is capped so that a transmission always fits into the
  transmitter time-out (TOT).
- **Site monitoring with automatic alerts:**
  - mains power loss, reported with the current battery voltage;
  - Internet connectivity loss;
  - a manual alarm button.

  An alert is raised only after the condition holds for several consecutive
  checks. A recovery message is sent when the condition clears. Alerts from the
  last 24 hours are kept in an archive and summarised in periodic digests.
- **Battery protection.** In automatic mode the transmitter is disabled when
  the battery runs low and enabled again when it recovers, with hysteresis.
  The voltage drop during each transmission is measured for diagnostics.
- **Persistent state.** The retransmission queue, active alerts and the
  archive survive daemon restarts and power cuts.
- **Interfaces:**
  - a retro "pager" web form for sending messages;
  - an admin page with a battery gauge, transmitter and queue controls, and a
    live log;
  - a console client;
  - a 16×2 LCD on the station itself.
- **Hardware power switch.** Turning it off shuts the Pi down cleanly. A
  delay relay then cuts power to the station.
- **Wireless sensors (experimental).** A 433 MHz ASK receiver speaking the
  RadioHead protocol. Received packets are currently only logged.

## Disclaimer

You are responsible for complying with the radio regulations of your country.
Transmit only on frequencies you are authorised to use. The modulator firmware
can append a Morse station ID to transmissions, which may be required for
amateur radio use (see the modulator's protocol notes).

## Project status and scope

The hardware platform and underlying software stack is deliberately frozen. My
p350d installation is an embedded integrated appliance which works quite well the
way it was assembled back in 2021. The station runs on Raspbian 11 (Bullseye) on
a Raspberry Pi 1 with RPi.GPIO, pigpio and wiringPi. These libraries are not 
supported on newer releases of Raspberry Pi OS or on the Raspberry Pi 5, and 
porting is out of scope. The code is published as it runs in production.

Bug reports and small, focused fixes are welcome. Large refactoring is not
planned, because every change has to be checked on real hardware.

## Hardware

```
                UART 9600 8N1                  data + PTT
 Raspberry Pi ◄──────────────► Modulator ────────────────► FM transmitter
   (pagerd)    level dividers  Arduino #1, N2RVQ firmware  (e.g. Motorola GM350)
      │
      │ I²C (active level shifter)
      ├──► LCD 16×2, HD44780 + PCF8574, address 0x27
      └──► Power monitor, Arduino #2, address 0x0B
                ├── A1  ◄── battery voltage divider
                ├── D7  ◄── "mains present" signal from the PSU/UPS
                └── D10 ──► alarm button LED (blinking)

 GPIO4  ◄── power switch
 GPIO17 ◄── alarm button
 GPIO27 ◄── 433 MHz ASK receiver (wireless sensors)
```

| Component | Notes |
|---|---|
| Raspberry Pi | Any model supported by Raspbian Bullseye. The reference station uses a Raspberry Pi 1. |
| Modulator | ATmega328 Arduino with a 16 MHz crystal (Nano, Pro Mini, etc.) running Henry Carl Ott N2RVQ's *pocsag transcode* firmware. It generates the POCSAG bit stream and keys the transmitter. Schematic, fuses and protocol notes are in [`third_party/pocsag_transcode_m328/`](third_party/pocsag_transcode_m328/). |
| Transmitter | Motorola GM350 or any FM transmitter with a flat (unfiltered) modulator input. Data polarity and deviation must be set correctly for pagers to decode reliably. |
| Power monitor | ATmega168 or ATmega328 Arduino running [`pager350_i2c.ino`](opt/p350/arduino-firmware/pager350_i2c.ino). |
| LCD | HD44780 16×2 with a PCF8574 I²C backpack. |
| Level shifting | An active shifter on I²C (Pi 3.3 V ↔ 5 V Arduinos and LCD). Resistive dividers on both serial lines between the Pi and the modulator. |
| Alarm button | A latching button with a built-in LED and two contact groups. One group connects GPIO17 to ground. The other connects the LED to the blinking D10 output, so the LED flashes while the button is latched. Releasing the latch clears the alarm. |
| Power switch | In the OFF position it does two things. It connects GPIO4 to ground, so the daemon shuts the Pi down. It also starts a delay relay that then cuts power to the whole station. Set the delay well above the time the Pi needs to shut down; the reference station uses about 1.5 minutes. |
| Battery | 12 V lead-acid (6 cells, up to 14.5 V), connected to A1 through a voltage divider. |
| UPS / PSU | Must provide a logic signal that tells the power monitor whether mains is present. On D7, HIGH means "on battery". |

### Pin map

**Raspberry Pi (BCM numbering)**

| GPIO | Function |
|---|---|
| 14 / 15 | UART TXD / RXD to the modulator (`/dev/ttyAMA0`) |
| 2 / 3 | I²C SDA / SCL |
| 4 | Power switch (input, pull-up; LOW = off → `poweroff`) |
| 17 | Alarm button (input, pull-up; LOW = alarm) |
| 27 | ASK receiver data (input) |

**Power monitor Arduino**

| Pin | Function |
|---|---|
| A4 / A5 | I²C SDA / SCL (slave address 0x0B) |
| A1 | Battery voltage through a divider. The internal 1.1 V reference is used. |
| D7 | Mains present (input, pull-up; HIGH = on battery) |
| D10 | Square wave for the alarm button LED (toggles every 100 ms) |

The battery divider has to be calibrated. Adjust `CALIBRATION_FULLSCALE` (in
millivolts) in the sketch until the voltage reported by the station matches a
multimeter.

## Software platform

Reference versions from the production station:

| Component | Version |
|---|---|
| OS | Raspbian GNU/Linux 11 (Bullseye), image 2021-10-30, armhf |
| Kernel | 6.1.21+ |
| Python | 3.9 |
| nginx | 1.18.0 |
| PHP-FPM | 7.4.33 |
| wiringPi | 2.52 (binary package in [`third_party/wiringpi/`](third_party/wiringpi/)) |
| pigpio / pigpiod | 1.79 |
| RPi.GPIO | 0.7.0 |
| pyserial | 3.5 (pip) |
| tendo | 0.2.15 (pip) |

## Repository layout

The repository mirrors the target filesystem: files under `opt/` and `etc/`
go to the same paths on the Pi.

```
opt/p350/                      → /opt/p350
├── pagerd                     main daemon (Python 3)
├── pagerd_config.py.defaults  default station settings
├── rh.py                      RadioHead ASK receiver on top of pigpio
├── rh_demo.py                 standalone sensor receiver demo
├── i2clcd.c                   LCD and power monitor I²C helper (built into i2clcd.so)
├── build_i2clcd.sh            builds i2clcd.so against wiringPi
├── checknet.sh                Internet connectivity probe
├── p350-cli                   interactive console client
├── p350-logd                  log collector for the web UI
├── p350-restart, p350-viewlog service helpers
├── reset_pconf.json           clean runtime state
├── arduino-firmware/          power monitor sketch
├── html/                      web UI: message form (/) and admin page (/admin)
└── tests/                     manual hardware checks
etc/
├── systemd/system/            p350d.service, p350d-log@.service
├── nginx/sites-available/     p350d site
├── php/7.4/fpm/conf.d/        PHP-FPM hardening settings
├── sudoers.d/p350d            lets the daemon power the Pi off
└── cron.d/p350d               periodic restart of the log collectors
third_party/
├── pocsag_transcode_m328/     modulator firmware (NOT covered by the GPL)
└── wiringpi/                  wiringPi 2.52 binary package (LGPLv3)
```

## Installation

All commands below run on the Pi from a clone of this repository.

1. **Operating system.** Install Raspberry Pi OS / Raspbian 11 (Bullseye),
   32-bit, from the Raspberry Pi OS image archive.

2. **Interfaces.** In `sudo raspi-config`, open *Interface Options*:
   - enable **I2C**;
   - in **Serial Port**, answer **No** to the login shell over serial and
     **Yes** to the serial port hardware.

   Reboot.

3. **Packages.** wiringPi 2.52 is not in the Raspbian repositories. Install
   it from the package in `third_party/wiringpi/`:

   ```sh
   sudo apt install nginx php7.4-fpm pigpio python3-pigpio python3-rpi.gpio \
                    python3-pip netcat-openbsd
   sudo dpkg -i third_party/wiringpi/wiringpi-2.52.deb
   sudo pip3 install pyserial==3.5 tendo==0.2.15
   ```

4. **Service account.** The daemon needs these groups:
   - `dialout` for the UART;
   - `i2c` for the I²C bus;
   - `gpio` for GPIO;
   - `systemd-journal` so the log collector can read the journal.

   ```sh
   sudo adduser --system --group --no-create-home pagerd
   sudo usermod -aG dialout,i2c,gpio,systemd-journal pagerd
   ```

5. **Files.** Copy the daemon, build the I²C library, and install the systemd
   units, sudoers rule and cron job:

   ```sh
   sudo cp -r opt/p350 /opt/
   sudo chown -R pagerd:pagerd /opt/p350
   sudo -u pagerd /opt/p350/build_i2clcd.sh

   sudo cp etc/systemd/system/p350d.service etc/systemd/system/p350d-log@.service /etc/systemd/system/
   sudo install -m 0440 etc/sudoers.d/p350d /etc/sudoers.d/p350d
   sudo visudo -c
   sudo install -m 0644 etc/cron.d/p350d /etc/cron.d/p350d
   ```

6. **Web server.** Install the p350d site and the PHP hardening settings,
   disable the default site, and reload nginx and PHP-FPM:

   ```sh
   sudo cp etc/nginx/sites-available/p350d /etc/nginx/sites-available/
   sudo ln -s /etc/nginx/sites-available/p350d /etc/nginx/sites-enabled/p350d
   sudo rm /etc/nginx/sites-enabled/default
   sudo install -m 0644 etc/php/7.4/fpm/conf.d/99-p350d-hardening.ini /etc/php/7.4/fpm/conf.d/
   sudo nginx -t && sudo systemctl reload nginx
   sudo systemctl restart php7.4-fpm
   ```

7. **Firmware.** Flash both Arduinos:
   - **Modulator:** `pocsag_transcode_m328_3.25.hex`. On an Arduino board you
     can upload it through the bootloader with avrdude. On a bare ATmega328,
     program the fuses from `fuses.txt` with an ISP programmer.
   - **Power monitor:** `opt/p350/arduino-firmware/pager350_i2c.ino` from the
     Arduino IDE. Calibrate the divider afterwards (see [Hardware](#hardware)).

   The daemon sends the modulator its baud rate, PTT polarity and keying delays
   (`PREKEY_MS`, `POSTKEY_MS`) before every transmission, so the modulator
   needs no manual setup.

8. **Configuration.** Create the station settings from the defaults, then
   edit `/opt/p350/pagerd_config.py`. At the very least set your capcodes,
   and replace the placeholder logo (see [Configuration](#configuration)).

   ```sh
   sudo -u pagerd cp /opt/p350/pagerd_config.py.defaults /opt/p350/pagerd_config.py
   ```

9. **Start.**

   ```sh
   sudo systemctl daemon-reload
   sudo systemctl enable --now pigpiod p350d p350d-log@all p350d-log@msg
   ```

   Open `http://<pi-address>/` to send a message and
   `http://<pi-address>/admin/` to manage the station.

> **Security.** The web interface has no authentication. Anyone who can reach
> it can send pages, turn off the transmitter and clear the queues. Keep the
> station on a trusted network, or enable HTTP basic authentication in nginx
> (`auth_basic` / `auth_basic_user_file`).

> **PHP 7.4** no longer receives security fixes. The site and the PHP
> settings above mitigate the October 2026 PHP vulnerabilities
> CVE-2026-109618, CVE-2026-109619 and CVE-2026-115560: session upload
> progress is disabled in PHP-FPM, and nginx refuses `multipart/form-data`
> requests and bodies larger than 16 KB before they reach PHP. The web UI
> needs neither. These are mitigations, not fixes.

## Configuration

Station settings live in `/opt/p350/pagerd_config.py`. This file belongs to
the station and is not tracked by git, so updates never overwrite it.
`pagerd_config.py.defaults` next to it holds the defaults and a comment for
every setting.

- **No `pagerd_config.py`.** pagerd creates it from the defaults on start.
  The defaults contain placeholder capcodes, so create and edit the file
  before the first start.
- **The file cannot be loaded, or a setting is missing.** pagerd refuses to
  start and names the problem in the log. When an update adds a new setting,
  copy it from the defaults.
- **Every start.** All settings are printed to the log, together with a
  warning about any unknown name, which is usually a typo.

The main settings:

| Setting | Default | Meaning |
|---|---|---|
| `INSTALLATION_NAME` | `'P350'` | Station name shown by the console client |
| `SERIAL` | `/dev/ttyAMA0` | UART connected to the modulator |
| `BAUD_RATE` | `512` | POCSAG baud rate: 512, 1200 or 2400 |
| `CAPCODE` | `1234567` | Capcode for messages and alerts |
| `CAPCODE_BACKGROUND` | `1765432` | Capcode for periodic status digests |
| `MSGTYPE` | `3` | POCSAG function code for alphanumeric messages |
| `DUMMY_CAPCODE`, `DUMMY_MSGTYPE` | `1111111`, `0` | Address used for keep-alive transmissions |
| `PING_INTERVAL` | `120` | Seconds between scheduled transmission cycles |
| `RETX_COUNT` | `50` | How many times a message is retransmitted after the first send |
| `RETX_COOLDOWN` | `40` | Seconds between retransmissions, so the transmitter can cool down |
| `REPEAT` | `3` | Copies of a message sent under one preamble (1–10) |
| `TX_TOT_LIMIT` | `10` | Maximum length of a transmission in seconds, keying delays included; `REPEAT` is reduced to fit |
| `PREKEY_MS`, `POSTKEY_MS` | `100`, `100` | Delay between keying the transmitter and the start of data, and between the end of data and unkeying, in milliseconds (0–2550, in 10 ms steps) |
| `THRESHOLD`, `HYSTER` | `11.0`, `0.2` | Low-battery cutoff and its hysteresis, in volts |
| `CONTROL_PORT` | `16032` | Control socket port, bound to 127.0.0.1 only |
| `STARTMSG`, `STOPMSG` | `'СТАНЦИЯ ЗАПУЩЕНА'`, `'СТАНЦИЯ ОТКЛЮЧАЕТСЯ'` | Paged when the daemon starts ("station started") and stops ("station stopping") |
| `GPIO_POWER_SWITCH`, `GPIO_RED_BUTTON`, `RX` | `4`, `17`, `27` | GPIO pins (BCM numbering) |

Capcodes must be between 8 and 2097151. Alert texts are in Russian and are
defined in `pagerd`.

**Logo.** The web interface ships with a placeholder logo. Replace
`/opt/p350/html/logo.png` with your own image, keeping the same format and
size: PNG with transparency (RGBA), 600×227 px. Both pages display it 400 px
wide, so no code changes are needed.

Runtime state is stored in `/opt/p350/pconf.json`:
- transmitter mode;
- silent-restart flag;
- retransmission queue;
- active and archived alerts.

The file is written atomically. If it turns out to be corrupted, it is set
aside and the defaults are used. To reset the station, stop the daemon and copy
`reset_pconf.json` over `pconf.json`.

## Operation

### Transmission cycle

Every `PING_INTERVAL` seconds the station runs a transmission cycle:

- active alerts and pending retransmissions are sent to `CAPCODE`;
- a status digest is sent to `CAPCODE_BACKGROUND`. It reads `СВОДКА` ("summary")
  with "no alerts" if nothing happened in the last 24 hours, or `АРХИВ`
  ("archive") followed by the list of past alerts.

The regular digest also serves as a keep-alive. It shows that the station is
on the air even when there is nothing to report.

Paging has no delivery confirmation. A pager that was out of range or switched
off would simply miss a message. To compensate, the station retransmits user
messages and its own start/stop notices up to `RETX_COUNT` times, once per
cycle. With the default settings that is about 100 minutes.

Alerts do not go into this queue. While an alert is active, it is sent again in
every cycle anyway, and after it clears it stays in the archive digest for
24 hours.

### Alerts

| Alert | Raised after | Start message | Clear message |
|---|---|---|---|
| Mains power loss | 10 checks (~5 s) | `НЕТ 220 БАТ=<volts>` ("no 220 V, battery = …") | `220 ОК` |
| Internet loss (TLS to 8.8.8.8:443) | 60 checks (~30 s) | `НЕТ СЕТИ` ("no network") | `СЕТЬ ОК` |
| Alarm button latched | 8 checks (~4 s) | `ТРЕВОЖНАЯ КНОПКА` ("alarm button") | `СБРОС КНОПКИ` ("button reset") |

Every alert message carries a timestamp. Resolved alerts go to the archive with
their start and end times, and the archive appears in digests for 24 hours.

### Transmitter modes

| Mode | Behaviour |
|---|---|
| `1` | Always enabled |
| `0` | Always disabled. Nothing is transmitted, but messages are still logged and their retransmission counters keep running down. |
| `auto` | Disabled below `THRESHOLD − HYSTER`, enabled again above `THRESHOLD + HYSTER` |

### LCD

- **Line 1:** power source icon (mains or battery), battery voltage and network
  status icon.
- **Line 2:** time until the next scheduled transmission (`Schd TX in mm:ss`),
  `TX DISABLED`, or what is being sent right now (`TX > <capcode>`,
  `ReTX > <capcode>`, `TX ping`).

### Console and control socket

`p350-cli` is an interactive shell for the control socket. Each command is a
single line, sent over its own TCP connection to `127.0.0.1:16032`:

| Command | Action |
|---|---|
| `page <text>` | Send a message to `CAPCODE` |
| `power` | Show voltage, power source, threshold and the voltage drop on the last transmission |
| `jps` | Power and transmitter state as JSON (used by the admin page) |
| `txenable 0\|1\|auto` | Set the transmitter mode |
| `silent-restart 0\|1` | Do not send start/stop messages when the daemon restarts |
| `get-pconf` | Dump the runtime state as JSON |
| `clrq` | Clear the retransmission queue |
| `clra` | Clear the alert archive |
| `help` | List the commands |

Logs go to the systemd journal: `p350-viewlog`, or `journalctl -u p350d`.
`p350-restart` restarts the daemon and follows the log.

## Field experience

The reference station transmits at 40 W into a half-wave dipole about 50 m
above ground. Motorola Advisor pagers receive it reliably within roughly 20 km,
including dense urban areas.

The station runs from an Accordtec ББП-60 UPS. This budget model has no
dedicated "mains present" output, so the signal for D7 is taken from its
"Mains" LED through an optocoupler. More expensive models of the same series
provide such an output directly.

## Known limitations

- No authentication on the web interface or the control socket (see
  [Installation](#installation)).
- Connectivity is checked against a single host: a TLS handshake with
  8.8.8.8:443 (Google Public DNS), every few seconds.
- The daemon does not read the modulator's replies, so modulator-side errors
  such as a full transmit buffer go unnoticed.
- Locking between the daemon's worker threads is minimal. This has caused no
  problems in years of production use, but heavy concurrent use (for example,
  many messages at once from several clients) is untested.
- The Pi 5, Bookworm and newer systems are not supported (see
  [Project status and scope](#project-status-and-scope)).
- Message text is converted to KOI-7 for Cyrillic pagers. Lowercase Latin
  letters may be shown as Cyrillic on such pagers.

## Credits

- **Henry Carl Ott, N2RVQ:** modulator firmware.
- **joan2937:** `vw.py` from pigpio, the basis of `rh.py`.
- **Lewis Loflin:** the I²C LCD code that `i2clcd.c` is derived from.
- **Bernard Kobos:** [gauge.js](https://bernii.github.io/gauge.js/).
- **Gordon Henderson:** wiringPi.
- **Fonticons, Inc.:** [Font Awesome Free](https://fontawesome.com/) icons,
  the basis of the status icons.

## License

p350d is free software, licensed under the GNU General Public License v3.0;
see [LICENSE](LICENSE).

Third-party components keep their own licenses; see
[THIRD_PARTY.md](THIRD_PARTY.md). In particular, the modulator firmware in
`third_party/pocsag_transcode_m328/` is **not** free software and is not
covered by the GPL. It is provided for educational and non-commercial use only,
under its author's terms (see `pocsag transcode m328 license.txt` in that
folder). The folder holds the author's original archive, unpacked unchanged.
