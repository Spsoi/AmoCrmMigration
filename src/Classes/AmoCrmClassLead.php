<?php

namespace Classes\AmoCrmClasses;
use Classes\YClientsClasses\YClientsLabelClass;
use Classes\UnixTimeClasses\UnixTimeToDate;
use Classes\UnixTimeClasses\DateToUnixTime;
use Classes\StringClasses\StringManipulation;
use App\Services\AmoCrm\SettingsAccount;
use App\Models\Company;
use App\Models\Lead_color;
class AmoCrmClassLead
{
    public $amo;
    public $catalogId = 5085;
    public function __construct() 
    {
        $this->l = logger('crm/Classes-AmoCrmClasses-AmoCrmClassLead.log');
        if (!empty($_POST['account_id'])) {
            if ($_POST['account_id'] == 28934125) { // party hard
                $this->catalogId = 5085;
            }
            if ($_POST['account_id'] == 28692862) { // party time
                $this->catalogId = 5493;
            }
            $this->amo =  SettingsAccount::setAccountSettings($_POST['account_id']);
        }
    }

    // function createLead($entity, $data)
    // {
    //     // $this->l->log('createLead');
    //     $amo = $this->amo;
    //     $lead = $entity->createLead();
    //     $lead->name = 'Автосделка с YClients';
    //     // $lead->sale                 = isset($this->amoCRM['payment']['amount']) ? $this->amoCRM['payment']['amount'] : null;
    //     $lead->responsible_user_id  = $entity->responsible_user_id;
    //     $lead->pipeline_id          = '3446683'; // основная воронка
    //     $lead->status_id            = '34239130';
    //     // if ($lead->cf()->byId(681691) && !empty($data->data->id)) {
    //     //     $lead->cf()->byId(681691)->setValue($data->data->id);
    //     // }

    //     $lead->save();
    //     $lead = $amo->leads()->find($lead->id);
    //     self::UpdatingFields($lead, $data, 5085);
    // }   

    // public function editLead($lead, $record)
    // {
    //     $amo = $this->amo;
    //     // $this->l->log($record);
    //     $lead->name = $record->data->client->name;
    //     // $lead->save();
        
    //     // $lead = $amo->leads()->find($lead->id);
    //     self::UpdatingFields($lead, $record, $this->catalogId);
    // }    

    public function editLead(object $lead, object $record, $amo = null)
    {
        $amo = $this->amo;
        if ($lead->cf('Цвет записи') && !empty($record->data->custom_color)) {

            $customColor = $record->data->custom_color;
            $colorDataBase = Lead_color::get(['hex' => $record->data->custom_color])->first();
            $customColorName = $colorDataBase->name;
            $colors = $lead->cf('Цвет записи')->field->enums;
            $colorEnum = '';
            foreach ($colors as $key => $color) {
                $colorArr = explode(' ', $color);
                
                if ($colorArr[0] == $customColorName) {
                    $colorEnum = $key;
                    break;
                }
            }
            if (!empty($colorEnum)) {
                $lead->cf('Цвет записи')->setEnum($colorEnum);
            }   
        }
        if (isset($record->data->client->name)) {
            if ($lead->main_contact_id) {
                $contact = $this->amo->contacts()->find($lead->main_contact_id);
                if ($contact->name !== $record->data->client->name) {
                    $contact->name = $record->data->client->name;
                    $contact->save();
                }
            }
            
        }

        // main_contact_id
        $this->UpdatingFields($lead, $record, $this->catalogId);
    }
    // обновляем поля сделки
    public function UpdatingFields ($entity, $data, $catalog_id) {
        $amo = $this->amo;
        if (!isset($data->data->services)){$this->l->log('ошибка', $data);return true;}
        self::detachProduct($entity, $catalog_id);
        self::attachProduct($entity, $data->data->services, $catalog_id);
        $company = Company::find($data->data->company_id);
        $entityForm = [
            'ID записи ЮК' => !empty($data->data->id) ? $data->data->id : null, // номер брони
            'ID компании ЮК' => !empty($data->data->company_id) ? $data->data->company_id : null, // номер компании
            
            'Дата визита' => !empty($data->data->date) ? strtotime($data->data->date) : null, // Дата проведения мероприятия
            'Время начала мероприятия' => !empty($data->data->datetime) ? UnixTimeToDate::convertUnixTimeToH_M(strtotime($data->data->datetime)) : null, // Начало мероприятия
            'Время окончания мероприятия' => !empty($data->data->datetime) ?  UnixTimeToDate::convertUnixTimeToH_M(strtotime($data->data->datetime)+ $data->data->seance_length) : null, // Дата окончания
            'Длительность' => !empty($data->data->datetime) ? (strtotime($data->data->datetime) + $data->data->seance_length - strtotime($data->data->datetime)) / 60 : null, // Длительность
            
            'Комментарий (YC)' => !empty($data->data->comment) ? $data->data->comment: null, // Комментарий
            'Комната' => !empty($data->data->staff->name) ? $data->data->staff->name: null, // Зал
        ];
        if (strlen($company->title) > 0) {
            $entityForm['Локация'] = StringManipulation::fcToUpper($company->title);
        }
        foreach ($entityForm as $CFVName => $field) {
            if ($entity->cf($CFVName) && !empty($field)) {
                $entity->cf($CFVName)->setValue($field);
            }
        }

        // if ($data->data->visit_attendance == 1 && $data->data->paid_full == 0) { // Если Клиент статус клиент пришёл, ордер не оплачен
        //     $entity = $this->amoChangeLeadStatus($entity, 34239133);  // статус в амо "визит состоялся"
        // }

        $entity->save();
        return $entity;
    }

