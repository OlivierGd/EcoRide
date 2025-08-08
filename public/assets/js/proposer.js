(()=>{document.addEventListener("DOMContentLoaded",function(){let f=document.getElementById("suggestedTripForm"),E=document.getElementById("publishSuggestedForm"),L=new bootstrap.Modal(document.getElementById("confirmationModal")),C=document.getElementById("confirmSubmit"),m=document.getElementById("pricePerPassenger"),w=document.querySelectorAll('input[name="available_seats"]');T(),D();function p(){let e=parseInt(m.value)||0,t=document.querySelector('input[name="available_seats"]:checked'),s=t?parseInt(t.value):3;document.getElementById("totalPrice").textContent=e*s,document.getElementById("placeFree").textContent=s}m.addEventListener("input",p),w.forEach(e=>{e.addEventListener("change",p)}),E.addEventListener("click",function(){I()&&B()}),C.addEventListener("click",function(){f.submit()});function I(){let e=[{id:"startCity",message:"La ville de d\xE9part est obligatoire"},{id:"startLocation",message:"Le lieu de d\xE9part pr\xE9cis est obligatoire"},{id:"endCity",message:"La ville de destination est obligatoire"},{id:"endLocation",message:"Le lieu d'arriv\xE9e pr\xE9cis est obligatoire"}];for(let r of e){let o=document.getElementById(r.id);if(!o||!o.value.trim())return d(r.message),o?.focus(),!1}let t=[{id:"departureDate",message:"La date de d\xE9part est obligatoire"},{id:"departureTime",message:"L'heure de d\xE9part est obligatoire"}];for(let r of t){let o=document.getElementById(r.id);if(!o||!o.value)return d(r.message),o?.focus(),!1}let s=document.getElementById("departureDate");if(s&&s.value){let r=new Date(s.value+"T00:00:00"),o=new Date;if(o.setHours(0,0,0,0),r<o)return d("La date ne peut pas \xEAtre dans le pass\xE9"),s.focus(),!1}let n=document.querySelector('select[name="duration_hours"]'),i=document.querySelector('select[name="duration_minutes"]');if(!n||!i||!n.value||!i.value)return d("Veuillez indiquer la dur\xE9e estim\xE9e du trajet"),(n&&!n.value?n:i)?.focus(),!1;let a=document.querySelector('select[name="vehicle_id"]');if(!a||!a.value)return d("Veuillez s\xE9lectionner un v\xE9hicule"),a?.focus(),!1;let l=parseInt(m.value);return!l||l<=0||l>1e3?(d("Le prix doit \xEAtre entre 1 et 1000 cr\xE9dits"),m.focus(),!1):!0}function B(){let e=new FormData(f),t='<div class="row g-3">',s=e.get("start_city"),n=e.get("start_location"),i=e.get("end_city"),a=e.get("end_location"),l=e.get("departure_date"),r=e.get("departure_time"),o=e.get("duration_hours"),c=e.get("duration_minutes"),v=e.get("available_seats"),h=e.get("price_per_passenger"),x=document.querySelector('select[name="vehicle_id"]'),k=x.options[x.selectedIndex].text;t+=`
            <div class="col-md-6">
                <h6 class="text-muted mb-2">Itin\xE9raire</h6>
                <p class="mb-1"><i class="bi bi-geo-alt text-success me-1"></i> <strong>${s}</strong></p>
                <p class="mb-1 small text-muted ms-3">${n}</p>
                <p class="mb-1"><i class="bi bi-arrow-down text-muted me-1"></i> <strong>${i}</strong></p>
                <p class="small text-muted ms-3">${a}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted mb-2">Date et heure</h6>
                <p class="mb-1"><i class="bi bi-calendar-event text-success me-1"></i> ${S(l)}</p>
                <p class="mb-1"><i class="bi bi-clock text-success me-1"></i> ${r}</p>
                <p class="small text-muted"><i class="bi bi-hourglass-split me-1"></i> Dur\xE9e : ${o}h${c.padStart(2,"0")}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted mb-2">V\xE9hicule</h6>
                <p class="mb-1"><i class="bi bi-car-front text-success me-1"></i> ${k}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted mb-2">Places et prix</h6>
                <p class="mb-1"><i class="bi bi-people text-success me-1"></i> ${v} places disponibles</p>
                <p class="mb-1"><i class="bi bi-currency-euro text-success me-1"></i> ${h} cr\xE9dits par passager</p>
                <p class="small text-success fw-bold">Total maximum : ${h*v} cr\xE9dits</p>
            </div>
        `;let u=[];e.get("no_smoking")&&u.push("\u{1F6AD} Non-fumeur"),e.get("music_allowed")&&u.push("\u{1F3B5} Musique autoris\xE9e"),e.get("discuss_allowed")&&u.push("\u{1F4AC} Discussions bienvenues"),u.length>0&&(t+=`
                <div class="col-12">
                    <h6 class="text-muted mb-2">Pr\xE9f\xE9rences</h6>
                    <p class="small">${u.join(", ")}</p>
                </div>
            `);let g=e.get("comment");g&&g.trim()&&(t+=`
                <div class="col-12">
                    <h6 class="text-muted mb-2">Commentaire</h6>
                    <p class="small fst-italic">"${_(g.trim())}"</p>
                </div>
            `),t+="</div>",document.getElementById("modalText").innerHTML=t,L.show()}function D(){let e=document.getElementById("departureDate");e&&!e.value&&(e.min=$())}function $(){let e=new Date,t=e.getFullYear(),s=String(e.getMonth()+1).padStart(2,"0"),n=String(e.getDate()).padStart(2,"0");return`${t}-${s}-${n}`}function S(e){try{return new Date(e+"T00:00:00").toLocaleDateString("fr-FR",{weekday:"long",year:"numeric",month:"long",day:"numeric"})}catch(t){return console.warn("Erreur formatage date:",t),e}}function d(e){let t=document.getElementById("errorAlert"),s=document.getElementById("errorMessage");t&&s?(s.textContent=e,t.classList.remove("d-none"),t.scrollIntoView({behavior:"smooth",block:"center"}),setTimeout(()=>{t.classList.add("d-none")},5e3)):alert(e)}function T(){y("startCity","startCitySuggestions"),y("endCity","endCitySuggestions"),b("startCity","startCitySuggestions"),b("endCity","endCitySuggestions")}function y(e,t){let s=document.getElementById(e);if(!s)return;let n=s.closest(".input-group");if(!n||document.getElementById(t))return;let i=document.createElement("div");i.id=t,i.className="suggestion-box",i.style.cssText=`
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
        `,n.style.position="relative",n.appendChild(i)}function b(e,t){let s=document.getElementById(e),n=document.getElementById(t);if(!s||!n){console.error(`\xC9l\xE9ments non trouv\xE9s: ${e} ou ${t}`);return}let i;s.addEventListener("input",()=>{let a=s.value.trim();if(clearTimeout(i),a.length<2){n.style.display="none",n.innerHTML="";return}i=setTimeout(async()=>{try{let l=await fetch(`https://geo.api.gouv.fr/communes?nom=${encodeURIComponent(a)}&fields=nom,codeDepartement&boost=population&limit=8`);if(!l.ok)throw new Error("Erreur r\xE9seau");let r=await l.json();if(n.innerHTML="",r.length===0){n.style.display="none";return}r.forEach(o=>{let c=document.createElement("div");c.className="suggestion-item",c.style.cssText=`
                            padding: 10px 15px;
                            cursor: pointer;
                            border-bottom: 1px solid #f8f9fa;
                            transition: background-color 0.2s;
                        `,c.textContent=`${o.nom} (${o.codeDepartement})`,c.addEventListener("mouseenter",()=>{c.style.backgroundColor="#f8f9fa"}),c.addEventListener("mouseleave",()=>{c.style.backgroundColor="white"}),c.addEventListener("click",()=>{s.value=o.nom,n.style.display="none",n.innerHTML="",s.dispatchEvent(new Event("input",{bubbles:!0}))}),n.appendChild(c)}),n.style.display="block"}catch(l){console.error("Erreur lors de l'autocompl\xE9tion :",l),n.style.display="none"}},300)}),document.addEventListener("click",a=>{!s.contains(a.target)&&!n.contains(a.target)&&(n.style.display="none")}),s.addEventListener("keydown",a=>{a.key==="Escape"&&(n.style.display="none")})}function _(e){let t=document.createElement("div");return t.textContent=e,t.innerHTML}p()});})();
