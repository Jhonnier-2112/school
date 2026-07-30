<?php
namespace App\Controllers;

class HomeController {
    public function index() {
        require __DIR__ . '/../views/home.php';
    }
    
    public function nosotros() {
        require __DIR__ . '/../views/nosotros.php';
    }
    
    public function productos() {
        require __DIR__ . '/../views/productos.php';
    }
    
    public function blog() {
        require __DIR__ . '/../views/blog.php';
    }
}
