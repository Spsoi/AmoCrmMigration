<?php

namespace Classes\AmoCrmClasses;
use App\Services\AmoCrm\SettingsAccount;
class AmoCrmClassCatalog
{
    public function __construct($account_id = null)
    {

        $this->l = logger("crm/Classes-AmoCrmClasses-AmoCrmClassCatalog.log");
        $this->amo = app('client')->crm();
    }

    public function getCatalog(int $catalog_id)
    {
        $amo = $this->amo;
        return $amo->catalogs()->find($catalog_id);
    }

    public function createCatalogElement(int $catalog_id, array $fieldsId)
    {
        $catalog = self::getCatalog($catalog_id);
        $element = $catalog->createElement();
    
        $element = self::setSettingsElement($element, $fieldsId);
        return $element->save();
    }

    public function updateCatalogElement(int $element_id, array $fieldsId)
    {
        $amo = $this->amo;
        $element = $amo->catalogElements()->find($element_id);
        $this->l->log('$element',$element);
        $this->l->log('$fieldsId',$fieldsId);
        $element = self::setSettingsElement($element, $fieldsId);
        return $element->save();
    }

                            public function setSettingsElement($element, $fieldsId) {
                                foreach ($fieldsId as $key => $value) {
                                    if ($key === "name") {
                                        $element->name = $value;
                                        continue;
                                    }
                                    $this->l->log('$key',$key);
                                    $this->l->log('$value',$value);
                                    $element
                                        ->cf($key)
                                        // ->byId($key)
                                        ->setValue($value);
                                }

                                if (strlen($element->name) <= 0) {
                                    $element->name = 'Без имени';
                                }
                                return $element;
                            }

    public function getElementsCatalogsByCatalogId(int $catalogId = null)
    {
        if ($catalogId === null) {
            return true;
        }
        $amo = $this->amo;
        $catalog_elements = [];
        $catalogResponse = $amo->ajax()->get("/ajax/v1/catalog_elements/list/?catalog_id=" .$catalogId ."&json=1&page=1");

        if (
            empty($catalogResponse) ||
            empty($catalogResponse->response) ||
            empty($catalogResponse->response->catalog_elements)
        ) {
            return;
        }

        $pageTotal = $catalogResponse->response->pagination->pages->total; // колличество страниц

        if (!empty($pageTotal)) {
            $catalog_elements = array_merge(
                $catalog_elements,
                json_decode(
                    json_encode($catalogResponse->response->catalog_elements),
                    true
                )
            );

            for ($i = 2; $i <= $pageTotal; $i++) {
                $catalogResponse = $amo
                    ->ajax()
                    ->get(
                        "/ajax/v1/catalog_elements/list/?catalog_id=" .
                            $catalogId .
                            "&json=1&page=" .
                            $i
                    );
                $catalog_elements = array_merge(
                    $catalog_elements,
                    json_decode(
                        json_encode(
                            $catalogResponse->response->catalog_elements
                        ),
                        true
                    )
                );
            }
        }

        return $catalog_elements;
    }

    public function searchElementToCatalogElementsByName(
        array $catalog_elements,
        string $title
    ) {
        foreach ($catalog_elements as $element) {
            if (isset($element["name"]) && $element["name"] === $title) {
                return $element;
            }
        }
        return null;
    }

    public function searchElementToCatalogElementsById(
        array $catalog_elements,
        string $fieldId,
        $id
    ) {
        foreach ($catalog_elements as $element) {
            foreach ($element['custom_fields'] as $field) {
                if ($field['id'] === $fieldId && $field['values'][0]['value'] === $id) {
                    return $element;
                }
            }
        }
        return null;
    }

    public function attachElementByCatalogId(
        $lead,
        $catalog,
        $elementId,
        $count
    ) {
        $lead->attachElement($catalog_id, $elementId, $count);
    }

    public function detachProductByCatalogId($lead, $catalog_id)
    {
        $amo = $this->amo;
        foreach ($lead->catalog_elements_id as $id) {
            $data = [
                [
                    "to_entity_id" => $id,
                    "to_entity_type" => "catalog_elements",
                    "metadata" => [
                        "catalog_id" => $catalog_id,
                    ],
                ],
            ];
            $amo->ajax()->postJson("/api/v4/leads/" . $lead->id . "/unlink",$data);
        }
    }

    public function addElementToCatalogCustomFields(
        $catalog_id,
        $element,
        $field_id,
        $title
    ) {
        $amo = $this->amo;
        $data = [
            [
                "id" => intval($element->id), // обязательное поле
                "name" => $element->name, // обязатиельное поле
                "custom_fields_values" => [
                    [
                        "field_id" => intval($field_id),
                        "values" => [
                            [
                                "value" => $title,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // $.ajax({
        //     headers : {
        //         'Content-Type' : 'application/json'
        //     },
        //     url : '/api/v4/catalogs/'+5085+'/elements',
        //     type : 'PATCH',
        //     data : JSON.stringify([
        //         {
        //             "id": 2209681,
        //             "name" :"TEST 1",
        //             "custom_fields_values": [
        //                 {
        //                     "field_id": 442553,
        //                     "values": [
        //                     {
        //                         "value": "Подряд"
        //                     }
        //                 ]
        //                 }
        //             ]
        //         }
        //     ])
        // });

        $amo->ajax()->patch("/api/v4/catalogs/" . $catalog_id . "/elements",$data);
    }
}
