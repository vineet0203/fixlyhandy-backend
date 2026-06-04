<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\JobActivity;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends BaseController
{
    /**
     * GET /vendors/reports/overview
     * Returns all dashboard data in a single request for performance.
     */
    public function overview(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (!$user?->vendor_id) {
            return $this->errorResponse('Authenticated user is not associated with a vendor.', 403);
        }

        $vendorId = $user->vendor_id;

        return $this->successResponse([
            'kpi_stats'            => $this->buildKpiStats($vendorId),
            'revenue_chart'        => $this->buildRevenueChart($vendorId),
            'job_status'           => $this->buildJobStatus($vendorId),
            'employee_performance' => $this->buildEmployeePerformance($vendorId),
            'invoice_analytics'    => $this->buildInvoiceAnalytics($vendorId),
            'recent_jobs'          => $this->buildRecentJobs($vendorId),
            'top_customers'        => $this->buildTopCustomers($vendorId),
            'top_employees'        => $this->buildTopEmployees($vendorId),
            'revenue_summary'      => $this->buildRevenueSummary($vendorId),
            'recent_activities'    => $this->buildRecentActivities($vendorId),
        ], 'Reports overview retrieved successfully.');
    }

    // ────────────────────────────────────────────────
    //  1. KPI Stats
    // ────────────────────────────────────────────────
    private function buildKpiStats(int $vendorId): array
    {
        $now = Carbon::now();
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd   = $now->copy()->endOfMonth();
        $prevMonthStart    = $now->copy()->subMonth()->startOfMonth();
        $prevMonthEnd      = $now->copy()->subMonth()->endOfMonth();

        // Job counts
        $totalJobs = Job::where('vendor_id', $vendorId)->count();
        $prevJobs  = Job::where('vendor_id', $vendorId)
            ->where('created_at', '<', $currentMonthStart)
            ->count();

        // Total revenue from invoices (grand total = sum of all invoice items' final_amount)
        $totalRevenue = (float) Invoice::whereHas('client', fn($q) => $q->where('vendor_id', $vendorId))
            ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->sum('invoice_items.final_amount');

        $prevRevenue = (float) Invoice::whereHas('client', fn($q) => $q->where('vendor_id', $vendorId))
            ->where('invoices.created_at', '<', $currentMonthStart)
            ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->sum('invoice_items.final_amount');

        // Pending invoices (draft + sent)
        $pendingInvoiceAmount = (float) Invoice::whereHas('client', fn($q) => $q->where('vendor_id', $vendorId))
            ->whereIn('status', ['draft', 'sent'])
            ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->sum('invoice_items.final_amount');

        $prevPendingAmount = (float) Invoice::whereHas('client', fn($q) => $q->where('vendor_id', $vendorId))
            ->whereIn('status', ['draft', 'sent'])
            ->where('invoices.created_at', '<', $currentMonthStart)
            ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->sum('invoice_items.final_amount');

        // Completed jobs
        $completedJobs = Job::where('vendor_id', $vendorId)->where('status', 'completed')->count();
        $prevCompleted = Job::where('vendor_id', $vendorId)->where('status', 'completed')
            ->where('created_at', '<', $currentMonthStart)->count();

        // Active customers (clients with at least 1 job)
        $activeCustomers = Job::where('vendor_id', $vendorId)
            ->whereNotNull('client_id')
            ->distinct('client_id')
            ->count('client_id');
        $prevActiveCustomers = Job::where('vendor_id', $vendorId)
            ->whereNotNull('client_id')
            ->where('created_at', '<', $currentMonthStart)
            ->distinct('client_id')
            ->count('client_id');

        // Employee hours from time_entries
        $totalMinutes = (int) TimeEntry::whereHas('employee', fn($q) => $q->where('vendor_id', $vendorId))
            ->sum('total_time');
        $totalHours = round($totalMinutes / 60);

        $prevMinutes = (int) TimeEntry::whereHas('employee', fn($q) => $q->where('vendor_id', $vendorId))
            ->where('created_at', '<', $currentMonthStart)
            ->sum('total_time');
        $prevHours = round($prevMinutes / 60);

        // Current month revenue
        $currentMonthRevenue = (float) Invoice::whereHas('client', fn($q) => $q->where('vendor_id', $vendorId))
            ->whereBetween('invoices.created_at', [$currentMonthStart, $currentMonthEnd])
            ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->sum('invoice_items.final_amount');

        $prevMonthRevenue = (float) Invoice::whereHas('client', fn($q) => $q->where('vendor_id', $vendorId))
            ->whereBetween('invoices.created_at', [$prevMonthStart, $prevMonthEnd])
            ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->sum('invoice_items.final_amount');

        // Expense estimate: mileage + other_expense from invoice_items
        $totalExpenses = (float) Invoice::whereHas('client', fn($q) => $q->where('vendor_id', $vendorId))
            ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->selectRaw('COALESCE(SUM(invoice_items.mileage), 0) + COALESCE(SUM(invoice_items.other_expense), 0) as total')
            ->value('total');


        $profitMargin = $totalRevenue > 0 ? round((($totalRevenue - $totalExpenses) / $totalRevenue) * 100, 1) : 0;
        $monthlyGrowth = $prevMonthRevenue > 0 ? round((($currentMonthRevenue - $prevMonthRevenue) / $prevMonthRevenue) * 100, 1) : 0;

        $growth = function ($current, $previous) {
            if ($previous <= 0) return $current > 0 ? 100 : 0;
            return round((($current - $previous) / $previous) * 100, 1);
        };

        $prevMonth = $now->copy()->subMonth()->format('M Y');

        return [
            ['id' => 'totalJobs',        'label' => 'Total Jobs',        'value' => number_format($totalJobs), 'growth' => $this->formatGrowth($growth($totalJobs, $prevJobs)), 'positive' => $growth($totalJobs, $prevJobs) >= 0, 'period' => "vs $prevMonth", 'icon' => 'briefcase', 'color' => '#2563eb', 'bg' => '#eff6ff'],
            ['id' => 'totalRevenue',     'label' => 'Total Revenue',     'value' => '$' . number_format($totalRevenue, 2), 'growth' => $this->formatGrowth($growth($totalRevenue, $prevRevenue)), 'positive' => $growth($totalRevenue, $prevRevenue) >= 0, 'period' => "vs $prevMonth", 'icon' => 'dollar', 'color' => '#059669', 'bg' => '#ecfdf5'],
            ['id' => 'pendingInvoices',  'label' => 'Pending Invoices',  'value' => '$' . number_format($pendingInvoiceAmount, 2), 'growth' => $this->formatGrowth($growth($pendingInvoiceAmount, $prevPendingAmount)), 'positive' => $growth($pendingInvoiceAmount, $prevPendingAmount) >= 0, 'period' => "vs $prevMonth", 'icon' => 'invoice', 'color' => '#d97706', 'bg' => '#fffbeb'],
            ['id' => 'completedJobs',    'label' => 'Completed Jobs',    'value' => number_format($completedJobs), 'growth' => $this->formatGrowth($growth($completedJobs, $prevCompleted)), 'positive' => $growth($completedJobs, $prevCompleted) >= 0, 'period' => "vs $prevMonth", 'icon' => 'check', 'color' => '#7c3aed', 'bg' => '#f5f3ff'],
            ['id' => 'activeCustomers',  'label' => 'Active Customers',  'value' => number_format($activeCustomers), 'growth' => $this->formatGrowth($growth($activeCustomers, $prevActiveCustomers)), 'positive' => $growth($activeCustomers, $prevActiveCustomers) >= 0, 'period' => "vs $prevMonth", 'icon' => 'users', 'color' => '#2563eb', 'bg' => '#eff6ff'],
            ['id' => 'employeeHours',    'label' => 'Employee Hours',    'value' => number_format($totalHours) . 'h', 'growth' => $this->formatGrowth($growth($totalHours, $prevHours)), 'positive' => $growth($totalHours, $prevHours) >= 0, 'period' => "vs $prevMonth", 'icon' => 'clock', 'color' => '#059669', 'bg' => '#ecfdf5'],
            ['id' => 'profitMargin',     'label' => 'Profit Margin',     'value' => $profitMargin . '%', 'growth' => $this->formatGrowth($profitMargin), 'positive' => $profitMargin >= 0, 'period' => "vs $prevMonth", 'icon' => 'trending', 'color' => '#d97706', 'bg' => '#fffbeb'],
            ['id' => 'monthlyGrowth',    'label' => 'Monthly Growth',    'value' => $monthlyGrowth . '%', 'growth' => $this->formatGrowth($monthlyGrowth), 'positive' => $monthlyGrowth >= 0, 'period' => "vs $prevMonth", 'icon' => 'chart', 'color' => '#7c3aed', 'bg' => '#f5f3ff'],
        ];
    }

    // ────────────────────────────────────────────────
    //  2. Revenue Chart (last 6 months)
    // ────────────────────────────────────────────────
    private function buildRevenueChart(int $vendorId): array
    {
        $result = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd   = $month->copy()->endOfMonth();

            $revenue = (float) Invoice::whereHas('client', fn($q) => $q->where('vendor_id', $vendorId))
                ->whereBetween('invoices.created_at', [$monthStart, $monthEnd])
                ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
                ->sum('invoice_items.final_amount');

            $result[] = [
                'month' => $month->format('M'),
                'value' => round($revenue, 2),
            ];
        }
        return $result;
    }

    // ────────────────────────────────────────────────
    //  3. Job Status Distribution
    // ────────────────────────────────────────────────
    private function buildJobStatus(int $vendorId): array
    {
        $statusColors = [
            'pending'     => '#f59e0b',
            'in_progress' => '#3b82f6',
            'completed'   => '#10b981',
            'cancelled'   => '#ef4444',
            'scheduled'   => '#8b5cf6',
        ];

        $statusLabels = [
            'pending'     => 'Pending',
            'in_progress' => 'In Progress',
            'completed'   => 'Completed',
            'cancelled'   => 'Cancelled',
            'scheduled'   => 'Scheduled',
        ];

        $counts = Job::where('vendor_id', $vendorId)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $total = array_sum($counts);
        $result = [];

        foreach ($counts as $status => $count) {
            $result[] = [
                'label'   => $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status)),
                'value'   => $count,
                'color'   => $statusColors[$status] ?? '#94a3b8',
                'percent' => $total > 0 ? round(($count / $total) * 100, 1) . '%' : '0%',
            ];
        }

        // Sort: completed first, then by value descending
        usort($result, fn($a, $b) => $b['value'] - $a['value']);

        return $result;
    }

    // ────────────────────────────────────────────────
    //  4. Employee Performance (top 5 by completed jobs)
    // ────────────────────────────────────────────────
    private function buildEmployeePerformance(int $vendorId): array
    {
        $employees = DB::table('employees')
            ->leftJoin('job_assignments', 'employees.id', '=', 'job_assignments.employee_id')
            ->leftJoin('jobs', function ($join) {
                $join->on('job_assignments.job_id', '=', 'jobs.id')
                     ->where('jobs.status', '=', 'completed');
            })
            ->where('employees.vendor_id', $vendorId)
            ->where('employees.is_active', true)
            ->whereNull('employees.deleted_at')
            ->select(
                'employees.id',
                DB::raw("CONCAT(employees.first_name, ' ', COALESCE(employees.last_name, '')) as name"),
                DB::raw('COUNT(DISTINCT jobs.id) as completed_jobs')
            )
            ->groupBy('employees.id', 'employees.first_name', 'employees.last_name')
            ->orderByDesc('completed_jobs')
            ->limit(5)
            ->get();

        return $employees->map(fn($e) => [
            'name'          => trim($e->name),
            'completedJobs' => (int) $e->completed_jobs,
            'color'         => '#3b82f6',
        ])->toArray();
    }

    // ────────────────────────────────────────────────
    //  5. Invoice Analytics (last 6 months — paid/unpaid/overdue)
    // ────────────────────────────────────────────────
    private function buildInvoiceAnalytics(int $vendorId): array
    {
        $result = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd   = $month->copy()->endOfMonth();

            $baseQuery = fn($status) => Invoice::whereHas('client', fn($q) => $q->where('vendor_id', $vendorId))
                ->where('status', $status)
                ->whereBetween('invoices.created_at', [$monthStart, $monthEnd])
                ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
                ->sum('invoice_items.final_amount');

            $paid   = (float) $baseQuery('paid');
            $draft  = (float) $baseQuery('draft');
            $sent   = (float) $baseQuery('sent');

            // Overdue: sent invoices past payment_deadline
            $overdue = (float) Invoice::whereHas('client', fn($q) => $q->where('vendor_id', $vendorId))
                ->where('status', 'sent')
                ->whereNotNull('payment_deadline')
                ->where('payment_deadline', '<', Carbon::now())
                ->whereBetween('invoices.created_at', [$monthStart, $monthEnd])
                ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
                ->sum('invoice_items.final_amount');

            $result[] = [
                'month'   => $month->format('M'),
                'paid'    => round($paid, 2),
                'unpaid'  => round($draft + $sent, 2),
                'overdue' => round($overdue, 2),
            ];
        }
        return $result;
    }

    // ────────────────────────────────────────────────
    //  6. Recent Jobs (last 10 jobs)
    // ────────────────────────────────────────────────
    private function buildRecentJobs(int $vendorId): array
    {
        $jobs = Job::where('vendor_id', $vendorId)
            ->with(['client:id,first_name,last_name,business_name,client_type', 'assignments' => fn($q) => $q->latest()->limit(1), 'assignments.employee:id,first_name,last_name'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return $jobs->map(function ($job) {
            // Get assigned employee name from latest assignment
            $assignment = DB::table('job_assignments')
                ->where('job_id', $job->id)
                ->orderByDesc('id')
                ->first();

            $employeeName = '-';
            if ($assignment) {
                $emp = Employee::find($assignment->employee_id);
                $employeeName = $emp ? trim($emp->first_name . ' ' . ($emp->last_name ?? '')) : '-';
            }

            $clientName = '-';
            if ($job->client) {
                $clientName = $job->client->client_type === 'commercial'
                    ? ($job->client->business_name ?? 'N/A')
                    : trim(($job->client->first_name ?? '') . ' ' . ($job->client->last_name ?? ''));
            }

            $statusMap = [
                'pending'     => 'Pending',
                'in_progress' => 'In Progress',
                'completed'   => 'Completed',
                'cancelled'   => 'Cancelled',
                'scheduled'   => 'Scheduled',
            ];

            return [
                'jobId'       => $job->job_number ?? ('JOB-' . $job->id),
                'customer'    => $clientName,
                'employee'    => $employeeName,
                'serviceType' => ucfirst($job->work_type ?? 'General'),
                'amount'      => '$' . number_format((float) $job->total_amount, 2),
                'status'      => $statusMap[$job->status] ?? ucfirst($job->status ?? 'Pending'),
                'date'        => $job->created_at ? $job->created_at->format('M d, Y') : '-',
            ];
        })->toArray();
    }

    // ────────────────────────────────────────────────
    //  7. Top Customers (by revenue)
    // ────────────────────────────────────────────────
    private function buildTopCustomers(int $vendorId): array
    {
        $customers = DB::table('clients')
            ->leftJoin('jobs', function ($join) {
                $join->on('clients.id', '=', 'jobs.client_id')
                     ->whereNull('jobs.deleted_at');
            })
            ->where('clients.vendor_id', $vendorId)
            ->whereNull('clients.deleted_at')
            ->select(
                'clients.id',
                'clients.client_type',
                'clients.first_name',
                'clients.last_name',
                'clients.business_name',
                DB::raw('COUNT(DISTINCT jobs.id) as total_jobs'),
                DB::raw('COALESCE(SUM(jobs.total_amount), 0) as revenue')
            )
            ->groupBy('clients.id', 'clients.client_type', 'clients.first_name', 'clients.last_name', 'clients.business_name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        return $customers->map(fn($c) => [
            'name'      => $c->client_type === 'commercial'
                ? ($c->business_name ?? 'N/A')
                : trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? '')),
            'totalJobs' => (int) $c->total_jobs,
            'revenue'   => '$' . number_format((float) $c->revenue, 2),
        ])->toArray();
    }

    // ────────────────────────────────────────────────
    //  8. Top Employees (by hours & completed jobs)
    // ────────────────────────────────────────────────
    private function buildTopEmployees(int $vendorId): array
    {
        $employees = DB::table('employees')
            ->leftJoin('time_entries', 'employees.id', '=', 'time_entries.employee_id')
            ->leftJoin('job_assignments', 'employees.id', '=', 'job_assignments.employee_id')
            ->leftJoin('jobs', function ($join) {
                $join->on('job_assignments.job_id', '=', 'jobs.id')
                     ->where('jobs.status', '=', 'completed')
                     ->whereNull('jobs.deleted_at');
            })
            ->where('employees.vendor_id', $vendorId)
            ->where('employees.is_active', true)
            ->whereNull('employees.deleted_at')
            ->select(
                'employees.id',
                DB::raw("CONCAT(employees.first_name, ' ', COALESCE(employees.last_name, '')) as name"),
                DB::raw('COALESCE(SUM(time_entries.total_time), 0) as total_minutes'),
                DB::raw('COUNT(DISTINCT jobs.id) as completed_jobs')
            )
            ->groupBy('employees.id', 'employees.first_name', 'employees.last_name')
            ->orderByDesc('completed_jobs')
            ->limit(5)
            ->get();

        return $employees->map(fn($e) => [
            'name'          => trim($e->name),
            'hours'         => round((int) $e->total_minutes / 60) . 'h',
            'completedJobs' => (int) $e->completed_jobs,
        ])->toArray();
    }

    // ────────────────────────────────────────────────
    //  9. Revenue Summary
    // ────────────────────────────────────────────────
    private function buildRevenueSummary(int $vendorId): array
    {
        $now = Carbon::now();
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd   = $now->copy()->endOfMonth();
        $prevMonthStart    = $now->copy()->subMonth()->startOfMonth();
        $prevMonthEnd      = $now->copy()->subMonth()->endOfMonth();

        $revenueQuery = fn($start, $end) => (float) Invoice::whereHas('client', fn($q) => $q->where('vendor_id', $vendorId))
            ->whereBetween('invoices.created_at', [$start, $end])
            ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->sum('invoice_items.final_amount');

        $expenseQuery = fn($start, $end) => (float) Invoice::whereHas('client', fn($q) => $q->where('vendor_id', $vendorId))
            ->whereBetween('invoices.created_at', [$start, $end])
            ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->selectRaw('COALESCE(SUM(invoice_items.mileage), 0) + COALESCE(SUM(invoice_items.other_expense), 0) as total')
            ->value('total');

        // All-time totals
        $totalRevenue = (float) Invoice::whereHas('client', fn($q) => $q->where('vendor_id', $vendorId))
            ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->sum('invoice_items.final_amount');

        $totalExpenses = (float) Invoice::whereHas('client', fn($q) => $q->where('vendor_id', $vendorId))
            ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->selectRaw('COALESCE(SUM(invoice_items.mileage), 0) + COALESCE(SUM(invoice_items.other_expense), 0) as total')
            ->value('total');

        $netProfit = $totalRevenue - $totalExpenses;

        // Growth calculations
        $curRev  = $revenueQuery($currentMonthStart, $currentMonthEnd);
        $prevRev = $revenueQuery($prevMonthStart, $prevMonthEnd);
        $curExp  = $expenseQuery($currentMonthStart, $currentMonthEnd);
        $prevExp = $expenseQuery($prevMonthStart, $prevMonthEnd);
        $curProfit  = $curRev - $curExp;
        $prevProfit = $prevRev - $prevExp;

        $growthCalc = function ($current, $previous) {
            if ($previous <= 0) return $current > 0 ? 100 : 0;
            return round((($current - $previous) / $previous) * 100, 1);
        };

        return [
            'totalRevenue'  => '$' . number_format($totalRevenue, 2),
            'totalExpenses' => '$' . number_format($totalExpenses, 2),
            'netProfit'     => '$' . number_format($netProfit, 2),
            'revenueGrowth' => $this->formatGrowth($growthCalc($curRev, $prevRev)),
            'expenseGrowth' => $this->formatGrowth($growthCalc($curExp, $prevExp)),
            'profitGrowth'  => $this->formatGrowth($growthCalc($curProfit, $prevProfit)),
        ];
    }

    // ────────────────────────────────────────────────
    //  10. Recent Activities (from job_activities)
    // ────────────────────────────────────────────────
    private function buildRecentActivities(int $vendorId): array
    {
        $activities = JobActivity::whereHas('Job', fn($q) => $q->where('vendor_id', $vendorId))
            ->with(['Job:id,job_number', 'performedBy:id,first_name,last_name'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $typeMap = [
            'created'       => 'job',
            'updated'       => 'status',
            'status_change' => 'status',
            'completion'    => 'completed',
            'payment'       => 'invoice',
            'assignment'    => 'job',
            'note'          => 'status',
            'email'         => 'invoice',
            'attachment'    => 'job',
            'other'         => 'job',
            'deleted'       => 'status',
        ];

        return $activities->map(function ($a) use ($typeMap) {
            $jobNumber = $a->Job?->job_number ?? ('JOB-' . $a->job_id);
            $text = $a->subject ?: ucfirst(str_replace('_', ' ', $a->type)) . " — $jobNumber";
            $by = $a->performedBy ? $a->performedBy->full_name : '';
            $time = $a->created_at ? $a->created_at->diffForHumans(null, true, true) . ' ago' : '';

            return [
                'text' => $text,
                'by'   => $by,
                'time' => $time,
                'type' => $typeMap[$a->type] ?? 'job',
            ];
        })->toArray();
    }

    // ────────────────────────────────────────────────
    //  Helpers
    // ────────────────────────────────────────────────
    private function formatGrowth(float $value): string
    {
        $prefix = $value >= 0 ? '+' : '';
        return $prefix . $value . '%';
    }
}
