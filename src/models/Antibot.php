<?php

namespace larikmc\Antibot\models;

use Yii;

/**
 * This is the model class for table "antibot".
 *
 * @property int $id
 * @property int $date
 * @property string $ip
 * @property string|null $referer
 * @property string|null $http
 * @property string|null $agent
 * @property string|null $page
 * @property string|null $status
 */
class Antibot extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'antibot';
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
            [['ip', 'http', 'status'], 'string', 'max' => 255],
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
            'ip' => 'Ip',
            'referer' => 'Referer',
            'http' => 'Http',
            'agent' => 'User Agent',
            'page' => 'Страница',
            'status' => 'Статус',
        ];
    }
}
