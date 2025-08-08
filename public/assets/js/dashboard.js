(()=>{(()=>{let d=document.getElementById("searchUserForm");d&&d.addEventListener("submit",function(e){e.preventDefault();let t=this.query,r=t.value.trim();r&&fetch("api/get_users.php?query="+encodeURIComponent(r)).then(n=>n.json()).then(n=>{let s=document.getElementById("userDetails");if(!Array.isArray(n)||n.length===0){s.innerHTML='<div class="alert alert-warning">Aucun utilisateur trouv\xE9</div>';return}let i=`<table class="table table-hover"><thead><tr>
              <th>ID</th><th>Pr\xE9nom</th><th>Nom</th><th>Email</th><th>R\xF4le</th><th>Statut</th>
            </tr></thead><tbody>`;n.forEach(a=>{i+=`<tr class="select-user" data-id="${a.user_id}">
              <td>${a.user_id}</td>
              <td>${a.firstname}</td>
              <td>${a.lastname}</td>
              <td>${a.email}</td>
              <td>${o(a.role)}</td>
              <td>${l(a.status)}</td>
            </tr>`}),i+="</tbody></table>",s.innerHTML=i,document.querySelectorAll(".select-user").forEach(a=>{a.addEventListener("click",function(){u(this.dataset.id)})}),t.value=""})});function u(e){fetch("api/get_users_details.php?user_id="+encodeURIComponent(e)).then(t=>t.json()).then(t=>{let r=document.getElementById("userDetails");if(t.error){r.innerHTML=`<div class="alert alert-danger">${t.error}</div>`;return}r.innerHTML=`
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">${t.firstname} ${t.lastname} (#${t.user_id})</h5>
              <p>Email : ${t.email}</p>
              <p>R\xF4le : ${o(t.role)}</p>
              <p>Statut : ${l(t.status)}</p>
              <p>Date cr\xE9ation : ${t.created_at||""}</p>
              <button class="btn btn-secondary mt-3" onclick="retourRecherche()">Retour</button>
            </div>
          </div>
        `})}function o(e){return e=parseInt(e),"Utilisateur";return e===1?"Gestionnaire":e===2?"Admin":"Inconnu"}function l(e){return e==="actif"?'<span class="badge bg-success">Actif</span>':'<span class="badge bg-secondary">Inactif</span>'}function c(e={}){let t="api/get_comments.php",r=new URLSearchParams(e).toString();r&&(t+="?"+r),fetch(t).then(n=>n.json()).then(n=>{let s=document.getElementById("commentsTableContainer");if(!Array.isArray(n)||n.length===0){s.innerHTML='<div class="alert alert-warning">Aucun commentaire trouv\xE9</div>';return}let i=`<table class="table table-hover"><thead><tr>
            <th>Voyage ID</th><th>Date</th><th>Voyageur</th><th>D\xE9part</th><th>Arriv\xE9e</th>
            <th>Montant pay\xE9</th><th>Ranking</th><th>Commentaire</th><th>Chauffeur</th>
          </tr></thead><tbody>`;n.forEach(a=>{i+=`<tr>
              <td>${a.trip_id}</td>
              <td>${a.trip_date}</td>
              <td>${a.voyager_firstname} ${a.voyager_lastname}</td>
              <td>${a.start_city}</td>
              <td>${a.end_city}</td>
              <td>${a.price_per_passenger||"-"}</td>
              <td>${a.rating} \u2605</td>
              <td>${a.commentaire}</td>
              <td>${a.driver_firstname} ${a.driver_lastname}</td>
            </tr>`}),i+="</tbody></table>",s.innerHTML=i})}c();let h=document.getElementById("commentsFilterForm");h&&h.addEventListener("submit",function(e){e.preventDefault();let t={rating:this.rating.value,date_min:this.date_min.value,date_max:this.date_max.value};c(t)});let m=document.getElementById("chartCommissions");m&&typeof monthlyData<"u"&&new Chart(m,{type:"line",data:{labels:monthlyData.map(e=>e.month),datasets:[{label:"Cr\xE9dits collect\xE9s (par mois)",data:monthlyData.map(e=>parseFloat(e.total)),backgroundColor:"rgba(25, 135, 84, 0.2)",borderColor:"rgba(25, 135, 84, 1)",borderWidth:2}]},options:{scales:{y:{beginAtZero:!0,ticks:{callback:e=>e+" cr\xE9dits"}}}}}),(()=>{let e=document.getElementById("chartTripsByDay");e&&typeof tripsByDay<"u"&&new Chart(e,{type:"bar",data:{labels:tripsByDay.map(t=>t.day),datasets:[{label:"Trajets actifs",data:tripsByDay.map(t=>parseInt(t.valid_trips)),backgroundColor:"rgba(25, 135, 84, 0.7)"},{label:"Trajets annul\xE9s",data:tripsByDay.map(t=>parseInt(t.cancelled_trips)),backgroundColor:"rgba(220, 53, 69, 0.7)"}]},options:{responsive:!0,scales:{y:{beginAtZero:!0,title:{display:!0,text:"Nombre de trajets"}}}}})})()})();})();
