<?php

namespace Tradesy\Innobackupex\SSH;

use Tradesy\Innobackupex\ConnectionInterface;
use Tradesy\Innobackupex\Exceptions\SSH2AuthenticationException;
use Tradesy\Innobackupex\Exceptions\ServerNotListeningException;
use Tradesy\Innobackupex\Exceptions\SSH2ConnectionException;
use Tradesy\Innobackupex\ConnectionResponse;
use Tradesy\Innobackupex\LogEntry;

/**
 * Class Connection
 * @package Tradesy\Innobackupex
 */
class Connection implements ConnectionInterface
{
    /**
     * @var Configuration
     */
    protected $config;
    /**
     * @var bool
     */
    protected $authenticated = false;
    /**
     * @var resource
     */
    protected $connection;

    /**
     * @var bool
     */
    protected $sudo_all = false;

    /**
     * @var bool
     */
    protected $verbosity = true;

    /**
     * @return boolean
     */
    public function isSudoAll()
    {
        return $this->sudo_all;
    }

    /**
     * @param boolean $sudo_all
     */
    public function setSudoAll($sudo_all)
    {
        $this->sudo_all = $sudo_all;
    }

    function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->verify();
    }

    public function mute()
    {
        $this->verbosity = false;

        return $this;
    }

    public function unmute()
    {
        $this->verbosity = true;

        return $this;
    }

    /**
     * @throws ServerNotListeningException
     */
    public function verify()
    {
        $this->verifySSHServerListening();
        $this->verifyConnection();
    }

    /**
     * Gets the ssh connection if connection succeeds
     *
     * @param bool $force_reconnect
     *
     * @return resource
     *
     * @throws SSH2ConnectionException
     */
    public function getConnection($force_reconnect = false)
    {
        if ($this->authenticated && !$force_reconnect) {
            return $this->connection;
        }

        $this->connection = ssh2_connect(
            $this->config->host(),
            $this->config->port(),
            $this->config->options()
        );

        if (!$this->connection) {
            throw new SSH2ConnectionException(
                'Connection to SSH Server failed at host: ' . $this->config->host() . ':' . $this->config->port(),
                0
            );
        }

        return $this->connection;
    }

    /**
     * Executes a command at the remote server
     *
     * @param $command
     * @param bool $no_sudo
     *
     * @return ConnectionResponse
     *
     * @throws SSH2ConnectionException
     */
    public function executeCommand($command, $no_sudo = false)
    {
        if ($this->verbosity) {
            LogEntry::logEntry('Executing command ' . $command);
        }

        $stream = ssh2_exec(
            $this->getConnection(),
            ($this->isSudoAll() && !$no_sudo ? 'sudo ' : '' ) . $command ,
            true
        );
        $stderrStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        stream_set_blocking($stream, true);
        stream_set_blocking($stderrStream, true);
        $stdout = stream_get_contents($stream);
        $stderr = stream_get_contents($stderrStream);

        return new ConnectionResponse(
            $command,
            $stdout,
            $stderr
        );
    }

    /**
     * @param string $file
     * @return mixed
     * @throws SSH2ConnectionException
     */
    public function getFileContents($file)
    {
        $temp_file = tempnam($this->getTemporaryDirectoryPath(),"");

        if ($this->verbosity) {
            LogEntry::logEntry('Temp file: ' . $temp_file);
        }

        if(ssh2_scp_recv($this->getConnection(), $file, $temp_file)){
            $contents = file_get_contents($temp_file);
        }else{
            $contents ="";
        }
        unlink($temp_file);
        return $contents;
    }

    /**
     * @return string
     */
    public function getTemporaryDirectoryPath(){
        return "/tmp/";
    }

    /**
     * @param string $file
     * @param string $contents
     * @param int $mode
     *
     * @return bool|void
     *
     * @throws SSH2ConnectionException
     */
    public function writeFileContents($file, $contents, $mode=0644)
    {
        if ($this->verbosity) {
            LogEntry::logEntry('Writing file: ' . $file);
        }

        $temp_file = tempnam($this->getTemporaryDirectoryPath(), "");
        file_put_contents($temp_file, $contents);
        ssh2_scp_send($this->getConnection(), $temp_file, $file, $mode);
        unlink($temp_file);
    }

    /**
     * @throws SSH2AuthenticationException
     * @throws SSH2ConnectionException
     */
    protected function verifyCredentials()
    {
        $resource = ssh2_auth_pubkey_file(
            $this->getConnection(),
            $this->config->user(),
            $this->config->publicKey(),
            $this->config->privateKey(),
            $this->config->passphrase()
        );
        if (!$resource) {
            throw new SSH2AuthenticationException(
                "Authentication  to SSH Server failed. Check credentials: ",
                0
            );
        } else {
            $this->authenticated = true;
        }
    }

    /**
     * @return bool
     * @throws ServerNotListeningException
     */
    protected function verifySSHServerListening()
    {
        $serverConn = @stream_socket_client(
            "tcp://" . $this->config->host() . ":" . $this->config->port(),
            $errno,
            $errstr);

        if ($errstr != '') {
            throw new ServerNotListeningException(
                "SSH Server is unreachable at host: " . $this->config->port() .
                ":" . $this->config->port(),
                0
            );
        }
        fclose($serverConn);
        return true;
    }

    /**
     * @throws SSH2AuthenticationException
     */
    protected function verifyConnection()
    {
        if ($this->authenticated) {
            return;
        } else {
            return $this->verifyCredentials();
        }
    }

    /**
     * @param string $file
     * @return boolean
     */
    public function file_exists($file){
        // Check if file exists
        $command = 'if test -f ' . $file .'; then echo "exists"; fi';
        $result = $this->executeCommand($command);

        return (trim($result->stdout()) === 'exists');
    }

    /**
     * @param string $directory
     * @return mixed
     */
    public function scandir($directory){
        // Get a list of files
        $result = $this->executeCommand('ls -1 ' . $directory . ' | tr \'\n\' \'\0\' | xargs -0 -n 1 basename');

        return array_map('trim', explode("\n", $result->stdout()));
    }
}