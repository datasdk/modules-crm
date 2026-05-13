<?php

namespace Modules\Crm\Http\Controllers\Api;

use App\Http\Controllers\OrionBaseController;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Modules\Crm\Services\UserService;
use Modules\Email\Services\InviteService;
use Orion\Http\Requests\Request;

class UserController extends OrionBaseController
{
    protected $model = User::class;

    protected $request = UserRequest::class;

    protected $includes = [
        "categories",
        "roles",
        "role",
        "contact",
        "address",
    ];

    protected $searchableBy = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
    ];

    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        parent::__construct();

        $this->userService = $userService;
    }

    public function store(Request $req)
    {
        return $this->userService->create($req->validated());
    }

    public function update(Request $req, ...$args)
    {
        $id = $args[0];
        $user = $id === 'me' ? Auth::user() : User::findOrFail($id);
        $params = $req->validated();

        if ($req->hasFile('image')) {
            $params['image'] = $req->file('image');
        } elseif ($req->exists('image') && $req->input('image') === null) {
            $params['image'] = null;
        }

        $this->userService->update($user, $params);

        return $user->load('address', 'contact');
    }

    public function destroy(Request $req, ...$args)
    {
        if (User::count() === 1) {
            return response("You can't delete the last user", 400);
        }

        $id = $args[0];
        $user = $id === 'me' ? Auth::user() : User::findOrFail($id);
        $user->delete();

        return response()->noContent();
    }

    public function invite(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "email" => "required_without:emails|email",
            "emails" => "required_without:email|array",
            "emails.*" => "required_if:emails|email",
        ]);

        if ($validator->fails()) {
            return response($validator->errors(), 400);
        }

        $emails = $req->has("email") ? [$req->get("email")] : $req->get("emails");
        $send = [];

        foreach ($emails as $email) {
            $send[] = app(InviteService::class)->send(User::findByEmail($email));
        }

        return $send;
    }

    public function exists(Request $req, $value)
    {
        return intval(User::where("email", $value)->orWhere("id", $value)->exists());
    }

    public function restore(Request $req, ...$args)
    {
        $id = $args[0];
        $user = User::withTrashed()->findOrFail($id);

        if ($user->trashed()) {
            $user->restore();

            return response()->json([
                'message' => 'User restored successfully',
                'user' => $user->fresh(),
            ]);
        }

        return response()->json([
            'message' => 'User is not deleted',
        ], 400);
    }

    protected function runFetchQuery(Request $request, Builder $query, $arg): Model
    {
        $key = $arg[0];

        if ($key === "me") {
            if (!$request->user()) {
                abort(401, 'Unauthenticated');
            }

            $key = $request->user()->id;
        }

        return $query->findOrFail($key);
    }
}
