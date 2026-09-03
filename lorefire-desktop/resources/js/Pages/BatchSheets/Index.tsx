import React, { useState, useMemo } from 'react'
import { Head, router } from '@inertiajs/react'
import AppLayout from '@/Layouts/AppLayout'
import { Button } from '@/Components/Button'
import { Badge } from '@/Components/Badge'
import { HpBar } from '@/Components/HpBar'
import { Campaign, Character } from '@/types'

interface Props {
  characters: Character[]
  campaigns: Campaign[]
  selectedCampaign: Campaign | null
}

export default function BatchSheetsIndex({ characters, campaigns, selectedCampaign }: Props) {
  const [selected, setSelected] = useState<Set<number>>(() => new Set(characters.map(c => c.id)))
  const [campaignFilter, setCampaignFilter] = useState<string>(selectedCampaign ? String(selectedCampaign.id) : '')

  const exportUrl = '/batch-sheets/export'
  const [exportStatus, setExportStatus] = useState<'idle' | 'pending' | 'done' | 'failed'>('idle')
  const [exportError, setExportError] = useState<string | null>(null)

  const filteredCharacters = useMemo(() => {
    if (!campaignFilter) return characters
    const id = Number(campaignFilter)
    return characters.filter(c => c.campaign_id === id)
  }, [characters, campaignFilter])

  const handleCampaignFilter = (value: string) => {
    setCampaignFilter(value)
    // When filter changes, select all matching characters
    if (!value) {
      setSelected(new Set(characters.map(c => c.id)))
    } else {
      const id = Number(value)
      setSelected(new Set(characters.filter(c => c.campaign_id === id).map(c => c.id)))
    }
    if (value) {
      router.get('/batch-sheets', { campaign: value }, { preserveState: true, replace: true })
    } else {
      router.get('/batch-sheets', {}, { preserveState: true, replace: true })
    }
  }

  const toggleAll = () => {
    if (allSelected) {
      setSelected(new Set())
    } else {
      setSelected(new Set(filteredCharacters.map(c => c.id)))
    }
  }

  const toggle = (id: number) => {
    setSelected(prev => {
      const next = new Set(prev)
      if (next.has(id)) next.delete(id)
      else next.add(id)
      return next
    })
  }

  const selectedIds = useMemo(
    () => filteredCharacters.filter(c => selected.has(c.id)).map(c => c.id),
    [filteredCharacters, selected]
  )

  const allSelected = filteredCharacters.length > 0 && selectedIds.length === filteredCharacters.length
  const noneSelected = selectedIds.length === 0

  const handleExport = async () => {
    if (noneSelected || exportStatus === 'pending') return
    await triggerWithIds(selectedIds)
  }

  const triggerWithIds = async (ids: number[]) => {
    if (exportStatus === 'pending') return
    setExportStatus('pending')
    setExportError(null)

    try {
      const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
      const res = await fetch(exportUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          Accept: 'application/json',
        },
        body: JSON.stringify({ character_ids: ids }),
      })
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      const key: string = data.key

      const poll = setInterval(async () => {
        try {
          const pr = await fetch(`/pdf-export/status?key=${encodeURIComponent(key)}`)
          const pd = await pr.json()
          if (pd.status === 'done') {
            clearInterval(poll)
            setExportStatus('done')
            setTimeout(() => setExportStatus('idle'), 3500)
          } else if (pd.status === 'failed') {
            clearInterval(poll)
            setExportError(pd.error ?? 'PDF generation failed')
            setExportStatus('failed')
            setTimeout(() => setExportStatus('idle'), 5000)
          }
        } catch {
          // network hiccup — keep polling
        }
      }, 2000)
    } catch {
      setExportStatus('failed')
      setExportError('Failed to start PDF export')
      setTimeout(() => setExportStatus('idle'), 5000)
    }
  }

  const exportLabel = () => {
    if (exportStatus === 'pending') return 'Generating PDF…'
    if (exportStatus === 'done') return 'Saved to Downloads ✓'
    if (exportStatus === 'failed') return 'Export Failed'
    return `Generate PDF (${selectedIds.length} sheet${selectedIds.length !== 1 ? 's' : ''})`
  }

  const breadcrumbs = [{ label: 'Batch Sheets' }]

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Batch Character Sheets" />

      <div className="max-w-3xl mx-auto">

        {/* Header */}
        <div className="flex items-start justify-between mb-6 flex-wrap gap-3">
          <div>
            <h1 className="font-heading text-2xl text-[var(--color-text-white)] tracking-widest uppercase">
              Batch Sheets
            </h1>
            <p className="text-xs text-[var(--color-text-dim)] mt-1 tracking-wide">
              Select characters and generate a combined printable PDF
            </p>
          </div>

          <div className="flex gap-2 flex-wrap">
            <Button
              variant="rune"
              disabled={noneSelected || exportStatus === 'pending'}
              onClick={handleExport}
            >
              <PrintIcon />
              {exportLabel()}
            </Button>
          </div>
        </div>

        {/* Error banner */}
        {exportError && (
          <div className="mb-4 px-4 py-2 rounded border border-[var(--color-danger)] text-[var(--color-danger)] text-sm">
            {exportError}
          </div>
        )}

        {/* Filters */}
        <div className="runic-card p-4 mb-4 flex items-center gap-4 flex-wrap">
          <div className="flex items-center gap-2">
            <label className="text-xs uppercase tracking-widest text-[var(--color-text-dim)]">Campaign</label>
            <select
              value={campaignFilter}
              onChange={e => handleCampaignFilter(e.target.value)}
              className="bg-[var(--color-deep)] border border-[var(--color-border)] text-[var(--color-text-base)] text-sm rounded px-2 py-1 focus:outline-none focus:border-[var(--color-rune)]"
            >
              <option value="">All Characters</option>
              {campaigns.map(c => (
                <option key={c.id} value={String(c.id)}>{c.name}</option>
              ))}
            </select>
          </div>

          <div className="flex items-center gap-2 ml-auto">
            <span className="text-xs text-[var(--color-text-dim)]">
              {selectedIds.length} of {filteredCharacters.length} selected
            </span>
            <Button variant="ghost" size="sm" onClick={toggleAll}>
              {allSelected ? 'Deselect All' : 'Select All'}
            </Button>
          </div>
        </div>

        {/* Character list */}
        {filteredCharacters.length === 0 ? (
          <div className="py-16 text-center text-[var(--color-text-dim)] text-sm">
            No characters found.
          </div>
        ) : (
          <div className="flex flex-col gap-2">
            {filteredCharacters.map(c => (
              <CharacterRow
                key={c.id}
                character={c}
                checked={selected.has(c.id)}
                onToggle={() => toggle(c.id)}
              />
            ))}
          </div>
        )}

      </div>
    </AppLayout>
  )
}

