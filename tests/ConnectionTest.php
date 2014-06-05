<?php

namespace extdbunit;

use Yii;
use Intersvyaz\ExtDb\Command;

class ConnectionTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->mockApplication([
            'components' => [
                'db' => [
                    'class' => '\Intersvyaz\ExtDb\Connection',
                    'dsn' => 'sqlite::memory:',
                ],
            ],
        ]);
    }

    /**
     * @covers \Intersvyaz\ExtDb\Connection::createCommand
     */
    public function testCreateCommand()
    {
        $db = Yii::$app->db;
        $db->open();
        $lines = explode(';', file_get_contents(__DIR__.'/data/fixture.sql'));
        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $db->pdo->exec($line);
            }
        }

        $params = ['desc'=>'1', 'noparam' => ['bind'=>false]];

        $command = Yii::$app->db->createCommand(__DIR__ . '/data/test.sql', $params);

        $this->assertInstanceOf('\Intersvyaz\ExtDb\Command', $command);
        $this->assertRegExp('/^select\s+\*\s+from\s+profile\s+where\s+1=1\s+and description = :desc$/', $command->getSql());
        $this->assertEquals(['desc'=>'1'], $command->params);
    }


}
