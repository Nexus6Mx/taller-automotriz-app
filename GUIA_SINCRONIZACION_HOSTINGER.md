# Guía de Sincronización: Hostinger -> GitHub

Esta guía detalla los pasos para configurar tu servidor de producción (Hostinger) para que sincronice su código con GitHub, protegiendo tus credenciales de base de datos.

## Prerrequisitos
1.  **Acceso SSH** a tu cuenta de Hostinger.
2.  Tener a la mano tu **Personal Access Token (PAT)** de GitHub (ya que las contraseñas normales ya no funcionan para la terminal).

---

## Paso 1: Conectarse por SSH
Abre tu terminal (PowerShell o CMD) y conéctate a tu servidor. El comando suele ser algo como:
```bash
ssh u123456789@tudominio.com -P 65002
```
*(Revisa tu panel de Hostinger en la sección "Acceso SSH" para obtener el comando exacto y la contraseña).*

---

## Paso 2: Preparar el entorno
Una vez dentro de la terminal de Hostinger, navega a la carpeta pública (usualmente `public_html`):
```bash
cd public_html
```

## Paso 3: Ejecutar el script de configuración
He creado un script llamado `setup_hostinger_git.sh` en tu proyecto local. Debes subir este archivo a tu servidor (puedes crearlo con `nano` o subirlo por FTP).

Si decides crearlo directamente en el servidor:
1.  Escribe: `nano setup_git.sh`
2.  Copia el contenido del archivo `setup_hostinger_git.sh` que generé.
3.  Pega el contenido en la terminal (Clic derecho suele pegar).
4.  Guarda con `Ctrl+O`, `Enter`, y sal con `Ctrl+X`.
5.  Dale permisos de ejecución:
    ```bash
    chmod +x setup_git.sh
    ```
6.  Ejecútalo:
    ```bash
    ./setup_git.sh
    ```

Este script inicializará Git, configurará el `.gitignore` para excluir `api/config/database.php` y preparará los archivos.

---

## Paso 4: Autenticación y Push (Subir a GitHub)
El script preparará todo, pero el último paso (enviar a GitHub) requiere tu permiso.

Ejecuta el siguiente comando en la terminal de Hostinger:
```bash
git push -u origin master --force
```

**Te pedirá credenciales:**
*   **Username:** `Nexus6Mx`
*   **Password:** Aquí **NO** pongas tu contraseña de GitHub. Debes poner tu **Personal Access Token (Classic)**.

### ¿Cómo generar un Token si no tienes uno?
1.  Ve a GitHub -> Settings -> Developer settings -> Personal access tokens -> Tokens (classic).
2.  "Generate new token".
3.  Dale permisos de `repo` (full control of private repositories).
4.  Copia el token (empieza con `ghp_...`).

---

## Resumen de lo que pasará
1.  El servidor se convertirá en un repositorio Git.
2.  El archivo `api/config/database.php` será ignorado (no se subirá).
3.  Todos los demás archivos del servidor reemplazarán (o se mezclarán) con lo que hay en GitHub.
4.  GitHub tendrá la copia exacta de tu producción.