    // Поиск сделкИ по полю и его значению.
    public function searchLeadByField($id, $idField) {
        $leads = $this->amo->leads()->searchByCustomField($id, $idField);
        return $leads->first();
    }

    // Поиск сделкИ по полю
    // Поиск контакта у этой сделки
    // Поиск сделок в определённой воронке у этого контакта
    public function searchLeadByLead($id, $idField, $pipeline_id) {
        $leads = $this->amo->leads()->searchByCustomField($id, $idField);
        if ($lead = $leads->first()) {
            $amoFoundContact = $this->amo->contacts()->find($lead->main_contact_id); // находим контакт
            $secondsLeads = self::getActiveLeadsInPipeline($amoFoundContact, $pipeline_id);
            return $secondsLeads->first();
        }
        return false;
    }

       // Поиск сделОК по полю и его значению.
    public function searchLeadByFields($fieldValue, $fieldId) {
        $lead = $this->amo->leads()->searchByCustomField($fieldValue, $fieldId);
        return $lead->first();
    }
        

    // Получить значение поля
    public function getFieldValue($entity, $idField) {
        return $entity->cf()->byId($idField)->getValue();
    }

    public function getElementsFromCatalog ($catalogId = null) {
        if ($catalogId == null){return true;}
        $amo = $this->amo;
        $catalog_elements = [];
        $catalog = $amo->catalogs()->find($catalogId);
        $catalogResponse = $amo->ajax()->get('/ajax/v1/catalog_elements/list/?catalog_id='.$catalogId.'&json=1&page=1');
        if (empty($catalogResponse) || empty($catalogResponse->response) || empty($catalogResponse->response->catalog_elements)) return;
        $pageTotal = $catalogResponse->response->pagination->pages->total; // колличество страниц
        if (!empty($pageTotal)) {
            $catalog_elements = array_merge($catalog_elements, json_decode(json_encode($catalogResponse->response->catalog_elements),true));

            for ($i = 2; $i <= $pageTotal; $i++) {
                $catalogResponse = $amo->ajax()->get('/ajax/v1/catalog_elements/list/?catalog_id='.$catalogId.'&json=1&page='.$i);
                $catalog_elements = array_merge($catalog_elements, json_decode(json_encode($catalogResponse->response->catalog_elements),true));
            }
        }

        return $catalog_elements;
    }

    // добавление товара в сделку
    public function attachProduct($entity, $products, $catalog_id) {
        // $this->l->log('$products', $products);
        $amo = $this->amo;
        $catalog_elements = self::getElementsFromCatalog($catalog_id);
        // $this->l->log('catalog_elements', $catalog_elements);

        foreach ($products as $key => $product) {
            foreach ($catalog_elements as $element) {
                // $this->l->log('element', $element);
                // $this->l->log('$element[name]', $element['name']);
                if(isset($element['name']) &&  $element['name'] == $product->title) {
                    $entity->attachElement($catalog_id, $element['id'], $product->amount);
                    $elementAmo = $amo->catalogElements()->find($element['id']);
                    // $this->l->log('elementAmo', $elementAmo);
                    // $elementAmo->cf()->byId(681749)->setValue($product->id);
                    // $elementAmo->save();
                    break;
                }
            }
        }
        return $entity;
    }
    // открепить товары от сделки
    public function detachProduct($entity, $catalog_id) {
        // $this->l->log('AmoCrmClassLead->detachProduct');
        // $this->l->log('$entity', $entity->catalog_elements_id);
        $amo = $this->amo;
        foreach ($entity->catalog_elements_id as $id) {
            $data = [
                [
                    "to_entity_id" => $id,
                    "to_entity_type" => "catalog_elements",
                    "metadata" => [
                        "catalog_id" => $catalog_id
                    ]
                ]
            ];
            $amo->ajax()->postJson('/api/v4/leads/'.$entity->id.'/unlink', $data);
        }
        // $entity->save();
        return $entity;
    }

