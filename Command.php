<?php
namespace Intersvyaz\ExtDb;

use Yii;

/**
 * @inheritdoc
 * Добавлено:
 * - Возможность передавать в setSql в параметр $sql имя файла с sql запросом.
 * - В setSql добавлен параметр $params, в котором передаются параметры для построения запроса на основе специально оформленного sql.
 *      Параметры принимаются в следующих форматах:
 *      [
 *          'p1'=>'v1',
 *          'p2' => ['value', 'PDO_SQL_TYPE'],
 *          'p3' => ['value', 'bind'=>'text'],
 *          'p4' => [['v1', 'v2', ...]]
 *      ]
 * - bindValues может принимать параметры в указанном формате, этот формат является расширением исходного формата используемого \yii\db\Command::bindValues
 */
class Command extends \yii\db\Command
{
    /**
     * Указание текста sql запроса
     * @param string $sql текст sql запроса или имя sql-файла.
     * @param array $params параметры для построения sql запроса
     *      (@example ['p1'=>'v1', 'p2' => ['value', 'PDO_SQL_TYPE'], 'p3' => ['value', 'bind'=>'text'], 'p4' => [['v1', 'v2', ...]]])
     * @return static
     */
    public function setSql($sql, array $params = [])
    {
        if (!empty($sql)) {
            if (substr($sql, -4) === '.sql')
                $sql = file_get_contents($sql);

            $sql = $this->prepareSql($sql, $params);
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
        $values = $this->simplifyParams($values);
        return parent::bindValues($values);
    }

    /**
     * Функция разбора и подготовки текста sql запроса.
     * @param  string $sql Запрос который нужно подготовить.
     * @param array $params Параметры построения запроса.
     * @return string Готовый текст sql запроса.
     */
    protected function prepareSql($sql, array &$params = [])
    {
        // Разбор многострочных комментариев
        if (preg_match_all('#/\*(\w+)(.+?)\*/#s', $sql, $matches)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i++) {
                $sql = $this->replaceComment($sql, $matches[0][$i], $matches[2][$i], $matches[1][$i], $params);
            }
        }

        // Многоитерационный разбор однострчных комментариев
        while (true) {
            if (preg_match_all('#--\*(\w+)(.+)#', $sql, $matches)) {
                $count = count($matches[0]);
                for ($i = 0; $i < $count; $i++) {
                    $sql = $this->replaceComment($sql, $matches[0][$i], $matches[2][$i], $matches[1][$i], $params);
                }
            } else {
                break;
            }
        }

        return preg_replace("/\n+/", "\n", trim($sql));
    }

    /**
     * Заменяем коментарий в запросе на соответствующе преобразованный блок или удаляем.
     * @param string $query Текст запроса.
     * @param string $comment Заменямый комментарий.
     * @param string $queryInComment Текст внутри комментария.
     * @param string $paramName Имя параметра.
     * @param array $params Массив параметров
     * @return string Запрос с замененным комментирием.
     */
    protected function replaceComment($query, $comment, $queryInComment, $paramName, array &$params)
    {
        if(array_key_exists($paramName, $params)) {
            $paramValue = $params[$paramName];
            if (is_array($paramValue)) {
                $value = isset($paramValue[0]) ? $paramValue[0] : null;
                $bind = isset($paramValue['bind']) ? $paramValue['bind'] : true;
            } else {
                $value = $paramValue;
                $bind = true;
            }

            if ($bind === true && is_array($value)) {
                $valArr = [];
                foreach (array_keys($value) as $keyVal) {
                    $valArr[] = ':' . $paramName . '_' . $keyVal;
                }
                $replacement = implode(',', $valArr);
                $queryInComment = preg_replace('/:@' . preg_quote($paramName) . '/', $replacement, $queryInComment);
            } elseif ($bind === 'text') {
                $queryInComment = preg_replace('/' . preg_quote($paramName) . '/', $value, $queryInComment);
            }
        }else{
            $queryInComment = '';
        }

        return str_replace($comment, $queryInComment, $query);
    }

    /**
     * Конвертирует параметры запроса из расширенного формата в параметры пригодные для \yii\db\Command::bindValues.
     * @param array $params Параметры построения запроса.
     * @return array
     */
    protected function simplifyParams($params)
    {
        if (empty($params)) {
            return $params;
        }

        $newParams = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                if (isset($value[0]) && is_array($value[0])) {
                    foreach ($value[0] as $valKey => $valVal)
                        $newParams[$key . '_' . $valKey] = $valVal;
                } elseif (!isset($value['bind']) || $value['bind'] === true) {
                    if(isset($value[0]) && isset($value[1]))
                        $newParams[$key] = [$value[0],$value[1]];
                    elseif(isset($value[0]))
                        $newParams[$key] = $value[0];
                }
            } else {
                $newParams[$key] = $value;
            }
        }

        return $newParams;
    }
}
