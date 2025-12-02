#!/bin/bash

# 1. Configuración de variables
REPO_URL="https://github.com/Nexus6Mx/taller-automotriz-app.git"
DB_CONFIG="api/config/database.php"

echo "--------------------------------------------------"
echo "Iniciando configuración de Git en Hostinger..."
echo "--------------------------------------------------"

# 2. Verificar si ya existe un repositorio git
if [ -d ".git" ]; then
    echo "¡Ya existe un repositorio Git aquí!"
else
    echo "Inicializando repositorio Git..."
    git init
    # Configurar rama por defecto a master para compatibilidad
    git branch -m master
fi

# 3. Configurar .gitignore para proteger la base de datos
echo "Configurando .gitignore..."
if [ -f ".gitignore" ]; then
    # Si existe, asegurarnos de que incluya el archivo de config
    if ! grep -q "$DB_CONFIG" ".gitignore"; then
        echo "" >> .gitignore
        echo "$DB_CONFIG" >> .gitignore
        echo "Se agregó $DB_CONFIG al .gitignore existente."
    fi
else
    # Si no existe, crearlo
    echo "$DB_CONFIG" > .gitignore
    echo "/vendor/" >> .gitignore
    echo "*.log" >> .gitignore
    echo ".env" >> .gitignore
    echo "Creado archivo .gitignore."
fi

# 4. Configurar identidad (Genérica para el servidor)
git config user.email "servidor@errautomotriz.online"
git config user.name "Servidor Produccion Hostinger"

# 5. Agregar archivos
echo "Agregando archivos al control de versiones..."
git add .

# 6. Commit inicial
echo "Creando commit de sincronización..."
git commit -m "Sincronización inicial desde Servidor de Producción (Hostinger)"

# 7. Configurar el remoto
echo "Configurando repositorio remoto..."
if git remote | grep -q "origin"; then
    git remote set-url origin "$REPO_URL"
else
    git remote add origin "$REPO_URL"
fi

echo "--------------------------------------------------"
echo "¡Configuración local completada!"
echo "--------------------------------------------------"
echo "PASO FINAL CRÍTICO:"
echo "Para subir los cambios a GitHub, necesitamos autenticación."
echo "Si usas HTTPS, te pedirá usuario y Token (PAT)."
echo "Si usas SSH, debes tener la llave configurada."
echo ""
echo "Intenta subir ejecutando: git push -u origin master --force"
echo "--------------------------------------------------"
