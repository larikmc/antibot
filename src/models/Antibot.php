<?php

namespace larikmc\Antibot\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "antibot".
 *
 * @property int $id
 * @property int $date
 * @property string $ip
 * @property string|null $referer
 * @property string|null $os
 * @property string|null $agent
 * @property string|null $page
 * @property string|null $status
 */
class Antibot extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'antibot'; // Имя таблицы в базе данных
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['date', 'ip'], 'required'],
            ['date', 'integer'],
            [['referer', 'agent', 'page'], 'string'],
            [['ip', 'os', 'status'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date' => 'Дата',
            'ip' => 'IP',
            'referer' => 'Реферер',
            'os' => 'OS',
            'agent' => 'User Agent',
            'page' => 'Страница',
            'status' => 'Статус',
        ];
    }
}