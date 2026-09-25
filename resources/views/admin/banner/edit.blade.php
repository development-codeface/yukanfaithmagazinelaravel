@extends('layouts.admin')
@section('content')
    <div class="card">
        <div class="card-header">
            <p><i class="fi fi-br-edit mr_15_icc"></i>
                  {{ __('Edit') }} Banner
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.banners.update',$banner->id) }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" value="{{ $banner->id }}" value="POST">
                <div class="form-group">
                    <label class="required" for="name">{{ trans('cruds.banner.fields.image') }}</label>
                    <input class="form-control {{ $errors->has('image') ? 'is-invalid' : '' }}" type="file"
                        name="image" id="image" value="{{ old('image', '') }}">
                    @if ($errors->has('image'))
                        <div class="invalid-feedback">
                            {{ $errors->first('image') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.banner.fields.name_helper') }}</span>
                </div>

                <div class="form-group">
                    <button class=" btn btn-success min-w-200 " type="submit">
                        {{ trans('global.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
