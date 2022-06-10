<?php

namespace App\Service;

use \Google_Client;
use Carbon\Carbon;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SpeseService {

    private $client;
    private $sheet;
    private $params;

    public function __construct(Google_Client $client, ParameterBagInterface $params)
    {
        $this->params = $params;

        $this->client = $client;
        $this->client->setAuthConfig($this->params->get('auth_config'));
        $this->client->addScope(\Google_Service_Sheets::SPREADSHEETS);
        
        $this->sheet = new \Google_Service_Sheets($client);
    }

    public function getLastAddedSpese($num_shown = 5): array
    {
        $spreadsheetId = $this->params->get('spreadsheet_id');
        $range = $this->params->get('spreadsheet_range');
        $response = $this->sheet->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        $num_values = count($values) - 1;
        $last_spese = [];

        if (empty($values)) {
            echo "NO SPESE LETTE, PROBLEMI!";
        } else {
            for ($i = $num_values; $i > $num_values - $num_shown; $i--) {
                $spesa_data = Carbon::createFromFormat('d/m/Y H.i.s', $values[$i][0]);
                $spesa_tipo = $values[$i][2];
                $spesa_note = ((strlen($values[$i][4]) != 0)?' (' . $values[$i][4] . ")":'');
                $spesa_costo = $values[$i][3];
                $last_spese[] = $spesa_data->format('d/m/Y') . ', ' . $spesa_tipo . $spesa_note . ', ' . $spesa_costo . " €";
            }
        }

        return $last_spese;
    }

    public function getTipiSpeseOrderedByMostUsed():array
    {
        $spreadsheetId = $this->params->get('spreadsheet_id');
        $spesetype_range = $this->sheet->spreadsheets_values->get($spreadsheetId, $this->params->get('spreadsheet_tipispese_range'))->getValues();
        
        $spesetypes = [];
        foreach ($spesetype_range as $spesetype) {
            $spesetypes[] = array(
                'code' => $spesetype[0],
                'name' => $spesetype[1],
            );
        }

        return $spesetypes;
    }

    public function addSpesa(Carbon $date, string $spesetype, float $cost, string $notes, string $person): void
    {
        $conf = ["valueInputOption" => "USER_ENTERED"];
        $spreadsheetId = $this->params->get('spreadsheet_id');
        $range = $this->params->get('spreadsheet_range');

        $month = $date->month;
        $year = $date->year;

        $values = [$date->format('d/m/Y H.i.s'), $person, $spesetype, (int)$cost, $notes, $month, $year];

        $valueRange= new \Google_Service_Sheets_ValueRange();
        $valueRange->setValues(["values" => $values]); 

        $this->sheet->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $conf);
    }

    public function addSpeseType($newspesetype): void
    {
        $conf = ["valueInputOption" => "USER_ENTERED"];
        $spreadsheetId = $this->params->get('spreadsheet_id');
        $range = $this->params->get('spreadsheet_tipispese_range');

        $newspesetype_code = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $newspesetype)));

        $values = array(
            $newspesetype_code,
            $newspesetype,
        );

        $valueRange= new \Google_Service_Sheets_ValueRange();
        $valueRange->setValues(["values" => $values]); 

        $this->sheet->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $conf);    
    }
}