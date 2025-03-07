<?php

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

$moduleId = 'belov.splitter';
Loc::loadMessages(__FILE__);

$request = Application::getInstance()->getContext()->getRequest();

if ($request->isPost()) {
    Option::set($moduleId, 'properties', $request->getPost('properties'));
}

$options = [
    [
        'properties',
        Loc::getMessage('BELOV_SPLITTER_PROPERTIES_LABEL'),
        Option::get($moduleId, 'properties'),
        ['textarea', '12', '120'],
    ]
];

$aTabs = [
    [
        'DIV' => 'settings',
        'TAB' => Loc::getMessage('BELOV_SPLITTER_SETTINGS_TAB'),
        'TITLE' => Loc::getMessage('BELOV_SPLITTER_SETTINGS_TITLE'),
        'OPTIONS' => $options,
    ],
];
$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>
<?php $tabControl->Begin() ?>

<form method="post"
      action="<?= $request->getRequestUri() ?>"
>
    <?php bitrix_sessid_post() ?>

    <?php foreach ($aTabs as ['OPTIONS' => $options]): ?>
        <?php $tabControl->BeginNextTab() ?>
        <?php __AdmSettingsDrawList($moduleId, $options); ?>
    <?php endforeach; ?>

    <?php $tabControl->Buttons() ?>
    <input type="submit" name="Update" value="<?= Loc::getMessage('MAIN_SAVE') ?>" class="adm-btn-save">
</form>
<?php $tabControl->End() ?>





