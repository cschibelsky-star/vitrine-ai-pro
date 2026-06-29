<?php

echo "MASTER 2.0.1 Hotfix HeyGen aplicado.\n";
echo "Arquivos corrigidos:\n";
echo "- HeygenVideoJobResource.php\n";
echo "- ListHeygenVideoJobs.php\n";
echo "- CreateHeygenVideoJob.php\n";
echo "- EditHeygenVideoJob.php\n";
echo "\nAgora rode:\n";
echo "php artisan optimize:clear\n";
echo "php artisan filament:cache-components\n";
echo "php artisan route:clear\n";
echo "php artisan route:list | grep heygen\n";
