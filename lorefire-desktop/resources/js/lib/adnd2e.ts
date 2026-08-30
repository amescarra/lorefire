/** AD&D 2nd Edition mechanical helpers (mirrors App\\Support\\Adnd2e). */

export const RACES = ['Human', 'Dwarf', 'Elf', 'Gnome', 'Half-Elf', 'Halfling', 'Half-Orc', 'Other'] as const

export const CLASSES = ['Fighter', 'Paladin', 'Ranger', 'Mage', 'Cleric', 'Druid', 'Thief', 'Bard'] as const

export const SPECIALIST_SCHOOLS = [
  'Abjurer', 'Conjurer', 'Diviner', 'Enchanter', 'Illusionist', 'Invoker', 'Necromancer', 'Transmuter',
] as const

export const ALIGNMENTS = [
  'Lawful Good', 'Neutral Good', 'Chaotic Good',
  'Lawful Neutral', 'True Neutral', 'Chaotic Neutral',
  'Lawful Evil', 'Neutral Evil', 'Chaotic Evil',
] as const

export const SAVE_CATEGORIES: Array<{ key: SaveKey; label: string }> = [
  { key: 'paralyzation', label: 'Paralyzation / Poison / Death' },
  { key: 'rod', label: 'Rod / Staff / Wand' },
  { key: 'petrification', label: 'Petrification / Polymorph' },
  { key: 'breath', label: 'Breath Weapon' },
  { key: 'spell', label: 'Spell' },
]

export type SaveKey = 'paralyzation' | 'rod' | 'petrification' | 'breath' | 'spell'

export const PRIEST_SPHERES = [
  'All', 'Animal', 'Astral', 'Charm', 'Combat', 'Creation', 'Divination', 'Elemental',
  'Guardian', 'Healing', 'Necromantic', 'Plant', 'Protection', 'Summoning', 'Sun', 'Weather',
]

export const WEAPON_PROFICIENCY_SUGGESTIONS = [
  'Long sword', 'Short sword', 'Bastard sword', 'Two-handed sword', 'Battle axe', 'Hand axe',
  'Dagger', 'Spear', 'Halberd', 'Morning star', 'Mace', 'Warhammer', 'Club', 'Quarterstaff',
  'Long bow', 'Short bow', 'Crossbow, light', 'Crossbow, heavy', 'Sling', 'Dart', 'Javelin',
  'Lance', 'Flail',
]

export const NONWEAPON_PROFICIENCY_SUGGESTIONS = [
  'Agriculture', 'Animal Handling', 'Animal Lore', 'Animal Training', 'Artistic Ability',
  'Astrology', 'Blacksmithing', 'Blind-fighting', 'Brewing', 'Carpentry', 'Cooking', 'Dancing',
  'Direction Sense', 'Endurance', 'Engineering', 'Etiquette', 'Fire-building', 'Fishing',
  'Healing', 'Heraldry', 'Herbalism', 'Hunting', 'Jumping', 'Languages, Ancient',
  'Languages, Modern', 'Leatherworking', 'Local History', 'Mining', 'Mountaineering',
  'Musical Instrument', 'Navigation', 'Pottery', 'Reading/Writing', 'Religion',
  'Riding, Land-based', 'Rope Use', 'Running', 'Seamanship', 'Set Snares', 'Singing',
  'Spellcraft', 'Stonemasonry', 'Survival', 'Swimming', 'Tracking', 'Weather Sense', 'Weaving',
]

export const CONDITIONS_2E = [
  'Blinded', 'Charmed', 'Confused', 'Cursed', 'Diseased', 'Feebleminded', 'Held',
  'Invisible', 'Paralyzed', 'Petrified', 'Poisoned', 'Silenced', 'Sleeping', 'Slowed',
  'Hasted', 'Unconscious', 'Dying', 'Dead', 'Fear', 'Berserk',
]

export const DEATH_THRESHOLD = -10

export type ClassGroup = 'warrior' | 'priest' | 'rogue' | 'wizard'

export function normalizeClass(characterClass: string): string {
  if ((SPECIALIST_SCHOOLS as readonly string[]).includes(characterClass) || characterClass === 'Wizard' || characterClass === 'Illusionist') {
    return 'Mage'
  }
  if (characterClass === 'Rogue') return 'Thief'
  if (characterClass === 'Priest') return 'Cleric'
  return characterClass
}

export function classGroup(characterClass: string): ClassGroup {
  const c = normalizeClass(characterClass)
  if (c === 'Fighter' || c === 'Paladin' || c === 'Ranger') return 'warrior'
  if (c === 'Cleric' || c === 'Druid') return 'priest'
  if (c === 'Thief' || c === 'Bard') return 'rogue'
  return 'wizard'
}

export function isSpecialist(characterClass: string, subclass?: string | null): boolean {
  if ((SPECIALIST_SCHOOLS as readonly string[]).includes(characterClass)) return true
  return !!subclass && (SPECIALIST_SCHOOLS as readonly string[]).includes(subclass)
}

