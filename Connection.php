<?php
/**
 * @link http://is74.ru/
 * @copyright Copyright (c) 2014 Интерсвязь
 */

namespace Intersvyaz\ExtDb;

use PDO;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\caching\Cache;

/**
 * @inheritdoc
 */
class Connection extends \yii\db\Connection
{
    /**
     * @inheritdoc
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
