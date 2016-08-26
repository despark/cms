@extends('auth.auth')

@section('htmlheader_title')
    Register
@endsection

@section('content')

    <div class="register-box">
        <div class="login-logo">
            <img src="{{ asset('images/logo.png') }}" class="admin-logo" alt="Logo" />
            <h4 class="uppercase">Website Administration</h4>
        </div>

        <div class="register-box-body">
            <p class="login-box-msg">Register a new membership</p>

            <form action="{{ url('/register') }}" method="post" autocomplete="off">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">

                <div class="form-group">
                    <input type="text" class="form-control" placeholder="Full name" name="name" value="{{ old('name') }}"/>
                    @if ($errors->has('name'))
                    <span class="error-message">{{ $errors->first('name') }}</span>
                    @endif
                </div>

                <div class="form-group">
                    <input type="email" class="form-control" placeholder="Email" name="email" value="{{ old('email') }}"/>
                    @if ($errors->has('email'))
                    <span class="error-message">{{ $errors->first('email') }}</span>
                    @endif
                </div>

                <div class="form-group">
                    <input type="password" class="form-control" placeholder="Password" name="password" value="{{ old('password') }}"/>
                    @if ($errors->has('password'))
                    <span class="error-message">{{ $errors->first('password') }}</span>
                    @endif
                </div>

                <div class="form-group">
                    <input type="password" class="form-control" placeholder="Retype password" name="password_confirmation" value="{{ old('password_confirmation') }}"/>
                    @if ($errors->has('password_confirmation'))
                    <span class="error-message">{{ $errors->first('password_confirmation') }}</span>
                    @endif
                </div>

                <div class="row">
                    <div class="col-xs-12">
                        <button type="submit" class="btn btn-primary btn-block btn-flat uppercase">Register</button>
                    </div><!-- /.col -->
                </div>
            </form>

            <div class="login-link">
                <a href="{{ url('/login') }}" class="text-center">I already have a membership</a>
            </div>
        </div><!-- /.form-box -->
    </div><!-- /.register-box -->

@endsection
