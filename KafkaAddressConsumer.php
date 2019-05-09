<?php

require_once ('custom/Kafka/AbstractKafka.php');

class KafkaAddressConsumer extends AbstractKafka
{

    /**
     * @var RdKafka\Conf
     * @description Kafka Configuration Handler
     */
    protected $kafkaConfigurator;

    /**
     * @var RdKafka\TopicConf
     * @description Kafka Topic Configuration Handler
     */
    protected $kafkaTopicConfigurator;

    /**
     * @var RdKafka\KafkaConsumer
     * @description Kafka Consumer Handler
     */
    protected $kafkaConsumer;

    /**
     * @var mixed
     * @description Logger Handler for Consumer messages
     */
    protected $logger_handler;
    /**
     * @var int
     * @description Chunk limit for processing
     */
    protected $message_counter = 0;

    /**
     * @description Binds the shutdown function for consumer.
     */
    public function __construct()
    {
        parent::__construct();

        $this->phpExtension = array(
            'rdkafka',
        );

        register_shutdown_function(array($this, 'onShutdownConsumer'));
    }

    /**
     * @description Executed the Kafka Consumer and logs the messages.
     *
     * @param array $args
     * @throws Exception
     */
    public function KafkaRun($args = array())
    {
        try {
            $this->checkPreRequisite($args);
            $this->connectLogger();
            $consumer = $this->getKafkaConsumer();
            while (true) {
                $message = $consumer->consume(
                    $this->kafkaConnectionSettings['conf']['consume']['timeout']
                );
                switch ($message->err) {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:
                        $this->kafkaLogs($message, 'MSG_RCVD');
                        $prev = $message;
                        break;
                    case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                        if (!empty($prev)) {
                            $consumer->commit($prev);
                        }
                        $this->kafkaLogs("No more messages; will wait for more\n", 'WAIT');
                        break;
                    case RD_KAFKA_RESP_ERR__TIMED_OUT:
                        break;
                    default:
                        $this->kafkaLogs(json_encode($message->errstr()), 'EXCEPTION');
                        throw new \Exception($message->errstr(), $message->err);
                        break 2;
                }
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @description Opens Kafka Logs file.
     *
     * @throws Exception
     */
    protected function connectLogger()
    {
        $kafkaLogsConfig = $this->kafkaConnectionSettings['logs'];
        $file = $kafkaLogsConfig['filename'];

        if (!empty($file)) {
            $this->logger_handler = fopen($file, 'a');
        }
    }

    /**
     * @description Closes Kafka Logs file.
     */
    protected function disconnectLogger()
    {
        if ($this->logger_handler) {
            fclose($this->logger_handler);
        }
    }

    /**
     * @description Write in Kafka Logs file.
     *
     * @param string $log
     * @throws Exception
     */
    protected function writeInLogger($log)
    {
        $res = false;
        if ($this->logger_handler) {
            $res = fwrite($this->logger_handler, $log);
        }
        if ($res === false) {
            throw new Exception('Exception occured while writing in log file');
        }
    }

    /**
     * @description Logs messages in Kafka Logs file.
     *
     * @param string $message
     * @param string $type
     * @throws Exception
     */
    protected function kafkaLogs($message, $type)
    {
        try {
            $kafkaLogsConfig = $this->kafkaConnectionSettings['logs'];
            $timezone = new DateTimeZone($kafkaLogsConfig['timezone']);
            $date = new DateTime('now', $timezone);
            $datetime = $date->format($kafkaLogsConfig['datetime_format']);

            if ($type === 'MSG_RCVD') {
                $log = "[{$datetime}][APACHE_KAFKA_LOGS]({$type}): key:{$message->key} \n";
                $log .= "[{$datetime}][APACHE_KAFKA_LOGS]({$type}): message:{$message->payload} \n";
            } else {
                $log = "[{$datetime}][APACHE_KAFKA_LOGS]({$type}): {$message}";
            }

            $this->writeInLogger($log);
            $this->message_counter++;
            if($this->message_counter >= $this->chunkLimit) {
                exec("logrotate /entrypoint/kafka-logrotate.conf");
                $this->message_counter = 0;
            }

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @description Checks for and returns the Kafka Consumer object.
     *
     * @return RdKafka\KafkaConsumer $kafkaConsumer
     */
    protected function getKafkaConsumer()
    {
        try {
            if (empty($this->kafkaConsumer)) {
                $this->kafkaConsumer = new RdKafka\KafkaConsumer(
                        $this->getKafkaConfigurator()
                );
                $this->kafkaConsumer->subscribe($this->kafkaConnectionSettings['conf']['topics']);
            }

            return $this->kafkaConsumer;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
    * @description sets the rebalance callback, so whenever a partition is assigned or revoked kafka calls this method.
    *
    * @throws Exception
    */
    private function setRebalanceCb()
    {
        // Set a rebalance callback to log partition assignments (optional)
        $this->kafkaConfigurator->setRebalanceCb(function (RdKafka\KafkaConsumer $kafka, $err, array $partitions = null) {
            switch ($err) {
                case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                    $ids = [];
                    foreach ($partitions as $p) {
                        $ids[] = $p->getPartition();
                    }
                    $ids = implode(',', $ids);
                    $kafka->assign($partitions);
                    $this->kafkaLogs("Assigned Partitions: {$ids}\n", 'PARTITIONS ASSIGNED');
                    break;
                 case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                     $this->kafkaLogs("\n", 'PARTITIONS REVOKED');
                     $kafka->assign(NULL);
                     break;
                 default:
                    throw new \Exception($err);
            }
        });
    }

    /**
     * @description Checks and returns the Kafka Conf object.
     *
     * @return RdKafka\Conf $kafkaConfigurator
     */
    protected function getKafkaConfigurator()
    {
        try {
            if (empty($this->kafkaConfigurator)) {
                $kafkaConnectionConfig = $this->kafkaConnectionSettings['conf'];
                $this->kafkaConfigurator = new RdKafka\Conf();
                if (!empty($kafkaConnectionConfig['rebalanceCallback'])) {
                    $this->setRebalanceCb();
                }
                if (!empty($kafkaConnectionConfig['configurations'])) {
                    foreach($kafkaConnectionConfig['configurations'] as $config_name => $value){
                        $this->kafkaConfigurator->set($config_name, $value);
                    }
                }
                $this->kafkaConfigurator->set('metadata.broker.list', implode(',', $kafkaConnectionConfig['metadata.broker.list']));
                $this->kafkaConfigurator->setDefaultTopicConf(
                        $this->getKafkaTopicConfigurator()
                );
            }
            return $this->kafkaConfigurator;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @description Checks and send Kafak Topic Conf object.
     *
     * @return RdKafka\TopicConf $kafkaTopicConfigurator
     */
    protected function getKafkaTopicConfigurator()
    {
        try {
            if (empty($this->kafkaTopicConfigurator)) {
                $this->kafkaTopicConfigurator = new RdKafka\TopicConf();
                if (!empty($this->kafkaConnectionSettings['conf']['topic_configurations'])) {
                    foreach($this->kafkaConnectionSettings['conf']['topic_configurations'] as $config_name => $value){
                        $this->kafkaTopicConfigurator->set($config_name, $value);
                    }
                }
            }
            return $this->kafkaTopicConfigurator;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @description Restarting the Kafka Consumer.
     */
    public function onShutdownConsumer()
    {

        if (!empty($this->kafkaConsumer) && !empty($this->kafkaConsumer->getSubscription())) {
            $this->kafkaConsumer->unsubscribe();
        }
        $this->disconnectLogger();
    }

}
