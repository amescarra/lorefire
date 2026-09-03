<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Character Record Sheets</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  html, body {
    background: #fffef8;
    color: #111;
    font-family: "Times New Roman", Times, Georgia, serif;
    font-size: 9pt;
    line-height: 1.25;
  }

  @page { size: letter; margin: 0.35in; }

  .sheet {
    width: 7.8in;
    min-height: 10.1in;
    margin: 0 auto 0.25in;
    padding: 8px 10px 22px;
    background: #fffef8;
    color: #111;
    border: 2px solid #111;
    page-break-after: always;
    position: relative;
  }
  .sheet:last-child { page-break-after: avoid; margin-bottom: 0; }

  .masthead {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    border-bottom: 2px solid #111;
    padding-bottom: 3px;
    margin-bottom: 6px;
  }
  .masthead-title {
    font-size: 13pt;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
  }
  .masthead-edition {
    font-size: 8pt;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }

  .fields {
    display: grid;
    grid-template-columns: 1.4fr 1fr 1fr;
    gap: 4px 8px;
    margin-bottom: 6px;
  }
  .fields.wide { grid-template-columns: 1.6fr 1.2fr 0.8fr 0.9fr; }
  .field {
    border-bottom: 1px solid #111;
    min-height: 22px;
    padding: 0 2px 1px;
  }
  .field-label {
    display: block;
    font-size: 6.5pt;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #111;
  }
  .field-value {
    display: block;
    font-size: 10pt;
    min-height: 13px;
  }

  .box {
    border: 1px solid #111;
    padding: 4px 6px;
    margin-bottom: 6px;
  }
  .box-title {
    font-size: 7.5pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    border-bottom: 1px solid #111;
    margin: -4px -6px 4px;
    padding: 2px 6px;
  }

  .cols {
    display: grid;
    grid-template-columns: 1.15fr 0.85fr;
    gap: 6px;
    margin-bottom: 6px;
  }
  .cols-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 6px;
    margin-bottom: 6px;
  }

  table.form {
    width: 100%;
    border-collapse: collapse;
  }
  table.form th, table.form td {
    border: 1px solid #111;
    padding: 2px 4px;
    text-align: center;
    font-size: 9pt;
  }
  table.form th {
    font-size: 6.5pt;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 700;
    background: #f3f0ea;
  }
  table.form td.score { font-size: 12pt; font-weight: 700; }
  table.form td.left, table.form th.left { text-align: left; }

  .combat-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 4px;
  }
  .stat-cell {
    border: 1px solid #111;
    text-align: center;
    padding: 3px 2px;
  }
  .stat-cell .lbl {
    display: block;
    font-size: 6pt;
    text-transform: uppercase;
    letter-spacing: 0.06em;
  }
  .stat-cell .val {
    display: block;
    font-size: 12pt;
    font-weight: 700;
    line-height: 1.15;
  }

  .list {
    font-size: 8.5pt;
    line-height: 1.4;
  }
  .muted-label {
    font-size: 6.5pt;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-top: 3px;
  }

  .notes p { font-size: 8.5pt; margin-bottom: 3px; }

  .footer {
    position: absolute;
    left: 10px;
    right: 10px;
    bottom: 5px;
    border-top: 1px solid #111;
    padding-top: 2px;
    font-size: 6.5pt;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    display: flex;
    justify-content: space-between;
  }
</style>
</head>
<body>

