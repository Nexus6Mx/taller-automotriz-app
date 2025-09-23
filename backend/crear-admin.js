const axios = require('axios');

// --- CONFIGURA AQUÍ LOS DATOS DE TU USUARIO ADMINISTRADOR ---
const adminData = {
  name: "Carlos Barba", // Cambia esto a tu nombre
  email: "cbarbap@gmail.com", // Cambia esto a tu email
  password: "Chckcl74" // Cambia esto a una contraseña segura
};
// ---------------------------------------------------------

async function createAdminUser() {
  try {
    console.log('Intentando registrar al usuario administrador en http://localhost:3001/api/users/register ...');
    const response = await axios.post('http://localhost:3001/api/users/register', adminData);

    console.log('\n--- ¡ÉXITO! ---');
    console.log('Respuesta del servidor:');
    console.log(response.data);

  } catch (error) {
    console.error('\n--- ¡ERROR! ---');
    console.error('No se pudo crear el usuario administrador.');

    // Esta nueva lógica nos dará más detalles del error
    if (error.response) {
      // El servidor respondió con un error (ej. email duplicado)
      console.error('El servidor respondió con un error:', error.response.status);
      console.error('Datos del error:', error.response.data);
    } else if (error.request) {
      // La solicitud se hizo pero no hubo respuesta (servidor apagado)
      console.error('No se recibió respuesta del servidor. ¿Está encendido y corriendo en la Terminal 1?');
    } else {
      // Ocurrió un error al configurar la solicitud
      console.error('Error al configurar la solicitud:', error.message);
    }
  }
}

createAdminUser();