<?php
namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller {
    public function index() {
        $this->view('home');
    }
    
    public function nosotros() {
        $this->view('nosotros');
    }
    
    public function productos() {
        $this->view('productos');
    }
    
    public function blog() {
        $this->view('blog');
    }
}
