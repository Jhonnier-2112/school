<?php
namespace App\Controllers;

class EventController {
    public function index() {
        // No se necesita modelo de eventos - página estática
        require __DIR__ . '/../views/event.php';
    }
}
