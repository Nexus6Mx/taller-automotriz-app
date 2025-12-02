<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tablero Kanban</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .kanban-board { display: block; padding: 1rem; }
        .kanban-column { width: 100%; margin-bottom: 1.5rem; max-height: none; display: flex; flex-direction: column; background-color: #f8f9fa; border-radius: 0.75rem; }
        @media (min-width: 768px) {
            .kanban-board { display: flex; gap: 1.5rem; padding: 1.5rem; overflow-x: auto; min-height: calc(100vh - 100px); align-items: flex-start; }
            .kanban-column { flex: 0 0 340px; margin-bottom: 0; max-height: calc(100vh - 150px); }
        }
        .column-header { padding: 0.75rem 1rem; font-weight: 600; border-top-left-radius: 0.75rem; border-top-right-radius: 0.75rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(0,0,0,0.05); cursor: grab; }
        .column-header:active { cursor: grabbing; }
        .column-header.wip-exceeded { background-color: #fee2e2 !important; color: #b91c1c; }
        .tasks-container { padding: 0.5rem; overflow-y: auto; flex-grow: 1; }
        .kanban-task { background-color: white; padding: 1rem; border-radius: 0.5rem; margin-bottom: 0.75rem; box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1); cursor: grab; }
        .sortable-ghost { opacity: 0.4; background: #c8ebfb; }
        .tasks-container::-webkit-scrollbar, .modal-body::-webkit-scrollbar { width: 8px; }
        .tasks-container::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        .tasks-container::-webkit-scrollbar-thumb, .modal-body::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
        .tab-button { padding: 0.5rem 1rem; border-bottom: 2px solid transparent; }
        .tab-button.active { border-color: #3b82f6; color: #3b82f6; }
        .tag { padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 500; }
        #appContainer.hidden, #authContainer.hidden { display: none; }
    </style>
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/phosphor-icons/1.4.2/css/phosphor.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

    <div id="authContainer" class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div><h2 id="authTitle" class="mt-6 text-center text-3xl font-extrabold text-gray-900">Iniciar sesión</h2></div>
            <form id="authForm" class="mt-8 space-y-6" onsubmit="return false;">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div><label for="username" class="sr-only">Nombre de usuario</label><input id="username" name="name" type="text" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Nombre de usuario"></div>
                    <div><label for="password" class="sr-only">Contraseña</label><input id="password" name="password" type="password" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Contraseña"></div>
                </div>
                <div id="authMessage" class="text-sm text-red-600"></div>
                <div><button id="submitAuthBtn" type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Entrar</button></div>
            </form>
            <p class="text-center text-sm"><a href="#" id="toggleAuthMode" class="font-medium text-indigo-600 hover:text-indigo-500">¿No tienes cuenta? Regístrate</a></p>
        </div>
    </div>

    <div id="appContainer" class="hidden">
        <header class="bg-white shadow-md p-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <select id="boardSelector" class="bg-gray-100 border-2 border-gray-200 rounded-lg text-xl font-bold p-2 focus:outline-none focus:border-blue-500"></select>
                <button id="editBoardBtn" class="flex items-center gap-1.5 text-gray-500 hover:text-blue-600 transition-colors duration-150">
                    <i class="ph-pencil-simple text-lg"></i>
                    <span class="text-sm font-medium">Editar</span>
                </button>
                <button id="newBoardBtn" class="flex items-center gap-1.5 text-gray-500 hover:text-blue-600 transition-colors duration-150">
                    <i class="ph-plus-circle text-lg"></i>
                    <span class="text-sm font-medium">Nuevo</span>
                </button>
            </div>
            <div class="flex items-center gap-4">
                <div class="relative"><i class="ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i><input type="text" id="searchInput" placeholder="Buscar tareas..." class="pl-10 pr-4 py-2 border rounded-lg"></div>
                <button id="addColumnBtn" class="bg-blue-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2"><i class="ph-plus"></i> Nueva Columna</button>
                <div id="userInfo" class="text-sm font-semibold text-gray-700"></div>
                <button id="logoutBtn" class="text-sm text-red-600 hover:underline">Cerrar Sesión</button>
            </div>
        </header>
        <main id="kanbanBoard" class="kanban-board"></main>
    </div>

    <div id="taskModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl h-[90vh] flex flex-col relative">
            <form id="taskForm" class="flex flex-col flex-grow">
                <div class="flex justify-between items-center p-4 border-b"><h2 id="taskModalTitle" class="text-2xl font-bold"></h2><button type="button" id="closeTaskModal" class="text-gray-500 text-2xl">&times;</button></div>
                <div class="flex flex-grow overflow-hidden pb-20">
                    <div class="w-2/3 p-6 overflow-y-auto modal-body">
                        <input type="hidden" id="taskId" name="id"><input type="hidden" id="taskColumnId" name="column_id">
                        <div class="mb-4"><label for="taskTitle" class="block text-sm font-medium text-gray-700 mb-1">Título</label><input type="text" id="taskTitle" name="title" class="w-full border-gray-300 rounded-lg" required></div>
                        <div class="mb-4"><label for="taskDescription" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label><textarea id="taskDescription" name="description" rows="5" class="w-full border-gray-300 rounded-lg"></textarea></div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="mb-4">
                                <label for="taskPriority" class="block text-sm font-medium text-gray-700 mb-1">Prioridad</label>
                                <select id="taskPriority" name="priority" class="w-full border-gray-300 rounded-lg">
                                    <option value="Baja">Baja</option>
                                    <option value="Media">Media</option>
                                    <option value="Alta">Alta</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="taskDueDate" class="block text-sm font-medium text-gray-700 mb-1">Fecha Límite</label>
                                <input type="date" id="taskDueDate" name="due_date" class="w-full border-gray-300 rounded-lg">
                            </div>
                        </div>
                        <div class="border-t pt-4">
                            <div class="flex border-b mb-4">
                                <button type="button" class="tab-button active" data-tab="subtasks"><i class="ph-check-square-offset mr-1"></i> Subtareas</button>
                                <button type="button" class="tab-button" data-tab="attachments"><i class="ph-paperclip mr-1"></i> Adjuntos</button>
                                <button type="button" class="tab-button" data-tab="comments"><i class="ph-chats mr-1"></i> Comentarios</button>
                            </div>
                            <div id="tab-content">
                                <div id="subtasks-tab" class="tab-pane"></div>
                                <div id="attachments-tab" class="tab-pane hidden">
                                    <ul id="attachment-list" class="space-y-3"></ul>
                                    <button type="button" id="addAttachmentBtn" class="mt-4 bg-gray-200 text-gray-700 font-semibold px-4 py-2 rounded-lg hover:bg-gray-300 flex items-center gap-2"><i class="ph-plus"></i> Añadir adjunto</button>
                                </div>
                                <div id="comments-tab" class="tab-pane hidden"></div>
                            </div>
                        </div>
                    </div>
                    <div class="w-1/3 bg-gray-50 p-6 border-l overflow-y-auto modal-body"></div>
                </div>
                <div class="absolute bottom-0 left-0 right-0 flex justify-end gap-3 p-4 bg-gray-50 border-t"><button type="button" id="deleteTaskBtn" class="bg-red-600 text-white font-semibold px-4 py-2 rounded-lg">Eliminar</button><button type="submit" id="saveTaskBtn" class="bg-blue-600 text-white font-semibold px-4 py-2 rounded-lg">Guardar Cambios</button></div>
            </form>
            <form id="attachmentForm" class="hidden"><input type="file" id="attachmentFile" name="attachmentFile" accept=".pdf,image/*,.doc,.docx,.xls,.xlsx,.ppt,.pptx"/></form>
        </div>
    </div>
    <div id="columnModal" class="fixed inset-0 bg-gray-800 bg-opacity-60 hidden items-center justify-center z-50 transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md transform transition-all duration-300 scale-95">
            <form id="columnForm">
                <div class="flex justify-between items-center p-5 border-b border-gray-200">
                    <h2 id="columnModalTitle" class="text-xl font-semibold text-gray-800"></h2>
                    <button type="button" id="closeColumnModal" class="text-gray-400 hover:text-gray-600 transition-colors"><i class="ph-x text-2xl"></i></button>
                </div>
                <div class="p-6 space-y-5">
                    <div>
                        <label for="columnTitle" class="block text-sm font-medium text-gray-600 mb-1">Título de la Columna</label>
                        <input type="text" id="columnTitle" name="title" class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow" required>
                    </div>
                    <div>
                        <label for="columnColor" class="block text-sm font-medium text-gray-600 mb-1">Color de cabecera</label>
                        <div class="flex items-center gap-3">
                            <input type="color" id="columnColor" name="color" value="#E0E0E0" class="w-12 h-10 p-1 bg-gray-50 border border-gray-300 rounded-lg cursor-pointer">
                            <span id="colorValueText" class="font-mono text-gray-500">#E0E0E0</span>
                        </div>
                    </div>
                    <div>
                        <label for="wipLimit" class="block text-sm font-medium text-gray-600 mb-1">Límite de Tareas (WIP)</label>
                        <input type="number" id="wipLimit" name="wip_limit" min="0" placeholder="0 = sin límite" class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow">
                    </div>
                </div>
                <div class="flex justify-between items-center p-5 bg-gray-50 rounded-b-xl">
                    <button type="button" id="deleteColumnBtn" class="text-sm font-semibold text-red-600 hover:text-red-800 transition-colors">Eliminar Columna</button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-transform transform hover:scale-105">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    
<script>
document.addEventListener('DOMContentLoaded', () => {
    // State
    let boardData = { columns: [], users: [], tags: [] };
    let boards = [];
    let currentBoardId = null;
    let currentUser = null;
    let sortableInstances = [];
    let isLoginMode = true;

    // DOM Elements
    const appContainer = document.getElementById('appContainer');
    const authContainer = document.getElementById('authContainer');
    const authForm = document.getElementById('authForm');
    const kanbanBoard = document.getElementById('kanbanBoard');
    const boardSelector = document.getElementById('boardSelector');
    const columnModal = document.getElementById('columnModal');
    const taskModal = document.getElementById('taskModal');

    // --- API Call ---
    async function apiCall(action, method = 'POST', body = null, params = null) {
        let url = `api.php?action=${action}`;
        if (params) url += '&' + new URLSearchParams(params).toString();
        try {
            const options = { method, headers: body ? { 'Content-Type': 'application/json' } : {}, body: body ? JSON.stringify(body) : null };
            const response = await fetch(url, options);
            const responseData = await response.json();
            if (!response.ok) throw new Error(responseData.message || `HTTP ${response.status}`);
            if (responseData.status === 'error') throw new Error(responseData.message);
            return responseData;
        } catch (error) {
            console.error('API Call Error:', action, error);
            if (error.message.includes('Debes iniciar sesión')) {
                alert('ERROR DE SESIÓN: El servidor no está guardando la sesión correctamente. Contacta al administrador del servidor sobre la configuración de sesiones de PHP.');
                showLogin(); 
            }
            throw error;
        }
    }

    // --- Auth ---
    function showApp() {
        authContainer.classList.add('hidden');
        appContainer.classList.remove('hidden');
        document.getElementById('userInfo').innerHTML = `Usuario: <b class="text-indigo-600">${currentUser.name}</b>`;
        loadUserBoards();
    }
    function showLogin() {
        appContainer.classList.add('hidden');
        authContainer.classList.remove('hidden');
        currentUser = null;
        localStorage.clear();
    }
    function checkLoginStatus() {
        const storedUser = localStorage.getItem('kanban_user');
        if (storedUser) { currentUser = JSON.parse(storedUser); showApp(); } 
        else { showLogin(); }
    }

    // --- Board Management ---
    async function loadUserBoards() {
        try {
            const res = await apiCall('get_boards', 'GET');
            boards = res.data;
            renderBoardSelector();
            if (boards.length === 0) {
                await handleNewBoard();
                if(boards.length === 0) {
                    kanbanBoard.innerHTML = `<div class="text-center p-8"><h2 class="text-2xl font-bold mb-4">¡Bienvenido!</h2><p class="text-gray-600">Crea tu primer tablero para empezar a organizarte.</p></div>`;
                    return;
                }
            }
            const lastBoardId = localStorage.getItem('kanban_last_board');
            currentBoardId = boards.find(b => b.id == lastBoardId) ? lastBoardId : boards[0].id;
            boardSelector.value = currentBoardId;
            await initializeBoard(currentBoardId);
        } catch (error) {
            kanbanBoard.innerHTML = `<p class="text-red-500 text-center p-8">Error al cargar tableros.</p>`;
        }
    }
    function renderBoardSelector() {
        boardSelector.innerHTML = boards.map(b => `<option value="${b.id}">${b.name}</option>`).join('');
    }
    async function handleNewBoard() {
        const boardName = prompt("Nombre del nuevo tablero:");
        if (boardName && boardName.trim()) {
            try {
                const res = await apiCall('create_board', 'POST', { name: boardName.trim() });
                boards.push(res.data);
                renderBoardSelector();
                boardSelector.value = res.data.id;
                await boardSelector.dispatchEvent(new Event('change'));
            } catch (error) { alert("Error al crear el tablero: " + error.message); }
        }
    }

    // --- Board Rendering ---
    async function initializeBoard(boardId) {
        if (!boardId) return;
        try {
            const response = await apiCall('get_board_data', 'GET', null, { board_id: boardId });
            boardData = response.data;
            renderBoard();
        } catch(error) { 
            console.error(error); 
            kanbanBoard.innerHTML = `<p class="text-red-500 text-center p-8">Error al cargar los datos del tablero.</p>`;
        }
    }
    function renderBoard() {
        kanbanBoard.innerHTML = '';
        sortableInstances.forEach(s => s.destroy());
        sortableInstances = [];
        if (!boardData || !boardData.columns || boardData.columns.length === 0) {
            kanbanBoard.innerHTML = '<p class="text-center text-gray-500 w-full mt-10">El tablero está vacío. ¡Añade una nueva columna para empezar!</p>';
            return;
        }
        Sortable.create(kanbanBoard, { animation: 150, handle: '.column-header', ghostClass: 'sortable-ghost', onEnd: handleColumnMove });
        boardData.columns.forEach(column => {
            const wipLimit = column.wip_limit || 0;
            const taskCount = column.tasks.length;
            const wipExceeded = wipLimit > 0 && taskCount > wipLimit;
            const columnEl = document.createElement('div');
            columnEl.className = 'kanban-column';
            columnEl.dataset.columnId = column.id;
            columnEl.innerHTML = `
                <div class="column-header ${wipExceeded ? 'wip-exceeded' : ''}" style="background-color: ${column.color};">
                    <span class="column-title-text">${column.title}</span>
                    <div class="flex items-center gap-2">
                         <span class="text-sm font-normal bg-black bg-opacity-10 rounded-full px-2 py-0.5">${taskCount}${wipLimit > 0 ? '/' + wipLimit : ''}</span>
                        <button type="button" class="edit-column-btn text-gray-600 hover:text-black p-1"><i class="ph-pencil-simple"></i></button>
                    </div>
                </div>
                <div class="tasks-container" data-column-id="${column.id}">${column.tasks.map(renderTask).join('')}</div>
                <button type="button" class="add-task-btn p-2 m-2 text-gray-500 hover:text-blue-600 hover:bg-blue-100 rounded-md">Añadir tarea</button>`;
            kanbanBoard.appendChild(columnEl);
            const tasksContainer = columnEl.querySelector('.tasks-container');
            sortableInstances.push(Sortable.create(tasksContainer, { group: 'tasks', animation: 150, ghostClass: 'sortable-ghost', onEnd: handleTaskMove }));
        });
    }
    function renderTask(task) {
        const completedSubtasks = task.subtasks.filter(st => st.is_completed == 1).length;
        const totalSubtasks = task.subtasks.length;

        // Date logic
        let dueDateDisplay = '';
        if (task.due_date) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            // The backend returns YYYY-MM-DD. new Date() will parse it as UTC midnight.
            // To avoid timezone issues, add T00:00:00 to make it explicit local time at midnight.
            const dueDate = new Date(task.due_date + 'T00:00:00');

            let dueDateClass = '';
            if (dueDate.getTime() < today.getTime()) {
                dueDateClass = 'text-red-600 font-semibold'; // Overdue
            } else {
                dueDateClass = 'text-green-600'; // Upcoming or due today
            }
            dueDateDisplay = `<span class="flex items-center gap-1 ${dueDateClass}"><i class="ph-calendar"></i> ${dueDate.toLocaleDateString()}</span>`;
        }

        const priorityColors = {
            'Alta': 'bg-red-500 text-white',
            'Media': 'bg-yellow-500 text-white',
            'Baja': 'bg-green-500 text-white'
        };
        const priorityBadge = task.priority ? `<span class="px-2 py-1 text-xs font-semibold rounded-full ${priorityColors[task.priority]}">${task.priority}</span>` : '';

        return `<div class="kanban-task" data-task-id="${task.id}" style="border-left: 5px solid ${task.color};">
            <div class="flex flex-wrap gap-1 mb-2">
                ${priorityBadge}
                ${task.tags.map(tag => `<span class="tag text-white" style="background-color:${tag.color}">${tag.name}</span>`).join('')}
            </div>
            <p class="font-semibold mb-2">${task.title}</p>
            <div class="flex justify-between items-center text-sm text-gray-600">
                <div class="flex items-center gap-2 flex-wrap">
                    ${dueDateDisplay}
                    ${totalSubtasks > 0 ? `<span class="flex items-center gap-1 ${completedSubtasks === totalSubtasks ? 'text-green-600' : ''}"><i class="ph-check-square-offset"></i> ${completedSubtasks}/${totalSubtasks}</span>` : ''}
                    ${task.attachments.length > 0 ? `<span class="flex items-center gap-1"><i class="ph-paperclip"></i> ${task.attachments.length}</span>` : ''}
                    ${task.comments.length > 0 ? `<span class="flex items-center gap-1"><i class="ph-chats"></i> ${task.comments.length}</span>` : ''}
                </div>
                ${task.user_name ? `<span class="bg-gray-200 rounded-full px-2 py-0.5 text-xs whitespace-nowrap">${task.user_name}</span>` : ''}
            </div>
        </div>`;
    }

    // --- Drag & Drop Handlers ---
    async function handleColumnMove(event) {
        const { oldIndex, newIndex } = event;
        const [movedColumn] = boardData.columns.splice(oldIndex, 1);
        boardData.columns.splice(newIndex, 0, movedColumn);
        const columnOrder = boardData.columns.map(col => col.id);
        try { await apiCall('update_column_order', 'POST', { order: columnOrder }); } 
        catch (error) { await initializeBoard(currentBoardId); }
    }
    async function handleTaskMove(event) {
        const taskId = event.item.dataset.taskId;
        const newColumnId = event.to.dataset.columnId;
        const oldColumnId = event.from.dataset.columnId;
        const task = findTask(taskId);
        if (!task) return;
        findColumn(oldColumnId).tasks.splice(event.oldIndex, 1);
        findColumn(newColumnId).tasks.splice(event.newIndex, 0, task);
        task.column_id = newColumnId;
    try { await apiCall('move_task', 'POST', { taskId: Number(taskId), newColumnId: Number(newColumnId), newIndex: event.newIndex, oldColumnId: Number(oldColumnId), oldIndex: event.oldIndex }); }
        catch (error) { await initializeBoard(currentBoardId); }
    }

    // --- Modals & Event Listeners ---
    function openColumnModal(column = null) {
        const form = document.getElementById('columnForm');
        if (form) form.reset();

        const titleEl = document.getElementById('columnModalTitle');
        const columnIdEl = document.getElementById('columnId');
        const columnTitleEl = document.getElementById('columnTitle');
        const columnColorEl = document.getElementById('columnColor');
        const wipLimitEl = document.getElementById('wipLimit');
        const deleteBtnEl = document.getElementById('deleteColumnBtn');
        const colorValueTextEl = document.getElementById('colorValueText');

        if (titleEl) titleEl.textContent = column ? 'Editar Columna' : 'Nueva Columna';
        if (deleteBtnEl) deleteBtnEl.style.display = column ? 'block' : 'none';
        if (colorValueTextEl) colorValueTextEl.textContent = column ? column.color.toUpperCase() : '#E0E0E0';

        if (column) {
            if (columnIdEl) columnIdEl.value = column.id;
            if (columnTitleEl) columnTitleEl.value = column.title;
            if (columnColorEl) columnColorEl.value = column.color;
            if (wipLimitEl) wipLimitEl.value = column.wip_limit || '';
        } else {
            if (columnIdEl) columnIdEl.value = '';
            if (columnColorEl) columnColorEl.value = '#E0E0E0';
            if (wipLimitEl) wipLimitEl.value = '';
        }

        if (columnModal) {
            columnModal.classList.remove('hidden');
            columnModal.classList.add('flex');
        }
    }
    function closeColumnModal() { columnModal.classList.add('hidden'); columnModal.classList.remove('flex'); }

    function openTaskModal(task = null, columnId = null) {
        const form = document.getElementById('taskForm');
        form.reset();
        const modalTitle = document.getElementById('taskModalTitle');
        const taskIdInput = document.getElementById('taskId');
        const taskColumnIdInput = document.getElementById('taskColumnId');
        const taskTitleInput = document.getElementById('taskTitle');
        const taskDescriptionInput = document.getElementById('taskDescription');
        const taskPriorityInput = document.getElementById('taskPriority');
        const taskDueDateInput = document.getElementById('taskDueDate');
        const deleteTaskBtn = document.getElementById('deleteTaskBtn');

        if (task) {
            modalTitle.textContent = 'Editar Tarea';
            taskIdInput.value = task.id;
            taskColumnIdInput.value = task.column_id;
            taskTitleInput.value = task.title;
            taskDescriptionInput.value = task.description;
            taskPriorityInput.value = task.priority;
            taskDueDateInput.value = task.due_date;
            deleteTaskBtn.style.display = 'block';
            renderSubtasks(task);
            renderAttachments(task);
            renderComments(task);
        } else {
            modalTitle.textContent = 'Nueva Tarea';
            taskIdInput.value = '';
            taskColumnIdInput.value = columnId;
            taskTitleInput.value = '';
            taskDescriptionInput.value = '';
            taskPriorityInput.value = 'Media';
            taskDueDateInput.value = '';
            deleteTaskBtn.style.display = 'none';
            document.getElementById('subtasks-tab').innerHTML = '<p class="text-gray-500">Guarda la tarea para poder añadir subtareas.</p>';
            document.getElementById('attachments-tab').innerHTML = '<p class="text-gray-500">Guarda la tarea para poder añadir adjuntos.</p>';
            document.getElementById('comments-tab').innerHTML = '<p class="text-gray-500">Guarda la tarea para poder añadir comentarios.</p>';
        }

        taskModal.classList.remove('hidden');
        taskModal.classList.add('flex');
    }

    function renderSubtasks(task) {
        const container = document.getElementById('subtasks-tab');
        if (!container) return;

        let subtasksHTML = task.subtasks.map(st => `
            <div class="subtask-item flex items-center justify-between p-2 border-b" data-subtask-id="${st.id}">
                <div class="flex items-center">
                    <input type="checkbox" class="subtask-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" ${st.is_completed == 1 ? 'checked' : ''}>
                    <span class="ml-3 ${st.is_completed == 1 ? 'line-through text-gray-500' : ''}">${st.title}</span>
                </div>
                <button type="button" class="delete-subtask-btn text-gray-400 hover:text-red-600"><i class="ph-trash"></i></button>
            </div>
        `).join('');

        const addSubtaskFormHTML = `
            <div id="addSubtaskContainer" class="flex gap-2 mt-4">
                <input type="text" id="newSubtaskTitle" class="flex-grow border-gray-300 rounded-lg text-sm" placeholder="Añadir nueva subtarea...">
                <button type="button" id="addSubtaskBtn" class="bg-blue-500 text-white px-3 py-1 rounded-lg hover:bg-blue-600 text-sm font-semibold">Añadir</button>
            </div>
        `;

        container.innerHTML = subtasksHTML + addSubtaskFormHTML;
    }

    function renderAttachments(task) {
        const container = document.getElementById('attachment-list');
        if (!container) return;
        container.innerHTML = task.attachments.map(file => `
            <li class="flex items-center justify-between bg-gray-100 p-2 rounded-lg" data-attachment-id="${file.id}">
                <a href="${file.file_path}" target="_blank" class="flex items-center gap-2 text-blue-600 hover:underline">
                    <i class="ph-file-text text-xl"></i>
                    <span>${file.file_name}</span>
                </a>
                <button type="button" class="delete-attachment-btn text-gray-400 hover:text-red-600"><i class="ph-trash"></i></button>
            </li>
        `).join('');
    }

    function renderComments(task) {
        const container = document.getElementById('comments-tab');
        if (!container) return;

        let commentsHTML = '<div id="comment-list" class="space-y-4">';
        commentsHTML += task.comments.map(comment => `
            <div class="comment-item" data-comment-id="${comment.id}">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center font-bold text-gray-600 text-sm">${comment.user_name ? comment.user_name.charAt(0).toUpperCase() : 'A'}</div>
                    <div class="flex-1">
                        <p class="font-semibold text-sm">${comment.user_name || 'Anónimo'}</p>
                        <div class="comment-content text-gray-700">${comment.comment}</div>
                        <div class="text-xs text-gray-400 mt-1">
                            <span>${new Date(comment.created_at).toLocaleString()}</span>
                            <button type="button" class="edit-comment-btn ml-2 font-semibold text-blue-500 hover:underline">Editar</button>
                            <button type="button" class="delete-comment-btn ml-2 font-semibold text-red-500 hover:underline">Eliminar</button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
        commentsHTML += '</div>';

        const addCommentFormHTML = `
            <div id="addCommentContainer" class="mt-6 pt-4 border-t">
                <textarea id="newCommentText" class="w-full border-gray-300 rounded-lg" rows="3" placeholder="Escribe un comentario..."></textarea>
                <button type="button" id="addCommentBtn" class="mt-2 bg-blue-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-blue-700">Añadir Comentario</button>
            </div>
        `;

        container.innerHTML = commentsHTML + addCommentFormHTML;
    }

    async function handleFileUpload(file) {
        const taskId = document.getElementById('taskId').value;
        if (!taskId || !file) return;

        const formData = new FormData();
        formData.append('action', 'upload_attachment');
        formData.append('task_id', taskId);
        formData.append('attachmentFile', file);

        try {
            const response = await fetch('api.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.status === 'success') {
                const task = findTask(taskId);
                task.attachments.push(result.file);
                renderAttachments(task);
                renderBoard(); // Re-render to update attachment counts
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            alert(`Error al subir el archivo: ${error.message}`);
        }
    }

    document.getElementById('addAttachmentBtn').addEventListener('click', () => {
        document.getElementById('attachmentFile').click();
    });

    document.getElementById('attachmentFile').addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFileUpload(e.target.files[0]);
        }
    });

    function closeTaskModal() {
        taskModal.classList.add('hidden');
        taskModal.classList.remove('flex');
    }

    // --- Helper Functions ---
    function findTask(taskId) { for (const col of boardData.columns) { const task = col.tasks.find(t => t.id == taskId); if (task) return task; } return null; }
    function findColumn(columnId) { return boardData.columns.find(c => c.id == columnId); }

    // --- Event Listeners Init ---
    document.getElementById('closeTaskModal').addEventListener('click', closeTaskModal);

    document.getElementById('deleteTaskBtn').addEventListener('click', async () => {
        const taskId = document.getElementById('taskId').value;
        if (!taskId) {
            alert('No se ha podido encontrar el ID de la tarea.');
            return;
        }

        if (confirm('¿Estás seguro de que quieres eliminar esta tarea? Todas las subtareas, adjuntos y comentarios asociados se perderán permanentemente.')) {
            try {
                await apiCall('delete_task', 'POST', { id: taskId });
                closeTaskModal();
                await initializeBoard(currentBoardId); // Refresh the board
            } catch (error) {
                alert('Error al eliminar la tarea: ' + error.message);
            }
        }
    });

    taskModal.addEventListener('click', e => {
        const tabButton = e.target.closest('.tab-button');
        if (tabButton) {
            const tabName = tabButton.dataset.tab;
            taskModal.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            taskModal.querySelectorAll('.tab-pane').forEach(pane => pane.classList.add('hidden'));
            tabButton.classList.add('active');
            const activePane = document.getElementById(`${tabName}-tab`);
            if (activePane) {
                activePane.classList.remove('hidden');
            }
        }

        const deleteAttachmentBtn = e.target.closest('.delete-attachment-btn');
        if (deleteAttachmentBtn) {
            const attachmentItem = deleteAttachmentBtn.closest('[data-attachment-id]');
            const attachmentId = attachmentItem.dataset.attachmentId;
            if (confirm('¿Estás seguro de que quieres eliminar este archivo adjunto?')) {
                apiCall('delete_attachment', 'POST', { id: attachmentId })
                    .then(() => {
                        const taskId = document.getElementById('taskId').value;
                        const task = findTask(taskId);
                        task.attachments = task.attachments.filter(att => att.id != attachmentId);
                        attachmentItem.remove();
                        renderBoard(); // Re-render to update attachment counts
                    })
                    .catch(error => alert('Error al eliminar el archivo adjunto: ' + error.message));
            }
        }

        const deleteSubtaskBtn = e.target.closest('.delete-subtask-btn');
        if (deleteSubtaskBtn) {
            const subtaskItem = deleteSubtaskBtn.closest('.subtask-item');
            const subtaskId = subtaskItem.dataset.subtaskId;
            if (confirm('¿Estás seguro de que quieres eliminar esta subtarea?')) {
                apiCall('delete_subtask', 'POST', { id: subtaskId })
                    .then(() => {
                        const taskId = document.getElementById('taskId').value;
                        const task = findTask(taskId);
                        task.subtasks = task.subtasks.filter(st => st.id != subtaskId);
                        subtaskItem.remove();
                        renderBoard(); // Re-render to update subtask counts
                    })
                    .catch(error => alert('Error al eliminar la subtarea: ' + error.message));
            }
        }

        const addSubtaskBtn = e.target.closest('#addSubtaskBtn');
        if (addSubtaskBtn) {
            const taskId = document.getElementById('taskId').value;
            const titleInput = document.getElementById('newSubtaskTitle');
            const title = titleInput.value.trim();

            if (title) {
                apiCall('add_subtask', 'POST', { task_id: taskId, title: title })
                    .then(res => {
                        const task = findTask(taskId);
                        task.subtasks.push(res.data);
                        renderSubtasks(task);
                        renderBoard(); // Re-render to update subtask counts
                    })
                    .catch(error => alert('Error al añadir la subtarea: ' + error.message));
            }
        }

        const addCommentBtn = e.target.closest('#addCommentBtn');
        if (addCommentBtn) {
            const taskId = document.getElementById('taskId').value;
            const commentText = document.getElementById('newCommentText').value.trim();
            if (commentText) {
                apiCall('add_comment', 'POST', { task_id: taskId, comment: commentText, user_id: currentUser.id })
                    .then(res => {
                        const task = findTask(taskId);
                        // The backend should return the full comment object
                        // For now, we'll create a temporary one.
                        const newComment = {
                            id: res.id,
                            comment: commentText,
                            user_name: currentUser.name,
                            created_at: new Date().toISOString()
                        };
                        task.comments.push(newComment);
                        renderComments(task);
                        renderBoard(); // to update comment count on task card
                    })
                    .catch(error => alert('Error al añadir el comentario: ' + error.message));
            }
        }

        const editCommentBtn = e.target.closest('.edit-comment-btn');
        if (editCommentBtn) {
            const commentItem = editCommentBtn.closest('.comment-item');
            const commentContent = commentItem.querySelector('.comment-content');
            const originalText = commentContent.textContent;

            commentContent.innerHTML = `
                <textarea class="w-full border-gray-300 rounded-lg text-sm">${originalText}</textarea>
                <div class="mt-1">
                    <button type="button" class="save-comment-btn bg-blue-500 text-white px-2 py-1 rounded text-xs">Guardar</button>
                    <button type="button" class="cancel-edit-comment-btn text-gray-500 px-2 py-1 rounded text-xs">Cancelar</button>
                </div>
            `;
        }

        const saveCommentBtn = e.target.closest('.save-comment-btn');
        if (saveCommentBtn) {
            const commentItem = saveCommentBtn.closest('.comment-item');
            const commentId = commentItem.dataset.commentId;
            const newText = commentItem.querySelector('textarea').value.trim();
            if (newText) {
                apiCall('update_comment', 'POST', { id: commentId, comment: newText })
                    .then(() => {
                        const taskId = document.getElementById('taskId').value;
                        const task = findTask(taskId);
                        const comment = task.comments.find(c => c.id == commentId);
                        comment.comment = newText;
                        renderComments(task);
                    })
                    .catch(error => alert('Error al guardar el comentario: ' + error.message));
            }
        }

        const cancelEditCommentBtn = e.target.closest('.cancel-edit-comment-btn');
        if (cancelEditCommentBtn) {
            const taskId = document.getElementById('taskId').value;
            const task = findTask(taskId);
            renderComments(task);
        }

        const deleteCommentBtn = e.target.closest('.delete-comment-btn');
        if (deleteCommentBtn) {
            const commentItem = deleteCommentBtn.closest('.comment-item');
            const commentId = commentItem.dataset.commentId;
            if (confirm('¿Estás seguro de que quieres eliminar este comentario?')) {
                apiCall('delete_comment', 'POST', { id: commentId })
                    .then(() => {
                        const taskId = document.getElementById('taskId').value;
                        const task = findTask(taskId);
                        task.comments = task.comments.filter(c => c.id != commentId);
                        renderComments(task);
                        renderBoard(); // to update comment count on task card
                    })
                    .catch(error => alert('Error al eliminar el comentario: ' + error.message));
            }
        }
    });

    taskModal.addEventListener('change', e => {
        const subtaskCheckbox = e.target.closest('.subtask-checkbox');
        if (subtaskCheckbox) {
            const subtaskItem = subtaskCheckbox.closest('.subtask-item');
            const subtaskId = subtaskItem.dataset.subtaskId;
            const is_completed = subtaskCheckbox.checked;
            
            apiCall('update_subtask', 'POST', { id: subtaskId, is_completed: is_completed ? 1 : 0 })
                .then(() => {
                    const taskId = document.getElementById('taskId').value;
                    const task = findTask(taskId);
                    const subtask = task.subtasks.find(st => st.id == subtaskId);
                    subtask.is_completed = is_completed ? 1 : 0;
                    
                    const span = subtaskItem.querySelector('span');
                    span.classList.toggle('line-through', is_completed);
                    span.classList.toggle('text-gray-500', is_completed);
                    renderBoard(); // Re-render to update subtask counts
                })
                .catch(error => {
                    alert('Error al actualizar la subtarea: ' + error.message);
                    subtaskCheckbox.checked = !is_completed; // Revert on failure
                });
        }
    });

    authForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(authForm).entries());
        const action = isLoginMode ? 'login' : 'register_user';
        try {
            const res = await apiCall(action, 'POST', data);
            if (isLoginMode && res.status === 'success') {
                currentUser = res.user;
                localStorage.setItem('kanban_user', JSON.stringify(currentUser));
                showApp();
            } else if (!isLoginMode && res.status === 'success') {
                document.getElementById('authMessage').className = 'text-sm text-green-600';
                document.getElementById('authMessage').textContent = '¡Registro exitoso! Por favor, inicia sesión.';
                document.getElementById('toggleAuthMode').click();
            }
        } catch (error) {
            document.getElementById('authMessage').className = 'text-sm text-red-600';
            document.getElementById('authMessage').textContent = error.message;
        }
    });
    document.getElementById('toggleAuthMode').addEventListener('click', (e) => { /* ... */ });
    document.getElementById('logoutBtn').addEventListener('click', () => apiCall('logout', 'POST').finally(showLogin));
    boardSelector.addEventListener('change', async () => {
        currentBoardId = boardSelector.value;
        localStorage.setItem('kanban_last_board', currentBoardId);
        await initializeBoard(currentBoardId);
    });
    document.getElementById('newBoardBtn').addEventListener('click', handleNewBoard);
    document.getElementById('editBoardBtn').addEventListener('click', async () => {
        if (!currentBoardId) return;
        const currentBoard = boards.find(b => b.id == currentBoardId);
        const newName = prompt("Nuevo nombre para el tablero:", currentBoard.name);
        if (newName && newName.trim() && newName.trim() !== currentBoard.name) {
            try {
                await apiCall('update_board', 'POST', { id: currentBoardId, name: newName.trim() });
                await loadUserBoards();
            } catch (error) { alert("Error al actualizar: " + error.message); }
        }
    });
    document.getElementById('addColumnBtn').addEventListener('click', () => openColumnModal());
    document.getElementById('closeColumnModal').addEventListener('click', closeColumnModal);
    document.getElementById('columnColor').addEventListener('input', (e) => { document.getElementById('colorValueText').textContent = e.target.value.toUpperCase(); });
    document.getElementById('columnForm').addEventListener('submit', async e => {
        e.preventDefault();
        const columnData = Object.fromEntries(new FormData(e.target).entries());
        if (!columnData.id) columnData.board_id = currentBoardId;
        const action = columnData.id ? 'update_column' : 'add_column';
        try {
            const res = await apiCall(action, 'POST', columnData);
            if (res.status === 'success') {
                closeColumnModal();
                await initializeBoard(currentBoardId);
            }
        } catch (error) { alert(`Error al guardar la columna: ${error.message}`); }
    });

    document.getElementById('taskForm').addEventListener('submit', async e => {
        e.preventDefault();
        const taskData = Object.fromEntries(new FormData(e.target).entries());
        const action = taskData.id ? 'update_task' : 'add_task';
        try {
            const res = await apiCall(action, 'POST', taskData);
            if (res.status === 'success') {
                closeTaskModal();
                await initializeBoard(currentBoardId);
            }
        } catch (error) { alert(`Error al guardar la tarea: ${error.message}`); }
    });
    kanbanBoard.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.edit-column-btn');
        const addBtn = e.target.closest('.add-task-btn');
        const taskEl = e.target.closest('.kanban-task');
        if (editBtn) {
            openColumnModal(findColumn(editBtn.closest('.kanban-column').dataset.columnId));
        } else if (addBtn) {
            openTaskModal(null, addBtn.closest('.kanban-column').dataset.columnId);
        } else if (taskEl) {
            openTaskModal(findTask(taskEl.dataset.taskId));
        }
    });

    // --- Init ---
    checkLoginStatus();
});
</script>
</body>
</html>