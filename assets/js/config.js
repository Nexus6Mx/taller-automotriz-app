// Frontend mejorado para la página de Configuración con modal
(function(){
    const token = localStorage.getItem('authToken');
    if (!token) {
        alert('Debes iniciar sesión como administrador para acceder a esta página.');
        window.location.href = '/login.html';
        return;
    }

    const state = { users: [], filter: '' };

    function headers(extra={}){
        return Object.assign({'Content-Type':'application/json','Authorization':'Bearer '+token}, extra);
    }

    async function api(path, opts={}){
        const res = await fetch(path, Object.assign({ headers: headers(), credentials: 'include' }, opts));
        if (res.status === 401 || res.status === 403) {
            let msg = 'No autorizado. Asegúrate de iniciar sesión con una cuenta con permisos.';
            let body = null;
            try { body = await res.json(); if (body && body.message) msg = body.message; } catch(_) {}

            // Retry once adding token in query string as fallback if header was stripped
            if (!opts.__retried && res.status === 401) {
                const t = localStorage.getItem('authToken');
                if (t) {
                    const url = new URL(path, window.location.origin);
                    url.searchParams.set('token', t);
                    const res2 = await fetch(url.toString(), Object.assign({ headers: headers(), credentials: 'include' }, { __retried: true }));
                    if (res2.ok) return res2.json();
                }
            }

            alert(msg);
            return null;
        }
        return res.json();
    }

    function openModal(mode, user){
        const modal = document.getElementById('user-modal');
        const title = document.getElementById('user-modal-title');
        const idEl = document.getElementById('user-id');
        const emailEl = document.getElementById('user-email');
        const emailHelp = document.getElementById('user-email-help');
        const roleEl = document.getElementById('user-role');
        const activeEl = document.getElementById('user-active');
        const pwdEl = document.getElementById('user-password');
        const errEl = document.getElementById('user-form-error');
        errEl.classList.add('hidden'); errEl.textContent='';

        if (mode === 'create'){
            title.textContent = 'Nuevo usuario';
            idEl.value = '';
            emailEl.value = '';
            emailEl.disabled = false;
            emailHelp.classList.remove('hidden');
            roleEl.value = 'Operador';
            activeEl.checked = true;
            pwdEl.value='';
        } else {
            title.textContent = 'Editar usuario';
            idEl.value = user.id;
            emailEl.value = user.email;
            emailEl.disabled = true;
            emailHelp.classList.remove('hidden');
            roleEl.value = user.role || 'Operador';
            activeEl.checked = user.active == 1;
            pwdEl.value='';
        }
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.dataset.mode = mode;
    }

    function closeModal(){
        const modal = document.getElementById('user-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.dataset.mode = '';
    }

    function renderUsers(){
        const tbody = document.getElementById('users-tbody');
        const q = state.filter.toLowerCase();
        tbody.innerHTML = '';
        state.users.filter(u => !q || u.email.toLowerCase().includes(q)).forEach(u => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td class="p-2">${u.id}</td>
                <td class="p-2">${u.email}</td>
                <td class="p-2">${u.role}</td>
                <td class="p-2">${u.active==1? 'Sí':'No'}</td>
                <td class="p-2 flex gap-2">
                    <button data-id="${u.id}" class="edit-btn bg-blue-500 text-white px-2 py-1 rounded">Editar</button>
                    <button data-id="${u.id}" class="del-btn bg-red-500 text-white px-2 py-1 rounded">Eliminar</button>
                </td>`;
            tbody.appendChild(tr);
        });
        bindRowActions();
    }

    function bindRowActions(){
        document.querySelectorAll('.del-btn').forEach(b=>b.addEventListener('click', async e=>{
            const id = Number(e.target.dataset.id);
            if (!confirm('Eliminar usuario ID '+id+'?')) return;
            await api('/api/users/delete.php', {method:'POST', body: JSON.stringify({id})});
            await loadUsers();
        }));
        document.querySelectorAll('.edit-btn').forEach(b=>b.addEventListener('click', async e=>{
            const id = Number(e.target.dataset.id);
            const user = state.users.find(u=>u.id==id);
            openModal('edit', user);
        }));
    }

    async function loadUsers(){
        const data = await api('/api/users/read.php');
        if (!data) return;
        state.users = data.data || [];
        renderUsers();
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

    // Listeners globales
    document.getElementById('user-search').addEventListener('input', (e)=>{
        state.filter = e.target.value || '';
        renderUsers();
    });

    document.getElementById('new-user-btn').addEventListener('click', ()=> openModal('create'));
    document.getElementById('user-cancel').addEventListener('click', closeModal);
    document.getElementById('user-modal').addEventListener('click', (e)=>{
        if (e.target.id === 'user-modal') closeModal();
    });

    // Guardar (crear/editar)
    document.getElementById('user-form').addEventListener('submit', async (e)=>{
        e.preventDefault();
        const mode = document.getElementById('user-modal').dataset.mode;
        const id = Number(document.getElementById('user-id').value);
        const email = document.getElementById('user-email').value.trim();
        const role = document.getElementById('user-role').value.trim();
        const active = document.getElementById('user-active').checked;
        const password = document.getElementById('user-password').value;
        const errEl = document.getElementById('user-form-error');
        errEl.classList.add('hidden'); errEl.textContent='';

        try {
            if (mode === 'create'){
                if (!email) throw new Error('Email requerido');
                if (!password) throw new Error('Contraseña requerida para crear');
                const res = await api('/api/users/create.php', {method:'POST', body: JSON.stringify({email, password, role, active})});
                if (!res) return;
                alert(res.message || 'Usuario creado');
            } else {
                const payload = { id, role, active };
                if (password) payload.password = password;
                const res = await api('/api/users/update.php', {method:'POST', body: JSON.stringify(payload)});
                if (!res) return;
                alert(res.message || 'Usuario actualizado');
            }
            closeModal();
            await loadUsers();
        } catch (err) {
            errEl.textContent = err.message || 'Error al guardar';
            errEl.classList.remove('hidden');
        }
    });

    (async function init(){
        await loadUsers();
        await loadLogs();
    })();
})();
