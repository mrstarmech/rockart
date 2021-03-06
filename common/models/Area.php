<?php


namespace common\models;


use Imagine\Image\Box;
use omgdef\multilingual\MultilingualBehavior;
use omgdef\multilingual\MultilingualQuery;
use Yii;
use yii\helpers\FileHelper;
use yii\imagine\Image;

/**
 * Class Area
 * @package common\models
 * @property integer $id
 * @property string $name
 * @property string $name_en
 * @property string $description
 * @property string $description_en
 * @property string $publication
 * @property string $publication_en
 * @property integer $archsite_id
 * @property double $lat
 * @property double $lng
 * @property string $image
 */
class Area extends \yii\db\ActiveRecord
{
    const DIR_IMAGE = 'storage/web/area';
    const SRC_IMAGE = '/storage/area';
    const SCENARIO_CREATE = 'create';
    const THUMBNAIL_PREFIX = 'thumbnail_';
    const THUMBNAIL_W = 800;
    const THUMBNAIL_H = 500;

    public $fileImage;

    private static function basePath()
    {
        $path = \Yii::getAlias('@' . self::DIR_IMAGE);

        FileHelper::createDirectory($path);

        return $path;
    }

    public function behaviors()
    {
        return [
            'ml' => [
                'class' => MultilingualBehavior::className(),
                'languages' => [
                    'ru' => 'Russian',
                    'en' => 'English',
                ],
                'languageField' => 'locale',
                'defaultLanguage' => 'ru',
                'langForeignKey' => 'area_id',
                'tableName' => '{{%area_language}}',
                'attributes' => [
                    'name',
                    'description',
                    'publication'
                ]
            ],
        ];
    }

    public function rules()
    {
        return [
            [['name', 'name_en', 'archsite_id'], 'required'],
            [['name', 'description','publication'], 'string'],
            [['lat', 'lng'], 'double'],
            ['image', 'string'],
            [['fileImage'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, gif'],
        ];
    }

    public static function find()
    {
        return new MultilingualQuery(get_called_class());
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['name', 'description', 'publication', 'image', 'lat', 'lng', 'archsite_id'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'name' => Yii::t('model', 'Name in Russian'),
            'name_en' => Yii::t('model', 'Name in English'),
            'description' => Yii::t('model', 'Description in Russian'),
            'description_en' => Yii::t('model', 'Description in English'),
            'lat' => Yii::t('model', 'Latitude'),
            'lng' => Yii::t('model', 'Longitude'),
            'image' => Yii::t('model', 'Image'),
            'fileImage' => Yii::t('model', 'Image'),
            'archsite_id' => Yii::t('model', 'Archsite'),
            'publication' => Yii::t('model', 'Publication'),
            'publication_en' => Yii::t('model', 'Publication in English'),
        ];
    }

    public function upload()
    {
        if ($this->validate() and !empty($this->fileImage)) {

            $path = self::basePath();

            if (!empty($this->image) and file_exists($path . '/' . $this->image)) {
                unlink($path . '/' . $this->image);

                if (file_exists($path . '/' . self::THUMBNAIL_PREFIX . $this->image)) {
                    unlink($path . '/' . self::THUMBNAIL_PREFIX . $this->image);
                }
            }

            FileHelper::createDirectory($path);

            $newName = md5(uniqid($this->id));
            $this->fileImage->saveAs($path . '/' . $newName . '.' . $this->fileImage->extension);
            $this->image = $newName . '.' . $this->fileImage->extension;

            $sizes = getimagesize($path . '/' . $newName . '.' . $this->fileImage->extension);
            if ($sizes[0] > self::THUMBNAIL_W) {
                Image::thumbnail($path . '/' . $newName . '.' . $this->fileImage->extension, self::THUMBNAIL_W, self::THUMBNAIL_H)
                    ->resize(new Box(self::THUMBNAIL_W, self::THUMBNAIL_H))
                    ->save($path . '/' . self::THUMBNAIL_PREFIX . $newName . '.' . $this->fileImage->extension, ['quality' => 80]);
            }

            $this->scenario = self::SCENARIO_CREATE;
            return $this->save();
        } else {
            return false;
        }
    }

    public function getThumbnailImage()
    {
        $path = self::basePath();

        if (file_exists($path . '/' . self::THUMBNAIL_PREFIX . $this->image)) {
            return self::THUMBNAIL_PREFIX . $this->image;
        } else {
            return $this->image;
        }
    }

    /**
     * @return \yii\db\ActiveRecord[]
     */
    public function getPetroglyphs()
    {
        $query = Petroglyph::find()->where(['area_id'=>$this->id]);
        $query->andWhere(['deleted' => null])->orderBy(['id' => SORT_DESC]);
        if (!Yii::$app->user->can('manager')) {
            $query->andWhere(['public' => 1]);
        }
        return $query->all();
    }

    public function beforeDelete()
    {
        $baseDir = self::basePath();

        if (!empty($this->image) and file_exists($baseDir . '/' . $this->image)) {
            unlink($baseDir . '/' . $this->image);
        }

        return parent::beforeDelete();
    }

    public function searchPetroglyphs($search)
    {
        //TODO: Этот запрос точно должен быть таким? Мб просто Petroglyph->find()? На мой взгляд тут просто переписан MultilingualQuery()
        $query = $this->hasMany(Petroglyph::className(), ['area_id' => 'id'])->join('LEFT JOIN', 'petroglyph_language', 'petroglyph_language.petroglyph_id = petroglyph.id');
        $query->where(['deleted' => null])->andWhere(['locale' => Yii::$app->language])->orderBy(['id' => SORT_DESC]);
        if (!Yii::$app->user->can('manager')) {
            $query->andWhere(['public' => 1]);
        }

        $dir = '../../vendor/phpmorphy-0.3.7/dicts';
        if (Yii::$app->language == "ru") $lang = 'ru_RU';
        else $lang = 'en_EN';
        $opts = array(
            'storage' => PHPMORPHY_STORAGE_FILE,
        );
        try {
            $morphy = new \phpMorphy($dir, $lang, $opts);
        } catch(phpMorphy_Exception $e) {
            die('Error occured while creating phpMorphy instance: ' . $e->getMessage());
        }
        $forms = $morphy->getAllForms($search);
        if (empty($forms)) $forms = $search;
        $query = $query->andWhere(['or',['or like', 'description', $forms], ['or like', 'name', $forms]]);
        return $query;
    }
}