import React from 'react'
import { MAX_TIMES_MEMORIZED } from '@/lib/adnd2e'

interface Props {
  timesMemorized: number
  onChange: (times: number) => void
  remaining?: number
}

export function MemorizedControl({ timesMemorized, onChange, remaining }: Props) {
  const count = Math.max(0, timesMemorized)
  const set = (n: number) => onChange(Math.max(0, Math.min(MAX_TIMES_MEMORIZED, n)))
  const showRemaining = remaining !== undefined && count > 0 && remaining < count

  return (
    <div className="flex items-center gap-1.5 shrink-0" data-testid="memorized-control">
      <label className="flex items-center gap-1.5 cursor-pointer select-none">
        <input
          type="checkbox"
          checked={count > 0}
          onChange={e => set(e.target.checked ? Math.max(1, count) : 0)}
          aria-label="Memorized"
          className="accent-[var(--color-rune)]"
        />
        <span
          className="text-[10px] uppercase tracking-widest"
          style={{ color: count > 0 ? 'var(--color-rune-bright)' : 'var(--color-text-dim)' }}
        >
          Memorized
        </span>
      </label>
      {count > 0 && (
        <div className="flex items-center gap-0.5">
          <button
            type="button"
            aria-label="Fewer copies"
            onClick={() => set(count - 1)}
            className="w-5 h-5 rounded text-xs leading-none border hover:border-[var(--color-rune)]"
            style={{ borderColor: 'var(--color-border)', color: 'var(--color-text-bright)' }}
          >
            −
          </button>
          <span className="font-mono text-xs w-4 text-center" data-testid="memorized-count">
            {count}
          </span>
          <button
            type="button"
            aria-label="More copies"
            disabled={count >= MAX_TIMES_MEMORIZED}
            onClick={() => set(count + 1)}
            className="w-5 h-5 rounded text-xs leading-none border hover:border-[var(--color-rune)] disabled:opacity-30"
            style={{ borderColor: 'var(--color-border)', color: 'var(--color-text-bright)' }}
          >
            +
          </button>
        </div>
      )}
      {showRemaining && (
        <span className="text-[9px] font-mono" style={{ color: 'var(--color-text-dim)' }}>
          {remaining} left
        </span>
      )}
    </div>
  )
}