function CharacterRow({
  character,
  checked,
  onToggle,
}: {
  character: Character
  checked: boolean
  onToggle: () => void
}) {
  return (
    <label className="block cursor-pointer group">
      <div
        className={[
          'runic-card p-4 flex items-center gap-4 transition-all duration-150',
          checked
            ? 'border-[var(--color-rune)] bg-[var(--color-rune-glow)]'
            : 'hover:border-[var(--color-muted)]',
        ].join(' ')}
      >
        {/* Checkbox */}
        <input
          type="checkbox"
          checked={checked}
          onChange={onToggle}
          className="w-4 h-4 shrink-0 accent-[var(--color-rune)] cursor-pointer"
        />

        {/* Name + class */}
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2 mb-0.5 flex-wrap">
            <span className="font-heading text-base text-[var(--color-text-white)] tracking-wide">
              {character.name}
            </span>
            <Badge variant="rune">Lv {character.level}</Badge>
            {character.campaign && (
              <Badge variant="muted">{character.campaign.name}</Badge>
            )}
          </div>
          <p className="text-xs text-[var(--color-text-dim)] truncate">
            {character.race} {character.class}
            {character.player_name ? ` · ${character.player_name}` : ''}
          </p>
          <HpBar current={character.current_hp} max={character.max_hp} className="mt-1.5 max-w-[160px]" />
        </div>

        {/* Combat stats */}
        <div className="flex items-center gap-4 shrink-0 text-center">
          <div>
            <div className="font-heading text-sm text-[var(--color-rune-bright)]">{character.armor_class}</div>
            <div className="text-[10px] uppercase tracking-widest text-[var(--color-text-dim)]">AC</div>
          </div>
          <div>
            <div className="font-heading text-sm text-[var(--color-rune-bright)]">{character.thac0}</div>
            <div className="text-[10px] uppercase tracking-widest text-[var(--color-text-dim)]">THAC0</div>
          </div>
          <div>
            <div className="font-heading text-sm text-[var(--color-rune-bright)]">
              {character.experience_points.toLocaleString()}
            </div>
            <div className="text-[10px] uppercase tracking-widest text-[var(--color-text-dim)]">XP</div>
          </div>
        </div>
      </div>
    </label>
  )
}

function PrintIcon() {
  return (
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
      <polyline points="6 9 6 2 18 2 18 9" />
      <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
      <rect x="6" y="14" width="12" height="8" />
    </svg>
  )
}