@foreach($characters as $character)
@php
  $modMap = [
    3 => -3, 4 => -2, 5 => -2, 6 => -1, 7 => -1,
    8 => 0, 9 => 0, 10 => 0, 11 => 0, 12 => 0,
    13 => 1, 14 => 1, 15 => 1, 16 => 2, 17 => 2,
    18 => 3, 19 => 3, 20 => 3, 21 => 4, 22 => 4, 25 => 7,
  ];
  $fmt = fn (int $v) => $v > 0 ? "+{$v}" : (string) $v;
  $mod = fn (int $score) => $fmt($modMap[$score] ?? 0);
  $strMod = function (int $str, ?string $exc) use ($fmt, $modMap) {
      if ($str === 18 && $exc) {
          $p = (int) $exc;
          if ($p <= 50) return '+1';
          if ($p <= 75) return '+2';
          if ($p <= 90) return '+3';
          if ($p <= 99) return '+4';
          return '+6';
      }
      return $fmt($modMap[$str] ?? 0);
  };

  $classDisplay = collect($character->class_levels ?? [])
      ->map(fn ($cl) => ($cl['class'] ?? '').' '.($cl['level'] ?? ''))
      ->filter()
      ->join(' / ');
  if ($classDisplay === '') {
      $classDisplay = trim($character->class.' '.$character->level);
  }
  if (($character->class_path ?? '') === 'dual') {
      $classDisplay = 'Dual-class '.$classDisplay;
  }

  $saves = $character->saving_throws ?? [];
  $saveLabels = [
      'paralyzation' => 'Paralyzation, Poison, or Death Magic',
      'paralysis_poison_death' => 'Paralyzation, Poison, or Death Magic',
      'rod' => 'Rod, Staff, or Wand',
      'rod_staff_wand' => 'Rod, Staff, or Wand',
      'petrification' => 'Petrification or Polymorph',
      'petrification_polymorph' => 'Petrification or Polymorph',
      'breath' => 'Breath Weapon',
      'breath_weapon' => 'Breath Weapon',
      'spell' => 'Spell',
      'spells' => 'Spell',
  ];

  $spellsByLevel = collect($character->spells ?? [])->sortBy(['level', 'name'])->groupBy('level');
  $hasCoins = ($character->copper + $character->silver + $character->electrum + $character->gold + $character->platinum) > 0;
  $equipped = collect($character->inventoryItems ?? [])->where('equipped', true)->sortBy('name');
  $inventory = collect($character->inventoryItems ?? [])->sortBy('name');

  $noteFields = array_filter([
      'Mannerisms'  => $character->mannerisms,
      'Motivations' => $character->motivations,
      'Ties'        => $character->ties,
      'Weaknesses'  => $character->weaknesses,
      'Backstory'   => $character->backstory,
      'Appearance'  => $character->appearance_description,
  ]);

  $memorization = $character->memorization ?? [];
  $memorizationUsed = $character->memorization_used ?? [];
  $hasMemorization = collect($memorization)->contains(fn ($cap) => (int) $cap > 0);
  $hasSpheres = $character->priest_spheres && (count($character->priest_spheres['major'] ?? []) + count($character->priest_spheres['minor'] ?? []));
  $hasPsp = $character->psp_max || $character->psp_current;
  $hasPowers = $character->psionic_powers && count($character->psionic_powers);
@endphp

