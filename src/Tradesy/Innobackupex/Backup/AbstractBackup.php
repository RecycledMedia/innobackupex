<?php

namespace Tradesy\Innobackupex\Backup;

use Tradesy\Innobackupex\LogEntry;
use Tradesy\Innobackupex\MySQL\Configuration;
use Tradesy\Innobackupex\Encryption\Configuration as EncryptionConfiguration;
use Tradesy\Innobackupex\ConnectionInterface;
use Tradesy\Innobackupex\Exceptions\InnobackupexException;
use Tradesy\Innobackupex\SaveInterface;

/**
 * Class AbstractBackup
 * @package Tradesy\Innobackupex\Backup
 */
abstract class AbstractBackup
{
    use \Tradesy\Innobackupex\Traits;

    /**
     * @var string
     * @desc Directory name relative to $save_directory
     */
    protected $relative_backup_directory;
    /**
     * @var string
     * The directory where any backup directories will live
     */
    protected $base_backup_directory;
    /**
     * @var string
     * @desc Prefix for relative backup directory
     */
    protected $save_directory_prefix;
    /**
     * @var bool
     */
    protected $compress;
    /**
     * @var int
     */
    protected $compress_threads;
    /**
     * @var int
     */
    protected $encrypt_threads;
    /**
     * @var int
     */
    protected $parallel_threads;
    /**
     * @var Configuration
     */
    protected $mysql_configuration;
    /**
     * @var ConnectionInterface
     */
    protected $connection;
    /**
     * @var \Tradesy\Innobackupex\SaveInterface[]
     */
    protected $save_modules;
    /**
     * @var EncryptionConfiguration
     */
    protected $encryption_configuration;
    /**
     * @var int timestamp
     */
    protected $start_date;
    /**
     * @var int timestamp
     */
    protected $end_date;
    /**
     * @var string
     * @desc Max memory to use during backup ie. "1G" for 1 gigabyte
     */
    protected $memory_limit = "1G";
    /**
     * @var string
     * @desc Filename to store serialized backup information
     */
    protected $backup_info_filename = "tradesy_percona_backup_info";

    /**
     * @var string
     * @desc Filename to store serialized backup information for full backups
     */
    protected $full_backup_info_filename = "tradesy_percona_full_backup_info";

    /**
     * @var \Tradesy\Innobackupex\Backup\Info
     */
    protected $BackupInfo;

    /**
     * @return Info
     */
    public function getBackupInfo()
    {
        return $this->BackupInfo;
    }

    /**
     * @param Info $BackupInfo
     */
    public function setBackupInfo($BackupInfo)
    {
        $this->BackupInfo = $BackupInfo;
    }


    /**
     * AbstractBackup constructor.
     * @param Configuration $mysql_configuration
     * @param ConnectionInterface $connection
     * @param SaveInterface[] $save_module
     * @param EncryptionConfiguration $enc_config
     * @param bool $compress
     * @param string $memory
     * @param string $base_backup_directory
     * @param string $save_directory_prefix
     */
    public function __construct(
        Configuration $mysql_configuration,
        ConnectionInterface $connection,
        array $save_modules,
        EncryptionConfiguration $enc_config = null,
        $compress = false,
        $compress_threads = 100,
        $paralle_threads = 100,
        $encrypt_threads = 100,
        $memory = "1G",
        $base_backup_directory = "/tmp",
        $save_directory_prefix = "full_backup"
    ) {
        $this->mysql_configuration = $mysql_configuration;
        $this->connection = $connection;
        $this->save_modules = $save_modules;
        $this->encryption_configuration = $enc_config;
        $this->compress = $compress;
        $this->compress_threads = $compress_threads;
        $this->parallel_threads = $paralle_threads;
        $this->encrypt_threads = $encrypt_threads;
        $this->memory = $memory;
        $this->base_backup_directory = $base_backup_directory;
        $this->save_directory_prefix = $save_directory_prefix;
        $this->mysql_configuration->verify();

    }

    /**
     * @return string
     */
    public function getBackupInfoFilename()
    {
        return $this->backup_info_filename;
    }

    /**
     * @return string
     */
    public function getFullBackupInfoFilename()
    {
        return $this->full_backup_info_filename;
    }

    /**
     * @param string $backup_info_filename
     */
    public function setBackupInfoFilename($backup_info_filename)
    {
        $this->backup_info_filename = $backup_info_filename;
    }

    /**
     * @param string $full_backup_info_filename
     */
    public function setFullBackupInfoFilename($full_backup_info_filename)
    {
        $this->full_backup_info_filename = $full_backup_info_filename;
    }

    /**
     * @return string
     */
    public function getSaveDirectoryPrefix()
    {
        return $this->save_directory_prefix;
    }

    /**
     * @param string $save_directory_prefix
     */
    public function setSaveDirectoryPrefix($save_directory_prefix)
    {
        $this->save_directory_prefix = $save_directory_prefix;
    }


    /**
     * @return mixed
     */
    public function getFullPathToBackup()
    {
        return $this->getBasebackupDirectory() . DIRECTORY_SEPARATOR . $this->getRelativebackupdirectory();
    }

    /**
     * @return string
     */
    public function getMemoryLimit()
    {
        return $this->memory_limit;
    }

