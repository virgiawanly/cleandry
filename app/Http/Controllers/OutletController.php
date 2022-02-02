<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Yajra\DataTables\DataTables;

class OutletController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('outlets.index', [
            'title' => 'Kelola Outlet',
            'breadcrumbs' => [
                [
                    'href' => '/outlets',
                    'label' => 'Outlets'
                ]
            ],
        ]);
    }

    /**
     * Return data for DataTables.
     *
     * @return \Illuminate\Http\Response
     */
    public function datatable()
    {
        $outlets = Outlet::all();

        return DataTables::of($outlets)
            ->addIndexColumn()
            ->addColumn('actions', function () {
                $editBtn = '<button class="btn btn-warning mx-1 mb-1">
                    <i class="fas fa-edit mr-1"></i>
                    <span>Edit outlet</span>
                </button>';
                $deleteBtn = '<button class="btn btn-danger mx-1 mb-1">
                    <i class="fas fa-trash mr-1"></i>
                    <span>Hapus outlet</span>
                </button>';
                return $editBtn . $deleteBtn;
            })->rawColumns(['actions'])->make(true);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'phone' => 'required|max:16',
            'address' => 'required'
        ]);

        $payload = [
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
        ];

        Outlet::create($payload);

        return response()->json([
            'success' => true,
            'message' => 'Outlet berhasil dibuat'
        ], Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Outlet  $outlet
     * @return \Illuminate\Http\Response
     */
    public function show(Outlet $outlet)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Outlet  $outlet
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Outlet $outlet)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Outlet  $outlet
     * @return \Illuminate\Http\Response
     */
    public function destroy(Outlet $outlet)
    {
        //
    }
}
