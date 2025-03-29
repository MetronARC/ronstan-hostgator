<?php

namespace App\Controllers;

class Backup extends BaseController
{
    public function index(): string
    {
        return view('backup/dailyBackup');
    }
}