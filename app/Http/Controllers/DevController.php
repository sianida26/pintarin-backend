<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DevController extends Controller
{
    /**
     * @OA\Get(
     *      path="/__test__",
     *      tags={"Development Endpoints"},
     *      summary="Test API Endpoint",
     *      description="Returns OK",
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *     )
     */
    public function test(){
        return 'OK';
    }
}
