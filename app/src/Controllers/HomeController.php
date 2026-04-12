<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function home(): string
    {
        $dayNames = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
        $openingTimes = [];
        foreach ($dayNames as $d => $label) {
            $hours = ($d <= 5) ? '08:00 - 17:00' : 'Closed';
            $openingTimes[] = ['day' => $label, 'hours' => $hours];
        }

        return $this->render('home', [
            'title' => 'Home',
            'openingTimes' => $openingTimes,
        ]);
    }
}
