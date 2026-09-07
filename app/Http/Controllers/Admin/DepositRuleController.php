<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\DepositRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class DepositRuleController extends Controller
{
    public function index(){ return view('admin.deposit-rules.index',['rules'=>DepositRule::query()->orderBy('sort_order')->orderBy('min_total')->get()]); }
    public function store(Request $request){ DepositRule::create($this->validated($request)); return back()->with('success','تمت إضافة قاعدة العربون.'); }
    public function update(Request $request, DepositRule $depositRule){ $depositRule->update($this->validated($request)); return back()->with('success','تم تحديث قاعدة العربون.'); }
    public function toggle(DepositRule $depositRule){ $depositRule->update(['is_active'=>!$depositRule->is_active]); return back()->with('success','تم تحديث حالة القاعدة.'); }
    public function destroy(DepositRule $depositRule){ $depositRule->delete(); return back()->with('success','تم حذف قاعدة العربون.'); }
    private function validated(Request $request): array { return $request->validate(['name'=>['required','string','max:150'],'min_total'=>['required','numeric','min:0'],'max_total'=>['nullable','numeric','gte:min_total'],'type'=>['required',Rule::in(['percentage','fixed'])],'value'=>['required','numeric','min:0'],'sort_order'=>['required','integer','min:0','max:9999']]); }
}
