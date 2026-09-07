<div class="cms-subsection"><div class="d-flex align-items-center justify-content-between"><h3 class="mb-0">{{ $mode==='feature' ? 'العناصر' : ($mode==='testimonial' ? 'آراء العملاء' : 'الأسئلة والأجوبة') }}</h3><span class="small text-secondary">اتركي الصف فارغ لو ما تبيهش</span></div><div class="cms-repeater mt-3">
@for($i=0;$i<8;$i++)
    @php($item=$items[$i]??[])
    <div class="cms-repeat-row">
    @if($mode==='feature')
        <div class="row g-2"><div class="col-md-3"><label class="form-label small">الأيقونة</label><select class="form-select" name="items[{{ $i }}][icon]">@foreach($iconOptions as $key=>$label)<option value="{{ $key }}" @selected(($item['icon']??'activity')===$key)>{{ $label }}</option>@endforeach</select></div><div class="col-md-9"><label class="form-label small">العنوان</label><input class="form-control" name="items[{{ $i }}][title]" value="{{ old('items.'.$i.'.title',$item['title']??'') }}"></div><div class="col-12"><label class="form-label small">الوصف</label><textarea class="form-control" rows="2" name="items[{{ $i }}][description]">{{ old('items.'.$i.'.description',$item['description']??'') }}</textarea></div></div>
    @elseif($mode==='testimonial')
        <div class="row g-2"><div class="col-md-6"><label class="form-label small">الاسم</label><input class="form-control" name="items[{{ $i }}][name]" value="{{ old('items.'.$i.'.name',$item['name']??'') }}"></div><div class="col-md-6"><label class="form-label small">المدينة</label><input class="form-control" name="items[{{ $i }}][city]" value="{{ old('items.'.$i.'.city',$item['city']??'') }}"></div><div class="col-12"><label class="form-label small">الرأي</label><textarea class="form-control" rows="2" name="items[{{ $i }}][quote]">{{ old('items.'.$i.'.quote',$item['quote']??'') }}</textarea></div></div>
    @else
        <div class="row g-2"><div class="col-12"><label class="form-label small">السؤال</label><input class="form-control" name="items[{{ $i }}][question]" value="{{ old('items.'.$i.'.question',$item['question']??'') }}"></div><div class="col-12"><label class="form-label small">الإجابة</label><textarea class="form-control" rows="2" name="items[{{ $i }}][answer]">{{ old('items.'.$i.'.answer',$item['answer']??'') }}</textarea></div></div>
    @endif
    </div>
@endfor
</div></div>