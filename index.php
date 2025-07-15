<?php
    require __DIR__. "/vendor/autoload.php";

    use MPF\Core\Router;
    use MPF\Core\Res;
    use MPF\Core\View;
    use MPF\Models\Users;

    Router::get('/', function () {
        return View::view('Welcome');
    });

    Router::get('/test', function () {
        $u = Users::save([
            "name" => "Adão Majadfasdfasdfr",
            "email" => "adaomajor01@gmail.com",
            "password" => "12345678"
        ]);

        Res::json($u);
    });
    
    Router::fallback('404');

    Router::static("/public/");
    
    Router::run();
    exit;
?>