# rdkafka Consumer Class

# Confluent
Required Ports:
- 9092 - broker
- 2181 - zookeeper
	
# Installation:
1. Install the Confluent public key. This key is used to sign the packages in the APT repository.
    - wget -qO - https://packages.confluent.io/deb/5.0/archive.key | sudo apt-key add -
    - The Output should be: OK
2. Add the repository to your /etc/apt/sources.list by running this command:
    - sudo add-apt-repository "deb [arch=amd64] https://packages.confluent.io/deb/5.0 stable main" 
3. Update apt-get and install the entire Confluent Platform platform. (Confluent Open Source)
    - sudo apt-get update && sudo apt-get install confluent-platform-oss-2.11
      
# Start-Up Services:
sudo systemctl < command > < service-name >
1. Commands:
    - Start
    - Status
    - Stop
2. Service Names:
    - confluent-zoopkeeper
    - confluent-kafka
    - confluent-schema-registry
    - Source: <a href="https://docs.confluent.io/current/installation/installing_cp/deb-ubuntu.html#get-the-software">here</a>

## Kafka Source:
1. <a href="https://docs.confluent.io/current/quickstart/cos-quickstart.html#cos-quick-start-local">Confluent</a>
2. <a href="https://kafka.apache.org/intro">Documentation</a>

# librdkafka - the Apache Kafka C/C++ client library
To install rdkafka php extension, we need to build librdkafka
### Installation:
1. Download librdkafka from https://github.com/edenhill/librdkafka/releases
2. cd librdkafka-master.
3. Run
   - ./configure
   - make
   - sudo make install

## PHP rdkafka Client Repository
Requirements: 
1. PHP Extension rdkafka
2. librdkafka (version >= 0.9)
### Installation: 
1. Run pecl install rdkafka
2. vi /etc/php.ini
3. add following: extension=rdkafka.so

##### Confirm using phpinfo()
Source:
- git clone https://github.com/arnaud-lb/php-rdkafka.git 

Examples: 
- Path: https://github.com/arnaud-lb/php-rdkafka/tree/master/examples 

High-Level Consumer Example:
- Path: https://arnaud-lb.github.io/php-rdkafka/phpdoc/rdkafka.examples-high-level-consumer.html