<div class="sheet">
  <div class="masthead">
    <div class="masthead-title">Character Record Sheet</div>
    <div class="masthead-edition">AD&amp;D 2nd Edition</div>
  </div>

  <div class="fields wide">
    <div class="field">
      <span class="field-label">Character Name</span>
      <span class="field-value">{{ $character->name }}</span>
    </div>
    <div class="field">
      <span class="field-label">Player</span>
      <span class="field-value">{{ $character->player_name ?: '' }}</span>
    </div>
    <div class="field">
      <span class="field-label">Alignment</span>
      <span class="field-value">{{ $character->alignment ?: '' }}</span>
    </div>
    <div class="field">
      <span class="field-label">Experience Points</span>
      <span class="field-value">{{ number_format($character->experience_points) }}</span>
    </div>
  </div>
  <div class="fields wide">
    <div class="field">
      <span class="field-label">Race</span>
      <span class="field-value">{{ $character->race }}{{ $character->subrace ? ' ('.$character->subrace.')' : '' }}</span>
    </div>
    <div class="field">
      <span class="field-label">Class / Level</span>
      <span class="field-value">{{ $classDisplay }}</span>
    </div>
    <div class="field">
      <span class="field-label">Kit / Specialist</span>
      <span class="field-value">{{ $character->subclass ?: '' }}</span>
    </div>
    <div class="field">
      <span class="field-label">Campaign</span>
      <span class="field-value">{{ $character->campaign->name ?? '' }}</span>
    </div>
  </div>

  <div class="cols">
    <div class="box">
      <div class="box-title">Ability Scores</div>
      <table class="form">
        <tr>
          @foreach (['STR','DEX','CON','INT','WIS','CHA'] as $lbl)
            <th>{{ $lbl }}</th>
          @endforeach
        </tr>
        <tr>
          <td class="score">
            {{ $character->strength }}@if($character->exceptional_strength && $character->strength === 18)/{{ $character->exceptional_strength }}@endif
          </td>
          <td class="score">{{ $character->dexterity }}</td>
          <td class="score">{{ $character->constitution }}</td>
          <td class="score">{{ $character->intelligence }}</td>
          <td class="score">{{ $character->wisdom }}</td>
          <td class="score">{{ $character->charisma }}</td>
        </tr>
        <tr>
          <td>{{ $strMod($character->strength, $character->exceptional_strength) }}</td>
          <td>{{ $mod($character->dexterity) }}</td>
          <td>{{ $mod($character->constitution) }}</td>
          <td>{{ $mod($character->intelligence) }}</td>
          <td>{{ $mod($character->wisdom) }}</td>
          <td>{{ $mod($character->charisma) }}</td>
        </tr>
      </table>
    </div>

    <div class="box">
      <div class="box-title">Combat</div>
      <div class="combat-grid">
        <div class="stat-cell">
          <span class="lbl">Hit Points</span>
          <span class="val">{{ $character->current_hp }}/{{ $character->max_hp }}</span>
        </div>
        <div class="stat-cell">
          <span class="lbl">Armor Class</span>
          <span class="val">{{ $character->armor_class }}</span>
        </div>
        <div class="stat-cell">
          <span class="lbl">THAC0</span>
          <span class="val">{{ $character->thac0 }}</span>
        </div>
        @if($character->speed)
          <div class="stat-cell">
            <span class="lbl">Movement</span>
            <span class="val">{{ $character->speed }}</span>
          </div>
        @endif
        @if($character->hit_die)
          <div class="stat-cell">
            <span class="lbl">Hit Die</span>
            <span class="val">{{ $character->hit_die }}</span>
          </div>
        @endif
      </div>
    </div>
  </div>

  @if($character->conditions && count($character->conditions))
    <div class="box">
      <div class="box-title">Conditions</div>
      <div class="list">
        {{ collect($character->conditions)->map(fn ($c) => $c->condition.($c->notes ? ': '.$c->notes : ''))->join('; ') }}
      </div>
    </div>
  @endif

  <div class="cols">
    @if(count($saves))
      <div class="box">
        <div class="box-title">Saving Throws</div>
        <table class="form">
          @foreach($saves as $cat => $val)
            <tr>
              <td class="left">{{ $saveLabels[$cat] ?? ucwords(str_replace('_', ' ', (string) $cat)) }}</td>
              <td>{{ $val }}</td>
            </tr>
          @endforeach
        </table>
      </div>
    @endif

    <div>
      @if($character->weapon_proficiencies && count($character->weapon_proficiencies))
        <div class="box">
          <div class="box-title">Weapon Proficiencies</div>
          <div class="list">{{ implode(', ', $character->weapon_proficiencies) }}</div>
        </div>
      @endif
      @if($character->nonweapon_proficiencies && count($character->nonweapon_proficiencies))
        <div class="box">
          <div class="box-title">Nonweapon Proficiencies</div>
          <div class="list">{{ implode(', ', $character->nonweapon_proficiencies) }}</div>
        </div>
      @endif
    </div>
  </div>

  @if($equipped->isNotEmpty())
    <div class="box">
      <div class="box-title">Weapons / Equipped</div>
      <table class="form">
        <tr>
          <th class="left">Item</th>
          <th>Qty</th>
          <th>Wt</th>
        </tr>
        @foreach($equipped as $item)
          <tr>
            <td class="left">{{ $item->name }}{{ $item->is_magical ? ' (magical)' : '' }}</td>
            <td>{{ $item->quantity }}</td>
            <td>{{ $item->weight > 0 ? $item->weight : '' }}</td>
          </tr>
        @endforeach
      </table>
    </div>
  @endif

  @if($hasPsp || $hasPowers)
    <div class="box">
      <div class="box-title">Psionics</div>
      @if($hasPsp)
        <div class="list">PSP {{ $character->psp_current }} / {{ $character->psp_max }}</div>
      @endif
      @if($hasPowers)
        <div class="list">{{ implode(', ', $character->psionic_powers) }}</div>
      @endif
    </div>
  @endif

  @if($hasSpheres)
    <div class="box">
      <div class="box-title">Priest Spheres</div>
      @if(!empty($character->priest_spheres['major']))
        <div class="muted-label">Major</div>
        <div class="list">{{ implode(', ', $character->priest_spheres['major']) }}</div>
      @endif
      @if(!empty($character->priest_spheres['minor']))
        <div class="muted-label">Minor</div>
        <div class="list">{{ implode(', ', $character->priest_spheres['minor']) }}</div>
      @endif
    </div>
  @endif

  @if($spellsByLevel->isNotEmpty() || $hasMemorization)
    <div class="box">
      <div class="box-title">Spells</div>
      @if(count($memorization))
        <div class="list" style="margin-bottom:4px;">
          @foreach($memorization as $lvl => $cap)
            @if((int) $cap > 0)
              L{{ $lvl }}: {{ (int) ($memorizationUsed[$lvl] ?? 0) }}/{{ (int) $cap }} used
            @endif
          @endforeach
        </div>
      @endif
      @if($spellsByLevel->isNotEmpty())
        <table class="form">
          <tr>
            <th class="left">Spell</th>
            <th>Level</th>
            <th>Memorized</th>
            <th>School</th>
          </tr>
          @foreach($spellsByLevel as $level => $spells)
            @foreach($spells as $spell)
              <tr>
                <td class="left">{{ $spell->name }}</td>
                <td>{{ $level }}</td>
                <td>{{ $spell->times_memorized ?? ($spell->is_prepared ? '1' : '0') }}</td>
                <td>{{ $spell->school ?: '' }}</td>
              </tr>
            @endforeach
          @endforeach
        </table>
      @endif
    </div>
  @endif

  @if($hasCoins || $inventory->isNotEmpty())
    <div class="cols">
      @if($inventory->isNotEmpty())
        <div class="box">
          <div class="box-title">Equipment / Inventory</div>
          <table class="form">
            <tr>
              <th class="left">Item</th>
              <th>Qty</th>
              <th>Wt</th>
            </tr>
            @foreach($inventory as $item)
              <tr>
                <td class="left">{{ $item->equipped ? '* ' : '' }}{{ $item->name }}{{ $item->is_magical ? ' (magical)' : '' }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ $item->weight > 0 ? $item->weight : '' }}</td>
              </tr>
            @endforeach
          </table>
        </div>
      @endif
      @if($hasCoins)
        <div class="box">
          <div class="box-title">Wealth</div>
          <table class="form">
            <tr>
              @foreach(['PP' => 'platinum', 'GP' => 'gold', 'EP' => 'electrum', 'SP' => 'silver', 'CP' => 'copper'] as $lbl => $key)
                @if($character->$key > 0)
                  <th>{{ $lbl }}</th>
                @endif
              @endforeach
            </tr>
            <tr>
              @foreach(['platinum','gold','electrum','silver','copper'] as $key)
                @if($character->$key > 0)
                  <td>{{ number_format($character->$key) }}</td>
                @endif
              @endforeach
            </tr>
          </table>
        </div>
      @endif
    </div>
  @endif

  @if($character->features && $character->features->isNotEmpty())
    <div class="box">
      <div class="box-title">Class Features</div>
      <div class="list">
        @foreach($character->features as $feat)
          {{ $feat->name }}@if($feat->has_uses && $feat->max_uses) ({{ $feat->uses_remaining }}/{{ $feat->max_uses }})@endif{{ $loop->last ? '' : '; ' }}
        @endforeach
      </div>
    </div>
  @endif

  @if(count($noteFields))
    <div class="box notes">
      <div class="box-title">Notes</div>
      @foreach($noteFields as $label => $text)
        <div class="muted-label">{{ $label }}</div>
        <p>{{ $text }}</p>
      @endforeach
    </div>
  @endif

  <div class="footer">
    <span>{{ $character->name }} · {{ $classDisplay }}</span>
    <span>Character Record Sheet</span>
  </div>
</div>
@endforeach

</body>
</html>
