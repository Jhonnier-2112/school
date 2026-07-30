<?php
namespace App\Controllers;

use App\Core\Controller;

class EventController extends Controller {
    public function index() {
        // No se necesita modelo de eventos - página estática
        $this->view('event');
    }
}
