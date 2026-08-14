<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>短链接生成器 - 登录</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .login-box {
      background: #fff;
      border-radius: 20px;
      padding: 48px 40px;
      width: 400px;
      max-width: 92vw;
      box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    }
    .login-box .logo { text-align: center; font-size: 48px; margin-bottom: 8px; }
    .login-box h1 {
      text-align: center;
      font-size: 22px;
      color: #1a202c;
      margin-bottom: 4px;
    }
    .login-box .subtitle {
      text-align: center;
      font-size: 13px;
      color: #a0aec0;
      margin-bottom: 32px;
    }
    .form-group { margin-bottom: 18px; }
    .form-group label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: #4a5568;
      margin-bottom: 6px;
    }
    .form-group input {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid #e2e8f0;
      border-radius: 10px;
      font-size: 15px;
      outline: none;
      transition: border-color 0.2s;
    }
    .form-group input:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
    .btn-login {
      width: 100%;
      padding: 13px;
      margin-top: 8px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: #fff;
      border: none;
      border-radius: 10px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }
    .btn-login:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(102,126,234,0.35); }
    .btn-login:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
    .error-msg {
      background: #fff5f5;
      color: #c53030;
      border: 1px solid #fed7d7;
      border-radius: 8px;
      padding: 10px 14px;
      font-size: 13px;
      margin-bottom: 16px;
      display: none;
    }
    .error-msg.show { display: block; }
    .spinner {
      display: inline-block;
      width: 16px; height: 16px;
      border: 2px solid rgba(255,255,255,0.3);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin 0.6s linear infinite;
      vertical-align: middle;
      margin-right: 6px;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
  </style>
</head>
<body>
  <div class="login-box">
    <div class="logo">🔗</div>
    <h1>短链接生成器</h1>
    <p class="subtitle">管理员登录后可创建和管理短链接</p>

    <div class="error-msg" id="errorMsg"></div>

    <form id="loginForm">
      <div class="form-group">
        <label for="username">用户名</label>
        <input type="text" id="username" placeholder="请输入用户名" required autofocus>
      </div>
      <div class="form-group">
        <label for="password">密码</label>
        <input type="password" id="password" placeholder="请输入密码" required>
      </div>
      <button type="submit" class="btn-login" id="loginBtn">登 录</button>
    </form>
  </div>

  <script>
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = document.getElementById('loginBtn');
      const errDiv = document.getElementById('errorMsg');
      errDiv.classList.remove('show');
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner"></span>登录中...';

      try {
        const resp = await fetch('/api/login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            username: document.getElementById('username').value.trim(),
            password: document.getElementById('password').value,
          }),
        });
        const data = await resp.json();
        if (resp.ok && data.success) {
          window.location.href = '/';
        } else {
          errDiv.textContent = data.error || '登录失败';
          errDiv.classList.add('show');
        }
      } catch {
        errDiv.textContent = '网络错误，请稍后重试';
        errDiv.classList.add('show');
      } finally {
        btn.disabled = false;
        btn.innerHTML = '登 录';
      }
    });

    document.getElementById('password').addEventListener('keydown', (e) => {
      if (e.key === 'Enter') document.getElementById('loginForm').dispatchEvent(new Event('submit'));
    });
  </script>
</body>
</html>
