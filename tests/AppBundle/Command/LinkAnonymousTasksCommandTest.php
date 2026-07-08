<?php

namespace Tests\AppBundle\Command;

use AppBundle\Command\LinkAnonymousTasksCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LinkAnonymousTasksCommandTest extends KernelTestCase
{
    /**
     * Test the CLI command behavior when data is already clean (standard state after fixtures)
     */
    public function testExecuteCommandWhenSchemaIsClean()
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        // Register our custom command within the application context
        $application->add(new LinkAnonymousTasksCommand());

        $command = $application->find('app:tasks:link-anonymous');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $output = $commandTester->getDisplay();

        // Assertions
        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Data clean-up complete', $output);
    }
}
