<?php

namespace App\Service;

use \Google_Client;
use Carbon\Carbon;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Entity\SpeseType;

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

    public function getSpesePaginated(int $offset, int $limit): array
    {
        $spreadsheetId = $this->params->get('spreadsheet_id');
        $range = $this->params->get('spreadsheet_range');
        $response = $this->sheet->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        
        if (empty($values)) {
            return [];
        }

        // Assume first row is header if it exists, but based on getLastAddedSpese logic, 
        // it seems we just want to reverse the array.
        // Let's play it safe and just reverse everything, then slice.
        // If the first row is a header, it will be at the end after reverse.
        // We can filter it out if needed, but for now let's just return data.
        
        $values = array_reverse($values);
        $slice = array_slice($values, $offset, $limit);
        
        $spese_list = [];
        foreach ($slice as $row) {
            // Check if row has enough columns and looks like a valid entry
            if (count($row) < 4) continue;

            try {
                // Try to parse date to ensure it's a valid expense row
                $spesa_data = Carbon::createFromFormat('d/m/Y H.i.s', $row[0]);
                $spesa_tipo = $row[2];
                $spesa_note = ((isset($row[4]) && strlen($row[4]) != 0) ? ' (' . $row[4] . ")" : '');
                $spesa_costo = $row[3];
                
                $spese_list[] = [
                    'date' => $spesa_data->format('d/m/Y'),
                    'type' => $spesa_tipo,
                    'note' => $spesa_note,
                    'cost' => $spesa_costo,
                    'full_text' => $spesa_data->format('d/m/Y') . ', ' . $spesa_tipo . $spesa_note . ', ' . $spesa_costo . " €"
                ];
            } catch (\Exception $e) {
                // Skip malformed rows (e.g. header)
                continue;
            }
        }

        return $spese_list;
    }

    /** 
     * @return SpeseType[]
     */
    public function getTipiSpeseOrderedByMostUsed():array
    {
        $spreadsheetId = $this->params->get('spreadsheet_id');
        $spesetype_range = $this->sheet->spreadsheets_values->get($spreadsheetId, $this->params->get('spreadsheet_tipispese_range'))->getValues();
        
        $today = Carbon::now();
        $reference_year = $today->year - 1;

        $spese_range = $this->params->get('spreadsheet_range');
        $spese_values = $this->sheet->spreadsheets_values->get($spreadsheetId, $spese_range)->getValues();
            
        $entries = [];
        $ytd = [];
        $curr_month = [];
        // calculate the most used in last 2 years
        foreach ($spese_values as $spese_value)
        {
            // column order:
            // 0 - timestamp
            // 1 - person (not used)
            // 2 - spesetype name
            // 3 - amount
            // 4 - notes
            // 5 - month
            // 6 - year 
            if ($spese_value[6] >= $reference_year ) {
                if (!isset($entries[$spese_value[2]])) {
                    $entries[$spese_value[2]] = 1;
                } else {
                    $entries[$spese_value[2]]++;
                }
            }

            // current month spent
            if ($spese_value[6] == $today->year && $spese_value[5] == $today->month) {
                if (!isset($curr_month[$spese_value[2]])) {
                    $curr_month[$spese_value[2]] = $spese_value[3];
                } else {
                    $curr_month[$spese_value[2]] += $spese_value[3];
                }
            }

            // YTD spent
            if ($spese_value[6] == $today->year) {
                if (!isset($ytd[$spese_value[2]])) {
                    $ytd[$spese_value[2]] = $spese_value[3];
                } else {
                    $ytd[$spese_value[2]] += $spese_value[3];
                }
            }
        }

        $spesetypes = [];
        foreach ($spesetype_range as $spesetype) {
            $spesetypes[] = new SpeseType(
                $spesetype[0], 
                $spesetype[1], 
                isset( $curr_month[$spesetype[1]]) ? $curr_month[$spesetype[1]] : 0,
                isset( $ytd[$spesetype[1]]) ? $ytd[$spesetype[1]] : 0,
                isset( $entries[$spesetype[1]]) ? $entries[$spesetype[1]] : 0
            );
        }

        // sort by most used
        usort($spesetypes, function(SpeseType $a, SpeseType $b) {
            return $b->getEntries() <=> $a->getEntries();
        });

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