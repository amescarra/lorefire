import React from 'react'
import { Input, Select } from '@/Components/Input'
import { SPECIALIST_SCHOOLS, suggestedRacialKits, kitClassNames } from '@/lib/adnd2e'

const OTHER = '__other__'

interface Props {
  race: string
  entries: Array<{ class: string; level: number }>
  value: string
  onChange: (value: string) => void
}

export function KitField({ race, entries, value, onChange }: Props) {
  const filled = entries.filter(e => e.class)
  const hasMage = kitClassNames(filled).includes('Mage')
  const kits = suggestedRacialKits(race, filled)
  const listed = new Set<string>([...(hasMage ? SPECIALIST_SCHOOLS : []), ...kits])
  const custom = value !== '' && !listed.has(value)
  const selectValue = custom ? OTHER : value
  const showSuggestions = hasMage || kits.length > 0

  if (!showSuggestions) {
    return (
      <Input
        label="Kit"
        value={value}
        onChange={e => onChange(e.target.value)}
        placeholder="Optional kit"
      />
    )
  }

  return (
    <div className="flex flex-col gap-3">
      <Select
        label="Kit / specialist"
        value={selectValue}
        onChange={e => {
          const next = e.target.value
          if (next === OTHER) {
            onChange(custom ? value : '')
            return
          }
          onChange(next)
        }}
        hint="Names only. Racial-handbook kits are filtered by race and class."
      >
        <option value="">{hasMage ? 'Generalist mage' : 'No kit'}</option>
        {hasMage && (
          <optgroup label="Specialist school">
            {SPECIALIST_SCHOOLS.map(s => <option key={s} value={s}>{s}</option>)}
          </optgroup>
        )}
        {kits.length > 0 && (
          <optgroup label="Racial handbook kits">
            {kits.map(k => <option key={k} value={k}>{k}</option>)}
          </optgroup>
        )}
        <option value={OTHER}>Other (type a name)</option>
      </Select>
      {custom || selectValue === OTHER ? (
        <Input
          label="Kit name"
          value={value}
          onChange={e => onChange(e.target.value)}
          placeholder="Optional kit"
        />
      ) : null}
    </div>
  )
}
