<?php

namespace Tradesy\Innobackupex\Backup;

use Tradesy\Innobackupex\LogEntry;

/**
 * Class Incremental
 * @package Tradesy\Innobackupex\Backup
 */
class Incremental extends AbstractBackup
{
    /**
     * @var string
     */
    protected $save_directory_prefix = "full_backup_";
    
    public function PerformBackup()
    {
        /*
         * If there are incrementals, use the directory returned by array_pop,
         * else use the base backup directory
         */
        $user = $this->getMysqlConfiguration()->getUsername();
        $password = $this->getMysqlConfiguration()->getPassword();
        $host = $this->getMysqlConfiguration()->getHost();
        $port = $this->getMysqlConfiguration()->getPort();
        $enc_class = "\Tradesy\Innobackupex\Encryption\Configuration";


        $encryption_string = (($this->getEncryptionConfiguration() instanceof $enc_class) ?
            $this->getEncryptionConfiguration()->getConfigurationString() : "");

        $basedir = $this->BackupInfo->getBaseBackupDirectory() . DIRECTORY_SEPARATOR .
            (is_null($this->BackupInfo->getLatestIncrementalBackup()) ?
                $this->BackupInfo->getLatestFullBackup() :
                $this->BackupInfo->getLatestIncrementalBackup());

        $this->decryptAndDecompressBackups([$basedir]);

        $encrypt_text = '';
        // Create a random string longer than key so it will not replace any text if not found.
        $encryption_key = substr(
            str_shuffle(
                str_repeat(
                    $x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(45/strlen($x))
                )
            ), 1, 45);

        if ($this->getEncryptionConfiguration() instanceof $enc_class) {
            $encrypt_text = $this->getEncryptionConfiguration()->getConfigurationString() .
                " --encrypt-threads=" . $this->encrypt_threads;
            $encryption_key = $this->getEncryptionConfiguration()->getKey();
        }

        $command = "innobackupex " .
            " --user={MYSQL_USER}" . 
            " --password={MYSQL_PASSWORD}" .
            " --host=" . $host .
            " --port=" . $port .
            " --parallel 100" .
            " --no-timestamp" .
            ($this->getCompress() ?
                " --compress --compress-threads=" . $this->compress_threads : "") .
            $encrypt_text .
            " --incremental " .
            $this->getFullPathToBackup() .
            " --incremental-basedir=" .
            $basedir;

        LogEntry::logEntry('Backup Command: ' . str_replace($encryption_key, '********', $command));

        $command = str_replace('MYSQL_USER', $user, $command);
        $command = str_replace('MYSQL_PASSWORD', $password, $command);
        
        $response = $this->getConnection()->executeCommand($command);

        LogEntry::logEntry('STDOUT: ' . $response->stdout());
        LogEntry::logEntry('STDERR: ' . $response->stderr());
    }

    public function SaveBackupInfo()
    {
        LogEntry::logEntry('Backup info save to home directory');
        $this->BackupInfo->addIncrementalBackup(
            $this->getRelativebackupdirectory()
        );
        $this->writeFile(
            $this->getBasebackupDirectory() . DIRECTORY_SEPARATOR . 
            $this->getBackupInfoFilename(),
            serialize($this->BackupInfo), 0644
        );

    }
}
