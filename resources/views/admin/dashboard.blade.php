@extends('admin.layout')

@section('title', '概览')

@section('content')
<h1>概览</h1>
<p class="lead">轻量管理端：用户岗位与审计只读。业务内容请用小程序园区工作台。</p>

<div class="stats">
    <div class="stat">
        <b>{{ $stats['users_total'] }}</b>
        <span>用户总数</span>
    </div>
    <div class="stat">
        <b>{{ $stats['park_staff'] }}</b>
        <span>园区员工</span>
    </div>
    <div class="stat">
        <b>{{ $stats['enterprises'] }}</b>
        <span>企业库条目</span>
    </div>
</div>

<div class="panel" style="margin-top:1rem">
    <h2 style="font-size:1rem;margin:0 0 .6rem">岗位说明</h2>
    <ul class="muted" style="margin:0;padding-left:1.1rem;font-size:.88rem;line-height:1.7">
        @foreach ($stats['roles'] as $key => $label)
            <li><code>{{ $key }}</code> — {{ $label }}</li>
        @endforeach
    </ul>
</div>
@endsection
