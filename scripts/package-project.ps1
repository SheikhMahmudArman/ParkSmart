param([string]$OriginalZip = 'W:\DB\Project\backup\U_1\ParkSmart.zip')
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression.FileSystem
$root = [IO.Path]::GetFullPath((Split-Path $PSScriptRoot -Parent))
$output = Join-Path $root 'deliverables'
[IO.Directory]::CreateDirectory($output) | Out-Null
function Get-SourceFiles([string]$folder) {
    foreach ($item in Get-ChildItem -LiteralPath $folder -Force) {
        if ($item.PSIsContainer) {
            if ($item.Name -in @('.git', '.runtime', 'vendor', 'node_modules', 'dist', 'deliverables', '.phpunit.cache')) { continue }
            Get-SourceFiles $item.FullName
        } else {
            $rel = $item.FullName.Substring($root.Length + 1).Replace('\', '/')
            if ($item.Name -like '.env*' -and $item.Name -ne '.env.example') { continue }
            if ($rel -match '^(repair-files.*\.json|\.workspace-check)$') { continue }
            if ($rel -match '^backend/parkbackend/(storage/|bootstrap/cache/)' -and $item.Name -ne '.gitignore') { continue }
            if ($item.Name -match '\.log$|^\.phpunit.result.cache$') { continue }
            [PSCustomObject]@{ Relative = $rel; FullName = $item.FullName }
        }
    }
}
function Hash-Bytes([byte[]]$bytes) {
    $sha = [Security.Cryptography.SHA256]::Create()
    try { return [Convert]::ToHexString($sha.ComputeHash($bytes)) } finally { $sha.Dispose() }
}
$original = @{}
$zip = [IO.Compression.ZipFile]::OpenRead($OriginalZip)
try {
    foreach ($entry in $zip.Entries) {
        $rel = $entry.FullName -replace '^ParkSmart/', ''
        if ($entry.FullName.EndsWith('/') -or $rel.StartsWith('.git/')) { continue }
        $stream = $entry.Open(); $memory = [IO.MemoryStream]::new()
        try { $stream.CopyTo($memory); $original[$rel] = Hash-Bytes $memory.ToArray() }
        finally { $stream.Dispose(); $memory.Dispose() }
    }
} finally { $zip.Dispose() }
$files = @(Get-SourceFiles $root | Sort-Object Relative)
$changes = @()
$current = @{}
foreach ($file in $files) {
    $current[$file.Relative] = $true
    $hash = Hash-Bytes ([IO.File]::ReadAllBytes($file.FullName))
    if (!$original.ContainsKey($file.Relative)) { $changes += [PSCustomObject]@{ Action='ADD'; Path=$file.Relative } }
    elseif ($hash -ne $original[$file.Relative]) { $changes += [PSCustomObject]@{ Action='REPLACE'; Path=$file.Relative } }
}
foreach ($rel in $original.Keys) {
    if (!$current.ContainsKey($rel)) { $changes += [PSCustomObject]@{ Action='DELETE'; Path=$rel } }
}
if (!$current.ContainsKey('docs/FILE_MANIFEST.md')) { $changes += [PSCustomObject]@{ Action='ADD'; Path='docs/FILE_MANIFEST.md' } }
$changes = @($changes | Sort-Object Action,Path)
$lines = @('# Complete-file replacement manifest','','All paths below are relative to your ParkSmart project root. The complete ZIP contains ParkSmart/ as its top folder. The replacement ZIP has files at the relative paths below, so extract/copy it into your project root.','','Back up your folder and database first. Copy all ADD/REPLACE files and remove only DELETE files. Keep your own .env and database; follow README.md before migrating existing data. Unchanged original files are included in the complete ZIP. Generated React files under backend/parkbackend/public/app are included so the pages are immediately available after backend setup.','','| Action | Exact file location |','|---|---|')
foreach ($change in $changes) { $lines += ('| ' + $change.Action + ' | `' + $change.Path + '` |') }
$lines += @('','','## Main entry points','','- frontend/src/App.jsx: all frontend routes.','- frontend/src/pages/: complete page components.','- backend/parkbackend/routes/api.php: API routes and permissions.','- backend/parkbackend/routes/web.php: serves the React build.','- backend/parkbackend/app/Http/Controllers/ParkSmartController.php: business API with raw SQL.','- backend/parkbackend/database/sql/schema.sql: raw table DDL.','- backend/parkbackend/database/sql/parksmart.sql: procedures, transactions, triggers, function, loops and views.','- backend/parkbackend/database/migrations/2026_09_19_000001_install_parksmart_sql.php: SQL installer.','- README.md: exact installation commands, demo accounts, data-upgrade cautions and workflow.','','No vendor/node_modules directories, local credentials, test databases or session files are included. Run composer install; npm ci is needed only to rebuild/develop the frontend.')
[IO.File]::WriteAllText((Join-Path $root 'docs/FILE_MANIFEST.md'), ($lines -join "`n"), [Text.UTF8Encoding]::new($false))
$files = @(Get-SourceFiles $root | Sort-Object Relative)
$changedPaths = @{}
foreach($change in $changes) { if($change.Action -ne 'DELETE') { $changedPaths[$change.Path]=$true } }
foreach($mode in @('complete','replacement-files')) {
    $path = Join-Path $output ('ParkSmart-' + $mode + '.zip')
    # Overwrite only this script's exact output file inside the verified delivery directory.
    if (![IO.Path]::GetFullPath($path).StartsWith($root + [IO.Path]::DirectorySeparatorChar)) { throw 'Invalid output path' }
    $stream = [IO.File]::Open($path,[IO.FileMode]::Create)
    $archive = [IO.Compression.ZipArchive]::new($stream,[IO.Compression.ZipArchiveMode]::Create)
    try {
        foreach($file in $files) {
            if($mode -eq 'replacement-files' -and !$changedPaths.ContainsKey($file.Relative)) { continue }
            $entryName = if($mode -eq 'complete') { 'ParkSmart/' + $file.Relative } else { $file.Relative }
            [IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive,$file.FullName,$entryName,[IO.Compression.CompressionLevel]::Optimal) | Out-Null
        }
    } finally { $archive.Dispose(); $stream.Dispose() }
    $verify=[IO.Compression.ZipFile]::OpenRead($path)
    try {
        $names=@($verify.Entries | ForEach-Object FullName)
        $prefix=if($mode -eq 'complete') {'ParkSmart/'} else {''}
        foreach($required in @('README.md','frontend/src/App.jsx','backend/parkbackend/public/app/index.html','backend/parkbackend/database/sql/parksmart.sql','docs/FILE_MANIFEST.md')) {
            if(($prefix+$required) -notin $names) { throw "Archive missing $required" }
        }
        if($names | Where-Object { $_ -match '(^|/)(node_modules|vendor|\.runtime)/|(^|/)\.env$|repair-files' }) { throw 'Unexpected private/generated file in archive' }
        Write-Output ($mode + ': ' + $verify.Entries.Count + ' files; ' + (Get-Item -LiteralPath $path).Length + ' bytes')
    } finally { $verify.Dispose() }
}
$hashes = Get-ChildItem -LiteralPath $output -Filter '*.zip' | Get-FileHash -Algorithm SHA256 | ForEach-Object { $_.Hash.ToLower() + '  ' + [IO.Path]::GetFileName($_.Path) }
[IO.File]::WriteAllText((Join-Path $output 'SHA256SUMS.txt'),($hashes -join "`n"),[Text.UTF8Encoding]::new($false))
Write-Output ('Manifest: ' + $changes.Count + ' additions/replacements/deletions')
