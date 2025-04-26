<?
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\HttpApplication;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\SiteTemplateTable;

Loc::loadMessages(__FILE__);

$request = HttpApplication::getInstance()->getContext()->getRequest();

$module_id = htmlspecialcharsbx($request["mid"] != "" ? $request["mid"] : $request["id"]);

Loader::includeModule($module_id);


// local function for build iblocks tree
$getIblocksTree = function()
{
    static $iblocks = null;

    if ($iblocks !== null)
    {
        return $iblocks;
    }

    $iblocks = [];
    if (\Bitrix\Main\Loader::includeModule('iblock'))
    {
        // first gets types
        $iblockTypes = [];
        $res = \CIBlockType::getList();
        while($row = $res->fetch())
        {
            if ($typeLang = \CIBlockType::getByIDLang($row['ID'], LANG))
            {
                $iblockTypes[$typeLang['IBLOCK_TYPE_ID']] = [
                    'NAME' => $typeLang['NAME'],
                    'SORT' => $typeLang['SORT']
                ];
            }
        }

        // and iblocks then
        $res = \CIBlock::getList(['sort' => 'asc']);
        while ($row = $res->GetNext(true, false))
        {
            if (!isset($iblocks[$row['IBLOCK_TYPE_ID']]))
            {
                $iblocks[$row['IBLOCK_TYPE_ID']] = [
                    'ID' => $row['IBLOCK_TYPE_ID'],
                    'NAME' => $iblockTypes[$row['IBLOCK_TYPE_ID']]['NAME'],
                    'SORT' => $iblockTypes[$row['IBLOCK_TYPE_ID']]['SORT'],
                    'ITEMS' => []
                ];
            }
            $iblocks[$row['IBLOCK_TYPE_ID']]['ITEMS'][] = [
                'ID' => $row['ID'],
                'NAME' => $row['NAME']
            ];
        }

        // sorting by sort
        usort($iblocks,
            function($a, $b)
            {
                if ($a['SORT'] == $b['SORT'])
                {
                    return 0;
                }
                return ($a['SORT'] < $b['SORT']) ? -1 : 1;
            }
        );

        return $iblocks;
    }
};

// options
$allOptions[] = array(
    'header',
    Loc::getMessage('BO_ALT2IMG_OPTIONS_TAB_COMMON')
);
$allOptions[] = array(
    'source_iblocks',
    Loc::getMessage('BO_ALT2IMG_IBLOCKS') . ':',
    array(
        'selectboxtree',
        $getIblocksTree(),
        'multiple="multiple" size="10"'
    )
);
$allOptions[] = array(
    'header2',
    Loc::getMessage('BO_ALT2IMG_EXE_TAB_COMMON')
);
$allOptions[] = array(
    'exe_btn',
    Loc::getMessage('BO_ALT2IMG_EXE_BTN')
);

// tabs
$tabControl = new \CAdmintabControl('tabControl', array(
    array('DIV' => 'edit1', 'TAB' => Loc::getMessage('BO_ALT2IMG_OPTIONS_TAB_NAME'), 'ICON' => ''),
    array('DIV' => 'edit2', 'TAB' => Loc::getMessage('BO_ALT2IMG_EXE_TAB_NAME'), 'ICON' => '')
));

