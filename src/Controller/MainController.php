<?php

namespace App\Controller;

use App\Entity\VisitedBrewery;
use App\Form\CoordinatesType;
use phpDocumentor\Reflection\Types\Array_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    private $visitedBreweries = [];
    private $collectedBeer = [];
    private $distanceTraveled = 0;
    private $maximumDistance = 2000;
    private $originLat = null;
    private $originLong = null;

    /**
     * @Route("/", name="appMain")
     */
    public function index(Request $request)
    {
        $form = $this->createForm(CoordinatesType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->originLong = $form->get('longCoord')->getData();
            $this->originLat = $form->get('latCoord')->getData();

            $em = $this->getDoctrine()->getManager();

            $query = "SELECT COUNT(id) as count FROM geocodes";
            $statement = $em->getConnection()->prepare($query);
            $statement->execute();

            $result = $statement->fetch();

            $visitedBrewery = new VisitedBrewery(
                -1,
                "HOME",
                $this->originLat,
                $this->originLong,
                -1,
                0
            );

            $this->visitedBreweries[] = $visitedBrewery;

            $this->findNextBrewery($result['count'], $this->originLat, $this->originLong);
        }

        return $this->render('main/index.html.twig', [
            'pageTitle' => 'Beer test',
            'coordForm' => $form->createView(),
            'collectedBeer' => $this->collectedBeer,
            'distanceTraveled' => $this->distanceTraveled,
            'flashMessages' => 0,
            'visitedBreweries' => $this->visitedBreweries,
        ]);
    }

    public function findNextBrewery($breweriesCount, $currentLat, $currentLong)
    {
        $em = $this->getDoctrine()->getManager();

        $query = "SELECT latitude, longitude, brewery_id, id FROM geocodes WHERE id = :id";

        $statement = $em->getConnection()->prepare($query);

        $distances = [];
        $geoCodesIds = [];

        for ($i = 1; $i <= $breweriesCount; $i++) {

            $statement->bindValue('id', $i);
            $statement->execute();

            $result = $statement->fetch();

            if ($currentLat === $result['latitude'] && $currentLong === $result['longitude']) {
                continue;
            }

            $continue = false;

            foreach ($this->visitedBreweries as $brewery) {
                if ($i === $brewery->getGeoCodeId()) {
                    $continue = true;
                    break;
                }
            }

            if (!$continue) {

                $distanceBetween = $this->haversineDistance($currentLat, $currentLong, $result['latitude'], $result['longitude']);

                if ($distanceBetween < ($this->maximumDistance / 2)) {

                    /*$distances[] = [
                        "distance" => $distanceBetween,
                        "geoCode" => $i
                    ];*/

                    $distances[] = $distanceBetween;
                    $geoCodesIds[] = $i;
                }
            }
        }

        if (!empty($distances)) {

            $minDistance = min($distances);
            //echo 'Min distance: '. $minDistance .'<br>';
            $nextBrewery = array_search($minDistance, $distances);
            //echo 'Next geo code id: '. $nextBrewery .'<br>';

            $statement->bindValue('id', $geoCodesIds[$nextBrewery]);
            $statement->execute();

            $result = $statement->fetch();

            //echo 'Brewery id from database '. $result['brewery_id'] .'<br><br>';

            /*echo '<pre>';
            print_r($distances);
            echo '</pre>';

            echo '<pre>';
            print_r($geoCodesIds);
            echo '</pre>';*/

            $distanceFromNextToHome = $this->haversineDistance(
                $this->originLat, $this->originLong, $result['latitude'], $result['longitude']
            );

            if (($this->distanceTraveled + $minDistance + $distanceFromNextToHome) < $this->maximumDistance) {

                $this->distanceTraveled += $minDistance;

                $visitedBrewery = new VisitedBrewery(
                    $result['brewery_id'],
                    $this->getBreweryName($result['brewery_id']),
                    $result['latitude'],
                    $result['longitude'],
                    $result['id'],
                    $minDistance
                );

                $this->visitedBreweries[] = $visitedBrewery;

                return $this->findNextBrewery($breweriesCount, $result['latitude'], $result['longitude']);
            }

            $this->distanceTraveled += $distanceFromNextToHome;

            $visitedBrewery = new VisitedBrewery(
                -1,
                "HOME",
                $this->originLat,
                $this->originLong,
                $result['id'],
                $distanceFromNextToHome
            );

            $this->visitedBreweries[] = $visitedBrewery;

        } else {

            return $this->addFlash('error', 'You need more jet fuel to travel from this location!');
        }
    }

    public function haversineDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371)
    {
        $latFrom = deg2rad($latitudeFrom);
        $longFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $longTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $longDelta = $longTo - $longFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                cos($latFrom) * cos($latTo) * pow(sin($longDelta / 2), 2)));

        return round($angle * $earthRadius);
    }

    public function getBreweryName($id)
    {
        $em = $this->getDoctrine()->getManager();

        $query = "SELECT name FROM breweries WHERE id = :id";
        $statement = $em->getConnection()->prepare($query);
        $statement->bindValue('id', $id);
        $statement->execute();

        $result = $statement->fetch();

        return $result['name'];
    }

    /*public function fillTravelMatrix($matrixSize)
    {
        $result1 = null;
        $result2 = null;

        $em = $this->getDoctrine()->getManager();

        $query = "SELECT latitude, longitude FROM geocodes WHERE id = :id";

        $statement = $em->getConnection()->prepare($query);

        for($i = 1; $i <= $matrixSize; $i++) {

            $statement->bindValue('id', $i);
            $statement->execute();

            $result1 = $statement->fetch();

            for($j = 1; $j <= $matrixSize; $j++) {

                $statement->bindValue('id', $i);
                $statement->execute();

                $result2 = $statement->fetch();

                $this->travelMatrix[$i][$j] = $this->haversineDistance(
                    $result1['latitude'], $result1['longitude'], $result2['latitude'], $result2['longitude']
                );
            }
        }

        /*
        $check = $this->haversineDistance(
            30.22340012, -97.76969910, 37.78250122, -122.39299774
        );

        if($check === $this->travelMatrix[1][2]) {

            echo "Reiksmes sutampa";
        }
        echo $this->travelMatrix[1][1], $this->travelMatrix[2][2];
        /
    }*/
}