    /**
     * @param string $memory_limit
     */
    public function setMemoryLimit($memory_limit)
    {
        $this->memory_limit = $memory_limit;
    }


    /**
     * @return string
     */
    public function getRelativebackupdirectory()
    {
        return $this->relative_backup_directory;
    }
    
    /**
     * @return mixed
     */
    public function getBasebackupDirectory()
    {
        return $this->base_backup_directory;
    }


    /**
     * @return boolean
     */
    public function getCompress()
    {
        return $this->compress;
    }

    /**
     * @param boolean $compress
     */
    public function setCompress($compress)
    {
        $this->compress = $compress;
    }


    /**
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return Configuration
     */
    public function getMysqlConfiguration()
    {
        return $this->mysql_configuration;
    }

    /**
     * @return EncryptionConfiguration
     */
    public function getEncryptionConfiguration()
    {
        return $this->encryption_configuration;
    }

    /**
     * @return mixed
     */
    public function getStartDate()
    {
        return $this->start_date;
    }

    /**
     * @param mixed $start_date
     */
    public function setStartDate($start_date)
    {
        $this->start_date = $start_date;
    }

    /**
     * @return mixed
     */
    public function getEndDate()
    {
        return $this->end_date;
    }

    /**
     * @param mixed $end_date
     */
    public function setEndDate($end_date)
    {
        $this->end_date = $end_date;
    }

    /**
     * Sets the backup start time
     */
    public function start()
    {
        $this->setStartDate(time());
        $this->setRelativebackupdirectory();
        LogEntry::logEntry('Starting Backup');;
    }

    /**
     * Sets the backup ending time
     */
    public function end()
    {
        $this->setEndDate(time());
        LogEntry::logEntry('Backup Finished');
    }

    public function test_innobackupex_exist()
    {
        $response = $this->connection->executeCommand(
            "which innobackupex"
        );
        if (
            empty($response->stdout())
            ||
            !empty($response->stderr())
        ) {
            throw new InnobackupexException(
                'Innobackupex binary not found' .
                0
            );
        } else {
            LogEntry::logEntry('Innobackupex located: ' . $response->stdout());
        }
    }

    /**
     * Perform the backup
     *
     * @throws InnobackupexException
     */
    public function Backup()
    {
        $this->test_innobackupex_exist();
        $this->createBaseDir();
        $this->start();
        if ($this->PerformBackup()) {
            LogEntry::logEntry('Backup finished successfully');
            $this->SaveBackupInfo();
            //  $this->ApplyLog();
            LogEntry::logEntry('Saved to ' . $this->getFullPathToBackup());
            foreach ($this->save_modules as $saveModule) {

                $saveModule->setKey(
                    $this->BackupInfo->getRepositoryBaseName() .
                    DIRECTORY_SEPARATOR .
                    $this->getRelativebackupdirectory()
                );
                $saveModule->save($this->getFullPathToBackup());
                /*
                 * optionally store backup info with save modules
                 */
                $saveModule->saveBackupInfo($this->BackupInfo, $this->getBackupInfoFilename());

                if ($this instanceof Full) {
                    // Let's also save information for full backup
                    $saveModule->saveBackupInfo($this->BackupInfo, $this->getFullBackupInfoFilename());
                }

                $saveModule->cleanup();
            }
            $this->PostHook();
        } else {
            LogEntry::logEntry('[Error] Backup had issues');
        }
        $this->end();
    }

    protected function createBaseDir()
    {
        $command = 'if test -d ' . $this->getBasebackupDirectory() .'; then echo "exists"; fi';
        $result = $this->getConnection()->executeCommand($command);

        if (trim($result->stdout()) !== 'exists') {
            LogEntry::logEntry('Creating dir ' . $this->getBasebackupDirectory());
            $this->getConnection()->executeCommand('mkdir ' . $this->getBasebackupDirectory());
        }
    }

    /**
     * Write the contents passed to the given destination file
     *
     * @param $dest
     * @param $contents
     * @param int $mode
     */
    public function writeFile($dest, $contents, $mode = 0644)
    {
        $this->connection->writeFileContents($dest, $contents, $mode);
    }

    /**
     * Fetches the backup information
     *
     * @return Info
     */
    public function fetchBackupInfo()
    {
        $remote_file = $this->getBasebackupDirectory() . DIRECTORY_SEPARATOR .
            $this->getBackupInfoFilename();

        if ($this->getConnection()->file_exists($remote_file)) {
            $file_contents = $this->getConnection()->getFileContents($remote_file);
            LogEntry::logEntry('Contents from file "' . $remote_file . '": ' . $file_contents);
            $this->BackupInfo = unserialize($file_contents);
            var_dump($this->BackupInfo);
        } else {
            $this->BackupInfo = new Info();
        }

        return $this->BackupInfo;
    }

    protected function PostHook()
    {

    }

    /**
     * Set the relative backup directory
     */
    protected function setRelativebackupdirectory()
    {
        $this->relative_backup_directory = $this->getSaveDirectoryPrefix() .
            date("m-j-Y--H-i-s", $this->getStartDate());
    }

    abstract function SaveBackupInfo();

    /**
     * Perform the backup. Returns true when the backup finished successfully, false otherwise.
     *
     * @return bool
     */
    abstract function PerformBackup();
}