// post save
if ($request->isPost() && check_bitrix_sessid())
{
    foreach ($allOptions as $arOption)
    {
        if ($arOption[0] == 'header')
        {
            continue;
        }
        $name = $arOption[0];
        if ($arOption[2][0] == 'selectboxtree')
        {
            $val = '';
            if (isset($$name))
            {
                for ($j=0; $j<count($$name); $j++)
                {
                    if (trim(${$name}[$j]) <> '')
                    {
                        $val .= ($val <> '' ? ',':'') . trim(${$name}[$j]);
                    }
                }
            }
        }
        else
        {
            $val = $$name;
        }

        $val = trim($val);

        \COption::setOptionString($module_id, $name, $val);
    }
    LocalRedirect($APPLICATION->GetCurPage()."?mid=".$module_id."&lang=".LANG);
}
?>


    <link rel="stylesheet" href="<?= '/bitrix/css/' . $module_id . '/admin_styles.css' ?>">
    <script>
        if (!window.jQuery) {
            document.write("<script src='https://code.jquery.com/jquery-3.5.1.min.js'><\/script>");
        }
    </script>
    <script src="<?= '/bitrix/js/' . $module_id . '/admin_js.js' ?>"></script>
    <form method="post" action="<?= $APPLICATION->GetCurPage()?>?mid=<?= urlencode($mid)?>&amp;lang=<?= LANGUAGE_ID?>"><?
        $tabControl->Begin();
        $tabControl->BeginNextTab();
        foreach($allOptions as $Option):
            if ($Option[0] == 'header')
            {
                ?>
                <tr class="heading">
                    <td colspan="2">
                        <?= $Option[1];?>
                    </td>
                </tr>
                <?if (isset($Option[2])):?>
                <tr>
                    <td></td>
                    <td>
                        <?
                        echo BeginNote();
                        echo $Option[2];
                        echo EndNote();
                        ?>
                    </td>
                </tr>
            <?endif;?>
                <?
                continue;
            }
            $type = $Option[2];

            $val = \COption::getOptionString(
                $module_id,
                $Option[0],
                isset($Option[3]) ? $Option[3] : null
            );

            ?>
            <?if ($Option[0] == 'source_iblocks'):?>
                <tr>
                    <td valign="top" width="40%"><?
                        if ($type[0]=='checkbox')
                        {
                            echo '<label for="' . \htmlspecialcharsbx($Option[0]) . '">'.$Option[1].'</label>';
                        }
                        else
                        {
                            echo $Option[1];
                        }
                        ?></td>
                    <td valign="middle" width="60%"><?
                        if ($type[0] == 'selectboxtree'):
                            $arr = $type[1];
                            $currValue = explode(',', $val);

                            $output = '<select name="'.\htmlspecialcharsbx($Option[0]).'[]"'.$type[2].'>';
                            $output .= '<option></option>';
                            foreach ($getIblocksTree() as $rowType)
                            {
                                $strIBlocksCpGr = '';
                                foreach ($rowType['ITEMS'] as $rowIb)
                                {
                                    if (in_array($rowIb['ID'], $currValue))
                                    {
                                        $sel = ' selected="selected"';
                                    }
                                    else
                                    {
                                        $sel = '';
                                    }
                                    $strIBlocksCpGr .= '<option value="' . $rowIb['ID'] . '"' . $sel . '>' .
                                        $rowIb['NAME'] .
                                        '</option>';
                                }
                                if ($strIBlocksCpGr != '')
                                {
                                    $output .= '<optgroup label="'.$rowType['NAME'].'">';
                                    $output .= $strIBlocksCpGr;
                                    $output .= '</optgroup>';
                                }
                            }
                            $output .= '</select>';
                            echo $output;
                        endif;
                        ?>
                    </td>
                </tr>
            <?endif;?>
        <?endforeach;
        // exe tab
        $tabControl->BeginNextTab();
        foreach($allOptions as $Option):
            if ($Option[0] == 'header2')
            {
                ?>
                <tr class="heading">
                    <td colspan="2">
                        <?= $Option[1];?>
                    </td>
                </tr>
                <?if (isset($Option[2])):?>
                <tr>
                    <td></td>
                    <td>
                        <?
                        echo BeginNote();
                        echo $Option[2];
                        echo EndNote();
                        ?>
                    </td>
                </tr>
            <?endif;?>
                <?
                continue;
            }
            ?>
            <tr>
                <td valign="middle" width="60%"><?
                if ($Option[0] == 'exe_btn'):?>
                    <div id="result"></div>
					<div class="btn-wrapper">
						<span class="btn addalt-btn" data-ids="<?=\COption::getOptionString(
							$module_id,
							'source_iblocks'
						);?>"><?=$Option[1]?></span>
					</div>
                    <p class="info"><b><? echo(Loc::GetMessage("BO_ALT2IMG_OPTIONS_CLEAR_CACHE")); ?></b></p>
                <?endif; 
                echo $Option[4];?>
            </td></tr>
        <?endforeach;
        $tabControl->Buttons();
        ?>
        <input type="submit" name="apply" value="<? echo(Loc::GetMessage("BO_ALT2IMG_OPTIONS_INPUT_APPLY")); ?>" class="adm-btn-save" />
        <input type="submit" name="default" value="<? echo(Loc::GetMessage("BO_ALT2IMG_OPTIONS_INPUT_DEFAULT")); ?>" />
            <?=bitrix_sessid_post();?>
            <?$tabControl->End();?>
    </form>


