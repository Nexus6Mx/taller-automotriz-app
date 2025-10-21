<?php
// Copia este archivo a mail.php y ajusta según tu entorno.
// Retorna un arreglo con configuración de correo.
return [
    // smtp | mail
    'transport' => 'smtp',
    'smtp' => [
        'host' => 'smtp.hostinger.com',
        'port' => 465,
        'secure' => 'ssl', // ssl | tls
        'username' => 'servicio@errautomotriz.online',
        'password' => 'CHANGE_ME',
        'from' => ['address' => 'servicio@errautomotriz.online', 'name' => 'ERR Automotriz'],
        'timeout' => 10,
    ],
];
