<?php

namespace Tradesy\Innobackupex;

interface SaveInterface
{

    public function save($filename);

    public function testSave();

    public function cleanup();

    public function verify();



}