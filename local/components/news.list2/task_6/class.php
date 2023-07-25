<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Iblock;

class DemoClass extends CBitrixComponent
{
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

    public function muFunction($idIBlock)
    {
        $filter = self::getFilterValueOrShowError($idIBlock);
        // если переменная $filter не получена, значит возникла ошибка, и дальнейший код выполнять не нужно
        if (!$filter)
            return;

        $arFilter = array("IBLOCK_LID" => SITE_ID, "ACTIVE" => "Y", $filter);

        $additionalCacheID = false;
        if ($this->startResultCache($arParams["CACHE_TIME"], $additionalCacheID))
        {
            $arResult["ITEMS"] = array();
            // $arResult["ELEMENTS"] = array();
            $arGroupBy = Array("IBLOCK_ID");
            $rsElement = CIBlockElement::GetList(["TIMESTAMP_X" => "DESC"], $arFilter, false);

            $arResult["IdElementIdBlock"] = array();
            while ($row = $rsElement->GetNext()) // ->Fetch()
            {
                $id = (int)$row["ID"];
                $arResult["ITEMS"][$id] = $row;
                // $arResult["ELEMENTS"][] = $id;

                // Получим массив такого вида: Array( [3] => 1 [2] => 1 [345] => 9 [343] => 9 ... [335] => 1 [336] => 1 )
                $arResult["IdElementIdBlock"][$id] = $row["IBLOCK_ID"];
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
            $arResult["IBLOCKS"] = array();
            foreach ($arResult["IdElementIdBlock"] as $element_id => $block_id)
            {
                foreach ($arResult["ITEMS"] as $key => $value)
                {
                    if ($element_id == $key)
                        $arResult["IBLOCKS"][$block_id][$element_id] = $value;
                }
            }
            unset($arResult["ITEMS"]);
            unset($arResult["IdElementIdBlock"]);
            // print_r($arResult['IBLOCKS']);

            return $arResult;
        }
    }

    private static function getFilterValueOrShowError($idIBlock)
    {
        $rsElement = CIBlockElement::GetList([], ['IBLOCK_ID' => $idIBlock]);
        if ($idIBlock == 0) // $idIBlock (м.б.) равна нулю, если 'IBLOCK_ID' не задан или в нём используются нецифровые символы
        {
            $filter = ['IBLOCK_TYPE' => 'news'];
        }
        else
        {
            if (!$rsElement->GetNext()) {
                ShowError("Инфоблока с таким ID не существует!");
                return;
            }
            $filter = ['IBLOCK_ID' => $idIBlock];
        }

        return $filter;
    }
}
