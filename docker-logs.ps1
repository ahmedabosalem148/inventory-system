# Docker Logs Viewer
# يعرض logs الـ containers

param(
    [string]$Service = "",
    [switch]$Follow = $false
)

if ($Service) {
    if ($Follow) {
        Write-Host "Following logs for $Service (Ctrl+C to stop)..." -ForegroundColor Cyan
        docker-compose logs -f $Service
    } else {
        Write-Host "Recent logs for $Service..." -ForegroundColor Cyan
        docker-compose logs --tail=50 $Service
    }
} else {
    if ($Follow) {
        Write-Host "Following all logs (Ctrl+C to stop)..." -ForegroundColor Cyan
        docker-compose logs -f
    } else {
        Write-Host "Recent logs from all services..." -ForegroundColor Cyan
        docker-compose logs --tail=20
    }
}
