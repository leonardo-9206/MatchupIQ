# MatchupIQ — Backend Automated Integration Tests

Suite automatizada de pruebas de integración para la API RESTful de MatchupIQ (PHP 8 + MySQL). Cumple con el criterio de **Pruebas automatizadas de integración (15% del Sprint 2)** según la rúbrica oficial de evaluación.

---

## 🎯 Cobertura de Pruebas

La suite evalúa automáticamente los tres escenarios requeridos por la materia:

### 1. Restricciones de Acceso (Seguridad HTTP)
* `GET /api/auth/me.php` sin sesión activa $\rightarrow$ Valida respuesta `401 Unauthorized`.
* Validación de rechazo de métodos no permitidos (`POST` en endpoints `GET`) $\rightarrow$ Valida `405 Method Not Allowed`.

### 2. Entradas Inválidas (Validaciones del Servidor)
* `POST /api/auth/register.php` con formato de email inválido $\rightarrow$ Valida respuesta `422 Unprocessable Content` y desglose de campo en error.
* `POST /api/auth/register.php` con contraseña menor a 8 caracteres $\rightarrow$ Valida respuesta `422 Unprocessable Content`.
* `POST /api/auth/login.php` con payload vacío $\rightarrow$ Valida respuesta `400 Bad Request`.
* `POST /api/auth/login.php` con contraseña incorrecta $\rightarrow$ Valida respuesta `401 Unauthorized` genérica (prevención de enumeración de usuarios).

### 3. Flujos Exitosos (Happy Path & Persistencia)
* `GET /api/health-check.php` $\rightarrow$ Valida respuesta `200 OK`, JSON de estatus y CORS habilitado.
* `POST /api/auth/register.php` $\rightarrow$ Inserción exitosa de usuario en MySQL (`201 Created`) con password cifrado en Bcrypt sin exponer el hash.
* Control de duplicados $\rightarrow$ Segundo registro con mismo email responde `409 Conflict`.
* `POST /api/auth/login.php` $\rightarrow$ Autenticación exitosa (`200 OK`) y emisión de cookie de sesión segura.
* `GET /api/auth/me.php` $\rightarrow$ Validación de persistencia de sesión activa (`200 OK`).
* `GET /api/props/index.php` $\rightarrow$ Consulta persistente a MySQL con `INNER JOIN` relacional de props, jugadores y momios ordenados por ventaja matemática (`calculated_edge`).
* `GET /api/props/show.php?id=1` $\rightarrow$ Consulta detallada de una prop (`200 OK`).
* `GET /api/props/show.php?id=999999` $\rightarrow$ Manejo de recurso inexistente (`404 Not Found`).
* `POST /api/auth/logout.php` $\rightarrow$ Destrucción de sesión y cookie (`200 OK`).

---

## 🚀 Cómo Ejecutar las Pruebas

### Opción A: Servidor embebido de PHP (Rápido)
1. En una terminal, levanta el servidor de PHP apuntando a `backend`:
   ```bash
   php -S 127.0.0.1:8000 -t backend
   ```
2. En otra terminal, corre el test runner:
   ```bash
   php backend/tests/run_tests.php http://127.0.0.1:8000
   ```

### Opción B: Apache de XAMPP
1. Inicia **Apache** y **MySQL** desde el Panel de Control de XAMPP.
2. Ejecuta el test runner apuntando a tu ruta local de Apache:
   ```bash
   php backend/tests/run_tests.php http://localhost/MatchupIQ/backend
   ```
