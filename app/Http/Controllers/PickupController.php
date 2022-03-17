<?php

namespace App\Http\Controllers;

use App\Exports\PickupsExport;
use App\Imports\PickupsImport;
use App\Models\Member;
use App\Models\Outlet;
use App\Models\Pickup;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;

class PickupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \App\Models\Outlet  $outlet
     * @return \Illuminate\Http\Response
     */
    public function index(Outlet $outlet)
    {
        return view('pickups.index', [
            'title' => 'Penjemputan Laundry',
            'breadcrumbs' => [
                [
                    'href' => '/services',
                    'label' => 'Layanan'
                ]
            ],
            'outlet' => $outlet,
            'members' => Member::where('outlet_id', $outlet->id)->get()
        ]);
    }

    /**
     * Datatable
     *
     * @param  \App\Models\Outlet  $outlet
     * @return \Illuminate\Http\Response
     */
    public function datatable(Outlet $outlet)
    {
        $pickups = Pickup::with('member')->where('outlet_id', $outlet->id)->latest()->get();

        return DataTables::of($pickups)
            ->addIndexColumn()
            ->addColumn('update_status', function ($pickup) use ($outlet) {
                $dropdown = '<select class="pickup-status form-control" data-update-url="' . route('pickups.updateStatus', [$outlet->id, $pickup->id]) . '">';
                $dropdown .= '<option value="noted"';
                if ($pickup->status === 'noted') $dropdown .= ' selected';
                $dropdown .= '>Tercatat</option>';

                $dropdown .= '<option value="process"';
                if ($pickup->status === 'process') $dropdown .= ' selected';
                $dropdown .= '>Penjemputan</option>';

                $dropdown .= '<option value="done"';
                if ($pickup->status === 'done') $dropdown .= ' selected';
                $dropdown .= '>Selesai</option>';
                $dropdown .= '</select>';
                return $dropdown;
            })
            ->addColumn('actions', function ($pickup) use ($outlet) {
                $editBtn = '<button class="btn btn-info mx-1 edit-pickup-button" data-update-url="' . route('pickups.update', [$outlet->id,  $pickup->id]) . '">
                    <i class="fas fa-edit"></i>
                    <span>Edit</span>
                </button>';
                $deletBtn = '<button class="btn btn-danger mx-1 delete-pickup-button" data-delete-url="' . route('pickups.destroy', [$outlet->id,  $pickup->id]) . '">
                    <i class="fas fa-trash"></i>
                    <span>Hapus</span>
                </button>';
                return $editBtn . $deletBtn;
            })
            ->editColumn('created_at', function ($pickup) {
                return $pickup->created_at ? $pickup->created_at->diffForHumans() : '-';
            })
            ->rawColumns(['update_status', 'actions'])->make(true);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Models\Outlet  $outlet
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Outlet $outlet)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'courier' => 'required',
            'status' => 'required|in:noted,process,done',
        ]);

        Pickup::create([
            'outlet_id' => $outlet->id,
            'member_id' => $request->member_id,
            'courier' => $request->courier,
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'Data penjemputan berhasil ditambahkan'
        ], Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Outlet  $outlet
     * @param  \App\Models\Pickup  $pickup
     * @return \Illuminate\Http\Response
     */
    public function show(Outlet $outlet, Pickup $pickup)
    {
        return response()->json([
            'message' => 'Data penjemputan',
            'pickup' => $pickup
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Outlet  $outlet
     * @param  \App\Models\Pickup  $pickup
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Outlet $outlet, Pickup $pickup)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'courier' => 'required',
            'status' => 'required|in:noted,process,done',
        ]);

        $pickup->update([
            'outlet_id' => $outlet->id,
            'member_id' => $request->member_id,
            'courier' => $request->courier,
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'Data penjemputan berhasil diupdate'
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified resource status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Outlet  $outlet
     * @param  \App\Models\Pickup  $pickup
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request, Outlet $outlet, Pickup $pickup)
    {
        $request->validate([
            'status' => 'required|in:noted,process,done',
        ]);

        $pickup->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'Status diupdate'
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Outlet  $outlet
     * @param  \App\Models\Pickup  $pickup
     * @return \Illuminate\Http\Response
     */
    public function destroy(Outlet $outlet, Pickup $pickup)
    {
        if ($pickup->delete()) {
            return response()->json([
                'message' => 'Data berhasil dihapus'
            ], Response::HTTP_OK);
        };

        return response()->json([
            'message' => 'Terjadi kesalahan'
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Save data as excel file.
     *
     * @param  \App\Models\Outlet  $outlet
     * @return \App\Exports\PickupsExport
     */
    public function exportExcel(Outlet $outlet)
    {
        return (new PickupsExport)->whereOutlet($outlet->id)->download('Data-penjemputan-' . date('d-m-Y') . '.xlsx');
    }

    /**
     * Save data as pdf file.
     *
     * @param  \App\Models\Outlet  $outlet
     * @return \Barryvdh\DomPDF\Facade\Pdf
     */
    public function exportPDF(Outlet $outlet)
    {
        $pickups = Pickup::where('outlet_id', $outlet->id)->with('outlet')->get();

        $pdf = Pdf::loadView('pickups.pdf', ['pickups' => $pickups, 'outlet' => $outlet]);
        return $pdf->stream('Layanan-' . date('dmY') . '.pdf');
    }

    /**
     * Import services data from xlsx file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Outlet  $outlet
     * @return \Illuminate\Http\RedirectResponse;
     */
    public function importExcel(Request $request, Outlet $outlet)
    {
        $request->validate([
            'file_import' => 'required|file|mimes:xlsx'
        ]);

        Excel::import(new PickupsImport, $request->file('file_import'));

        return response()->json([
            'message' => 'Import data berhasil'
        ], Response::HTTP_OK);
    }


    /**
     * Download excel template.
     *
     * @return \Illuminate\Support\Facades\Storage
     */
    public function downloadTemplate()
    {
        return Storage::download('templates/Import_penjemputan_cleandry.xlsx');
    }
}
