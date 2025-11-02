@echo off
REM Script para ejecutar verificación de alertas periódicamente
REM Para usar con el Programador de tareas de Windows

cd /d "c:\laragon\www\CruzMotorSAC"
php scripts\verificar_alertas.php

REM Log del resultado
echo [%date% %time%] Script de alertas ejecutado >> logs\alertas_automaticas.log