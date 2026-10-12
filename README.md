# MatchupIQ

> **Statistical Props Analysis & Odds Comparison Platform for NFL and Liga MX**  
> Proyecto desarrollado para la materia de **Programación de Sistemas Web** (5° Semestre - ISC 5-A)  
> **Universidad Autónoma de Aguascalientes — Centro de Ciencias Básicas**  
> **Profesor:** Frank Elgar Delgado Araiza

---

## 📌 Descripción del Proyecto

**MatchupIQ** es una plataforma web desarrollada bajo una arquitectura cliente-servidor desacoplada diseñada para centralizar, analizar y visualizar estadísticas de rendimiento individual (*player props*) en la **NFL** y la **Liga MX**.

### Problemática que Resuelve
En el análisis deportivo, la información se encuentra severamente fragmentada: las estadísticas de los atletas residen en portales de noticias deportivas, las clasificaciones defensivas en plataformas analíticas independientes y las líneas de cuotas en diferentes casas de apuestas (*sportsbooks*). MatchupIQ centraliza estos datos e implementa el **Value Edge Algorithm**, un motor matemático del lado del servidor que calcula la ventaja estadística esperada (+EV) contrastando las tendencias del atleta contra las debilidades defensivas del rival frente a la línea de apuesta.

---

## 🛠 Stack Tecnológico

* **Frontend:** Single Page Application (SPA) en **React 18** construida con **Vite**, estilizada con **Tailwind CSS** (soporte nativo de *Dark Mode*) y gráficos interactivos con **Chart.js / ApexCharts**.
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

---

## 📂 Estructura General del Proyecto

```
MatchupIQ/
├── backend/            # API RESTful en PHP 8 (Patrón MVC)
│   ├── api/            # Puntos de entrada HTTP públicos (JSON)
│   ├── config/         # Conexión Singleton PDO a MySQL y configuración CORS
│   ├── controllers/    # Controladores de lógica de negocio (Auth, Props, Tracker)
│   ├── middlewares/    # Control de sesiones, CORS y roles (RBAC)
│   ├── models/         # Modelos de datos y consultas preparadas con PDO
│   └── services/       # Algoritmo de Edge y consumidor cURL de The Odds API
├── frontend/           # Single Page Application en React 18 + Vite
│   ├── src/components/ # Componentes modulares (PropCard, OddsTable, BetTracker)
│   ├── src/context/    # Estado global de sesión y autenticación
│   └── src/services/   # Cliente HTTP asíncrono hacia el backend
├── Docs/               # Documentación y diagramas del sistema
│   ├── DATA_DICTIONARY.md             # Diccionario de datos en 3NF
│   ├── Documento Planeacion Sprint1.pdf # Documento oficial de planeación (Sprint 1)
│   └── *.png                          # Diagramas UML y de Arquitectura
├── .gitignore          # Exclusión de variables de entorno, temporales y dependencias
└── README.md           # Descripción general del repositorio
```

---

## 🚀 Instalación y Entorno Local

### Requisitos Previos
* PHP 8.1 o superior
* MySQL 8.0 o MariaDB
* Node.js 18+ y npm
* Composer (opcional para dependencias de backend)

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
