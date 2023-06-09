<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;


class UserController extends Controller
{
    //



    public function __construct()
    {
        $this->middleware('auth:api', ['except' => [
            'login',
            'logout',
            'getOrganizationbyUserId'
        ]]);
    }

    public function login(Request $request) {
        try {
            $request->validate([
                'email' => ['required','email'],
                'password' => ['required'],
            ]);
            $email = $request->input('email');
            $password = $request->input('password');
            $data = User::where('email', $email)->first();

            if(Hash::check($password, $data->password))
            {
                if(! $token = auth('api')->login($data)) {
                    return response()->json(['error' => 'Unauthorized'], 401);
                } else {
                    $user = User::where('email', $request->email)->first();
                    $user->save();
                    return $this->respondWithToken($token, $data);
                }
            }
        }catch(\Exception $e){
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ],401);
        }
    }

    public function register(Request $request) {
        $validatedData = $request->validate([
            'name' => ['required','string'],
            'email' => ['required','string', 'email'],
            'password' => ['required','string'],
            'phone_number' => ['required','string'],
            // 'photo' => ['string'],
            'organization_ids' => 'array',
            'organization_ids.*' => 'integer|exists:organizations,id',
            'role_ids' => 'array',
            'role_ids.*' => 'integer|exists:roles,id',
        ]);

        try {
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone_number' => $request->input('phone_number'),
                // 'photo' => $request->input('photo'),
                'password' => Hash::make($validatedData['password'])
            ]);

            $organizationIds = $request->input('organization_ids');
            $roleIds = $request->input('role_ids');

            $organizations = Organization::whereIn('id', $organizationIds)->get();
            $roles = Role::whereIn('id', $roleIds)->get();

            foreach ($organizations as $organization) {
                foreach ($roles as $role) {
                    $user->organizations()->attach($organization, ['role_id' => $role->id]);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Register success',
                'data' => new UserResource($user)
            ],200);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ],401);
        }
    }

    public function profile($id) {
        $data = User::find($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Success',
            'data' => new UserResource($data)
        ], 200);
    }

    public function update(Request $request, $id) {
        $validatedData = $request->validate([
            'name' => ['string'],
            'email' => ['email'],
            'password' => ['string'],
            'phone_number' => ['string'],
            // 'photo' => ['string'],
            'organization_ids' => 'array',
            'organization_ids.*' => 'integer',
            'role_ids' => 'array',
            'role_ids.*' => 'integer',
        ]);

        try {
            // $data = User::find($id);
            // $data->update([
            //     'name' => $validatedData['name'] ?? $data->name,
            //     'email' => $validatedData['email'] ?? $data->email,
            //     'phone_number' => $validatedData['phone_number'] ?? $data->phone_number,
            //     'photo' => $validatedData['photo'] ?? $data->photo,
            //     // 'password' => Hash::make($validatedData['password']) ?? $data->password
            // ]);
            $user = User::findOrFail($id);

            // Validasi request jika diperlukan
            $validatedData = $request->validate([
                'name' => 'required',
                'email' => 'required|email',
                'phone_number' => 'required',
                'password' => 'nullable|string',
                'organization_ids' => 'nullable|array',
                'role_ids' => 'nullable|array',
            ]);

            // Update data user
            $user->name = $validatedData['name'];
            $user->email = $validatedData['email'];
            $user->phone_number = $validatedData['phone_number'];
            $user->password = Hash::make($validatedData['password']);
            // $user->photo = $validatedData['photo'];

            // Simpan perubahan pada user
            $user->save();

            if (isset($validatedData['organization_ids'])) {
                $user->organizations()->sync($validatedData['organization_ids']);
            } else {
                $user->organizations()->detach();
            }

            if (isset($validatedData['role_ids'])) {
                foreach ($validatedData['organization_ids'] as $organizationId) {
                    $user->roles()->syncWithoutDetaching($validatedData['role_ids'], ['organization_id' => $organizationId]);
                }
            } else {
                $user->roles()->detach();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Update success',
                'data' => $user
            ],200);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getOrganizationbyUserId($id) {
        $data = User::find($id);
        $organizations = $data->organizations;
        return response()->json([
            'status' => 'success',
            'message' => 'Success',
            'data' => $organizations
        ], 200);
    }

    public function delete($id) {
        $data = User::find($id);
        $data->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Delete success'
        ], 200);
    }


         /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token,$data)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * (60 * 24 * 30),
            'data' => new UserResource($data)
        ]);
    }

    public function refresh() {
        $token = JWTAuth::getToken();
        $newToken = JWTAuth::refresh($token, true);
        return response()->json([
            'code' => 200,
            'access_token' => $newToken
        ], 200);
    }
}
