#include <esp_task_wdt.h>

//---- Website ----
#include <HTTPClient.h>
#include <WiFi.h>
#include <WiFiMulti.h>

const int ledPin = 33;
const int ledPin2 = 25;

HTTPClient http;
String Link, LinkTiming, Mode, Status, payload;
int httpCode; 

WiFiMulti wifiMulti;

bool arcDetected = false;
bool uidRead = false;
String uid = "RONSTAN-2";
String weldID;
String currentTime;
unsigned long arcInitiatedTime = 0;
bool arcInitiated = false;
unsigned long arcStartTime = 0; // Variable to track the start time of arc initiation

float sumCurrentDC = 0;
int counterCurrentDC = 0;
bool initializationDone = false;

float averageCurrentDC = 0.0;  // Variable to store the average currentDC during arc-on period

const char *ssid1 = "MetronARC Office";
const char *password1 = "2468g0a7";
const char *host1 = "test.sparcmonitoring.com";

const char *ssid2 = "MetronARC";
const char *password2 = "2468g0a7";
const char *host2 = "ronstan.sparcmonitoring.com";

const char *ssid3 = "MetronARCs Technology";
const char *password3 = "2468g0a7A7B7*";
const char *host3 = "ronstan.sparcmonitoring.com";

const char* host = "";
String machineID = "RONSTAN-2";
byte statusKoneksi;

int area = 1;

const float FACTOR = 50; //50A/1V from teh CT
const float multiplier = 0.0000394;
int16_t currentRMS, currentDC, newCurrentDC;

long millisSebelum;
byte detik, menit, jam;
String detikS, menitS, jamS, jmdS;
byte statusWaktu;
String statusT;

unsigned long previousMillis = 0;
const int period = 1;

const float ESPVCC = 3.3; // Voltage of your Arduino
const unsigned long VALUE_R1 = 21500;
const unsigned long VALUE_R2 = 3280;
const double VOLTAGE_SOURCE = 25.4;
const int ANALOG_PIN = 32;
const int INPUT_RESOLUTION = 4096;
const float AVERAGE_OF = 500;

int analogValue;
int voltageMillivolts;
float voltage;
float calibration;
char voltageString[10]; // Global variable for voltage string
float voltageAverage;

unsigned long previousCheckTimeMillis = 0;
const unsigned long checkTimeInterval = 6000; // 10 seconds in milliseconds

void heartBeat() {
  String startReading = "heartBeat";
  Link = "https://" + String(host) + "/backEnd/checkTime.php?State=" + startReading + "&MachineID=" + machineID + "&weldID=" + weldID;

  int retryCount = 0;
  const int maxRetries = 10;  // Maximum number of retries before resetting the ESP32
  unsigned long retryInterval = 1000;  // Interval between retries in milliseconds
  unsigned long retryStartTime = millis();  // Store the start time for retries

  while (true) {
    http.begin(Link);
    httpCode = http.GET();

    if (httpCode == 200) {
      payload = http.getString();
      Serial.println("HTTP GET response: " + String(httpCode));
      
      payload.trim();
      Serial.println("Payload: " + payload);

      currentTime = payload;
      http.end();
      break;  // Exit the loop if the response is successful
    } else {
      Serial.println("HTTP GET failed. Retrying...");
      retryCount++;  // Increment the retry counter

      // Wait for the retry interval before trying again
      while (millis() - retryStartTime < retryInterval) {
        // Do nothing, just wait for the retry interval to pass
      }
      retryStartTime = millis();  // Reset the start time for the next retry

      if (retryCount >= maxRetries) {
        Serial.println("HTTP GET failed after maximum retries. Resetting ESP32...");
        delay(2000);  // Wait for 2 seconds before resetting (keeping this delay for resetting)
        ESP.restart();  // Reset the ESP32
      }
    }
  }
}

