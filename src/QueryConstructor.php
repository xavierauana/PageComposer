<?php
/**
 * Author: Xavier Au
 * Date: 14/6/2017
 * Time: 12:58 PM
 */

namespace Anacreation\PageComposer;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class QueryConstructor
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    private $object;


    /**
     * QueryParser constructor.
     */
    public function __construct(Model $object) {
        $this->object = $object;
    }

    public function construct(array $queries = null): Builder {

        if ($queries != null) {
            $command = null;
            foreach ($queries as $query) {
                if ($command == null) {
                    $command = $this->object->where($query['attribute'],
                        $this->parseQueryCondition($query['condition']), $this->parseQueryValue($query));
                } else {
                    $command->where($query['attribute'], $this->parseQueryCondition($query['condition']),
                        $this->parseQueryValue($query));
                }
            }

            return $command;
        }

        return (new Builder(DB::table($this->object->getTable())))->setModel($this->object);
    }


    private function parseQueryCondition($condition) {
        switch (strtolower($condition)) {
            case "equal":
                return "=";
            case "not equal":
                return "<>";
            default:
                throw new Exception("Not support Query condition");
        }
    }

    private function parseQueryValue($query) {
        switch (strtolower($query['type'])) {
            case "boolean":
                return $query['value'] === "true";
            default:
                return $query['value'];
        }
    }
}