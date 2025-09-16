#!/bin/bash

echo "🔧 Solucionando problemas de rutas en producción..."

# Limpiar todas las cachés
echo "📦 Limpiando cachés..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

# Regenerar cachés optimizados
echo "⚡ Regenerando cachés optimizados..."
php artisan config:cache
php artisan route:cache

# Verificar permisos de archivos
echo "🔐 Verificando permisos..."
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/

# Verificar que las rutas funcionen
echo "✅ Verificando rutas..."
php artisan route:list --name=dte.error-show

echo "🎉 ¡Proceso completado! El módulo de errores debería funcionar ahora."
echo "📝 Si el problema persiste, verifica:"
echo "   - Que el archivo routes/web.php esté actualizado en producción"
echo "   - Que las vistas estén actualizadas"
echo "   - Que no haya conflictos de permisos"
