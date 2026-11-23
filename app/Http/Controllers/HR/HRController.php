<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

use App\Models\User;
use App\Models\Branch;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\HrAnnouncement;

class HRController extends Controller
{
    /* ============================================================
     |  DASHBOARD
     ============================================================ */

    /**
     * HR Dashboard
     */
    public function index()
    {
        $user = Auth::user();

        // Staff statistics
        $totalStaff       = User::count();
        $branchManagers   = User::where('role', 'branch_manager')->count();
        $accountants      = User::where('role', 'accountant')->count();
        $secretaries      = User::where('role', 'secretary')->count();
        $regularStaff     = User::where('role', 'staff')->count();
        $hrStaff          = User::where('role', 'hr')->count();
        $directors        = User::where('role', 'director')->count();

        // Recent hires (last 30 days)
        $newHires = User::whereBetween('created_at', [now()->subDays(30), now()])->count();

        // Staff by branch
        $staffByBranch = User::with('branch')
            ->select('assigned_branch', DB::raw('count(*) as total'))
            ->groupBy('assigned_branch')
            ->get()
            ->map(function ($item) {
                return [
                    'branch_name' => $item->branch->name ?? 'Unassigned',
                    'total'       => $item->total,
                ];
            });

        // Role distribution
        $roleDistribution = User::select('role', DB::raw('count(*) as total'))
            ->groupBy('role')
            ->get()
            ->map(function ($item) {
                return [
                    'role'  => ucfirst(str_replace('_', ' ', $item->role)),
                    'total' => $item->total,
                ];
            });

        $activeEmployees = $totalStaff;

        return view('hr.index', compact(
            'totalStaff',
            'branchManagers',
            'accountants',
            'secretaries',
            'regularStaff',
            'hrStaff',
            'directors',
            'newHires',
            'staffByBranch',
            'roleDistribution',
            'activeEmployees'
        ));
    }

    /* ============================================================
     |  EMPLOYEES
     ============================================================ */

