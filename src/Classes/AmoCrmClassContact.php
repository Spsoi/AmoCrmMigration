<?php

namespace Classes\AmoCrm;

use \stdClass;

class Contact
{
    public $amo;

    public $phoneIdField;
    public $emailIdField;

    public $phone;
    public $email;


    public function __construct($phone, $phoneIdField, $email, $emailIdField) 
    {
        $this->phone = $phone;
        $this->phoneIdField = $phoneIdField;
        $this->email = $email;
        $this->emailIdField = $emailIdField;

        $this->amo = app('client')->crm();
        $this->l = logger('crm/Classes-AmoCrm-Contact.log');
    }


    public function contactSearch()
    {
        $amo = $this->amo;
        $contact = null;
        if (!empty($this->phone)) {
            $contacts = $amo->contacts()->searchByPhone($this->phone);
            if ($contact = $contacts->first()) {
                if (isset($this->email)) {
                    $contact->cf()->byId($this->emailIdField)->setValue($this->email,'WORK');
                }
                $contact->save();
                return $contact;
            }
        }

        if (!$contact && !empty($this->email)) {
            if (filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
                $contacts = $amo->contacts()->searchByEmail($this->email);
                if ($contact = $contacts->first()) {
                    if (isset($this->phone)) {
                        $contact->cf()->byId($this->phoneIdField)->setValue($this->phone,'WORK');
                    }
                    $contact->save();
                    return $contact;
                }
            }
        }
        return false;
    }

    public function contactSearchByPhone()
    {
        $amo = $this->amo;
        if (!empty($this->phone)) {
            $contacts = $amo->contacts()->searchByPhone($this->phone);
            if ($contact = $contacts->first()) {
                if (isset($this->email)) {
                    $contact->cf()->byId($this->emailIdField)->setValue($this->email,'WORK');
                }
                $contact->save();
                return $contact;
            }
        }
        return false;
    }

    public function contactSearchByEmail()
    {

        $amo = $this->amo;
        if (!$contact && !empty($this->email)) {
            if (filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
                $contacts = $amo->contacts()->searchByEmail($this->email);
                if ($contact = $contacts->first()) {
                    if (isset($this->phone)) {
                        $contact->cf()->byId($this->phoneIdField)->setValue($this->phone,'WORK');
                    }
                    $contact->save();
                    return $contact;
                }
            }
        }
        return false;
    }

    public function contactCreate($client)
    {
        $amo = $this->amo;
        if (!empty($client)) {

            $contact = $amo->contacts()->create();
            $contact->responsible_user_id = $client->responsible_user_id;
            $contact->name = $client->name;
            if (isset($this->phone)) {
                $contact->cf()->byId($this->phoneIdField)->setValue($this->phone,'WORK');
            }
            if (isset($this->email)) {
                $contact->cf()->byId($this->emailIdField)->setValue($this->email,'WORK');
            }
            $contact->save();
            return $contact;
        }  
    }

    public function contactFilterCreate ($data) {
        $this->l->log($data);
        $client = new stdClass();
        $this->l->log($client);
        $data->company_id === 513602 ? $client->responsible_user_id = '7075387' : null;
        $data->company_id === 544384 ? $client->responsible_user_id = '6235588' : null;

        $dataToData = $data->data; // data в теле
        $client->name = $dataToData->client->name;
        !empty($dataToData->record_labels)  ? $client->tags = $dataToData->record_labels: null;
        !empty($dataToData->date)           ? $client->dateTimeStart = $dataToData->date: null;
        $dateTimeEnd = null;
  
        !empty($client->dateTimeStart)  ? $dateTimeEnd = DateToUnixTime::DateTimeToUnixTime($client->dateTimeStart) : null;
        !empty($dateTimeEnd)            ? $dateTimeEnd = $dateTimeEnd + $dataToData->seance_length : null;
        return self::contactCreate($client);
    }
}