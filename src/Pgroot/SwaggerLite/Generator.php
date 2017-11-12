<?php
/**
 * Date: 2017-11-12 15:26
 * @author: GROOT (pzyme@outlook.com)
 */

namespace Pgroot\SwaggerLite;

use ReflectionClass;
use Route;
use Config;
use File;
use phpDocumentor\Reflection\DocBlockFactory;

class Generator {

    /**
     * generated code for swagger
     * @var array
     */
    protected $swagger = [];
    /**
     * all tags
     * @var array
     */
    private $tags = [];

    /**
     * Set main data for swagger. Version, title ,etc.
     */
    protected function setMainSwaggerInfo()
    {
        $this->swagger['swagger'] = config('swagger-lite.default-swagger-version');
        $this->swagger['info'] = [
                'description' => config('swagger-lite.api.description'),
                'version'     => config('swagger-lite.api.version'),
                'title'       => config('swagger-lite.api.title'),
            ];
        $this->swagger['host'] = str_replace(['http://', 'https://'], '', env('APP_URL'));
        $this->swagger['basePath'] = '/'.trim(config('swagger-lite.api.base-path'), '/');
        $this->swagger['tags'] = [];
    }

    /**
     * @param bool $string
     * @return array|mixed
     */
    public function make($string = false) {
        if (!Config::get('swagger-lite.generateAlways') && File::exists(storage_path('swagger-lite/resource.json'))) {
            $json = File::get(storage_path('swagger-lite/resource.json'));
            return $string ? $json : json_decode($json, true);
        } else {
            $this->setMainSwaggerInfo();
            foreach ($this->getRouteControllerData() as $controller => $methods) {

                $currentControllerClassName = current($methods);
                foreach ($methods as $method) {
                    $this->setPaths($method);
                }
            }
            try {
                $this->writeToFile();
            } catch(\Exception $e) {

            }
            return $string ? json_encode($this->swagger) : $this->swagger;
        }
    }

    /**
     * Get controllers data from routes
     * @return mixed
     */
    protected function getRouteControllerData()
    {
        $controllers = [];
        foreach (Route::getRoutes() as $route) {
            $controllerName = explode('@', $route->getActionName());

            $controllerNameSpace = array_get($controllerName, 0);
            $actionName = array_get($controllerName, 1);

            $controllerClassName = explode('\\', $controllerNameSpace);
            $controllerClassName = end($controllerClassName);

            if ($controllerClassName === 'Closure') {
                continue;
            }

            $classDoc = (new ReflectionClass($controllerNameSpace))->getDocComment();
            if($classDoc === false || strpos($classDoc, "@api") === false) {
                continue;
            }

            $tag = $this->classCommentToArray($classDoc);
            $this->setTag($tag['api'], $tag['summary']);

            $controllers[$controllerNameSpace][] = [
                'host'                => $route->domain(),
                'method'              => implode('|', $route->methods()),
                'uri'                 => $route->uri(),
                'name'                => $route->getName(),
                'controllerNameSpace' => $controllerNameSpace,
                'controllerClassName' => $controllerClassName,
                'actionName'          => $actionName,
                'tags' => [$tag['api']]
            ];
        }

        return $controllers;
    }

    /**
     * Set all tags
     * @param $methods
     */
    protected function setTag($name, $desc)
    {
        if(!in_array($name, $this->tags)) {
            $tag = [
                'name'        => $name,
                'description' => $desc,
            ];

            $this->swagger['tags'][] = $tag;
            array_push($this->tags, $name);
        }
    }

