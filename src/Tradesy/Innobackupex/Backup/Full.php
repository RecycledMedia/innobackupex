<?php

namespace Tradesy\Innobackupex\Backup;
use \Tradesy\Innobackupex\Backup\AbstractBackup;
use \Tradesy\Innobackupex\Backup\Info;
use Tradesy\Innobackupex\LogEntry;


/**
 * Class Full
 * @package Tradesy\Innobackupex\Backup
 */
class Full extends AbstractBackup
{
    /**
     * @var string
     */
    protected $save_directory_prefix = "full_backup_";

    /**
     * @inheritdoc
     */
    public function PerformBackup()
    {
        $user = $this->getMysqlConfiguration()->getUsername();
        $password  = $this->getMysqlConfiguration()->getPassword();
        $host = $this->getMysqlConfiguration()->getHost();
        $port = $this->getMysqlConfiguration()->getPort();
        $directory = $this->getFullPathToBackup();
        $enc_class = "\Tradesy\Innobackupex\Encryption\Configuration";
        /*
         * TODO: --compress-threads=
         * TODO: --parallel
         */

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

        $command =
            "innobackupex" .
            " --user={MYSQL_USER}" .
            " --password={MYSQL_PASSWORD}" .
            " --host=" . $host .
            " --port=" . $port .
            " --parallel 100" .
            " --no-timestamp" .
            ($this->getCompress() ? 
                " --compress  --compress-threads=" . $this->compress_threads : "") .
            $encrypt_text . ' '  . $directory;

        LogEntry::logEntry('Backup Command: ' . str_replace($encryption_key, '********', $command));

        $command = str_replace('{MYSQL_USER}', $user, $command);
        $command = str_replace('{MYSQL_PASSWORD}', $password, $command);

        $this->getConnection()->setSudoAll(true);
        $response = $this->getConnection()->mute()->executeCommand($command);
        $this->getConnection()->unmute()->setSudoAll(false);

        $out = str_replace($encryption_key, '********', $response->stdout());
        $err = str_replace($encryption_key, '********', $response->stderr());
        LogEntry::logEntry('STDOUT: ' . $out);
        LogEntry::logEntry('STDERR: ' . $err);

        // Return true when stdout finished correctly
        return (strpos(str_replace('prints "completed OK!".', '', $out), 'completed OK!') !== false);
    }

    public function SaveBackupInfo()
    {
        LogEntry::logEntry('Backup info save to home directory');
        $enc_class = "\Tradesy\Innobackupex\Encryption\Configuration";
        $this->BackupInfo->setBaseBackupDirectory($this->getBasebackupDirectory());
        $this->BackupInfo->setLatestFullBackup($this->getRelativebackupdirectory());
        $this->BackupInfo->setIncrementalBackups(array());
        $this->BackupInfo->setRepositoryBaseName(date("m-j-Y--H-i-s", $this->getStartDate()));
        $this->BackupInfo->setEncrypted(($this->getEncryptionConfiguration() instanceof $enc_class)? true : false );
        $this->BackupInfo->setCompression($this->getCompress());
        $this->writeFile($this->getBasebackupDirectory() . DIRECTORY_SEPARATOR . $this->getBackupInfoFilename(), serialize($this->BackupInfo), 0644);

    }

}
