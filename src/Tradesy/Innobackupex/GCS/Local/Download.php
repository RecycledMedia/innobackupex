<?php

namespace Tradesy\Innobackupex\GCS\Local;

use Tradesy\Innobackupex\LogEntry;
use \Tradesy\Innobackupex\SSH\Connection;
use \Tradesy\Innobackupex\LoadInterface;
use \Tradesy\Innobackupex\ConnectionInterface;
use \Tradesy\Innobackupex\Exceptions\CLINotFoundException;
use \Tradesy\Innobackupex\Exceptions\BucketNotFoundException;
use \Google;

class Download implements LoadInterface
{

    /**
     * @var \Google_Client
     */
    protected $client;

    protected $connection;
    protected $bucket;
    protected $region;
    protected $source;
    protected $key;
    protected $concurrency;

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
    ) {
        $this->connection = $connection;
        $this->bucket = $bucket;
        $this->region = $region;
        $this->concurrency = $concurrency;
        $this->client = new \Google_Client();
        $this->client->setApplicationName("My Application");
        $this->client->setDeveloperKey("MY_SIMPLE_API_KEY");

        $this->testSave();

    }

    public function testSave()
    {
        if (!$this->client->doesBucketExist($this->bucket)) {
            throw new BucketNotFoundException(
                "S3 bucket (" . $this->bucket . ")  not found in region (" .
                $this->region . ")",
                0
            );
        }

    }

    public function load(\Tradesy\Innobackupex\Backup\Info $info, $filename)
    {
        //$filename = $info->getLatestFullBackup();
        LogEntry::logEntry('downloading ' . $filename);
        LogEntry::logEntry('Saving to: '  . $info->getBaseBackupDirectory() . DIRECTORY_SEPARATOR);
//        $service = new \Google_Service_Storage($this->client);
//        $object = $service->objects->get( $this->bucket, $filename )->;
//        $request = new Google_Http_Request($object['mediaLink'], 'GET');
//        $signed_request = $this->client->getAuth()->sign($request);
//        $http_request = $this->client->getIo()->makeRequest($signed_request);
//        LogEntry::logEntry('Response received: ' . $http_request->getResponseBody());
//
//
//        $this->client->downloadBucket(
//            $info->getBaseBackupDirectory() . DIRECTORY_SEPARATOR . $filename ,
//            $this->bucket,
//            DIRECTORY_SEPARATOR . $info->getRepositoryBaseName() . DIRECTORY_SEPARATOR . $filename,
//            [
//                "allow_resumable" => false,
//                "concurrency" => $this->concurrency,
//                "base_dir" => $info->getRepositoryBaseName(). DIRECTORY_SEPARATOR . $filename,
//                "debug" => true
//            ]);
    }

    public function cleanup()
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