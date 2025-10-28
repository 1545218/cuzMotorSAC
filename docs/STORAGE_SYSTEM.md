# Sistema de Storage Seguro - Cruz Motor SAC

## 📖 Descripción General

El sistema de storage seguro implementado para Cruz Motor SAC proporciona una infraestructura robusta y segura para el manejo de archivos del sistema de inventario. Este sistema garantiza que todos los archivos sensibles estén protegidos contra acceso web directo y proporciona una API unificada para operaciones de archivo.

## 🏗️ Arquitectura del Sistema

### Estructura de Directorios

```
storage/
├── .htaccess               # Protección principal - Deny from all
├── .gitkeep               # Preservar estructura en repositorio
├── logs/                  # Registros del sistema
│   ├── .htaccess         # Bloqueo de archivos .log
│   └── .gitkeep
├── cache/                 # Cache temporal del sistema
│   ├── .htaccess         # Deny all + TTL headers
│   └── .gitkeep
├── backups/              # Respaldos de base de datos
│   ├── .htaccess         # Ultra-protegido
│   └── .gitkeep
├── uploads/              # Archivos subidos por usuarios
│   ├── .htaccess         # Control de tipos de archivo
│   ├── .gitkeep
│   ├── productos/        # Imágenes de productos (5MB máx)
│   │   ├── .htaccess     # Solo imágenes permitidas
│   │   └── .gitkeep
│   ├── cotizaciones/     # PDFs de cotizaciones (10MB máx)
│   │   ├── .htaccess     # Solo PDFs permitidos
│   │   └── .gitkeep
│   └── documentos/       # Documentos office (20MB máx)
│       ├── .htaccess     # Solo docs office permitidos
│       └── .gitkeep
└── temp/                 # Archivos temporales
    ├── .htaccess         # Auto-limpieza configurada
    └── .gitkeep
```

## 🛡️ Características de Seguridad

### Protección Web
- **Acceso Denegado**: Todos los directorios tienen `.htaccess` con `Deny from all`
- **Sin Ejecución**: Scripts PHP/JS bloqueados completamente
- **Cabeceras Seguras**: Headers de seguridad adicionales configurados

### Validación de Archivos
- **Tipos Permitidos**: Lista blanca estricta por directorio
- **Límites de Tamaño**: Configurables por tipo de contenido
- **Sanitización**: Nombres de archivo limpiados automáticamente
- **Path Traversal**: Protección contra ataques de directorio

### Control de Acceso
- **API Centralizada**: Solo acceso a través de StorageManager
- **Logging Completo**: Todas las operaciones registradas
- **Validación Estricta**: Verificación en cada operación

## 🔧 API del StorageManager

### Métodos Principales

```php
// Guardar archivo
StorageManager::saveFile($directory, $filename, $content, $validate = true)

// Leer archivo  
StorageManager::readFile($directory, $filename)

// Eliminar archivo
StorageManager::deleteFile($directory, $filename)

// Listar archivos
StorageManager::listFiles($directory)

// Limpiar temporales
StorageManager::cleanTempFiles($maxAge = 86400)

// Obtener estadísticas
StorageManager::getStorageStats()

// Obtener ruta
StorageManager::getPath($directory)

// Formatear bytes
StorageManager::formatBytes($size)
```

### Directorios Disponibles

| Directorio | Uso | Tipos Permitidos | Tamaño Máx |
|------------|-----|------------------|------------|
| `logs` | Registros del sistema | .log, .txt | 100MB |
| `cache` | Cache temporal | Todos | 50MB |
| `backups` | Respaldos BD | .sql, .zip | 1GB |
| `uploads` | Archivos generales | Controlado | 100MB |
| `temp` | Temporales | Todos | 50MB |
| `uploads_productos` | Imágenes productos | jpg, png, gif, webp | 5MB |
| `uploads_cotizaciones` | PDFs cotizaciones | pdf | 10MB |
| `uploads_documentos` | Docs office | doc, docx, xls, xlsx | 20MB |

## 📋 Ejemplos de Uso

### 1. Subir Imagen de Producto

```php
// En ProductoController
public function uploadImage($productoId, $uploadedFile) {
    try {
        // Validar archivo
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error en la subida');
        }
        
        // Generar nombre único
        $extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
        $filename = "producto_{$productoId}_" . time() . ".{$extension}";
        
        // Leer contenido
        $content = file_get_contents($uploadedFile['tmp_name']);
        
        // Guardar usando StorageManager
        $savedFile = StorageManager::saveFile(
            'uploads_productos',
            $filename,
            $content,
            true // Validar tipo y tamaño
        );
        
        return $savedFile;
        
    } catch (Exception $e) {
        Logger::error("Error subiendo imagen: " . $e->getMessage());
        throw $e;
    }
}
```

### 2. Generar PDF de Cotización

```php
// En CotizacionController
public function generatePDF($cotizacionId) {
    try {
        // Generar PDF (usando TCPDF, DOMPDF, etc.)
        $pdf = new TCPDF();
        // ... configurar PDF ...
        $pdfContent = $pdf->Output('', 'S');
        
        // Guardar usando StorageManager
        $filename = "cotizacion_{$cotizacionId}_" . date('Y-m-d') . ".pdf";
        $savedFile = StorageManager::saveFile(
            'uploads_cotizaciones',
            $filename,
            $pdfContent,
            true
        );
        
        return $savedFile;
        
    } catch (Exception $e) {
        Logger::error("Error generando PDF: " . $e->getMessage());
        throw $e;
    }
}
```

