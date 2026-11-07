
/*
*
* by Lewis Loflin www.bristolwatch.com lewis@bvu.net
* http://www.bristolwatch.com/rpi/i2clcd.htm
* Using wiringPi by Gordon Henderson
*
*
* Port over lcd_i2c.py to C and added improvements.
* Supports 16x2 and 20x4 screens.
* This was to learn now the I2C lcd displays operate.
* There is no warrenty of any kind use at your own risk.
*
*/

#include <wiringPiI2C.h>
#include <wiringPi.h>
#include <stdlib.h>
#include <stdio.h>

// Define some device parameters
#define I2C_ADDR_LCD   0x27 // LCD address
#define I2C_ADDR_ARD   0x0B // Arduino address

// Define some device constants
#define LCD_CHR  1 // Mode - Sending data
#define LCD_CMD  0 // Mode - Sending command

#define LINE1  0x80 // 1st line
#define LINE2  0xC0 // 2nd line

#define LCD_BACKLIGHT   0x08  // On

#define ENABLE  0b00000100 // Enable bit

void lcd_init(void);
void lcd_byte(int bits, int mode);
void lcd_toggle_enable(int bits);

// added by Lewis
void typeInt(int i);
void typeFloat(float myFloat);
void lcdLoc(int line); //move cursor
void ClrLcd(void); // clr LCD return home
void typeln(const char *s);
void typeChar(char val);
int queryDuino(int command);

int init();

int state_lcd();
int state_ard();

int fd_lcd = -1;
int fd_ard = -1;  // seen by all subroutines

int state_lcd() {
	return fd_lcd;
}

int state_ard() {
	return fd_ard;
}

int init() {
    putenv("WIRINGPI_CODES=1");
    if (wiringPiSetup () == -1) return -1;
    fd_lcd = wiringPiI2CSetup(I2C_ADDR_LCD);
    fd_ard = wiringPiI2CSetup(I2C_ADDR_ARD);
    lcd_init();
    return 0;
}

int queryDuino(int command) {
//	wiringPiI2CWriteReg8 (fd_ard, command, command);
//	delayMicroseconds(500);
	return wiringPiI2CReadReg8(fd_ard, command);
}

// float to string
void typeFloat(float myFloat)   {
	char buffer[20];
	sprintf(buffer, "%4.2f",  myFloat);
	typeln(buffer);
}

// int to string
void typeInt(int i)   {
	char array1[20];
	sprintf(array1, "%d",  i);
	typeln(array1);
}

// clr lcd go home loc 0x80
void ClrLcd(void)   {
	lcd_byte(0x01, LCD_CMD);
	lcd_byte(0x02, LCD_CMD);
}

// go to location on LCD
void lcdLoc(int line)   {
	//if (preinit() == -1) return;
	
	lcd_byte(line, LCD_CMD);
}

// out char to LCD at current position
void typeChar(char val)   {

	lcd_byte(val, LCD_CHR);
}


// this allows use of any size string
void typeln(const char *s)   {
	while ( *s ) {
		char c = *(s++);
		if (c < 9) c--;			// Map custom chars codes from 1..8 to 0..7
		lcd_byte(c, LCD_CHR);
	}
}

void lcd_byte(int bits, int mode)   {

	//Send byte to data pins
	// bits = the data
	// mode = 1 for data, 0 for command
	int bits_high;
	int bits_low;
	// uses the two half byte writes to LCD
	bits_high = mode | (bits & 0xF0) | LCD_BACKLIGHT ;
	bits_low = mode | ((bits << 4) & 0xF0) | LCD_BACKLIGHT ;

	// High bits
	wiringPiI2CReadReg8(fd_lcd, bits_high);
	lcd_toggle_enable(bits_high);

	// Low bits
	wiringPiI2CReadReg8(fd_lcd, bits_low);
	lcd_toggle_enable(bits_low);
}

void lcd_toggle_enable(int bits)   {
	// Toggle enable pin on LCD display
	delayMicroseconds(500);
	wiringPiI2CReadReg8(fd_lcd, (bits | ENABLE));
	delayMicroseconds(500);
	wiringPiI2CReadReg8(fd_lcd, (bits & ~ENABLE));
	delayMicroseconds(500);
}


void lcd_init()   {
	// Initialise display
	lcd_byte(0x33, LCD_CMD); // Initialise
	lcd_byte(0x32, LCD_CMD); // Initialise
	lcd_byte(0x06, LCD_CMD); // Cursor move direction
	lcd_byte(0x0C, LCD_CMD); // 0x0F On, Blink Off
	lcd_byte(0x28, LCD_CMD); // Data length, number of lines, font size
	lcd_byte(0x01, LCD_CMD); // Clear display
	
	//thx https://omerk.github.io/lcdchargen/
	char icons[] = {
	// icon 0 -- no net
	0b00000,
	0b00000,
	0b01110,
	0b11011,
	0b10001,
	0b10001,
	0b11111,
	0b00000,

	//icon 1 -- net ok
	0b00000,
	0b00000,
	0b01110,
	0b11111,
	0b11111,
	0b11111,
	0b11111,
	0b00000,
	
	// icon 2 -- 220V
	0b01010,
	0b01010,
	0b11111,
	0b11111,
	0b01110,
	0b00100,
	0b00100,
	0b00000,

        // icon 3 -- battery
	0b01110,
	0b11111,
	0b10001,
	0b10001,
	0b10001,
	0b10001,
	0b11111,
	0b00000
        };

	lcd_byte(0x40, LCD_CMD); // Icon loading mode
	for (int i = 0; i < sizeof(icons); i++) {
		lcd_byte(icons[i], LCD_CHR); // Push icons to display
	}
	
	delayMicroseconds(500);
}