void firstHeartBeat() {
  String startReading = "firstHeartBeat";
  Link = "https://" + String(host) + "/backEnd/checkFirst.php?State=" + startReading + "&MachineID=" + machineID + "&Area=" + area + "&weldID=" + weldID + "&UID=" + uid;

  int retryCount = 0;
  const int maxRetries = 10;
  unsigned long retryStartTime = 0;
  bool retrying = false;

  while (true) {
    if (!retrying || millis() - retryStartTime >= 1000) { // 1 second retry interval
      http.begin(Link);
      int httpCode = http.GET();

      if (httpCode == 200) {
        String payload = http.getString();
        Serial.println("HTTP GET response: " + String(httpCode));
        payload.trim();
        Serial.println("Payload: " + payload);

        currentTime = payload;
        http.end();
        break;
      } else {
        Serial.println("HTTP GET failed. Retrying...");
        retrying = true;
        retryStartTime = millis();
        retryCount++;

        if (retryCount >= maxRetries) {
          Serial.println("HTTP GET failed after maximum retries. Resetting ESP32...");
          delay(2000);
          ESP.restart();
        }
      }
    }
  }
}


void checkWeldID() {
  String startReading = "Start";
  Link = "https://" + String(host) + "/backEnd/checkWeldID.php?State=" + startReading + "&MachineID=" + machineID;

  int retryCount = 0;
  const int maxRetries = 10;  // Maximum number of retries before resetting the ESP32
  unsigned long retryInterval = 1000;  // 1 second interval between retries
  unsigned long lastRetryTime = 0;     // Timestamp of the last retry

  while (true) {
    if (millis() - lastRetryTime >= retryInterval) {
      lastRetryTime = millis();
      
      http.begin(Link);
      int httpCode = http.GET();

      if (httpCode == 200) {
        String payload = http.getString();
        Serial.println("HTTP GET response: " + String(httpCode));
        
        payload.trim();
        Serial.println("Payload: " + payload);

        weldID = payload;
        http.end();
        break;  // Exit the loop if the response is successful
      } else {
        Serial.println("HTTP GET failed. Retrying...");
        retryCount++;  // Increment the retry counter

        if (retryCount >= maxRetries) {
          Serial.println("HTTP GET failed after maximum retries. Resetting ESP32...");
          delay(2000);  // Wait for 2 seconds before resetting
          ESP.restart();  // Reset the ESP32
        }
      }
    }
  }
}


void httpGet()
{
  http.begin(Link);
  httpCode = http.GET();
  payload = http.getString();
  Serial.println("HTTP GET response: " + String(httpCode));
  Serial.println(payload);
  http.end();
}

void kirimAwal() {
  Status = "Inactive";
  Mode = "";
  Link = "https://" + String(host) + "/backEnd/kirimInfo2.php?UID=" + uid + "&Status=" + Status + "&Mode=" + Mode + "&Area=" + area + 
  "&weldID=" + weldID + "&MachineID=" + machineID;

    http.begin(Link);
    httpCode = http.GET();

      payload = http.getString();
      Serial.println("HTTP GET response: " + String(httpCode));
      Serial.println(payload);
      http.end();
}

void sendLogin() {
  Status = "Active";
  Mode = "Login";

  Serial.println("First Tap");

  Link = "https://" + String(host) + "/backEnd/kirimInfo2.php?UID=" + uid + "&Status=" + Status + "&Mode=" + Mode + "&Area=" 
  + area + "&weldID=" + weldID + "&MachineID=" + machineID + "&Timing=" + jmdS;

  int retryCount = 0;
  const int maxRetries = 10;  // Maximum number of retries before resetting the ESP32
  unsigned long retryInterval = 1000;  // 1 second interval between retries
  unsigned long lastRetryTime = 0;     // Timestamp of the last retry

  // Loop until a successful HTTP response is received
  while (true) {
    if (millis() - lastRetryTime >= retryInterval) {
      lastRetryTime = millis();
      
      http.begin(Link);
      int httpCode = http.GET();

      if (httpCode == 200) {
        String payload = http.getString();
        Serial.println("HTTP GET response: " + String(httpCode));
        Serial.println(payload);
        http.end();
        break;  // Exit the loop if the response is successful
      } else {
        Serial.println("HTTP GET failed. Retrying...");
        retryCount++;  // Increment the retry counter

        if (retryCount >= maxRetries) {
          Serial.println("HTTP GET failed after maximum retries. Resetting ESP32...");
          delay(2000);  // Wait for 2 seconds before resetting
          ESP.restart();  // Reset the ESP32
        }
      }
    }
  }
}

