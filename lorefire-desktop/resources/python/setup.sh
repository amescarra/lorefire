#!/usr/bin/env bash
# setup.sh -- Create a Python venv and install WhisperX for Lorefire.
#
# Usage:
#   bash resources/python/setup.sh [--gpu]
#
#   --gpu  Install GPU (CUDA) versions of torch/torchaudio instead of CPU.
#
# Supported platforms (auto-detected):
#   macOS arm64  (Apple Silicon)
#   macOS x86_64 (Intel)
#   Linux x86_64
#   Windows x86_64 (Git Bash/MSYS2)
#
# The bundled python-build-standalone runtime is used when present
# (resources/python/runtime/).  Falls back to system Python for dev use.
# Download the runtime with: bash resources/python/download_runtime.sh
#
# The app's TranscribeAudio job calls the venv's Python directly:
#   resources/python/venv/bin/python resources/python/run_whisperx.py ...

set -euo pipefail

export PYTHONUNBUFFERED=1
export PYTHONIOENCODING=utf-8
export PIP_PROGRESS_BAR=off
export PIP_DISABLE_PIP_VERSION_CHECK=1
export PIP_DEFAULT_TIMEOUT=60
export PIP_NO_INPUT=1

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VENV_DIR="$SCRIPT_DIR/venv"
REQ_FILE="$SCRIPT_DIR/requirements.txt"
GPU=false

# Platform-specific paths inside the bundled python-build-standalone runtime
# and inside the venv.  Windows (Git Bash / MSYS2) uses Scripts/ and .exe.
case "$(uname -s)" in
  MINGW*|MSYS*|CYGWIN*)
    BUNDLED_RUNTIME="$SCRIPT_DIR/runtime/python.exe"
    VENV_BIN_DIR="$VENV_DIR/Scripts"
    ;;
  *)
    BUNDLED_RUNTIME="$SCRIPT_DIR/runtime/bin/python3"
    VENV_BIN_DIR="$VENV_DIR/bin"
    ;;
esac

# Parse args
for arg in "$@"; do
  case "$arg" in
    --gpu) GPU=true ;;
    *) echo "Unknown argument: $arg" && exit 1 ;;
  esac
done

echo "==> Lorefire -- WhisperX Setup"
echo "    Script dir : $SCRIPT_DIR"
echo "    Venv dir   : $VENV_DIR"
echo "    GPU mode   : $GPU"
echo ""

# -- Locate Python -------------------------------------------------------
# Prefer the bundled python-build-standalone runtime (portable, no system dep).
# Fall back to PATH only so developers can still run setup.sh manually without
# the runtime present (e.g. CI or dev machines with a system Python).
PYTHON_BIN=""

if [ -x "$BUNDLED_RUNTIME" ]; then
  PYTHON_VERSION=$("$BUNDLED_RUNTIME" -c "import sys; print(sys.version_info[:2])")
  echo "    Using bundled runtime: $BUNDLED_RUNTIME ($PYTHON_VERSION)"
  PYTHON_BIN="$BUNDLED_RUNTIME"
else
  echo "    Bundled runtime not found at $BUNDLED_RUNTIME"
  echo "    Falling back to system Python..."
  for candidate in python3.12 python3.11 python3.10 python3.9 python3 python; do
    if command -v "$candidate" &>/dev/null; then
      PYTHON_VERSION=$("$candidate" -c "import sys; print(sys.version_info[:2])")
      echo "    Found system Python: $candidate ($PYTHON_VERSION)"
      PYTHON_BIN="$candidate"
      break
    fi
  done
fi

if [ -z "$PYTHON_BIN" ]; then
  echo "ERROR: No Python interpreter found."
  echo "  Expected bundled runtime at: $BUNDLED_RUNTIME"
  echo "  To download it, run: bash $SCRIPT_DIR/download_runtime.sh  (or download_runtime.ps1 on Windows)"
  echo "  Or install Python 3.9+ system-wide and re-run this script."
  exit 1
fi

