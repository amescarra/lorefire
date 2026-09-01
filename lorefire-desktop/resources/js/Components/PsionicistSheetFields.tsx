import React from 'react'
import { Input } from '@/Components/Input'
import { PSIONIC_DISCIPLINES } from '@/lib/adnd2e'

const LIST_ID = 'psionic-discipline-labels'

interface Props {
  pspCurrent: number | null
  pspMax: number | null
  powers: string[]
  onPspCurrent: (value: number | null) => void
  onPspMax: (value: number | null) => void
  onPowers: (value: string[]) => void
}

function parseOptionalInt(raw: string): number | null {
  if (raw === '') return null
  const n = parseInt(raw, 10)
  return Number.isNaN(n) ? null : n
}

export function PsionicistSheetFields({
  pspCurrent,
  pspMax,
  powers,
  onPspCurrent,
  onPspMax,
  onPowers,
}: Props) {
  const rows = powers.length > 0 ? powers : ['']

  const setRow = (index: number, value: string) => {
    const next = [...rows]
    next[index] = value
    onPowers(next)
  }

  return (
    <div data-testid="psionicist-sheet" className="flex flex-col gap-3">
      <p className="text-[10px] uppercase tracking-widest text-[var(--color-text-dim)]">
        Psionicist sheet
      </p>
      <p className="text-xs text-[var(--color-text-dim)]">
        Fill PSP and power names yourself. This app does not calculate PSP or store power text.
      </p>
      <div className="grid grid-cols-2 gap-4">
        <Input
          label="PSP current"
          type="number"
          min={0}
          value={pspCurrent ?? ''}
          onChange={e => onPspCurrent(parseOptionalInt(e.target.value))}
        />
        <Input
          label="PSP max"
          type="number"
          min={0}
          value={pspMax ?? ''}
          onChange={e => onPspMax(parseOptionalInt(e.target.value))}
        />
      </div>
      <div className="flex flex-col gap-2">
        <p className="text-xs uppercase tracking-widest text-[var(--color-text-dim)]">Known powers</p>
        <p className="text-[10px] text-[var(--color-text-dim)]">
          Names you type. Discipline labels below are suggestions only — not a power list.
        </p>
        <datalist id={LIST_ID}>
          {PSIONIC_DISCIPLINES.map(name => (
            <option key={name} value={name} />
          ))}
        </datalist>
        {rows.map((name, i) => (
          <Input
            key={i}
            label={i === 0 ? 'Power name' : undefined}
            value={name}
            list={LIST_ID}
            onChange={e => setRow(i, e.target.value)}
            placeholder="Type a power name"
          />
        ))}
        <button
          type="button"
          className="text-[10px] uppercase tracking-widest text-[var(--color-rune)] self-start"
          onClick={() => onPowers([...rows, ''])}
        >
          + Power name
        </button>
      </div>
    </div>
  )
}
