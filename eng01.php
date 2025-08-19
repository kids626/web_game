<?php
?><!doctype html>
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

		let state;

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
				setTimeout(()=>{ nextQuestion(); }, 600);
			}else{
				li.classList.add('wrong');
				li.dataset.used = '1';
				li.style.pointerEvents = 'none';
				state.bad++;
				elBad.textContent = state.bad;
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

		init();
	</script>
</body>
</html>