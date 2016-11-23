<?php

namespace Tradesy\Innobackupex\GCS\Remote;

use Tradesy\Innobackupex\LogEntry;
use \Tradesy\Innobackupex\SSH\Connection;
use \Tradesy\Innobackupex\ConnectionInterface;
use \Tradesy\Innobackupex\LoadInterface;
use \Tradesy\Innobackupex\Exceptions\CLINotFoundException;
use \Tradesy\Innobackupex\Exceptions\BucketNotFoundException;

class Download implements LoadInterface {

    protected $connection;
    protected $bucket;
    protected $region;
    protected $source;
    protected $key;
    protected $concurrency;
    protected $binary = "gsutil";

    /**
     * Upload constructor.
     * @param $connection
     * @param $bucket
     * @param $key
     * @param $region
     * @param bool $remove_file_after_upload
     * @param int $concurrency
     */
    public function __construct(
        ConnectionInterface $connection,
        $bucket,
        $region,
        $concurrency = 10
    ){
        $this->connection               = $connection;
        $this->bucket                   = $bucket;
        $this->region                   = $region;
        $this->concurrency              = $concurrency;
        $this->testSave();
    }

    public function testSave()
    {
        $command = "which " . $this->binary;
        $response = $this->connection->executeCommand($command);
        if(strlen($response->stdout()) == 0 || preg_match("/not found/i", $response->stdout())){
            throw new CLINotFoundException(
                $this->binary ." CLI not installed.",
                0
            );
        }
        /*
         * TODO: Check that credentials work
         */
        $command = $this->binary .
            " ls -b gs://" . $this->bucket . " | grep -c " . $this->bucket;
        LogEntry::logEntry($command);
        $response = $this->connection->executeCommand($command);
        if(intval($response->stdout())==0){
            throw new BucketNotFoundException(
                "GCS bucket (" . $this->bucket . ")  not found. ",
                0
            );
        }

    }

    public function save($filename)
    {
    }

    public function cleanup()
    {
    }

    public function saveBackupInfo(\Tradesy\Innobackupex\Backup\Info $info)
    {
    }

    public function load( \Tradesy\Innobackupex\Backup\Info $info, $filename)
    {
    }

    public function getBackupInfo($backup_info_filename)
    {
    }

    public function verify()
    {
    }

    /**
     * @param mixed $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }
}