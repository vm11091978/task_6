<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Iblock;

if ($this->startResultCache())
{
    //$this - экземпляр DemoClass
    $arResult = $this->muFunction($arParams["IBLOCK_ID"]);
}

$this->includeComponentTemplate();
