<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LeaveRequestController extends Controller
{
    /**
     * GET /api/leave
     * Riwayat pengajuan izin/cuti karyawan yang sedang login.
     * Query params: ?status=Pending|Approved|Rejected (opsional)
     *               ?per_page=10 (opsional, default 10)
     */
    public function index(Request $request)
    {
        $user    = $request->user();
        $query   = LeaveRequest::where('user_id', $user->id)
            ->with(['user'])
            ->orderBy('created_at', 'desc');

        // Filter opsional by status
        if ($request->has('status') && in_array($request->status, ['Pending', 'Approved', 'Rejected'])) {
            $query->where('status', $request->status);
        }

        $perPage = (int) $request->query('per_page', 10);
        $leaves  = $query->paginate($perPage);

        $leaves->getCollection()->transform(function ($item) {
            return [
                'id'         => $item->id,
                'type'       => $item->type,
                'reason'     => $item->reason,
                'start_date' => $item->start_date,
                'end_date'   => $item->end_date,
                'status'     => $item->status,
                'attachment' => $item->attachment ? asset('storage/' . $item->attachment) : null,
                'created_at' => $item->created_at->format('Y-m-d H:i'),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $leaves,
        ]);
    }

    public function store(Request $request)
    {
        $maxFutureDate = Carbon::now()->addYear()->toDateString();
        
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after_or_equal:start_date|before_or_equal:' . $maxFutureDate,
            'type'       => 'required|in:Sakit,Izin,Cuti',
            'reason'     => 'required|string',
            'image'      => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = $request->user();
        $today = Carbon::today()->toDateString();

        // Validate maximum leave duration (e.g., max 30 days per request)
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $duration = $startDate->diffInDays($endDate) + 1; // +1 to include both start and end dates

        // Maximum leave duration validation
        $maxDuration = 30; // days
        if ($duration > $maxDuration) {
            return response()->json([
                'success' => false,
                'message' => "Durasi izin maksimal {$maxDuration} hari. Anda mengajukan {$duration} hari. Silakan ajukan per periode.",
            ], 422);
        }

        // Use database transaction to prevent race conditions
        return \DB::transaction(function () use ($request, $user, $today) {
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
            // Exclude rejected leaves dari check
            // Use lockForUpdate to prevent concurrent inserts
            $alreadyHasLeave = LeaveRequest::where('user_id', $user->id)
                ->where('status', '!=', 'Rejected')
                ->where(function ($query) use ($request) {
                    $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                          ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                          ->orWhere(function ($q) use ($request) {
                              // Izin lama mencakup seluruh range izin baru (contains)
                              $q->where('start_date', '<=', $request->start_date)
                                ->where('end_date', '>=', $request->end_date);
                          });
                })
                ->lockForUpdate()
                ->exists();

            if ($alreadyHasLeave) {
                return response()->json([
                    'message' => 'Anda sudah mengajukan izin pada tanggal tersebut. Silakan pilih tanggal lain atau tunggu persetujuan.'
                ], 422);
            }

            // 3. PROSES UPLOAD IMAGE (jika ada)
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imageName = time() . '_' . $user->id . '_' . uniqid() . '.png';
                $imagePath = $request->file('image')->storeAs('leave-requests', $imageName, 'public');
            }

            // 4. BUAT LEAVE REQUEST
            $leave = LeaveRequest::create([
                'user_id'     => $user->id,
                'start_date'  => $request->start_date,
                'end_date'    => $request->end_date,
                'type'        => $request->type,
                'reason'      => $request->reason,
                'status'      => 'Pending',
                'attachment'  => $imagePath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan izin berhasil dikirim. Tunggu persetujuan atasan.',
                'data'    => [
                    'id'         => $leave->id,
                    'start_date' => $leave->start_date,
                    'end_date'   => $leave->end_date,
                    'type'       => $leave->type,
                    'status'     => $leave->status,
                ],
            ], 201);
        });
    }

    /**
     * GET /api/leave/approvals
     * Daftar izin yang menunggu persetujuan (khusus Admin/Super Admin).
     */
    public function approvals(Request $request)
    {
        $user = $request->user();

        if (!$user->hasRole(['super_admin', 'admin_pt'])) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses.',
            ], 403);
        }

        $query = LeaveRequest::with(['user.department', 'user.company'])
            ->whereIn('status', ['Pending', 'Approved', 'Rejected'])
            ->orderBy('created_at', 'desc');

        // Jika admin_pt, filter berdasarkan company_id dari user yang mengajukan
        if ($user->hasRole('admin_pt') && !$user->hasRole('super_admin')) {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        $perPage = (int) $request->query('per_page', 10);
        $leaves  = $query->paginate($perPage);

        $leaves->getCollection()->transform(function ($item) {
            return [
                'id'         => $item->id,
                'user_name'  => $item->user?->name,
                'user_nik'   => $item->user?->nik,
                'department' => $item->user?->department?->name,
                'type'       => $item->type,
                'reason'     => $item->reason,
                'start_date' => $item->start_date,
                'end_date'   => $item->end_date,
                'status'     => $item->status,
                'attachment' => $item->attachment ? asset('storage/' . $item->attachment) : null,
                'created_at' => $item->created_at->format('Y-m-d H:i'),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $leaves,
        ]);
    }

    /**
     * PUT /api/leave/{id}/approve
     */
    public function approve(Request $request, $id)
    {
        return $this->updateLeaveStatus($request, $id, 'Approved');
    }

    /**
     * PUT /api/leave/{id}/reject
     */
    public function reject(Request $request, $id)
    {
        return $this->updateLeaveStatus($request, $id, 'Rejected');
    }

    private function updateLeaveStatus(Request $request, $id, $status)
    {
        $user = $request->user();

        if (!$user->hasRole(['super_admin', 'admin_pt'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $leave = LeaveRequest::with('user')->find($id);

        if (!$leave) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengajuan tidak ditemukan.',
            ], 404);
        }

        // Validasi status transition - hanya bisa approve/reject jika status Pending
        if ($leave->status !== 'Pending') {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan ini sudah ' . strtolower($leave->status) . '. Tidak bisa diubah lagi.',
            ], 422);
        }

        // Pastikan admin PT hanya bisa approve data dari perusahaannya sendiri
        if ($user->hasRole('admin_pt') && !$user->hasRole('super_admin')) {
            if ($leave->user?->company_id !== $user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengajuan ini dari perusahaan lain.',
                ], 403);
            }
        }

        $leave->update(['status' => $status]);

        \Log::info('Leave request status updated', [
            'leave_id' => $leave->id,
            'user_id' => $leave->user_id,
            'status' => $status,
            'approved_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Pengajuan berhasil di-$status.",
            'data'    => $leave
        ]);
    }
}