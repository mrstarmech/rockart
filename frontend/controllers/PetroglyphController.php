<?php
namespace frontend\controllers;

use common\models\Petroglyph;
use Yii;
use yii\base\InvalidParamException;
use yii\data\ActiveDataProvider;
use yii\web\BadRequestHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use frontend\models\ContactForm;
use yii\web\HttpException;

/**
 * Class PetroglyphController
 * @package frontend\controllers
 */
class PetroglyphController extends BaseController
{

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $query = null;
        if ($filter = Yii::$app->request->get('filter'))
            $query = Petroglyph::morphySearch(mb_strtoupper($filter));
        else{
            $query = Petroglyph::find()->where(['deleted' => null]);
            if (!Yii::$app->user->can('manager')) $query->andWhere(['public' => 1]);
        }
        $query->orderBy(['id' => SORT_DESC])->all();

        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 15,
            ],
        ]);

        return $this->render('index', [
            'provider' => $provider,
            'filter' => $filter,
        ]);
    }

    public function actionView($id)
    {
        $query = Petroglyph::find()->where(['id' => $id])->andWhere(['deleted' => null]);

        if (!Yii::$app->user->can('manager')) {
            $query->andWhere(['public' => 1]);
        }

        $petroglyph = $query->one();

        if (empty($petroglyph)) {
            throw new HttpException(404);
        }

        $json_petroglyphs = null;
        $inherit_coords = '';
        if (!$petroglyph->lat || !$petroglyph->lng){
            $area = $petroglyph->area;
            $archsite = $petroglyph->archsite;
            if($area && $area->lat && $area->lng){
                $petroglyph->lat = $area->lat;
                $petroglyph->lng = $area->lng;
                $inherit_coords = 'area';
            } else if ($archsite->lat && $archsite->lng){
                $petroglyph->lat = $archsite->lat;
                $petroglyph->lng = $archsite->lng;
                $inherit_coords = 'archsite';
            }
        }
        if ($petroglyph->lat && $petroglyph->lng) {
            $array_petroglyphs[] = [
                'id' => $petroglyph->id,
                'name' => $petroglyph->name,
                'lat' => $petroglyph->lat,
                'lng' => $petroglyph->lng,
                'image' => Petroglyph::SRC_IMAGE . '/' . $petroglyph->thumbnailImage,
            ];
            $json_petroglyphs = json_encode($array_petroglyphs, JSON_UNESCAPED_UNICODE);
        }

        $mapProvider = Yii::$app->request->get('mapProvider') == 'yandex' ? 'yandex' : 'google';

        return $this->render('view', [
            'json_petroglyphs' => $json_petroglyphs,
            'petroglyph' => $petroglyph,
            'mapProvider' => $mapProvider,
            'inherit_coords' => $inherit_coords,
        ]);
    }
}