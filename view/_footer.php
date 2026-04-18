<?php
$protocol=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!='off')?'https':'http';
$base=$protocol.'://'.$_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['SCRIPT_NAME']));
?>
<footer>
  <div class="footer-grid">
    <div>
      <div class="footer-logo">🌶 Felflix</div>
      <p class="footer-desc">Premier site tunisien de films et séries. Partage ton avis, découvre des nouveautés, discute avec la communauté ! 🇹🇳</p>
      <div class="soc-row">
        <a href="#" class="soc-btn"><i class="fab fa-facebook-f"></i></a>
        <a href="#" class="soc-btn"><i class="fab fa-instagram"></i></a>
        <a href="#" class="soc-btn"><i class="fab fa-tiktok"></i></a>
        <a href="#" class="soc-btn"><i class="fab fa-youtube"></i></a>
      </div>
    </div>
    <div class="footer-col">
      <h4>Contenu</h4>
      <a href="<?=$base?>/view/movies.php">Films 🎬</a>
      <a href="<?=$base?>/view/series.php">Séries 📺</a>
      <a href="<?=$base?>/view/movies.php?genre=action">Action 🔥</a>
      <a href="<?=$base?>/view/movies.php?genre=horreur">Horreur 👻</a>
      <a href="<?=$base?>/view/movies.php?genre=scifi">Sci-Fi 🚀</a>
      <a href="<?=$base?>/view/movies.php?genre=romance">Romance 💕</a>
      <a href="<?=$base?>/view/movies.php?genre=animation">Animation 🌟</a>
    </div>
    <div class="footer-col">
      <h4>Communauté</h4>
      <a href="<?=$base?>/view/community.php">Discussions 💬</a>
      <a href="<?=$base?>/view/watchlist.php">Ma liste 📋</a>
      <a href="<?=$base?>/view/profile.php">Mon profil 👤</a>
      <a href="<?=$base?>/view/signup.php">S'inscrire 🌶</a>
    </div>
  </div>
  <div class="footer-bottom">© <?=date('Y')?> Felflix 🌶 — Maaml bel 7ob fi Tounes — Tous droits réservés</div>
</footer>

<!-- CHATBOT -->
<button id="chat-fab" onclick="toggleChat()" title="Klem 3ami l felfil 🌶">🌶</button>
<div id="chat-win">
  <div class="cw-head">
    <div class="cw-av">🌶</div>
    <div><div class="cw-name">3ami l felfil 🤖</div><div class="cw-status" id="chatStatus">Yit7awas 3lik ✨</div></div>
    <div class="cw-btns">
      <button class="cw-btn" onclick="resetChat()" title="Conv jedida">🔄</button>
      <button class="cw-btn" onclick="toggleChat()">✕</button>
    </div>
  </div>
  <div class="cw-history" id="chatHist">💾 3ami l felfil yit7awas — <span id="chatHistCount">0</span> ta 7a9i ya 9sas</div>
  <div class="cw-msgs" id="cwMsgs">
    <div class="cm bot">Salam! 🌶 Ana 3ami l felfil — as2alni 3la ay film walla mosalsala, bel derja, bil 3arbi, bil français, bil inglizi — passe partout! 😎</div>
  </div>
  <div class="cw-inp">
    <button onclick="sendChat()" id="chatSend"><i class="fas fa-paper-plane"></i></button>
    <input type="text" id="cwInput" placeholder="War Machine, Squid Game..." onkeydown="if(event.key==='Enter')sendChat()"/>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?=$base?>/assets/js/script.js"></script>
<script>
/* BG CANVAS */
(function(){
  const c=document.getElementById('bg-canvas');if(!c)return;
  const ctx=c.getContext('2d');
  function resize(){c.width=innerWidth;c.height=innerHeight;}
  resize();window.addEventListener('resize',resize);
  const pts=Array.from({length:50},()=>({x:Math.random()*innerWidth,y:Math.random()*innerHeight,vx:(Math.random()-.5)*.35,vy:-(Math.random()*.6+.1),life:Math.random(),sz:Math.random()*2.5+.5,col:Math.random()>.5?'rgba(230,57,70,':'rgba(244,114,30,'}));
  (function draw(){ctx.clearRect(0,0,c.width,c.height);pts.forEach(p=>{p.x+=p.vx;p.y+=p.vy;p.life-=.003;if(p.life<=0){p.life=1;p.x=Math.random()*c.width;p.y=c.height+10;}ctx.beginPath();ctx.arc(p.x,p.y,p.sz,0,Math.PI*2);ctx.fillStyle=p.col+Math.min(p.life,.32)+')';ctx.fill();});requestAnimationFrame(draw);})();
})();

/* CHATBOT avec historique */
let chatOpen=false;
const CHAT_API='<?=$base?>/controller/chatbot_api.php';

function toggleChat(){
  chatOpen=!chatOpen;
  document.getElementById('chat-win').classList.toggle('open',chatOpen);
  if(chatOpen) setTimeout(()=>document.getElementById('cwInput').focus(),200);
}
async function resetChat(){
  try{
    const r=await fetch(CHAT_API,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({message:'',action:'reset'})});
    const d=await r.json();
    document.getElementById('cwMsgs').innerHTML=`<div class="cm bot">${d.reply}</div>`;
    document.getElementById('chatHist').classList.remove('show');
  }catch(e){}
}
async function sendChat(){
  const inp=document.getElementById('cwInput');
  const msgs=document.getElementById('cwMsgs');
  const btn=document.getElementById('chatSend');
  const txt=inp.value.trim();
  if(!txt)return;
  msgs.innerHTML+=`<div class="cm user">${esc(txt)}</div>`;
  inp.value='';btn.disabled=true;
  const lid='l'+Date.now();
  msgs.innerHTML+=`<div class="cm bot typing" id="${lid}">🌶 Yt9assa...</div>`;
  msgs.scrollTop=msgs.scrollHeight;
  try{
    const r=await fetch(CHAT_API,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({message:txt,action:'message'})});
    const d=await r.json();
    document.getElementById(lid)?.remove();
    msgs.innerHTML+=`<div class="cm bot">${d.reply.replace(/\n/g,'<br/>')}</div>`;
    if(d.history_len>2){
      document.getElementById('chatHist').classList.add('show');
      document.getElementById('chatHistCount').textContent=Math.floor(d.history_len/2);
    }
  }catch(e){
    document.getElementById(lid)?.remove();
    msgs.innerHTML+=`<div class="cm bot">😅 Problème, réessaie!</div>`;
  }
  btn.disabled=false;msgs.scrollTop=msgs.scrollHeight;
}
function esc(t){return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function showToast(msg){document.querySelectorAll('.toast-pop').forEach(t=>t.remove());const t=document.createElement('div');t.className='toast-pop';t.textContent=msg;document.body.appendChild(t);setTimeout(()=>t.remove(),3200);}
</script>
<?php if(isset($extraScript)) echo $extraScript; ?>
</body></html>