export function hitDie(characterClass: string): string {
  switch (classGroup(characterClass)) {
    case 'warrior': return 'd10'
    case 'priest': return 'd8'
    case 'rogue': return 'd6'
    default: return 'd4'
  }
}

export function movementRate(race: string): number {
  return race === 'Dwarf' || race === 'Gnome' || race === 'Halfling' ? 6 : 12
}

export function thac0(characterClass: string, level: number): number {
  const lv = Math.max(1, Math.min(20, level))
  switch (classGroup(characterClass)) {
    case 'warrior':
      return 21 - lv
    case 'priest':
      if (lv <= 3) return 20
      if (lv <= 6) return 18
      if (lv <= 9) return 16
      if (lv <= 12) return 14
      if (lv <= 15) return 12
      if (lv <= 18) return 10
      return 8
    case 'rogue':
      if (lv <= 4) return 20
      if (lv <= 8) return 19
      if (lv <= 12) return 16
      if (lv <= 16) return 14
      return 12
    default:
      if (lv <= 5) return 20
      if (lv <= 10) return 19
      if (lv <= 15) return 16
      return 14
  }
}

export type SavingThrows = Record<SaveKey, number>

export function savingThrows(characterClass: string, level: number): SavingThrows {
  const lv = Math.max(1, Math.min(20, level))
  const group = classGroup(characterClass)
  let row: [number, number, number, number, number]
  if (group === 'warrior') {
    if (lv <= 2) row = [14, 16, 15, 17, 17]
    else if (lv <= 4) row = [13, 15, 14, 16, 16]
    else if (lv <= 6) row = [11, 13, 12, 13, 14]
    else if (lv <= 8) row = [10, 12, 11, 12, 13]
    else if (lv <= 10) row = [8, 10, 9, 9, 11]
    else if (lv <= 12) row = [7, 9, 8, 8, 10]
    else if (lv <= 14) row = [5, 7, 6, 5, 8]
    else if (lv <= 16) row = [4, 6, 5, 4, 7]
    else row = [3, 5, 4, 4, 6]
  } else if (group === 'priest') {
    if (lv <= 3) row = [10, 14, 13, 16, 15]
    else if (lv <= 6) row = [9, 13, 12, 15, 14]
    else if (lv <= 9) row = [7, 11, 10, 13, 12]
    else if (lv <= 12) row = [6, 10, 9, 12, 11]
    else if (lv <= 15) row = [5, 9, 8, 11, 10]
    else if (lv <= 18) row = [4, 8, 7, 10, 9]
    else row = [2, 6, 5, 8, 7]
  } else if (group === 'rogue') {
    if (lv <= 4) row = [13, 14, 12, 16, 15]
    else if (lv <= 8) row = [12, 12, 11, 15, 13]
    else if (lv <= 12) row = [11, 10, 10, 14, 11]
    else if (lv <= 16) row = [10, 8, 9, 13, 9]
    else row = [9, 6, 8, 12, 7]
  } else {
    if (lv <= 5) row = [14, 11, 13, 15, 12]
    else if (lv <= 10) row = [13, 9, 11, 13, 10]
    else if (lv <= 15) row = [11, 7, 9, 11, 8]
    else row = [10, 5, 7, 9, 6]
  }
  return { paralyzation: row[0], rod: row[1], petrification: row[2], breath: row[3], spell: row[4] }
}

export function strengthAdjustments(score: number, exceptional?: string | null): { hit: number; damage: number } {
  if (score <= 1) return { hit: -5, damage: -4 }
  if (score === 2) return { hit: -3, damage: -2 }
  if (score === 3) return { hit: -3, damage: -1 }
  if (score <= 5) return { hit: -2, damage: -1 }
  if (score <= 7) return { hit: -1, damage: 0 }
  if (score <= 15) return { hit: 0, damage: 0 }
  if (score === 16) return { hit: 0, damage: 1 }
  if (score === 17) return { hit: 1, damage: 1 }
  if (score === 18) {
    const exc = parseExceptional(exceptional)
    if (exc === null) return { hit: 1, damage: 2 }
    if (exc <= 50) return { hit: 1, damage: 3 }
    if (exc <= 75) return { hit: 2, damage: 3 }
    if (exc <= 90) return { hit: 2, damage: 4 }
    if (exc <= 99) return { hit: 3, damage: 5 }
    return { hit: 3, damage: 6 }
  }
  if (score === 19) return { hit: 3, damage: 7 }
  if (score === 20) return { hit: 3, damage: 8 }
  return { hit: 4, damage: 9 }
}

