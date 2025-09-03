<?php
// 瀏覽次數讀取（避免重整即加計，實際遞增交由前端呼叫 API）
$counterFile = __DIR__ . DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . 'eng01_views.txt';
$viewCount = 0;

// 確保資料夾與檔案存在
if (!is_dir(__DIR__ . DIRECTORY_SEPARATOR . 'project')) {
	@mkdir(__DIR__ . DIRECTORY_SEPARATOR . 'project', 0777, true);
}
if (!is_file($counterFile)) {
	@file_put_contents($counterFile, '0', LOCK_EX);
}
// 直接讀取目前數值
$raw = @file_get_contents($counterFile);
$viewCount = is_numeric(trim((string)$raw)) ? (int)trim((string)$raw) : 0;
?>
<!doctype html>
<html lang="zh-Hant">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>英文字母配對</title>
	<style>
		:root{
			--bg:#0e1621;
			--panel:#1c2531;
			--panel2:#222b38;
			--text:#e6f0ff;
			--muted:#9fb3c8;
			--ok:#22c55e;
			--bad:#ef4444;
			--accent:#60a5fa;
		}
		*{box-sizing:border-box}
		html,body{height:100%}
		body{
			margin:0;
			background:linear-gradient(180deg,#0b1220,#0f172a 60%);
			color:var(--text);
			font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,PingFang TC,Microsoft JhengHei,sans-serif;
			display:flex;align-items:center;justify-content:center;padding:16px;
		}
		.app{
			width:min(920px,100%);
			display:grid;
			grid-template-columns:1fr 1.2fr;
			gap:20px;
		}
		.card{
			background:var(--panel);
			border:1px solid #223047;
			border-radius:14px;
			box-shadow:0 10px 30px rgba(0,0,0,.35), inset 0 1px 0 rgba(255,255,255,.03);
		}
		.left{padding:20px 24px}
		.hint{color:var(--muted);font-size:14px;margin-top:8px}
		.big{
			font-size:160px;line-height:1;margin:10px 0 6px;
			text-shadow:0 0 24px rgba(96,165,250,.6), 0 0 64px rgba(96,165,250,.25);
			color:#e9f2ff;
			font-weight:800;
			letter-spacing:.02em;
		}
		.title{font-weight:700;color:#cfe1ff}
		.options{display:flex;flex-direction:column;gap:12px;margin:0;padding:0;list-style:none}
		.opt{
			background:var(--panel2);
			border-radius:10px;
			border:1px solid #2a394e;
			padding:14px 16px;
			font-size:24px;
			display:flex;align-items:center;gap:10px;
			cursor:pointer;
			transition:transform .06s ease,border-color .2s ease,background-color .2s ease;
		}
		.opt:hover{transform:translateY(-1px);border-color:#375179}
		.bullet{
			width:8px;height:8px;border-radius:999px;background:#b7c7dd;display:inline-block;margin-right:8px;
		}
		.opt.correct{background:rgba(34,197,94,.12);border-color:#27b45c}
		.opt.wrong{background:rgba(239,68,68,.12);border-color:#ef4444}
		.stats{
			padding:16px 20px;display:flex;gap:16px;flex-wrap:wrap
		}
		.badge{
			background:#0f2036;border:1px solid #223047;border-radius:10px;padding:10px 12px
		}
		.badge b{color:#fff}
		.controls{display:flex;gap:10px;padding:0 20px 20px}
		button{
			background:linear-gradient(180deg,#2563eb,#1d4ed8);
			border:0;color:#fff;padding:10px 16px;border-radius:10px;cursor:pointer;
			font-weight:700
		}
		button.secondary{background:#2b3443}
		.end{
			padding:24px;text-align:center
		}
		.small{font-size:12px;color:var(--muted)}
		@media (max-width: 720px){
			.app{grid-template-columns:1fr}
			body{padding-bottom:140px}
		}
	</style>
</head>
<body>
	<div class="app">
		<div class="card left">
			<div class="title">題目（大寫）</div>
			<div id="big" class="big">A</div>
			<div class="hint">請點選對應的小寫</div>
		</div>

		<div class="card">
			<div class="stats">
				<div class="badge">第 <b id="qNo">1</b> 題 / <span id="totalQ">10</span></div>
				<div class="badge">答對 <b id="ok">0</b></div>
				<div class="badge">答錯 <b id="bad">0</b></div>
			</div>
			<ul id="opts" class="options"></ul>
			<div id="end" class="end" style="display:none">
				<h3>完成！</h3>
				<p>總分：<b id="scoreText">0</b></p>
				<p>總用時：<b id="timeText">0.0s</b></p>
				<p class="small">成績已嘗試上傳。</p>
				<div class="controls" style="justify-content:center">
					<button id="againBtn">再玩一次</button>
				</div>
			</div>
		</div>
	</div>

	<!-- 底部固定區塊：小 QR（可點擊放大） 與瀏覽次數 -->
	<div class="small" style="position:fixed;bottom:10px;left:0;right:0;opacity:.9;padding:10px;display:flex;align-items:center;justify-content:center;gap:12px;text-align:center">
		<div id="qrCode" title="點我放大" style="width:5px;height:5px;cursor:pointer"></div>
		<span>瀏覽次數 <b id="viewCount"><?php echo htmlspecialchars((string)$viewCount, ENT_QUOTES, 'UTF-8'); ?></b></span>
	</div>

	<!-- QR 放大覆蓋層 -->
	<div id="qrOverlay" style="position:fixed;inset:0;background:rgba(0,0,0,.6);display:none;align-items:center;justify-content:center;z-index:9999">
		<div style="background:#0f2036;border:1px solid #223047;border-radius:12px;padding:16px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,.45)">
			<div class="small" style="margin-bottom:8px;color:#cfe1ff">掃描在手機開啟本頁</div>
			<div id="qrCodeLarge" style="width:260px;height:260px;margin:0 auto"></div>
			<div style="margin-top:10px">
				<button id="qrClose" class="secondary">關閉</button>
			</div>
		</div>
	</div>

	<!-- 音效元素 -->
	<audio id="correctSound" preload="auto" playsinline>
		<source src="audio/quiz/correct.wav" type="audio/wav">
		<source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT" type="audio/wav">
	</audio>
	<audio id="wrongSound" preload="auto" playsinline>
		<source src="audio/quiz/error.mp3" type="audio/mpeg">
		<source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10 IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT" type="audio/wav">
	</audio>

	<!-- 產生 QRCode 的小型程式庫 -->
	<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

	<script>
		const letters = Array.from({length:26},(_,i)=>String.fromCharCode(65+i));
		const totalQuestionsDefault = 10;

		const elBig = document.getElementById('big');
		const elOpts = document.getElementById('opts');
		const elQNo = document.getElementById('qNo');
		const elTotal = document.getElementById('totalQ');
		const elOk = document.getElementById('ok');
		const elBad = document.getElementById('bad');
		const endPanel = document.getElementById('end');
		const againBtn = document.getElementById('againBtn');
		const scoreText = document.getElementById('scoreText');
		const timeText = document.getElementById('timeText');
		const viewCountEl = document.getElementById('viewCount');
		
		// 音效元素
		const correctSound = document.getElementById('correctSound');
		const wrongSound = document.getElementById('wrongSound');

		let state;

		function pronounce(letter){
			try{
				if(!('speechSynthesis' in window)) return;
				speechSynthesis.cancel();
				const u = new SpeechSynthesisUtterance(letter);
				u.lang = 'en-US';
				u.rate = 0.9;
				u.pitch = 1.0;
				speechSynthesis.speak(u);
			}catch(_){/* 忽略發音失敗 */}
		}

		// 綁定發音事件（按鈕 / 大寫字母）
		function bindPronounceEvents(){
			if(elBig){
				elBig.style.cursor = 'pointer';
				elBig.title = '點我播放發音';
				elBig.setAttribute('tabindex','0');
				elBig.addEventListener('click',()=>{
					const letter = (state && state.currentUpper) ? state.currentUpper : (elBig.textContent||'').trim();
					if(letter) pronounce(letter);
				});
				elBig.addEventListener('keydown',(e)=>{
					if(e.key === 'Enter' || e.key === ' '){
						e.preventDefault();
						const letter = (state && state.currentUpper) ? state.currentUpper : (elBig.textContent||'').trim();
						if(letter) pronounce(letter);
					}
				});
			}
		}

		// 播放音效函數
		function playSound(audioElement) {
			audioElement.currentTime = 0;
			audioElement.play().catch(e => {
				// 忽略音效播放錯誤
				console.log('音效播放失敗:', e);
			});
		}

		// 播放答對音效
		function playCorrectSound() {
			playSound(correctSound);
		}

		// 播放答錯音效
		function playWrongSound() {
			playSound(wrongSound);
		}

		function shuffle(a){
			for(let i=a.length-1;i>0;i--){
				const j=Math.floor(Math.random()*(i+1));
				[a[i],a[j]]=[a[j],a[i]];
			}
			return a;
		}

		function sampleWrong(lowerCorrect){
			const pool = 'abcdefghijklmnopqrstuvwxyz'.split('').filter(c=>c!==lowerCorrect);
			shuffle(pool);
			return pool.slice(0,3);
		}

		function init(){
			state = {
				total: totalQuestionsDefault,
				idx: 0,
				ok: 0,
				bad: 0,
				locked: false,
				startAt: performance.now(),
				qStart: performance.now(),
				currentUpper: null
			};
			elTotal.textContent = state.total;
			elOk.textContent = '0';
			elBad.textContent = '0';
			endPanel.style.display = 'none';
			renderQuestion();
		}


		function renderQuestion(){
			state.locked = false;
			state.qStart = performance.now();
			elOpts.innerHTML = '';

			const upper = letters[Math.floor(Math.random()*letters.length)];
			state.currentUpper = upper;
			elBig.textContent = upper;

			const correct = upper.toLowerCase();
			const wrongs = sampleWrong(correct);
			const options = shuffle([correct, ...wrongs]);

			options.forEach(opt=>{
				const li = document.createElement('li');
				li.className = 'opt';
				li.innerHTML = '<span class="bullet"></span> ' + opt;
				li.addEventListener('click',()=>onChoose(li,opt,correct));
				elOpts.appendChild(li);
			});

			elQNo.textContent = (state.idx+1);
		}

		function onChoose(li, chosen, correct){
			if(state.locked) return;
			if(li.dataset.used === '1') return;

			if(chosen === correct){
				li.classList.add('correct');
				state.ok++;
				elOk.textContent = state.ok;
				state.locked = true;
				// 播放答對音效
				playCorrectSound();
				setTimeout(()=>{ nextQuestion(); }, 600);
			}else{
				li.classList.add('wrong');
				li.dataset.used = '1';
				li.style.pointerEvents = 'none';
				state.bad++;
				elBad.textContent = state.bad;
				// 播放答錯音效
				playWrongSound();
			}
		}

		function nextQuestion(){
			if(state.idx + 1 >= state.total){
				return endGame();
			}
			state.idx++;
			renderQuestion();
		}

		function endGame(){
			elOpts.innerHTML = '';
			endPanel.style.display = 'block';
			const duration = Math.round(performance.now() - state.startAt);
			const score = Math.max(0, state.ok*10 - state.bad*5);
			scoreText.textContent = String(score);
			timeText.textContent = (duration/1000).toFixed(1) + 's';
			postScore({
				game_type:'eng_upper_lower',
				difficulty:'A-Z',
				score,
				correct: state.ok,
				wrong: state.bad,
				duration_ms: duration,
				ended_at: new Date().toISOString()
			});
			// 確保小螢幕可見
			setTimeout(()=>{ try{ endPanel.scrollIntoView({behavior:'smooth',block:'center'});}catch(_){} }, 50);
		}

		async function postScore(payload){
			try{
				await fetch('api/score.php',{
					method:'POST',
					headers:{'Content-Type':'application/json'},
					body: JSON.stringify(payload)
				});
			}catch(e){
				// 忽略上傳失敗
			}
		}

		againBtn.addEventListener('click', init);

		bindPronounceEvents();
		init();

		// 產生底部 QR：小圖與大圖
		try{
			if(window.QRCode){
				const url = 'https://shous.ddns.net/web_game/eng01.php';
				const smallBox = document.getElementById('qrCode');
				const largeBox = document.getElementById('qrCodeLarge');
				if(smallBox && smallBox.childNodes.length === 0){
					new QRCode(smallBox, { text: url, width: 5, height: 5, correctLevel: QRCode.CorrectLevel.M });
				}
				if(largeBox && largeBox.childNodes.length === 0){
					new QRCode(largeBox, { text: url, width: 260, height: 260, correctLevel: QRCode.CorrectLevel.M });
				}
			}
		}catch(_){/* 略過 QR 產生錯誤 */}

		// 點擊顯示 / 關閉放大 QR
		(function bindQrOverlay(){
			const overlay = document.getElementById('qrOverlay');
			const small = document.getElementById('qrCode');
			const closeBtn = document.getElementById('qrClose');
			if(small && overlay){
				small.addEventListener('click',()=>{ overlay.style.display='flex'; });
				overlay.addEventListener('click', (e)=>{ if(e.target === overlay) overlay.style.display='none'; });
			}
			if(closeBtn && overlay){
				closeBtn.addEventListener('click',()=>{ overlay.style.display='none'; });
			}
		})();

		// 瀏覽次數：同分頁工作階段只計一次（避免重整就加計）
		(async function manageViewCounter(){
			try{
				const countedKey = 'eng01_view_counted';
				const mode = sessionStorage.getItem(countedKey) ? 'get' : 'inc';
				const res = await fetch('api/views.php',{
					method:'POST',
					headers:{'Content-Type':'application/json'},
					body: JSON.stringify({page:'eng01', mode})
				});
				const data = await res.json();
				if(data && typeof data.count === 'number' && viewCountEl){
					viewCountEl.textContent = String(data.count);
				}
				if(mode === 'inc'){
					sessionStorage.setItem(countedKey,'1');
				}
			}catch(_){/* 失敗時沿用伺服器初始值 */}
		})();
	</script>
</body>
</html>