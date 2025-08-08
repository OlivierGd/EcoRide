(()=>{(()=>{let d=document.getElementById("searchUserForm");d&&d.addEventListener("submit",function(e){e.preventDefault();let t=this.query,n=t.value.trim();n&&fetch("api/get_users.php?query="+encodeURIComponent(n)).then(r=>r.json()).then(r=>{let i=document.getElementById("userDetails");if(!Array.isArray(r)||r.length===0){i.innerHTML='<div class="alert alert-warning">Aucun utilisateur trouv\xE9</div>';return}let s=`<table class="table table-hover"><thead><tr>
              <th>ID</th><th>Pr\xE9nom</th><th>Nom</th><th>Email</th><th>R\xF4le</th><th>Statut</th>
            </tr></thead><tbody>`;r.forEach(a=>{s+=`<tr class="select-user" data-id="${a.user_id}">
              <td>${a.user_id}</td>
              <td>${a.firstname}</td>
              <td>${a.lastname}</td>
              <td>${a.email}</td>
              <td>${l(a.role)}</td>
              <td>${o(a.status)}</td>
            </tr>`}),s+="</tbody></table>",i.innerHTML=s,document.querySelectorAll(".select-user").forEach(a=>{a.addEventListener("click",function(){m(this.dataset.id)})}),t.value=""})});function m(e){fetch("api/get_users_details.php?user_id="+encodeURIComponent(e)).then(t=>t.json()).then(t=>{let n=document.getElementById("userDetails");if(t.error){n.innerHTML=`<div class="alert alert-danger">${t.error}</div>`;return}n.innerHTML=`
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">${t.firstname} ${t.lastname} (#${t.user_id})</h5>
              <p>Email : ${t.email}</p>
              <p>R\xF4le : ${l(t.role)}</p>
              <p>Statut : ${o(t.status)}</p>
              <p>Date cr\xE9ation : ${t.created_at||""}</p>
              <button class="btn btn-secondary mt-3" onclick="retourRecherche()">Retour</button>
            </div>
          </div>
        `})}function l(e){return e=parseInt(e),"Utilisateur"}function o(e){return e==="actif"?'<span class="badge bg-success">Actif</span>':'<span class="badge bg-secondary">Inactif</span>'}function c(e={}){let t="api/get_comments.php",n=new URLSearchParams(e).toString();n&&(t+="?"+n),fetch(t).then(r=>r.json()).then(r=>{let i=document.getElementById("commentsTableContainer");if(!Array.isArray(r)||r.length===0){i.innerHTML='<div class="alert alert-warning">Aucun commentaire trouv\xE9</div>';return}let s=`<table class="table table-hover"><thead><tr>
            <th>Voyage ID</th><th>Date</th><th>Voyageur</th><th>D\xE9part</th><th>Arriv\xE9e</th>
            <th>Montant pay\xE9</th><th>Ranking</th><th>Commentaire</th><th>Chauffeur</th>
          </tr></thead><tbody>`;r.forEach(a=>{s+=`<tr>
              <td>${a.trip_id}</td>
              <td>${a.trip_date}</td>
              <td>${a.voyager_firstname} ${a.voyager_lastname}</td>
              <td>${a.start_city}</td>
              <td>${a.end_city}</td>
              <td>${a.price_per_passenger||"-"}</td>
              <td>${a.rating} \u2605</td>
              <td>${a.commentaire}</td>
              <td>${a.driver_firstname} ${a.driver_lastname}</td>
            </tr>`}),s+="</tbody></table>",i.innerHTML=s})}c();let h=document.getElementById("commentsFilterForm");h&&h.addEventListener("submit",function(e){e.preventDefault();let t={rating:this.rating.value,date_min:this.date_min.value,date_max:this.date_max.value};c(t)});let u=document.getElementById("chartCommissions");u&&typeof monthlyData<"u"&&new Chart(u,{type:"line",data:{labels:monthlyData.map(e=>e.month),datasets:[{label:"Cr\xE9dits collect\xE9s (par mois)",data:monthlyData.map(e=>parseFloat(e.total)),backgroundColor:"rgba(25, 135, 84, 0.2)",borderColor:"rgba(25, 135, 84, 1)",borderWidth:2}]},options:{scales:{y:{beginAtZero:!0,ticks:{callback:e=>e+" cr\xE9dits"}}}}}),(()=>{let e=document.getElementById("chartTripsByDay");e&&typeof tripsByDay<"u"&&new Chart(e,{type:"bar",data:{labels:tripsByDay.map(t=>t.day),datasets:[{label:"Trajets actifs",data:tripsByDay.map(t=>parseInt(t.valid_trips)),backgroundColor:"rgba(25, 135, 84, 0.7)"},{label:"Trajets annul\xE9s",data:tripsByDay.map(t=>parseInt(t.cancelled_trips)),backgroundColor:"rgba(220, 53, 69, 0.7)"}]},options:{responsive:!0,scales:{y:{beginAtZero:!0,title:{display:!0,text:"Nombre de trajets"}}}}})})()})();})();
