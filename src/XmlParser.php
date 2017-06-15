<?php
/**
 * Author: Xavier Au
 * Date: 12/6/2017
 * Time: 2:45 PM
 */

namespace Anacreation\PageComposer;


use Anacreation\PageComposer\Contracts\ParserInterface;

class XmlParser implements ParserInterface
{

    private $path;

    /**
     * XmlParser constructor.
     */
    private function __construct($filePath) {
        $this->path = $filePath;
    }

    public static function parseComponents(string $file_location): array {
        $instance = new static($file_location);


        $getUsage = function () use ($instance) {
            $storageArray = [];

            $sting = file_get_contents($instance->getPath());

            $xml = simplexml_load_string($sting);

            foreach ($xml->children() as $section => $value) {
                if ($section == "components") {
                    foreach ($value->children() as $item) {
                        $storageArray[] = (string)$item->name;
                    };
                }
            }

            return $storageArray;
        };

        if (env("CACHE_DRIVER") === "memcached") {
            return cache()->tags(["page_content"])->rememberForever($file_location . "_page_content", $getUsage);
        } else {
            cache()->rememberForever($file_location . "_page_content", $getUsage);
        }
    }

    public static function parseVariables(string $file_location): array {
        $instance = new static($file_location);


        $getUsage = function () use ($instance) {
            $sting = file_get_contents($instance->getPath());

            $xml = simplexml_load_string($sting);

            $queries = [];
            $values = [];

            foreach ($xml->children() as $section => $value) {
                if ($section == "variables") {
                    foreach ($value->children() as $item) {
                        if ($item['type'] == 'query') {
                            $queries[(string)$item->name] = [
                                'class'      => (string)$item->class,
                                'predicates' => ($predicates = $instance->getPredicates($item->predicates)) ? $predicates : null
                            ];
                        } elseif ($item['type'] == 'string') {
                            $values[(string)$item->name] = (string)$item->value;
                        };

                    };
                }
            }

            return ["queries" => $queries, 'values' => $values];
        };

        cache()->flush();
        if (env("CACHE_DRIVER") === "memcached") {
            return cache()->tags(["page_variable"])->rememberForever($file_location . "_page_variable", $getUsage);
        } else {
            cache()->rememberForever($file_location . "_page_variable", $getUsage);
        }
    }

    /**
     * @return mixed
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * @param $item
     * @return array
     */
    private function getPredicates($predicates): ?array {
        $result = [];
        /** @var \SimpleXMLElement $predicate */

        if (!!$predicates->count()) {
            foreach ($predicates->children() as $predicate) {
                $result[] = [
                    'attribute' => (string)$predicate->attribute,
                    'condition' => (string)$predicate->condition,
                    'value'     => (string)$predicate->value,
                    'type'      => (string)$predicate->value['type']
                ];
            }
            if ($predicates['eager-load']) {
                $result["eager-load"] = $predicates['eager-load'];
            }
        }


        return count($result) > 0 ? $result : null;
    }


}