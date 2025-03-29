//---- Website ----
#include <HTTPClient.h>
#include <WiFi.h>
#include <WiFiMulti.h>

HTTPClient http;
String Link, LinkTiming, Mode, Status, payload;
int httpCode; 

WiFiMulti wifiMulti;

String uid = "A20-2534";

const char *ssid1 = "ico";
const char *password1 = "19403054";
const char *host1 = "tugasakhirvica.net";

const char *ssid2 = "MetronARC";
const char *password2 = "2468g0a7";
const char *host2 = "ronstan.sparcmonitoring.com";

const char *ssid3 = "MetronARCs Technology";
const char *password3 = "2468g0a7A7B7*";
const char *host3 = "ronstan.sparcmonitoring.com";

const char* host = "";
String machineID = "A20-2534";
byte statusKoneksi;

int area = 1;

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

void setup(){
    Serial.begin(115200);

    connectToWiFi();

    checkWeldID();
}

void loop(){
    //Nothing for now
}