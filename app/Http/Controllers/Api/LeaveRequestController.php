<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LeaveRequestController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'type'       => 'required|in:Sakit,Izin,Cuti',
            'reason'     => 'required|string',
            'image'      => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = $request->user();
        $today = Carbon::today()->toDateString();

        // 1. CEK APAKAH SUDAH ABSEN HARI INI?
        // Jika start_date izin adalah hari ini, cek tabel attendance
        if ($request->start_date == $today) {
            $hasAttended = Attendance::where('user_id', $user->id)
                ->where('date', $today)
                ->exists();

            if ($hasAttended) {
                return response()->json([
                    'message' => 'Gagal! Anda sudah tercatat absen masuk hari ini. Izin hanya bisa diajukan jika Anda tidak masuk.'
                ], 422);
            }
        }

        // 2. CEK APAKAH SUDAH ADA IZIN DI TANGGAL TERSEBUT?
        // Mencegah karyawan spam izin berkali-kali di tanggal yang sama
        $alreadyHasLeave = LeaveRequest::where('user_id', $user->id)
            ->where(function ($query) use ($request) {
                $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                      ->orWhereBetween('end_date', [$request->start_date, $request->end_date]);
            })
            ->exists();

        if ($alreadyHasLeave) {
            return response()->json([
                'message' => 'Anda sudah memiliki pengajuan izin (Pending/Approved) di tanggal tersebut.'
            ], 422);
        }

        // 3. PROSES SIMPAN FOTO
        $attachmentPath = null;
        if ($request->hasFile('image')) {
            $attachmentPath = $request->file('image')->store('leaves', 'public');
        }

        // 4. SIMPAN DATA
        $leave = LeaveRequest::create([
            'user_id'    => $user->id,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'type'       => $request->type,
            'reason'     => $request->reason,
            'attachment' => $attachmentPath,
            'status'     => 'Pending',
        ]);

        return response()->json([
            'message' => 'Pengajuan ' . $request->type . ' berhasil dikirim. Menunggu persetujuan admin.',
            'data'    => $leave
        ]);
    }
}