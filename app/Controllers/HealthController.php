<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

class HealthController extends Controller
{
    public function index(Request $req, array $params): Response
    {
        return Response::json([
            'servicio' => 'Polycraft API',
            'version'  => '1.0',
            'hora'     => date('c'),
        ]);
    }
}
