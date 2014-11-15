<?php
namespace Intersvyaz\ExtDb;

use Intersvyaz\SqlParser\Parser;
use Yii;

/**
 * @inheritdoc
 * Добавлено:
 * - Возможность передавать в setSql в параметр $sql имя файла с sql запросом.
 * - В setSql добавлен параметр $params, в котором передаются параметры для построения запроса на основе специально оформленного sql.
 *      Параметры принимаются в следующих форматах:
 *      [
 *          ':p1'=>'v1',
 *          ':p2' => ['value', 'PDO_SQL_TYPE'],
 *          ':p3' => ['value', 'bind'=>'text'],
 *          ':p4' => [['v1', 'v2', ...]]
 *      ]
 * - bindValues может принимать параметры в указанном формате, этот формат является расширением исходного формата используемого \yii\db\Command::bindValues
 */
class Command extends \yii\db\Command
{
    /**
     * Указание текста sql запроса
     * @param string $sql текст sql запроса или имя sql-файла.
     * @param array $params параметры для построения sql запроса
     *      (@example [':p1'=>'v1', ':p2' => ['value', 'PDO_SQL_TYPE'], ':p3' => ['value', 'bind'=>'text'], ':p4' => [['v1', 'v2', ...]]])
     * @return static
     */
    public function setSql($sql, array $params = [])
    {
        if (!empty($sql)) {
            $sql = (new Parser($sql, $params))->getSql();
        }

        return parent::setSql($sql);
    }

    /**
     * Доработанная версия bindValues, умеет принимать массив в формате, который используется парсером (@see \app\components\Command::replaceComment())
     * @param array $values
     * @return static
     */
    public function bindValues($values)
    {
        return parent::bindValues((new Parser('', $values))->getSimplifiedParams());
    }
}
