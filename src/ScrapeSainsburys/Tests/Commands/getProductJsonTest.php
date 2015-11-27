<?php

// src/ScrapeSainsburys/Tests/Commands/getProductJsonTest.php
namespace ScrapeSainsburys\Tests\Commands;

use ScrapeSainsburys\Commands\GetProductJsonCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

require_once 'vendor/autoload.php';

class getProductJsonTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $application = new Application();
        $application->add(new GetProductJsonCommand());

        $command = $application->find('scrapesainsburys:getproductjson');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array());

        $this->assertRegExp('/results/', $commandTester->getDisplay());
    }
}