### 3. Sistema de Cache

```php
// Cache de consultas pesadas
public function getProductosPopulares() {
    $cacheKey = 'productos_populares_' . date('Y-m-d');
    
    try {
        // Intentar desde cache
        $cachedData = StorageManager::readFile('cache', $cacheKey . '.json');
        return json_decode($cachedData, true);
        
    } catch (Exception $e) {
        // Cache miss, generar datos
        $productos = $this->consultarProductosPopulares();
        
        // Guardar en cache
        StorageManager::saveFile(
            'cache',
            $cacheKey . '.json',
            json_encode($productos),
            false
        );
        
        return $productos;
    }
}
```

### 4. Backup Automático

```php
// Backup programado
public function createBackup() {
    try {
        // Generar dump de BD
        $backupContent = $this->generateDatabaseDump();
        
        // Guardar backup
        $filename = "backup_cruzmotorsac_" . date('Y-m-d_H-i-s') . ".sql";
        $savedFile = StorageManager::saveFile(
            'backups',
            $filename,
            $backupContent,
            false
        );
        
        Logger::info("Backup creado: {$savedFile}");
        return $savedFile;
        
    } catch (Exception $e) {
        Logger::error("Error creando backup: " . $e->getMessage());
        throw $e;
    }
}
```

## 🔗 Integración con Controladores

### ProductoController
```php
class ProductoController extends Controller {
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Procesar imagen si existe
            if (isset($_FILES['imagen'])) {
                $imagenFile = $this->uploadImage($_POST['id'], $_FILES['imagen']);
                // Guardar referencia en BD
            }
        }
    }
}
```

### API Endpoints Sugeridos

```php
// api/image.php - Servir imágenes de productos
if (isset($_GET['type'], $_GET['file'])) {
    $type = $_GET['type'];
    $file = $_GET['file'];
    
    // Validar permisos de usuario
    if ($type === 'producto' && userCanViewProduct()) {
        $content = StorageManager::readFile('uploads_productos', $file);
        header('Content-Type: image/jpeg');
        echo $content;
    }
}

// api/download.php - Descarga controlada de PDFs
if (isset($_GET['type'], $_GET['file'])) {
    $type = $_GET['type'];
    $file = $_GET['file'];
    
    if ($type === 'cotizacion' && userCanDownloadCotizacion()) {
        $content = StorageManager::readFile('uploads_cotizaciones', $file);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        echo $content;
    }
}
```

## 🧹 Mantenimiento Automático

### Limpieza de Temporales
```php
// Configurar en cron job o tarea programada
// Limpiar archivos temporales de más de 24 horas
$cleaned = StorageManager::cleanTempFiles(86400);
Logger::info("Archivos temporales limpiados: {$cleaned}");
```

### Monitoreo de Espacio
```php
// Dashboard de administración
$stats = StorageManager::getStorageStats();
foreach ($stats as $dir => $info) {
    echo "Directorio: {$dir}\n";
    echo "Archivos: {$info['files_count']}\n";
    echo "Tamaño: {$info['size_formatted']}\n";
}
```

## 📊 Configuración Git

### .gitignore Actualizado
```gitignore
# Storage - Excluir contenido, preservar estructura
/storage/logs/*
!/storage/logs/.gitkeep
!/storage/logs/.htaccess

/storage/cache/*
!/storage/cache/.gitkeep
!/storage/cache/.htaccess

/storage/backups/*
!/storage/backups/.gitkeep
!/storage/backups/.htaccess

/storage/uploads/*
!/storage/uploads/.gitkeep
!/storage/uploads/.htaccess

/storage/temp/*
!/storage/temp/.gitkeep
!/storage/temp/.htaccess
```

## ✅ Verificación del Sistema

Ejecutar el script de prueba para verificar funcionamiento:

```bash
php tests/test_storage.php
```

Ejecutar ejemplos de integración:

```bash
php tests/examples_storage_integration.php
```

## 🎯 Estado del Sistema

### ✅ Implementado Completamente
- [x] Estructura de directorios segura
- [x] Protección .htaccess multicapa
- [x] API unificada StorageManager
- [x] Validación de tipos de archivo
- [x] Límites de tamaño configurables
- [x] Sanitización de nombres
- [x] Protección path traversal
- [x] Sistema de logging integrado
- [x] Limpieza automática de temporales
- [x] Estadísticas de uso
- [x] Configuración de repositorio
- [x] Scripts de prueba y ejemplos

### 🚀 Listo para Producción
El sistema está completamente funcional y listo para ser usado en producción. Todas las características de seguridad están implementadas y verificadas.

### 📋 Próximos Pasos Opcionales
1. Crear endpoints API para servir archivos
2. Implementar sistema de thumbnails
3. Añadir compresión de archivos
4. Configurar limpieza automática por cron
5. Implementar sistema de versionado de archivos

---

**Documentación actualizada:** 1 de octubre de 2025  
**Versión del sistema:** 1.0  
**Estado:** Producción Ready ✅