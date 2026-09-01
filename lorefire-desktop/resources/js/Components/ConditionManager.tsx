import React, { useState } from 'react'
import { router } from '@inertiajs/react'
import { Badge } from '@/Components/Badge'
import { CharacterCondition } from '@/types'
import { CONDITIONS_2E } from '@/lib/adnd2e'

interface Props {
  characterId: number
  conditions: CharacterCondition[]
  compact?: boolean
}

export function ConditionManager({ characterId, conditions, compact = false }: Props) {
  const [open, setOpen] = useState(false)
  const [notes, setNotes] = useState('')
  const active = new Set(conditions.map(c => c.condition))

  const add = (condition: string) => {
    router.post(`/characters/${characterId}/conditions`, { condition, notes }, {
      preserveScroll: true,
      onSuccess: () => { setNotes(''); setOpen(false) },
    })
  }

  const remove = (id: number) => {
    router.delete(`/characters/${characterId}/conditions/${id}`, { preserveScroll: true })
  }

  return (
    <div className="flex flex-col gap-2">
      <div className="flex flex-wrap items-center gap-1.5">
        {conditions.length === 0 && (
          <span className="text-[10px] text-[var(--color-text-dim)] uppercase tracking-widest">No conditions</span>
        )}
        {conditions.map(c => (
          <button
            key={c.id}
            type="button"
            onClick={() => remove(c.id)}
            title={c.notes ?? 'Clear'}
            className="group"
          >
            <Badge variant="warning">
              {c.condition}
              <span className="ml-1 opacity-50 group-hover:opacity-100">×</span>
            </Badge>
          </button>
        ))}
        <button
          type="button"
          onClick={() => setOpen(v => !v)}
          className="text-[10px] uppercase tracking-widest text-[var(--color-rune)]"
        >
          {open ? 'Close' : '+ Condition'}
        </button>
      </div>

      {open && (
        <div className="runic-card p-3 flex flex-col gap-2">
          <p className="text-[10px] uppercase tracking-widest text-[var(--color-text-dim)]">
            2E states — click to apply
          </p>
          <div className="flex flex-wrap gap-1.5">
            {CONDITIONS_2E.map(name => (
              <button
                key={name}
                type="button"
                disabled={active.has(name)}
                onClick={() => add(name)}
                className={`px-2 py-0.5 text-[10px] uppercase tracking-wider rounded border ${
                  active.has(name)
                    ? 'opacity-30 border-[var(--color-border)]'
                    : 'border-[var(--color-border)] text-[var(--color-text-base)] hover:border-[var(--color-rune)] hover:text-[var(--color-rune)]'
                }`}
              >
                {name}
              </button>
            ))}
          </div>
          {!compact && (
            <input
              value={notes}
              onChange={e => setNotes(e.target.value)}
              placeholder="Optional note (source, duration…)"
              className="bg-[var(--color-deep)] border border-[var(--color-border)] rounded px-2 py-1 text-xs text-[var(--color-text-base)] focus:outline-none focus:border-[var(--color-rune)]"
            />
          )}
        </div>
      )}
    </div>
  )
}
