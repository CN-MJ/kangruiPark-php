@extends('admin.layout')

@section('title', '审计日志')

@section('content')
<h1>审计日志</h1>
<p class="lead">只读；含 Web 管理端与小程序超管/代管相关操作。</p>

<div class="panel">
    <form class="filters" method="get" action="{{ route('admin.audit-logs.index') }}">
        <label>
            动作
            <input type="text" name="action" value="{{ $filters['action'] }}" placeholder="如 user.update">
        </label>
        <label>
            操作人手机
            <input type="text" name="phone" value="{{ $filters['phone'] }}" placeholder="手机号">
        </label>
        <button type="submit" class="btn btn-sm">筛选</button>
    </form>

    <table>
        <thead>
        <tr>
            <th>时间</th>
            <th>操作人</th>
            <th>动作</th>
            <th>目标</th>
            <th>摘要</th>
            <th>IP</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($logs as $log)
            <tr>
                <td style="white-space:nowrap">{{ $log->created_at }}</td>
                <td>{{ $log->phone ?: ('#'.$log->user_id) }}</td>
                <td><code>{{ $log->action }}</code></td>
                <td class="muted">
                    @if ($log->target_type)
                        {{ $log->target_type }}#{{ $log->target_id }}
                    @else
                        —
                    @endif
                </td>
                <td style="max-width:280px;word-break:break-all;font-size:.8rem" class="muted">
                    @php
                        $p = $log->payload ?? [];
                        $bits = [];
                        if (!empty($p['channel'])) { $bits[] = $p['channel']; }
                        if (!empty($p['via_super'])) { $bits[] = 'via_super'; }
                        if (!empty($p['park_act_as'])) { $bits[] = 'park_act_as'; }
                        if (!empty($p['password_changed'])) { $bits[] = '改密'; }
                        if (!empty($p['changes']) && is_array($p['changes'])) {
                            $bits[] = implode(',', array_keys($p['changes']));
                        } elseif (!empty($p['before']) && !empty($p['after'])) {
                            $bits[] = '有变更';
                        }
                    @endphp
                    {{ $bits ? implode(' · ', $bits) : '—' }}
                </td>
                <td class="muted">{{ $log->ip ?: '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">暂无日志</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="pager">
        {{ $logs->links('admin.partials.pager') }}
    </div>
</div>
@endsection
