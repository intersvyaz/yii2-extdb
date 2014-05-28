<?php
namespace Intersvyaz\ExtDb;

use Yii;

/**
 * @todo сделать описание наших изменений
 * Документацию по базовому использованию компонента смотрите @see \yii\db\Command
 */
class Command extends \yii\db\Command
{
    /**
     * Specifies the SQL statement to be executed.
     * The previous SQL execution (if any) will be cancelled, and [[params]] will be cleared as well.
     * @param string $sql the SQL statement to be set.
     * @param array $params params for parsing sql query
     *      (@example ['p1'=>'v1', 'p2' => ['value', 'PDO_SQL_TYPE'], 'p3' => ['value', 'bind'=>'text'], 'p4' => [['v1', 'v2', ...]]])
     * @return static this command instance
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
     * Функция разбора и подготовки текста sql запроса.
     * @param  string $sql Запрос который нужно подготовить.
     * @param array $params Параметры построения запроса.
     * @return string Готовый текст sql запроса.
     */
    protected function prepareSql($sql, array &$params = [])
    {
        if (empty($params)) {
            return $sql;
        }

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

        return preg_replace("/\n+/", "\n", $sql);
    }

    /**
     * Заменяем коментарий в запросе на соответствующе преобразованный блок или удаляем.
     * @param string $query Текст запроса.
     * @param string $comment Заменямый комментарий.
     * @param string $queryInComment Текст внутри комментария.
     * @param string $paramName Имя параметра.
     * @param array $params Параметры построения запроса.
     *      (['p1'=>'v1', 'p2' => ['value', 'PDO_SQL_TYPE'], 'p3' => ['value', 'bind'=>'text'], 'p4' => [['v1', 'v2', ...]]])
     * @return string Запрос с замененным комментирием.
     */
    protected function replaceComment($query, $comment, $queryInComment, $paramName, array &$params)
    {
        if (array_key_exists($paramName, $params)) {
            $param = $params[$paramName];
            if (is_array($param)) {
                $bind = isset($param['bind']) ? $param['bind'] : false;
                $value = isset($param[0]) ? $param[0] : null;
            } else {
                $value = $param;
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
        } else {
            $queryInComment = '';
        }

        $query = str_replace($comment, $queryInComment, $query);

        return $query;
    }

    /**
     * Конвертирует параметры запроса из расширенного формата в параметры пригодные для \yii\db\Command::bindValues.
     * @param array $params Параметры построения запроса.
     * @return array
     */
    public function simplifyParams($params)
    {
        if (empty($params)) {
            return $params;
        }

        $newParams = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                if (is_array($value[0])) {
                    foreach ($value[0] as $valKey => $valVal)
                        $newParams[$key . '_' . $valKey] = $valVal;
                } elseif (!isset($value['bind']) || $value['bind'] === true) {
                    $newParams[$key] = $value;
                }
            } else {
                $newParams[$key] = $value;
            }
        }

        return $newParams;
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


}
