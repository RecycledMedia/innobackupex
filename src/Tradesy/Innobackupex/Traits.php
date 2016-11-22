<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 3/28/16
 * Time: 1:29 PM
 */
namespace Tradesy\Innobackupex;

trait Traits
{
    function decryptAndDecompressBackups($backups)
    {
        $class = "\Tradesy\Innobackupex\Encryption\Configuration";


        foreach ($backups as $basedir) {
            LogEntry::logEntry('PROCESSING: ' . $basedir);
            /*
             * Next we have to check if files are encrpyted,
             */

            /*
             * If compressed and encrypted, decrypt first
             */
            if ($this->decryptionRequired($basedir)
            ) {
                $decryption_string = '';
                // Create a random string longer than key so it will not replace any text if not found.
                $encryption_key = substr(
                    str_shuffle(
                        str_repeat(
                            $x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(45/strlen($x))
                        )
                    ), 1, 45);

                if ($this->getEncryptionConfiguration() instanceof $class) {
                    $decryption_string = $this->getEncryptionConfiguration()->getDecryptConfigurationString();
                    $encryption_key = $this->getEncryptionConfiguration()->getKey();
                }

                $command = "innobackupex " .
                    $decryption_string .
                    " --parallel " . $this->parallel_threads .
                    " $basedir";

                LogEntry::logEntry('Decrypting command: ' . str_replace($encryption_key, '********', $command));
                $response = $this->getConnection()->executeCommand($command,true);

                LogEntry::logEntry('STDOUT: ' . str_replace($encryption_key, '********', $response->stdout()));
                LogEntry::logEntry('STDERR: ' . str_replace($encryption_key, '********', $response->stderr()));
            }
            /*
             * Now if compressed, decompress
             * xtrabackup_checkpoints doesn't get compressed, so check with different file
             * such as xtrabackup_info
             */

            $xtrabackup_file = $basedir . DIRECTORY_SEPARATOR . "xtrabackup_info";


            if ($this->decompressionRequired($basedir)
            ) {
                $command = "innobackupex " .
                    " --decompress" .
                    " --parallel " . $this->parallel_threads .
                    " $basedir";
                LogEntry::logEntry('Decompressing command: ' . $command);
                $response = $this->getConnection()->executeCommand($command,true);

                LogEntry::logEntry('STDOUT: ' . $response->stdout());
                LogEntry::logEntry('STDERR: ' . $response->stderr());
            }

        }
    }
    public function decryptionRequired($directory){
        $files = $this->getConnection()->scandir($directory);
        $pattern = '/.*\.xbcrypt$/';
        $matches = preg_grep($pattern,$files);
        $do_these_files_exist = str_replace(".xbcrypt", "" , $matches);
        foreach($do_these_files_exist as $file){
            if(!in_array($file,$files))
                return true;
        }
        return false;
    }

    public function decompressionRequired($directory){
        $files = $this->getConnection()->scandir($directory);
        $pattern = '/.*\.qp$/';
        $matches = preg_grep($pattern,$files);
        $do_these_files_exist = str_replace(".qp", "" , $matches);
        foreach($do_these_files_exist as $file){
            if(!in_array($file,$files))
                return true;
        }
        return false;
    }
    /**
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }


    public function getMemoryLimit()
    {
        return $this->memory_limit;
    }

    /**
     * @param mixed $memory_limit
     */
    public function setMemoryLimit($memory_limit)
    {
        $this->memory_limit = $memory_limit;
    }

    /**
     * @return Configuration
     */
    public function getMysqlConfiguration()
    {
        return $this->mysql_configuration;
    }

    /**
     * @param Configuration $mysql_configuration
     */
    public function setMysqlConfiguration($mysql_configuration)
    {
        $this->mysql_configuration = $mysql_configuration;
    }

    /**
     * @return \Tradesy\Innobackupex\Backup\Info
     */
    public function getBackupInfo()
    {
        return $this->BackupInfo;
    }

    /**
     * @param \Tradesy\Innobackupex\Backup\Info $BackupInfo
     */
    public function setBackupInfo($BackupInfo)
    {
        $this->BackupInfo = $BackupInfo;
    }

    /**
     * @return EncryptionConfiguration
     */
    public function getEncryptionConfiguration()
    {
        return $this->encryption_configuration;
    }

    /**
     * @param EncryptionConfiguration $encryption_configuration
     */
    public function setEncryptionConfiguration($encryption_configuration)
    {
        $this->encryption_configuration = $encryption_configuration;
    }
}