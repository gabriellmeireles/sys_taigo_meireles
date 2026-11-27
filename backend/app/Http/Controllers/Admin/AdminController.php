<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admins\AdminAccounts;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminResource;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/** Gestão de administradores (tela 4.20 do painel). */
class AdminController extends Controller
{
    public function __construct(private AdminAccounts $accounts) {}

    public function index(): AnonymousResourceCollection
    {
        return AdminResource::collection(Admin::orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge(['email' => mb_strtolower((string) $request->input('email'))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:191', 'unique:admins,email'],
        ], [
            'email.unique' => 'Já existe um administrador com este e-mail.',
        ]);

        $admin = $this->accounts->invite($data['name'], $data['email'], $request->user());

        return (new AdminResource($admin))->response()->setStatusCode(201);
    }

    public function update(Request $request, Admin $admin): AdminResource
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('name', $data)) {
            $admin->update(['name' => $data['name']]);
        }

        if (array_key_exists('is_active', $data)) {
            $this->accounts->setActive($admin, (bool) $data['is_active'], $request->user());
        }

        return new AdminResource($admin->refresh());
    }

    public function destroy(Request $request, Admin $admin): Response
    {
        $this->accounts->delete($admin, $request->user());

        return response()->noContent();
    }

    public function resendInvite(Request $request, Admin $admin): JsonResponse
    {
        $this->accounts->sendInvitation($admin, $request->user());

        return response()->json(['message' => 'Convite reenviado.']);
    }
}
