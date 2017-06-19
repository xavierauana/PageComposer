<?php
/**
 * Author: Xavier Au
 * Date: 12/6/2017
 * Time: 2:20 PM
 */

namespace Anacreation\PageComposer;


use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PageRender
{
    private $page;
    private $components = [];
    private $variables = [];
    private $is_component = false;
    private $injectVariables = [];

    /**
     * PageRender constructor.
     * @param $page
     */
    public function __construct($page, $is_component = false, $injectVariables = []) {
        $this->page = $page;
        $this->is_component = $is_component;
        $this->getComponents();
        $this->injectVariables = $injectVariables;

    }

    private function getComponents($fileType = "xml") {
        $file_directory = $this->is_component ? config("PageComposer.components_directory") : config("PageComposer.theme_directory");
        if ($configFilePath = $this->getConfigFilePath($fileType, $file_directory)) {
            $this->getComponentsNode($configFilePath);
            $this->getVariables($configFilePath);
        }
    }

    public function renderHtml() {
        $html = "";
        if (count($this->components) > 0) {
            foreach ($this->components as $component) {
                $component = (new PageRender($component, true, $this->injectVariables));
                $html = $html . $component->renderHtml();
            }
        } else {
            $components_location = $components_location?? str_replace("/", ".", str_replace("views/", "",
                    $this->is_component ? config("PageComposer.components_directory") : config("PageComposer.theme_directory")));

            $view = $this->createView($components_location . "." . $this->page);
            $html = $view->render();
        }

        return $html;
    }

    /**
     * @param $fileType
     * @param $file_location
     * @return null|string
     * @throws \Exception
     */
    private function getConfigFilePath($fileType, $file_location) {
        $files = scandir(resource_path($file_location));

        $filePath = null;

        foreach ($files as $file) {
            if ($file == $this->page . ".$fileType") {
                $filePath = resource_path($file_location . "/" . $file);
            }
        }


        return $filePath;
    }

    /**
     * @param $componentTag
     * @param $nodes
     */
    private function getComponentsNode($filePath) {
        $nodes = XmlParser::parseComponents($filePath);
        foreach ($nodes as $value) {
            $this->components[] = $value;
        }
    }

    /**
     * @param $componentTag
     * @param $nodes
     */
    private function getVariables($filePath) {
        $this->variables = XmlParser::parseVariables($filePath);
    }

    private function createView(string $file_path): View {
        $variables = $this->variables;

        $content = [];
        // TODO:: refactor to enable eagerload in xml file
        if (isset($variables['queries']) and count($variables['queries']) > 0) {
            $content = $this->getQueryResult($variables['queries']);
        }

        $data = $this->injectVariables;
        $data['content'] = $this->getStaticVariables($content, $variables);

        return view($file_path, compact("data"));
    }

    private function getQueryResult($queriesArray): array {
        $result = [];
        if (count($queriesArray) > 0) {
            foreach ($queriesArray as $key => $queries) {
                if ($data = $this->fetchFromDB($queries)) {
                    $result[$key] = $data;
                };
            }
        }

        return $result;
    }


    # region get queries implementation

    /**
     * @param $query
     */
    private function getObject($query):?Model {
        $object = null;
        try {
            $object = app()->make($query['class']);
        } catch (Exception $e) {
            Log::warning($e->getMessage());
        } finally {
            return $object;
        }
    }

    /**
     * @param $result
     * @param $variables
     * @return array
     */
    private function getStaticVariables($result, $variables): array {

        if (isset($variables['values'])) {
            $result = array_merge($result, $variables['values']);
        }

        return $result;
    }

    /**
     * @param $queries
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    private function fetchFromDB($query): ?Collection {
        return ($object = $this->getObject($query)) ? (new QueryConstructor($object))->construct($query['predicates'])
                                                                                     ->get() : [];
    }

    #endregion

}