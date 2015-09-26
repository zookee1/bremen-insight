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

    public function geojsonAction($id, $level)
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
        $geojson = json_decode(file_get_contents($dataFolder . 'bremen-level-' . $level . '.geojson'), true);

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
        $geojson['datasets'] = [];

        foreach($datasets as $key => $datasetData) {
            $geojson['datasets'][] = [
                'id' => $key,
                'label' => $datasetData['name']
            ];
        }

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

}