void kirimArcOn() {
  Status = "ArcOn";
  Link = "https://" + String(host) + "/backEnd/kirimWaktu2.php?Timing=" + jmdS + "&Status=" + Status + "&MachineID=" + machineID +
         "&weldID=" + weldID + "&Area=" + area + "&UID=" + uid + "&Status=" 
         + Status + "&currentDC=" + currentDC + "&Mode=" + Mode;

  int retryCount = 0;
  const int maxRetries = 10;  // Maximum number of retries before resetting the ESP32
  unsigned long retryInterval = 1000;  // 1 second interval between retries
  unsigned long lastRetryTime = 0;     // Timestamp of the last retry

  // Loop until a successful HTTP response is received
  while (true) {
    if (millis() - lastRetryTime >= retryInterval) {
      lastRetryTime = millis();

      http.begin(Link);
      int httpCode = http.GET();

      if (httpCode == 200) {
        String payload = http.getString();
        Serial.println("HTTP GET response: " + String(httpCode));
        Serial.println(payload);
        http.end();
        break;  // Exit the loop if the response is successful
      } else {
        Serial.println("HTTP GET failed. Retrying...");
        retryCount++;  // Increment the retry counter

        if (retryCount >= maxRetries) {
          Serial.println("HTTP GET failed after maximum retries. Resetting ESP32...");
          delay(2000);  // Wait for 2 seconds before resetting
          ESP.restart();  // Reset the ESP32
        }
      }
    }
  }
}

void kirimArcOff() {
  Status = "ArcOff";

  // Include the average CurrentDC in the request
  Link = "https://" + String(host) + "/backEnd/kirimWaktu2.php?Timing=" + jmdS + "&Status=" + Status + "&MachineID=" + machineID + 
         "&Area=" + area + "&UID=" + uid + "&Status=" + Status + "&currentDC=" + averageCurrentDC + "&weldID=" + weldID + "&voltageAverage=" + String(voltageAverage);

  int retryCount = 0;
  const int maxRetries = 10;  // Maximum number of retries before resetting the ESP32
  unsigned long retryInterval = 1000;  // 1 second interval between retries
  unsigned long lastRetryTime = 0;     // Timestamp of the last retry

  while (true) {
    if (millis() - lastRetryTime >= retryInterval) {
      lastRetryTime = millis();

      http.begin(Link);
      int httpCode = http.GET();

      if (httpCode == 200) {
        String payload = http.getString();
        Serial.println("HTTP GET response: " + String(httpCode));
        Serial.println(payload);
        http.end();
        break;  // Exit the loop if the response is successful
      } else {
        Serial.println("HTTP GET failed. Retrying...");
        retryCount++;  // Increment the retry counter

        if (retryCount >= maxRetries) {
          Serial.println("HTTP GET failed after maximum retries. Resetting ESP32...");
          delay(2000);  // Wait for 2 seconds before resetting
          ESP.restart();  // Reset the ESP32
        }
      }
    }
  }
  
  // Reset averageCurrentDC for the next arc-on period
  averageCurrentDC = 0.0;
}


