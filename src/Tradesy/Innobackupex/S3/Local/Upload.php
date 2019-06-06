<?php

namespace Tradesy\Innobackupex\S3\Local;

use \Aws\S3\S3Client;
use Tradesy\Innobackupex\LogEntry;

class Upload implements \Tradesy\Innobackupex\SaveInterface
{

    protected $bucket;
    protected $region;
    protected $client;
    protected $source;
    protected $key;
    protected $concurrency;

    public function __construct(S3Client $client, $concurrency = 10)
    {
        $this->client = $client;
        $this->concurrency = $concurrency;
    }

    public function testSave()
    {

    }

    public function save($filename)
    {
        $uploader = UploadBuilder::newInstance()
            ->setClient($this->client)
            ->setSource($this->source)
            ->setBucket($this->bucket)
            ->setKey($this->key)
            ->setOption('CacheControl', 'max-age=3600')
            ->setConcurrency($this->concurrency)
            ->build();
    }

    public function cleanup()
    {

    }

    public function verify()
    {

    }
    public function saveBackupInfo(\Tradesy\Innobackupex\Backup\Info $info, $filename){
        $serialized = serialize($info);

        $response = $this->connection->writeFileContents("/tmp/temporary_backup_info", $serialized);
        $command = $this->binary . " s3 cp /tmp/temporary_backup_info s3://" . $this->bucket . "/tradesy_percona_backup_info";
        LogEntry::logEntry('Upload latest backup info to S3 with command: ' . $command);

        $response = $this->connection->executeCommand($command);
        LogEntry::logEntry('STDOUT: ' . $response->stdout());
        LogEntry::logEntry('STDERR: ' . $response->stderr());
    }

    /**
     * @param mixed $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }
}
