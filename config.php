<?php
/*
 * Env = dev, test and local
 * deev = LKW Dev Kafka
 * test = LKW Test kafka
 * local = Developer own kafka
 */
$config['kafka']['environment'] = 'local';
/*
 * Apache Kafka configurations
 * Env = dev, test or local
 * type = Consumer or Producer
 *
 * In credentails and Mapping for each Consumer and
 * Producer, the kafkaCurrentOperation must be same,
 * like kafkaCurrentOperation = 'logginConsumer'
 */

// Kafka Consumer -- Start
$config['kafka']['local']['Consumer'] = [
    'logginConsumer' => [
        'logs' => [
            'filename' => __DIR__ . DIRECTORY_SEPARATOR . 'KafkaConsumerLogging.log',
            'datetime_format' => 'Y-m-d H:i:s',
            'timezone' => 'UTC',
        ],
        'conf' => [
            'topics' => ['test_topic'],
            'topic_configurations' => [
                'auto.offset.reset' => 'smallest',
            ],
            'configurations' => [
              'group.id' => 'KafkaLogginConsumerGroup',
              'queued.min.messages' => 10000,
              'enable.auto.commit' => true,
              'auto.commit.interval.ms' => 5000
            ],
            'metadata.broker.list' => [
                '127.0.0.1',
            ],
            'consume' => [
                'timeout' => 10000
            ],
            'rebalanceCallback' => true,
        ]
    ],
    'chunkLimit' => 1000000,
];
// Kafka Consumer -- End
