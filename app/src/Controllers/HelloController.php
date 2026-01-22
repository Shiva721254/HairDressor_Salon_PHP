<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class HelloController extends Controller
{
    public function greet(array $vars): string
    {
        $name = (string)($vars['name'] ?? 'Guest');

        return $this->render('hello', [
            'title' => 'Hello',
            'name'  => $name
        ]);
    }
}
