<?php
/**
 * @link http://is74.ru/
 * @copyright Copyright (c) 2014 Интерсвязь
 */

namespace Intersvyaz\ExtDb;

/**
 * @inheritdoc
 */
class Connection extends \yii\db\Connection
{
    /**
     * @event [[yii\base\Event|Event]] an event that is triggered before a DB connection is established
     */
    const EVENT_BEFORE_OPEN = 'beforeOpen';

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

    /**
     * @inheritdoc
     */
    protected function createPdoInstance()
    {
        $this->trigger(self::EVENT_BEFORE_OPEN);
        return parent::createPdoInstance();
    }
}