void kirimArcCheck() {
  Status = "ArcCheck";
  Link = "https://" + String(host) + "/backEnd/kirimWaktu2.php?Timing=" + jmdS + "&Status=" + Status + "&MachineID=" + machineID +
         "&weldID=" + weldID + "&Area=" + area + "&UID=" + uid + "&Status=" + Status + "&currentDC=" + currentDC + "&Mode=" + Mode;

    http.begin(Link);
    int httpCode = http.GET();

      payload = http.getString();
      Serial.println("HTTP GET response: " + String(httpCode));
      Serial.println(payload);
}

void connectToWiFi() {
  // Add the SSIDs and passwords for both networks
  wifiMulti.addAP(ssid1, password1);
  wifiMulti.addAP(ssid2, password2);
  wifiMulti.addAP(ssid3, password3);

  int attempts = 0;
  const int maxAttempts = 5;

  Serial.println("Connecting to WiFi...");

  // Attempt to connect to WiFi networks
  while (wifiMulti.run() != WL_CONNECTED && attempts < maxAttempts) {
    Serial.println("Trying to connect to WiFi...");
    delay(5000); // Wait for the connection
    attempts++;
  }

  // Check the connection result
  if (wifiMulti.run() == WL_CONNECTED) {
    if (WiFi.SSID() == ssid1) {
      host = host1;
    } else if (WiFi.SSID() == ssid2) {
      host = host2;
    } else if (WiFi.SSID() == ssid3) {
      host = host3;
    } 

    Serial.println("WiFi connected");
    digitalWrite(ledPin, HIGH);
  } else {
    Serial.println("WiFi failed to connect after several tries");
  }
}

void setup() {
  Serial.begin(115200);

  // Initialize the watchdog timer
  esp_task_wdt_init(20, true);  // 20 seconds timeout, and panic if timeout occurs
  esp_task_wdt_add(NULL);       // Add the current task to the watchdog

  digitalWrite(ledPin2, HIGH);

  connectToWiFi();

  pinMode(ledPin,OUTPUT);
  pinMode(ledPin2,OUTPUT);
  
  checkWeldID();
  delay(1000);
  kirimAwal();
  delay(1000);
  sendLogin();
  delay(1000);
  firstHeartBeat();
}

void loop() {
      
  readVoltage();

  esp_task_wdt_reset();

  unsigned long currentTime = millis();

  unsigned long currentMillis = millis();
  
  if (currentMillis - previousCheckTimeMillis >= checkTimeInterval) {
    heartBeat();
    previousCheckTimeMillis = currentMillis;
  }

  Serial.print("Voltage: ");
  Serial.println(voltageMillivolts);

  // Check if the arc is initiated or stopped
  if (voltageMillivolts > 600 && !arcInitiated) {
    Serial.println("Arc Initiated");
    kirimArcOn();
    arcInitiated = true;
    arcStartTime = currentTime; // Record the time when the arc is initiated
  } else if (voltageMillivolts <= 600 && arcInitiated) {
    Serial.println("Arc Stopped");
    kirimArcOff();
    arcInitiated = false;
  }

  // Check if the arc has been initiated for 6 seconds
  if (arcInitiated && (currentTime - arcStartTime >= 6000)) {
    Serial.println("Warning: Arc has been initiated for 6 seconds");
    kirimArcCheck();
    arcStartTime = currentTime; // Reset the start time to trigger the next warning after another 6 seconds
  }

  delay(100); // Adding a small delay to make the serial output readable
}


void readVoltage() {
  analogValue = analogRead(ANALOG_PIN);
  voltageMillivolts = analogValue + 100;
  
  float sensedVoltage = voltageMillivolts * (ESPVCC / (float)INPUT_RESOLUTION); 
  voltage = sensedVoltage / ((float)VALUE_R2 / ((float)VALUE_R1 + (float)VALUE_R2)); 
  calibration = (1.0447 * voltage) + 1.2885;
}

float getVoltageAverage() {
  float tempAverageVoltage = 0;
  for (int i = 0; i < AVERAGE_OF; i++) {
    readVoltage();
    tempAverageVoltage += calibration;
  }
  return tempAverageVoltage / AVERAGE_OF;
}