<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Carbon\Carbon;
use \Google_Client;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="main", methods={"GET"})
     */
    public function index(Request $request, Google_Client $client)
    {
        $client->setAuthConfig($this->getParameter('auth_config'));
        $client->addScope(\Google_Service_Sheets::SPREADSHEETS);
        
        $service = new \Google_Service_Sheets($client);
        
        $spreadsheetId = $this->getParameter('spreadsheet_id');
        $range = $this->getParameter('spreadsheet_range');
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        $num_values = count($values) - 1;
        $last_spese = [];

        if (empty($values)) {
            echo "NO SPESE LETTE, PROBLEMI!";
        } else {
            for ($i = $num_values; $i > $num_values - 5; $i--) {
                $spesa_data = Carbon::createFromFormat('d/m/Y H.i.s', $values[$i][0]);
                $spesa_tipo = $values[$i][2];
                $spesa_note = (isset($values[$i][4])?' (' . $values[$i][4] . ")":'');
                $spesa_costo = $values[$i][3];
                $last_spese[] = $spesa_data->format('d/m/Y') . ', ' . $spesa_tipo . $spesa_note . ', ' . $spesa_costo . " €";
            }
        }
        
        $spesetypes = json_decode(file_get_contents($this->getParameter('spesetype_file')), true);

        return $this->render('main/index.html.twig', [
            'spesetypes' => $spesetypes,
            'last_spese' => $last_spese,
        ]);
    }

    /**
     * @Route("/", name="add_spesa", methods={"POST"})
     */
    public function addSpesa(Request $request, Google_Client $client)
    {
        $spesetype = $request->get('spesetype');
        $cost = $request->get('cost');
        $notes = $request->get('notes');
        $person = '';
        $today = Carbon::now();
        $month = $today->month;
        $year = $today->year;

        $values = [$today->format('d/m/Y H.i.s'), $person, $spesetype, (int)$cost, $notes, $month, $year];

        $client->setAuthConfig($this->getParameter('auth_config'));
        $client->addScope(\Google_Service_Sheets::SPREADSHEETS);
        
        $service = new \Google_Service_Sheets($client);
        $spreadsheetId = $this->getParameter('spreadsheet_id');
        $range = $this->getParameter('spreadsheet_range');

        $valueRange= new \Google_Service_Sheets_ValueRange();
        $valueRange->setValues(["values" => $values]); 

        $conf = ["valueInputOption" => "USER_ENTERED"];

        $service->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $conf);

        return $this->render('main/addspesa.html.twig', [
            'spesetype' => $spesetype,
            'cost' => $cost,
            'notes' => $notes,
        ]);
    }
    
}
