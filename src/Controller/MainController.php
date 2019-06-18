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

        if($form->isSubmitted() && $form->isValid()) {

            $this->originLong = $form->get('longCoord')->getData();
            $this->originLat = $form->get('latCoord')->getData();

            $em = $this->getDoctrine()->getManager();

            $query = "SELECT COUNT(id) as count FROM geocodes";
            $statement = $em->getConnection()->prepare($query);
            $statement->execute();

            $result = $statement->fetch();

            $visitedBrewery = new VisitedBrewery();
            $visitedBrewery->setLongitude($this->originLong);
            $visitedBrewery->setLatitude($this->originLat);
            $visitedBrewery->setName("HOME");
            $visitedBrewery->setDistance(0);

            $this->visitedBreweries[] = $visitedBrewery;

            $this->findNextBrewery($result['count'], $this->originLat, $this->originLong);
        }

        return $this->render('main/index.html.twig', [
            'pageTitle' => 'Beer test',
            'coordForm' => $form->createView(),
            //'visitedBreweries' => $this->visitedBreweries,
            'collectedBeer' => $this->collectedBeer,
            'distanceTraveled' => $this->distanceTraveled,
            'flashMessages' => 0,
            'visitedBreweries' => $this->visitedBreweries,
        ]);
    }

    public function findNextBrewery($breweriesCount, $currentLat, $currentLong)
    {
        $em = $this->getDoctrine()->getManager();

        $query = "SELECT latitude, longitude, brewery_id FROM geocodes WHERE id = :id";

        $statement = $em->getConnection()->prepare($query);

        $distances = [];
        $geoCodesIds = [];

        for($i = 1; $i <= $breweriesCount; $i++) {

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
                }
            }

            if(!$continue) {

                $distanceBetween = $this->haversineDistance($currentLat, $currentLong, $result['latitude'], $result['longitude']);

                if ($distanceBetween < ($this->maximumDistance / 2)) {
                    $distances[] = $distanceBetween;
                    $geoCodesIds[] = $i;
                }
            }
        }

        if(!empty($distances)) {

            $minDistance = min($distances);
            $nextBrewery = array_search($minDistance, $distances);

            $statement->bindValue('id', $nextBrewery);
            $statement->execute();

            $result = $statement->fetch();

            $distanceFromNextToHome = $this->haversineDistance(
                $result['latitude'], $result['longitude'], $this->originLat, $this->originLong
            );

            if ($this->distanceTraveled + $minDistance + $distanceFromNextToHome < $this->maximumDistance) {

                //$this->visitedBreweries[] = $geoCodesIds[$nextBrewery];
                $this->distanceTraveled += $minDistance;

                $visitedBrewery = new VisitedBrewery();
                $visitedBrewery->setId($result['brewery_id']);
                $visitedBrewery->setDistance($minDistance);
                $visitedBrewery->setLatitude($result['latitude']);
                $visitedBrewery->setLongitude($result['longitude']);
                $visitedBrewery->setGeoCodeId($geoCodesIds[$nextBrewery]);

                $this->visitedBreweries[] = $visitedBrewery;

                $this->findNextBrewery($breweriesCount, $result['latitude'], $result['longitude']);
            }
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

    /**
     *  Check if an array is a multidimensional array.
     *
     *  @param   array   $arr  The array to check
     *  @return  boolean       Whether the the array is a multidimensional array or not
     */
    function is_multi_array( $x ) {
        if( count( array_filter( $x,'is_array' ) ) > 0 ) return true;
        return false;
    }

    /**
     *  Convert an object to an array.
     *
     *  @param   array   $object  The object to convert
     *  @return  array            The converted array
     */
    function object_to_array( $object ) {
        if( !is_object( $object ) && !is_array( $object ) ) return $object;
        return array_map( 'object_to_array', (array) $object );
    }

    /**
     *  Check if a value exists in the array/object.
     *
     *  @param   mixed    $needle    The value that you are searching for
     *  @param   mixed    $haystack  The array/object to search
     *  @param   boolean  $strict    Whether to use strict search or not
     *  @return  boolean             Whether the value was found or not
     */
    function search_for_value( $needle, $haystack, $strict=true ) {
        $haystack = $this->object_to_array( $haystack );
        if( is_array( $haystack ) ) {
            if( $this->is_multi_array( $haystack ) ) {   // Multidimensional array
                foreach( $haystack as $subhaystack ) {
                    if( $this->search_for_value( $needle, $subhaystack, $strict ) ) {
                        return true;
                    }
                }
            } elseif( array_keys( $haystack ) !== range( 0, count( $haystack ) - 1 ) ) {    // Associative array
                foreach( $haystack as $key => $val ) {
                    if( $needle == $val && !$strict ) {
                        return true;
                    } elseif( $needle === $val && $strict ) {
                        return true;
                    }
                }
                return false;
            } else {    // Normal array
                if( $needle == $haystack && !$strict ) {
                    return true;
                } elseif( $needle === $haystack && $strict ) {
                    return true;
                }
            }
        }
        return false;
    }
}