export function dexterityAdjustments(score: number): { reaction: number; missile: number; defensive: number } {
  if (score <= 1) return { reaction: -6, missile: -6, defensive: 5 }
  if (score === 2) return { reaction: -4, missile: -4, defensive: 5 }
  if (score === 3) return { reaction: -3, missile: -3, defensive: 4 }
  if (score <= 5) return { reaction: -2, missile: -2, defensive: 3 }
  if (score === 6) return { reaction: -1, missile: -1, defensive: 2 }
  if (score <= 14) return { reaction: 0, missile: 0, defensive: 0 }
  if (score === 15) return { reaction: 0, missile: 0, defensive: -1 }
  if (score === 16) return { reaction: 1, missile: 1, defensive: -2 }
  if (score === 17) return { reaction: 2, missile: 2, defensive: -3 }
  if (score === 18) return { reaction: 2, missile: 2, defensive: -4 }
  if (score === 19) return { reaction: 3, missile: 3, defensive: -4 }
  return { reaction: 3, missile: 3, defensive: -5 }
}

export function constitutionHpAdjustment(score: number, characterClass = 'Fighter'): number {
  const warrior = classGroup(characterClass) === 'warrior'
  if (score <= 1) return -3
  if (score <= 3) return -2
  if (score <= 6) return -1
  if (score <= 14) return 0
  if (score === 15) return 1
  if (score === 16) return 2
  if (score === 17) return warrior ? 3 : 2
  if (score === 18) return warrior ? 4 : 2
  return warrior ? 5 : 2
}

export function wisdomMagicalDefense(score: number): number {
  if (score <= 1) return -6
  if (score === 2) return -4
  if (score === 3) return -3
  if (score <= 5) return -2
  if (score <= 7) return -1
  if (score <= 14) return 0
  if (score === 15) return 1
  if (score === 16) return 2
  if (score === 17) return 3
  return 4
}

export function charismaAdjustments(score: number): { max_henchmen: number; loyalty: number; reaction: number } {
  if (score <= 2) return { max_henchmen: 1, loyalty: -8, reaction: -7 }
  if (score === 3) return { max_henchmen: 1, loyalty: -6, reaction: -5 }
  if (score <= 5) return { max_henchmen: 2, loyalty: -4, reaction: -3 }
  if (score <= 7) return { max_henchmen: 3, loyalty: -2, reaction: -1 }
  if (score <= 11) return { max_henchmen: 4, loyalty: 0, reaction: 0 }
  if (score === 12) return { max_henchmen: 5, loyalty: 0, reaction: 0 }
  if (score === 13) return { max_henchmen: 5, loyalty: 0, reaction: 1 }
  if (score === 14) return { max_henchmen: 6, loyalty: 1, reaction: 2 }
  if (score === 15) return { max_henchmen: 7, loyalty: 3, reaction: 3 }
  if (score === 16) return { max_henchmen: 8, loyalty: 4, reaction: 5 }
  if (score === 17) return { max_henchmen: 10, loyalty: 6, reaction: 6 }
  return { max_henchmen: 15, loyalty: 8, reaction: 7 }
}

export function primaryAdjustment(ability: string, score: number, exceptional?: string | null, characterClass = 'Fighter'): number {
  switch (ability) {
    case 'strength': return strengthAdjustments(score, exceptional).hit
    case 'dexterity': return dexterityAdjustments(score).missile
    case 'constitution': return constitutionHpAdjustment(score, characterClass)
    case 'intelligence': return score <= 8 ? -1 : score >= 16 ? 3 : score >= 12 ? 1 : 0
    case 'wisdom': return wisdomMagicalDefense(score)
    case 'charisma': return charismaAdjustments(score).reaction
    default: return 0
  }
}

export function formatSigned(n: number): string {
  return n >= 0 ? `+${n}` : `${n}`
}

export function numberNeededToHit(characterThac0: number, armorClass: number): number {
  return characterThac0 - armorClass
}

export function resolveAttack(characterThac0: number, armorClass: number, roll: number) {
  const needed = numberNeededToHit(characterThac0, armorClass)
  const automatic = roll === 1 || roll === 20
  const hit = roll === 20 || (roll !== 1 && roll >= needed)
  return { hit, needed, roll, automatic }
}

export function resolveInitiative(d10: number, dexterity: number, otherModifiers = 0) {
  const reaction = dexterityAdjustments(dexterity).reaction
  return { roll: d10, modifier: -reaction + otherModifiers, total: d10 - reaction + otherModifiers }
}

export function vitalityState(currentHp: number): 'ok' | 'unconscious' | 'dying' | 'dead' {
  if (currentHp <= DEATH_THRESHOLD) return 'dead'
  if (currentHp < 0) return 'dying'
  if (currentHp === 0) return 'unconscious'
  return 'ok'
}

export function isCaster(characterClass: string, level: number): boolean {
  const c = normalizeClass(characterClass)
  if (c === 'Mage' || c === 'Cleric' || c === 'Druid' || c === 'Bard') return true
  if (c === 'Paladin') return level >= 9
  if (c === 'Ranger') return level >= 8
  return false
}

function parseExceptional(exceptional?: string | null): number | null {
  if (!exceptional) return null
  const v = exceptional.toUpperCase().trim()
  if (v === '00' || v === '100') return 100
  if (!/^\d{1,3}$/.test(v)) return null
  return parseInt(v, 10)
}
