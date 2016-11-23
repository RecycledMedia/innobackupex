<?php

namespace Tradesy\Innobackupex\S3\Remote;

use Tradesy\Innobackupex\LogEntry;
use \Tradesy\Innobackupex\SSH\Connection;
use \Tradesy\Innobackupex\SaveInterface;
use \Tradesy\Innobackupex\ConnectionInterface;
use \Tradesy\Innobackupex\Exceptions\CLINotFoundException;
use \Tradesy\Innobackupex\Exceptions\BucketNotFoundException;

class Upload implements SaveInterface {

    protected $connection;
    protected $bucket;
    protected $region;
    protected $source;
    protected $key;
    protected $remove_file_after_upload;
    protected $concurrency;
    protected $binary = "aws";

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
                $this->binary . " CLI not installed.",
                0
            );
        }
        /*
         * TODO: Check that credentials work
         */
        $command = $this->binary .
                    " --region " . $this->region .
                    " s3 ls | grep -c " . $this->bucket;
        LogEntry::logEntry($command);
        $response = $this->connection->executeCommand($command);
        if(intval($response->stdout())==0){
            throw new BucketNotFoundException(
                "S3 bucket (" . $this->bucket . ")  not found in region (" . $this->region .")",
                0
            );
        }

    }

    public function save($filename)
    {
        # upload compressed file to s3
        $command = $this->binary .
            ' s3 sync ' . $filename .' s3://' . $this->bucket . DIRECTORY_SEPARATOR . $this->key
            . ' --only-show-errors';
        LogEntry::logEntry($command);
        $response = $this->connection->executeCommand($command);
        LogEntry::logEntry('STDOUT: ' . $response->stdout());
        LogEntry::logEntry('STDERR: ' . $response->stderr());
    }

    public function cleanup()
    {
    }

    public function saveBackupInfo(\Tradesy\Innobackupex\Backup\Info $info, $filename){
        // create temp file
        $response = $this->connection->executeCommand("mktemp", true);
        $file = rtrim($response->stdout());
        $serialized = serialize($info);

        $response = $this->connection->writeFileContents("$file", $serialized);
        $command = $this->binary .
            ' s3 cp ' . $file . ' s3://' . $this->bucket . DIRECTORY_SEPARATOR . $filename
            . ' --only-show-errors';
        LogEntry::logEntry('Upload latest backup info to S3 with command: ' . $command);
        $response = $this->connection->executeCommand($command,true);
        LogEntry::logEntry('STDOUT: ' . $response->stdout());
        LogEntry::logEntry('STDERR: ' . $response->stderr());

        $command = $this->binary .
            " s3 cp $file s3://" . $this->bucket .
            DIRECTORY_SEPARATOR .
            $info->getRepositoryBaseName() .
            DIRECTORY_SEPARATOR .
            "$filename";
        $response = $this->connection->executeCommand($command,true);
        LogEntry::logEntry('STDOUT: ' . $response->stdout());
        LogEntry::logEntry('STDERR: ' . $response->stderr());
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