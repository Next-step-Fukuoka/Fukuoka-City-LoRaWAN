/*******************************************************************************
 * Copyright (c) 2015 Thomas Telkamp and Matthijs Kooijman
 *
 * Permission is hereby granted, free of charge, to anyone
 * obtaining a copy of this document and accompanying files,
 * to do whatever they want with them without any restriction,
 * including, but not limited to, copying, modification and redistribution.
 * NO WARRANTY OF ANY KIND IS PROVIDED.
 *H
 * This example sends a valid LoRaWAN packet with payload "Hello,
 * world!", using frequency and encryption settings matching those of
 * the (early prototype version of) The Things Network.
 *
 * Note: LoRaWAN per sub-band duty-cycle limitation is enforced (1% in g1,
 *  0.1% in g2).
 *
 * Change DEVADDR to a unique address!
 * See http://thethingsnetwork.org/wiki/AddressSpace
 *
 * Do not forget to define the radio type correctly in config.h.
 *
 *mydata
 *[0] - [3]
 *[4] - [7]
 *[8] - [9]
 *******************************************************************************/

#include <lmic.h>
#include <hal/hal.h>
#include <SPI.h>
#include <Wire.h>
#include <MsTimer2.h>

#define SHT21_ADDR   0b1000000
#define SHT21_CMD_T  0b11100011
#define SHT21_CMD_RH 0b11100101

#define     pwmPin A0                               /* CO2 data input to pin A0 */
#define     LedPin 13                               /* LED to pin 13 */

// LoRaWAN NwkSKey, network session key
// This is the default Semtech key, which is used by the prototype TTN
// network initially.
//ttn
static const PROGMEM u1_t NWKSKEY[16] = { 0xBE, 0xC4, 0x99, 0xC6, 0x9E, 0x9C, 0x93, 0x9E, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF, 0x02 };
// LoRaWAN AppSKey, application session key
// This is the default Semtech key, which is used by the prototype TTN
// network initially.
//ttn
static const u1_t PROGMEM APPSKEY[16] = { 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00 };
// LoRaWAN end-device address (DevAddr)
// See http://thethingsnetwork.org/wiki/AddressSpace
//ttn
static const u4_t DEVADDR = 0x76FFFF02;


// These callbacks are only used in over-the-air activation, so they are
// left empty here (we cannot leave them out completely unless
// DISABLE_JOIN is set in config.h, otherwise the linker will complain).
void os_getArtEui (u1_t* buf) { }
void os_getDevEui (u1_t* buf) { }
void os_getDevKey (u1_t* buf) { }

static uint8_t mydata[] = "1234567890123**";
static osjob_t initjob,sendjob,blinkjob;

static float temp = 0.0 ;   // variable to store temperature
static float humidity = 0.0 ; // variable to store hemidity
static uint16_t co2 ;

static float befor_temp = 0.0 ;   // variable to store temperature
static float befor_humidity = 0.0 ; // variable to store hemidity
static uint16_t befor_co2 = 0 ;

boolean     prevVal = LOW;
uint32_t    th, tl, h, l, ppm, ppm_now;

uint32_t    ppm_befor = 0L;
uint32_t    tt = 0L;

// Schedule TX every this many seconds (might become longer due to duty
// cycle limitations).
const unsigned TX_INTERVAL = 300;   // 5分

// Pin mapping
const lmic_pinmap lmic_pins = {
    .nss = 10,
    .rxtx = LMIC_UNUSED_PIN,
    .rst = 9,
    .dio = {2, 6, 7},
};

void do_send(osjob_t* j){

//    temp = sht.getTemperature()*100;            // get temp from SHT 
//    humidity = sht.getHumidity()*100;           // get humidity from SHT
    sht21_get();
    temp = temp * 100;
    humidity = humidity * 100;
    co2 = ppm_now ;
   // Check if there is not a current TX/RX job running
    sprintf(mydata,"%04d%04d%04d",(int)temp,(int)humidity,co2 );

    if (LMIC.opmode & OP_TXRXPEND) {
        Serial.println("OP_TXRXPEND, not sending");
    } else {
        // Prepare upstream data transmission at the next possible time.
        LMIC_setTxData2(1, mydata, sizeof(mydata)-1, 0);
        Serial.println("Packet queued");
//        Serial.println(LMIC.freq);
    }
    // Next TX is scheduled after TX_COMPLETE event.
}

