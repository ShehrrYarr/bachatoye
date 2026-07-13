{{-- Shop filter dropdown for admin filter forms. Expects $shops (may be empty). --}}
@if(($shops ?? collect())->count())
<select name="shop" class="form-select text-sm" {{ ($autoSubmit ?? false) ? 'onchange=this.form.submit()' : '' }}>
    <option value="">All Shops</option>
    <option value="main" {{ request('shop') === 'main' ? 'selected' : '' }}>Main Shop</option>
    @foreach($shops as $shopOption)
    <option value="{{ $shopOption->id }}" {{ request('shop') == $shopOption->id ? 'selected' : '' }}>{{ $shopOption->name }}</option>
    @endforeach
</select>
@endif
