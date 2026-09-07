<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\SalarySlip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MySalarySlipController extends Controller
{
    protected function myEmployee()
    {
        $emp = Auth::user()->employee;
        abort_if(! $emp, 403, 'No employee record linked to your account.');

        return $emp;
    }

    public function index(Request $request)
    {
        if (! Auth::user()->employee) {
            return view('admin.hrm.employee.no-profile');
        }
        $employee = $this->myEmployee();

        $query = SalarySlip::where('employee_id', $employee->id);

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $slips = $query->orderByDesc('year')->orderByDesc('month')
            ->paginate(15)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $slips]);
        }

        $years = SalarySlip::where('employee_id', $employee->id)
            ->distinct()->orderByDesc('year')->pluck('year');

        return view('admin.hrm.my-salary-slips.index', compact('employee', 'slips', 'years'));
    }

    public function downloadPdf(SalarySlip $salarySlip)
    {
        $employee = $this->myEmployee();
        abort_if($salarySlip->employee_id !== $employee->id, 403);
        $salarySlip->load(['employee.user', 'employee.department', 'employee.designation', 'items']);

        $pdf = Pdf::loadView('admin.hrm.salary-slips.pdf', compact('salarySlip'))
            ->setOption(['defaultFont' => 'DejaVu Sans']); // Ensures ₹ is supported globally

        return $pdf->download("salary-slip-{$salarySlip->slip_number}.pdf");
    }
}
