//#include <RFReceiver.h>

//#include <RH_ASK.h>
//#include <SPI.h> // Not actualy used but needed to compile

#include <Wire.h>

#define I2C_SLAVE_ADDRESS 11 // 12 pour l'esclave 2 et ainsi de suite

#define REQTYPE_PWR_VOLT 1
#define REQTYPE_PWR_SRC 2
volatile int reqType = 0;

#define RETCODE_PWRSRC_AC 21
#define RETCODE_PWRSRC_BATT 22
volatile int retCode = 0;

#define CALIBRATION_FULLSCALE 15650.0 //mV
float voltage = 0.0;

#define VOLT_BUF_SIZE 15
float voltBuf[VOLT_BUF_SIZE];
uint8_t voltIter = 0;

#define BTN_PIN 10
#define BLINK_TIME 100
#define RF_TIME 300

//RH_ASK driver (1200,2,3,4);
//RFReceiver receiver(2);

#define SENSOR_COUNT 32
uint8_t sensData[SENSOR_COUNT];
uint32_t sensLastHeard[SENSOR_COUNT];
uint8_t sensHeardAgoSec[SENSOR_COUNT];

void setup() {
  Wire.begin(I2C_SLAVE_ADDRESS);
  Serial.begin(9600);
  Serial.println("-------------------------------------I am Slave1");
  delay(1000);
  Wire.onRequest(requestEvents);
  Wire.onReceive(receiveEvents);

  for (int i = 0; i < VOLT_BUF_SIZE; i++) voltBuf[i] = 0;
  
  analogReference(INTERNAL);
  pinMode(A1, INPUT);

  pinMode(7, INPUT_PULLUP);

  pinMode(BTN_PIN, OUTPUT);

  //if (!driver.init()) Serial.println("init failed");
//  receiver.begin();
  
  for (int i = 0; i< SENSOR_COUNT; i++) {
    sensData[i] = 0;
    sensLastHeard[i] = 0;
  }
}

void replyByte(uint8_t b) {
  //Serial.println(F("---> recieved request"));
  Serial.print(F("sending byte : "));
  Serial.println(b);
  Wire.write(b);
}

void printlnArray(uint8_t arr[]) {
  for(int i = 0; i < SENSOR_COUNT-1; i++) {
    Serial.print(arr[i]);
    Serial.print(", ");
  }
  Serial.println(arr[SENSOR_COUNT-1]);
}

void replySensArray(uint8_t arr[]) {
  Serial.print(F("sending array : "));
  printlnArray(arr);
  Wire.write(arr, SENSOR_COUNT);
}

void replyMaster() {
  switch (reqType) {
    case REQTYPE_PWR_VOLT:
      retCode = round(voltage * 10);
      replyByte(retCode);
      break;

    case REQTYPE_PWR_SRC:
      retCode = digitalRead(7) ? RETCODE_PWRSRC_BATT : RETCODE_PWRSRC_AC;
      replyByte(retCode);
      break;
  }
}

float calcVolt() {
  voltBuf[voltIter] = analogRead(A1);
  float avg = 0;
  for (int i = 0; i < VOLT_BUF_SIZE; i++) avg += voltBuf[i];
  voltIter = (voltIter == VOLT_BUF_SIZE - 1) ? 0 : voltIter + 1;
  //Serial.println(voltIter);
  return CALIBRATION_FULLSCALE / 1023.0 * (avg / float(VOLT_BUF_SIZE)) / 1000.0;
}


uint32_t lastMillisBlink = 0;
boolean blinkState = false;
void blinkButton() {
  uint32_t nowms = millis();
  if (nowms - lastMillisBlink >= BLINK_TIME) {
    blinkState = !blinkState;
    digitalWrite(BTN_PIN, blinkState);
    lastMillisBlink = nowms;
  } else if (nowms < lastMillisBlink) {  //overflow
    lastMillisBlink = nowms;
  }
  
}

uint32_t lastMillisRf = 0;
void rfTimer() {
  uint32_t nowms = millis();
  if (nowms - lastMillisRf >= RF_TIME) {
    //getRf();
    testrf();
    lastMillisRf = nowms;
  } else if (nowms < lastMillisRf) {  //overflow
    lastMillisRf = nowms;
  }
}

//void getRf() {
//    if (!driver.available()) return;
//    uint8_t buf[2];
//    uint8_t buflen = 2;
//    if (driver.recv(buf, &buflen)) // Non-blocking
//    {
//      if (buflen != 2 || buf[0] >= SENSOR_COUNT) return; // Invalid package from sensor
//      sensData[buf[0]] = buf[1];
//      sensLastHeard[buf[0]] = millis();
//    }
//}

void testrf() {
//  if (!receiver.ready()) return;
  
//  char msg[MAX_PACKAGE_SIZE];
  byte senderId = 0;
  byte packageId = 0;
//  byte len = receiver.recvPackage((byte *)msg, &senderId, &packageId);

  Serial.println("");
  Serial.print("Package: ");
  Serial.println(packageId);
  Serial.print("Sender: ");
  Serial.println(senderId);
  Serial.print("Message: ");
//  Serial.println(msg);
}

void loop() {
  voltage = calcVolt();
//  reqType = REQTYPE_PWR_SRC;
//  Serial.println(retCode);
//  updateRetCode();
  blinkButton();
  //testrf();
}

// RPi queries MCU
void requestEvents() {
  reqType = Wire.read();
  //Serial.print(numBytes);
  //Serial.println(F("bytes recieved"));
  Serial.print(F("recieved value : "));
  Serial.println(reqType);
  
  replyMaster();

}

void receiveEvents(int numBytes) {
//  Serial.println(F("---> recieved events"));
//  reqType = Wire.read();
//  Serial.print(numBytes);
//  Serial.println(F("bytes recieved"));
//  Serial.print(F("recieved value : "));
//  Serial.println(reqType);
}
