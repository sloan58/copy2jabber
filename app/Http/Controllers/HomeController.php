<?php

namespace App\Http\Controllers;

use App\Models\Ucm;
use Illuminate\Contracts\Support\Renderable;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Show the application dashboard.
     *
     * @return Renderable
     */
    public function index()
    {
        $ucms = Ucm::all();

        return view('home', compact('ucms'));
    }
}