void onEvent (ev_t ev) {
//    Serial.print(os_getTime());
//    Serial.print(": ");
//    Serial.println(ev);
    switch(ev) {
        case EV_TXCOMPLETE:
            Serial.println("EV_TXCOMPLETE (includes waiting for RX windows)");
            if(LMIC.dataLen) {
                // data received in rx slot after tx
//                Serial.print("Data Received: ");
//                Serial.write(LMIC.frame+LMIC.dataBeg, LMIC.dataLen);
//                Serial.println();
            }
            // Schedule next transmission
            os_setTimedCallback(&sendjob, os_getTime()+sec2osticks(TX_INTERVAL), do_send);
            break;
        
        case EV_SCAN_TIMEOUT:
//            Serial.println("EV_SCAN_TIMEOUT");
            break;
        case EV_BEACON_FOUND:
//            Serial.println("EV_BEACON_FOUND");
            break;
        case EV_BEACON_MISSED:
//            Serial.println("EV_BEACON_MISSED");
            break;
        case EV_BEACON_TRACKED:
//            Serial.println("EV_BEACON_TRACKED");
            break;
        case EV_JOINING:
//            Serial.println("EV_JOINING");
            break;
        case EV_JOINED:
//            Serial.println("EV_JOINED");
            break;
        case EV_RFU1:
//            Serial.println("EV_RFU1");
            break;
        case EV_JOIN_FAILED:
//            Serial.println("EV_JOIN_FAILED");
            break;
        case EV_REJOIN_FAILED:
//            Serial.println("EV_REJOIN_FAILED");
            break;
        case EV_LOST_TSYNC:
//            Serial.println("EV_LOST_TSYNC");
            break;
        case EV_RESET:
//            Serial.println("EV_RESET");
            break;
        case EV_RXCOMPLETE:
            // data received in ping slot
//            Serial.println("EV_RXCOMPLETE");
            break;
        case EV_LINK_DEAD:
//            Serial.println("EV_LINK_DEAD");
            break;

        case EV_LINK_ALIVE:
//            Serial.println("EV_LINK_ALIVE");
            break;

        default:
//            Serial.println("Unknown event");
            break;
    }
}

void setup() {
    Wire.begin();
    Serial.begin(9600);
    while(!Serial);
//    Serial.println("Starting");
    #ifdef VCC_ENABLE
    // For Pinoccio Scout boards
    pinMode(VCC_ENABLE, OUTPUT);
    digitalWrite(VCC_ENABLE, HIGH);
    delay(1000);
    #endif

    // LMIC init
    os_init();
    // Reset the MAC state. Session and pending data transfers will be discarded.
    LMIC_reset();
    //LMIC_setClockError(MAX_CLOCK_ERROR * 1/100);
    // Set static session parameters. Instead of dynamically establishing a session
    // by joining the network, precomputed session parameters are be provided.
    #ifdef PROGMEM
    // On AVR, these values are stored in flash and only copied to RAM
    // once. Copy them to a temporary buffer here, LMIC_setSession will
    // copy them into a buffer of its own again.
    uint8_t appskey[sizeof(APPSKEY)];
    uint8_t nwkskey[sizeof(NWKSKEY)];
    memcpy_P(appskey, APPSKEY, sizeof(APPSKEY));
    memcpy_P(nwkskey, NWKSKEY, sizeof(NWKSKEY));
    LMIC_setSession (0x1, DEVADDR, nwkskey, appskey);
    #else
    // If not running an AVR with PROGMEM, just use the arrays directly 
    LMIC_setSession (0x1, DEVADDR, NWKSKEY, APPSKEY);
    #endif
    
    // Disable link check validation
    LMIC_setLinkCheckMode(0);

    // TTN uses SF9 for its RX2 window.
    LMIC.dn2Dr = DR_SF9;
    
    // Set data rate and transmit power (note: txpow seems to be ignored by the library)
    LMIC_setDrTxpow(DR_SF7,13);

    // Start job
    do_send(&sendjob);
    MsTimer2::set(1, flash);                            // 1ms毎に割り込みを発生させる
    MsTimer2::start();

}

void loop() {
    os_runloop_once();
}


void sht21_get(){
  uint8_t msb, lsb, chk; // 読み出す3byte
  uint16_t st;           // 計測した生データ

  // 温度読み込みコマンドの送信
  Wire.beginTransmission(SHT21_ADDR);
  Wire.write(SHT21_CMD_T);
  Wire.endTransmission();

  // 結果の3バイトを読み込む
  Wire.requestFrom(SHT21_ADDR,3);
  while (!Wire.available()){}
  msb = Wire.read();
  lsb = Wire.read();
  chk = Wire.read();

  // 計測結果の生データ
  st = msb << 8 | lsb;

  // 摂氏の温度の計算
  temp = -46.85 + 175.72 * (float)st / (16.0 * 16.0 * 16.0 * 16.0);

  // シリアルポートに書き込む
  Serial.println(temp);

  // コマンドの送信
  Wire.beginTransmission(SHT21_ADDR);
  Wire.write(SHT21_CMD_RH);
  Wire.endTransmission();

  // 結果の3バイトを読み込む
  Wire.requestFrom(SHT21_ADDR,3);
  while (!Wire.available()){}
  msb = Wire.read();
  lsb = Wire.read();
  chk = Wire.read();

  // 計測結果の生データ
  st = msb << 8 | lsb;

  // 摂氏の温度の計算
  humidity = -6.0 + 125.0 * (float)st / (16.0 * 16.0 * 16.0 * 16.0);
  // シリアルポートに書き込む
  Serial.println(humidity);

}

void flash()
{
  uint8_t myVal = digitalRead(pwmPin);
  
  tt++ ;
  if (myVal == HIGH) {
    digitalWrite(LedPin, HIGH);
    if (myVal != prevVal) {
      tl = tt ; 
      prevVal = myVal;
      tt =0L;
    }
  }  else {
    digitalWrite(LedPin, LOW);
    if (myVal != prevVal) {
      th = tt; 
      prevVal = myVal;
      ppm = 5000 * (th - 2) / (th + tl - 4);
      ppm_now = ppm_befor * 0.9 + ppm * 0.1 ;
      ppm_befor = ppm_now;
      tt = 0L;
      Serial.println(ppm);
      Serial.println(ppm_now);
    }
  }
}


