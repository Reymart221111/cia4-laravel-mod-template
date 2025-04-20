<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\Validation\LaravelValidator;
use App\Libraries\Validation\RequestValidator;
use App\Models\User;
use App\Requests\User\StoreUserRequest;
use App\Requests\User\UpdateUserRequest;
use App\Traits\SearchPaginationTrait;
use CodeIgniter\HTTP\ResponseInterface;


class UserController extends BaseController
{
    use SearchPaginationTrait;
    public function index()
    {
        $users = User::select(['id', 'name', 'email'])->paginate(10);
        return blade_view('demo', ['users' => $users]);
    }

    public function create()
    {
        return blade_view('demo-create');
    }

    public function store()
    {
        User::create(StoreUserRequest::validateRequest());
        return redirect()->route('users.index')->with('success', 'User created successfully');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $this->redirectBack404IfNotFound($user, 'User');
        return blade_view('demo-edit', compact('user'));
    }

    public function update($id)
    {
        $user = User::find($id);
        $this->redirectBackIfNotFound($user, 'User');
        $user->update(UpdateUserRequest::validateRequest());
        return redirect()->to(back_url(route_to('users.index')))->with('success', 'User updated successfully');
    }

    public function destroy($id)
    {
        $user = User::find($id);
        $this->redirectBackIfNotFound($user, 'User');
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully');
    }
}
