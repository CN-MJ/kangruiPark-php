@extends('admin.layout')

@section('title', '用户与岗位')

@section('content')
<h1>用户与岗位</h1>
<p class="lead">调整角色 / 园区岗位 / 超管入口权限；改岗或改密会吊销对方小程序会话。</p>

<div class="panel">
    <form class="filters" method="get" action="{{ route('admin.users.index') }}">
        <label>
            关键词
            <input type="text" name="keyword" value="{{ $filters['keyword'] }}" placeholder="手机号 / 姓名">
        </label>
        <label>
            角色
            <select name="role">
                <option value="">全部</option>
                @foreach (['visitor' => '访客', 'merchant' => '商户', 'park' => '园区'] as $k => $t)
                    <option value="{{ $k }}" @selected($filters['role'] === $k)>{{ $t }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="btn btn-sm">筛选</button>
    </form>

    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>账号</th>
            <th>角色 / 岗位</th>
            <th>超管入口</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($users as $user)
            <tr>
                <td>{{ $user->id }}</td>
                <td>
                    <div>{{ $user->phone }}</div>
                    <div class="muted">{{ $user->name ?: '—' }}</div>
                </td>
                <td>
                    <span class="tag">{{ $user->role }}</span>
                    @if ($user->role === 'park')
                        <div class="muted" style="margin-top:.25rem">
                            {{ $parkRoles[$user->park_role] ?? ($user->park_role ?: 'admin') }}
                        </div>
                    @endif
                </td>
                <td>{{ $user->can_escalate ? '是' : '否' }}</td>
                <td>
                    <form method="post" action="{{ route('admin.users.update', $user) }}" class="row-form">
                        @csrf
                        @method('PUT')
                        <input type="text" name="name" value="{{ $user->name }}" placeholder="姓名" maxlength="50">
                        <select name="role">
                            @foreach (['visitor' => '访客', 'merchant' => '商户', 'park' => '园区'] as $k => $t)
                                <option value="{{ $k }}" @selected($user->role === $k)>{{ $t }}</option>
                            @endforeach
                        </select>
                        <select name="park_role">
                            @foreach ($parkRoles as $k => $t)
                                <option value="{{ $k }}" @selected(($user->park_role ?: 'admin') === $k)>{{ $t }}</option>
                            @endforeach
                        </select>
                        <label style="font-size:.78rem;display:flex;align-items:center;gap:.25rem;color:var(--muted)">
                            <input type="checkbox" name="can_escalate" value="1" @checked($user->can_escalate)>
                            可升超管
                        </label>
                        <input type="password" name="password" placeholder="新密码(可选)" autocomplete="new-password">
                        <button type="submit" class="btn btn-sm">保存</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">暂无用户</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="pager">
        {{ $users->links('admin.partials.pager') }}
    </div>
</div>
@endsection
