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

    public function simplifyParamsData()
    {
        return [
            [[], []],
            [['simpleName' => 'simpleValue'], ['simpleName' => 'simpleValue']],
            [['simpleNameSimpleValueWithType' => ['simpleValue', \PDO::PARAM_STR]], ['simpleNameSimpleValueWithType' => ['simpleValue', \PDO::PARAM_STR]]],
            [['complexNameSimpleValue' => ['simpleValue', 'bind' => true]], ['complexNameSimpleValue' => 'simpleValue']],
            [['complexNameBindText' => ['simpleValue', 'bind' => 'text']], []],
            [['complexNameNoBind' => ['bind' => false]], []],
            [['arrayName' => [[0, 1, 2, 3]]], ['arrayName_0' => 0, 'arrayName_1' => 1, 'arrayName_2' => 2, 'arrayName_3' => 3]],
        ];
    }

    /**
     * @covers       \Intersvyaz\ExtDb\Command::simplifyParams
     * @dataProvider simplifyParamsData
     */
    public function testSimplifyParams($params, $simplifiedParams)
    {
        $cmd = new Command();

        $method = new \ReflectionMethod('\Intersvyaz\ExtDb\Command', 'simplifyParams');
        $method->setAccessible(true);

        $this->assertEquals($simplifiedParams, $method->invoke($cmd, $params));
    }

    public function replaceCommentData()
    {
        /* $query, $comment, $queryInComment, $paramName, $paramValue, $returnQuery */
        return [
            ['begin /*param1 sql */ end', '/*param1 sql */', ' sql ', 'param1', ['param1' => 'foobar'], 'begin  sql  end'],
            ['begin /*param2 :@param */ end', '/*param2 :@param */', ' :@param2 ', 'param2', ['param2' => [[4, 5, 6]]], 'begin  :param2_0,:param2_1,:param2_2  end'],
            ['begin /*param3 :@param */ end', '/*param3 :@param */', ' :@param3 ', 'param3', ['param3' => [[4, 5, 6], 'bind' => true]], 'begin  :param3_0,:param3_1,:param3_2  end'],
            ['begin /*param4 count(*) */ end', '/*param4 count(*) */', ' count(*) ', 'param4', ['param4' => ['bind' => false]], 'begin  count(*)  end'],
            ['begin /*OLOLO OLOLO */ end', '/*OLOLO OLOLO */', ' OLOLO ', 'OLOLO', ['OLOLO' => ['WOLOLO', 'bind' => 'text']], 'begin  WOLOLO  end'],
            ['begin /*NOPARAM OLOLO */ end', '/*NOPARAM OLOLO */', ' OLOLO ', 'NOPARAM', [], 'begin  end'],
        ];
    }

    /**
     * @covers       \Intersvyaz\ExtDb\Command::replaceComment
     * @dataProvider replaceCommentData
     */
    public function testReplaceComment($query, $comment, $queryInComment, $paramName, $params, $returnQuery)
    {
        $cmd = new Command();
        $method = new \ReflectionMethod('\Intersvyaz\ExtDb\Command', 'replaceComment');
        $method->setAccessible(true);

        $this->assertEquals($returnQuery, $method->invokeArgs($cmd, [$query, $comment, $queryInComment, $paramName, &$params]));
    }

    public function prepareSqlData()
    {
        $query = '/*param1 sql1 */
                /*param2 sql2 */
                --*param3 sql3
                --*param6 --*param7 sql7
                /*param4 --*param5 sql5 */
                /*param8 --*param9 --*param10 sql10 */
        ';

        return [
            [$query, ['param1' => 'v1'], '/^\s*sql1\s*$/'],
            [$query, ['param1' => 'v1', 'param2' => ['v2', \PDO::PARAM_STR]], '/^\s*sql1\s*sql2\s*$/'],
            [$query, ['param6' => 'v6'], '/^\s*$/'],
            [$query, ['param6' => 'v6', 'param7' => 'v7'], '/^\s*sql7\s*$/'],
            [$query, ['param4' => 'v4', 'param5' => 'v5'], '/^\s*sql5\s*$/'],
            [$query, ['param8' => 'v8', 'param9' => 'v9', 'param10' => 'v10'], '/^\s*sql10\s*$/'],
            ["sql1\n\n\n\n\nsql2", ['param1' => 'v1'], '/^sql1\nsql2$/'],
            ["sql1", [], '/^sql1$/'],
        ];
    }

    /**
     * @covers       \Intersvyaz\ExtDb\Command::prepareSql
     * @dataProvider prepareSqlData
     */
    public function testPrepareSql($query, $params, $queryPattern)
    {
        $cmd = new Command();
        $method = new \ReflectionMethod('\Intersvyaz\ExtDb\Command', 'prepareSql');
        $method->setAccessible(true);

        $this->assertRegExp($queryPattern, $method->invokeArgs($cmd, [$query, &$params]));
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
        $this->assertEquals(['desc'=>'1'], $command->params);
    }


}