    public function getLeadToStatus ($entity, $pipeline_id, $status_id) 
    {
        $leads = null;
        function foundLead () {
            foreach ($options['pipeline_id'] as $pipeline_id) {
                // $this->l->log('В воронке '.$pipeline_id.' .......');
                foreach ($options['status_id'] as $status_id) {
                    // $this->l->log('ищем сделку с ID '.$status_id.' ........');
                    $leads = $entity->leads->filter(function($lead) {     
                        return $lead->pipeline_id == $pipeline_id && $lead->status_id == $status_id;
                    });
                }
            }
        }
        return $leads;
    }

    // получить сделку в статусе
    public function getLeadsToStatus ($entity, $options) 
    {
        $leads = null;
        function foundLead () {
            foreach ($options['pipeline_id'] as $pipeline_id) {
                // $this->l->log('В воронке '.$pipeline_id.' .......');
                foreach ($options['status_id'] as $status_id) {
                    // $this->l->log('ищем сделку с ID '.$status_id.' ........');
                    $leads = $entity->leads->filter(function($lead) {     
                        return $lead->pipeline_id == $pipeline_id && $lead->status_id == $status_id;
                    });
                }
            }
        }
        return $leads;
    }

    // получить любую активную сделку из одной воронки с определённым статусом
    public function getActiveLeadInStatus ($entity, $pipeline_id, $status_id) 
    {
        $leads = $entity->leads->filter(function($lead) use (&$pipeline_id, &$status_id) {     
            return $lead->pipeline_id == $pipeline_id && $lead->status_id == $status_id;
        });
        return $leads->first();
    }
    // получить любые активные сделки из одной воронки
    public function getActiveLeadsInPipeline ($entity, $pipeline_id) {
        // $this->l->log('getActiveLeadsInPipeline');
        // $this->l->log($entity->id);
        $leads = $entity->leads->filter(function($lead) use (&$pipeline_id) {     
            return $lead->pipeline_id == $pipeline_id && $lead->status_id != 142 && $lead->status_id != 143;
        });
        return $leads; // return list object
    }

    // поиск успешно выполненных
    public function getCompleteLeadInPipeline ($entity, $pipeline_id) {
        // $this->l->log('getCompleteLeadInPipeline');
        $leads = $entity->leads->filter(function($lead) use (&$pipeline_id)  {
            return $lead->pipeline_id == $pipeline_id && $lead->status_id == '142'  || $lead->pipeline_id == $pipeline_id && $lead->status_id == '142' || $lead->pipeline_id == $pipeline_id && $lead->status_id = '142';
        });
        return $leads;
    }

    public function amoChangePipelineAndStatus ($lead, $pipeline_id, $status_id) {
        // $this->l->log('amoChangePipelineAndStatus');

        if (method_exists($lead,'first')){
            // $this->l->log('Есть метод first');
            $lead = $lead->first();
        } 
        $lead->pipeline_id = $pipeline_id;
        $lead->status_id = $status_id;
        $lead->save();
    }
    // меняем у сделки
    public function amoChangeLeadStatus ($lead, $status_id) {
        // $this->l->log('amoChangeLeadStatus');

        if (method_exists($lead,'first')){
            // $this->l->log('Есть метод first');
            $lead = $lead->first();
        } 
        $lead->status_id = $status_id;
        return $lead;
    }

    public function amoChangeFields ($lead, $values) {
        if (method_exists($lead,'first')){
            $lead = $lead->first();
        } 
        foreach ($values as $CFV => $value) {
            if ($lead->cf()->byId($CFV) && !empty($value)) {
                $lead->cf()->byId($CFV)->setValue($value);
            }
        }
        $lead->save();
    }

    public function getLeadById($id) {
        $amo = $this->amo;
        $lead = $amo->leads()->find($id); 
        return $lead;
    }


    public function getDataByIdField($lead, $CFV) {

        if ( $lead->cf()->byId($CFV)) {
            return $lead->cf()->byId($CFV)->getValue($value);
        } else {
            $this->l->log('Метод getDataByIdField '.__LINE__,"Поле $CFV пустое в сделке", $lead->id);
            return false;
        }
    }


}