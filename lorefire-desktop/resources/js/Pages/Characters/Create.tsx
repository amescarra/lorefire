import React from 'react'
import { Head, useForm } from '@inertiajs/react'
import AppLayout from '@/Layouts/AppLayout'
import { Button } from '@/Components/Button'
import { Input, Select } from '@/Components/Input'
import { RuneDivider } from '@/Components/RuneDivider'
import { Campaign } from '@/types'
import {
  ALIGNMENTS, CLASSES, RACES, SPECIALIST_SCHOOLS,
  formatSigned, hitDie, movementRate, primaryAdjustment, thac0,
} from '@/lib/adnd2e'

interface Props {
  campaign: Campaign | null
  campaigns?: Campaign[]
}

export default function Create({ campaign, campaigns }: Props) {
  const standalone = campaign === null

  const { data, setData, post, processing, errors } = useForm({
    name: '', player_name: '', race: '', subrace: '', class: '', subclass: '',
    level: 1, background: '', alignment: '',
    strength: 10, exceptional_strength: '', dexterity: 10, constitution: 10,
    intelligence: 10, wisdom: 10, charisma: 10,
    max_hp: 8, current_hp: 8, armor_class: 10, speed: 12,
    campaign_id: campaign?.id?.toString() ?? '',
  })

  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    if (standalone) {
      post('/characters')
    } else {
      post(`/campaigns/${campaign!.id}/characters`)
    }
  }

  const adj = (ability: string, score: number) =>
    formatSigned(primaryAdjustment(ability, score, data.exceptional_strength || null, data.class || 'Fighter'))

  const breadcrumbs = standalone
    ? [{ label: 'Characters', href: '/characters' }, { label: 'New Character' }]
    : [
        { label: 'Campaigns', href: '/campaigns' },
        { label: campaign!.name, href: `/campaigns/${campaign!.id}` },
        { label: 'New Character' },
      ]

  const cancelHref = standalone ? '/characters' : `/campaigns/${campaign!.id}`

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="New Character" />

      <div className="max-w-2xl mx-auto">
        <h1 className="font-heading text-2xl text-[var(--color-text-white)] tracking-widest uppercase mb-2">New Character</h1>
        <p className="text-xs text-[var(--color-text-dim)] mb-8">AD&amp;D 2nd Edition sheet — THAC0, descending AC, Vancian memorization.</p>

        <form onSubmit={submit} className="flex flex-col gap-5">
          <div className="grid grid-cols-2 gap-4">
            <Input label="Character Name" value={data.name} onChange={e => setData('name', e.target.value)} error={errors.name} required autoFocus />
            <Input label="Player Name" value={data.player_name} onChange={e => setData('player_name', e.target.value)} placeholder="Optional" />
          </div>

          {standalone && campaigns && campaigns.length > 0 && (
            <Select label="Campaign (optional)" value={data.campaign_id} onChange={e => setData('campaign_id', e.target.value)}>
              <option value="">No campaign</option>
              {campaigns.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
            </Select>
          )}

          <div className="grid grid-cols-3 gap-4">
            <Select
              label="Race"
              value={data.race}
              onChange={e => {
                const race = e.target.value
                setData('race', race)
                setData('speed', movementRate(race))
              }}
              error={errors.race}
            >
              <option value="">Select…</option>
              {RACES.map(r => <option key={r} value={r}>{r}</option>)}
            </Select>
            <Input label="Subrace" value={data.subrace} onChange={e => setData('subrace', e.target.value)} placeholder="Hill dwarf, high elf…" />
            <Select label="Alignment" value={data.alignment} onChange={e => setData('alignment', e.target.value)}>
              <option value="">Select…</option>
              {ALIGNMENTS.map(a => <option key={a} value={a}>{a}</option>)}
            </Select>
          </div>

          <div className="grid grid-cols-3 gap-4">
            <Select label="Class" value={data.class} onChange={e => setData('class', e.target.value)} error={errors.class}>
              <option value="">Select…</option>
              {CLASSES.map(c => <option key={c} value={c}>{c}</option>)}
            </Select>
            {data.class === 'Mage' ? (
              <Select label="Kit / specialist school" value={data.subclass} onChange={e => setData('subclass', e.target.value)}>
                <option value="">Generalist mage</option>
                {SPECIALIST_SCHOOLS.map(s => <option key={s} value={s}>{s}</option>)}
              </Select>
            ) : (
              <Input label="Kit" value={data.subclass} onChange={e => setData('subclass', e.target.value)} placeholder="Optional kit" />
            )}
            <Input label="Level" type="number" min={1} max={20} value={data.level} onChange={e => setData('level', parseInt(e.target.value))} error={errors.level} />
          </div>

          <Input label="Origin / notes" value={data.background} onChange={e => setData('background', e.target.value)} placeholder="Home region, patron, or kit notes…" />

          <RuneDivider label="Ability Scores" />

          <div className="grid grid-cols-6 gap-3">
            {([
              ['STR', 'strength'], ['DEX', 'dexterity'], ['CON', 'constitution'],
              ['INT', 'intelligence'], ['WIS', 'wisdom'], ['CHA', 'charisma']
            ] as [string, keyof typeof data][]).map(([label, key]) => (
              <div key={key} className="flex flex-col items-center gap-1">
                <label className="text-[10px] uppercase tracking-widest text-[var(--color-text-dim)]">{label}</label>
                <input
                  type="number" min={1} max={25}
                  value={data[key] as number}
                  onChange={e => setData(key, parseInt(e.target.value) || 10)}
                  className="w-full text-center bg-[var(--color-deep)] border border-[var(--color-border)] rounded py-2 text-[var(--color-text-white)] font-heading text-lg focus:outline-none focus:border-[var(--color-rune)]"
                />
                <span className="text-xs text-[var(--color-rune)] font-mono">{adj(String(key), data[key] as number)}</span>
              </div>
            ))}
          </div>

          {(data.class === 'Fighter' || data.class === 'Paladin' || data.class === 'Ranger') && data.strength === 18 && (
            <Input
              label="Exceptional Strength (18/xx)"
              value={data.exceptional_strength}
              onChange={e => setData('exceptional_strength', e.target.value)}
              placeholder="01–00"
            />
          )}

          <RuneDivider label="Combat" />

          <div className="grid grid-cols-4 gap-4">
            <Input label="Max HP" type="number" min={1} value={data.max_hp} onChange={e => { const v = parseInt(e.target.value); setData('max_hp', v); setData('current_hp', v) }} />
            <Input label="Armor Class" type="number" value={data.armor_class} onChange={e => setData('armor_class', parseInt(e.target.value))} hint="Descending — 10 unarmored" />
            <Input label="Movement" type="number" min={0} value={data.speed} onChange={e => setData('speed', parseInt(e.target.value))} />
            <div className="flex flex-col justify-end pb-0.5">
              <p className="text-[10px] uppercase tracking-widest text-[var(--color-text-dim)] mb-1">Computed</p>
              <p className="text-sm text-[var(--color-rune-bright)] font-mono">
                THAC0 {data.class ? thac0(data.class, data.level) : '—'} · {data.class ? hitDie(data.class) : 'HD'}
              </p>
            </div>
          </div>

          <div className="flex gap-3 mt-4">
            <Button type="submit" variant="rune" disabled={processing}>{processing ? 'Creating…' : 'Create Character'}</Button>
            <Button type="button" variant="ghost" as="a" href={cancelHref}>Cancel</Button>
          </div>
        </form>
      </div>
    </AppLayout>
  )
}
