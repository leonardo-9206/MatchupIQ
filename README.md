# MatchupIQ

> **Statistical Props Analysis, Odds Comparison & Tactical Heatmaps Platform for NFL and Liga MX**  
> Proyecto desarrollado para la materia de **Programación de Sistemas Web** (5° Semestre - ISC 5-A)  
> **Universidad Autónoma de Aguascalientes — Centro de Ciencias Básicas**  
> **Profesor:** Frank Elgar Delgado Araiza

---

## 📌 Descripción del Proyecto

**MatchupIQ** es una plataforma web desarrollada bajo una arquitectura cliente-servidor desacoplada diseñada para centralizar, analizar y visualizar estadísticas de rendimiento individual (*player props*), comparación de momios en tiempo real y **análisis táctico avanzado de jugadas de la NFL (mapas de calor y coberturas defensivas)**.

### Problemática que Resuelve
En el análisis deportivo, la información se encuentra severamente fragmentada: las estadísticas básicas residen en portales de noticias deportivas, las líneas de cuotas en diferentes casas de apuestas (*sportsbooks*), y los análisis tácticos profundos (esquemas de cobertura y mapas de acarreos) en plataformas de analítica avanzada cerradas.

MatchupIQ centraliza estos datos e implementa:
1. **Value Edge Algorithm:** Motor matemático del lado del servidor que calcula la ventaja estadística esperada (+EV) contrastando las tendencias del atleta contra las debilidades defensivas del rival frente a la línea de apuesta.
2. **Tactical Heatmaps & Scheme Breakdown:** Módulo de visualización táctica que desglosa mapas de calor interactivos de acarreos por zonas del campo para corredores (RBs), árboles de rutas para receptores (WRs) y métricas de rendimiento contra esquemas de Cobertura Personal (*Man Coverage*) vs Cobertura en Zona (*Zone Coverage*).
3. **Bet Tracker (Full CRUD):** Bitácora personalizada para gestión y seguimiento del rendimiento de jugadas.

---

## 🛠 Stack Tecnológico

* **Frontend:** Single Page Application (SPA) en **React 18** construida con **Vite**, estilizada con **Tailwind CSS** (soporte nativo de *Dark Mode*), visualización interactiva de mapas de calor con Canvas/SVG y gráficos con **Chart.js / ApexCharts**.
* **Backend:** API RESTful en **PHP 8** bajo el patrón arquitectónico **MVC (Modelo-Vista-Controlador)** desacoplado, exponiendo endpoints en formato JSON con cabeceras CORS.
* **Base de Datos:** **MySQL 8** normalizada en **Tercera Forma Normal (3NF)** con integridad referencial estricta y nomenclatura en inglés.
* **Persistencia Segura:** **PHP Data Objects (PDO)** con sentencias preparadas (*Prepared Statements*) para eliminar riesgos de inyección SQL.
* **Integración Externa:** Middleware con **cURL** para consumir datos de *The Odds API* con almacenamiento en caché local.
* **Metodología y Control de Versiones:** **Scrum** en sprints de dos semanas y modelo de ramas **GitFlow** (`main`, `develop`, `feature/*`).

---

## 👥 Integrantes del Equipo

* **Erich Leonardo Castruita Rodríguez** (ID: 340804) — *Lead Developer & Backend Architect*
* **Rodrigo Vázquez Zúñiga** (ID: 549632) — *Database & External API Integration Architect*
* **Carlos Antonio Villaseñor García** (ID: 441641) — *Lead Frontend & UI/UX Architect*
* **Joao (JoaoSanBills)** — *Data Modeling & NFL Analytics Engine Architect*
* **Josh** — *Data Visualization & Interactive Heatmaps Engineer*

---

## 📂 Estructura General del Proyecto

```
MatchupIQ/
├── backend/            # API RESTful en PHP 8 (Patrón MVC)
│   ├── api/            # Puntos de entrada HTTP públicos (JSON: auth, props, tracker, tactics)
│   ├── config/         # Conexión Singleton PDO a MySQL y configuración CORS
│   ├── controllers/    # Controladores de lógica de negocio (Auth, Props, Tracker, Tactics)
│   ├── middlewares/    # Control de sesiones, CORS y roles (RBAC)
│   ├── models/         # Modelos de datos y consultas preparadas con PDO
│   └── services/       # Algoritmo de Edge, Odds API cURL y HeatmapEngine
├── frontend/           # Single Page Application en React 18 + Vite
│   ├── src/components/ # Componentes (PropCard, OddsTable, BetTracker, FieldHeatmap)
│   ├── src/context/    # Estado global de sesión y autenticación
│   └── src/services/   # Cliente HTTP asíncrono hacia el backend
├── Docs/               # Documentación y diagramas del sistema
│   ├── DATA_DICTIONARY.md             # Diccionario de datos en 3NF (11 entidades)
│   ├── Documento Planeacion Sprint1.pdf # Documento oficial de planeación (Sprint 1)
│   └── *.png                          # Diagramas UML, ERD y Arquitectura
├── .gitignore          # Exclusión de variables de entorno, temporales y dependencias
└── README.md           # Descripción general del repositorio
```

---

## 🚀 Instalación y Entorno Local

### Requisitos Previos
* PHP 8.1 o superior
* MySQL 8.0 o MariaDB
* Node.js 18+ y npm

### Backend (PHP 8)
1. Configurar la base de datos MySQL ejecutando los scripts DDL ubicados en `Docs/DATA_DICTIONARY.md`.
2. Configurar las variables de entorno en el archivo `.env` del backend (credenciales de BD y llave de The Odds API).
3. Iniciar el servidor local de desarrollo:
   ```bash
   php -S localhost:8000 -t backend/
   ```

### Frontend (React + Vite)
1. Instalar dependencias:
   ```bash
   cd frontend
   npm install
   ```
2. Iniciar el servidor de desarrollo:
   ```bash
   npm run dev
   ```
