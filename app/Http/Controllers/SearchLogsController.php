<?php

namespace Expose\Client\Http\Controllers;

use Expose\Client\Contracts\LogStorageContract;
use Expose\Common\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class SearchLogsController extends Controller
{
    public function __construct(protected LogStorageContract $logStorage)
    {
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $httpConnection->send(respond_json($this
            ->logStorage
            ->requests()
            ->search($request->get('search_term'))
            ->toArray()
        ));
    }
}
