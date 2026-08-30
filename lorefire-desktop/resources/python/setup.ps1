# setup.ps1 -- Create a Python venv and install WhisperX for Lorefire (Windows).
#
# Must parse under Windows PowerShell 5.1 (powershell.exe), which is what
# PythonSetupService launches. Rules for this file:
#   - ASCII only (no em dash, no smart quotes). PS 5.1 reads UTF-8 without BOM
#     as ANSI; U+2014 becomes a stray quote and the rest of the file misparses.
#   - Literal Write-Host text is single-quoted so (optional, 3 min cap) cannot
#     be treated as a parameter list.
#   - Every Python -c snippet is a single-quoted here-string (@' ... '@).
#
# Usage:
#   powershell -ExecutionPolicy Bypass -File resources\python\setup.ps1 [-Gpu]
#
#   -Gpu  Install GPU (CUDA) versions of torch/torchaudio instead of CPU.
#         Optional. The default CPU wheel path must complete on a typical
#         Windows 11 box without compiling from source.

param(
    [switch]$Gpu
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# Live, unbuffered output so the Lorefire UI can tail python_setup.log.
$env:PYTHONUNBUFFERED = '1'
$env:PYTHONIOENCODING = 'utf-8'
$env:PIP_PROGRESS_BAR = 'off'
$env:PIP_DISABLE_PIP_VERSION_CHECK = '1'
$env:PIP_DEFAULT_TIMEOUT = '60'
$env:PIP_NO_INPUT = '1'

$ScriptDir      = Split-Path -Parent $MyInvocation.MyCommand.Path
$VenvDir        = Join-Path $ScriptDir 'venv'
$ReqFile        = Join-Path $ScriptDir 'requirements.txt'
$BundledRuntime = Join-Path $ScriptDir 'runtime\python.exe'
$VenvScriptsDir = Join-Path $VenvDir 'Scripts'

$pythonVersionCode = @'
import sys; print(sys.version_info[:2])
'@
$whisperxVerifyCode = @'
import whisperx
print("  whisperx OK:", getattr(whisperx, "__version__", "installed"))
'@
$torchVerifyCode = @'
import torch
print("  torch OK:", torch.__version__)
'@
$modelCode = @'
import os
cache = os.path.join(os.path.expanduser("~"), ".cache")
os.environ["HF_HOME"] = os.path.join(cache, "huggingface")
os.environ["TORCH_HOME"] = os.path.join(cache, "torch")
import whisperx
whisperx.load_model("base", device="cpu", compute_type="int8")
print("  base model OK")
'@

Write-Host '==> Lorefire -- WhisperX Setup'
Write-Host ('    Script dir : ' + $ScriptDir)
Write-Host ('    Venv dir   : ' + $VenvDir)
Write-Host ('    GPU mode   : ' + $Gpu.IsPresent)
Write-Host '    Default    : CPU binary wheels (GPU torch is optional)'
Write-Host ''

function Stop-ProcessTree {
    param([int]$ProcessId)
    Get-CimInstance Win32_Process -Filter "ParentProcessId=$ProcessId" -ErrorAction SilentlyContinue |
        ForEach-Object { Stop-ProcessTree -ProcessId $_.ProcessId }
    Stop-Process -Id $ProcessId -Force -ErrorAction SilentlyContinue
}

function Write-NewFileBytes {
    param([string]$Path, [long]$Position)
    if (-not (Test-Path $Path)) { return $Position }
    $stream = $null
    try {
        $stream = [System.IO.File]::Open($Path, [System.IO.FileMode]::Open, [System.IO.FileAccess]::Read, [System.IO.FileShare]::ReadWrite)
        if ($Position -gt $stream.Length) { $Position = 0 }
        $stream.Seek($Position, [System.IO.SeekOrigin]::Begin) | Out-Null
        $reader = New-Object System.IO.StreamReader($stream)
        $chunk = $reader.ReadToEnd()
        if ($chunk) { Write-Host $chunk -NoNewline }
        return $stream.Position
    } catch {
        return $Position
    } finally {
        if ($stream) { $stream.Dispose() }
    }
}

# Run a child with merged stdout/stderr, live log streaming, a hard timeout,
# and a non-zero exit that Windows PowerShell 5.1 would otherwise ignore.
function Invoke-TimedCommand {
    param(
        [string]$Label,
        [string]$FilePath,
        [string[]]$ArgumentList,
        [int]$TimeoutSec,
        [switch]$AllowFailure
    )

    Write-Host ('==> ' + $Label)
    Write-Host ('    command : ' + $FilePath + ' ' + ($ArgumentList -join ' '))
    Write-Host ('    timeout : ' + $TimeoutSec + 's')

    $outFile = [System.IO.Path]::GetTempFileName()
    $errFile = [System.IO.Path]::GetTempFileName()
    $process = $null

    try {
        $process = Start-Process -FilePath $FilePath -ArgumentList $ArgumentList `
            -NoNewWindow -PassThru `
            -RedirectStandardOutput $outFile `
            -RedirectStandardError $errFile

        $deadline = [datetime]::UtcNow.AddSeconds($TimeoutSec)
        $outPos = [long]0
        $errPos = [long]0

        while (-not $process.HasExited) {
            if ([datetime]::UtcNow -gt $deadline) {
                Write-Host ('ERROR: ' + $Label + ' timed out after ' + $TimeoutSec + 's. Killing process tree...')
                Stop-ProcessTree -ProcessId $process.Id
                Start-Sleep -Milliseconds 400
                $outPos = Write-NewFileBytes -Path $outFile -Position $outPos
                $errPos = Write-NewFileBytes -Path $errFile -Position $errPos
                if ($AllowFailure) {
                    Write-Host 'WARNING: step skipped after timeout.'
                    return $false
                }
                Write-Error ('TIMEOUT: ' + $Label + ' exceeded ' + $TimeoutSec + 's')
                exit 1
            }
            $outPos = Write-NewFileBytes -Path $outFile -Position $outPos
            $errPos = Write-NewFileBytes -Path $errFile -Position $errPos
            Start-Sleep -Milliseconds 400
        }

        Start-Sleep -Milliseconds 200
        Write-NewFileBytes -Path $outFile -Position $outPos | Out-Null
        Write-NewFileBytes -Path $errFile -Position $errPos | Out-Null
        Write-Host ''

        if ($process.ExitCode -ne 0) {
            if ($AllowFailure) {
                Write-Host ('WARNING: ' + $Label + ' exited ' + $process.ExitCode + ' - continuing.')
                return $false
            }
            Write-Error ('FAILED: ' + $Label + ' exited with code ' + $process.ExitCode)
            exit $process.ExitCode
        }

        return $true
    } finally {
        Remove-Item $outFile, $errFile -Force -ErrorAction SilentlyContinue
    }
}

function Invoke-Pip {
    param(
        [string]$Label,
        [string[]]$PipArgs,
        [int]$TimeoutSec = 900
    )

    $all = @(
        '-m', 'pip', 'install',
        '--no-input',
        '--prefer-binary',
        '--only-binary=:all:',
        '--timeout', '60',
        '--retries', '3',
        '--disable-pip-version-check',
        '--progress-bar', 'off'
    ) + $PipArgs

    Invoke-TimedCommand -Label $Label -FilePath $VenvPython -ArgumentList $all -TimeoutSec $TimeoutSec
}

# -- Locate Python -------------------------------------------------------
$PythonBin = $null

if (Test-Path $BundledRuntime) {
    $ver = & $BundledRuntime -c $pythonVersionCode
    Write-Host ('    Using bundled runtime: ' + $BundledRuntime + ' (' + $ver + ')')
    $PythonBin = $BundledRuntime
} else {
    Write-Host ('    Bundled runtime not found at ' + $BundledRuntime)
    Write-Host '    Falling back to system Python...'
    foreach ($candidate in @('python3.12', 'python3.11', 'python3.10', 'python3.9', 'python3', 'python')) {
        if (Get-Command $candidate -ErrorAction SilentlyContinue) {
            $ver = & $candidate -c $pythonVersionCode
            Write-Host ('    Found system Python: ' + $candidate + ' (' + $ver + ')')
            $PythonBin = $candidate
            break
        }
    }
}

if (-not $PythonBin) {
    Write-Error (@'
ERROR: No Python interpreter found.
  Expected bundled runtime next to this script (runtime\python.exe).
  To download it, run: powershell -ExecutionPolicy Bypass -File resources\python\download_runtime.ps1
  Or install Python 3.9+ system-wide and re-run this script.
'@)
    exit 1
}

# -- Check ffmpeg --------------------------------------------------------
# imageio-ffmpeg (in requirements.txt) bundles ffmpeg so a system install
# is not required.  Print a note if one is present anyway.
if (Get-Command ffmpeg -ErrorAction SilentlyContinue) {
    Write-Host ('    ffmpeg (system): ' + ((ffmpeg -version 2>&1)[0]))
} else {
    Write-Host '    ffmpeg: using bundled binary from imageio-ffmpeg'
}

# -- Create venv ---------------------------------------------------------
if (-not (Test-Path $VenvDir)) {
    Write-Host ('==> Creating virtual environment at ' + $VenvDir + '...')
    & $PythonBin -m venv --copies $VenvDir
    if ($LASTEXITCODE -ne 0) {
        Write-Error ('FAILED: python -m venv exited ' + $LASTEXITCODE)
        exit $LASTEXITCODE
    }
} else {
    Write-Host ('==> Virtual environment already exists at ' + $VenvDir)
}

$VenvPython = Join-Path $VenvScriptsDir 'python.exe'
if (-not (Test-Path $VenvPython)) {
    Write-Error ('ERROR: venv python missing at ' + $VenvPython)
    exit 1
}

# -- Upgrade pip ---------------------------------------------------------
Invoke-Pip -Label 'Upgrading pip' -PipArgs @('--upgrade', 'pip', 'setuptools', 'wheel') -TimeoutSec 300

# -- Install torch (CPU or CUDA) -----------------------------------------
# Pin 2.5.1 to match requirements.txt / pyannote 3.x. GPU is optional;
# the CPU index is the supported first-run path on Windows 11.
if ($Gpu) {
    Invoke-Pip -Label 'Installing torch (CUDA 11.8, optional GPU path)' `
        -PipArgs @('torch==2.5.1', 'torchaudio==2.5.1', '--index-url', 'https://download.pytorch.org/whl/cu118') `
        -TimeoutSec 900
} else {
    Invoke-Pip -Label 'Installing torch (CPU only)' `
        -PipArgs @('torch==2.5.1', 'torchaudio==2.5.1', '--index-url', 'https://download.pytorch.org/whl/cpu') `
        -TimeoutSec 900
}

# -- Install WhisperX and remaining deps ---------------------------------
Invoke-Pip -Label 'Installing WhisperX and dependencies' `
    -PipArgs @('-r', $ReqFile) `
    -TimeoutSec 1200

# -- Pre-download WhisperX models (fail-soft) ----------------------------
# Must not hang first-run setup. Transcription downloads the model later
# if this step times out or fails. Audio stays local either way.
Write-Host '==> Pre-downloading WhisperX base model (optional, 3 min cap)...'
Invoke-TimedCommand `
    -Label 'Pre-downloading WhisperX base model' `
    -FilePath $VenvPython `
    -ArgumentList @('-c', $modelCode) `
    -TimeoutSec 180 `
    -AllowFailure | Out-Null

# -- Verify install ------------------------------------------------------
Invoke-TimedCommand -Label 'Verifying whisperx import' `
    -FilePath $VenvPython `
    -ArgumentList @('-c', $whisperxVerifyCode) `
    -TimeoutSec 60
Invoke-TimedCommand -Label 'Verifying torch import' `
    -FilePath $VenvPython `
    -ArgumentList @('-c', $torchVerifyCode) `
    -TimeoutSec 60

Write-Host ''
Write-Host '==> Setup complete.'
Write-Host ''
Write-Host '    Run transcription with:'
Write-Host ('    ' + $VenvPython + ' ' + $ScriptDir + '\run_whisperx.py')
Write-Host '      --audio C:\path\to\audio.webm'
Write-Host '      --output C:\path\to\output.json'
Write-Host '      --model base --diarize --hf-token <TOKEN>'
Write-Host ''
