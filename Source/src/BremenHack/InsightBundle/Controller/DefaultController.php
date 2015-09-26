<?php

namespace BremenHack\InsightBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('BremenHackInsightBundle:Default:index.html.twig');
    }

    public function geojsonAction($id)
    {
        $datasets = $this->getParameter('datasets');
        if(!array_key_exists($id, $datasets)) {
            throw new \Exception('not found: ' . $id);
        }
        $dataset = $datasets[$id];
        $dataFolder = __DIR__ . '/../Resources/data/';
        $csv = array_map(function($line) {
            return str_getcsv(utf8_encode($line), ';');
        }, file($dataFolder . $dataset['filename']));
        $geojson = json_decode(file_get_contents($dataFolder . 'bremen-level-10.geojson'), true);

        $columns = array_slice($csv[2], 4);



        $geojson['columns'] = $columns;

        foreach($geojson['features'] as &$feature) {
            if(!isset($feature['properties']['name'])) {
                continue;
            }
            $name = $feature['properties']['name'];


            $results = [];

            foreach($csv as $line) {
                if(count($line) < 3) {
                    continue;
                }
                if(strpos($line[1], $name) !== FALSE) {
                    $feature['properties']['locationKey'] = $line[0];
                    $key = $line[3];
                    $results[$key] = array_map(function($point) {
                        return (float) str_replace(',', '.', $point);
                    }, array_slice($line, 4));

                }
            }
            $feature['properties']['dataPoints'] = $results;


        }
        $response = new Response(json_encode($geojson));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

}
