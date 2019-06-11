<?php

namespace Tradesy\Innobackupex\S3\Local;

use Aws\Command;
use Aws\S3\S3Client;
use Tradesy\Innobackupex\LogEntry;
use \Tradesy\Innobackupex\LoadInterface;
use \Tradesy\Innobackupex\ConnectionInterface;
use \Tradesy\Innobackupex\Exceptions\BucketNotFoundException;

class Download implements LoadInterface
{
    const AWS_S3_API_VERSION = '2006-03-01';

    /**
     * @var \Aws\S3\S3Client
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

        $this->client = new S3Client([
            'region' => $this->region,
            'version' => self::AWS_S3_API_VERSION
        ]);
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
        $path_to = $info->getBaseBackupDirectory();
        $key_prefix = $info->getRepositoryBaseName();

        LogEntry::logEntry('Downloading ' . $filename);
        LogEntry::logEntry('Saving to: ' . $path_to);
        try {
            $this->client->downloadBucket(
                $path_to . DIRECTORY_SEPARATOR . $filename,
                $this->bucket,
                DIRECTORY_SEPARATOR . $key_prefix . DIRECTORY_SEPARATOR . $filename,
                [
                    "allow_resumable" => false,
                    "concurrency" => $this->concurrency,
                    "base_dir" => $key_prefix . DIRECTORY_SEPARATOR . $filename,
                    "debug" => true,
                    "before" => function (Command $command) use ($path_to, $key_prefix) {
                        $target_file_name = substr_replace($command['Key'], $path_to, 0, strlen($key_prefix));

                        # make sure any nested dirs exist first
                        $file_dir = dirname($target_file_name);
                        if (!is_dir($file_dir)) {
                            mkdir($file_dir, 007, true);
                        }

                        touch($target_file_name);
                    }
                ]
            );
        } catch (\Exception $e) {
            LogEntry::logEntry('Exception caught ' . $e->getMessage());
        }
        return;
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
