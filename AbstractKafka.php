<?php

require_once ('config.php');
require_once ('config_override.php');

abstract class AbstractKafka
{

    /**
     * @var array
     * @description Connection info of Kafka
     */
    protected $kafkaConnectionSettings = array();

    /**
     * @var string
     * @description Current Kafka operation,
     * like AdressLogginConsumer etc.
     */
    protected $kafkaCurrentOperation = '';

    /**
     * @var string
     * @description Current Environment, Dev or Production
     */
    protected $kafkaCurrentEnvironment = '';

    /**
     * @var string
     * @description Current type, either Consumer or Producer
     */
    protected $kafkaCurrentType = '';

    /**
     * @var mixed
     * @description Save the Sugar Configuration
     */
    protected $sugar_config = array();

    /**
     * @var array
     * @discription Extensions which are required
     */
    protected $phpExtension = array();
    /**
     * @var array
     * @description Contains the mapping for consumer or producers
     */
    protected $sugarAndkafkaMapping = '';

    /**
     * @var int
     * @description Chunk limit for processing
     */
    protected $chunkLimit = 0;

    /**
     * @description Initiates class, and sets up Apache Kafka Consumer.
     *
     * @throws Exception
     */
    public function __construct()
    {
        global $sugar_config;
        $this->sugar_config = $sugar_config;
    }

    /**
     * @discription To start the consumer or producer
     *
     * @throws Exception
     */
    abstract public function KafkaRun();

    /**
     * @description Handles verification of php extensions
     * which are required.
     *
     * @param array $extensions
     */
    public function checkPhpExtensions()
    {
        if (empty($this->phpExtension)) {
            return true;
        }

        foreach ($this->phpExtension as $ext) {
            if (!extension_loaded($ext)) {
                throw new Exception('Missing Extension: ' . $ext);
            }
        }
    }

    /**
     * @description Handles validation of required arguments for a request.
     *
     * @param array $args
     * @param array $requiredFields
     * @throws Exception
     */
    public function requireKafkaArgs($args, $requiredFields = array())
    {
        foreach ($requiredFields as $fieldName) {
            if (!array_key_exists($fieldName, $args) || empty($args[$fieldName])) {
                throw new Exception('Missing parameter: ' . $fieldName);
            }
        }
    }

    /**
     * @description To perform pre requisite checking before starting up Kafka.
     *
     * @param array $args
     * @throws Exception
     */
    protected function checkPreRequisite($args = array())
    {
        try {
            $this->requireKafkaArgs($args, array('operation', 'KafkaType'));

            // check extensions first
            $this->checkPhpExtensions();

            $env = !empty($args['environment']) ? $args['environment'] : (!empty($this->sugar_config['kafka']['environment']) ? $this->sugar_config['kafka']['environment'] : '');
            $operation = !empty($args['operation']) ? $args['operation'] : '';
            $KafkaType = !empty($args['KafkaType']) ? $args['KafkaType'] : '';

            if (empty($env) || empty($operation) || empty($KafkaType)) {
                throw new Exception("Apache Kafka - Missing Parameters: Environment: {$env}, KafkaType: {$KafkaType}, Operation : {$operation}");
            }

            if (!in_array($KafkaType, $this->sugar_config['queueTypes'])) {
                throw new Exception("Apache Kafka - Invalid Queue Type: Environment: {$env}, KafkaType: {$KafkaType}");
            }

            if (
                    isset($this->sugar_config['kafka']) &&
                    isset($this->sugar_config['kafka'][$env]) &&
                    isset($this->sugar_config['kafka'][$env][$KafkaType]) &&
                    isset($this->sugar_config['kafka'][$env][$KafkaType][$operation])
            ) {
                $this->kafkaConnectionSettings = $this->sugar_config['kafka'][$env][$KafkaType][$operation];
            }

            // set variables
            $this->chunkLimit = !empty($this->sugar_config['kafka'][$env][$KafkaType]['chunkLimit'])
                ? $this->sugar_config['kafka'][$env][$KafkaType]['chunkLimit'] : 1000000;


            $this->kafkaCurrentOperation = $operation;
            $this->kafkaCurrentEnvironment = $env;
            $this->kafkaCurrentType = $KafkaType;
            $this->sugarAndkafkaMapping = $this->sugar_config['kafka'][$KafkaType]['mapping'][$operation];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}
