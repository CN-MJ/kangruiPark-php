@extends('admin.layout')

@section('title', '登录')

@section('content')
<div class="login-wrap">
    <div class="login-card">
        <h1>赣南健康城</h1>
        <p class="lead">园区 Web 管理端 · 仅 park_role=admin</p>

        @if ($errors->any())
            <div class="flash err">{{ $errors->first() }}</div>
        @endif

        <form method="post" action="{{ route('admin.login.submit') }}">
            @csrf
            <div class="field">
                <label for="phone">手机号</label>
                <input id="phone" type="text" name="phone" value="{{ old('phone') }}" autocomplete="username" required>
            </div>
            <div class="field">
                <label for="password">密码</label>
                <input id="password" type="password" name="password" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn">登录</button>
        </form>
        <p class="lead" style="margin-top:1rem;margin-bottom:0;font-size:.8rem">
            示例账号见 README（如 <code>13800000001</code>）。小程序工作台账号体系共用。
        </p>
    </div>
</div>
@endsection
