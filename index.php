<?php
    require __DIR__. "/vendor/autoload.php";

    use MPF\Core\Router;
    use MPF\Core\Res;
    use MPF\Core\View;

    Router::get('/', function () {
        return View::view('Welcome');
    });
    
    Router::fallback('404');

    Router::static("/public/");
    
    Router::run();
    exit;
?>