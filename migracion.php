<?php
// Script para migrar datos existentes al nuevo esquema
// Este script debe ejecutarse después de crear las nuevas tablas y antes de implementar el nuevo código

// 1. Migrar niveles de interpretación textual a la nueva tabla de niveles
$query = "INSERT INTO niveles_interpretacion (id, nombre, rango_min, rango_max, color, orden)
          SELECT DISTINCT 
            CASE nivel 
              WHEN 'bajo' THEN 1 
              WHEN 'medio' THEN 2 
              WHEN 'alto' THEN 3 
              ELSE 4 
            END as id,
            CASE nivel 
              WHEN 'bajo' THEN 'En desarrollo' 
              WHEN 'medio' THEN 'Adecuado' 
              WHEN 'alto' THEN 'Notable' 
              ELSE 'Sin clasificar' 
            END as nombre,
            CASE nivel 
              WHEN 'bajo' THEN 35.00 
              WHEN 'medio' THEN 60.00 
              WHEN 'alto' THEN 70.00 
              ELSE 0.00 
            END as rango_min,
            CASE nivel 
              WHEN 'bajo' THEN 59.99 
              WHEN 'medio' THEN 69.99 
              WHEN 'alto' THEN 100.00 
              ELSE 34.99 
            END as rango_max,
            CASE nivel 
              WHEN 'bajo' THEN '#FFA500' 
              WHEN 'medio' THEN '#FFFF00' 
              WHEN 'alto' THEN '#90EE90' 
              ELSE '#FF0000' 
            END as color,
            CASE nivel 
              WHEN 'bajo' THEN 6 
              WHEN 'medio' THEN 4 
              WHEN 'alto' THEN 3 
              ELSE 7 
            END as orden
          FROM interpretaciones
          GROUP BY nivel";

// 2. Actualizar la tabla de interpretaciones para referenciar a la nueva tabla de niveles
$query = "UPDATE interpretaciones i
          SET i.nivel_id = 
            CASE i.nivel 
              WHEN 'bajo' THEN 6 
              WHEN 'medio' THEN 4 
              WHEN 'alto' THEN 3 
              ELSE 7 
            END";

// 3. Actualizar la tabla de resultados para referenciar a la nueva tabla de niveles
$query = "UPDATE resultados r
          SET r.nivel_id = 
            CASE r.nivel 
              WHEN 'bajo' THEN 6 
              WHEN 'medio' THEN 4 
              WHEN 'alto' THEN 3 
              ELSE 7 
            END";

// 4. Actualizar la tabla de dimensiones para clasificar correctamente cada dimensión
$query = "UPDATE dimensiones d
          SET d.tipo = 'primaria',
              d.prueba_id = (
                SELECT p.id
                FROM pruebas p
                JOIN pruebas_dimensiones pd ON p.id = pd.prueba_id
                WHERE pd.dimension_id = d.id
                LIMIT 1
              ),
              d.bipolar = 
                CASE
                  WHEN d.nombre LIKE '%vs%' THEN 1
                  ELSE 0
                END";

// Si hay dimensiones bipolares, extraer los nombres de los polos
$query = "UPDATE dimensiones d
          SET d.polo_positivo = SUBSTRING_INDEX(d.nombre, 'vs', 1),
              d.polo_negativo = SUBSTRING_INDEX(d.nombre, 'vs', -1)
          WHERE d.bipolar = 1";