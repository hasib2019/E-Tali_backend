<?php

namespace App\Http\Controllers\Api;

use App\Models\Business;
use App\Models\Party;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends ApiController
{
    /**
     * Attendance sheet for a date: each student and their marked status.
     * ?date=YYYY-MM-DD (defaults to today), optional ?batch_id.
     */
    public function index(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $date = $request->string('date')->toString();
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = now()->toDateString();
        }

        $students = $business->parties()
            ->where('type', 'customer')
            ->when($request->filled('batch_id'), fn ($q) => $q->where('batch_id', $request->integer('batch_id')))
            ->orderBy('name')
            ->get();

        $marks = $business->attendances()
            ->whereDate('date', $date)
            ->get()
            ->keyBy('party_id');

        $rows = $students->map(fn (Party $s) => [
            'party_id' => $s->id,
            'name' => $s->name,
            'roll' => $s->roll,
            'batch_id' => $s->batch_id,
            'status' => $marks->get($s->id)?->status,
        ])->values();

        return $this->ok([
            'date' => $date,
            'present' => $rows->where('status', 'present')->count(),
            'absent' => $rows->where('status', 'absent')->count(),
            'late' => $rows->where('status', 'late')->count(),
            'rows' => $rows,
        ]);
    }

    /**
     * Bulk upsert attendance for a date.
     * Body: { date, records: [{ party_id, status }] }.
     */
    public function store(Request $request, Business $business): JsonResponse
    {
        $this->ensureOwnsBusiness($business);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.party_id' => ['required', 'integer', 'exists:parties,id'],
            'records.*.status' => ['required', 'in:present,absent,late'],
        ]);

        $date = date('Y-m-d', strtotime($data['date']));
        $ownedIds = $business->parties()->pluck('id');

        DB::transaction(function () use ($business, $data, $date, $ownedIds) {
            foreach ($data['records'] as $rec) {
                if (! $ownedIds->contains($rec['party_id'])) {
                    continue;
                }
                $business->attendances()->updateOrCreate(
                    ['party_id' => $rec['party_id'], 'date' => $date],
                    ['status' => $rec['status']],
                );
            }
        });

        return $this->ok(null, 'Attendance saved.');
    }
}
