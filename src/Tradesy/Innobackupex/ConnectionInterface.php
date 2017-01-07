<?php

namespace Tradesy\Innobackupex;

interface ConnectionInterface
{
    /**
     * @return ConnectionResponse
     */
    function executeCommand($command);

    /**
     * @param string $file
     * @return string
     */
    function getFileContents($file);

    /**
     * @param string $file
     * @param string $contents
     * @param int $mode
     * @return bool
     */
    function writeFileContents($file, $contents, $mode = 0644);

    /**
     * @param string $file
     * @return boolean
     */
    function file_exists($file);

    /**
     * @return resource
     */
    function getConnection();

    public function verify();

    public function scandir($directory);

    /**
     * @return ConnectionInterface
     */
    public function mute();

    /**
     * @return ConnectionInterface
     */
    public function unmute();
}