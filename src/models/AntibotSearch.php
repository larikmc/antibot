<?php

namespace larikmc\Antibot\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * AntibotSearch represents the model behind the search form of `larikmc\Antibot\models\Antibot`.
 * AntibotSearch представляет модель для формы поиска по модели `larikmc\Antibot\models\Antibot`.
 */
class AntibotSearch extends Antibot // Наследуемся от Antibot из того же пространства имен
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'date'], 'integer'],
            [['ip', 'referer', 'os', 'agent', 'page', 'status'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     * Создает экземпляр поставщика данных с примененным поисковым запросом.
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Antibot::find();

        // add conditions that should always apply here
        // Добавьте условия, которые должны применяться всегда.

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 100,
            ],
            'sort'=> ['defaultOrder' => ['id' => SORT_DESC]]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            // раскомментируйте следующую строку, если вы не хотите возвращать записи при неудачной валидации
            return $dataProvider;
        }

        // grid filtering conditions
        // Условия фильтрации сетки
        $query->andFilterWhere([
            'id' => $this->id,
            'date' => $this->date, // Добавлено для фильтрации по дате как integer
        ]);

        $query->andFilterWhere(['like', 'ip', $this->ip])
            ->andFilterWhere(['like', 'referer', $this->referer])
            ->andFilterWhere(['like', 'os', $this->os])
            ->andFilterWhere(['like', 'agent', $this->agent])
            ->andFilterWhere(['like', 'page', $this->page])
            ->andFilterWhere(['like', 'status', $this->status]);

        return $dataProvider;
    }
}