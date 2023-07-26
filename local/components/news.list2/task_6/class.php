<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Iblock;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Loader;

class DemoClass extends CBitrixComponent
{
    protected $errors = array();

    // подключение языковых файлов (метод подключается автоматически)
    public function onIncludeComponentLang()
    {
        Loc::loadMessages(__FILE__);
    }

    // обработка массива $arParams (метод подключается автоматически)
    // Родительский метод проходит по всем параметрам переданным в $APPLICATION->IncludeComponent
    // и применяет к ним функцию htmlspecialcharsex. В данном случае такая обработка избыточна.
    // Переопределяем.
    public function onPrepareComponentParams($arParams)
    {
        $result = array(
            "CACHE_TYPE" => $arParams["CACHE_TYPE"],
            "CACHE_TIME" => isset($arParams["CACHE_TIME"]) ?$arParams["CACHE_TIME"]: 36000000,
            "IBLOCK_ID" => intval($arParams["IBLOCK_ID"]),
        );
        return $result;
    }

    // выполняет основной код компонента, аналог конструктора (метод подключается автоматически)
    public function executeComponent()
    {
        try {
            $idIBlock = $this->arParams["IBLOCK_ID"];
            $flag = $this->checkIBlock($idIBlock);
            // если переменная $flag получена, значит возникла ошибка, и дальнейший код выполнять не нужно
            if (!$flag) {
                $this->arResult = $this->muFunction($idIBlock);
                $this->includeComponentTemplate();
            }
        } catch (SystemException $e) {
            ShowError($e->getMessage());
        }
    }

    protected function checkIBlock($idIBlock)
    {
        $rsElement = CIBlockElement::GetList([], ['IBLOCK_ID' => $idIBlock]);

        if ($idIBlock != 0 && !$rsElement->GetNext()) {
            ShowError("Инфоблока с таким ID не существует!");
            return true;
        }
    }

    protected function muFunction($idIBlock)
    {
        if ($idIBlock == 0) // $idIBlock (м.б.) равна нулю, если 'IBLOCK_ID' не задан или в нём используются нецифровые символы
            $filter = ['IBLOCK_TYPE' => 'news'];
        else
            $filter = ['IBLOCK_ID' => $idIBlock];

        $arFilter = array("IBLOCK_LID" => SITE_ID, "ACTIVE" => "Y", $filter);

        $additionalCacheID = false;
        if ($this->startResultCache($arParams["CACHE_TIME"], $additionalCacheID))
        {
            $arGroupBy = Array("IBLOCK_ID");
            $rsElement = CIBlockElement::GetList(["TIMESTAMP_X" => "DESC"], $arFilter, false);

            while ($row = $rsElement->GetNext()) // ->Fetch()
            {
                $id = (int)$row["ID"];
                $arResult["ITEMS"][$id] = $row;
                // $arResult["ELEMENTS"][] = $id;

                // Получим массив такого вида: Array( [3] => 1 [2] => 1 [345] => 9 [343] => 9 ... [335] => 1 [336] => 1 )
                $arIdElementIdBlock[$id] = $row["IBLOCK_ID"];
            }
            unset($rsElement);

            foreach ($arResult["ITEMS"] as &$arItem)
            {
                $ipropValues = new Iblock\InheritedProperty\ElementValues($arItem["IBLOCK_ID"], $arItem["ID"]);
                $arItem["IPROPERTY_VALUES"] = $ipropValues->getValues();
                Iblock\Component\Tools::getFieldImageData(
                    $arItem,
                    array('PREVIEW_PICTURE', 'DETAIL_PICTURE'),
                    Iblock\Component\Tools::IPROPERTY_ENTITY_ELEMENT,
                    'IPROPERTY_VALUES'
                );
            }
            unset($arItem);

            // Преобразуем массив данных $arResult["ITEMS"] в массив $arResult['IBLOCKS']
            foreach ($arIdElementIdBlock as $element_id => $block_id)
            {
                foreach ($arResult["ITEMS"] as $key => $value)
                {
                    if ($element_id == $key)
                        $arResult["IBLOCKS"][$block_id][$element_id] = $value;
                }
            }
            unset($arIdElementIdBlock);
            unset($arResult["ITEMS"]);

            return $arResult;
        }
    }
}