    /**
     * Set path
     * @param $method
     * @return array|void
     */
    protected function setPaths($method)
    {
        $docArray = $this->methodCommentToArray($method);

        if ( ! count($docArray)) {
            return;
        }

        $methodType = strtolower(str_replace(['|HEAD', '|PATCH'], '', $method['method']));

        $path = [
            'tags'        => $method['tags'],
            'summary'     => array_get($docArray, 'summary'),
            'description' => array_get($docArray, 'description', ''),
            'operationId' => '',
            'consumes'    => [
                'application/json',
                'application/xml',
            ],
            'produces'    => [
                "application/json",
                "application/xml"
            ],
            'parameters'  => $this->setParams($docArray['params']),
            'responses'   => $this->setResponses($docArray['return']),
        ];

        $uri = $method['uri'];

        foreach($path['parameters'] as $param) {
            if($param['in'] === 'path') {
                $uri = preg_replace("/\{(.*?)\}/", '{'.$param['name'].'}', $uri, 1);
            }
        }
        $route = [
            $uri
        ];

        $this->swagger['paths'][str_replace($this->swagger['basePath'], '', '/'. implode("/", $route))][$methodType] = $path;
    }

    /**
     * Set method params
     * @param $docArray
     * @param $method
     * @return array
     */
    protected function setParams($params)
    {
        $saved = [];
        foreach($params as $param) {

            $types = explode("|", $param->getType());
            $in = 'query';
            $required = false;
            $type = 'string';
            switch (count($types)) {
                case 1:
                    $type = array_get($types, 0, 'string');
                    break;
                case 2:
                    $type = array_get($types, 0, 'string');
                    $in = ltrim(array_get($types, 1, 'query'), "\\");
                    break;
                case 3:
                    $type = array_get($types, 0, 'string');
                    $in = ltrim(array_get($types, 1, 'query'), "\\");
                    $required = ltrim(array_get($types,2, 'no'), "\\") === 'required';
                    break;
                default:

            }

            $saved[] = [
                'name' => $param->getVariableName(),
                'in' => $in,
                'required' => $required,
                'type' => ltrim($type, "\\"),
                'description' => (String) $param->getDescription()
            ];
        }
        return $saved;
    }

    /**
     * Set response
     * @param $paramDocString
     * @return array
     */
    protected function setResponses($return)
    {
        $response = [];
        foreach($return as $item) {
            $response= [
                '200' => [
                    'description' => (String) $item->getDescription()
                ]
            ];
        }
        return $response;
    }

    /**
     * Get documentation to array
     * @param string $doc
     * @return array
     */
    protected function classCommentToArray($doc) {
        $documentationArray = [];
        $factory = DocBlockFactory::createInstance();
        $docblock = $factory->create($doc);
        $documentationArray['summary'] = $docblock->getSummary();
        $documentationArray['description'] = (String) $docblock->getDescription();

        $tag = $docblock->getTagsByName('api');
        $documentationArray['api'] = (String) $tag[0]->getDescription();

        return $documentationArray;
    }

    /**
     * Get documentation to array
     * @param $method
     * @return array
     */
    protected function methodCommentToArray($method)
    {
        $actionMethodName = array_get($method, 'actionName', null);
        $controllerNameSpace = array_get($method, 'controllerNameSpace', null);

        if ((empty($actionMethodName)) || ( ! $controllerNameSpace)) {
            return [];
        }

        $documentationArray = [];
        $reflector = new ReflectionClass($controllerNameSpace);
        if ( ! $reflector->hasMethod($actionMethodName)) {
            return [];
        }

        $doc = $reflector->getMethod($actionMethodName)->getDocComment();
        if(empty($doc)) {
            return [];
        }

        $factory = DocBlockFactory::createInstance();
        $docblock = $factory->create($doc);
        $documentationArray['summary'] = $docblock->getSummary();
        $documentationArray['description'] = (String) $docblock->getDescription();
        $documentationArray['params'] = $docblock->getTagsByName('param');
        $documentationArray['return'] = $docblock->getTagsByName('return');

        return $documentationArray;

    }

    /**
     * Write swagger data to json file
     */
    protected function writeToFile()
    {
        $fileDir = storage_path('swagger-lite');
        \File::put($fileDir.DIRECTORY_SEPARATOR.'resource.json', json_encode($this->swagger));
    }
}