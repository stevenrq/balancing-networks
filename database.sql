-- Base de datos para Balanceo de Líneas
CREATE DATABASE IF NOT EXISTS balanceo_db;
USE balanceo_db;

-- Tabla de Proyectos (Intentos de balanceo)
CREATE TABLE IF NOT EXISTS proyectos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL DEFAULT 'Proyecto Sin Nombre',
    tiempo_turno INT NOT NULL COMMENT 'Tiempo disponible en minutos',
    demanda INT NOT NULL COMMENT 'Unidades a producir',
    takt_time FLOAT NOT NULL COMMENT 'Calculado: (Tiempo * 60) / Demanda',
    eficiencia FLOAT DEFAULT 0 COMMENT 'Eficiencia calculada del balanceo',
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de Tareas
CREATE TABLE IF NOT EXISTS tareas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proyecto_id INT NOT NULL,
    letra_tarea VARCHAR(10) NOT NULL COMMENT 'ID visual: A, B, C...',
    duracion INT NOT NULL COMMENT 'Duración en segundos',
    precedencias TEXT COMMENT 'Lista separada por comas: A,C',
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE
);
