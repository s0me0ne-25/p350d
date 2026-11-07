# Third-party components

p350d is licensed under the GNU General Public License v3.0 (see
[LICENSE](LICENSE)). The components listed below were written by other people
and keep their own licenses. They are distributed alongside p350d as separate
works.

## POCSAG transcode m328 (modulator firmware)

- **Location:** [`third_party/pocsag_transcode_m328/`](third_party/pocsag_transcode_m328/)
- **Author:** Henry Carl Ott, N2RVQ
- **Version:** 3.25
- **License:** **not free software.** The author makes it available for
  educational and non-commercial use only; commercial use requires a license
  from him. The full terms are in
  `pocsag transcode m328 license.txt` in the same folder.
- **Origin:** `http://users.rcn.com/carlott/pocsag_transcode_m328.zip`
  (SHA-256 `649b2784f317936a1d698a0eb1d87463659ea4a36735e9201ea471aab6fcf3dc`).
  The archive is unpacked unchanged, file names included, so that the
  author's license and documentation stay next to the firmware.

The firmware runs on a separate microcontroller and talks to pagerd over a
serial line. It is not part of p350d, is not covered by the GPL, and is
mirrored here intact with the sole purpose of keeping the project reproducible
in case of `users.rcn.com` website outage.

## wiringPi 2.52

- **Location:** [`third_party/wiringpi/`](third_party/wiringpi/)
- **Author:** Gordon Henderson
- **License:** GNU Lesser General Public License v3.0; see
  [`third_party/wiringpi/COPYING.LESSER`](third_party/wiringpi/COPYING.LESSER)
  together with the GPLv3 text in [LICENSE](LICENSE).
- **What is included:** the original armhf binary package, unchanged. It was
  distributed upstream as `wiringpi-latest.deb` and is renamed here to
  `wiringpi-2.52.deb`.
- **Source code:** the author never published the source of release 2.52;
  his last official source release is 2.50. The community repository
  <https://github.com/WiringPi/WiringPi> holds the 2.50 source and a
  reconstruction meant to be source-equivalent to the 2.52 package (commit
  `aca883a`, "Add support for Raspberry Pi 4B").

## rh.py (RadioHead ASK receiver)

- **Location:** `opt/p350/rh.py`; `opt/p350/rh_demo.py` is derived from the
  same example.
- **Based on:** `vw.py` by joan2937, from the pigpio examples.
- **License:** Public Domain, as stated by the original author.
- **Changes:** adapted to the RadioHead ASK packet format (to/from/id/flags
  headers and address filtering).

## i2clcd.c (I²C LCD and power monitor helper)

- **Location:** `opt/p350/i2clcd.c`
- **Based on:** the C port of `lcd_i2c.py` by Lewis Loflin,
  <http://www.bristolwatch.com/rpi/i2clcd.htm>.
- **License:** the original does not state a license. It is published with a
  no-warranty notice, which is kept in the file header. It is used here with
  attribution.
- **Changes:** custom status icons, a mapping for custom characters, and
  queries to the power monitor Arduino.

## gauge.js 1.3.7

- **Location:** `opt/p350/html/gauge.min.js`
- **Author:** Bernard Kobos (bernii) and contributors,
  <https://github.com/bernii/gauge.js>
- **What is included:** the minified distribution file, byte-for-byte
  identical to `dist/gauge.min.js` in the `gaugeJS` 1.3.7 npm package.
- **License:** MIT, as declared in the upstream package metadata. The upstream
  package ships no separate license file. The MIT terms:

  > Permission is hereby granted, free of charge, to any person obtaining a
  > copy of this software and associated documentation files (the
  > "Software"), to deal in the Software without restriction, including
  > without limitation the rights to use, copy, modify, merge, publish,
  > distribute, sublicense, and/or sell copies of the Software, and to permit
  > persons to whom the Software is furnished to do so, subject to the
  > following conditions:
  >
  > The above copyright notice and this permission notice shall be included
  > in all copies or substantial portions of the Software.
  >
  > THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
  > OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
  > MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN
  > NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
  > DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
  > OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE
  > USE OR OTHER DEALINGS IN THE SOFTWARE.

## Status icons (Font Awesome Free)

- **Location:** `opt/p350/html/ac.svg`, `batt.svg`, `txon.svg`, `txoff.svg`,
  `txauto.svg`
- **Based on:** Font Awesome Free icons by Fonticons, Inc.: *plug*,
  *car-battery*, *broadcast-tower* and *left-right*. The icons are recoloured.
  `txoff.svg` is *broadcast-tower* with the radio waves removed.
- **License:** CC BY 4.0, <https://fontawesome.com/license/free>

## Background texture (plastic.jpg)

- **Location:** `opt/p350/html/plastic.jpg`
- **Based on:** the displacement map of the ambientCG material *Plastic 004*,
  <https://ambientcg.com/a/Plastic004>
- **License:** CC0 1.0 Universal (public domain dedication)
- **Changes:** the relief is shaded with a side light to look like textured
  black plastic. It is then resampled to 587×612 px with the tiling kept
  seamless, large-scale variation is removed, and brightness and contrast are
  set for the web UI.
