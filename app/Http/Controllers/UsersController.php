<?php

namespace App\Http\Controllers;

use App\User;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\ValidationException;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param User $model
     * @return Response
     * @throws Exception
     */
    public function index(User $model)
    {
        if (request()->wantsJson()) {
            return DataTables::of($model->select())
                ->addColumn('actions', function ($model) {
                    return view('users.actions_column', [
                        'path' => strtolower(class_basename($model)),
                        'id' => $model->id
                    ]);
                })->toJson();
        }

        return view('users.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'email' => 'required|email'
        ]);

        try {
            User::create($data);

            flash('User Created')->success();
            return redirect('/user');

        } catch(QueryException $e) {
            request()->session()->flash('status', app()->environment('production') ? 'Could not create user' : $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param User $user
     * @return Response
     */
    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param User $user
     * @return Response
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required',
            'email' => 'required|email'
        ]);

        try {
            $user->update($data);

            flash('User Updated')->success();
            return redirect('/user');

        } catch(QueryException $e) {
            request()->session()->flash('status', app()->environment('production') ? 'Could not update user' : $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return void
     * @throws Exception
     */
    public function destroy(User $user)
    {
        $user->delete();

        flash('User Deleted')->success();
        return redirect('/user');
    }
}
