<?php
// Script local para actualizar contraseñas de admin y carlos de forma segura.
$dsn = 'mysql:host=db;dbname=u185421649_gestor_ordenes;charset=utf8mb4';
$user = 'u185421649_gestor_user';
$pass = 'Chckcl74&';
try{
  $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
  $adminHash = password_hash('AdminTemp123!', PASSWORD_BCRYPT);
  $carlosHash = password_hash('TempPass123!', PASSWORD_BCRYPT);
  $pdo->prepare("UPDATE users SET password_hash=?, role='Administrador', active=1 WHERE email='admin@errautomotriz.online'")->execute([$adminHash]);
  $pdo->prepare("INSERT INTO users(email,password_hash,role,active,created_at) SELECT ?, ?, 'Operador', 1, NOW() FROM DUAL WHERE NOT EXISTS(SELECT 1 FROM users WHERE email=?)")
      ->execute(['carlos@errautomotriz.online', $carlosHash, 'carlos@errautomotriz.online']);
  $pdo->prepare("UPDATE users SET password_hash=?, role='Operador', active=1 WHERE email='carlos@errautomotriz.online'")->execute([$carlosHash]);
  echo "done\n";
}catch(Exception $e){ echo 'ERR: '.$e->getMessage()."\n"; http_response_code(500);} 
