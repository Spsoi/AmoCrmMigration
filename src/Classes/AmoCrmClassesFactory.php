<?php

namespace Classes\AmoCrmClasses;

class AmoCrmClassesFactory
{
    public $amo;
    public function __construct() {
        $this->l = logger('crm/Classes-AmoCrmClasses-AmoCrmClassesFactory.log');
    }

    public function factory() {
		$phpInput = file_get_contents('php://input');
        $this->l->log('AmoCrmClassesFactory phpInput', $phpInput);
        $operation = json_decode($phpInput);
    }
}

