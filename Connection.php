<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Intersvyaz\ExtDb;

use PDO;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\caching\Cache;

/**
 * @todo сделать описание наших изменений
 * Документацию по базовому использованию компонента смотрите @see \yii\db\Command
 */
class Connection extends \yii\db\Connection
{
    /**
     * Creates a command for execution.
     * @param string $sql the SQL statement to be executed
     * @param array $params the parameters to be bound to the SQL statement
     * @return Command the DB command
     */
    public function createCommand($sql = null, $params = [])
    {
        $this->open();
        $command = new Command([
            'db' => $this,
        ]);

        $command->setSql($sql, $params);

        return $command->bindValues($params);
    }
}
