(()=>{document.addEventListener("DOMContentLoaded",function(){let g=document.getElementById("suggestedTripForm"),h=document.getElementById("publishSuggestedForm"),y=new bootstrap.Modal(document.getElementById("confirmationModal")),x=document.getElementById("confirmSubmit"),u=document.getElementById("pricePerPassenger"),I=document.querySelectorAll('input[name="available_seats"]');D();function m(){let e=parseInt(u.value)||0,t=document.querySelector('input[name="available_seats"]:checked'),s=t?parseInt(t.value):3;document.getElementById("totalPrice").textContent=e*s,document.getElementById("placeFree").textContent=s}u.addEventListener("input",m),I.forEach(e=>{e.addEventListener("change",m)}),h.addEventListener("click",function(){E()&&L()}),x.addEventListener("click",function(){g.submit()});function E(){let e=[{id:"startCity",message:"La ville de d\xE9part est obligatoire"},{id:"startLocation",message:"Le lieu de d\xE9part pr\xE9cis est obligatoire"},{id:"endCity",message:"La ville de destination est obligatoire"},{id:"endLocation",message:"Le lieu d'arriv\xE9e pr\xE9cis est obligatoire"}];for(let o of e){let n=document.getElementById(o.id);if(!n||!n.value.trim())return i(o.message),n?.focus(),!1}let t=[{id:"departureDate",message:"La date de d\xE9part est obligatoire"},{id:"departureTime",message:"L'heure de d\xE9part est obligatoire"}];for(let o of t){let n=document.getElementById(o.id);if(!n||!n.value)return i(o.message),n?.focus(),!1}let s=document.getElementById("departureDate");if(s&&s.value){let o=new Date(s.value+"T00:00:00"),n=new Date;if(n.setHours(0,0,0,0),o<n)return i("La date ne peut pas \xEAtre dans le pass\xE9"),s.focus(),!1}let a=document.querySelector('select[name="duration_hours"]'),c=document.querySelector('select[name="duration_minutes"]');if(!a||!c||!a.value||!c.value)return i("Veuillez indiquer la dur\xE9e estim\xE9e du trajet"),(a&&!a.value?a:c)?.focus(),!1;let r=document.querySelector('select[name="vehicle_id"]');if(!r||!r.value)return i("Veuillez s\xE9lectionner un v\xE9hicule"),r?.focus(),!1;let l=parseInt(u.value);return!l||l<=0||l>1e3?(i("Le prix doit \xEAtre entre 1 et 1000 cr\xE9dits"),u.focus(),!1):!0}function L(){let e=new FormData(g),t='<div class="row g-3">',s=e.get("start_city"),a=e.get("start_location"),c=e.get("end_city"),r=e.get("end_location"),l=e.get("departure_date"),o=e.get("departure_time"),n=e.get("duration_hours"),$=e.get("duration_minutes"),f=e.get("available_seats"),v=e.get("price_per_passenger"),b=document.querySelector('select[name="vehicle_id"]'),S=b.options[b.selectedIndex].text;t+=`
            <div class="col-md-6">
                <h6 class="text-muted mb-2">Itin\xE9raire</h6>
                <p class="mb-1"><i class="bi bi-geo-alt text-success me-1"></i> <strong>${s}</strong></p>
                <p class="mb-1 small text-muted ms-3">${a}</p>
                <p class="mb-1"><i class="bi bi-arrow-down text-muted me-1"></i> <strong>${c}</strong></p>
                <p class="small text-muted ms-3">${r}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted mb-2">Date et heure</h6>
                <p class="mb-1"><i class="bi bi-calendar-event text-success me-1"></i> ${w(l)}</p>
                <p class="mb-1"><i class="bi bi-clock text-success me-1"></i> ${o}</p>
                <p class="small text-muted"><i class="bi bi-hourglass-split me-1"></i> Dur\xE9e : ${n}h${$.padStart(2,"0")}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted mb-2">V\xE9hicule</h6>
                <p class="mb-1"><i class="bi bi-car-front text-success me-1"></i> ${S}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted mb-2">Places et prix</h6>
                <p class="mb-1"><i class="bi bi-people text-success me-1"></i> ${f} places disponibles</p>
                <p class="mb-1"><i class="bi bi-currency-euro text-success me-1"></i> ${v} cr\xE9dits par passager</p>
                <p class="small text-success fw-bold">Total maximum : ${v*f} cr\xE9dits</p>
            </div>
        `;let d=[];e.get("no_smoking")&&d.push("\u{1F6AD} Non-fumeur"),e.get("music_allowed")&&d.push("\u{1F3B5} Musique autoris\xE9e"),e.get("discuss_allowed")&&d.push("\u{1F4AC} Discussions bienvenues"),d.length>0&&(t+=`
                <div class="col-12">
                    <h6 class="text-muted mb-2">Pr\xE9f\xE9rences</h6>
                    <p class="small">${d.join(", ")}</p>
                </div>
            `);let p=e.get("comment");p&&p.trim()&&(t+=`
                <div class="col-12">
                    <h6 class="text-muted mb-2">Commentaire</h6>
                    <p class="small fst-italic">"${B(p.trim())}"</p>
                </div>
            `),t+="</div>",document.getElementById("modalText").innerHTML=t,y.show()}function D(){let e=document.getElementById("departureDate");e&&!e.value&&(e.min=_())}function _(){let e=new Date,t=e.getFullYear(),s=String(e.getMonth()+1).padStart(2,"0"),a=String(e.getDate()).padStart(2,"0");return`${t}-${s}-${a}`}function w(e){try{return new Date(e+"T00:00:00").toLocaleDateString("fr-FR",{weekday:"long",year:"numeric",month:"long",day:"numeric"})}catch(t){return console.warn("Erreur formatage date:",t),e}}function i(e){let t=document.getElementById("errorAlert"),s=document.getElementById("errorMessage");t&&s?(s.textContent=e,t.classList.remove("d-none"),t.scrollIntoView({behavior:"smooth",block:"center"}),setTimeout(()=>{t.classList.add("d-none")},5e3)):alert(e)}function B(e){let t=document.createElement("div");return t.textContent=e,t.innerHTML}m()});})();
