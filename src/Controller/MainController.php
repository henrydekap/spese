<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Carbon\Carbon;
use App\Service\SpeseService;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="main", methods={"GET"})
     */
    public function index(Request $request, SpeseService $spese)
    {
        $last_spese = $spese->getLastAddedSpese();
        
        $spesetypes = $spese->getTipiSpeseOrderedByMostUsed();

        return $this->render('main/index.html.twig', [
            'spesetypes' => $spesetypes,
            'last_spese' => $last_spese,
        ]);
    }

    /**
     * @Route("/", name="add_spesa", methods={"POST"})
     */
    public function addSpesa(Request $request, SpeseService $spese)
    {
        $spesetype = $request->get('spesetype');
        $cost = $request->get('cost');
        $notes = $request->get('notes');
        $newspesetype = $request->get('newspesetype');

        if ($spesetype == 'altro' && strlen($newspesetype) > 0) {
            $spese->addSpeseType($newspesetype);
            $spesetype = $newspesetype;
        }

        $person = '';
        $today = Carbon::now();

        $spese->addSpesa($today, $spesetype, $cost, $notes, $person);

        return $this->render('main/addspesa.html.twig', [
            'spesetype' => $spesetype,
            'cost' => $cost,
            'notes' => $notes,
        ]);
    }
    
}
