// Felflix v5 — script.js
// Pages now load directly from TMDB API via PHP
// This file kept for compatibility and utility functions

function showToast(msg){
    document.querySelectorAll('.toast-pop').forEach(t=>t.remove());
    const t=document.createElement('div');t.className='toast-pop';t.textContent=msg;
    document.body.appendChild(t);setTimeout(()=>t.remove(),3200);
}
