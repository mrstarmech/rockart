<?php

/* @var $this yii\web\View */
/* @var $archsite Archsite */
/* @var $area Area */
/* @var $petroglyphs Array */

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\Archsite;
use common\models\Area;
use common\models\Petroglyph;

$this->title = $area->name;

$this->params['breadcrumbs'] = [
    ['label' => Yii::t('app', 'Site'), 'url' => ['archsite/'.$area->archsite_id]],
    $this->title,
];

?>

<?= $this->render('../layouts/_filter_bar', [
    'petroglyphs' => $petroglyphs,
    'filter' => $filter,
]) ?>


<?php

$script = <<< JS

$(document).ready(function () {
    var container = $('.collection');

    container.imagesLoaded(function () {
        container.masonry({itemSelector: '.msnry'});
    });
});

function filter(){
    var filter = ".petroglyph-card";
    if ($('#epoch_button').val() != "epoch_all") filter += '.'+$('#epoch_button').val();
    if ($('#culture_button').val() != "culture_all") filter += '.'+$('#culture_button').val();
    if ($('#method_button').val() != "method_all") filter += '.'+$('#method_button').val();
    if ($('#style_button').val() != "style_all") filter += '.'+$('#style_button').val();

    $('.petroglyph-card').removeClass('msnry');
    $('.collection').masonry('destroy');
    $('.petroglyph-card').hide();
    $(filter).addClass('msnry');
    $(filter).show();
    $('.collection').masonry({itemSelector: '.msnry'});

}

$(document).ready(function() {
    $("#imagetype_dropdown li a").click(
        function () { $('#imagetype_button').html($(this).text() + ' <span class="caret"></span>');
        });
    
    $("#epoch_dropdown li a").click(function () {
         $('#epoch_button').html($(this).text() + ' <span class="caret"></span>');
         $('#epoch_button').val($(this).attr('id'));
         filter()});
    $("#culture_dropdown li a").click(function () {
         $('#culture_button').html($(this).text() + ' <span class="caret"></span>');
         $('#culture_button').val($(this).attr('id'));
         filter()});
    $("#method_dropdown li a").click(function () {
         $('#method_button').html($(this).text() + ' <span class="caret"></span>');
         $('#method_button').val($(this).attr('id'));
         filter()});
    $("#style_dropdown li a").click(function () {
         $('#style_button').html($(this).text() + ' <span class="caret"></span>');
         $('#style_button').val($(this).attr('id'));
         filter()});
});


JS;

