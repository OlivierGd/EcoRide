(()=>{function l(s){s.forEach(n=>{p(n.inputId,n.suggestionId),m(n.inputId,n.suggestionId)})}function p(s,n){let t=document.getElementById(s);if(!t){console.warn(`Input ${s} non trouv\xE9`);return}let e=t.closest(".input-group")||t.parentElement;if(!e){console.warn(`Parent container non trouv\xE9 pour ${s}`);return}if(document.getElementById(n))return;let i=document.createElement("div");i.id=n,i.className="suggestion-box",i.style.cssText=`
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #dee2e6;
        border-top: none;
        border-radius: 0 0 0.375rem 0.375rem;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    `,e.style.position="relative",e.appendChild(i)}function m(s,n){let t=document.getElementById(s),e=document.getElementById(n);if(!t||!e){console.error(`\xC9l\xE9ments non trouv\xE9s: ${s} ou ${n}`);return}let i;t.addEventListener("input",()=>{let r=t.value.trim();if(clearTimeout(i),r.length<2){e.style.display="none",e.innerHTML="";return}i=setTimeout(async()=>{try{let a=await fetch(`https://geo.api.gouv.fr/communes?nom=${encodeURIComponent(r)}&fields=nom,codeDepartement&boost=population&limit=8`);if(!a.ok)throw new Error("Erreur r\xE9seau");let u=await a.json();if(e.innerHTML="",u.length===0){e.style.display="none";return}u.forEach(d=>{let o=document.createElement("div");o.className="suggestion-item",o.style.cssText=`
                        padding: 10px 15px;
                        cursor: pointer;
                        border-bottom: 1px solid #f8f9fa;
                        transition: background-color 0.2s;
                    `,o.textContent=`${d.nom} (${d.codeDepartement})`,o.addEventListener("mouseenter",()=>{o.style.backgroundColor="#f8f9fa"}),o.addEventListener("mouseleave",()=>{o.style.backgroundColor="white"}),o.addEventListener("click",()=>{t.value=d.nom,e.style.display="none",e.innerHTML="",t.dispatchEvent(new Event("input",{bubbles:!0}))}),e.appendChild(o)}),e.style.display="block"}catch(a){console.error("Erreur lors de l'autocompl\xE9tion :",a),e.style.display="none"}},300)}),document.addEventListener("click",r=>{!t.contains(r.target)&&!e.contains(r.target)&&(e.style.display="none")}),t.addEventListener("keydown",r=>{r.key==="Escape"&&(e.style.display="none")})}var c={search:[{inputId:"searchStartCity",suggestionId:"startCitySuggestions"},{inputId:"searchEndCity",suggestionId:"endCitySuggestions"}],propose:[{inputId:"startCity",suggestionId:"startCitySuggestions"},{inputId:"endCity",suggestionId:"endCitySuggestions"}]};document.addEventListener("DOMContentLoaded",function(){document.getElementById("searchStartCity")&&document.getElementById("searchEndCity")&&(console.log("Initialisation autocompl\xE9tion pour page recherche"),l(c.search)),document.getElementById("startCity")&&document.getElementById("endCity")&&(console.log("Initialisation autocompl\xE9tion pour page proposition"),l(c.propose))});})();