    /**
     * List all employees
     */
    public function employees(Request $request)
    {
        $query = User::with(['branch']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', '%' . $search . '%')
                  ->orWhere('username', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('branch_id')) {
            $query->where('assigned_branch', $request->branch_id);
        }

        $employees = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        $branches  = Branch::orderBy('name')->get();
        $roles     = User::select('role')->distinct()->pluck('role');

        return view('hr.employees', compact('employees', 'branches', 'roles'));
    }

    /**
     * Show employee details
     */
    public function show($id)
    {
        $employee = User::with(['branch'])->findOrFail($id);

        // Extra HR context
        $recentAttendance = Attendance::where('employee_id', $id)
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        $recentLeaves = Leave::where('employee_id', $id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentPayroll = Payroll::where('employee_id', $id)
            ->orderBy('period_end', 'desc')
            ->limit(5)
            ->get();

        return view('hr.show', compact('employee', 'recentAttendance', 'recentLeaves', 'recentPayroll'));
    }

    /* ============================================================
     |  ATTENDANCE
     ============================================================ */

    /**
     * Show attendance page
     */
    public function attendance(Request $request)
    {
        $date = $request->input('date', now()->toDateString());

        $base = Attendance::query()->whereDate('date', $date);

        if ($request->filled('branch_id')) {
            $base->whereHas('employee', function ($q) use ($request) {
                $q->where('assigned_branch', $request->branch_id);
            });
        }

        if ($request->filled('status')) {
            $base->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $base->whereHas('employee', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $stats = [
            'present'        => (clone $base)->where('status', 'present')->count(),
            'late'           => (clone $base)->where('status', 'late')->count(),
            'absent'         => (clone $base)->where('status', 'absent')->count(),
            'on_leave'       => (clone $base)->where('status', 'leave')->count(),
            'total_hours'    => (clone $base)->sum('hours_worked') ?? 0,
            'total_overtime' => (clone $base)->sum('overtime_hours') ?? 0,
        ];

        $attendance = $base->with('employee.branch')
            ->orderBy('check_in', 'asc')
            ->paginate(25)
            ->withQueryString();

        $branches  = Branch::orderBy('name')->get();
        $employees = User::with('branch')
            ->orderBy('full_name')
            ->when($request->filled('branch_id'), fn($q) => $q->where('assigned_branch', $request->branch_id))
            ->get();

        $markedIds = Attendance::whereDate('date', $date)->pluck('employee_id')->toArray();

        return view('hr.attendance', compact(
            'attendance',
            'stats',
            'branches',
            'employees',
            'date',
            'markedIds'
        ));
    }

    /**
     * Store / update attendance record
     */
    public function storeAttendance(Request $request)
    {
        $validated = $request->validate([
            'employee_id'    => 'required|exists:users,id',
            'date'           => 'required|date',
            'check_in'       => 'nullable|date_format:H:i',
            'check_out'      => 'nullable|date_format:H:i|after:check_in',
            'status'         => 'required|in:present,absent,late,half_day,holiday,leave',
            'overtime_hours' => 'nullable|numeric|min:0|max:24',
            'notes'          => 'nullable|string|max:500',
        ]);

        $hours = 0;
        if (!empty($validated['check_in']) && !empty($validated['check_out'])) {
            $in  = Carbon::createFromFormat('H:i', $validated['check_in']);
            $out = Carbon::createFromFormat('H:i', $validated['check_out']);
            $hours = round($out->diffInMinutes($in) / 60, 2);
        }

        Attendance::updateOrCreate(
            [
                'employee_id' => $validated['employee_id'],
                'date'        => $validated['date'],
            ],
            [
                'check_in'       => $validated['check_in']  ?? null,
                'check_out'      => $validated['check_out'] ?? null,
                'status'         => $validated['status'],
                'hours_worked'   => $hours,
                'overtime_hours' => $validated['overtime_hours'] ?? 0,
                'notes'          => $validated['notes'] ?? null,
            ]
        );

        return back()->with('success', __('Attendance saved successfully.'));
    }

    /**
     * Check-in current user
     */
    public function checkIn(Request $request)
    {
        $user  = Auth::user();
        $today = now()->toDateString();

        $attendance = Attendance::firstOrNew([
            'employee_id' => $user->id,
            'date'        => $today,
        ]);

        if ($attendance->check_in) {
            return back()->with('error', __('You have already checked in today.'));
        }

        $now = now();
        $attendance->check_in = $now->format('H:i:s');
        $attendance->status   = $now->format('H:i') > '09:00' ? 'late' : 'present';
        $attendance->save();

        return back()->with('success', __('Checked in at :time', ['time' => $now->format('H:i')]));
    }

    /**
     * Check-out current user
     */
    public function checkOut(Request $request)
    {
        $user  = Auth::user();
        $today = now()->toDateString();

        $attendance = Attendance::where('employee_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance || !$attendance->check_in) {
            return back()->with('error', __('You must check in first.'));
        }

        if ($attendance->check_out) {
            return back()->with('error', __('You have already checked out today.'));
        }

        $now = now();
        $attendance->check_out = $now->format('H:i:s');

        $in  = Carbon::parse($today . ' ' . $attendance->check_in);
        $attendance->hours_worked = round($now->diffInMinutes($in) / 60, 2);

        $attendance->save();

        return back()->with('success', __('Checked out at :time', ['time' => $now->format('H:i')]));
    }

    /**
     * Delete attendance record
     */
    public function destroyAttendance($id)
    {
        Attendance::findOrFail($id)->delete();
        return back()->with('success', __('Attendance record deleted.'));
    }

    /* ============================================================
     |  LEAVES
     ============================================================ */

    /**
     * Show leave management page
     */
    public function leaves(Request $request)
    {
        $query = Leave::with('employee.branch');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('leave_type', $request->type);
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('assigned_branch', $request->branch_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $leaves = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $stats = [
            'pending'  => Leave::where('status', 'pending')->count(),
            'approved' => Leave::where('status', 'approved')->count(),
            'rejected' => Leave::where('status', 'rejected')->count(),
            'on_leave_today' => Leave::where('status', 'approved')
                ->whereDate('start_date', '<=', today())
                ->whereDate('end_date', '>=', today())
                ->count(),
        ];

        $branches  = Branch::orderBy('name')->get();
        $employees = User::orderBy('full_name')->get();

        return view('hr.leaves', compact('leaves', 'stats', 'branches', 'employees'));
    }

    /**
     * Store a leave request
     */
    public function storeLeave(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'leave_type'  => 'required|in:annual,sick,maternity,paternity,emergency,unpaid,other',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'reason'      => 'nullable|string|max:1000',
        ]);

        $days = Carbon::parse($validated['start_date'])
            ->diffInDays(Carbon::parse($validated['end_date'])) + 1;

        Leave::create([
            'employee_id' => $validated['employee_id'],
            'leave_type'  => $validated['leave_type'],
            'start_date'  => $validated['start_date'],
            'end_date'    => $validated['end_date'],
            'days'        => $days,
            'reason'      => $validated['reason'] ?? null,
            'status'      => 'pending',
        ]);

        return back()->with('success', __('Leave request submitted.'));
    }

    /**
     * Approve a leave request
     */
    public function approveLeave(Request $request, $id)
    {
        $leave = Leave::findOrFail($id);

        $leave->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'notes'       => $request->input('notes'),
        ]);

        // Optionally flag attendance as "leave" for the date range
        $period = Carbon::parse($leave->start_date)->daysUntil($leave->end_date);
        foreach ($period as $date) {
            Attendance::updateOrCreate(
                ['employee_id' => $leave->employee_id, 'date' => $date->toDateString()],
                ['status' => 'leave', 'notes' => 'Auto-marked from approved leave #' . $leave->id]
            );
        }

        return back()->with('success', __('Leave approved.'));
    }

    /**
     * Reject a leave request
     */
    public function rejectLeave(Request $request, $id)
    {
        $leave = Leave::findOrFail($id);

        $leave->update([
            'status'      => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'notes'       => $request->input('notes'),
        ]);

        return back()->with('success', __('Leave rejected.'));
    }

    /**
     * Delete a leave record
     */
    public function destroyLeave($id)
    {
        Leave::findOrFail($id)->delete();
        return back()->with('success', __('Leave record deleted.'));
    }

    /* ============================================================
     |  PAYROLL
     ============================================================ */

    /**
     * Show payroll page
     */
    public function payroll(Request $request)
    {
        $query = Payroll::with('employee.branch');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('assigned_branch', $request->branch_id);
            });
        }

        if ($request->filled('period_start') && $request->filled('period_end')) {
            $query->whereBetween('period_start', [$request->period_start, $request->period_end]);
        }

        $payroll = $query->orderBy('period_end', 'desc')->paginate(20)->withQueryString();

        $stats = [
            'draft'         => Payroll::where('status', 'draft')->count(),
            'processed'     => Payroll::where('status', 'processed')->count(),
            'paid'          => Payroll::where('status', 'paid')->count(),
            'total_net_pay' => Payroll::where('status', 'paid')
                ->whereMonth('payment_date', now()->month)
                ->sum('net_pay'),
        ];

        $branches = Branch::orderBy('name')->get();

        return view('hr.payroll', compact('payroll', 'stats', 'branches'));
    }

    /**
     * Show payroll generation form
     */
    public function createPayroll()
    {
        $branches  = Branch::orderBy('name')->get();
        $employees = User::with('branch')->orderBy('full_name')->get();

        return view('hr.payroll-create', compact('branches', 'employees'));
    }

    /**
     * Store a payroll entry
     */
    public function storePayroll(Request $request)
    {
        $validated = $request->validate([
            'employee_id'  => 'required|exists:users,id',
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
            'base_salary'  => 'required|numeric|min:0',
            'allowances'   => 'nullable|numeric|min:0',
            'bonus'        => 'nullable|numeric|min:0',
            'overtime_pay' => 'nullable|numeric|min:0',
            'deductions'   => 'nullable|numeric|min:0',
            'tax'          => 'nullable|numeric|min:0',
            'notes'        => 'nullable|string|max:500',
        ]);

        $net = $validated['base_salary']
             + ($validated['allowances']   ?? 0)
             + ($validated['bonus']        ?? 0)
             + ($validated['overtime_pay'] ?? 0)
             - ($validated['deductions']   ?? 0)
             - ($validated['tax']          ?? 0);

        Payroll::create([
            'employee_id'  => $validated['employee_id'],
            'period_start' => $validated['period_start'],
            'period_end'   => $validated['period_end'],
            'base_salary'  => $validated['base_salary'],
            'allowances'   => $validated['allowances']   ?? 0,
            'bonus'        => $validated['bonus']        ?? 0,
            'overtime_pay' => $validated['overtime_pay'] ?? 0,
            'deductions'   => $validated['deductions']   ?? 0,
            'tax'          => $validated['tax']          ?? 0,
            'net_pay'      => $net,
            'status'       => 'draft',
            'notes'        => $validated['notes'] ?? null,
        ]);

        return redirect()->route('hr.payroll')->with('success', __('Payroll entry created.'));
    }

    /**
     * Show a single payroll entry
     */
    public function showPayroll($id)
    {
        $payroll = Payroll::with('employee.branch')->findOrFail($id);
        return view('hr.payroll-show', compact('payroll'));
    }

    /**
     * Mark payroll as processed
     */
    public function processPayroll($id)
    {
        $payroll = Payroll::findOrFail($id);
        $payroll->update(['status' => 'processed']);

        return back()->with('success', __('Payroll marked as processed.'));
    }

    /**
     * Mark payroll as paid
     */
    public function markPayrollPaid(Request $request, $id)
    {
        $payroll = Payroll::findOrFail($id);
        $payroll->update([
            'status'       => 'paid',
            'payment_date' => $request->input('payment_date', now()->toDateString()),
        ]);

        return back()->with('success', __('Payroll marked as paid.'));
    }

    /**
     * Delete payroll entry
     */
    public function destroyPayroll($id)
    {
        Payroll::findOrFail($id)->delete();
        return back()->with('success', __('Payroll entry deleted.'));
    }

    /* ============================================================
     |  ANNOUNCEMENTS
     ============================================================ */

    /**
     * Show announcements page
     */
    public function announcements()
    {
        $announcements = HrAnnouncement::with('creator')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('hr.announcements', compact('announcements'));
    }

    /**
     * Store a new announcement
     */
    public function storeAnnouncement(Request $request)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'content'      => 'required|string',
            'type'         => 'required|in:general,policy,event,training,holiday',
            'priority'     => 'required|in:low,medium,high',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
            'is_published' => 'nullable|boolean',
        ]);

        HrAnnouncement::create([
            'title'        => $validated['title'],
            'content'      => $validated['content'],
            'type'         => $validated['type'],
            'priority'     => $validated['priority'],
            'start_date'   => $validated['start_date'] ?? null,
            'end_date'     => $validated['end_date']   ?? null,
            'is_published' => $validated['is_published'] ?? 1,
            'created_by'   => Auth::id(),
        ]);

        return back()->with('success', __('Announcement published.'));
    }

    /**
     * Show edit form for an announcement
     */
    public function editAnnouncement($id)
    {
        $announcement = HrAnnouncement::findOrFail($id);
        return view('hr.announcement-edit', compact('announcement'));
    }

    /**
     * Update an announcement
     */
    public function updateAnnouncement(Request $request, $id)
    {
        $announcement = HrAnnouncement::findOrFail($id);

        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'content'      => 'required|string',
            'type'         => 'required|in:general,policy,event,training,holiday',
            'priority'     => 'required|in:low,medium,high',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
            'is_published' => 'nullable|boolean',
        ]);

        $announcement->update($validated);

        return redirect()->route('hr.announcements')->with('success', __('Announcement updated.'));
    }

    /**
     * Delete an announcement
     */
    public function destroyAnnouncement($id)
    {
        HrAnnouncement::findOrFail($id)->delete();
        return back()->with('success', __('Announcement deleted.'));
    }

    /* ============================================================
     |  REPORTS
     ============================================================ */

    /**
     * Show HR reports landing page
     */
    public function reports()
    {
        $branches = Branch::orderBy('name')->get();
        return view('hr.reports', compact('branches'));
    }

    /**
     * Attendance report
     */
    public function attendanceReport(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to',   now()->toDateString());

        $query = Attendance::with('employee.branch')
            ->whereBetween('date', [$from, $to]);

        if ($request->filled('branch_id')) {
            $query->whereHas('employee', fn($q) => $q->where('assigned_branch', $request->branch_id));
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Summary grouped by employee
        $summary = (clone $query)
            ->select(
                'employee_id',
                DB::raw('COUNT(*) as total_days'),
                DB::raw("SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present_days"),
                DB::raw("SUM(CASE WHEN status='late' THEN 1 ELSE 0 END) as late_days"),
                DB::raw("SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) as absent_days"),
                DB::raw("SUM(CASE WHEN status='leave' THEN 1 ELSE 0 END) as leave_days"),
                DB::raw('SUM(hours_worked) as total_hours'),
                DB::raw('SUM(overtime_hours) as total_overtime')
            )
            ->groupBy('employee_id')
            ->get();

        $records  = $query->orderBy('date', 'desc')->paginate(50)->withQueryString();
        $branches = Branch::orderBy('name')->get();
        $employees = User::orderBy('full_name')->get();

        return view('hr.reports-attendance', compact('summary', 'records', 'branches', 'employees', 'from', 'to'));
    }

    /**
     * Leave report
     */
    public function leavesReport(Request $request)
    {
        $from = $request->input('from', now()->startOfYear()->toDateString());
        $to   = $request->input('to',   now()->toDateString());

        $query = Leave::with('employee.branch')
            ->whereBetween('start_date', [$from, $to]);

        if ($request->filled('branch_id')) {
            $query->whereHas('employee', fn($q) => $q->where('assigned_branch', $request->branch_id));
        }

        $summary = (clone $query)
            ->select(
                'leave_type',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(days) as total_days')
            )
            ->groupBy('leave_type')
            ->get();

        $records  = $query->orderBy('start_date', 'desc')->paginate(50)->withQueryString();
        $branches = Branch::orderBy('name')->get();

        return view('hr.reports-leaves', compact('summary', 'records', 'branches', 'from', 'to'));
    }

    /**
     * Payroll report
     */
    public function payrollReport(Request $request)
    {
        $from = $request->input('from', now()->startOfYear()->toDateString());
        $to   = $request->input('to',   now()->toDateString());

        $query = Payroll::with('employee.branch')
            ->whereBetween('period_end', [$from, $to]);

        if ($request->filled('branch_id')) {
            $query->whereHas('employee', fn($q) => $q->where('assigned_branch', $request->branch_id));
        }

        $summary = (clone $query)
            ->select(
                DB::raw('COUNT(*) as total_entries'),
                DB::raw('SUM(base_salary) as total_base'),
                DB::raw('SUM(allowances) as total_allowances'),
                DB::raw('SUM(bonus) as total_bonus'),
                DB::raw('SUM(overtime_pay) as total_overtime'),
                DB::raw('SUM(deductions) as total_deductions'),
                DB::raw('SUM(tax) as total_tax'),
                DB::raw('SUM(net_pay) as total_net')
            )
            ->first();

        $records  = $query->orderBy('period_end', 'desc')->paginate(50)->withQueryString();
        $branches = Branch::orderBy('name')->get();

        return view('hr.reports-payroll', compact('summary', 'records', 'branches', 'from', 'to'));
    }

    /**
     * Export HR report (CSV)
     */
    public function exportReport(Request $request)
    {
        $type = $request->input('type', 'attendance');
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to',   now()->toDateString());

        $filename = "hr_{$type}_report_{$from}_to_{$to}.csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($type, $from, $to) {
            $handle = fopen('php://output', 'w');

            if ($type === 'attendance') {
                fputcsv($handle, ['Employee', 'Branch', 'Date', 'Check In', 'Check Out', 'Hours', 'Overtime', 'Status', 'Notes']);
                Attendance::with('employee.branch')
                    ->whereBetween('date', [$from, $to])
                    ->chunk(500, function ($rows) use ($handle) {
                        foreach ($rows as $r) {
                            fputcsv($handle, [
                                $r->employee->full_name ?? '',
                                $r->employee->branch->name ?? '',
                                $r->date,
                                $r->check_in,
                                $r->check_out,
                                $r->hours_worked,
                                $r->overtime_hours,
                                $r->status,
                                $r->notes,
                            ]);
                        }
                    });
            } elseif ($type === 'leaves') {
                fputcsv($handle, ['Employee', 'Type', 'Start', 'End', 'Days', 'Status', 'Reason']);
                Leave::with('employee')
                    ->whereBetween('start_date', [$from, $to])
                    ->chunk(500, function ($rows) use ($handle) {
                        foreach ($rows as $r) {
                            fputcsv($handle, [
                                $r->employee->full_name ?? '',
                                $r->leave_type,
                                $r->start_date,
                                $r->end_date,
                                $r->days,
                                $r->status,
                                $r->reason,
                            ]);
                        }
                    });
            } elseif ($type === 'payroll') {
                fputcsv($handle, ['Employee', 'Period Start', 'Period End', 'Base', 'Allowances', 'Bonus', 'Overtime', 'Deductions', 'Tax', 'Net Pay', 'Status']);
                Payroll::with('employee')
                    ->whereBetween('period_end', [$from, $to])
                    ->chunk(500, function ($rows) use ($handle) {
                        foreach ($rows as $r) {
                            fputcsv($handle, [
                                $r->employee->full_name ?? '',
                                $r->period_start,
                                $r->period_end,
                                $r->base_salary,
                                $r->allowances,
                                $r->bonus,
                                $r->overtime_pay,
                                $r->deductions,
                                $r->tax,
                                $r->net_pay,
                                $r->status,
                            ]);
                        }
                    });
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /* ============================================================
     |  API ENDPOINTS
     ============================================================ */

    /**
     * API: list employees
     */
    public function apiEmployees(Request $request)
    {
        $employees = User::with('branch')
            ->when($request->filled('branch_id'), fn($q) => $q->where('assigned_branch', $request->branch_id))
            ->when($request->filled('role'),      fn($q) => $q->where('role', $request->role))
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'username', 'role', 'assigned_branch', 'email', 'avatar']);

        return response()->json($employees);
    }

    /**
     * API: today's attendance
     */
    public function apiTodayAttendance(Request $request)
    {
        $query = Attendance::with('employee.branch')->whereDate('date', today());

        if ($request->filled('branch_id')) {
            $query->whereHas('employee', fn($q) => $q->where('assigned_branch', $request->branch_id));
        }

        return response()->json($query->get());
    }
}