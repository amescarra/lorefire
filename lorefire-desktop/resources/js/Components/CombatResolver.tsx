import React, { useMemo, useState } from 'react'
import { WEAPON_PROFICIENCY_SUGGESTIONS, formatSigned, resolveAttack, resolveInitiative, weaponSpeed } from '@/lib/adnd2e'

/**
 * Live 2E attack / initiative helper: THAC0 vs descending AC, d10 initiative.
 */
export function CombatResolver({ defaultThac0 = 20, defaultDex = 10 }: { defaultThac0?: number; defaultDex?: number }) {
  const [thac0, setThac0] = useState(defaultThac0)
  const [ac, setAc] = useState(10)
  const [roll, setRoll] = useState(10)
  const [dex, setDex] = useState(defaultDex)
  const [initDie, setInitDie] = useState(5)
  const [initExtra, setInitExtra] = useState(0)

  const attack = useMemo(() => resolveAttack(thac0, ac, roll), [thac0, ac, roll])
  const init = useMemo(() => resolveInitiative(initDie, dex, initExtra), [initDie, dex, initExtra])

  return (
    <div className="runic-card p-4 flex flex-col gap-4">
      <div>
        <p className="text-[10px] uppercase tracking-widest text-[var(--color-rune)] font-heading mb-1">Attack (THAC0 vs AC)</p>
        <p className="text-[10px] text-[var(--color-text-dim)] mb-3">Need d20 ≥ THAC0 − AC. A 20 hits, a 1 misses.</p>
        <div className="grid grid-cols-3 gap-3">
          <Num label="THAC0" value={thac0} onChange={setThac0} />
          <Num label="Target AC" value={ac} onChange={setAc} />
          <Num label="d20" value={roll} min={1} max={20} onChange={setRoll} />
        </div>
        <p className="mt-3 text-sm">
          Need <span className="font-mono text-[var(--color-rune-bright)]">{attack.needed}</span>
          {' · '}
          <span className={attack.hit ? 'text-[var(--color-success)]' : 'text-[var(--color-danger)]'}>
            {attack.hit ? 'Hit' : 'Miss'}
          </span>
          {attack.automatic && <span className="text-[10px] text-[var(--color-text-dim)]"> (natural {roll})</span>}
        </p>
      </div>

      <div>
        <p className="text-[10px] uppercase tracking-widest text-[var(--color-rune)] font-heading mb-1">Initiative (d10, low first)</p>
        <div className="grid grid-cols-3 gap-3">
          <Num label="d10" value={initDie} min={1} max={10} onChange={setInitDie} />
          <Num label="DEX" value={dex} min={1} max={25} onChange={setDex} />
          <Num label="Weapon speed" value={initExtra} onChange={setInitExtra} />
        </div>
        <select
          className="mt-2 w-full bg-[var(--color-deep)] border border-[var(--color-border)] rounded px-2 py-1 text-xs text-[var(--color-text-base)]"
          defaultValue=""
          onChange={e => {
            const speed = weaponSpeed(e.target.value)
            if (speed !== null) setInitExtra(speed)
          }}
        >
          <option value="">Apply speed factor…</option>
          {WEAPON_PROFICIENCY_SUGGESTIONS.map(w => (
            <option key={w} value={w}>{w}{weaponSpeed(w) != null ? ` (${weaponSpeed(w)})` : ''}</option>
          ))}
        </select>
        <p className="mt-3 text-sm">
          Total <span className="font-mono text-[var(--color-rune-bright)]">{init.total}</span>
          <span className="text-[10px] text-[var(--color-text-dim)]"> ({formatSigned(init.modifier)} from reaction / modifiers)</span>
        </p>
      </div>
    </div>
  )
}

function Num({ label, value, onChange, min, max }: { label: string; value: number; onChange: (n: number) => void; min?: number; max?: number }) {
  return (
    <div className="flex flex-col gap-1">
      <label className="text-[10px] uppercase tracking-widest text-[var(--color-text-dim)]">{label}</label>
      <input
        type="number"
        min={min}
        max={max}
        value={value}
        onChange={e => onChange(parseInt(e.target.value) || 0)}
        className="w-full text-center bg-[var(--color-deep)] border border-[var(--color-border)] rounded py-1.5 text-[var(--color-text-white)] font-heading text-sm focus:outline-none focus:border-[var(--color-rune)]"
      />
    </div>
  )
}
