<?php

namespace Classes\AmoCrm;

class Note
{
    public $amo;
    public function __construct() 
    {
        $this->amo = app('client')->crm();
        $this->l = logger('crm/Classes_AmoCrm_Note');
    }

    function create($entity, $data) {
        $template= "";
        $find = [];
        $replace = [];
        $note = $entity->createNote($type = 4);
 
        if (!empty($data['fields'])) {
            $template .= "Примечание:" . PHP_EOL;
            $item = $data['fields'];

            $template .= "{pagename} " . PHP_EOL;
            array_push($find, '{pagename}');
            $replace['{pagename}'] = $data['pagename']." ".$data['formname'];

            if (!empty($data['host'] &&
                !empty($data['path'])
            )) {
                $template .= "{address} " . PHP_EOL;
                array_push($find, '{address}');
                $replace['{address}'] = "https://".$data['host'].$data['path'];
            }  

            if (!empty($item['count'])) {
                $template .= "кол-во чел: {count} " . PHP_EOL;
                array_push($find, '{count}');
                $replace['{count}'] = $item['count'];
            } 

            if (!empty($data['level_education'])) {
                $template .= "образование: {level_education} " . PHP_EOL;
                array_push($find, '{level_education}');
                $replace['{level_education}'] = $data['level_education'];
            } 

            if (!empty($data['situation'])) {
                $template .= "причина: {situation} " . PHP_EOL;
                array_push($find, '{situation}');
                $replace['{situation}'] = $data['situation'];
            } 

            if (!empty($data['type_specialty'])) {
                $template .= "специализация: {type_specialty} " . PHP_EOL;
                array_push($find, '{type_specialty}');
                $replace['{type_specialty}'] = $data['type_specialty'];
            } 

            if (!empty($item['radio_calc'])) {
                $template .= "как: {radio_calc} " . PHP_EOL;
                array_push($find, '{radio_calc}');
                $replace['{radio_calc}'] = $item['radio_calc'];
            }  
            if (!empty($item['type'])) {
                $template .= "направление: {type} " . PHP_EOL;
                array_push($find, '{type}');
                $replace['{type}'] = $item['type'];
            }  
            if (!empty($item['type_cat'])) {
                $template .= "вид курса: {type_cat} " . PHP_EOL;
                array_push($find, '{type_cat}');
                $replace['{type_cat}'] = $item['type_cat'];
            }  
            if (!empty($item['calc_block'])) {
                $template .= "программы: {calc_block} " . PHP_EOL;
                array_push($find, '{calc_block}');
                $replace['{calc_block}'] = $item['calc_block'];
            }  
            if (!empty($item['checkbox-719'])) {
                $template .= "программы: {checkbox-719} " . PHP_EOL;
                $string = "\n";
                foreach ($item['checkbox-719'] as $point) {
                    $string .= $point."\n";
                }
                array_push($find, '{checkbox-719}');
                $replace['{checkbox-719}'] = $string;
            }  

            if (!empty($item['comment'])) {
                $template .= "комментарий: {comment} " . PHP_EOL;
                array_push($find, '{comment}');
                $replace['{comment}'] = $item['comment'];
            }  
        }

        $note->text .=  str_replace($find, $replace, $template);
        $note->save();
    }
}