# rdkafka Consumer Class

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