# -- Check ffmpeg --------------------------------------------------------
# imageio-ffmpeg (in requirements.txt) bundles ffmpeg so a system install
# is not required.  Print a note if one is present anyway.
if command -v ffmpeg &>/dev/null; then
  echo "    ffmpeg (system): $(ffmpeg -version 2>&1 | head -1)"
else
  echo "    ffmpeg: using bundled binary from imageio-ffmpeg"
fi

# -- Create venv ---------------------------------------------------------
# --copies ensures the venv contains actual binaries, not symlinks back into
# the runtime directory.  This makes the venv fully self-contained so it
# works even after the app is relocated (e.g. inside the packaged .app).
if [ ! -d "$VENV_DIR" ]; then
  echo "==> Creating virtual environment at $VENV_DIR..."
  "$PYTHON_BIN" -m venv --copies "$VENV_DIR"
else
  echo "==> Virtual environment already exists at $VENV_DIR"
fi

VENV_PYTHON="$VENV_BIN_DIR/python"

pip_install() {
  local label="$1"
  local timeout_sec="$2"
  shift 2
  echo "==> $label"
  echo "    timeout : ${timeout_sec}s"
  local pip_args=(
    -m pip install
    --no-input
    --prefer-binary
    --timeout 60
    --retries 3
    --disable-pip-version-check
    --progress-bar off
  )
  if command -v timeout >/dev/null 2>&1; then
    timeout --foreground "$timeout_sec" "$VENV_PYTHON" "${pip_args[@]}" "$@"
  else
    "$VENV_PYTHON" "${pip_args[@]}" "$@"
  fi
}

# -- Upgrade pip ---------------------------------------------------------
pip_install "Upgrading pip" 300 --upgrade pip setuptools wheel

# -- Install torch (CPU or CUDA) -----------------------------------------
# GPU is optional. CPU wheels are the supported first-run path.
if [ "$GPU" = true ]; then
  pip_install "Installing torch (CUDA 11.8, optional GPU path)" 900 \
    torch==2.5.1 torchaudio==2.5.1 --index-url https://download.pytorch.org/whl/cu118
else
  pip_install "Installing torch (CPU only)" 900 \
    torch==2.5.1 torchaudio==2.5.1 --index-url https://download.pytorch.org/whl/cpu
fi

# -- Install WhisperX and remaining deps ---------------------------------
pip_install "Installing WhisperX and dependencies" 1200 -r "$REQ_FILE"

# -- Pre-download WhisperX models (fail-soft) ----------------------------
# Must not hang first-run setup. Models download on first transcription if this
# step is skipped. Audio stays local either way.
echo "==> Pre-downloading WhisperX base model (optional, 3 min cap)..."
if command -v timeout >/dev/null 2>&1; then
  if ! timeout --foreground 180 "$VENV_PYTHON" -c "
import os
cache = os.path.join(os.path.expanduser('~'), '.cache')
os.environ['HF_HOME']    = os.path.join(cache, 'huggingface')
os.environ['TORCH_HOME'] = os.path.join(cache, 'torch')
import whisperx
whisperx.load_model('base', device='cpu', compute_type='int8')
print('  base model OK')
"; then
    echo "WARNING: model preload skipped. Models download on first local transcription."
  fi
else
  echo "==> Skipping model preload (no timeout command). Models download on first transcription."
fi

# -- Verify install ------------------------------------------------------
echo "==> Verifying installation..."
"$VENV_PYTHON" -c "import whisperx; print('  whisperx OK:', whisperx.__version__ if hasattr(whisperx,'__version__') else 'installed')"
"$VENV_PYTHON" -c "import torch; print('  torch OK:', torch.__version__)"

echo ""
echo "==> Setup complete."
echo ""
echo "    Run transcription with:"
echo "    $VENV_PYTHON $SCRIPT_DIR/run_whisperx.py \\"
echo "      --audio /path/to/audio.webm \\"
echo "      --output /path/to/output.json \\"
echo "      --model base --diarize --hf-token <TOKEN>"
echo ""