$this->registerJsFile('/js/masonry/masonry.pkgd.min.js', ['depends' => ['yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile('/js/masonry/imagesloaded.pkgd.min.js', ['depends' => ['yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile('/js/archsitemanage.js?20200501');
$this->registerJs($script, yii\web\View::POS_READY);
$this->registerCssFile('css/archsite.css?20200317', ['depends' => ['yii\bootstrap\BootstrapPluginAsset']]);
$this->registerCssFile('css/petroglyph.css', ['depends' => ['yii\bootstrap\BootstrapPluginAsset']]);
?>

<?= newerton\fancybox\FancyBox::widget([
    'target' => 'a[rel=findImages]',
    'helpers' => true,
    'mouse' => true,
    'config' => [
        'maxWidth' => '90%',
        'maxHeight' => '90%',
        'playSpeed' => 7000,
        'padding' => 0,
        'fitToView' => false,
        'width' => '70%',
        'height' => '70%',
        'autoSize' => false,
        'closeClick' => false,
        'openEffect' => 'elastic',
        'closeEffect' => 'elastic',
        'prevEffect' => 'elastic',
        'nextEffect' => 'elastic',
        'closeBtn' => false,
        'openOpacity' => true,
        'helpers' => [
            'title' => ['type' => 'float'],
            'buttons' => [],
            'thumbs' => ['width' => 68, 'height' => 50],
            'overlay' => [
                'css' => [
                    'background' => 'rgba(0, 0, 0, 0.8)'
                ]
            ]
        ],
    ]
]) ?>

<?php if (empty($area->image)): ?>
    <?php if (Yii::$app->user->can('manager')): ?>
        <?= Html::a(Yii::t('app', 'Edit'), ['manager/area-update', 'id' => $area->id], ['class' => 'btn btn-primary pull-right']) ?>
    <?php endif; ?>
    <h1><?= Html::encode($area->name) ?></h1>
    <?= $area->description ?>
<?php else: ?>
    <div class="pull-left poster col-xs-6">
        <?= Html::a(Html::img(Area::SRC_IMAGE . '/' . $area->thumbnailImage, [
            'class' => 'img-responsive'
        ]), Area::SRC_IMAGE . '/' . $area->image, [
            'rel' => 'findImages'
        ]); ?>
    </div>
    <?php if (Yii::$app->user->can('manager')): ?>
        <?= Html::a(Yii::t('app', 'Edit'), ['manager/area-update', 'id' => $area->id], ['class' => 'btn btn-primary pull-right']) ?>
    <?php endif; ?>
    <h1><?= Html::encode($area->name) ?></h1>
    <?= $area->description ?>
<?php endif; ?>
<?php if (!empty($area->publication)): ?>
    <h3><?= Yii::t('app', 'Publications') ?></h3>
    <?= $area->publication ?>
<?php endif; ?>

    <div class="clearfix"></div>

<?php if (!empty($petroglyphs)): ?>
    <h2><?= Yii::t('app', 'Panels') ?></h2>
    <div class="row collection" id="petroglyph_container">
        <?php foreach ($petroglyphs as $petroglyph): ?>
            <?php
            $class = "";
            foreach($petroglyph->epochs as $epoch) $class .= " epoch_" . $epoch->id;
            foreach($petroglyph->cultures as $culture) $class .= " culture_" . $culture->id;
            foreach($petroglyph->methods as $method) $class .= " method_" . $method->id;
            foreach($petroglyph->styles as $style) $class .= " style_" . $style->id;
            if(isset($petroglyph->area_id)) $class .= " area_" . $petroglyph->area_id;
            ?>
            <div id="card_<?=$petroglyph->id?>" class="petroglyph-card <?= $class?> col-xs-12 col-sm-4 col-md-3 msnry">
                <?php if (!empty($petroglyph->image)): ?>

                    <a href="<?= Url::to(['petroglyph/view', 'id' => $petroglyph->id]) ?>" class="petroglyph-item" >
                        <div class="row" id="<?=$petroglyph->id?>">
                            <div class="image-origin" style="display:block">
                                <?= Html::img(Petroglyph::SRC_IMAGE . '/' . $petroglyph->thumbnailImage, ['class' => 'img-responsive']) ?>
                            </div>
                            <?php if (!empty($petroglyph->im_dstretch)): ?>
                                <div class="image-dstretch" style="display: none">
                                    <?= Html::img(Petroglyph::SRC_IMAGE . '/' . $petroglyph->thumbnailImDstretch, ['class' => 'img-responsive']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($petroglyph->im_drawing)): ?>
                                <div class="image-drawing" style="display: none">
                                    <?= Html::img(Petroglyph::SRC_IMAGE . '/' . $petroglyph->thumbnailImDrawing, ['class' => 'img-responsive']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($petroglyph->im_reconstruction)): ?>
                                <div class="image-reconstruction" style="display:none">
                                    <?= Html::img(Petroglyph::SRC_IMAGE . '/' . $petroglyph->thumbnailImReconstr, ['class' => 'img-responsive']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($petroglyph->im_overlay)): ?>
                                <div class="image-overlay" style="display:none">
                                    <?= Html::img(Petroglyph::SRC_IMAGE . '/' . $petroglyph->thumbnailImOverlay, ['class' => 'img-responsive']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <h4>
                            <?php if (!empty($petroglyph->index)):?><?= $petroglyph->index ?>. <?endif?><?= $petroglyph->name ?>
                        </h4>
                        <?/*= $petroglyph->annotation */?>
                    </a>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>