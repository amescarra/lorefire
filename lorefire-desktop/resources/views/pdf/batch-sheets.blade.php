<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Character Sheets</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  html, body {
    width: 210mm;
    background: #0e0c0a;
    color: #c8bfa8;
    font-family: 'EB Garamond', 'Palatino Linotype', Palatino, Georgia, serif;
    font-size: 10pt;
    line-height: 1.5;
  }

  @page { size: A4; margin: 0; }

  .sheet {
    width: 210mm;
    min-height: 297mm;
    padding: 12mm 14mm;
    page-break-after: always;
    background: #0e0c0a;
    position: relative;
  }
  .sheet:last-child { page-break-after: avoid; }

  h1, h2, h3, h4 {
    font-family: 'Cinzel', 'Palatino Linotype', Palatino, serif;
    color: #f0ead8;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    line-height: 1.3;
  }

  .sheet-header {
    border-bottom: 1px solid #3a3020;
    padding-bottom: 4mm;
    margin-bottom: 5mm;
    display: flex;
    align-items: flex-start;
    gap: 5mm;
  }
  .portrait {
    width: 22mm;
    height: 22mm;
    object-fit: cover;
    object-position: top;
    border: 1px solid #3a3020;
    border-radius: 1mm;
    background: #141210;
    flex-shrink: 0;
  }
  .portrait-placeholder {
    width: 22mm;
    height: 22mm;
    border: 1px solid #3a3020;
    border-radius: 1mm;
    background: #141210;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Cinzel', serif;
    font-size: 14pt;
    color: #c9963a;
    flex-shrink: 0;
  }
  .header-info { flex: 1; min-width: 0; }
  .char-name {
    font-family: 'Cinzel', serif;
    font-size: 18pt;
    font-weight: 700;
    color: #f0ead8;
    margin-bottom: 1mm;
  }
  .char-subtitle {
    font-size: 9pt;
    color: #c9963a;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin-bottom: 2mm;
  }
  .char-meta {
    font-size: 8pt;
    color: #8b7a60;
    display: flex;
    gap: 4mm;
    flex-wrap: wrap;
  }
  .char-meta span { letter-spacing: 0.05em; }

  /* Two-column grid */
  .two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4mm;
    margin-bottom: 4mm;
  }
  .three-col {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 3mm;
    margin-bottom: 4mm;
  }

  .section {
    margin-bottom: 4mm;
  }
  .section-title {
    font-family: 'Cinzel', serif;
    font-size: 7.5pt;
    color: #c9963a;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    border-bottom: 1px solid #2e2a22;
    padding-bottom: 1mm;
    margin-bottom: 2mm;
  }

  /* Stat grid */
  .stat-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 2mm;
  }
  .stat-box {
    background: #141210;
    border: 1px solid #2e2a22;
    border-radius: 1mm;
    padding: 2mm;
    text-align: center;
  }
  .stat-label {
    font-family: 'Cinzel', serif;
    font-size: 6pt;
    color: #8b7a60;
    letter-spacing: 0.1em;
    text-transform: uppercase;
  }
  .stat-value {
    font-size: 13pt;
    font-weight: 700;
    color: #f0ead8;
    line-height: 1.2;
    margin: 0.5mm 0;
  }
  .stat-mod {
    font-size: 7pt;
    color: #c9963a;
  }

  /* Combat row */
  .combat-row {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 2mm;
  }
  .combat-box {
    background: #141210;
    border: 1px solid #2e2a22;
    border-radius: 1mm;
    padding: 2mm 3mm;
    text-align: center;
  }
  .combat-label {
    font-family: 'Cinzel', serif;
    font-size: 6pt;
    color: #8b7a60;
    text-transform: uppercase;
    letter-spacing: 0.1em;
  }
  .combat-value {
    font-size: 12pt;
    font-weight: 700;
    color: #c9963a;
    line-height: 1.3;
  }

  /* Saves */
  .saves-table { width: 100%; border-collapse: collapse; }
  .saves-table td, .saves-table th {
    padding: 1mm 2mm;
    font-size: 8pt;
    border-bottom: 1px solid #1e1c18;
  }
  .saves-table th {
    font-family: 'Cinzel', serif;
    font-size: 6.5pt;
    color: #8b7a60;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    text-align: left;
    padding-bottom: 1.5mm;
  }
  .saves-table td:last-child { text-align: right; color: #c9963a; font-weight: 600; }

  /* Spell table */
  .spell-table { width: 100%; border-collapse: collapse; }
  .spell-table th {
    font-family: 'Cinzel', serif;
    font-size: 6pt;
    color: #8b7a60;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    text-align: left;
    padding: 1mm 2mm 1.5mm;
    border-bottom: 1px solid #2e2a22;
  }
  .spell-table td {
    font-size: 7.5pt;
    padding: 1mm 2mm;
    border-bottom: 1px solid #1a1814;
    color: #c8bfa8;
  }
  .spell-table td.num { text-align: center; color: #c9963a; }
  .spell-level-row td {
    background: #141210;
    font-family: 'Cinzel', serif;
    font-size: 6.5pt;
    color: #8b6c3e;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 1mm 2mm;
  }

  /* Inventory */
  .inv-table { width: 100%; border-collapse: collapse; }
  .inv-table th {
    font-family: 'Cinzel', serif;
    font-size: 6pt;
    color: #8b7a60;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    text-align: left;
    padding: 1mm 2mm 1.5mm;
    border-bottom: 1px solid #2e2a22;
  }
  .inv-table td {
    font-size: 8pt;
    padding: 1mm 2mm;
    border-bottom: 1px solid #1a1814;
    color: #c8bfa8;
  }
  .inv-table td.qty { text-align: center; width: 14mm; }
  .inv-table td.wt  { text-align: right; width: 14mm; color: #8b7a60; }
  .equipped-dot { color: #c9963a; }

  /* Gold row */
  .gold-row {
    display: flex;
    gap: 3mm;
    font-size: 8.5pt;
  }
  .gold-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5mm;
  }
  .gold-item .gl { font-family: 'Cinzel', serif; font-size: 6pt; color: #8b7a60; text-transform: uppercase; letter-spacing: 0.08em; }
  .gold-item .gv { color: #c9963a; font-weight: 600; }

  /* Notes */
  .notes-block { font-size: 8.5pt; color: #c8bfa8; line-height: 1.6; }
  .notes-label { font-family: 'Cinzel', serif; font-size: 6.5pt; color: #8b7a60; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.5mm; }

  /* XP bar */
  .xp-label { font-family: 'Cinzel', serif; font-size: 6.5pt; color: #8b7a60; text-transform: uppercase; letter-spacing: 0.08em; }
  .xp-value { font-size: 9pt; color: #c8bfa8; }

  /* HP bar */
  .hp-bar-wrap { margin-top: 1mm; }
  .hp-track {
    background: #1e1c18;
    border: 1px solid #2e2a22;
    border-radius: 1mm;
    height: 4mm;
    overflow: hidden;
  }
  .hp-fill {
    height: 100%;
    background: #2e7a3e;
    border-radius: 1mm;
    transition: width 0s;
  }
  .hp-text { font-size: 7.5pt; color: #8b7a60; margin-top: 0.5mm; }

  /* Conditions */
  .condition-tag {
    display: inline-block;
    background: #2c1a0e;
    border: 1px solid #8b3a1a;
    border-radius: 1mm;
    padding: 0.5mm 2mm;
    font-size: 7.5pt;
    color: #e07050;
    margin: 0.5mm 1mm 0.5mm 0;
  }

  /* Proficiencies */
  .prof-list { font-size: 8.5pt; color: #c8bfa8; line-height: 1.7; }

  /* Divider */
  .divider {
    width: 100%;
    height: 1px;
    background: linear-gradient(to right, transparent, #3a3020, transparent);
    margin: 3mm 0;
  }

  .footer {
    position: absolute;
    bottom: 8mm;
    left: 14mm;
    right: 14mm;
    padding-top: 2mm;
    border-top: 1px solid #2e2a22;
    font-size: 6.5pt;
    color: #6b6050;
    font-family: 'Cinzel', serif;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    display: flex;
    justify-content: space-between;
  }
</style>
</head>
<body>

@foreach($characters as $character)
@php
  // --- Ability modifier helper (mirrors Adnd2e::primaryAdjustment) ---
  $modMap = [
    3  => -3, 4  => -2, 5  => -2, 6  => -1, 7  => -1,
    8  => 0,  9  => 0,  10 => 0,  11 => 0,  12 => 0,
    13 => 1,  14 => 1,  15 => 1,  16 => 2,  17 => 2,
    18 => 3,  19 => 3,  20 => 3,  21 => 4,  22 => 4, 25 => 7,
  ];
  $strModMap = [
    3=>-3, 4=>-2, 5=>-2, 6=>-1, 7=>-1, 8=>0, 9=>0, 10=>0, 11=>0, 12=>0,
    13=>0, 14=>0, 15=>0, 16=>0, 17=>1, 18=>1,
  ];
  // Dex reactive (AC, saves) uses standard mod map
  $fmt = fn(int $v) => $v > 0 ? "+{$v}" : (string)$v;
  $mod = fn(int $score) => $fmt($modMap[$score] ?? 0);
  $strMod = function(int $str, ?string $exc) use($fmt, $modMap, $strModMap) {
    if ($str === 18 && $exc) {
      $p = (int)$exc;
      if ($p <= 50)  return '+1';
      if ($p <= 75)  return '+2';
      if ($p <= 90)  return '+3';
      if ($p <= 99)  return '+4';
      return '+6'; // 00
    }
    return $fmt($modMap[$str] ?? 0);
  };

  // HP percent
  $hpMax = max($character->max_hp, 1);
  $hpPct = max(0, min(100, round($character->current_hp / $hpMax * 100)));

  // Classes display
  $classDisplay = collect($character->class_levels ?? [])
    ->map(fn($cl) => "{$cl['class']} {$cl['level']}")
    ->join(' / ');
  if (!$classDisplay) {
    $classDisplay = "{$character->class} {$character->level}";
  }

  // Saves
  $saves = $character->saving_throws ?? [];

  // Spells grouped by level
  $spellsByLevel = collect($character->spells ?? [])
    ->sortBy(['level', 'name'])
    ->groupBy('level');

  // Coin totals
  $hasCoins = ($character->copper + $character->silver + $character->electrum + $character->gold + $character->platinum) > 0;

  // Notes
  $noteFields = [
    'Mannerisms'   => $character->mannerisms,
    'Motivations'  => $character->motivations,
    'Ties'         => $character->ties,
    'Weaknesses'   => $character->weaknesses,
    'Backstory'    => $character->backstory,
    'Appearance'   => $character->appearance_description,
  ];
  $noteFields = array_filter($noteFields);

  // Portrait
  $portraitSrc = $character->portrait_path
    ? "{$baseUrl}/storage-file/{$character->portrait_path}"
    : null;
@endphp

<div class="sheet">

  {{-- Header --}}
  <div class="sheet-header">
    @if($portraitSrc)
      <img src="{{ $portraitSrc }}" alt="{{ $character->name }}" class="portrait" />
    @else
      <div class="portrait-placeholder">{{ mb_strtoupper(mb_substr($character->name, 0, 1)) }}</div>
    @endif

    <div class="header-info">
      <div class="char-name">{{ $character->name }}</div>
      <div class="char-subtitle">{{ $classDisplay }}{{ $character->subclass ? ' — ' . $character->subclass : '' }}</div>
      <div class="char-meta">
        <span>{{ $character->race }}{{ $character->subrace ? ' (' . $character->subrace . ')' : '' }}</span>
        @if($character->alignment)
          <span>{{ $character->alignment }}</span>
        @endif
        @if($character->player_name)
          <span>Player: {{ $character->player_name }}</span>
        @endif
        @if($character->campaign)
          <span>Campaign: {{ $character->campaign->name }}</span>
        @endif
      </div>

      {{-- HP bar --}}
      <div class="hp-bar-wrap">
        <div class="hp-track">
          <div class="hp-fill" style="width: {{ $hpPct }}%;"></div>
        </div>
        <div class="hp-text">HP {{ $character->current_hp }} / {{ $character->max_hp }}
          @if($character->hit_die) &nbsp;·&nbsp; {{ $character->hit_die }} @endif
        </div>
      </div>
    </div>

    {{-- XP --}}
    <div style="text-align:right; flex-shrink:0;">
      <div class="xp-label">Experience</div>
      <div class="xp-value">{{ number_format($character->experience_points) }} XP</div>
      @if($character->speed)
        <div style="margin-top:2mm;">
          <div class="xp-label">Speed</div>
          <div class="xp-value">{{ $character->speed }}</div>
        </div>
      @endif
    </div>
  </div>

  {{-- Conditions --}}
  @if($character->conditions && count($character->conditions))
    <div class="section" style="margin-bottom:3mm;">
      @foreach($character->conditions as $cond)
        <span class="condition-tag">{{ $cond->condition }}{{ $cond->notes ? ': ' . $cond->notes : '' }}</span>
      @endforeach
    </div>
  @endif

  <div class="two-col">
    <div>
      {{-- Ability Scores --}}
      <div class="section">
        <div class="section-title">Ability Scores</div>
        <div class="stat-grid">
          @foreach([
            ['STR','strength'],['DEX','dexterity'],['CON','constitution'],
            ['INT','intelligence'],['WIS','wisdom'],['CHA','charisma']
          ] as [$lbl,$key])
            <div class="stat-box">
              <div class="stat-label">{{ $lbl }}</div>
              <div class="stat-value">{{ $character->$key }}</div>
              <div class="stat-mod">
                @if($key === 'strength')
                  {{ $strMod($character->strength, $character->exceptional_strength) }}
                  @if($character->exceptional_strength && $character->strength === 18)
                    /{{ $character->exceptional_strength }}
                  @endif
                @else
                  {{ $mod($character->$key) }}
                @endif
              </div>
            </div>
          @endforeach
        </div>
      </div>

      {{-- Combat --}}
      <div class="section">
        <div class="section-title">Combat</div>
        <div class="combat-row">
          <div class="combat-box">
            <div class="combat-label">AC</div>
            <div class="combat-value">{{ $character->armor_class }}</div>
          </div>
          <div class="combat-box">
            <div class="combat-label">THAC0</div>
            <div class="combat-value">{{ $character->thac0 }}</div>
          </div>
          <div class="combat-box">
            <div class="combat-label">Current HP</div>
            <div class="combat-value">{{ $character->current_hp }}</div>
          </div>
          <div class="combat-box">
            <div class="combat-label">Max HP</div>
            <div class="combat-value">{{ $character->max_hp }}</div>
          </div>
          @if($character->psp_max)
            <div class="combat-box">
              <div class="combat-label">PSP</div>
              <div class="combat-value">{{ $character->psp_current }}/{{ $character->psp_max }}</div>
            </div>
          @endif
        </div>
      </div>

      {{-- Saving Throws --}}
      @if(count($saves))
        <div class="section">
          <div class="section-title">Saving Throws</div>
          <table class="saves-table">
            <tr><th>Category</th><th style="text-align:right;">Score</th></tr>
            @foreach($saves as $cat => $val)
              <tr>
                <td>{{ ucwords(str_replace('_', ' ', $cat)) }}</td>
                <td>{{ $val }}</td>
              </tr>
            @endforeach
          </table>
        </div>
      @endif
    </div>

    <div>
      {{-- Wealth --}}
      @if($hasCoins)
        <div class="section">
          <div class="section-title">Wealth</div>
          <div class="gold-row">
            @foreach([
              ['PP','platinum'],['GP','gold'],['EP','electrum'],
              ['SP','silver'],['CP','copper']
            ] as [$lbl,$key])
              @if($character->$key > 0)
                <div class="gold-item">
                  <span class="gl">{{ $lbl }}</span>
                  <span class="gv">{{ number_format($character->$key) }}</span>
                </div>
              @endif
            @endforeach
          </div>
        </div>
      @endif

      {{-- Weapon Proficiencies --}}
      @if($character->weapon_proficiencies && count($character->weapon_proficiencies))
        <div class="section">
          <div class="section-title">Weapon Proficiencies</div>
          <div class="prof-list">{{ implode(', ', $character->weapon_proficiencies) }}</div>
        </div>
      @endif

      {{-- Nonweapon Proficiencies --}}
      @if($character->nonweapon_proficiencies && count($character->nonweapon_proficiencies))
        <div class="section">
          <div class="section-title">Nonweapon Proficiencies</div>
          <div class="prof-list">{{ implode(', ', $character->nonweapon_proficiencies) }}</div>
        </div>
      @endif

      {{-- Priest Spheres --}}
      @if($character->priest_spheres && (count($character->priest_spheres['major'] ?? []) + count($character->priest_spheres['minor'] ?? [])))
        <div class="section">
          <div class="section-title">Priest Spheres</div>
          @if(!empty($character->priest_spheres['major']))
            <div class="notes-label">Major</div>
            <div class="prof-list" style="margin-bottom:1mm;">{{ implode(', ', $character->priest_spheres['major']) }}</div>
          @endif
          @if(!empty($character->priest_spheres['minor']))
            <div class="notes-label">Minor</div>
            <div class="prof-list">{{ implode(', ', $character->priest_spheres['minor']) }}</div>
          @endif
        </div>
      @endif

      {{-- Psionic Powers --}}
      @if($character->psionic_powers && count($character->psionic_powers))
        <div class="section">
          <div class="section-title">Psionic Powers</div>
          <div class="prof-list">{{ implode(', ', $character->psionic_powers) }}</div>
        </div>
      @endif
    </div>
  </div>

  {{-- Spells --}}
  @if($spellsByLevel->isNotEmpty())
    <div class="section">
      <div class="section-title">Memorized Spells</div>
      <table class="spell-table">
        <thead>
          <tr>
            <th>Spell</th>
            <th style="width:16mm; text-align:center;">School</th>
            <th style="width:12mm; text-align:center;">Prep</th>
            <th style="width:12mm; text-align:center;">Cast</th>
          </tr>
        </thead>
        <tbody>
          @foreach($spellsByLevel as $level => $spells)
            <tr class="spell-level-row"><td colspan="4">Level {{ $level }}</td></tr>
            @foreach($spells as $spell)
              <tr>
                <td>{{ $spell->name }}</td>
                <td class="num">{{ $spell->school ?: '—' }}</td>
                <td class="num">{{ $spell->times_memorized ?? ($spell->is_prepared ? '✓' : '—') }}</td>
                <td class="num">{{ $spell->times_cast ?? ($spell->is_cast ? '✓' : '—') }}</td>
              </tr>
            @endforeach
          @endforeach
        </tbody>
      </table>
    </div>
  @endif

  {{-- Inventory --}}
  @if($character->inventoryItems && $character->inventoryItems->isNotEmpty())
    <div class="section">
      <div class="section-title">Inventory</div>
      <table class="inv-table">
        <thead>
          <tr>
            <th>Item</th>
            <th style="width:14mm; text-align:center;">Qty</th>
            <th style="width:16mm; text-align:right;">Weight</th>
          </tr>
        </thead>
        <tbody>
          @foreach($character->inventoryItems->sortBy('name') as $item)
            <tr>
              <td>
                {{ $item->equipped ? '◆ ' : '' }}{{ $item->name }}
                {{ $item->is_magical ? ' ✦' : '' }}
              </td>
              <td class="qty">{{ $item->quantity }}</td>
              <td class="wt">{{ $item->weight > 0 ? $item->weight : '—' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif

  {{-- Class Features --}}
  @if($character->features && $character->features->isNotEmpty())
    <div class="section">
      <div class="section-title">Class Features</div>
      <div class="prof-list">
        @foreach($character->features as $feat)
          <span style="color:#f0ead8; font-weight:600;">{{ $feat->name }}</span>
          @if($feat->has_uses && $feat->max_uses)
            <span style="color:#c9963a;"> ({{ $feat->uses_remaining }}/{{ $feat->max_uses }})</span>
          @endif
          @if($feat->description)
            <span style="color:#8b7a60;"> — {{ Str::limit($feat->description, 100) }}</span>
          @endif
          <br>
        @endforeach
      </div>
    </div>
  @endif

  {{-- Notes --}}
  @if(count($noteFields))
    <div class="section">
      <div class="section-title">Notes</div>
      <div class="notes-block">
        @foreach($noteFields as $label => $text)
          <div style="margin-bottom:2mm;">
            <div class="notes-label">{{ $label }}</div>
            <div>{{ $text }}</div>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  {{-- Footer --}}
  <div class="footer">
    <span>{{ $character->name }} · {{ $classDisplay }}</span>
    <span>Lorefire · AD&amp;D 2E</span>
  </div>

</div>
@endforeach

</body>
</html>
