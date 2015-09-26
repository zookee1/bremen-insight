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

        $controlColumn = $dataset['controlColumn'];

        $columnRows = [];
        for($i=0; $i<=$dataset['labelRow']; $i++) {
            $columnRows[] = array_slice($csv[$i], $controlColumn + 1);
        }

        $columns = array_reduce($columnRows, function($a, $b) {
            for($i=0; $i<count($a); $i++) {
                if(!isset($b[$i])) {
                    $b[$i] = '';
                }
                $b[$i] = $a[$i] . ' ' . $b[$i];
            }
            return $b;
        }, []);

        $geojson['columns'] = $columns;
        $geojson['columnMaxima'] = array_fill(0, count($columns), 0);
        $geojson['columnMinima'] = array_fill(0, count($columns), 0);
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
                    $key = $line[$controlColumn];
                    $results[$key] = array_map(function($point) {
                        return (float) str_replace(',', '.', $point);
                    }, array_slice($line, $controlColumn+1));
                    for($i=0; $i<count($results[$key]); $i++) {
                        if($results[$key][$i] > $geojson['columnMaxima'][$i]) {
                            $geojson['columnMaxima'][$i] = $results[$key][$i];
                        }
                        if($results[$key][$i] < $geojson['columnMinima'][$i]) {
                            $geojson['columnMinima'][$i] = $results[$key][$i];
                        }
                    }
                }
            }
            $feature['properties']['dataPoints'] = $results;
            if($results) {
                $geojson['categories'] = array_keys($results);
            }
        }
        $response = new Response(json_encode($geojson));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function isLineRelevantForFeature($name, $locationKey)
    {
        if(!is_numeric($locationKey)){
            return false;
        }
        return $this->getBremenMapping($name, 10);
    }

    public function getBremenMapping($name, $administrationLevel)
    {
        $bremenMapping = $this->getParameter('bremenMapping');
        echo $this->extractColumnFromArray($bremenMapping, $administrationLevel);
        return false;
    }

    public function extractColumnFromArray($array, $administrationLevel) {
        if($administrationLevel === 10)
        {
            $array_keys = array_keys($array['4#Bremen']['4011#Stadt Bremen']);

        }
        else if($administrationLevel === 11)
        {
            $array_keys = array_keys($array['Bremen']['4']['Stadt Bremen']['4011']['Mitte Bezirk']);
        }

        echo '<pre>';
        print_r($array_keys);
        exit;
    }

}
