# Swagger Lite
Laravel 5 api documentation generator, based on [Swagger 3](http://swagger.io/) 

**swagger lite** use just a few lines of code added to your controllers methods.  

## Installation
Require this package with composer using the following command:

    composer require pgroot/swagger-lite
     
After that add to the providers array in config/app.php
 
    Pgroot\SwaggerLite\SwaggerLiteServiceProvider::class,
    
Then call

    php artisan vendor:publish
    
## Usage

If you do all steps mentioned above than the file /config/swagger-lite.php should be generated for you.    

    <?php
    return array(
        'api' => [
            'version' => '1.0',
            'title' => 'API',
            'description' => '',
            'base-path' => '/api'
        ],
        'doc-route' => 'docs',
        'api-docs-route' => 'api/docs',
        "generateAlways" => true,
        "default-swagger-version" => "2.0",
    );
 
 
#### Controllers and methods

    <?php
    /**
     * A description of the api
     *
     * @api tag
     * @package App\Http\Controllers
     */
    class TestController extends Controller {
    
        /**
         * A description of the method
         *
         * This is a Description. A Summary and Description are separated by either
         *
         * @param string|path|required $id Description of the parameterName 
         * @param int|query $pid test Description of the parameterName 
         * @param string $type Description of the parameterName 
         * @return string Indicates the number of items
         */
        public function index(){
            return ['index'];
        }
    } 
    
**Notice:** : "@api" is required
  
You can set how the parameters are send and there are 3 options:

- formData
- path 
- query - this is the default option

Parameters can be required or not required. `default: false`

That's it. Now you can access your new documentation at APP_URL/docs