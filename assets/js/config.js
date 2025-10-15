// Frontend mínimo para la página de Configuración
(async function(){
    const token = localStorage.getItem('authToken');
    if (!token) {
        alert('Debes iniciar sesión como administrador para acceder a esta página.');
        window.location.href = '/login.html';
        return;
    }

    async function api(path, opts={}){
        opts.headers = Object.assign({'Content-Type':'application/json','Authorization':'Bearer '+token}, opts.headers||{});
        const res = await fetch(path, opts);
        if (res.status === 401 || res.status === 403) {
            alert('No autorizado. Asegúrate de iniciar sesión con una cuenta con permisos.');
            return null;
        }
        return res.json();
    }

    async function loadUsers(){
        const data = await api('/api/users/read.php');
        if (!data) return;
        const tbody = document.getElementById('users-tbody');
        tbody.innerHTML = '';
        data.data.forEach(u => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td class="p-2">${u.id}</td><td class="p-2">${u.email}</td><td class="p-2">${u.role}</td><td class="p-2">${u.active==1? 'Sí':'No'}</td><td class="p-2"><button data-id="${u.id}" class="edit-btn bg-blue-500 text-white px-2 py-1 rounded">Editar</button> <button data-id="${u.id}" class="del-btn bg-red-500 text-white px-2 py-1 rounded">Eliminar</button></td>`;
            tbody.appendChild(tr);
        });
        document.querySelectorAll('.del-btn').forEach(b=>b.addEventListener('click', async e=>{
            const id = e.target.dataset.id;
            if (!confirm('Eliminar usuario ID '+id+'?')) return;
            await api('/api/users/delete.php', {method:'POST', body: JSON.stringify({id})});
            loadUsers();
        }));
        document.querySelectorAll('.edit-btn').forEach(b=>b.addEventListener('click', async e=>{
            const id = Number(e.target.dataset.id);
            const row = e.target.closest('tr');
            const email = row.children[1].textContent.trim();
            const role = prompt('Rol (Administrador, Operador, Consulta):', row.children[2].textContent.trim());
            const activo = prompt('Activo? (1=Si,0=No):', row.children[3].textContent.trim());
            const newPwd = prompt('Nueva contraseña (opcional, dejar vacío para no cambiar):', '');
            const payload = { id, role, active: Number(activo)===1 };
            if (newPwd) payload.password = newPwd;
            const res = await api('/api/users/update.php', {method:'POST', body: JSON.stringify(payload)});
            if (res) alert(res.message || 'Actualizado');
            loadUsers();
        }));
    }

    async function loadLogs(){
        const data = await api('/api/users/logs.php');
        if (!data) return;
        const tbody = document.getElementById('logs-tbody');
        tbody.innerHTML = '';
        data.data.forEach(l=>{
            const tr = document.createElement('tr');
            tr.innerHTML = `<td class="p-2">${l.created_at}</td><td class="p-2">${l.actor_email||l.actor_user_id}</td><td class="p-2">${l.action}</td><td class="p-2">${l.details||''}</td>`;
            tbody.appendChild(tr);
        });
    }

    document.getElementById('new-user-btn').addEventListener('click', async ()=>{
        const email = prompt('Email del nuevo usuario');
        if (!email) return;
        const role = prompt('Rol (Administrador, Operador, Consulta)', 'Operador');
        const pwd = prompt('Contraseña temporal (se recomienda cambiarla luego)');
        const res = await api('/api/users/create.php', {method:'POST', body: JSON.stringify({email, password: pwd, role, active:1})});
        if (res) alert(res.message || 'OK');
        loadUsers();
    });

    await loadUsers();
    await loadLogs();
})();
