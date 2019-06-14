<?php

namespace App\Controller;

use App\Form\CoordinatesType;
use phpDocumentor\Reflection\Types\Array_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="appMain")
     */
    public function index(Request $request)
    {
        $visitedBreweries = [];
        $collectedBeer = [];

        $form = $this->createForm(CoordinatesType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {


        }

        return $this->render('main/index.html.twig', [
            'pageTitle' => 'Beer test',
            'coordForm' => $form->createView(),
            'visitedBreweries' => $visitedBreweries,
            'collectedBeer' => $collectedBeer,
        ]);
    }
}
