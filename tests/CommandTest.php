<?php

namespace extdbunit;

use Yii;
use Intersvyaz\ExtDb\Command;

class CommandTest extends TestCase
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
     * @covers \Intersvyaz\ExtDb\Command::setSql
     */
    public function testSetSql()
    {
        $db = Yii::$app->db;
        $db->open();
        $lines = explode(';', file_get_contents(__DIR__.'/data/fixture.sql'));
        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $db->pdo->exec($line);
            }
        }

        $command = new Command([
            'db' => $db,
        ]);

        $command->setSql(__DIR__ . '/data/test.sql', ['foo'=>'bar']);
        $this->assertRegExp('/^select\s+\*\s+from\s+profile\s+where\s+1=1\s*$/', $command->getSql());

        $command->setSql(__DIR__ . '/data/test.sql', ['desc'=>'1']);
        $this->assertRegExp('/^select\s+\*\s+from\s+profile\s+where\s+1=1\s+and description = :desc$/', $command->getSql());
    }

    /**
     * @covers \Intersvyaz\ExtDb\Command::bindValues
     */
    public function testBindValues()
    {
        $db = Yii::$app->db;
        $db->open();
        $lines = explode(';', file_get_contents(__DIR__.'/data/fixture.sql'));
        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $db->pdo->exec($line);
            }
        }

        $command = new Command([
            'db' => $db,
        ]);

        $params = ['desc'=>'1', 'noparam' => ['bind'=>false]];

        $command->setSql(__DIR__ . '/data/test.sql', $params);
        $command->bindValues($params);

        $this->assertRegExp('/^select\s+\*\s+from\s+profile\s+where\s+1=1\s+and description = :desc$/', $command->getSql());
        $this->assertEquals([':desc'=>'1'], $command->params);
    }


}
