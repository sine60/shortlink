<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>短链接管理 - 控制台</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif;
      background: #f7fafc;
      min-height: 100vh;
      color: #2d3748;
    }
    .header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 14px 32px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .header-left { display: flex; align-items: center; gap: 12px; }
    .header-logo { font-size: 22px; }
    .header h1 { font-size: 18px; color: #fff; font-weight: 600; }
    .header-right { display: flex; align-items: center; gap: 12px; }
    .user-info { color: rgba(255,255,255,0.9); font-size: 14px; }
    .admin-badge { background: #f6e05e; color: #975a16; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; }
    .btn-header { padding: 6px 14px; background: rgba(255,255,255,0.2); color: #fff; border: 1px solid rgba(255,255,255,0.3); border-radius: 6px; cursor: pointer; font-size: 13px; transition: all 0.2s; }
    .btn-header:hover { background: rgba(255,255,255,0.3); }
    .main { max-width: 1200px; margin: 0 auto; padding: 28px 24px; }
    .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 28px; }
    .stat-card { background: #fff; border-radius: 12px; padding: 18px 22px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
    .stat-card .stat-number { font-size: 26px; font-weight: 700; color: #667eea; }
    .stat-card .stat-label { font-size: 13px; color: #a0aec0; margin-top: 3px; }
    .section { background: #fff; border-radius: 12px; padding: 28px; margin-bottom: 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
    .section-title { font-size: 16px; font-weight: 700; color: #1a202c; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #edf2f7; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    .form-row.full { grid-template-columns: 1fr; }
    .form-row.triple { grid-template-columns: 1fr 1fr 1fr; }
    .form-group label { display: block; font-size: 13px; font-weight: 600; color: #4a5568; margin-bottom: 5px; }
    .form-group input, .form-group select {
      width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 8px;
      font-size: 14px; transition: all 0.2s; outline: none; color: #2d3748; background: #fff;
    }
    .form-group input:focus, .form-group select:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
    .form-group .hint { font-size: 11px; color: #a0aec0; margin-top: 4px; }
    .btn { padding: 10px 22px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; }
    .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(102,126,234,0.35); }
    .btn-sm { padding: 5px 12px; font-size: 12px; }
    .dt-display { padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; color: #4a5568; cursor: pointer; background: #fff; transition: border-color 0.2s; user-select: none; }
    .dt-display:hover { border-color: #667eea; }
    .dt-display.empty { color: #a0aec0; }
    .dt-popup { position: absolute; z-index: 1000; width: 280px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.12); padding: 12px; font-size: 13px; color: #2d3748; }
    .dt-cal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
    .dt-title { font-weight: 600; }
    .dt-nav { border: none; background: #f7fafc; width: 28px; height: 28px; border-radius: 6px; cursor: pointer; font-size: 16px; color: #667eea; }
    .dt-nav:hover { background: #eef0ff; }
    .dt-nav-year { opacity: 0.6; font-size: 13px; }
    .dt-nav-year:hover { opacity: 1; }
    .dt-weekdays, .dt-days { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; text-align: center; }
    .dt-weekdays span { color: #a0aec0; font-size: 11px; padding: 4px 0; }
    .dt-day { padding: 6px 0; border-radius: 6px; cursor: pointer; }
    .dt-day:hover { background: #eef0ff; }
    .dt-day.muted { color: #cbd5e0; }
    .dt-day.selected { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; }
    .dt-time { display: flex; gap: 8px; justify-content: center; margin: 10px 0; align-items: center; }
    .dt-time label { font-size: 12px; color: #718096; display: flex; align-items: center; gap: 4px; }
    .dt-time input { width: 48px; padding: 5px; border: 1px solid #e2e8f0; border-radius: 6px; text-align: center; font-size: 13px; }
    .dt-actions { display: flex; gap: 6px; justify-content: space-between; margin-top: 6px; }
    .dt-actions .btn-sm { flex: 1; text-align: center; justify-content: center; }
    .btn-danger { background: #fff; color: #e53e3e; border: 1px solid #fed7d7; }
    .btn-danger:hover { background: #fff5f5; }
    .btn-outline { background: #fff; color: #667eea; border: 1px solid #667eea; }
    .btn-outline:hover { background: #f0f0ff; }
    .btn-warn { background: #fff; color: #d69e2e; border: 1px solid #fefcbf; }
    .btn-warn:hover { background: #fffff0; }
    .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none !important; }
    .msg { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; display: none; }
    .msg.show { display: block; }
    .msg-error { background: #fff5f5; color: #c53030; border: 1px solid #fed7d7; }
    .msg-success { background: #f0fff4; color: #276749; border: 1px solid #c6f6d5; }
    .tabs { display: flex; gap: 4px; margin-bottom: 24px; border-bottom: 2px solid #edf2f7; }
    .tab { padding: 10px 24px; cursor: pointer; font-size: 14px; font-weight: 600; color: #a0aec0; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; }
    .tab.active { color: #667eea; border-bottom-color: #667eea; }
    .tab:hover { color: #4a5568; }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }
    .link-table, .user-table { width: 100%; border-collapse: collapse; }
    .link-table th, .user-table th { text-align: left; padding: 12px; font-size: 12px; font-weight: 600; color: #a0aec0; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #edf2f7; }
    .link-table td, .user-table td { padding: 14px 12px; font-size: 13px; border-bottom: 1px solid #f7fafc; vertical-align: middle; }
    .link-table tr:hover td, .user-table tr:hover td { background: #f7fafc; }
    .link-url { max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; color: #4a5568; text-decoration: none; }
    .link-url:hover { color: #667eea; }
    .short-code { font-family: 'SF Mono', 'Fira Code', monospace; background: #edf2f7; padding: 3px 8px; border-radius: 4px; font-size: 13px; color: #667eea; font-weight: 600; cursor: pointer; }
    .short-code:hover { background: #e2e8f0; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
    .badge-active { background: #c6f6d5; color: #276749; }
    .badge-expired { background: #fed7d7; color: #c53030; }
    .badge-limit { background: #fefcbf; color: #975a16; }
    .badge-disabled { background: #e2e8f0; color: #4a5568; }
    .badge-permanent { background: #bee3f8; color: #2b6cb0; }
    .visit-count { font-weight: 700; color: #667eea; }
    .visit-max { color: #a0aec0; font-size: 12px; }
    .actions { display: flex; gap: 6px; }
    .creator-tag { background: #edf2f7; color: #4a5568; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
    .remark-cell { max-width: 220px; color: #4a5568; font-size: 13px; word-break: break-word; white-space: pre-wrap; }
    .remark-cell .muted { color: #cbd5e0; }
    .muted { color: #cbd5e0; }
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; display: none; }
    .modal-overlay.show { display: flex; }
    .modal { background: #fff; border-radius: 14px; padding: 32px; width: 520px; max-width: 90vw; box-shadow: 0 20px 60px rgba(0,0,0,0.2); max-height: 85vh; overflow-y: auto; }
    .modal h3 { margin-bottom: 20px; font-size: 18px; color: #1a202c; }
    .modal .btn-row { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
    .empty-state { text-align: center; padding: 48px 20px; color: #a0aec0; }
    .empty-state .empty-icon { font-size: 48px; margin-bottom: 12px; }
    .empty-state p { font-size: 14px; }
    .spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .copy-toast { position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%); background: #1a202c; color: #fff; padding: 10px 24px; border-radius: 8px; font-size: 13px; z-index: 2000; opacity: 0; transition: opacity 0.3s; pointer-events: none; }
    .copy-toast.show { opacity: 1; }
    @media (max-width: 768px) {
      .form-row, .form-row.triple { grid-template-columns: 1fr; }
      .header { padding: 12px 16px; }
      .main { padding: 16px 10px; }
      .link-table, .user-table { display: block; overflow-x: auto; }
      .section { padding: 18px; }
    }
  </style>
</head>
<body>
  <div class="header">
    <div class="header-left"><span class="header-logo">🔗</span><h1>短链接管理</h1></div>
    <div class="header-right">
      <span class="user-info" id="userDisplay"></span>
      <button class="btn-header" onclick="openPasswordModal()">修改密码</button>
      <button class="btn-header" onclick="logout()">退出登录</button>
    </div>
  </div>

  <div class="main">
    <div class="stats" id="statsRow"></div>
    <div class="tabs" id="tabsRow"><div class="tab active" data-tab="links">🔗 短链管理</div></div>

    <div class="tab-panel active" id="panel-links">
      <div class="section">
        <div class="section-title">创建短链接</div>
        <div class="msg msg-error" id="createError"></div>
        <div class="msg msg-success" id="createSuccess"></div>
        <form id="createForm">
          <div class="form-row full">
            <div class="form-group">
              <label for="originalUrl">原始链接 *</label>
              <input type="url" id="originalUrl" placeholder="https://example.com/very/long/url" required>
            </div>
          </div>
          <div class="form-row triple">
            <div class="form-group">
              <label for="customCode">自定义短链（可选）</label>
              <input type="text" id="customCode" placeholder="留空自动生成4位随机码" maxlength="20" pattern="[a-zA-Z0-9_-]{2,20}">
              <div class="hint">字母、数字、下划线、连字符，2-20位</div>
            </div>
            <div class="form-group">
              <label for="expiresAt">有效期</label>
              <input type="hidden" id="expiresAt">
              <div class="dt-display" id="expiresAt_display">点击选择有效期（留空为永久）</div>
              <div class="hint">留空表示永久有效</div>
            </div>
            <div class="form-group">
              <label for="maxVisits">访问次数限制</label>
              <input type="number" id="maxVisits" placeholder="留空表示不限制" min="1">
              <div class="hint">留空表示不限制次数</div>
            </div>
          </div>
          <button type="submit" class="btn btn-primary" id="createBtn">生成短链接</button>
        </form>
      </div>

      <div class="section">
        <div class="section-title">短链接列表</div>
        <div id="linksContainer"><div class="empty-state"><div class="empty-icon">📭</div><p>还没有创建任何短链接</p></div></div>
      </div>
    </div>

    <div class="tab-panel" id="panel-accounts">
      <div class="section">
        <div class="section-title">创建账号</div>
        <div class="msg msg-error" id="createUserError"></div>
        <div class="msg msg-success" id="createUserSuccess"></div>
        <form id="createUserForm">
          <div class="form-row">
            <div class="form-group">
              <label for="newUsername">用户名 *</label>
              <input type="text" id="newUsername" placeholder="字母、数字、下划线、中文" maxlength="20" required>
            </div>
            <div class="form-group">
              <label for="newPassword">初始密码 *</label>
              <input type="text" id="newPassword" placeholder="至少3位" minlength="3" required>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">创建账号</button>
        </form>
      </div>
      <div class="section">
        <div class="section-title">账号列表</div>
        <div id="usersContainer"><div class="empty-state"><div class="empty-icon">👥</div><p>加载中...</p></div></div>
      </div>
    </div>
  </div>

  <!-- Modals -->
  <div class="modal-overlay" id="editModal">
    <div class="modal">
      <h3>编辑短链接</h3>
      <div class="msg msg-error" id="editError"></div>
      <form id="editForm">
        <input type="hidden" id="editId">
        <div class="form-group" style="margin-bottom:14px"><label for="editOriginalUrl">原始链接</label><input type="url" id="editOriginalUrl" required></div>
        <div class="form-group" style="margin-bottom:14px"><label for="editShortCode">短链代码</label><input type="text" id="editShortCode" maxlength="20" pattern="[a-zA-Z0-9_-]{2,20}" required></div>
        <div class="form-group" style="margin-bottom:14px"><label for="editExpiresAt">有效期（留空为永久）</label><input type="hidden" id="editExpiresAt"><div class="dt-display" id="editExpiresAt_display">点击选择有效期（留空为永久）</div></div>
        <div class="form-group" style="margin-bottom:14px"><label for="editMaxVisits">访问次数限制（留空为不限制）</label><input type="number" id="editMaxVisits" min="1"></div>
        <div class="form-group" style="margin-bottom:14px"><label for="editRemark">备注</label><input type="text" id="editRemark" maxlength="200" placeholder="填写用途说明，便于区分短链"></div>
        <div class="form-group" style="margin-bottom:14px;display:none" id="editOwnerGroup"><label for="editOwnerId">所属用户</label><select id="editOwnerId"></select></div>
        <div class="btn-row"><button type="button" class="btn btn-outline" onclick="closeEditModal()">取消</button><button type="submit" class="btn btn-primary">保存修改</button></div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="passwordModal">
    <div class="modal">
      <h3>修改密码</h3>
      <div class="msg msg-error" id="passwordError"></div>
      <div class="msg msg-success" id="passwordSuccess"></div>
      <form id="passwordForm">
        <div class="form-group" style="margin-bottom:14px"><label for="oldPassword">原密码</label><input type="password" id="oldPassword" required></div>
        <div class="form-group" style="margin-bottom:14px"><label for="newPasswordField">新密码</label><input type="password" id="newPasswordField" minlength="3" placeholder="至少3位" required></div>
        <div class="form-group" style="margin-bottom:14px"><label for="confirmPassword">确认新密码</label><input type="password" id="confirmPassword" minlength="3" required></div>
        <div class="btn-row"><button type="button" class="btn btn-outline" onclick="closePasswordModal()">取消</button><button type="submit" class="btn btn-primary">修改密码</button></div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="resetPwdModal">
    <div class="modal">
      <h3>重置密码 - <span id="resetPwdUsername"></span></h3>
      <div class="msg msg-error" id="resetPwdError"></div>
      <form id="resetPwdForm">
        <input type="hidden" id="resetPwdUserId">
        <div class="form-group" style="margin-bottom:14px"><label for="resetNewPassword">新密码</label><input type="text" id="resetNewPassword" minlength="3" placeholder="至少3位" required></div>
        <div class="btn-row"><button type="button" class="btn btn-outline" onclick="closeResetPwdModal()">取消</button><button type="submit" class="btn btn-warn">重置密码</button></div>
      </form>
    </div>
  </div>

  <div class="copy-toast" id="copyToast"></div>

  <script>
    let links = [];
    let users = [];
    let currentUser = null;

    // 日期解析：后端 created_at 由 SQLite CURRENT_TIMESTAMP 生成，是 UTC（无时区标记）；
    // expires_at 已是带 Z 的 UTC ISO。无时区标记的字符串一律按 UTC 解析，
    // 再由 toLocaleString 显示为用户本地时间（北京时间），避免 China 环境下差 8 小时。
    function parseDate(d) {
      if (!d) return null;
      let s = d.includes(' ') ? d.replace(' ', 'T') : d;
      if (!/[zZ]$|[+-]\d{2}:?\d{2}$/.test(s)) s += 'Z';
      const dt = new Date(s);
      return isNaN(dt.getTime()) ? null : dt;
    }
    function toLocalInput(d) {
      const dt = parseDate(d);
      if (!dt) return '';
      const local = new Date(dt.getTime() - dt.getTimezoneOffset() * 60000);
      return local.toISOString().slice(0, 19);
    }
    function fmt(d) {
      const dt = parseDate(d);
      return dt ? dt.toLocaleString('zh-CN', { year:'numeric', month:'2-digit', day:'2-digit', hour:'2-digit', minute:'2-digit' }) : (d || '');
    }
    function escapeHtml(s) {
      return String(s == null ? '' : s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    // ===== 自定义日期时间选择器（秒级）=====
    // 底部按钮：清除 | 00:00:00 | 23:59:59 | 今天
    let dtActive = null;   // 当前编辑的隐藏 input 元素
    let dtState = null;    // {y,m,d,h,min,s} 当前选择
    let dtPopupBuilt = false;

    function pad2(n) { return String(n).padStart(2, '0'); }
    function nowState() {
      const n = new Date();
      return { y: n.getFullYear(), m: n.getMonth(), d: n.getDate(), h: n.getHours(), min: n.getMinutes(), s: n.getSeconds() };
    }

    function buildDTPopup() {
      if (dtPopupBuilt) return;
      const p = document.createElement('div');
      p.id = 'dtPopup'; p.className = 'dt-popup'; p.style.display = 'none';
      p.innerHTML =
        '<div class="dt-cal">' +
          '<div class="dt-cal-head"><button type="button" class="dt-nav dt-nav-year" data-nav="prevYear">&lt;&lt;</button>' +
          '<button type="button" class="dt-nav" data-nav="prev">‹</button>' +
          '<span class="dt-title"></span>' +
          '<button type="button" class="dt-nav" data-nav="next">›</button>' +
          '<button type="button" class="dt-nav dt-nav-year" data-nav="nextYear">&gt;&gt;</button></div>' +
          '<div class="dt-weekdays"><span>日</span><span>一</span><span>二</span><span>三</span><span>四</span><span>五</span><span>六</span></div>' +
          '<div class="dt-days"></div>' +
        '</div>' +
        '<div class="dt-time">' +
          '<label>时<input type="number" class="dt-h" min="0" max="23"></label>' +
          '<label>分<input type="number" class="dt-min" min="0" max="59"></label>' +
          '<label>秒<input type="number" class="dt-s" min="0" max="59"></label>' +
        '</div>' +
        '<div class="dt-actions">' +
          '<button type="button" class="btn btn-outline btn-sm" data-act="clear">清除</button>' +
          '<button type="button" class="btn btn-outline btn-sm" data-act="start">00:00:00</button>' +
          '<button type="button" class="btn btn-outline btn-sm" data-act="end">23:59:59</button>' +
          '<button type="button" class="btn btn-outline btn-sm" data-act="today">今天</button>' +
        '</div>';
      document.body.appendChild(p);

      p.querySelector('[data-nav="prev"]').addEventListener('click', () => { dtState.m--; if (dtState.m < 0) { dtState.m = 11; dtState.y--; } renderDTCal(); });
      p.querySelector('[data-nav="next"]').addEventListener('click', () => { dtState.m++; if (dtState.m > 11) { dtState.m = 0; dtState.y++; } renderDTCal(); });
      p.querySelector('[data-nav="prevYear"]').addEventListener('click', () => { dtState.y--; renderDTCal(); });
      p.querySelector('[data-nav="nextYear"]').addEventListener('click', () => { dtState.y++; renderDTCal(); });

      ['h', 'min', 's'].forEach(k => {
        const inp = p.querySelector('.dt-' + k);
        inp.addEventListener('input', () => {
          let v = parseInt(inp.value, 10);
          if (isNaN(v)) return;
          const max = k === 'h' ? 23 : 59;
          if (v < 0) v = 0; if (v > max) v = max;
          dtState[k] = v; commitDT();
        });
      });

      p.querySelectorAll('.dt-actions button').forEach(b => b.addEventListener('click', () => dtAction(b.dataset.act)));

      document.addEventListener('click', (e) => {
        if (p.style.display === 'none') return;
        if (p.contains(e.target)) return;
        closeDTPicker();
      });

      dtPopupBuilt = true;
    }

    function renderDTCal() {
      const p = document.getElementById('dtPopup');
      p.querySelector('.dt-title').textContent = dtState.y + '年 ' + (dtState.m + 1) + '月';
      const daysEl = p.querySelector('.dt-days');
      daysEl.innerHTML = '';
      const first = new Date(dtState.y, dtState.m, 1).getDay();
      const daysInMonth = new Date(dtState.y, dtState.m + 1, 0).getDate();
      const prevDays = new Date(dtState.y, dtState.m, 0).getDate();
      for (let i = 0; i < first; i++) {
        const d = document.createElement('div');
        d.className = 'dt-day muted'; d.textContent = prevDays - first + 1 + i;
        daysEl.appendChild(d);
      }
      for (let d = 1; d <= daysInMonth; d++) {
        const el = document.createElement('div');
        el.className = 'dt-day'; el.textContent = d;
        if (dtState.d === d) el.classList.add('selected');
        el.addEventListener('click', () => { dtState.d = d; renderDTCal(); commitDT(); });
        daysEl.appendChild(el);
      }
      const tail = (7 - ((first + daysInMonth) % 7)) % 7;
      for (let i = 1; i <= tail; i++) {
        const d = document.createElement('div');
        d.className = 'dt-day muted'; d.textContent = i;
        daysEl.appendChild(d);
      }
    }

    function syncTimeInputs() {
      const p = document.getElementById('dtPopup');
      p.querySelector('.dt-h').value = pad2(dtState.h);
      p.querySelector('.dt-min').value = pad2(dtState.min);
      p.querySelector('.dt-s').value = pad2(dtState.s);
    }

    function commitDT() {
      if (!dtActive || !dtState || dtState.d == null) return;
      dtActive.value = dtState.y + '-' + pad2(dtState.m + 1) + '-' + pad2(dtState.d) + 'T' +
                       pad2(dtState.h) + ':' + pad2(dtState.min) + ':' + pad2(dtState.s);
      refreshDTDisplay(dtActive.id);
    }

    function dtAction(act) {
      const now = new Date();
      if (act === 'clear') { dtActive.value = ''; refreshDTDisplay(dtActive.id); closeDTPicker(); return; }
      if (act === 'start') {
        if (dtState.d == null) { dtState.y = now.getFullYear(); dtState.m = now.getMonth(); dtState.d = now.getDate(); }
        dtState.h = 0; dtState.min = 0; dtState.s = 0;
      } else if (act === 'end') {
        if (dtState.d == null) { dtState.y = now.getFullYear(); dtState.m = now.getMonth(); dtState.d = now.getDate(); }
        dtState.h = 23; dtState.min = 59; dtState.s = 59;
      } else if (act === 'today') {
        dtState.y = now.getFullYear(); dtState.m = now.getMonth(); dtState.d = now.getDate();
        dtState.h = now.getHours(); dtState.min = now.getMinutes(); dtState.s = now.getSeconds();
      }
      renderDTCal(); syncTimeInputs(); commitDT(); closeDTPicker();
    }

    function openDTPicker(inputId) {
      const input = document.getElementById(inputId);
      if (!input) return;
      dtActive = input;
      buildDTPopup();
      const p = document.getElementById('dtPopup');
      if (input.value && /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})$/.test(input.value)) {
        const m = input.value.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})$/);
        dtState = { y: +m[1], m: +m[2] - 1, d: +m[3], h: +m[4], min: +m[5], s: +m[6] };
      } else {
        dtState = nowState(); // 没选时默认当前时间（精确到秒）
      }
      renderDTCal(); syncTimeInputs();
      const disp = document.getElementById(inputId + '_display');
      const r = disp.getBoundingClientRect();
      p.style.display = 'block';
      let left = r.left + window.scrollX;
      let top = r.bottom + window.scrollY + 6;
      const maxLeft = window.scrollX + document.documentElement.clientWidth - 290;
      if (left > maxLeft) left = maxLeft;
      p.style.top = top + 'px'; p.style.left = left + 'px';
    }

    function closeDTPicker() {
      const p = document.getElementById('dtPopup');
      if (p) p.style.display = 'none';
      dtActive = null;
    }

    function refreshDTDisplay(inputId) {
      const input = document.getElementById(inputId);
      const disp = document.getElementById(inputId + '_display');
      if (!input || !disp) return;
      if (input.value) {
        disp.textContent = input.value.replace('T', ' ') + '（点击修改）';
        disp.classList.remove('empty');
      } else {
        disp.textContent = '点击选择有效期（留空为永久）';
        disp.classList.add('empty');
      }
    }

    function initDateTimePicker(inputId) {
      const input = document.getElementById(inputId);
      if (!input) return;
      refreshDTDisplay(inputId);
      const disp = document.getElementById(inputId + '_display');
      if (disp) disp.addEventListener('click', (e) => { e.stopPropagation(); openDTPicker(inputId); });
    }

    async function init() {
      initDateTimePicker('expiresAt');
      initDateTimePicker('editExpiresAt');
      try {
        const resp = await fetch('/api/me');
        if (!resp.ok) { window.location.href = '/'; return; }
        currentUser = await resp.json();
        const badge = currentUser.isAdmin ? ' <span class="admin-badge">管理员</span>' : '';
        document.getElementById('userDisplay').innerHTML = '👤 ' + currentUser.username + badge;

        if (currentUser.isAdmin) {
          document.getElementById('tabsRow').innerHTML += '<div class="tab" data-tab="accounts">👥 账号管理</div>';
          document.getElementById('editOwnerGroup').style.display = 'block';
        }

        await loadLinks();
        if (currentUser.isAdmin) await loadUsers();
      } catch { window.location.href = '/'; }
    }

    async function loadLinks() {
      try {
        const resp = await fetch('/api/links');
        links = await resp.json();
        renderLinks();
        updateStats();
      } catch (err) { console.error('加载链接失败', err); }
    }

    function updateStats() {
      const now = new Date();
      const active = links.filter(l => {
        if (l.disabled) return false;
        if (l.expires_at && new Date(l.expires_at) < now) return false;
        if (l.max_visits && l.visit_count >= l.max_visits) return false;
        return true;
      });
      const totalVisits = links.reduce((sum, l) => sum + l.visit_count, 0);
      document.getElementById('statsRow').innerHTML =
        '<div class="stat-card"><div class="stat-number">' + links.length + '</div><div class="stat-label">总短链数</div></div>' +
        '<div class="stat-card"><div class="stat-number">' + active.length + '</div><div class="stat-label">有效短链</div></div>' +
        '<div class="stat-card"><div class="stat-number">' + totalVisits + '</div><div class="stat-label">总访问量</div></div>' +
        (currentUser.isAdmin ? '<div class="stat-card"><div class="stat-number">' + users.length + '</div><div class="stat-label">用户数</div></div>' : '');
    }

    function getLinkStatus(link) {
      if (link.disabled) return 'disabled';
      const now = new Date();
      if (link.expires_at && new Date(link.expires_at) < now) return 'expired';
      if (link.max_visits && link.visit_count >= link.max_visits) return 'limit';
      return 'active';
    }

    function renderLinks() {
      const container = document.getElementById('linksContainer');
      if (links.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="empty-icon">📭</div><p>还没有创建任何短链接</p></div>';
        return;
      }
      const formatDate = d => fmt(d);
      const creatorCol = currentUser.isAdmin ? '<th>创建者</th>' : '';
      container.innerHTML = '<table class="link-table"><thead><tr><th>短链代码</th><th>原始链接</th><th>状态</th><th>有效期</th><th>访问统计</th>' + creatorCol + '<th>创建时间</th><th>备注</th><th>操作</th></tr></thead><tbody>' +
        links.map(l => {
          const status = getLinkStatus(l);
          const statusBadge = status === 'active' ? '<span class="badge badge-active">有效</span>' : status === 'expired' ? '<span class="badge badge-expired">已过期</span>' : status === 'limit' ? '<span class="badge badge-limit">已达上限</span>' : '<span class="badge badge-disabled">失效</span>';
          const expiryText = l.expires_at ? formatDate(l.expires_at) : '<span class="badge badge-permanent">永久</span>';
          const visitInfo = l.max_visits ? '<span class="visit-count">' + l.visit_count + '</span> <span class="visit-max">/ ' + l.max_visits + '</span>' : '<span class="visit-count">' + l.visit_count + '</span>';
          const creatorHtml = currentUser.isAdmin ? '<td><span class="creator-tag">' + (l.created_by || '未知') + '</span></td>' : '';
          return '<tr>' +
            '<td><span class="short-code" onclick="copyShortUrl(\'' + l.short_url + '\')" title="点击复制">' + l.short_code + '</span></td>' +
            '<td><a href="' + l.short_url + '" target="_blank" class="link-url" title="' + l.original_url + '">' + l.original_url + '</a></td>' +
            '<td>' + statusBadge + '</td>' +
            '<td style="font-size:12px">' + expiryText + '</td>' +
            '<td>' + visitInfo + '</td>' +
            creatorCol +
            '<td style="font-size:12px;color:#a0aec0">' + formatDate(l.created_at) + '</td>' +
            '<td class="remark-cell">' + (l.remark ? escapeHtml(l.remark) : '<span class="muted">—</span>') + '</td>' +
            '<td><div class="actions">' + (status === 'disabled' ? '' : '<button class="btn btn-warn btn-sm" onclick="disableLink(' + l.id + ')">失效</button>') + '<button class="btn btn-outline btn-sm" onclick="openEditModal(' + l.id + ')">编辑</button><button class="btn btn-danger btn-sm" onclick="deleteLink(' + l.id + ')">删除</button></div></td></tr>';
        }).join('') + '</tbody></table>';
    }

    async function copyShortUrl(url) {
      try { await navigator.clipboard.writeText(url); showToast('已复制: ' + url); } catch { showToast('复制失败'); }
    }

    function showToast(msg) {
      const toast = document.getElementById('copyToast');
      toast.textContent = msg;
      toast.classList.add('show');
      setTimeout(() => toast.classList.remove('show'), 2000);
    }

    document.getElementById('createForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = document.getElementById('createBtn');
      const errDiv = document.getElementById('createError');
      const succDiv = document.getElementById('createSuccess');
      errDiv.classList.remove('show'); succDiv.classList.remove('show');
      btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>生成中...';
      try {
        const resp = await fetch('/api/links', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            original_url: document.getElementById('originalUrl').value.trim(),
            custom_code: document.getElementById('customCode').value.trim() || undefined,
            expires_at: document.getElementById('expiresAt').value ? new Date(document.getElementById('expiresAt').value).toISOString() : undefined,
            max_visits: document.getElementById('maxVisits').value ? parseInt(document.getElementById('maxVisits').value) : undefined,
          }),
        });
        const data = await resp.json();
        if (resp.ok && data.success) {
          succDiv.textContent = '短链接创建成功: ' + data.link.short_url;
          succDiv.classList.add('show');
          document.getElementById('createForm').reset();
          refreshDTDisplay('expiresAt');
          await loadLinks();
        } else {
          errDiv.textContent = data.error || '创建失败';
          errDiv.classList.add('show');
        }
      } catch { errDiv.textContent = '网络错误'; errDiv.classList.add('show'); }
      finally { btn.disabled = false; btn.innerHTML = '生成短链接'; }
    });

    async function deleteLink(id) {
      if (!confirm('确定要删除这条短链接吗？')) return;
      try { const resp = await fetch('/api/links/' + id, { method: 'DELETE' }); if (resp.ok) { await loadLinks(); showToast('已删除'); } }
      catch { showToast('删除失败'); }
    }

    async function disableLink(id) {
      if (!confirm('确定要让这条短链接失效吗？失效后需编辑并保存才能重新生效。')) return;
      try {
        const resp = await fetch('/api/links/' + id, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ disabled: 1 }),
        });
        if (resp.ok) { await loadLinks(); showToast('该短链接已失效'); }
        else { showToast('操作失败'); }
      } catch { showToast('操作失败'); }
    }

    async function openEditModal(id) {
      const link = links.find(l => l.id === id);
      if (!link) return;
      document.getElementById('editId').value = link.id;
      document.getElementById('editOriginalUrl').value = link.original_url;
      document.getElementById('editShortCode').value = link.short_code;
      document.getElementById('editExpiresAt').value = link.expires_at ? toLocalInput(link.expires_at) : '';
      refreshDTDisplay('editExpiresAt');
      document.getElementById('editMaxVisits').value = link.max_visits || '';
      document.getElementById('editRemark').value = link.remark || '';
      document.getElementById('editError').classList.remove('show');
      if (currentUser.isAdmin && users.length > 0) {
        const select = document.getElementById('editOwnerId');
        select.innerHTML = users.map(u => '<option value="' + u.id + '"' + (u.id === link.user_id ? ' selected' : '') + '>' + u.username + '</option>').join('');
      }
      document.getElementById('editModal').classList.add('show');
    }

    function closeEditModal() { document.getElementById('editModal').classList.remove('show'); }

    document.getElementById('editForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const errDiv = document.getElementById('editError');
      errDiv.classList.remove('show');
      const id = document.getElementById('editId').value;
      try {
        const resp = await fetch('/api/links/' + id, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            original_url: document.getElementById('editOriginalUrl').value.trim(),
            short_code: document.getElementById('editShortCode').value.trim(),
            expires_at: document.getElementById('editExpiresAt').value ? new Date(document.getElementById('editExpiresAt').value).toISOString() : null,
            max_visits: document.getElementById('editMaxVisits').value ? parseInt(document.getElementById('editMaxVisits').value) : null,
            disabled: 0,
            remark: document.getElementById('editRemark').value.trim(),
          }),
        });
        if (!resp.ok) { const data = await resp.json(); errDiv.textContent = data.error || '修改失败'; errDiv.classList.add('show'); return; }
        if (currentUser.isAdmin) {
          const newOwner = document.getElementById('editOwnerId').value;
          const link = links.find(l => l.id == id);
          if (link && link.user_id != newOwner) {
            const ownerResp = await fetch('/api/links/' + id + '/owner', { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ user_id: parseInt(newOwner) }) });
            if (!ownerResp.ok) { errDiv.textContent = '所有权转移失败'; errDiv.classList.add('show'); return; }
          }
        }
        closeEditModal(); await loadLinks(); showToast('修改已保存');
      } catch { errDiv.textContent = '网络错误'; errDiv.classList.add('show'); }
    });

    // ==================== Users ====================
    async function loadUsers() {
      try { const resp = await fetch('/api/users'); users = await resp.json(); renderUsers(); updateStats(); }
      catch { console.error('加载用户失败'); }
    }

    function renderUsers() {
      const container = document.getElementById('usersContainer');
      if (users.length === 0) { container.innerHTML = '<div class="empty-state"><div class="empty-icon">👥</div><p>暂无用户</p></div>'; return; }
      const formatDate = d => fmt(d);
      container.innerHTML = '<table class="user-table"><thead><tr><th>用户名</th><th>角色</th><th>短链数</th><th>创建时间</th><th>操作</th></tr></thead><tbody>' +
        users.map(u => {
          const role = u.id === 1 ? '<span class="badge badge-active">管理员</span>' : '<span class="badge badge-permanent">普通用户</span>';
          const canDelete = u.id !== 1;
          return '<tr><td><strong>' + u.username + '</strong></td><td>' + role + '</td><td>' + (u.link_count || 0) + '</td><td style="font-size:12px;color:#a0aec0">' + formatDate(u.created_at) + '</td><td><div class="actions"><button class="btn btn-warn btn-sm" onclick="openResetPwdModal(' + u.id + ',\'' + u.username + '\')">重置密码</button>' + (canDelete ? '<button class="btn btn-danger btn-sm" onclick="deleteUser(' + u.id + ',\'' + u.username + '\')">删除</button>' : '') + '</div></td></tr>';
        }).join('') + '</tbody></table>';
    }

    document.getElementById('createUserForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const errDiv = document.getElementById('createUserError');
      const succDiv = document.getElementById('createUserSuccess');
      errDiv.classList.remove('show'); succDiv.classList.remove('show');
      try {
        const resp = await fetch('/api/users', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ username: document.getElementById('newUsername').value.trim(), password: document.getElementById('newPassword').value }) });
        const data = await resp.json();
        if (resp.ok && data.success) { succDiv.textContent = '账号创建成功: ' + data.username; succDiv.classList.add('show'); document.getElementById('createUserForm').reset(); await loadUsers(); }
        else { errDiv.textContent = data.error || '创建失败'; errDiv.classList.add('show'); }
      } catch { errDiv.textContent = '网络错误'; errDiv.classList.add('show'); }
    });

    async function deleteUser(id, username) {
      if (!confirm('确定要删除用户 "' + username + '" 吗？\n\n该用户的所有短链将转移给管理员。')) return;
      try { const resp = await fetch('/api/users/' + id, { method: 'DELETE' }); if (resp.ok) { await loadUsers(); await loadLinks(); showToast('用户 "' + username + '" 已删除'); } }
      catch { showToast('删除失败'); }
    }

    function openResetPwdModal(userId, username) {
      document.getElementById('resetPwdUserId').value = userId;
      document.getElementById('resetPwdUsername').textContent = username;
      document.getElementById('resetPwdError').classList.remove('show');
      document.getElementById('resetNewPassword').value = '';
      document.getElementById('resetPwdModal').classList.add('show');
    }
    function closeResetPwdModal() { document.getElementById('resetPwdModal').classList.remove('show'); }

    document.getElementById('resetPwdForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const errDiv = document.getElementById('resetPwdError');
      errDiv.classList.remove('show');
      try {
        const resp = await fetch('/api/users/' + document.getElementById('resetPwdUserId').value + '/reset-password', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ newPassword: document.getElementById('resetNewPassword').value }) });
        const data = await resp.json();
        if (resp.ok) { closeResetPwdModal(); showToast('密码已重置'); }
        else { errDiv.textContent = data.error || '重置失败'; errDiv.classList.add('show'); }
      } catch { errDiv.textContent = '网络错误'; errDiv.classList.add('show'); }
    });

    // ==================== Password ====================
    function openPasswordModal() {
      document.getElementById('passwordError').classList.remove('show');
      document.getElementById('passwordSuccess').classList.remove('show');
      document.getElementById('passwordForm').reset();
      document.getElementById('passwordModal').classList.add('show');
    }
    function closePasswordModal() { document.getElementById('passwordModal').classList.remove('show'); }

    document.getElementById('passwordForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const errDiv = document.getElementById('passwordError');
      const succDiv = document.getElementById('passwordSuccess');
      errDiv.classList.remove('show'); succDiv.classList.remove('show');
      const newPwd = document.getElementById('newPasswordField').value;
      if (newPwd !== document.getElementById('confirmPassword').value) { errDiv.textContent = '两次输入的新密码不一致'; errDiv.classList.add('show'); return; }
      try {
        const resp = await fetch('/api/me/password', { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ oldPassword: document.getElementById('oldPassword').value, newPassword: newPwd }) });
        const data = await resp.json();
        if (resp.ok) { succDiv.textContent = '密码修改成功'; succDiv.classList.add('show'); setTimeout(closePasswordModal, 1500); }
        else { errDiv.textContent = data.error || '修改失败'; errDiv.classList.add('show'); }
      } catch { errDiv.textContent = '网络错误'; errDiv.classList.add('show'); }
    });

    // ==================== Tabs (event delegation) ====================
    document.getElementById('tabsRow').addEventListener('click', (e) => {
      const tab = e.target.closest('.tab');
      if (!tab) return;
      document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      tab.classList.add('active');
      document.getElementById('panel-' + tab.dataset.tab).classList.add('active');
    });

    // ==================== Modal close on overlay click ====================
    ['editModal','passwordModal','resetPwdModal'].forEach(id => {
      document.getElementById(id).addEventListener('click', (e) => { if (e.target === document.getElementById(id)) document.getElementById(id).classList.remove('show'); });
    });

    async function logout() {
      await fetch('/api/logout', { method: 'POST' });
      window.location.href = '/';
    }

    init();
  </script>
</body>
</html>
