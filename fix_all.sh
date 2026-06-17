#!/bin/bash
FILES=(
    "/home/bprw7255/public_html/procurement.bprshikmciyk.co.id/app/FIlament/Pages/StatusPengajuanSaya.php"
    "/home/bprw7255/public_html/procurement.bprshikmciyk.co.id/app/Filament/Pages/StatusPengajuanSaya.php"
    "/home/bprw7255/bprs_procurement/app/FIlament/Pages/StatusPengajuanSaya.php"
    "/home/bprw7255/bprs_procurement/app/Filament/Pages/StatusPengajuanSaya.php"
)

FIXED_FILE="/home/bprw7255/bprs_procurement/app/Filament/Pages/StatusPengajuanSaya.php"

for f in "${FILES[@]}"; do
    if [ -f "$f" ]; then
        echo "Updating $f"
        cp "$FIXED_FILE" "$f"
    fi
done

roots=("/home/bprw7255/bprs_procurement" "/home/bprw7255/public_html/procurement.bprshikmciyk.co.id")
for r in "${roots[@]}"; do
    if [ -f "$r/artisan" ]; then
        echo "Clearing cache in $r"
        cd "$r"
        php artisan view:clear
        php artisan cache:clear
    fi
done
