<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class UserController extends Controller
{
    /**
     * Menampilkan halaman manajemen user.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $outlets = Outlet::all();

        return view('users.index', [
            'title' => 'Kelola Users',
            'breadcrumbs' => [
                [
                    'href' => '/users',
                    'label' => 'Users'
                ]
            ],
            'outlets' => $outlets
        ]);
    }

    /**
     * Mendapatkan semua data user di database.
     *
     * @return \Illuminate\Http\Response
     */
    public function data()
    {
        $users = User::all();

        return response()->json([
            'message' => 'Data outlet',
            'users' => $users,
        ]);
    }

    /**
     * Mendapatkan data user untuk datatable.
     *
     * @return \Illuminate\Http\Response
     */
    public function datatable()
    {
        $users = User::with('outlet')->where('id', '!=', 1)->get();

        return DataTables::of($users)
            ->addIndexColumn()
            ->addColumn('actions', function ($user) {
                $buttons = '<button onclick="editHandler(' . "'" . route('users.update', $user->id) . "'" . ')" class="btn btn-warning mx-1 mb-1">
                        <i class="fas fa-edit mr-1"></i>
                        <span>Edit</span>
                    </button>';
                if ($user->id !== Auth::id()) {
                    $buttons .= '<button onclick="deleteHandler(' . "'" . route('users.destroy', $user->id) . "'" . ')" class="btn btn-danger mx-1 mb-1">
                        <i class="fas fa-trash mr-1"></i>
                        <span>Hapus</span>
                    </button>';
                }
                return $buttons;
            })->rawColumns(['actions'])->make(true);
    }

    /**
     * Menyimpan data user baru ke database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'phone' => 'max:24|unique:users,phone',
            'password' => 'required|min:5|confirmed',
            'password_confirmation' => 'required',
            'role' => 'required|in:admin,owner,cashier',
            'outlet_id' => 'required|exists:outlets,id',
        ]);

        $payload = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'password' => bcrypt($request->password),
            'outlet_id' => $request->outlet_id,
        ];

        User::create($payload);

        return response()->json([
            'message' => 'Registrasi user berhasil'
        ], Response::HTTP_OK);
    }

    /**
     * Menampilkan data user berdasarkan id tertentu.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return response()->json([
            'message' => 'Data user',
            'user' => $user
        ], Response::HTTP_OK);
    }

    /**
     * Mengupdate data user berdasarkan id tertentu.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'max:24|unique:users,phone,' . $user->id,
            'role' => 'required|in:admin,owner,cashier',
            'outlet_id' => 'required|exists:outlets,id',
        ]);

        $payload = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'outlet_id' => $request->outlet_id,
        ];

        if ($request->has('password') && $request->password != '') {
            $request->validate([
                'password' => 'required|min:5|confirmed',
                'password_confirmation' => 'required',
            ]);
            $payload['password'] = bcrypt($request->password);
        }

        $user->update($payload);

        return response()->json([
            'message' => 'User berhasil diupdate'
        ], Response::HTTP_OK);
    }

    /**
     * Menghapus data user di database berdasarkan id tertentu.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        if($user->id === Auth::id()){
            return response()->json([
                'message' => 'Tidak dapat menghapus user',
            ], Response::HTTP_BAD_REQUEST);
        }
        if ($user->delete()) {
            return response()->json([
                'message' => 'User berhasil dihapus'
            ], Response::HTTP_OK);
        };

        return response()->json([
            'message' => 'Terjadi kesalahan'
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Menampilkan halaman edit profile.
     *
     * @return \Illuminate\Http\Response
     */
    public function editProfile()
    {
        $user = Auth::user();

        return view('users.edit_profile', [
            'title' => 'Edit Profile',
            'breadcrumbs' => [
                [
                    'href' => '/edit-profile',
                    'label' => 'Edit Profile'
                ]
            ],
            'user' => $user
        ]);
    }

    /**
     * Mengupdate data profile user.
     *
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        if (!$user) return redirect()->back();

        $request->validate([
            'name' => 'required',
            'phone' => 'required'
        ]);

        $user->name = $request->name;
        $user->phone = $request->phone;

        if ($request->has('password') && !empty($request->password)) {
            $request->validate([
                'password' => 'required|confirmed',
            ]);
            $user->password = bcrypt($request->password);
        }

        $user->save();

        return redirect()->back()->with('success', 'Profile berhasil diupdate');
    }
}
