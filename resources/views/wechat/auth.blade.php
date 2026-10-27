<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover" />
  <title>微信授权</title>
  <script src="https://res.wx.qq.com/open/js/jweixin-1.6.0.js"></script>
  <style>
    :root {
      --bg: #f3f6fb;
      --card: #ffffff;
      --text: #0f172a;
      --muted: #64748b;
      --primary: #003791;
      --ok: #16a34a;
      --warn: #d97706;
      --danger: #dc2626;
    }
    * { box-sizing: border-box; }
    html, body {
      margin: 0; min-height: 100%;
      background: var(--bg); color: var(--text);
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang SC", "Microsoft YaHei", sans-serif;
      -webkit-tap-highlight-color: transparent;
    }
    .page {
      min-height: 100vh; min-height: 100dvh;
      display: flex; align-items: center; justify-content: center;
      padding: 32px 20px calc(32px + env(safe-area-inset-bottom));
    }
    .card {
      width: 100%; max-width: 420px; background: var(--card);
      border-radius: 20px; padding: 40px 24px 28px;
      box-shadow: 0 12px 36px rgba(15, 40, 100, 0.08); text-align: center;
    }
    .spinner {
      width: 44px; height: 44px; margin: 0 auto 20px;
      border: 3px solid #e2e8f0; border-top-color: var(--primary);
      border-radius: 50%; animation: spin 0.8s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .icon {
      width: 64px; height: 64px; margin: 0 auto 18px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 34px; font-weight: 700; line-height: 1; color: #fff;
    }
    .icon-ok { background: var(--ok); box-shadow: 0 8px 20px rgba(22, 163, 74, 0.28); }
    .icon-warn { background: var(--warn); box-shadow: 0 8px 20px rgba(217, 119, 6, 0.28); font-size: 30px; }
    .icon-err { background: var(--danger); box-shadow: 0 8px 20px rgba(220, 38, 38, 0.25); }
    .title { margin: 0 0 10px; font-size: 20px; font-weight: 700; line-height: 1.4; }
    .desc { margin: 0; font-size: 15px; color: var(--muted); line-height: 1.65; }
    .desc strong { color: var(--text); font-weight: 600; }
    .hint { margin-top: 12px; font-size: 12px; color: #94a3b8; }
    .qr-wrap {
      margin: 24px auto 12px; width: 200px; height: 200px; padding: 10px;
      background: #fff; border: 1px solid #e8eef5; border-radius: 16px;
      box-shadow: 0 6px 18px rgba(15, 40, 100, 0.06);
    }
    .qr-wrap img { display: block; width: 100%; height: 100%; object-fit: contain; border-radius: 8px; background: #f8fafc; }
    .qr-tip { margin: 0 0 24px; font-size: 14px; color: var(--muted); }
    .btn {
      display: block; width: 100%; height: 48px; border: none; border-radius: 12px;
      background: var(--ok); color: #fff; font-size: 16px; font-weight: 600; text-decoration: none;
      line-height: 48px; text-align: center;
    }
    .toast {
      position: fixed; left: 50%; bottom: calc(48px + env(safe-area-inset-bottom));
      transform: translateX(-50%) translateY(12px); max-width: calc(100vw - 48px);
      padding: 12px 18px; background: rgba(15, 23, 42, 0.92); color: #fff;
      border-radius: 12px; font-size: 14px; line-height: 1.45; opacity: 0;
      pointer-events: none; transition: opacity 0.2s ease, transform 0.2s ease; z-index: 99; text-align: center;
    }
    .toast.is-show { opacity: 1; transform: translateX(-50%) translateY(0); }
  </style>
</head>
<body>
  <div class="page">
    <div class="card">
      @if ($panel === 'redirect' || $panel === 'loading')
        <div class="spinner" aria-hidden="true"></div>
        <p class="title">正在安全验证您的微信身份...</p>
        <p class="desc">请稍候，不要关闭本页</p>
        @if ($bindHint)<p class="hint">{{ $bindHint }}</p>@endif
      @elseif ($panel === 'ok')
        <div class="icon icon-ok" aria-hidden="true">✓</div>
        <p class="title">授权成功！</p>
        <p class="desc">预约状态变更将通过微信实时通知您</p>
        <p class="hint">即将返回小程序…</p>
      @elseif ($panel === 'unsub')
        <div class="icon icon-warn" aria-hidden="true">!</div>
        <p class="title">最后一步：请关注服务号</p>
        <p class="desc">
          系统检测到您尚未关注<strong>【赣州康瑞产业园】</strong>服务号。若不关注，您将无法接收预约确认等关键通知。
        </p>
        <div class="qr-wrap">
          <img src="{{ $qrcodeUrl }}" alt="服务号二维码" />
        </div>
        <p class="qr-tip">👇 长按识别二维码关注</p>
        <a class="btn" href="{{ $recheckUrl }}">我已关注，重新检查</a>
      @else
        <div class="icon icon-err" aria-hidden="true">×</div>
        <p class="title">{{ $errorTitle ?: '授权失败' }}</p>
        <p class="desc">{{ $errorMsg ?: '请稍后重试' }}</p>
        @if ($bindHint)<p class="hint">{{ $bindHint }}</p>@endif
      @endif
    </div>
  </div>
  <div class="toast" id="toast"></div>

  <script>
    (function () {
      var panel = @json($panel);
      var oauthUrl = @json($oauthUrl);

      function toast(msg) {
        var el = document.getElementById('toast');
        if (!el) return;
        el.textContent = msg;
        el.classList.add('is-show');
        setTimeout(function () { el.classList.remove('is-show'); }, 2600);
      }

      function backToMiniProgram() {
        try {
          if (typeof wx !== 'undefined' && wx.miniProgram && typeof wx.miniProgram.navigateBack === 'function') {
            wx.miniProgram.navigateBack({ delta: 1 });
            return;
          }
        } catch (e) {}
        toast('请点击右上角关闭本页，返回小程序');
      }

      if (panel === 'redirect' && oauthUrl) {
        window.location.replace(oauthUrl);
        return;
      }

      if (panel === 'ok') {
        setTimeout(backToMiniProgram, 1800);
      }
    })();
  </script>
</body>
</html>
