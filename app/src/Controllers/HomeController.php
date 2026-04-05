<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\AvailabilityRepositoryInterface;

class HomeController extends Controller
{
    public function __construct(private AvailabilityRepositoryInterface $availability)
    {
    }

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

    public function contact(): string
    {
        // CSRF token for the form
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $this->render('contact', [
            'title' => 'Contact',
            'success' => $this->flash('contact_success'),
            'errors'  => $_SESSION['contact_errors'] ?? [],
            'old'     => $_SESSION['contact_old'] ?? [],
            'csrf'    => $_SESSION['csrf_token'],
        ]);
    }

    public function submitContact(): string
    {
        $token = (string)($_POST['csrf'] ?? '');
        $sessionToken = (string)($_SESSION['csrf_token'] ?? '');

        if ($token === '' || !hash_equals($sessionToken, $token)) {
            http_response_code(400);
            return 'Invalid CSRF token';
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));

        $errors = [];
        if ($name === '') $errors[] = 'Name is required.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if ($message === '') $errors[] = 'Message is required.';

        if ($errors) {
            // store temporarily in session then redirect (PRG)
            $_SESSION['contact_errors'] = $errors;
            $_SESSION['contact_old'] = compact('name', 'email', 'message');
            return $this->redirect('/contact');
        }

        // clear old session data
        unset($_SESSION['contact_errors'], $_SESSION['contact_old']);

        $this->flash('contact_success', 'Thanks! Your message was received.');
        return $this->redirect('/contact');
    }
}
