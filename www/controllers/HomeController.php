<?php

class HomeController {

    public $homePage = '';

    public function home() {
        header(" Location: $this->homePage ");
    }
}