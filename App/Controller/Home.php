<?php
    namespace MPF\Controller;
    use MPF\Core\View;

    class Home{
        public function index(){
            return View::view('Welcome');
        }
    }
?>