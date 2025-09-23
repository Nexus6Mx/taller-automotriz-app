const jwt = require('jsonwebtoken');

function authMiddleware(req, res, next) {
  // 1. Busca el token en los encabezados de la petición
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1]; // Formato "Bearer TOKEN"

  if (token == null) {
    return res.sendStatus(401); // 401 Unauthorized: No hay token
  }

  // 2. Verifica que el token sea válido
  jwt.verify(token, process.env.JWT_SECRET, (err, user) => {
    if (err) {
      return res.sendStatus(403); // 403 Forbidden: El token no es válido
    }

    // 3. Si es válido, adjuntamos los datos del usuario a la petición
    req.user = user;
    next(); // ¡Puede pasar a la siguiente función!
  });
}

module.exports = authMiddleware;