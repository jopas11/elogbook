$date = Get-Date -Format "yyyy-MM-dd_HHmmss"
$backupDir = "C:\laragon\www\elogbook\database\backups"
$file = "$backupDir\elogbook_$date.sql"

# Keep only last 30 backups
$keep = 30

# Backup
mysqldump -u root elogbook2 > $file 2>> "$backupDir\backup_error.log"

if ($?) {
    Write-Host "[OK] Backup selesai: $file"

    # Remove old backups
    Get-ChildItem "$backupDir\elogbook_*.sql" | Sort-Object Name -Descending | Select-Object -Skip $keep | Remove-Item -Force
    Write-Host "[OK] Sisa: $keep backup terbaru"
} else {
    Write-Host "[ERROR] Backup gagal"
}
