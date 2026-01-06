<?php

namespace App\Http\Controllers\Api; 


use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct(){ $this->middleware('auth:api'); }

    // /reports/monthly?year=2025
    public function monthly(Request $req){
      $year = (int)($req->get('year', now()->year));
      $uid = $req->user()->id;

      // sum incomes & expenses per month
      $incomes = DB::table('thu_nhap')
        ->selectRaw('MONTH(ngay_thu) as thang, SUM(so_tien) as tong_thu')
        ->whereYear('ngay_thu',$year)->where('user_id',$uid)
        ->groupByRaw('MONTH(ngay_thu)')->pluck('tong_thu','thang');

      $expenses = DB::table('chi_tieu')
        ->selectRaw('MONTH(ngay_chi) as thang, SUM(so_tien) as tong_chi')
        ->whereYear('ngay_chi',$year)->where('user_id',$uid)
        ->groupByRaw('MONTH(ngay_chi)')->pluck('tong_chi','thang');

      $result=[];
      for($m=1;$m<=12;$m++){
        $result[] = [
          'thang'=>$m,
          'thu'=>(float)($incomes[$m] ?? 0),
          'chi'=>(float)($expenses[$m] ?? 0),
          'so_du'=>(float)($incomes[$m] ?? 0) - (float)($expenses[$m] ?? 0),
        ];
      }
      return $result;
    }

    // /reports/by-category?month=9&year=2025
    public function byCategory(Request $req){
      $uid = $req->user()->id;
      $month = (int)($req->get('month', now()->month));
      $year  = (int)($req->get('year',  now()->year));

      $rows = DB::table('chi_tieu as c')
        ->leftJoin('danh_muc as d','d.id','=','c.danh_muc_id')
        ->selectRaw('COALESCE(d.ten,"Khác") as danh_muc, SUM(c.so_tien) as tong_chi')
        ->where('c.user_id',$uid)
        ->whereMonth('c.ngay_chi',$month)->whereYear('c.ngay_chi',$year)
        ->groupBy('danh_muc')->orderByDesc('tong_chi')->get();

      return $rows;
    }
}

