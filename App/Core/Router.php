<?php
    namespace MPF\Core;
    
    use MPF\Core\Res;
    use Exception;

    $routes = ['static' => null,"fallback" => null];

    $URI = $_SERVER['REQUEST_URI'];
    $FILE_PATH = __DIR__ . $URI;
    

    class Router{
        private static $routes = null;
        private static $fallback = null;
        private static $CONTROLLER_NAMESPACE = "MPF\\Controller\\";
        

        public static function get($path, $controller, $type = null){
            global $routes;
            if(is_array($controller)){
                $routes["get"][$path] = [ self::$CONTROLLER_NAMESPACE.$controller[0], $controller[1]];
            }else{
                $routes["get"][$path] = $controller;
            }
        }
        public static function post($path, $controller){
            global $routes;
            if(is_array($controller)){
                $routes["post"][$path] = [ self::$CONTROLLER_NAMESPACE.$controller[0], $controller[1]];
            }else{
                $routes["post"][$path] = $controller;
            }
        }

        public static function static($path){
            global $routes;
            
            //echo realpath(__DIR__."../../..".$path);
            
            if(file_exists(realpath(__DIR__."../../..".$path))){
                //$routes['static'] = realpath(__DIR__."../../..".$path);
                $routes['static'] = $path;
            }else{
                throw new Exception("the path: ".$path." for static file was not found, try: Router::static('/public/')");
            }
        }

        public static function fallback($viewName){
            global $routes;
            $routes['fallback'] = $viewName;
        }

        public static function run(){
            Res::header('x-powered-by: mpf-amj');
            global $routes;
            $method = strtolower($_SERVER['REQUEST_METHOD']);

            if(strpos($_SERVER['REQUEST_URI'],"?")){
                $uri = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'],"?"));
            }else{
                $uri = $_SERVER['REQUEST_URI'];
            }
            $mimeTypes = [
                'css' => 'text/css',
                'js' => 'application/javascript',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'ico' => 'image/ico',
                'pdf' => 'application/pdf',
                'svg' => 'image/svg+xml',
                'woff' => 'font/woff',
                'woff2' => 'font/woff2',
                'ttf' => 'font/ttf',
                'eot' => 'application/vnd.ms-fontobject',
                'html' => 'text/html',
                'json' => 'application/json',
            ];

            if($method == "get" && isset($routes['static'])){
                $regex =  "/^".str_replace("/","\/",$routes['static']).".*/i";
                if(preg_match($regex, $uri)){
                    $FILE_PATH = realpath(__DIR__."../../..".$uri);
                    $exRegex = '(jpg|jpeg|png|gif|css|svg|html|pdf|docx|doc|xls|pptx|js|ico)';
                    if(is_file($FILE_PATH) && preg_match('/\.'.$exRegex.'$/i', $uri)) {
                        $ext = pathinfo($FILE_PATH, PATHINFO_EXTENSION);
                        header("Content-Type: ".$mimeTypes[$ext]);
                        readfile($FILE_PATH);
                        Res::status(200);
                        return exit(0);
                    }
                    if(isset($routes["fallback"])){
                        if(is_callable($routes["fallback"])){
                            call_user_func($routes["fallback"]);
                            return;
                        }
                        Res::status(404);
                        return View::view($routes['fallback']);
                    }
                    echo "FILE NOT FOUND";
                    return Res::status(404);
                }
            }

            if(isset($routes[$method])){
                foreach($routes[$method] as $path => $route){
                    
                    $pathRegex = preg_replace("/\[[a-zA-Z_]+\]/","([a-zA-Z0-9_\-]+)",$path); // replace [param] for ([a-zA-Z0-9_\-]+)
                    $pathRegex = preg_replace("/\//","\/",$pathRegex); // replace / for \/
                    $pathRegex = "/^".$pathRegex."\$/i"; // /user/[id] => /\/user\/([a-zA-Z0-9_\-]+)/i
                    
                    // for routes with arguments Eg: /user/{id}";

                    preg_match($pathRegex, $uri, $pathMatches); // check if the regex matches que current uri
                                                                // Eg.: /user/1 => matches /\/user\/([a-zA-Z0-9_\-]+)/i
                    if(!empty($pathMatches)){
                        array_shift($pathMatches); // remove the first element of the mach: array( 0 => string, 1 => path)

                        preg_match_all("/\[([a-zA-Z]+)\]/" ,$path, $paramMatches); // find path params like the [id] => id in /user/[id]
                        $params = array_combine($paramMatches[1], $pathMatches);   // set the the values in the params
                                                                                   // array([id], [12])
                        if(is_callable($route)){  // if the controller passed is an anonymous function call it
                            call_user_func_array($route, $params);
                            return;
                        }

                        if(is_array($route) && count($route) === 2){ // if the controller is a class instanciate and call the method
                            [$controller, $method] = $route;   
                            if(class_exists($controller) && method_exists($controller, $method)){
                                call_user_func_array([new $controller, $method], $params);
                                return;
                            }
                            throw new Exception("Controller or Method Error. make sure both exist");
                        }
                        break;
                    }else{
                        // routes with no arguments Eg: /user/info 
                        if(isset($routes[$method][$uri])){
                            if(is_callable($routes[$method][$uri])){  // if the controller passed is an anonymous function call it
                                call_user_func_array($routes[$method][$uri], []);
                                return;
                            }
                            
                            if(is_array($routes[$method][$uri]) && count($routes[$method][$uri]) === 2){ // if the controller is a class instanciate and call the method
                                [$controller, $method] = $routes[$method][$uri];   
                                if(class_exists($controller) && method_exists($controller, $method)){
                                    call_user_func_array([new $controller, $method], []);
                                    return;
                                }
                                throw new Exception("Controller Error.");
                            }
                            break;
                        }
                    }
                }

                if(isset($routes["fallback"])){
                    if(is_callable($routes["fallback"])){
                        call_user_func($routes["fallback"]);
                        return;
                    }
                    Res::status(404);
                    return View::view($routes['fallback']);
                }else{
                    Res::status(404);
                    echo "404 page not found.";
                    return;  
                }

            }else{
                if(isset($routes["fallback"])){
                    if(is_callable($routes["fallback"])){
                        call_user_func($routes["fallback"]);
                        return;
                    }
                    Res::status(404);
                    return View::view($routes['fallback']);
                }else{
                    Res::status(404);
                    echo "404 page not found.";
                    return;  
                }
            }
        }
    }

?>