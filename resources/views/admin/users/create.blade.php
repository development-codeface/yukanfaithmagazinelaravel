@extends('layouts.admin')
@section('content')

<div class="card w_80">
    <div class="card-header"><p><i class="fi fi-br-edit mr_15_icc"></i>
        {{ trans('global.create') }} {{ trans('cruds.user.title_singular') }} </p>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route("admin.users.store") }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="required" for="name">{{ trans('cruds.user.fields.name') }}</label>
                <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text" name="name" id="name" value="{{ old('name', '') }}" required>
                @if($errors->has('name'))
                    <div class="invalid-feedback">
                        {{ $errors->first('name') }}
                    </div>
                @endif
                <span class="help-block">{{ trans('cruds.user.fields.name_helper') }}</span>
            </div>
            <div class="form-group">
                <label class="required" for="email">{{ trans('cruds.user.fields.email') }}</label>
                <input class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" type="email" name="email" id="email" value="{{ old('email') }}" required>
                @if($errors->has('email'))
                    <div class="invalid-feedback">
                        {{ $errors->first('email') }}
                    </div>
                @endif
                <span class="help-block">{{ trans('cruds.user.fields.email_helper') }}</span>
            </div>
            <div class="form-group">
                <label class="required" for="password">{{ trans('cruds.user.fields.password') }}</label>
                <input class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" type="password" name="password" id="password" required>
                @if($errors->has('password'))
                    <div class="invalid-feedback">
                        {{ $errors->first('password') }}
                    </div>
                @endif
                <span class="help-block">{{ trans('cruds.user.fields.password_helper') }}</span>
            </div>

            <div class="form-group">
            <label class="required" for="roles">
                {{ trans('cruds.user.fields.roles') }}
            </label>

            <select class="form-control select2 {{ $errors->has('roles') ? 'is-invalid' : '' }}"
                    name="roles"
                    id="roles"
                    required>
                <option value="">{{ __('Select role') }}</option>

                @foreach($roles as $id => $role)
                    <option value="{{ $id }}"
                        {{ (string) old('roles') === (string) $id ? 'selected' : '' }}>
                        {{ $role }}
                    </option>
                @endforeach
            </select>

            @if($errors->has('roles'))
                <div class="invalid-feedback">
                    {{ $errors->first('roles') }}
                </div>
            @endif
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
