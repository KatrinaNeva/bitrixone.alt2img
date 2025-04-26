<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Iblock\PropertyTable;
use Bitrixone\Alt2Img;

Loc::loadMessages(__FILE__);

class bitrixone_alt2img extends CModule {
    public function __construct(){
        if(file_exists(__DIR__."/version.php")){

            $arModuleVersion = array();

            include_once(__DIR__."/version.php");

            $this->MODULE_ID            = str_replace("_", ".", get_class($this));
            $this->MODULE_VERSION       = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
            $this->MODULE_NAME          = Loc::getMessage("BO_ALT2IMG_NAME");
            $this->MODULE_DESCRIPTION  = Loc::getMessage("BO_ALT2IMG_DESCRIPTION");
            $this->PARTNER_NAME     = Loc::getMessage("BO_ALT2IMG_PARTNER_NAME");
            $this->PARTNER_URI      = Loc::getMessage("BO_ALT2IMG_PARTNER_URI");
        }

        return false;
    }



    public function DoInstall(){

        global $APPLICATION;

        if(CheckVersion(ModuleManager::getVersion("main"), "14.00.00")){

            $this->InstallFiles();
            $this->InstallDB();

            ModuleManager::registerModule($this->MODULE_ID);

            $this->InstallEvents();
        }else{

            $APPLICATION->ThrowException(
                Loc::getMessage("BO_ALT2IMG_INSTALL_ERROR_VERSION")
            );
        }

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("BO_ALT2IMG_INSTALL_TITLE")." \"".Loc::getMessage("BO_ALT2IMG_NAME")."\"",
            __DIR__."/step.php"
        );

        return false;
    }

    public function InstallFiles(){

//        CopyDirFiles(
//            __DIR__."/assets/scripts",
//            Application::getDocumentRoot()."/bitrix/js/".$this->MODULE_ID."/",
//            true,
//            true
//        );
//
//        CopyDirFiles(
//            __DIR__."/assets/styles",
//            Application::getDocumentRoot()."/bitrix/css/".$this->MODULE_ID."/",
//            true,
//            true
//        );
        // copy js files
        if(!CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/local/modules/".$this->MODULE_ID."/install/assets/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js/".$this->MODULE_ID, true, true)){
            throw new Exception(Loc::getMessage("ERRORS_CREATE_DIR",array('#DIR#'=>'bitrix/assets/js')));
        }

        if(!CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/local/modules/".$this->MODULE_ID."/install/assets/css", $_SERVER["DOCUMENT_ROOT"]."/bitrix/css/".$this->MODULE_ID, true, true)){
            throw new Exception(Loc::getMessage("ERRORS_CREATE_DIR",array('#DIR#'=>'bitrix/assets/css')));
            return false;
        }
        return true;
    }

    public function InstallDB(){

        return false;
    }

    public function InstallEvents(){
        RegisterModuleDependences("main", "OnBeforeEndBufferContent", $this->MODULE_ID, "Bitrixone\Alt2Img\Main", "createPropsAtIb", "100");
        //RegisterModuleDependences("iblock", "OnBeforeIBlockElementAdd", $this->MODULE_ID, "Bitrixone\Alt2Img\Main", "OnBeforeIBlockElementAddOrUpdateHandler", "200");
        RegisterModuleDependences("iblock", "OnBeforeIBlockElementUpdate", $this->MODULE_ID, "Bitrixone\Alt2Img\Main", "OnBeforeIBlockElementAddOrUpdateHandler", "300");

        return false;
    }

    public function DoUninstall(){

        global $APPLICATION;

        $this->UnInstallFiles();
        $this->UnInstallDB();
        $this->UnInstallEvents();

        ModuleManager::unRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("BO_ALT2IMG_UNINSTALL_TITLE")." \"".Loc::getMessage("BO_ALT2IMG_NAME")."\"",
            __DIR__."/unstep.php"
        );

        return false;
    }

    public function UnInstallFiles(){

//        Directory::deleteDirectory(
//            Application::getDocumentRoot()."/bitrix/js/".$this->MODULE_ID
//        );
//
//        Directory::deleteDirectory(
//            Application::getDocumentRoot()."/bitrix/css/".$this->MODULE_ID
//        );
        if(file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/js/'.$this->MODULE_ID) && !DeleteDirFilesEx( '/bitrix/js/'.$this->MODULE_ID )){
            throw new Exception(Loc::getMessage("ERRORS_DELETE_FILE",array('#FILE#'=>'bitrix/js/'.$this->MODULE_ID)));
        }
        if(file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/css/'.$this->MODULE_ID) && !DeleteDirFilesEx( '/bitrix/css/'.$this->MODULE_ID )){
            throw new Exception(Loc::getMessage("ERRORS_DELETE_FILE",array('#FILE#'=>'bitrix/css/'.$this->MODULE_ID)));
        }
        return true;
    }

    public function UnInstallDB(){
        Bitrix\Main\Loader::includeModule("bitrixone.alt2img");
		if(defined("ADMIN_SECTION") && ADMIN_SECTION == true){
            $module_id = pathinfo(dirname(__DIR__))["basename"];
            $iblockIds = (string)Option::get($module_id, 'source_iblocks');

            if ($iblockIds !== '')
            {
                $iblockIds = explode(',', $iblockIds);

                foreach ($iblockIds as $id) {
                    if (\Bitrix\Main\Loader::includeModule('iblock')) {

                       $arPropsCodesById = array();

						$arProps = PropertyTable::getList(array(
							'select' => array('*'),
							'filter' => array('IBLOCK_ID' => $id)
						))->fetchAll();
			

						foreach ($arProps as $prop) {
						   
								$arPropsCodesById[$prop["ID"]] = $prop["CODE"];
						   
						}

                        if(in_array("ATT_AUTO_ALT", $arPropsCodesById)) {
                            PropertyTable::delete(
                                array_search('ATT_AUTO_ALT', $arPropsCodesById)
                            );
                        }
                        if(in_array("ATT_TEXT_ALT", $arPropsCodesById)) {
                            PropertyTable::delete(
                                array_search('ATT_TEXT_ALT', $arPropsCodesById)
                            );
                        }
                    }
                }
            }
        }
        //Alt2Img\Main::deletePropsAtIb();
        //RegisterModuleDependences("main", "OnBeforeEndBufferContent", $this->MODULE_ID, "Bitrixone\Alt2Img\Main", "deletePropsAtIb", "100");
        //        При удалении модуля должна отработать функция deletePropsAtIb по удалению свойств ATT_AUTO_ALT и ATT_TEXT_ALT инфоблоков - ее надо написать
        Option::delete($this->MODULE_ID);

        return false;
    }

    public function UnInstallEvents(){
        //UnRegisterModuleDependences("main", "OnBeforeEndBufferContent", $this->MODULE_ID, "Bitrixone\Alt2Img\Main", "deletePropsAtIb");
        UnRegisterModuleDependences("main", "OnBeforeEndBufferContent", $this->MODULE_ID, "Bitrixone\Alt2Img\Main", "createPropsAtIb");
        UnRegisterModuleDependences("main", "OnBeforeIBlockElementUpdate", $this->MODULE_ID, "Bitrixone\Alt2Img\Main", "OnBeforeIBlockElementAddOrUpdateHandler");


        return false;
    }

}