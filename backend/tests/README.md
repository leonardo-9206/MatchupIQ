# MatchupIQ — Backend Automated Integration Tests

Suite automatizada de pruebas de integración para la API RESTful de MatchupIQ (PHP 8 + MySQL). Cumple con el criterio de **Pruebas automatizadas de integración** según la rúbrica oficial de evaluación de Sprints 2 y 3.

---

## 🎯 Cobertura de Pruebas

La suite evalúa automáticamente los escenarios requeridos por la materia (incluyendo la **Suite 6: NFL Tactical Analytics** y la **Suite 7: Bet Tracker CRUD & BOLA**):

### 1. Restricciones de Acceso (Seguridad HTTP & BOLA)
* `GET /api/auth/me.php` y `GET /api/tracker/index.php` sin sesión activa $\rightarrow$ Valida respuesta `401 Unauthorized`.
* Prevención OWASP BOLA: Usuario intentando eliminar la apuesta de otro usuario en `/api/tracker/delete.php` $\rightarrow$ Rechazado con `404 Not Found`.
* Validación de rechazo de métodos no permitidos (`POST` en endpoints `GET` como `/api/health-check.php` y `/api/tactics/heatmap.php`) $\rightarrow$ Valida `405 Method Not Allowed`.

### 2. Entradas Inválidas (Validaciones del Servidor)
* `POST /api/auth/register.php` con formato de email inválido $\rightarrow$ Valida respuesta `422 Unprocessable Content` y desglose de campo en error.
* `POST /api/auth/register.php` con contraseña menor a 8 caracteres $\rightarrow$ Valida respuesta `422 Unprocessable Content`.
* `POST /api/auth/login.php` con payload vacío $\rightarrow$ Valida respuesta `400 Bad Request`.
* `POST /api/auth/login.php` con contraseña incorrecta $\rightarrow$ Valida respuesta `401 Unauthorized` genérica (prevención de enumeración de usuarios).
* `GET /api/tactics/heatmap.php`, `GET /api/tactics/coverage.php` y `GET /api/tactics/routes.php` sin parámetro `player_id` válido $\rightarrow$ Valida respuesta `400 Bad Request`.

### 3. Flujos Exitosos (Happy Path & Persistencia)
* `GET /api/health-check.php` $\rightarrow$ Valida respuesta `200 OK`, JSON de estatus y CORS habilitado.
* `POST /api/auth/register.php` $\rightarrow$ Inserción exitosa de usuario en MySQL (`201 Created`) con password cifrado en Bcrypt sin exponer el hash.
* Control de duplicados $\rightarrow$ Segundo registro con mismo email responde `409 Conflict`.
* `POST /api/auth/login.php` $\rightarrow$ Autenticación exitosa (`200 OK`) y emisión de cookie de sesión segura.
* `GET /api/auth/me.php` $\rightarrow$ Validación de persistencia de sesión activa (`200 OK`).
* `GET /api/props/index.php` y `POST /api/props/calculate-edge.php` $\rightarrow$ Consulta persistente a MySQL con `INNER JOIN` relacional y cálculo del algoritmo matemático *Value Edge*.
* `GET /api/props/show.php?id=1` y `GET /api/props/show.php?id=999999` $\rightarrow$ Consulta detallada (`200 OK`) y manejo de recurso inexistente (`404 Not Found`).
* `GET /api/tactics/heatmap.php?player_id=3` $\rightarrow$ Desglose táctico de las 5 zonas de acarreo (`left_end`, `left_tackle`, `middle`, `right_tackle`, `right_end`) y resumen de eficiencia (`200 OK`).
* `GET /api/tactics/coverage.php?player_id=2&zone_rate=70` $\rightarrow$ Desglose `man` vs `zone`, cálculo de `projected_catch_rate`, clasificación `tactical_advantage = 'FAVORABLE'` e integración de `tactical_edge_boost` (`200 OK`).
* `GET /api/tactics/routes.php?player_id=2` $\rightarrow$ Desglose NextGenStats del árbol de rutas de receptores (`flat`, `slant`, `comeback`, `curl`, `out`, `dig`, `corner`, `post`, `go`) y métricas de eficiencia (`200 OK`).
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
