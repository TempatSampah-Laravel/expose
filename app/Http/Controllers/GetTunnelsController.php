<?php

namespace Expose\Client\Http\Controllers;

use Expose\Client\Client;
use Expose\Common\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class GetTunnelsController extends Controller
{
    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $httpConnection->send(respond_json([
            'tunnels' => Client::$subdomains,
        ], 200));
    }
}
