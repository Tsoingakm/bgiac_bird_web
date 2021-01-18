<?php

namespace app\admin\controller;

use think\Controller;

class Test extends Controller
{
   public function testKml(){
       $path='/birdSuperviseWeb/public/upload/kml/20180813\66a1c9a5913e9f5494cb79525396a4af.kml';
       $root = dirname ( $_SERVER ['DOCUMENT_ROOT'] . "/aa" ) . "/";
       $fileName=$root.$path;
       $myfile = fopen($fileName, "r") or die("Unable to open file!");
       $content=fread($myfile,filesize($fileName));
       fclose($myfile);

//       print_r($content);

//       $pattern = '/<Placemark>.*?<\/Placemark>/';

       $polygonArr=\app\common\Util\KmlHelper::getKmlPolygon($content);
       print_r($polygonArr);

       /*
//       echo $content;
       $xml=simplexml_load_file($fileName);
//       print_r($xml);
//       $json_xml=json_encode($xml);
//       $dejson_xml=json_decode($json_xml,true);
////       print_r($dejson_xml);
//       print_r($dejson_xml['Document']['Folder']);
//       $floder=$dejson_xml['Document']['Folder'];
//       $placemarkArr=[];
       $xmlFolder=$xml->Document->Folder;
//       print_r($xmlFolder);
       foreach($xmlFolder as $k1=>$v1){
           $folder1=$v1->Folder;
           var_dump(gettype($folder1->Placemark));
           var_dump(gettype($folder1));
           print_r(($folder1));
           foreach ($folder1 as $k2=>$v2){
               $folder2=$v2->Folder;
               var_dump(gettype($folder2->Placemark));
               var_dump(gettype($folder2));
               print_r(($folder2));
           }
       }*/

   }


    /**
     * 解析kml文件返回一个解析后的数据
     * @param $file
     * @return array
     */
    function parseKML($file)
    {
        // 获得文件内容
        $xml = simplexml_load_file($file);
        // 输出KML数据数组
        $result = array();

        // 读取document标签
        $values = $xml->Document;

        $floderArr = array();
        foreach ($values->Folder as $folder) {
            $floderArr[] = $folder;
        }

        // 读取style标签
        $styleArrs = array();
        foreach ($values->Style as $style) {
            $jsonStyle = $this->xmlToArray($style);
            $key = $jsonStyle["@attributes"]["id"];
            $styleArrs[$key] = $jsonStyle;
        }
        print_r($floderArr);
        // 分别获得线和区域的数据
        $placeMarksCache = array();
        foreach ($floderArr as $key => $value) {
            $name = (string)$value->name;
            if ($name == 'Area Features') {
                foreach ($value->Placemark as $placeMark) {
                    $placeMarksCache['area'][] = $placeMark;
                }
            } else {
                foreach ($value->Placemark as $placeMark) {
                    $placeMarksCache['lines'][] = $placeMark;
                }
            }
        }

        // 循环输出数据
        foreach ($placeMarksCache as $k => $place){
            // 获取要输出的点集
            $placeMarkOutCache = array();
            // 将点集read出来
            foreach ($place as $placeMark) {
                $styleCache = (string)$placeMark->styleUrl;
                $styleCache = str_replace("#", "", $styleCache);
                $styleCache = $styleArrs[$styleCache];
                if (!$styleCache) {
                    $styleCache = "00000000";
                }
                // 获取点集合
                $strCache = $placeMark->Polygon;
                if ($strCache) {
                    $styleCache = "#" . $styleCache["PolyStyle"]["color"];
                    $strCache = (string)$strCache->outerBoundaryIs->LinearRing->coordinates;
                    $strCache = explode("\n              ", trim($strCache));
                } else {
                    $styleCache = "#" . $styleCache["LineStyle"]["color"];
                    $strCache = (string)$placeMark->LineString->coordinates;
                    $strCache = explode("\n          ", trim($strCache));
                }
                // 分割点集 作为数组进行保存
                foreach ($strCache as $sc) {
                    $args = explode(",", $sc);
                    $coords[] = array($args[0], $args[1], $args[2]);
                }
                // 将color 和 points 作为对象保存到result中
                $result[$k][] = array("color" => $styleCache, "points" => $coords);
                // 将这个数组清空
                $coords = array();
            }
        }
        // 最后返回集合点数据
        return $result;
    }

    function xmlToArray($xmlstring) {
        return json_decode(json_encode($xmlstring), true);
    }

}
